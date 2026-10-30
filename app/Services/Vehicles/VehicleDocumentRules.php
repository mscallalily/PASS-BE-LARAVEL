<?php

namespace App\Services\Vehicles;

use App\Services\LicenseVerification\LicenseValidationRules;
use Carbon\CarbonImmutable;

/**
 * Automatic OR/CR document-consistency decision for a campus vehicle.
 *
 * OCR extraction (VehicleDocumentFields), data validation (this class) and
 * account/driver eligibility (VehicleApprovalEligibility) are separate steps.
 * Passing here means the documents are readable and consistent with the
 * form; it does NOT prove the documents are authentic or who owns the
 * vehicle: no LTO verification service is available, and ownership is
 * intentionally not checked (school policy decision).
 *
 * Decisions:
 *  - passed            every check below passed
 *  - retake_required   a document or required value could not be read reliably
 *  - needs_correction  a value conflicts, is expired, or the wrong document was uploaded
 *  - unavailable       the reading service is down; nothing is approved, retry later
 */
class VehicleDocumentRules
{
    public const POLICY_VERSION = 'vehicle-orcr-auto-v3';

    /** Mean OCR confidence needed to trust a read (same threshold as license checks). */
    public const CONFIDENT = 80.0;

    /** Below this best-read mean confidence the text is mostly unreadable. */
    public const READABLE = 50.0;

    private const MIN_TEXT_CHARS = 40;

    private const NAMES = ['or' => 'Official Receipt (OR)', 'cr' => 'Certificate of Registration (CR)'];

