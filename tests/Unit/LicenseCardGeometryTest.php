<?php

namespace Tests\Unit;

use App\Services\LicenseVerification\LicenseDocumentReader;
use PHPUnit\Framework\TestCase;

class LicenseCardGeometryTest extends TestCase
{
    /** A light card with dark "text lines", optionally tilted on a dark table. */
    private function card(float $tilt = 0.0, bool $onTable = true): \GdImage
    {
        $card = imagecreatetruecolor(1000, 630);
        imagefill($card, 0, 0, imagecolorallocate($card, 235, 232, 228));
        $ink = imagecolorallocate($card, 30, 30, 30);
        for ($y = 90; $y < 580; $y += 45) imagefilledrectangle($card, 300, $y, 300 + (($y * 37) % 500) + 150, $y + 14, $ink);
        if (!$onTable) return $card;
        $table = imagecolorallocate($card, 60, 45, 35);
        $card = $tilt ? imagerotate($card, $tilt, $table) : $card;
        $photo = imagecreatetruecolor(1500, 1000);
        imagefill($photo, 0, 0, imagecolorallocate($photo, 60, 45, 35));
        imagecopy($photo, $card, (int) ((1500 - imagesx($card)) / 2), (int) ((1000 - imagesy($card)) / 2), 0, 0, imagesx($card), imagesy($card));
        imagefilter($photo, IMG_FILTER_GRAYSCALE);
        return $photo;
    }

    public function test_tilted_card_on_a_table_is_straightened_and_cropped(): void
    {
        $geometry = LicenseDocumentReader::cardGeometry($this->card(5.0));
        $this->assertNotNull($geometry);
        $this->assertEqualsWithDelta(-5.0, $geometry['skew'], 1.0);
        [, , $width, $height] = $geometry['crop'];
        $this->assertLessThan(.9, $width * $height);
        $straight = LicenseDocumentReader::applyGeometry($this->card(5.0), $geometry);
        $this->assertEqualsWithDelta(1000 / 630, imagesx($straight) / imagesy($straight), .2);
    }

    public function test_straight_card_on_a_table_is_only_cropped(): void
    {
        $geometry = LicenseDocumentReader::cardGeometry($this->card());
        $this->assertSame(0.0, $geometry['skew']);
        $this->assertEqualsWithDelta(1000 / 1500, $geometry['crop'][2], .05);
    }

    public function test_card_filling_the_photo_is_left_alone(): void
    {
        $card = $this->card(onTable: false);
        imagefilter($card, IMG_FILTER_GRAYSCALE);
        $this->assertNull(LicenseDocumentReader::cardGeometry($card));
    }
}
