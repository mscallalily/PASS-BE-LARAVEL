<?php

namespace Tests\Unit;

use App\Services\LicenseVerification\LicenseDocumentReader;
use App\Services\Registration\InstitutionalIdReader;
use App\Services\Registration\InstitutionalIdRules;
use App\Services\Registration\InstitutionalIdSignals;
use App\Services\Registration\InstitutionalSchoolBranding;
use App\Services\Registration\ShsIdReader;
use Tests\TestCase;

class InstitutionalSchoolBrandingTest extends TestCase
{
    public function test_official_name_is_recognized_without_requiring_the_acronym(): void
    {
        foreach (["Dr. Yanga's Colleges, Inc.", 'DR. YANGA’S COLLEGES, INC.', "Dr.\nYanga's\nColleges, Inc.", 'Dr Yanga C0lleges Inc', 'www.dyci.edu.ph'] as $text) {
            $this->assertTrue(InstitutionalIdSignals::school($text), $text);
        }
        $this->assertSame("Dr. Yanga's Colleges, Inc.", InstitutionalIdSignals::SCHOOL_NAME);
    }

    public function test_unrelated_or_incomplete_branding_cannot_become_a_school_match(): void
    {
        foreach (['DYCI', 'COLLEGE DEPARTMENT', 'EMPLOYEE', 'SENIOR HIGH SCHOOL', 'Other Colleges Inc.', 'Yanga', 'Student 2024-00123', 'www.dyci.edu.com'] as $text) {
            $this->assertFalse(InstitutionalIdSignals::school($text), $text);
        }
    }

    public function test_all_three_categories_use_the_official_name_and_preserve_review_rules(): void
    {
        foreach (['college' => '202400123', 'employee' => '0221234', 'shs' => '000123456789'] as $category => $id) {
            $fields = ['user_code' => $id, 'first_name' => 'MARIA BEATRICE', 'last_name' => 'DELA CRUZ', 'middle_initial' => '', 'suffix' => ''];
            $e = ['kind' => 'image', 'engine_available' => true, 'width' => 1000, 'height' => 1600, 'sharpness' => 100,
                'school_marker' => InstitutionalIdSignals::school("Dr. Yanga's Colleges, Inc."), 'shs_marker' => true,
                'fields' => $fields, 'field_confidence' => ['user_code' => 95, 'first_name' => 95, 'last_name' => 95]];
            $rules = new InstitutionalIdRules;
            $matched = $rules->evaluate($category, $e, $fields);
            $this->assertSame('matched', $matched['status'], $category);
            $this->assertSame('pass', $matched['checks']['school']['result']);
            $this->assertStringContainsString(InstitutionalIdSignals::SCHOOL_NAME, $matched['checks']['school']['detail']);
            $this->assertSame($category === 'shs', $matched['requires_staff_approval']);
            $uncertain = $rules->evaluate($category, array_replace($e, ['school_marker' => false]), $fields);
            $this->assertSame('manual_review', $uncertain['status']);
            $this->assertSame('uncertain', $uncertain['checks']['school']['result']);
            $this->assertStringContainsString(InstitutionalIdSignals::SCHOOL_NAME, $uncertain['checks']['school']['detail']);
            $this->assertArrayNotHasKey('middle_initial', $matched['checks']);
            $this->assertArrayNotHasKey('suffix', $matched['checks']);
        }
    }

    public function test_fallback_requires_actual_ocr_school_evidence_and_bounds_each_call(): void
    {
        $path = $this->blankImage();
        $reader = \Mockery::mock(LicenseDocumentReader::class);
        $reader->shouldReceive('readTextImage')->once()->withArgs(fn ($file, $mode, $timeout) => is_file($file) && $mode === 6 && $timeout >= 1 && $timeout <= 2)
            ->andReturn(['engine_available' => true, 'text' => "Dr. Yanga's Colleges, Inc."]);
        try { $this->assertTrue((new InstitutionalSchoolBranding($reader))->confirm($path, 'college')); }
        finally { @unlink($path); }
    }

    public function test_unavailable_ocr_stops_without_approving_or_repeated_calls(): void
    {
        $path = $this->blankImage();
        $reader = \Mockery::mock(LicenseDocumentReader::class);
        $reader->shouldReceive('readTextImage')->once()->andReturn(['engine_available' => false, 'text' => '']);
        try { $this->assertFalse((new InstitutionalSchoolBranding($reader))->confirm($path, 'employee')); }
        finally { @unlink($path); }
    }

