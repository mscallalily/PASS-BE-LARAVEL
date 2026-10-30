<?php

namespace Tests\Unit;

use App\Services\LicenseVerification\LicenseFieldConfidence;
use App\Services\LicenseVerification\LicenseValidationRules;
use PHPUnit\Framework\TestCase;

class LicenseFieldConfidenceTest extends TestCase
{
    private const CARD = "REPUBLIC OF THE PHILIPPINES\nDEPARTMENT OF TRANSPORTATION\nLAND TRANSPORTATION OFFICE\nDRIVER'S LICENSE\nLast Name First Name Middle Name\nDELA CRUZ, JUAN MIGUEL SANTOS\nNationality Sex Date of Birth\nPHL M 1995/05/14\nLicense No. Expiration Date Agency Code\nN01-23-456789 2030/05/14 N01\nBlood Type Eyes Color Conditions";

    private function submitted(array $changes = []): array
    {
        return $changes + ['first_name' => 'JUAN MIGUEL', 'last_name' => 'DELA CRUZ',
            'license_number' => 'N01-23-456789', 'expiry_date' => '2030-05-14', 'today' => '2026-10-21'];
    }

    private function read(array $scores = [], string $source = 'full_gray', ?string $text = null): array
    {
        $text ??= self::CARD;
        $words = [];
        foreach (explode("\n", $text) as $i => $line) {
            foreach (explode(' ', $line) as $word) {
                $words[] = ['text' => $word, 'line' => 'line-'.$i, 'confidence' => $scores[$word] ?? 96.0];
            }
        }
        return ['text' => $text, 'words' => $words, 'confidence' => 66.0, 'source' => $source];
    }

    private function document(?array $reads = null, array $changes = []): array
    {
        $reads ??= [$this->read()];
        return $changes + ['kind' => 'image', 'engine_available' => true, 'text' => $reads[0]['text'],
            'words' => $reads[0]['words'], 'field_reads' => $reads, 'confidence' => 66.0,
            'width' => 1800, 'height' => 1100, 'sharpness' => 150.0];
    }

    private function evaluate(array $document, array $submission = []): array
    {
        return (new LicenseValidationRules)->evaluate($document, $this->submitted($submission));
    }

    public function test_low_whole_image_mean_does_not_hide_strong_matching_identity_fields(): void
    {
        $document = $this->document();
        $result = $this->evaluate($document);
        $this->assertSame('verified', $result['decision']);
        $this->assertSame('pass', $result['checks']['ocr_confidence']['result']);
        $this->assertSame('required_fields_and_corroboration', $result['confidence_basis']);
        $this->assertSame(66.0, $document['confidence']);
        $this->assertSame('unavailable', $result['official_verification']);
        $this->assertTrue($result['preliminary']);
    }

    public function test_all_words_in_long_first_and_compound_last_names_must_be_confident(): void
    {
        foreach (['JUAN', 'MIGUEL', 'DELA', 'CRUZ,'] as $word) {
            $result = $this->evaluate($this->document([$this->read([$word => 79.9])], ['confidence' => 99.0]));
            $this->assertSame('pending_review', $result['decision'], $word);
            $this->assertSame(79.9, $result['identity_confidence']['fields']['name']);
        }
    }

    public function test_weak_number_or_expiry_cannot_be_overridden_by_a_high_global_mean(): void
    {
        foreach (['N01-23-456789', '2030/05/14'] as $word) {
            $result = $this->evaluate($this->document([$this->read([$word => 79.9])], ['confidence' => 99.0]));
            $this->assertSame($word === 'N01-23-456789' ? 'pending_review' : 'needs_correction', $result['decision'], $word);
        }
        $result = $this->evaluate($this->document([$this->read(['N01-23-456789' => 80.0, '2030/05/14' => 80.0])]));
        $this->assertSame('verified', $result['decision']);
    }

    public function test_three_distinct_number_regions_can_corroborate_without_inflating_confidence(): void
    {
        $reads = [$this->read(['N01-23-456789' => 45.0]),
            $this->read(['N01-23-456789' => 52.0], 'body_gray'),
            $this->read(['N01-23-456789' => 76.0], 'number_color')];
        $result = $this->evaluate($this->document($reads));
        $this->assertSame('verified', $result['decision']);
        $this->assertTrue($result['identity_confidence']['number_consensus']);
        $this->assertSame(76.0, $result['identity_confidence']['fields']['license_number']);
        $this->assertCount(3, $result['identity_confidence']['number_sources']);
        $this->assertStringContainsString('corroborated', $result['checks']['ocr_confidence']['detail']);
    }

    public function test_repeated_segmentation_of_one_source_is_not_three_votes(): void
    {
        $read = $this->read(['N01-23-456789' => 75.0]);
        foreach ([[$read, $read, $read], [$read, $this->read(['N01-23-456789' => 75.0], 'body_gray')],
            [$read, $this->read(['N01-23-456789' => 75.0], 'body_gray'), $this->read(['N01-23-456789' => 75.0], 'header_color')]] as $reads) {
            $result = $this->evaluate($this->document($reads));
            $this->assertSame('pending_review', $result['decision']);
            $this->assertFalse($result['identity_confidence']['number_consensus']);
        }
    }

