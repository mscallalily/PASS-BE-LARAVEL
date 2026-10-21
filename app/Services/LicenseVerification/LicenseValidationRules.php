<?php

namespace App\Services\LicenseVerification;

use Carbon\CarbonImmutable;

/**
 * Makes automatic document-consistency decisions from OCR evidence.
 * Approval means the supported document checks passed; it does not establish
 * issuer authenticity. Unreadable/mismatched uploads are returned with reasons.
 * An unavailable OCR service remains unapproved and can be retried.
 */
class LicenseValidationRules
{
    /** Below this Laplacian variance the photo is too blurry to read reliably. */
    public const BLUR_THRESHOLD = 60.0;

    public const MIN_WIDTH = 500;

    public const MIN_HEIGHT = 300;

    /** Mean OCR confidence above which a missing or different value is trusted. */
    private const CONFIDENT = 80.0;

    /** Enough readable text to judge what kind of document it is. */
    private const MIN_TEXT_CHARS = 60;

    /** Phrases printed on Philippine LTO driver's licenses, grouped by meaning. */
    private const LICENSE_MARKERS = [
        'issuing_country' => ['REPUBLIC OF THE PHILIPPINES', 'PHILIPPINES', 'PILIPINAS'],
        'issuing_agency' => ['LAND TRANSPORTATION OFFICE', 'LAND TRANSPORTATION'],
        'department' => ['DEPARTMENT OF TRANSPORTATION'],
        'document_title' => ["DRIVER'S LICENSE", 'DRIVERS LICENSE', 'DRIVER LICENSE', 'NON-PROFESSIONAL', 'PROFESSIONAL', 'STUDENT PERMIT'],
        'license_number_label' => ['LICENSE NO', 'LICENSE NUMBER'],
        'expiry_label' => ['EXPIRATION DATE', 'EXPIRATION', 'EXPIRY'],
        'card_fields' => ['AGENCY CODE', 'BLOOD TYPE', 'RESTRICTION', 'CONDITIONS', 'EYES COLOR'],
    ];