    /**
     * @param array $documents ['or' => reader result, 'cr' => reader result]
     * @param array $fields    ['or' => parsed fields, 'cr' => parsed fields]
     * @param array $submitted plate, make, model, color, year (nullable), type_code
     */
    public function evaluate(array $documents, array $fields, array $submitted, ?CarbonImmutable $today = null): array
    {
        $today ??= CarbonImmutable::now(config('app.timezone'))->startOfDay();
        $checks = [];
        $retake = [];
        $correct = [];
        $unavailable = false;
        $readable = [];

        foreach (['or', 'cr'] as $kind) {
            $doc = $documents[$kind] ?? null;
            $name = self::NAMES[$kind];
            $quality = self::imageQuality($doc);
            $checks["{$kind}_image"] = $quality;
            if ($quality['result'] === 'unavailable') {
                $unavailable = true;
                continue;
            }
            if ($quality['result'] === 'wrong_format') {
                $correct[] = "Upload a JPG or PNG photo of the {$name}. PDF files cannot be checked automatically.";
                continue;
            }
            if ($quality['result'] !== 'pass') {
                $retake[] = "Retake the {$name}: {$quality['detail']}";
                continue;
            }
            $marker = $fields[$kind]['markers'][$kind] ?? false;
            $checks["{$kind}_document_type"] = $this->check($marker ? 'pass' : 'fail', $marker ? "{$name} heading found." : "{$name} heading not found.");
            if (!$marker) {
                $correct[] = "The image uploaded as the {$name} does not show the document heading. Make sure you uploaded the correct document, flat and in full.";
                continue;
            }
            $readable[$kind] = true;
        }

        if ($unavailable) {
            return $this->result('unavailable', ['The document reading service is unavailable. Your vehicle remains unapproved. Retry the check later.'], $checks, $fields);
        }

        $orFields = $fields['or'] ?? [];
        $crFields = $fields['cr'] ?? [];
        $submittedPlate = VehicleDocumentFields::plateKey($submitted['plate'] ?? '');

        $names = ['or' => 'OR', 'cr' => 'CR'];
        $docs = array_intersect_key(['or' => $orFields, 'cr' => $crFields], $readable);

        // Plate number: read from whichever document shows it clearly. When both
        // documents show a plate they must agree, and every plate read must
        // match the confirmed plate. Unreadable on one document is not a
        // failure when the other document supplies it.
        $plates = [];
        foreach ($docs as $kind => $f) {
            $plate = VehicleDocumentFields::plateKey($f['plate'] ?? null);
            if ($plate === '') {
                $checks["{$kind}_plate"] = $this->check('uncertain', 'Plate number not readable on this document.');
                continue;
            }
            $plates[$kind] = $plate;
            if ($plate !== $submittedPlate) {
                $checks["{$kind}_plate"] = $this->check('fail', 'Plate number differs from the form.');
                $correct[] = 'The plate number on the ' . self::NAMES[$kind] . " ({$f['plate']}) does not match the plate number you entered. Correct the plate number or upload the documents for this vehicle.";
            } else {
                $checks["{$kind}_plate"] = $this->check('pass', 'Plate number matches the form.');
            }
        }
        if (count($plates) === 2 && $plates['or'] !== $plates['cr']) {
            $checks['plate_documents_agree'] = $this->check('fail', 'OR and CR plate numbers differ.');
            $correct[] = "The plate numbers on the OR ({$orFields['plate']}) and CR ({$crFields['plate']}) do not match. Upload the OR and CR of the same vehicle.";
        }
        if ($docs && !$plates) {
            $retake[] = 'The plate number could not be read on either the OR or the CR. Retake the photo of either document so the plate number is clear.';
        }
        if ($docs) {
            $checks['plate_source'] = $this->check($plates ? 'pass' : 'uncertain', $plates
                ? 'Plate number read from: ' . implode(' and ', array_map(fn ($k) => $names[$k], array_keys($plates))) . '.'
                : 'Plate number not read on either document.');
        }

        // Vehicle description (make and model/series): each value may come from
        // either document. A value read on both must agree between them, and any
        // value read must match the form. The model/series identifies the vehicle,
        // so it must be read on at least one document; an unreadable make is left
        // to the owner's confirmation and does not block the check.
        $described = false;
        $descriptionFailed = false;
        foreach (['make' => 'make', 'model' => 'model (series)'] as $field => $label) {
            $values = array_filter(array_map(fn ($f) => $f[$field] ?? null, $docs), fn ($v) => $v !== null && trim((string) $v) !== '');
            if (!$values) {
                if ($docs) $checks["cr_{$field}"] = $this->check('uncertain', ucfirst($label) . ' not readable on either document; the confirmed value is not contradicted.');
                continue;
            }
            if (count($values) === 2 && !$this->sameDescription($field, $values['or'], $values['cr'])) {
                $checks["{$field}_documents_agree"] = $this->check('fail', "OR and CR {$label} differ.");
                $correct[] = "The {$label} on the OR (\"{$values['or']}\") does not match the CR (\"{$values['cr']}\"). Upload the OR and CR of the same vehicle.";
                $descriptionFailed = true;
                continue;
            }
            $failed = [];
            foreach ($values as $kind => $value) {
                if (!$this->sameDescription($field, $value, (string) ($submitted[$field] ?? ''), true)) $failed[$kind] = $value;
            }
            $source = implode(' and ', array_map(fn ($k) => $names[$k], array_keys($values)));
            if ($failed) {
                $kind = array_key_first($failed);
                $checks["cr_{$field}"] = $this->check('fail', ucfirst($label) . " on the {$names[$kind]} differs from the form.");
                $correct[] = "The {$label} on the {$names[$kind]} is \"{$failed[$kind]}\", which does not match \"{$submitted[$field]}\". Correct the {$label} to match the {$names[$kind]}.";
                $descriptionFailed = true;
            } else {
                $checks["cr_{$field}"] = $this->check('pass', ucfirst($label) . " on the {$source} matches the form.");
                $described = $described || $field === 'model';
            }
        }
        if ($docs && !$described && !$descriptionFailed) {
            $retake[] = 'The model (series) could not be read on the OR or the CR. Retake the CR photo so the SERIES is clear.';
        }

        // Color is supporting evidence: used when readable on either document, and
        // a conflict is a correction, but an unreadable color does not block.
        $colors = array_filter(array_map(fn ($f) => $f['color'] ?? null, $docs));
        if ($colors) {
            $matching = array_filter($colors, fn ($c) => $this->describes($c, (string) ($submitted['color'] ?? '')));
            if (!$matching) {
                $kind = array_key_first($colors);
                $checks['cr_color'] = $this->check('fail', "Color on the {$names[$kind]} differs from the form.");
                $correct[] = "The color on the {$names[$kind]} is \"{$colors[$kind]}\", which does not match \"{$submitted['color']}\". Correct the color to match the {$names[$kind]}.";
            } else {
                $checks['cr_color'] = $this->check('pass', 'Color on the ' . implode(' and ', array_map(fn ($k) => $names[$k], array_keys($matching))) . ' matches the form.');
            }
        } elseif ($docs) {
            $checks['cr_color'] = $this->check('uncertain', 'Color not readable; the confirmed color is not contradicted.');
        }

        if ($docs) {
            // Year is optional on the form; a conflict is still a correction.
            $year = $crFields['year'] ?? $orFields['year'] ?? null;
            if ($year && !empty($submitted['year']) && (int) $year !== (int) $submitted['year']) {
                $checks['cr_year'] = $this->check('fail', 'Year model differs from the form.');
                $correct[] = "The year model on the documents is {$year}, but you entered {$submitted['year']}. Correct the year model.";
            } elseif ($year && !empty($submitted['year'])) {
                $checks['cr_year'] = $this->check('pass', 'Year model matches the form.');
            }
            // Vehicle category (car or motorcycle) decides the parking area; it must not conflict.
            $categorySource = ($crFields['type_code'] ?? null) ? 'CR' : 'OR';
            $documentCategory = VehicleDocumentFields::category($crFields['type_code'] ?? $orFields['type_code'] ?? null);
            $formCategory = VehicleDocumentFields::category($submitted['type_code'] ?? null);
            if ($documentCategory && $formCategory && $documentCategory !== $formCategory) {
                $checks['cr_vehicle_category'] = $this->check('fail', 'Vehicle category differs from the form.');
                $correct[] = "The {$categorySource} describes a {$documentCategory}, but you selected a {$formCategory} type. Choose the vehicle type shown on the {$categorySource}.";
            } elseif ($documentCategory) {
                $checks['cr_vehicle_category'] = $this->check('pass', 'Vehicle category matches the form.');
            } else {
                $checks['cr_vehicle_category'] = $this->check('uncertain', 'Body type not readable; the selected type is not contradicted.');
            }
        }

        // Registration validity from the OR: required and not expired.
        if (isset($readable['or'])) {
            $expiry = $orFields['registration_expiry'] ?? null;
            if ($expiry === null) {
                $checks['or_registration_expiry'] = $this->check('uncertain', 'Registration validity date not readable.');
                $retake[] = 'The registration validity date could not be read on the OR. Retake the OR photo so the validity date is clear.';
            } elseif (CarbonImmutable::parse($expiry)->lt($today)) {
                $checks['or_registration_expiry'] = $this->check('fail', "Registration expired on {$expiry}.");
                $correct[] = "The vehicle registration expired on {$expiry}. Renew the registration and upload the new OR.";
            } else {
                $checks['or_registration_expiry'] = $this->check('pass', "Registration valid until {$expiry}.");
            }
        }

        $decision = $correct ? 'needs_correction' : ($retake ? 'retake_required' : 'passed');
        return $this->result($decision, array_values(array_unique(array_merge($correct, $retake))), $checks, $fields);
    }

