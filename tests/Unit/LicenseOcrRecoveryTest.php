<?php

namespace Tests\Unit;

use App\Services\LicenseVerification\LicenseDocumentReader;
use App\Services\LicenseVerification\LicenseOcrRecovery;
use PHPUnit\Framework\TestCase;

class LicenseOcrRecoveryTest extends TestCase
{
    private array $files = [];

    protected function tearDown(): void
    {
        foreach ($this->files as $path) @unlink($path);
        parent::tearDown();
    }

    private function image(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'pass_ocr_test_'); $this->files[] = $path;
        $image = imagecreatetruecolor(1800, 1100); imagepng($image, $path); imagedestroy($image);
        return $path;
    }

    private function read(string $text): array
    {
        $words = [];
        foreach (explode("\n", $text) as $i => $line) foreach (explode(' ', $line) as $word) {
            $words[] = ['text' => $word, 'confidence' => 96.0, 'line' => 'line-'.$i];
        }
        return ['text' => $text, 'words' => $words, 'confidence' => 96.0, 'kind' => 'image',
            'engine_available' => true, 'engine_error' => null, 'width' => 1800, 'height' => 1100, 'sharpness' => 150.0];
    }

    public function test_regions_recover_headings_preserve_original_evidence_and_use_short_timeouts(): void
    {
        $original = $this->read('PARTLY READ CARD');
        $header = $this->read("DEPARTMENT OF TRANSPORTATION\nLAND TRANSPORTATION OFFICE\nDRIVER'S LICENSE");
        $body = $this->read("License No. Expiration Date\nN01-23-456789 2030/05/14");
        $reader = $this->createMock(LicenseDocumentReader::class);
        $reader->expects($this->once())->method('read')->willReturn($original);
        $temporaryPaths = []; $responses = [$header, $body];
        $reader->expects($this->exactly(2))->method('readTextImage')->willReturnCallback(function ($path, $mode, $timeout) use (&$temporaryPaths, &$responses) {
            $this->assertFileExists($path); $this->assertSame(6, $mode);
            $this->assertGreaterThanOrEqual(1, $timeout); $this->assertLessThanOrEqual(3, $timeout);
            $temporaryPaths[] = $path; return array_shift($responses);
        });
        $result = (new LicenseOcrRecovery($reader))->read($this->image(), 'image/png');
        $this->assertSame($header['text']."\n".$body['text'], $result['text']);
        $this->assertSame($original['text'], $result['field_reads'][0]['text']);
        $this->assertSame(['full_gray', 'header_color', 'body_gray'], array_column($result['field_reads'], 'source'));
        $this->assertSame($original['sharpness'], $result['sharpness']);
        foreach ($temporaryPaths as $path) $this->assertFileDoesNotExist($path);
    }

    public function test_failed_additional_read_retains_original_instead_of_inventing_fields(): void
    {
        $original = $this->read('ORIGINAL READ');
        $reader = $this->createMock(LicenseDocumentReader::class);
        $reader->method('read')->willReturn($original);
        $reader->expects($this->once())->method('readTextImage')->willThrowException(new \RuntimeException('unavailable'));
        $result = (new LicenseOcrRecovery($reader))->read($this->image(), 'image/png');
        $this->assertSame($original['text'], $result['text']);
        $this->assertSame($original['confidence'], $result['confidence']);
        $this->assertCount(1, $result['field_reads']);
    }

    public function test_missing_engine_unsupported_format_or_unstructured_metadata_skip_regions(): void
    {
        $base = $this->read('ORIGINAL READ');
        $noMetadata = $base; unset($noMetadata['words'][0]['line']);
        foreach ([[$base, 'application/pdf'], [['engine_available' => false] + $base, 'image/png'],
            [['width' => 300] + $base, 'image/png'], [['width' => 1000, 'height' => 1800] + $base, 'image/png'], [$noMetadata, 'image/png']] as [$original, $mime]) {
            $reader = $this->createMock(LicenseDocumentReader::class); $reader->method('read')->willReturn($original);
            $reader->expects($this->never())->method('readTextImage');
            $this->assertSame($original, (new LicenseOcrRecovery($reader))->read('not-decoded', $mime));
        }
    }

    public function test_missing_headings_never_replace_the_whole_card_with_partial_regions(): void
    {
        $original = $this->read('ORIGINAL READ');
        $reader = $this->createMock(LicenseDocumentReader::class); $reader->method('read')->willReturn($original);
        $reader->expects($this->exactly(6))->method('readTextImage')->willReturn($this->read('NO REQUIRED FIELDS'));
        $result = (new LicenseOcrRecovery($reader))->read($this->image(), 'image/png');
        $this->assertSame($original['text'], $result['text']);
        $this->assertCount(5, $result['field_reads']);
    }

    public function test_low_confidence_long_name_gets_an_extra_bounded_region_read(): void
    {
        $original = $this->read('PARTLY READ CARD');
        $header = $this->read("DEPARTMENT OF TRANSPORTATION\nLAND TRANSPORTATION OFFICE\nDRIVER'S LICENSE");
        $body = $this->read("Last Name First Name Middle Name\nDELA CRUZ, JUAN MIGUEL SANTOS\nNationality Sex Date of Birth\nLicense No. Expiration Date\nN01-23-456789 2030/05/14");
        foreach ($body['words'] as &$word) if ($word['text'] === 'MIGUEL') $word['confidence'] = 65;
        unset($word);
        $name = $this->read("Last Name First Name Middle Name\nDELA CRUZ, JUAN MIGUEL SANTOS");
        $reader = $this->createMock(LicenseDocumentReader::class); $reader->method('read')->willReturn($original);
        $responses = [$header, $body, $name]; $paths = [];
        $reader->expects($this->exactly(3))->method('readTextImage')->willReturnCallback(function ($path, $mode, $timeout) use (&$responses, &$paths) {
            $this->assertFileExists($path); $this->assertSame(6, $mode);
            $this->assertGreaterThanOrEqual(1, $timeout); $this->assertLessThanOrEqual(3, $timeout);
            $paths[] = $path; return array_shift($responses);
        });
        $result = (new LicenseOcrRecovery($reader))->read($this->image(), 'image/png');
        $this->assertSame(['full_gray', 'header_color', 'body_gray', 'name_color'], array_column($result['field_reads'], 'source'));
        foreach ($paths as $path) $this->assertFileDoesNotExist($path);
    }
}