    public function test_very_weak_or_different_number_does_not_count_as_corroboration(): void
    {
        $full = $this->read(['N01-23-456789' => 60.0]);
        $body = $this->read(['N01-23-456789' => 65.0], 'body_gray');
        foreach ([$this->read(['N01-23-456789' => 39.9], 'number_color'),
            $this->read(['N01-23-456788' => 60.0], 'number_color', str_replace('456789', '456788', self::CARD))] as $number) {
            $result = $this->evaluate($this->document([$full, $body, $number]));
            $this->assertSame('pending_review', $result['decision']);
        }
    }

    public function test_strong_conflicting_number_blocks_approval_even_when_another_read_matches(): void
    {
        $wrong = $this->read([], 'body_gray', str_replace('456789', '456788', self::CARD));
        $result = $this->evaluate($this->document([$this->read(), $wrong]));
        $this->assertSame('pending_review', $result['decision']);
        $this->assertTrue($result['identity_confidence']['number_conflict']);
        $this->assertStringContainsString('disagree', $result['checks']['ocr_confidence']['detail']);
    }

    public function test_label_ocr_confusion_does_not_change_the_license_number_value(): void
    {
        $read = $this->read([], 'body_gray', str_replace('License No.', 'Licenge No,', self::CARD));
        $numbers = LicenseFieldConfidence::numberReadings($read);
        $this->assertSame([['value' => 'N0123456789', 'confidence' => 96.0]], $numbers);
        $result = $this->evaluate($this->document([$read]));
        $this->assertSame('verified', $result['decision']);
    }

    public function test_positional_digit_confusions_still_require_the_same_canonical_number(): void
    {
        $text = str_replace('N01-23-456789', 'NO1-23-4S6789', self::CARD);
        $this->assertSame('verified', $this->evaluate($this->document([$this->read([], 'full_gray', $text)]))['decision']);
        $this->assertSame('needs_correction', $this->evaluate($this->document([$this->read([], 'full_gray', $text)]), ['license_number' => 'N01-23-456788'])['decision']);
    }

    public function test_values_in_unrelated_annotations_or_birth_date_are_not_field_evidence(): void
    {
        foreach ([str_replace('DELA CRUZ, JUAN MIGUEL SANTOS', 'SOMEONE ELSE', self::CARD)."\nAnnotation JUAN MIGUEL DELA CRUZ",
            str_replace('License No.', 'Other reference', self::CARD),
            str_replace('N01-23-456789 2030/05/14 N01', 'N01-23-456789 2030/05/15 N01', self::CARD)] as $text) {
            $this->assertSame('needs_correction', $this->evaluate($this->document([$this->read([], 'full_gray', $text)]))['decision']);
        }
    }

    public function test_missing_or_corrupt_structured_metadata_never_promotes_a_read(): void
    {
        $read = $this->read();
        $variants = [$read + ['unused' => true]];
        $variants[0]['words'][0]['confidence'] = 101;
        $variants[] = $read; $variants[1]['text'] .= ' EXTRA TEXT';
        $variants[] = $read; unset($variants[2]['words'][2]['line']);
        $variants[] = ['text' => self::CARD, 'words' => []];
        foreach ($variants as $invalid) {
            $result = $this->evaluate($this->document([$invalid], ['confidence' => 99.0]));
            $this->assertSame('needs_correction', $result['decision']);
            $this->assertFalse($result['identity_confidence']['reliable']);
        }
    }

    public function test_better_field_confidence_never_overrides_other_required_checks(): void
    {
        foreach ([['sharpness' => null], ['confidence' => null], ['kind' => 'pdf']] as $changes) {
            $this->assertNotSame('verified', $this->evaluate($this->document(null, $changes))['decision']);
        }
        $noAgency = str_replace('LAND TRANSPORTATION OFFICE', 'OTHER AGENCY', self::CARD);
        $this->assertSame('pending_review', $this->evaluate($this->document([$this->read([], 'full_gray', $noAgency)]))['decision']);
        foreach ([['first_name' => 'JUAN MIGUEL PEDRO'], ['license_number' => 'N01-23-456788'], ['expiry_date' => '2031-05-14'], ['expiry_date' => '2025-05-14']] as $submission) {
            $this->assertSame('needs_correction', $this->evaluate($this->document(), $submission)['decision']);
        }
        $this->assertSame('pending_review', $this->evaluate($this->document(null, ['engine_available' => false]))['decision']);
    }

