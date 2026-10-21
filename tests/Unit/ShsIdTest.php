<?php

namespace Tests\Unit;

use App\Services\Registration\InstitutionalIdReader;
use App\Services\Registration\InstitutionalIdRules;
use App\Services\Registration\ShsIdReader;
use Tests\TestCase;

class ShsIdTest extends TestCase
{
    public function test_lrn_is_twelve_digits_without_a_college_year_and_keeps_zero(): void
    {
        foreach (['000123456789', '0001 2345 6789', '9999-1234-5678'] as $n) $this->assertNull(InstitutionalIdRules::idProblem('shs', $n, 2026));
        foreach (['', '00012345678', '0001234567890', 'SHS-24-001', '00012345A789'] as $n) $this->assertNotNull(InstitutionalIdRules::idProblem('shs', $n, 2026));
        $this->assertSame('000123456789', InstitutionalIdRules::format('shs', '0001-2345-6789'));
        $this->assertSame(['000123456789'], InstitutionalIdReader::numbers('LRN: OOO1 2345 6789', 'shs'));
        $this->assertSame([], InstitutionalIdReader::numbers('10001234567890', 'shs'));
        $this->assertSame([], InstitutionalIdReader::numbers('1 0001 2345 6789', 'shs'));
        $this->assertSame([], InstitutionalIdReader::numbers('2026-00123', 'shs'));
    }

    public function test_name_reader_keeps_both_given_names_and_an_initial_without_name_dictionary(): void
    {
        $read = ['words' => [['text' => 'ALEXANDRIANA', 'confidence' => 96], ['text' => 'CHRISTOPHER', 'confidence' => 94], ['text' => 'P.', 'confidence' => 92], ['text' => '|', 'confidence' => 95]]];
        $r = ShsIdReader::candidate($read, 'first_name');
        $this->assertSame(['middle_initial' => 'P.', 'first_name' => 'ALEXANDRIANA CHRISTOPHER'], $r['fields']);
        $this->assertSame(94.0, $r['confidence']); $this->assertSame(92.0, $r['initial_confidence']);
        $read['words'][1] = ['text' => 'CHR1STOPHER', 'confidence' => 20];
        $this->assertNull(ShsIdReader::candidate($read, 'first_name'));
        $this->assertNull(ShsIdReader::candidate(['words' => [['text' => 'GRADE', 'confidence' => 99]]], 'first_name'));
    }

    public function test_low_initial_confidence_and_ambiguous_lrn_are_not_promoted(): void
    {
        $r = ShsIdReader::candidate(['words' => [['text' => 'ALYSSA', 'confidence' => 96], ['text' => 'BEATRICE', 'confidence' => 96], ['text' => 'P.', 'confidence' => 25]]], 'first_name');
        $this->assertSame(25.0, $r['initial_confidence']);
        $this->assertNull(ShsIdReader::candidate(['text' => '000123456789 000123456788', 'confidence' => 99], 'user_code'));
    }

    public function test_shs_document_requires_both_school_and_category_and_matching_complete_name(): void
    {
        $fields = ['first_name' => 'ALYSSA BEATRICE', 'last_name' => 'DELA CRUZ', 'middle_initial' => 'P.', 'user_code' => '000123456789'];
        $e = ['kind' => 'image', 'engine_available' => true, 'width' => 1000, 'height' => 1600, 'sharpness' => 200, 'school_marker' => true, 'shs_marker' => true,
            'fields' => $fields, 'field_confidence' => ['first_name' => 95, 'last_name' => 95, 'middle_initial' => 95, 'user_code' => 95]];
        $rules = new InstitutionalIdRules;
        $r = $rules->evaluate('shs', $e, $fields);
        $this->assertSame('matched', $r['status']); $this->assertTrue($r['requires_staff_approval']); $this->assertSame(InstitutionalIdRules::SHS_POLICY, $r['policy_version']);
        foreach (['shs_marker', 'school_marker'] as $marker) $this->assertSame('manual_review', $rules->evaluate('shs', array_replace($e, [$marker => false]), $fields)['status']);
        $this->assertSame('needs_correction', $rules->evaluate('shs', $e, array_replace($fields, ['first_name' => 'ALYSSA']))['status']);
        $this->assertSame('manual_review', $rules->evaluate('shs', array_replace($e, ['width' => 409]), $fields)['status']);
    }

