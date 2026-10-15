<?php

namespace Tests\Unit;

use App\Services\LicenseVerification\LicenseValidationRules;
use PHPUnit\Framework\TestCase;

class LicenseValidationRulesTest extends TestCase
{
    private const CARD_TEXT = <<<TXT
        REPUBLIC OF THE PHILIPPINES
        DEPARTMENT OF TRANSPORTATION
        LAND TRANSPORTATION OFFICE
        NON-PROFESSIONAL DRIVER'S LICENSE
        Last Name, First Name, Middle Name
        DELA CRUZ, JUAN SANTOS
        Nationality Sex Date of Birth Weight(kg) Height(m)
        PHL M 1995/05/14 70 1.70
        License No. Expiration Date Agency Code
        N01-23-456789 2030/05/14 N01
        Blood Type Eyes Color Restrictions Conditions
        TXT;

    private function document(array $overrides = []): array
    {
        return $overrides + [
            'kind' => 'image',
            'engine_available' => true,
            'engine_error' => null,
            'text' => self::CARD_TEXT,
            'confidence' => 92.0,
            'width' => 1700,
            'height' => 1071,
            'sharpness' => 1200.0,
        ];
    }

    private function submitted(array $overrides = []): array
    {
        return $overrides + [
            'license_number' => 'N01-23-456789',
            'expiry_date' => '2030-05-14',
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'today' => '2026-10-13',
        ];
    }

    private function evaluate(array $document = [], array $submitted = []): array
    {
        return (new LicenseValidationRules())->evaluate($this->document($document), $this->submitted($submitted));
    }

    public function test_matching_license_goes_to_pending_review_never_verified(): void
    {
        $result = $this->evaluate();

        $this->assertSame('pending_review', $result['decision']);
        $this->assertSame([], $result['reasons']);
        $this->assertTrue($result['preliminary']);
        $this->assertSame('unavailable', $result['official_verification']);
        foreach (['document_type', 'license_number', 'expiry_date', 'name'] as $check) {
            $this->assertSame('pass', $result['checks'][$check]['result'], $check);
        }
    }

    public function test_license_number_mismatch_is_returned(): void
    {
        $result = $this->evaluate([], ['license_number' => 'N09-99-111222']);

        $this->assertSame('needs_correction', $result['decision']);
        $this->assertStringContainsString('N01-23-456789', $result['reasons'][0]);
        $this->assertSame('fail', $result['checks']['license_number']['result']);
    }

    public function test_single_character_difference_is_left_for_staff(): void
    {
        $result = $this->evaluate([], ['license_number' => 'N01-23-456788']);

        $this->assertSame('pending_review', $result['decision']);
        $this->assertSame('uncertain', $result['checks']['license_number']['result']);
    }

    public function test_ocr_digit_confusions_are_corrected(): void
    {
        $text = str_replace('N01-23-456789', 'NO1-23-4S6789', self::CARD_TEXT);
        $result = $this->evaluate(['text' => $text]);

        $this->assertSame('pass', $result['checks']['license_number']['result']);
    }

    public function test_expiry_mismatch_is_returned(): void
    {
        $result = $this->evaluate([], ['expiry_date' => '2031-05-14']);

        $this->assertSame('needs_correction', $result['decision']);
        $this->assertSame('fail', $result['checks']['expiry_date']['result']);
    }

    public function test_expiry_mismatch_with_low_confidence_is_left_for_staff(): void
    {
        $result = $this->evaluate(['confidence' => 55.0], ['expiry_date' => '2031-05-14']);

        $this->assertSame('pending_review', $result['decision']);
        $this->assertSame('uncertain', $result['checks']['expiry_date']['result']);
    }

    public function test_expired_license_on_document_is_returned(): void
    {
        $text = str_replace('2030/05/14', '2025/05/14', self::CARD_TEXT);
        $result = $this->evaluate(['text' => $text], ['expiry_date' => '2025-05-14', 'today' => '2026-10-13']);

        $this->assertSame('needs_correction', $result['decision']);
        $this->assertStringContainsString('expired', implode(' ', $result['reasons']));
    }

    public function test_name_mismatch_is_returned_when_card_was_read_well(): void
    {
        $result = $this->evaluate([], ['first_name' => 'Maria', 'last_name' => 'Villanueva']);

        $this->assertSame('needs_correction', $result['decision']);
        $this->assertSame('fail', $result['checks']['name']['result']);
    }