    /**
     * Same make or model/series, allowing harmless OCR differences: spacing and
     * punctuation, extra words on one side ("HONDA" / "HONDA MOTOR"), and, for
     * series codes, look-alike characters ("FU1SOMFZ" / "FU150MFZ").
     * $oneWay: only the second value's words must appear in the first (form check).
     */
    private function sameDescription(string $field, string $a, string $b, bool $oneWay = false): bool
    {
        if ($this->describes($a, $b) || (!$oneWay && $this->describes($b, $a))) return true;
        // A brand name with one misread letter ("UZUKI" for "SUZUKI") is the same make.
        if ($field === 'make') return $this->describes($a, $b, true) || (!$oneWay && $this->describes($b, $a, true));
        if ($field !== 'model') return false;
        [$ka, $kb] = [VehicleDocumentFields::codeKey($a), VehicleDocumentFields::codeKey($b)];
        if ($ka === '' || $kb === '') return false;
        if ($ka === $kb) return true;
        // A code read together with neighbouring text still contains the confirmed code.
        return (strlen($kb) >= 3 && str_contains($ka, $kb)) || (!$oneWay && strlen($ka) >= 3 && str_contains($kb, $ka));
    }

    /** All words entered on the form appear in the document value (e.g. "WHITE" within "PEARL WHITE"). */
    private function describes(string $documentValue, string $formValue, bool $oneLetter = false): bool
    {
        $form = VehicleDocumentFields::wordsOf($formValue);
        if (!$form) return false;
        $document = VehicleDocumentFields::wordsOf($documentValue);
        if (!$oneLetter) return !array_diff($form, $document);
        // Words of five or more letters may differ by one misread letter.
        foreach ($form as $word) {
            $found = in_array($word, $document, true);
            foreach ($document as $read) $found = $found || (strlen($word) >= 5 && levenshtein($word, $read) <= 1);
            if (!$found) return false;
        }
        return true;
    }

