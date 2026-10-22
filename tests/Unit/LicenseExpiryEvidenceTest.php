<?php

namespace Tests\Unit;

use App\Services\LicenseVerification\LicenseValidationRules;
use PHPUnit\Framework\TestCase;

class LicenseExpiryEvidenceTest extends TestCase
{
    private const TEXT = "REPUBLIC OF THE PHILIPPINES\nDEPARTMENT OF TRANSPORTATION\nLAND TRANSPORTATION OFFICE\nDRIVER'S LICENSE\nLast Name First Name Middle Name\nDELA CRUZ, JUAN MIGUEL SANTOS\nNationality Sex Date of Birth\nPHL M 2005/08/10\nLicense No. Expiration Date Agency Code\nN01-23-456789 2030/05/14 N01\nBlood Type Eyes Color";

    private function read(string $text, array $confidence = [], string $source = 'full_gray'): array
    {
        $words = [];
        foreach (explode("\n", $text) as $i => $line) foreach (explode(' ', $line) as $word) {
            $words[] = ['text' => $word, 'line' => 'line-'.$i, 'confidence' => $confidence[$word] ?? 96.0];
        }
        return ['text' => $text, 'words' => $words, 'source' => $source];
    }

    private function evaluate(?array $reads, ?string $displayText = null): array
    {
        $document = ['kind' => 'image', 'engine_available' => true, 'text' => $displayText ?? self::TEXT,
            'confidence' => 63.5, 'width' => 845, 'height' => 525, 'sharpness' => 1291.3];
        if ($reads !== null) $document['field_reads'] = $reads;
        else $document['confidence'] = 92.0;
        return (new LicenseValidationRules)->evaluate($document, ['license_number' => 'N01-23-456789',
            'first_name' => 'JUAN MIGUEL', 'last_name' => 'DELA CRUZ', 'expiry_date' => '2030-05-14', 'today' => '2026-10-22']);
    }

    public function test_matching_region_evidence_is_used_for_the_decision_as_well_as_confidence(): void
    {
        $display = str_replace(['2030/05/14', 'JUAN MIGUEL', 'N01-23-456789'], ['', 'JUA MIG', 'O01-23-456789'], self::TEXT);
        $result = $this->evaluate([$this->read(self::TEXT)], $display);
        $this->assertSame('verified', $result['decision']);
        $this->assertSame('2030-05-14', $result['extracted']['expiry_date']);
        foreach (['name', 'license_number', 'expiry_date', 'ocr_confidence'] as $check) $this->assertSame('pass', $result['checks'][$check]['result']);
    }

    public function test_a_birth_date_is_never_reported_as_expired_when_expiry_was_not_read(): void
    {
        $text = str_replace('2030/05/14', '', self::TEXT);
        foreach ([null, [$this->read($text)]] as $reads) {
            $result = $this->evaluate($reads, $text);
            $this->assertSame('needs_correction', $result['decision']);
            $this->assertNull($result['extracted']['expiry_date']);
            $this->assertSame('uncertain', $result['checks']['expiry_date']['result']);
            $this->assertStringNotContainsString('appears to be expired', implode(' ', $result['reasons']));
        }
    }

    public function test_a_future_year_misread_in_the_birth_block_does_not_override_labeled_expiry(): void
    {
        $text = str_replace('2005/08/10', '2065/08/10', self::TEXT);
        foreach ([null, [$this->read($text)]] as $reads) {
            $result = $this->evaluate($reads, $text);
            $this->assertSame('verified', $result['decision']);
            $this->assertSame('2030-05-14', $result['extracted']['expiry_date']);
        }
    }

    public function test_conflicting_strong_expiry_readings_stay_unapproved(): void
    {
        $result = $this->evaluate([$this->read(self::TEXT), $this->read(str_replace('2030/05/14', '2031/05/14', self::TEXT), [], 'body_gray')]);
        $this->assertSame('needs_correction', $result['decision']);
        $this->assertNull($result['extracted']['expiry_date']);
        $this->assertTrue($result['identity_confidence']['expiry_conflict']);
        $this->assertSame('uncertain', $result['checks']['expiry_date']['result']);
    }

    public function test_only_a_confident_labeled_expiry_can_produce_an_expired_finding(): void
    {
        $text = str_replace('2030/05/14', '2025/05/14', self::TEXT);
        $strong = $this->evaluate([$this->read($text)]);
        $this->assertSame('needs_correction', $strong['decision']);
        $this->assertSame('2025-05-14', $strong['extracted']['expiry_date']);
        $this->assertStringContainsString('appears to be expired', implode(' ', $strong['reasons']));
        $weak = $this->evaluate([$this->read($text, ['2025/05/14' => 10.0])]);
        $this->assertSame('needs_correction', $weak['decision']);
        $this->assertNull($weak['extracted']['expiry_date']);
        $this->assertStringNotContainsString('appears to be expired', implode(' ', $weak['reasons']));
    }

    public function test_weak_matching_number_requires_review_when_name_and_expiry_are_clear(): void
    {
        $result = $this->evaluate([$this->read(self::TEXT, ['N01-23-456789' => 4.1])]);
        $this->assertSame('pending_review', $result['decision']);
        $this->assertSame('pass', $result['checks']['name']['result']);
        $this->assertSame('pass', $result['checks']['expiry_date']['result']);
        $this->assertSame('uncertain', $result['checks']['ocr_confidence']['result']);
        $this->assertSame(4.1, $result['identity_confidence']['fields']['license_number']);
        $this->assertStringNotContainsString('expired', implode(' ', $result['reasons']));
    }

    public function test_wrong_expiry_and_unlabeled_annotations_cannot_be_promoted(): void
    {
        $wrong = $this->evaluate([$this->read(str_replace('2030/05/14', '2031/05/14', self::TEXT))]);
        $this->assertSame('needs_correction', $wrong['decision']);
        $this->assertSame('fail', $wrong['checks']['expiry_date']['result']);
        $missing = str_replace(['Expiration Date', '2030/05/14'], ['', ''], self::TEXT)."\nAnnotation 2030/05/14";
        $result = $this->evaluate([$this->read($missing)], $missing);
        $this->assertSame('needs_correction', $result['decision']);
        $this->assertNull($result['extracted']['expiry_date']);
    }
}