    public function test_name_mismatch_is_uncertain_when_number_was_not_confirmed(): void
    {
        $text = str_replace('N01-23-456789', '', self::CARD_TEXT);
        $result = $this->evaluate(['text' => $text], ['first_name' => 'Maria', 'last_name' => 'Villanueva']);

        $this->assertSame('pending_review', $result['decision']);
        $this->assertSame('uncertain', $result['checks']['name']['result']);
    }

    public function test_one_character_name_misread_still_matches(): void
    {
        $text = str_replace('DELA CRUZ, JUAN', 'DELA CRUS, JUAN', self::CARD_TEXT);
        $result = $this->evaluate(['text' => $text]);

        $this->assertSame('pass', $result['checks']['name']['result']);
    }

    public function test_unrelated_document_is_returned(): void
    {
        $receipt = "SARI-SARI GROCERY STORE\nOFFICIAL RECEIPT NO. 004512\nRice 5kg 320.00\nCooking oil 145.00\n"
            . "Canned sardines 66.00\nInstant noodles 150.00\nTOTAL DUE 861.00\nThank you for shopping with us";
        $result = $this->evaluate(['text' => $receipt, 'confidence' => 88.0]);

        $this->assertSame('needs_correction', $result['decision']);
        $this->assertSame('fail', $result['checks']['document_type']['result']);
    }

    public function test_blurry_image_is_returned(): void
    {
        $result = $this->evaluate(['text' => 'Nee Fe See mite Ne DELA CRUZ', 'confidence' => 40.0, 'sharpness' => 12.0]);

        $this->assertSame('needs_correction', $result['decision']);
        $this->assertSame('fail', $result['checks']['image_quality']['result']);
        $this->assertStringContainsString('blurry', $result['reasons'][0]);
    }

    public function test_blurry_image_with_unreadable_number_is_returned_even_if_dates_read(): void
    {
        $result = $this->evaluate(['text' => "DELA CRUZ, JUAN\n7NO1 23.4567\n2030/05/14", 'confidence' => 60.0, 'sharpness' => 4.0]);

        $this->assertSame('needs_correction', $result['decision']);
        $this->assertSame('fail', $result['checks']['image_quality']['result']);
    }

    public function test_blurry_image_with_readable_number_is_left_for_staff(): void
    {
        $result = $this->evaluate(['text' => "DELA CRUZ, JUAN\nN01-23-456789\n2030/05/14", 'confidence' => 75.0, 'sharpness' => 7.0]);

        $this->assertSame('pending_review', $result['decision']);
        $this->assertSame('pass', $result['checks']['image_quality']['result']);
    }

    public function test_first_character_digit_misread_is_corrected(): void
    {
        $text = str_replace('N01-23-456789', '801-23-456789', self::CARD_TEXT);
        $result = $this->evaluate(['text' => $text], ['license_number' => 'B01-23-456789']);

        $this->assertSame('pass', $result['checks']['license_number']['result']);
    }

    public function test_low_resolution_image_is_returned(): void
    {
        $result = $this->evaluate(['width' => 320, 'height' => 200, 'text' => '', 'confidence' => null]);

        $this->assertSame('needs_correction', $result['decision']);
        $this->assertStringContainsString('resolution', $result['reasons'][0]);
    }

    public function test_sharp_image_with_little_text_is_left_for_staff(): void
    {
        $result = $this->evaluate(['text' => 'LICENSE', 'confidence' => 35.0]);

        $this->assertSame('pending_review', $result['decision']);
    }

    public function test_unavailable_ocr_goes_to_review_not_rejection(): void
    {
        $result = $this->evaluate(['engine_available' => false, 'engine_error' => 'The OCR engine is not available on the server.', 'text' => '', 'confidence' => null]);

        $this->assertSame('pending_review', $result['decision']);
        $this->assertSame('uncertain', $result['checks']['text_extraction']['result']);
    }

    public function test_scanned_pdf_without_text_goes_to_review(): void
    {
        $result = $this->evaluate(['kind' => 'pdf', 'engine_available' => false, 'engine_error' => 'The PDF has no text layer.', 'text' => '', 'confidence' => null, 'width' => null, 'height' => null, 'sharpness' => null]);

        $this->assertSame('pending_review', $result['decision']);
    }

    public function test_license_number_helpers(): void
    {
        $this->assertTrue(LicenseValidationRules::isLtoFormat('n01-23-456789'));
        $this->assertTrue(LicenseValidationRules::isLtoFormat('N0123456789'));
        $this->assertFalse(LicenseValidationRules::isLtoFormat('123-45-678901'));
        $this->assertFalse(LicenseValidationRules::isLtoFormat('N01-23-4567'));
        $this->assertSame('N01-23-456789', LicenseValidationRules::formatLicenseNumber('n01 23 456789'));
    }
}
