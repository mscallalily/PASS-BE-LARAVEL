<?php

namespace Tests\Unit;

use App\Services\Registration\InstitutionalNameRows;
use PHPUnit\Framework\TestCase;

class InstitutionalNameRowsTest extends TestCase
{
    public function test_complete_given_names_are_kept_and_initial_punctuation_is_separate(): void
    {
        $this->assertSame(['middle_initial' => 'D.', 'first_name' => 'ANGELICA PAULINE'], InstitutionalNameRows::fields('Angelica Pauline D .', 'first_name'));
        $this->assertSame(['middle_initial' => 'D.', 'first_name' => 'ANGELICA PAULINE'], InstitutionalNameRows::fields('Angelica Pauline D,', 'first_name'));
        $this->assertSame(['middle_initial' => 'S.', 'first_name' => 'ALEXANDRIANA CHRISTOPHER'], InstitutionalNameRows::fields('ALEXANDRIANA CHRISTOPHER S.', 'first_name'));
        $this->assertSame(['first_name' => 'ANGELICA PAULINE'], InstitutionalNameRows::fields('ANGELICA PAULINE', 'first_name'));
        $this->assertSame(['middle_initial' => 'B.', 'first_name' => 'CAROLINE'], InstitutionalNameRows::fields('Caroline B .', 'first_name'));
        // Never silently replace a questionable OCR word using a name dictionary.
        $this->assertSame(['first_name' => 'ANGELICA RAULMNE VV'], InstitutionalNameRows::fields('ANGELICA RAULMNE VV', 'first_name'));
    }

    public function test_low_initial_confidence_is_not_hidden_by_a_high_given_name_average(): void
    {
        $read = ['words' => [['text' => 'ANGELICA', 'confidence' => 98], ['text' => 'PAULINE', 'confidence' => 95], ['text' => 'D.', 'confidence' => 34]]];
        $this->assertSame(34.0, InstitutionalNameRows::initialConfidence($read, 'D.'));
        $this->assertSame(0.0, InstitutionalNameRows::initialConfidence($read, 'P.'));
    }

    public function test_projection_keeps_the_whole_name_row_without_merging_the_surname_row(): void
    {
        $image = imagecreatetruecolor(800, 100);
        imagefill($image, 0, 0, 0xFFFFFF);
        imagefilledrectangle($image, 80, 5, 600, 20, 0x101010); // neighbouring surname
        for ($x = 12; $x < 760; $x += 24) imagefilledrectangle($image, $x, 53, $x + 12, 72, 0x202020); // long given name + initial
        $band = InstitutionalNameRows::lineBand($image, .65);
        imagedestroy($image);
        $this->assertNotNull($band);
        $this->assertLessThanOrEqual(.53, $band[0]);
        $this->assertGreaterThanOrEqual(.73, $band[1]);
        $this->assertGreaterThan(.20, $band[0]);
    }

    public function test_blank_region_produces_no_guessed_name_band(): void
    {
        $image = imagecreatetruecolor(800, 100); imagefill($image, 0, 0, 0xFFFFFF);
        $this->assertNull(InstitutionalNameRows::lineBand($image, .5)); imagedestroy($image);
    }

    public function test_faint_blue_gray_name_is_not_reduced_to_only_the_darkest_fragments(): void
    {
        $image = imagecreatetruecolor(800, 100); imagefill($image, 0, 0, 0xEEEEEE);
        imagefilledrectangle($image, 80, 5, 600, 20, 0x202020);
        for ($x = 120; $x < 660; $x += 24) imagefilledrectangle($image, $x, 53, $x + 12, 72, 0x9399B1);
        $band = InstitutionalNameRows::lineBand($image, .65); imagedestroy($image);
        $this->assertNotNull($band); $this->assertLessThanOrEqual(.53, $band[0]); $this->assertGreaterThanOrEqual(.73, $band[1]); $this->assertGreaterThan(.20, $band[0]);
    }
}
