<?php

namespace Tests\Unit;

use App\Services\LicenseVerification\LicenseDocumentReader;
use App\Services\Vehicles\VehiclePlateReader;
use PHPUnit\Framework\TestCase;

class VehiclePlateReaderTest extends TestCase
{
    public function test_extracts_whole_candidates_preserves_leading_zero_and_does_not_correct_letters_into_digits(): void
    {
        $this->assertSame(['ABC4321', 'AB01234', '123ABC'], VehiclePlateReader::candidates('ABC-4321 AB 01234 123 ABC'));
        $this->assertSame([], VehiclePlateReader::candidates('XABC432199 ABC O321'));
        $this->assertSame(['ABC4321'], VehiclePlateReader::candidates('ABC 4321 ABC-4321'));
    }

    public function test_split_plate_words_use_their_own_confidence_and_ambiguous_plates_are_not_auto_selected(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'vehicle_test_');
        $im = imagecreatetruecolor(800, 500);
        imagepng($im, $path);
        imagedestroy($im);
        try {
            foreach ([false, true] as $multiple) {
                $read = ['engine_available' => true, 'confidence' => 99, 'text' => $multiple ? 'ABC 4321 XYZ 0001' : 'ABC 4321', 'words' => [['text' => 'ABC', 'confidence' => 91], ['text' => '4321', 'confidence' => 93], ...($multiple ? [['text' => 'XYZ', 'confidence' => 90], ['text' => '0001', 'confidence' => 92]] : [])]];
                $engine = $this->createMock(LicenseDocumentReader::class);
                $engine->method('read')->willReturn($read);
                $engine->method('readTextImage')->willReturn($read);
                $r = (new VehiclePlateReader($engine))->read($path, 'image/png');
                $this->assertSame($multiple ? null : 'ABC4321', $r['plate_number']);
                $this->assertSame($multiple ? 'review_needed' : 'suggested', $r['status']);
                $this->assertSame(91.0, $r['candidates'][0]['confidence']);
            }
        } finally {
            @unlink($path);
        }
    }
}