    public function test_low_quality_score_alone_does_not_reject_reliably_read_fields(): void
    {
        foreach ([['sharpness' => 59.9], ['width' => 499]] as $changes) {
            $result = $this->evaluate($this->document(null, $changes));
            $this->assertSame('verified', $result['decision'], json_encode($changes));
            $this->assertSame('pass', $result['checks']['image_quality']['result']);
            $this->assertStringContainsString('still read', $result['checks']['image_quality']['detail']);
        }
        // Readability does not prove validity: mismatches still fail on a low-quality photo.
        foreach ([['license_number' => 'N01-23-456788'], ['expiry_date' => '2031-05-14'], ['first_name' => 'PEDRO']] as $submission) {
            $this->assertSame('needs_correction', $this->evaluate($this->document(null, ['sharpness' => 20.0]), $submission)['decision']);
        }
    }

    public function test_low_quality_with_unreliable_fields_explains_the_photo_problem(): void
    {
        $result = $this->evaluate($this->document([$this->read(['N01-23-456789' => 55.0])], ['sharpness' => 20.0]));
        $this->assertSame('needs_correction', $result['decision']);
        $this->assertSame('uncertain', $result['checks']['image_quality']['result']);
        $reasons = implode("\n", $result['reasons']);
        $this->assertStringContainsString('out of focus', $reasons);
        $this->assertStringNotContainsString('could not be read consistently', $reasons);
    }

    public function test_misread_small_print_labels_still_locate_the_values(): void
    {
        // Real card OCR: the labels under the hologram read as "License Na." / "Expirzuon Date".
        $card = str_replace('License No. Expiration Date', 'License Na. Expirzuon Date', self::CARD);
        $result = $this->evaluate($this->document([$this->read([], 'full_gray', $card)]));
        $this->assertSame('verified', $result['decision']);
        $this->assertSame('pass', $result['checks']['document_type']['result']);
        $found = LicenseFieldConfidence::extract($this->document([$this->read([], 'full_gray', $card)]));
        $this->assertSame('N01-23-456789', $found['license_number']['value']);
        $this->assertTrue($found['license_number']['labeled']);
        $this->assertSame('2030-05-14', $found['expiry_date']['value']);
        // The tolerance only locates the field; a different value is still a mismatch.
        $this->assertSame('needs_correction', $this->evaluate($this->document([$this->read([], 'full_gray', $card)]), ['license_number' => 'N01-23-456788'])['decision']);
    }

    public function test_label_patterns_do_not_match_titles_or_other_fields(): void
    {
        foreach (['LICENSE NO.', 'LICENSE NA.', 'LICENSE NOA.', 'LICENGE NO,', 'LICENSE NUMBER'] as $label) {
            $this->assertMatchesRegularExpression(LicenseFieldConfidence::NUMBER_LABEL, $label);
        }
        foreach (["DRIVER'S LICENSE NON-PROFESSIONAL", 'SIGNATURE OF LICENSEE', 'DATE OF BIRTH'] as $other) {
            $this->assertDoesNotMatchRegularExpression(LicenseFieldConfidence::NUMBER_LABEL, $other);
        }
        foreach (['EXPIRATION DATE', 'EXPIRZUON DATE', 'EXPIRY', 'EXOIRATION DATE'] as $label) {
            $this->assertMatchesRegularExpression(LicenseFieldConfidence::EXPIRY_LABEL, $label);
        }
        $this->assertDoesNotMatchRegularExpression(LicenseFieldConfidence::EXPIRY_LABEL, 'DATE OF BIRTH');
    }

    public function test_extract_never_invents_values(): void
    {
        // No labels: only a printed A00-00-000000 token is offered (marked unlabeled);
        // a birth date is never used as the expiry, and dates/heights are not joined into a number.
        $card = "DRIVER'S LICENSE\nPHL M 2004/07/21 35 1.48\nN01-23-456789 2030/05/14 N01";
        $found = LicenseFieldConfidence::extract($this->document([$this->read([], 'full_gray', $card)]));
        $this->assertSame('N01-23-456789', $found['license_number']['value']);
        $this->assertFalse($found['license_number']['labeled']);
        $this->assertNull($found['expiry_date']);
        $found = LicenseFieldConfidence::extract($this->document([$this->read([], 'full_gray', "PHL M 2004/07/21 35 1.48\nAddress 215 NIA RD")]));
        $this->assertSame(['license_number' => null, 'expiry_date' => null], $found);
    }

    public function test_extract_reports_disagreeing_strong_readings(): void
    {
        $other = str_replace('N01-23-456789', 'N01-23-456780', self::CARD);
        $found = LicenseFieldConfidence::extract($this->document([$this->read(), $this->read([], 'body_gray', $other), $this->read([], 'number_color', 'N01-23-456789')]));
        $this->assertSame('N01-23-456789', $found['license_number']['value']);
        $this->assertTrue($found['license_number']['conflict']);
    }

    public function test_legacy_unstructured_read_does_not_bypass_the_existing_mean_threshold(): void
    {
        $document = $this->document(); unset($document['words'], $document['field_reads']);
        $this->assertSame('pending_review', $this->evaluate($document)['decision']);
        $document['confidence'] = 90.0;
        $this->assertSame('verified', $this->evaluate($document)['decision']);
    }
}