    /** Image checks shared by OR/CR approval and the form pre-fill reader. */
    public static function imageQuality(?array $doc): array
    {
        if (!$doc) return self::checkResult('fail', 'The document could not be found.');
        if (($doc['kind'] ?? 'image') !== 'image') return self::checkResult('wrong_format', 'Automatic checks require an image.');
        if (($doc['width'] ?? null) === null) return self::checkResult('fail', $doc['engine_error'] ?? 'The image could not be opened.');
        if (!($doc['engine_available'] ?? false)) return self::checkResult('unavailable', $doc['engine_error'] ?? 'The reading service is unavailable.');
        if ($doc['width'] < LicenseValidationRules::MIN_WIDTH || $doc['height'] < LicenseValidationRules::MIN_HEIGHT) {
            return self::checkResult('fail', "the image resolution ({$doc['width']}x{$doc['height']}) is too low. Take a closer, full-size photo.");
        }
        if (mb_strlen(preg_replace('/\s+/', '', (string) ($doc['text'] ?? ''))) < self::MIN_TEXT_CHARS) {
            return self::checkResult('fail', 'not enough text could be read. Make sure the whole document is in the photo, flat and without glare.');
        }
        // An OR/CR is printed over a security pattern that lowers the mean OCR
        // confidence of a sharp, readable photo (about 75-80%). Whether the
        // required details were read is decided field by field; only text that
        // is mostly unreadable fails here.
        if (($doc['confidence'] ?? 0) < self::READABLE) {
            return self::checkResult('fail', 'the text could not be read clearly. Retake it in good light, without glare or shadows.');
        }
        return self::checkResult('pass', "Resolution {$doc['width']}x{$doc['height']}, sharpness {$doc['sharpness']}, best-read OCR confidence {$doc['confidence']}%.");
    }

    private function check(string $result, string $detail): array
    {
        return self::checkResult($result, $detail);
    }

    private static function checkResult(string $result, string $detail): array
    {
        return ['result' => $result, 'detail' => $detail];
    }

    private function result(string $decision, array $reasons, array $checks, array $fields): array
    {
        $extracted = [];
        foreach (['or' => ['plate', 'make', 'model', 'color', 'registration_expiry'], 'cr' => ['plate', 'make', 'model', 'color', 'year', 'body_type']] as $kind => $keys) {
            $extracted[$kind] = array_intersect_key($fields[$kind] ?? [], array_flip($keys));
        }
        return [
            'decision' => $decision,
            'reasons' => $reasons,
            'checks' => $checks,
            'extracted' => $extracted,
            'policy_version' => self::POLICY_VERSION,
            'approval_basis' => 'ocr_document_consistency',
            'official_verification' => 'unavailable',
            'ownership_verified' => false,
        ];
    }
}
