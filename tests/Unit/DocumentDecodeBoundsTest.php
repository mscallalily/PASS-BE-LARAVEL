<?php

namespace Tests\Unit;

use App\Services\LicenseVerification\LicenseDocumentReader;
use PHPUnit\Framework\TestCase;

class DocumentDecodeBoundsTest extends TestCase
{
    public function test_large_bitmap_header_is_rejected_before_decode(): void
    {
        $this->checkBounds(50000, 50000);
    }

    public function test_extremely_tall_narrow_bitmap_does_not_trigger_an_unbounded_upscale(): void
    {
        $this->checkBounds(20, 50000);
    }

    private function checkBounds(int $width, int $height): void
    {
        $path = tempnam(sys_get_temp_dir(), 'pass-bounds-');
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aFikAAAAASUVORK5CYII=');
        $png = substr_replace($png, pack('NN', $width, $height), 16, 8);
        file_put_contents($path, $png);
        try {
            $result = (new LicenseDocumentReader)->read($path, 'image/png');
            $this->assertFalse($result['engine_available']);
            $this->assertSame('', $result['text']);
            $this->assertStringContainsString('20 megapixels', $result['engine_error']);
        } finally { unlink($path); }
    }
}