    public function test_category_text_cannot_be_inferred_from_lrn_or_unrelated_school_text(): void
    {
        foreach (['SENIOR HIGH SCHOOL', 'Senior High Sch00l', 'SENIOR HIGH SCHOOE'] as $text) $this->assertTrue(ShsIdReader::hasShsText($text), $text);
        foreach (['HIGH SCHOOL', 'JUNIOR HIGH SCHOOL', 'DYCI BASIC EDUCATION', 'LRN 000123456789'] as $text) $this->assertFalse(ShsIdReader::hasShsText($text), $text);
    }

    public function test_conflicting_suffixes_on_separate_name_rows_cannot_claim_a_match(): void
    {
        $read = fn ($text) => ['text' => $text, 'words' => array_map(fn ($word) => ['text' => $word, 'confidence' => 95], explode(' ', $text)), 'confidence' => 95];
        $reader = \Mockery::mock(\App\Services\LicenseVerification\LicenseDocumentReader::class);
        $reader->shouldReceive('read')->once()->andReturn(['kind' => 'image', 'engine_available' => true, 'width' => 1000, 'height' => 1600,
            'sharpness' => 200, 'confidence' => 95, 'text' => "Dr Yanga Colleges Inc\nSENIOR HIGH SCHOOL"]);
        $reader->shouldReceive('readTextImage')->andReturn(
            $read('ALYSSA BEATRICE P. JR.'), $read('ALYSSA BEATRICE P. JR.'), $read('DELA CRUZ SR.'), $read('DELA CRUZ SR.'),
            $read('000123456789'), $read('000123456789'), $read('Yanga Colleges'), $read('Yanga Colleges'),
            $read('SENIOR HIGH SCHOOL'), $read('SENIOR HIGH SCHOOL'), $read('SENIOR HIGH SCHOOL'));
        $path = tempnam(sys_get_temp_dir(), 'shs_test_'); $image = imagecreatetruecolor(1000, 1600); imagefill($image, 0, 0, 0xFFFFFF); imagepng($image, $path); imagedestroy($image);
        try {
            $e = (new ShsIdReader($reader))->read($path, 'image/png');
            $this->assertTrue($e['field_conflicts']['suffix']);
            $this->assertSame('manual_review', (new InstitutionalIdRules)->evaluate('shs', $e, $e['fields'])['status']);
        } finally { @unlink($path); }
    }

    public function test_missing_optional_initial_and_suffix_do_not_cause_review_and_review_copy_identifies_matched_text(): void
    {
        $fields = ['first_name' => 'ALYSSA BEATRICE', 'last_name' => 'DELA CRUZ', 'user_code' => '000123456789'];
        $e = ['kind' => 'image', 'engine_available' => true, 'width' => 1000, 'height' => 1600, 'sharpness' => 200, 'school_marker' => true, 'shs_marker' => true,
            'fields' => $fields, 'field_confidence' => ['first_name' => 95, 'last_name' => 95, 'user_code' => 95]];
        $rules = new InstitutionalIdRules;
        $submitted = $fields + ['middle_initial' => '', 'suffix' => ''];
        $r = $rules->evaluate('shs', $e, $submitted);
        $this->assertSame('matched', $r['status']);
        $this->assertArrayNotHasKey('middle_initial', $r['checks']); $this->assertArrayNotHasKey('suffix', $r['checks']);
        $r = $rules->evaluate('shs', array_replace($e, ['school_marker' => false]), $submitted);
        $public = \App\Services\Registration\PublicIdCheck::summary($r);
        $this->assertSame('manual_review', $public['status']); $this->assertTrue($public['identity_matched']);
        $this->assertStringContainsString('name and LRN match', $public['message']);
        $this->assertArrayNotHasKey('checks', $public);
        $r = $rules->evaluate('shs', $e, array_replace($submitted, ['first_name' => 'SOMEONE ELSE']));
        $this->assertSame('needs_correction', $r['status']);
        $this->assertFalse(\App\Services\Registration\PublicIdCheck::summary($r)['identity_matched']);
        $r = $rules->evaluate('shs', $e, array_replace($submitted, ['suffix' => 'JR.']));
        $this->assertSame('manual_review', $r['status']); // An entered suffix still needs document evidence.
    }
}