    public function test_blank_unrelated_text_is_rejected_after_at_most_six_calls(): void
    {
        $path = $this->blankImage(); $calls = 0;
        $reader = \Mockery::mock(LicenseDocumentReader::class);
        $reader->shouldReceive('readTextImage')->andReturnUsing(function () use (&$calls) { $calls++; return ['engine_available' => true, 'text' => 'OTHER COLLEGE']; });
        try {
            $this->assertFalse((new InstitutionalSchoolBranding($reader))->confirm($path, 'shs'));
            $this->assertSame(6, $calls);
        } finally { @unlink($path); }
    }

    public function test_expired_deadline_and_invalid_input_do_not_call_ocr(): void
    {
        $path = $this->blankImage();
        $reader = \Mockery::mock(LicenseDocumentReader::class); $reader->shouldNotReceive('readTextImage');
        try {
            $branding = new InstitutionalSchoolBranding($reader);
            $this->assertFalse($branding->confirm($path, 'shs', microtime(true) - 1));
            $this->assertFalse($branding->confirm($path, 'guest'));
            $this->assertFalse($branding->confirm($path.'.missing', 'college'));
        } finally { @unlink($path); }
    }

    public function test_college_employee_reader_delegates_to_shared_school_header_reader(): void
    {
        $path = $this->blankImage();
        $reader = \Mockery::mock(LicenseDocumentReader::class);
        $reader->shouldReceive('readTextImage')->twice()->andReturn(['engine_available' => true, 'text' => "Dr. Yanga's Colleges, Inc."]);
        $service = new InstitutionalIdReader($reader);
        $method = new \ReflectionMethod($service, 'readSchoolBranding');
        try {
            $this->assertTrue($method->invoke($service, $path, 'college'));
            $this->assertTrue($method->invoke($service, $path, 'employee'));
        } finally { @unlink($path); }
    }

    public function test_shs_uses_shared_fallback_after_original_regions_cannot_confirm_school(): void
    {
        $path = $this->blankImage(); $calls = 0;
        $reader = \Mockery::mock(LicenseDocumentReader::class);
        $reader->shouldReceive('read')->once()->andReturn(['engine_available' => true, 'width' => 1000, 'height' => 1600, 'sharpness' => 100, 'confidence' => 95, 'text' => 'SENIOR HIGH SCHOOL']);
        $reader->shouldReceive('readTextImage')->andReturnUsing(function () use (&$calls) {
            return ['engine_available' => true, 'text' => ++$calls > 11 ? "Dr. Yanga's Colleges, Inc." : '', 'words' => [], 'confidence' => 0];
        });
        try {
            $e = (new ShsIdReader($reader))->read($path, 'image/png');
            $this->assertTrue($e['school_marker']);
            $this->assertTrue($e['shs_marker']);
            $this->assertSame(12, $calls);
        } finally { @unlink($path); }
    }

    public function test_old_staff_results_get_official_school_copy_without_rewriting_decisions(): void
    {
        $old = ['status' => 'manual_review', 'checks' => ['school' => ['result' => 'uncertain', 'detail' => 'DYCI text could not be confirmed.']],
            'reasons' => ['DYCI text could not be confirmed.'], 'policy_version' => 'dyci-id-v3'];
        $display = InstitutionalIdSignals::forDisplay($old);
        $this->assertSame('manual_review', $display['status']);
        $this->assertSame('uncertain', $display['checks']['school']['result']);
        $this->assertSame(InstitutionalIdSignals::SCHOOL_NAME.' could not be confirmed on the document.', $display['checks']['school']['detail']);
        $this->assertSame([$display['checks']['school']['detail']], $display['reasons']);
        $this->assertSame('DYCI text could not be confirmed.', $old['checks']['school']['detail']);
        $this->assertNull(InstitutionalIdSignals::forDisplay(null));
        $this->assertSame([], InstitutionalIdSignals::forDisplay([]));
    }

    private function blankImage(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'school_test_');
        $image = imagecreatetruecolor(1000, 1600); imagefill($image, 0, 0, 0xFFFFFF); imagepng($image, $path); imagedestroy($image);
        return $path;
    }
}