    /**
     * Normalize a license number to its 11 significant characters, e.g. N0123456789.
     */
    public static function normalizeLicenseNumber(string $value): string
    {
        return strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $value));
    }

    /**
     * Whether a value follows the LTO format A00-00-000000.
     */
    public static function isLtoFormat(string $value): bool
    {
        return (bool) preg_match('/^[A-Z]\d{10}$/', self::normalizeLicenseNumber($value));
    }

    /**
     * Display a normalized number as A00-00-000000.
     */
    public static function formatLicenseNumber(string $value): string
    {
        $n = self::normalizeLicenseNumber($value);

        return strlen($n) === 11
            ? substr($n, 0, 3) . '-' . substr($n, 3, 2) . '-' . substr($n, 5)
            : $value;
    }

    /**
     * @param  array  $document  Output of LicenseDocumentReader::read()
     * @param  array{license_number: string, expiry_date: string, first_name: ?string, last_name: ?string, license_type?: ?string, today?: string}  $submitted
     * @return array{decision: string, reasons: string[], notes: string[], checks: array, extracted: array, document_score: int, preliminary: bool, official_verification: string}
     */
    public function evaluate(array $document, array $submitted): array
    {
        $today = CarbonImmutable::parse($submitted['today'] ?? 'today')->startOfDay();
        $text = $this->normalizeText($document['text'] ?? '');
        $confidence = $document['confidence'] ?? null;
        $textChars = strlen(preg_replace('/[^A-Z0-9]/', '', $text));
        $checks = [];
        $reasons = [];
        $notes = [];
        $numbers = $this->findLicenseNumbers($text);
        $dates = $this->findDates($text);
        $score = $this->documentScore($text);
        $submittedNumber = self::normalizeLicenseNumber($submitted['license_number']);
        $submittedExpiry = CarbonImmutable::parse($submitted['expiry_date'])->startOfDay();
        $documentExpiry = $dates ? max($dates) : null;
        $extracted = [
            'license_numbers' => array_map([self::class, 'formatLicenseNumber'], $numbers),
            'dates' => array_map(fn (CarbonImmutable $d) => $d->toDateString(), $dates),
            'expiry_date' => $documentExpiry?->toDateString(),
            'name_tokens_found' => [],
        ];

        if ($submittedExpiry->lt($today)) {
            $reasons[] = 'The submitted license is expired. Upload a current, unexpired license.';
        }

        // The automatic approval policy uses image OCR confidence and image quality.
        // Retain PDF uploads for compatibility/manual exceptions, but never approve
        // a PDF merely because its editable text layer matches the form.
        if (($document['kind'] ?? null) !== 'image') {
            $checks['document_format'] = $this->check('fail', 'Automatic approval requires an image of the license front.');
            $reasons[] = 'For the automatic document check, upload a clear JPG or PNG photo of the license front instead of a PDF. Contact PASS staff if you need a document-format exception.';
            return $this->result($reasons, $notes, $checks, $extracted, $score);
        }

        $width = $document['width'] ?? null;
        $height = $document['height'] ?? null;
        $sharpness = $document['sharpness'] ?? null;
        if ($width !== null && $height !== null && ($width < self::MIN_WIDTH || $height < self::MIN_HEIGHT)) {
            $checks['image_quality'] = $this->check('fail', "Resolution {$width}x{$height} is below the minimum ".self::MIN_WIDTH.'x'.self::MIN_HEIGHT.'.');
            $reasons[] = 'The image resolution is too low. Upload a clear, full-size photo of the license front.';
        } elseif ($sharpness !== null && $sharpness < self::BLUR_THRESHOLD) {
            $checks['image_quality'] = $this->check('fail', "Sharpness {$sharpness} is below ".self::BLUR_THRESHOLD.'.');
            $reasons[] = 'The image is too blurry for automatic approval. Retake the full license front in good light and in focus.';
        } else {
            $qualityKnown = $width !== null && $height !== null && $sharpness !== null;
            $checks['image_quality'] = $this->check($qualityKnown ? 'pass' : 'uncertain', $qualityKnown ? "Resolution {$width}x{$height}, sharpness {$sharpness}." : 'Image quality could not be measured.');
        }

        if (!($document['engine_available'] ?? false)) {
            $checks['text_extraction'] = $this->check('uncertain', $document['engine_error'] ?? 'The text-reading service is unavailable.');
            $notes[] = 'The automatic check could not complete. This is not a finding that the license is invalid. Retry after the reading service is restored.';
            return $this->result($reasons, $notes, $checks, $extracted, $score, unavailable: true);
        }
        $checks['text_extraction'] = $this->check($textChars >= self::MIN_TEXT_CHARS ? 'pass' : 'uncertain', "{$textChars} readable characters.");
        $checks['ocr_confidence'] = $this->check($confidence !== null && $confidence >= self::CONFIDENT ? 'pass' : 'uncertain',
            'Mean OCR confidence '.($confidence === null ? 'unavailable' : "{$confidence}%").'; automatic approval threshold '.self::CONFIDENT.'%. This measures text recognition, not authenticity.');
        if ($reasons) {
            return $this->result($reasons, $notes, $checks, $extracted, $score);
        }

        $hasAgency = str_contains($text, 'LAND TRANSPORTATION');
        $hasTitle = (bool) preg_match("/DRIVER'?S?\\s+LICENSE|STUDENT\\s+PERMIT/", $text);
        $hasExpiryLabel = (bool) preg_match('/EXPIRATION|EXPIRY/', $text);
        $recognized = $score >= 4 && $hasAgency && $hasTitle && $hasExpiryLabel;
        $checks['document_type'] = $this->check($recognized ? 'pass' : 'fail', "Matched {$score} LTO marker groups; agency, document title and expiry heading ".($recognized ? 'present.' : 'could not all be confirmed.'));
        if (!$recognized) {
            $reasons[] = "The required Philippine LTO license headings could not be confirmed. Upload the full, clear front of your license. For a different license format, contact PASS staff.";
        }

        if (in_array($submittedNumber, $numbers, true)) {
            $checks['license_number'] = $this->check('pass', 'The license number read from the document matches the submitted number.');
        } elseif ($numbers && $this->closestDistance($submittedNumber, $numbers) <= 1) {
            $checks['license_number'] = $this->check('uncertain', 'The number differs by one character; automatic approval requires an exact normalized match.');
        } elseif ($numbers) {
            $checks['license_number'] = $this->check('fail', 'The number read from the document does not match the submitted number.');
            $reasons[] = 'The license number on the document ('.self::formatLicenseNumber($numbers[0]).') does not match the number you entered. Correct the number or upload your own license.';
        } else {
            $checks['license_number'] = $this->check('uncertain', 'The license number could not be read.');
        }

        // A matching birth/issue date is not enough: the latest date must be the
        // submitted, unexpired expiry, with an expiry heading also present.
        if ($documentExpiry && $documentExpiry->equalTo($submittedExpiry) && !$documentExpiry->lt($today) && $hasExpiryLabel) {
            $checks['expiry_date'] = $this->check('pass', 'The latest date read from the license matches the submitted, unexpired expiry date.');
        } elseif ($documentExpiry && $documentExpiry->lt($today)) {
            $checks['expiry_date'] = $this->check('fail', 'The latest date read from the document is expired.');
            $reasons[] = 'The license shown on the document appears to be expired. Upload a current license.';
        } elseif ($documentExpiry && $confidence !== null && $confidence >= self::CONFIDENT) {
            $checks['expiry_date'] = $this->check('fail', 'The document expiry does not match the submitted expiry.');
            $reasons[] = 'The expiration date on the document does not match the date you entered. Check the expiry date and upload the full front of the license.';
        } else {
            $checks['expiry_date'] = $this->check('uncertain', 'The expiry date could not be confirmed from the document.');
        }

        $name = $this->checkName($text, $submitted['first_name'] ?? null, $submitted['last_name'] ?? null);
        $extracted['name_tokens_found'] = $name['found'];
        $checks['name'] = $this->check($name['result'] === 'pass' ? 'pass' : 'uncertain', $name['result'] === 'pass'
            ? 'All registered first-name and last-name words were read exactly.'
            : 'The full registered name could not be matched exactly. A near match is not auto-approved.');

        if (array_key_exists('license_type', $submitted)) {
            $typeText = str_replace('-', ' ', $text);
            $nonProfessional = (bool) preg_match('/NON\\s*PROFESSIONAL/', $typeText);
            $typeMatches = match ($submitted['license_type']) {
                'non_professional' => $nonProfessional,
                'professional' => !$nonProfessional && (bool) preg_match('/\\bPROFESSIONAL\\b/', $typeText),
                'student_permit' => str_contains($typeText, 'STUDENT PERMIT'),
                default => false,
            };
            $checks['license_type'] = $this->check($typeMatches ? 'pass' : 'uncertain', $typeMatches ? 'The license type matches the document text.' : 'The selected license type could not be confirmed in the document text.');
        }

        return $this->result($reasons, $notes, $checks, $extracted, $score);
    }

    private function result(array $reasons, array $notes, array $checks, array $extracted, int $score, bool $unavailable = false): array
    {
        if (!$unavailable && !$reasons) {
            $required = [
                'image_quality' => 'The image quality could not be confirmed. Upload a clear full-size JPG or PNG.',
                'text_extraction' => 'Not enough readable text was found. Upload the full license front with all details visible.',
                'ocr_confidence' => 'The text was not read confidently enough for automatic approval. Retake the photo in good light without glare.',
                'document_type' => 'The required LTO document headings could not be confirmed.',
                'license_number' => 'The license number could not be matched exactly. Check your entry against the image and upload a clearer photo.',
                'expiry_date' => 'The expiry date could not be confirmed. Check the date and make sure it is readable in the photo.',
                'name' => 'The full name could not be matched exactly to your registration. Upload your own clear license or contact PASS staff to correct your registered name.',
            ];
            if (isset($checks['license_type'])) {
                $required['license_type'] = 'The selected license type could not be confirmed. Check the type against your document.';
            }
            foreach ($required as $key => $reason) {
                if (($checks[$key]['result'] ?? null) !== 'pass') {
                    $reasons[] = $reason;
                }
            }
        }
        $decision = $reasons ? 'needs_correction' : ($unavailable ? 'pending_review' : 'verified');
        $notes[] = 'Automated OCR and document-consistency check only. Authenticity has not been confirmed with the LTO. Account approval, vehicle authorization and RFID assignment remain separate.';

        return [
            'decision' => $decision,
            'decision_kind' => $decision === 'verified' ? 'document_consistency_pass' : ($decision === 'pending_review' ? 'check_unavailable' : 'correction_required'),
            'policy_version' => 'license-document-auto-v1',
            'approval_basis' => 'ocr_document_consistency',
            'reasons' => array_values(array_unique($reasons)),
            'notes' => array_values(array_unique($notes)),
            'checks' => $checks,
            'extracted' => $extracted,
            'document_score' => $score,
            'preliminary' => true,
            'official_verification' => 'unavailable',
        ];
    }

    private function check(string $result, string $detail): array
    {
        return ['result' => $result, 'detail' => $detail];
    }

    private function normalizeText(string $text): string
    {
        $text = strtoupper($text);
        $text = strtr($text, ['’' => "'", '‘' => "'", '–' => '-', '—' => '-', 'Ñ' => 'N', 'ñ' => 'N']);

        return preg_replace('/[ \t]+/', ' ', $text);
    }

    private function documentScore(string $text): int
    {
        $score = 0;

        foreach (self::LICENSE_MARKERS as $phrases) {
            foreach ($phrases as $phrase) {
                if (str_contains($text, $phrase)) {
                    $score++;
                    break;
                }
            }
        }

        return $score;
    }

    /**
     * License numbers in the text, normalized. Common OCR digit confusions
     * (O/0, I/1, S/5, B/8) are corrected in positions that must be digits.
     *
     * @return string[]
     */
    private function findLicenseNumbers(string $text): array
    {
        $digit = '[0-9OILSB]';
        preg_match_all("/(?<![A-Z0-9])([A-Z0-9]{$digit}{2})\s?-?\s?({$digit}{2})\s?-?\s?({$digit}{6})(?![A-Z0-9])/", $text, $matches, PREG_SET_ORDER);

        $numbers = [];

        foreach ($matches as $m) {
            // The first character is always a letter; OCR sometimes reads it as a look-alike digit.
            $first = strtr($m[1][0], ['0' => 'O', '1' => 'I', '2' => 'Z', '5' => 'S', '6' => 'G', '8' => 'B']);
            $rest = strtr(substr($m[1], 1) . $m[2] . $m[3], ['O' => '0', 'I' => '1', 'L' => '1', 'S' => '5', 'B' => '8']);
            $candidate = $first . $rest;

            if (preg_match('/^[A-Z]\d{10}$/', $candidate)) {
                $numbers[] = $candidate;
            }
        }

        return array_values(array_unique($numbers));
    }

    /**
     * Plausible calendar dates in YYYY/MM/DD, YYYY-MM-DD or MM/DD/YYYY form.
     *
     * @return CarbonImmutable[]
     */
    private function findDates(string $text): array
    {
        $dates = [];

        preg_match_all('/\b(19\d{2}|20\d{2})\s?[\/\-.]\s?(\d{1,2})\s?[\/\-.]\s?(\d{1,2})\b/', $text, $ymd, PREG_SET_ORDER);
        foreach ($ymd as $m) {
            $dates[] = [(int) $m[1], (int) $m[2], (int) $m[3]];
        }

        preg_match_all('/\b(\d{1,2})\s?[\/\-.]\s?(\d{1,2})\s?[\/\-.]\s?(19\d{2}|20\d{2})\b/', $text, $mdy, PREG_SET_ORDER);
        foreach ($mdy as $m) {
            $dates[] = [(int) $m[3], (int) $m[1], (int) $m[2]];
        }

        $valid = [];

        foreach ($dates as [$y, $mo, $d]) {
            if (checkdate($mo, $d, $y)) {
                $valid[] = CarbonImmutable::create($y, $mo, $d)->startOfDay();
            }
        }

        return $valid;
    }

    private function closestDistance(string $value, array $candidates): int
    {
        return min(array_map(fn ($c) => levenshtein($value, $c), $candidates));
    }

    /**
     * @return array{result: string, found: string[]}
     */
    private function checkName(string $text, ?string $firstName, ?string $lastName): array
    {
        $tokenize = fn (?string $s) => array_values(array_filter(
            preg_split('/[^A-Z]+/', $this->normalizeText((string) $s)),
            fn ($t) => strlen($t) >= 1
        ));

        $first = $tokenize($firstName);
        $last = $tokenize($lastName);

        if (!$first || !$last) {
            return ['result' => 'missing_identity', 'found' => []];
        }

        // A name elsewhere on the card (e.g. a restriction or annotation) is
        // insufficient. Require the printed name block and only its nearby lines.
        $lines = preg_split('/\r?\n/', $text);
        $nameBlock = '';
        foreach ($lines as $i => $line) {
            if (!preg_match('/(?:LAST|FIRST|FULL)\s*NAME/', $line)) {
                continue;
            }
            $nameBlock = preg_replace('/(?:LAST|FIRST|MIDDLE|FULL)\s*NAME/', '', $line);
            foreach (array_slice($lines, $i + 1, 2) as $next) {
                if (preg_match('/NATIONALITY|DATE OF BIRTH|LICENSE NO|EXPIRATION|\bSEX\b/', $next)) {
                    break;
                }
                $nameBlock .= ' '.$next;
            }
            break;
        }
        $words = array_unique(preg_split('/[^A-Z]+/', $nameBlock, -1, PREG_SPLIT_NO_EMPTY));
        $found = [];

        $has = function (string $token) use ($words, &$found) {
            if (in_array($token, $words, true)) {
                $found[] = $token;
                return true;
            }
            return false;
        };

        $firstFound = count(array_filter($first, $has)) === count($first);
        $lastFound = count(array_filter($last, $has)) === count($last);

        $result = $firstFound && $lastFound ? 'pass' : ($found ? 'partial' : 'none');

        return ['result' => $result, 'found' => array_values(array_unique($found))];
    }
}
