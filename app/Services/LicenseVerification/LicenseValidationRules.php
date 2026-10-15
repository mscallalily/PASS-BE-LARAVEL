<?php

namespace App\Services\LicenseVerification;

use Carbon\CarbonImmutable;

/**
 * Turns what LicenseDocumentReader saw into a preliminary decision.
 *
 * The outcome is only ever "needs_correction" (a clear, explainable failure)
 * or "pending_review". It never returns "verified": OCR cannot prove a license
 * is authentic and no official LTO verification service is integrated, so a
 * person must confirm authenticity. Anything ambiguous goes to staff review
 * instead of being rejected.
 */
class LicenseValidationRules
{
    /** Below this Laplacian variance the photo is too blurry to read reliably. */
    public const BLUR_THRESHOLD = 60.0;

    public const MIN_WIDTH = 500;

    public const MIN_HEIGHT = 300;

    /** Mean OCR confidence above which a missing or different value is trusted. */
    private const CONFIDENT = 70.0;

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
     * @param  array{license_number: string, expiry_date: string, first_name: ?string, last_name: ?string, today?: string}  $submitted
     * @return array{decision: string, reasons: string[], notes: string[], checks: array, extracted: array, document_score: int, preliminary: bool, official_verification: string}
     */
    public function evaluate(array $document, array $submitted): array
    {
        $today = CarbonImmutable::parse($submitted['today'] ?? 'today')->startOfDay();
        $text = $this->normalizeText($document['text'] ?? '');
        $confidence = $document['confidence'] ?? null;
        $textChars = strlen(preg_replace('/[^A-Z0-9]/', '', $text));

        $reasons = [];
        $notes = [];
        $checks = [];

        $licenseNumbers = $this->findLicenseNumbers($text);
        $submittedNumber = self::normalizeLicenseNumber($submitted['license_number']);
        $numberReadable = $licenseNumbers && $this->closestDistance($submittedNumber, $licenseNumbers) <= 1;
        $dates = $this->findDates($text);
        $score = $this->documentScore($text);
        $confident = $confidence !== null && $confidence >= self::CONFIDENT && $textChars >= self::MIN_TEXT_CHARS;

        $extracted = [
            'license_numbers' => array_map([self::class, 'formatLicenseNumber'], $licenseNumbers),
            'dates' => array_map(fn (CarbonImmutable $d) => $d->toDateString(), $dates),
            'expiry_date' => null,
            'name_tokens_found' => [],
        ];

        // 1. Image quality (images only).
        if ($document['kind'] === 'image' && $document['width'] !== null) {
            if ($document['width'] < self::MIN_WIDTH || $document['height'] < self::MIN_HEIGHT) {
                $checks['image_quality'] = $this->check('fail', "Resolution {$document['width']}x{$document['height']} is below the minimum " . self::MIN_WIDTH . 'x' . self::MIN_HEIGHT . '.');
                $reasons[] = 'The image resolution is too low. Upload a clear, full-size photo or scan of the front of your license.';
            } elseif (($document['sharpness'] ?? 0) < self::BLUR_THRESHOLD && !$numberReadable) {
                // Blurry and the key field is unreadable. A blurry photo whose
                // number still reads correctly is left for staff instead.
                $checks['image_quality'] = $this->check('fail', "Sharpness {$document['sharpness']} is below " . self::BLUR_THRESHOLD . ' and the license number could not be read.');
                $reasons[] = 'The image is too blurry to read. Retake the photo in good light with the license in focus and filling the frame.';
            } else {
                $checks['image_quality'] = $this->check('pass', "Resolution {$document['width']}x{$document['height']}, sharpness " . ($document['sharpness'] ?? 'n/a') . '.');
            }
        }

        // 2. Was any text extracted at all?
        if (!($document['engine_available'] ?? false)) {
            $notes[] = 'Automatic text check could not run: ' . ($document['engine_error'] ?? 'unknown error') . ' Staff must review the document manually.';
            $checks['text_extraction'] = $this->check('uncertain', $document['engine_error'] ?? 'Text extraction unavailable.');

            return $this->result($reasons, $notes, $checks, $extracted, $score);
        }

        $checks['text_extraction'] = $this->check(
            $textChars >= self::MIN_TEXT_CHARS ? 'pass' : 'uncertain',
            "{$textChars} readable characters" . ($confidence !== null ? ", mean confidence {$confidence}%." : '.')
        );

        if ($reasons) {
            // The image itself must be fixed first; field comparisons on it are not meaningful.
            return $this->result($reasons, $notes, $checks, $extracted, $score);
        }

        // 3. Does it look like a Philippine LTO driver's license?
        $recognized = $score >= 2 || $licenseNumbers;

        if ($recognized) {
            $checks['document_type'] = $this->check('pass', "Matched {$score} LTO license marker group(s).");
        } elseif ($confident && $score === 0) {
            $checks['document_type'] = $this->check('fail', 'Readable text contains no Philippine driver\'s license markers.');
            $reasons[] = 'The uploaded file does not appear to be a Philippine LTO driver\'s license. Upload a photo or scan of the front of your driver\'s license.';

            return $this->result($reasons, $notes, $checks, $extracted, $score);
        } else {
            $checks['document_type'] = $this->check('uncertain', "Matched {$score} license marker group(s); not enough to decide.");
            $notes[] = 'Could not confirm the document type automatically.';
        }

        // 4. License number.
        if (!$licenseNumbers) {
            $checks['license_number'] = $this->check('uncertain', 'No license number could be read from the document.');
            $notes[] = 'License number could not be read automatically.';
        } elseif (in_array($submittedNumber, $licenseNumbers, true)) {
            $checks['license_number'] = $this->check('pass', 'The license number on the document matches the submitted number.');
        } elseif ($this->closestDistance($submittedNumber, $licenseNumbers) <= 1) {
            $checks['license_number'] = $this->check('uncertain', 'The license number differs by one character, which may be an OCR misread.');
            $notes[] = 'License number nearly matches; confirm it against the image.';
        } else {
            $found = self::formatLicenseNumber($licenseNumbers[0]);
            $checks['license_number'] = $this->check('fail', "Document shows {$found}.");
            $reasons[] = "The license number on the document ({$found}) does not match the number you entered (" . self::formatLicenseNumber($submittedNumber) . '). Check the number or upload the correct license.';
        }

        // 5. Expiration date. On LTO cards it is the latest date printed.
        $submittedExpiry = CarbonImmutable::parse($submitted['expiry_date'])->startOfDay();
        $documentExpiry = $dates ? max($dates) : null;
        $extracted['expiry_date'] = $documentExpiry?->toDateString();

        if (!$dates) {
            $checks['expiry_date'] = $this->check('uncertain', 'No dates could be read from the document.');
            $notes[] = 'Expiration date could not be read automatically.';
        } elseif ($this->containsDate($dates, $submittedExpiry)) {
            $checks['expiry_date'] = $this->check('pass', 'The expiration date on the document matches the submitted date.');
        } elseif ($confident) {
            $checks['expiry_date'] = $this->check('fail', "Document shows {$documentExpiry->toDateString()}.");
            $reasons[] = "The expiration date on the document ({$documentExpiry->format('Y/m/d')}) does not match the date you entered ({$submittedExpiry->format('Y/m/d')}).";
        } else {
            $checks['expiry_date'] = $this->check('uncertain', 'The submitted expiration date was not found, but the text is not clear enough to be sure.');
            $notes[] = 'Expiration date could not be confirmed automatically.';
        }

        if ($documentExpiry && $documentExpiry->lt($today) && $confident) {
            $reasons[] = 'The license shown on the document appears to be expired. Upload a current, unexpired license.';
        }

        // 6. Name of the license holder.
        $nameCheck = $this->checkName($text, $submitted['first_name'] ?? null, $submitted['last_name'] ?? null);
        $extracted['name_tokens_found'] = $nameCheck['found'];

        if ($nameCheck['result'] === 'pass') {
            $checks['name'] = $this->check('pass', 'The registered first and last name appear on the document.');
        } elseif ($nameCheck['result'] === 'missing_identity') {
            $checks['name'] = $this->check('uncertain', 'No registered name is on file to compare against.');
            $notes[] = 'No registered name to compare.';
        } elseif ($nameCheck['result'] === 'none' && $confident && ($checks['license_number']['result'] ?? null) === 'pass') {
            // The card was read well enough to match its number, so a missing name is a real mismatch.
            $checks['name'] = $this->check('fail', 'Neither the registered first nor last name appears on the document.');
            $reasons[] = 'The name on the license does not match your registered name. Upload your own driver\'s license, or contact PASS staff if your name has changed.';
        } else {
            $checks['name'] = $this->check('uncertain', 'The registered name could not be fully matched in the extracted text.');
            $notes[] = 'Name could not be fully confirmed automatically.';
        }

        // 7. Required details: both key fields missing from a clearly read license.
        if ($recognized && $confident && !$licenseNumbers && !$dates) {
            $reasons[] = 'The license number and expiration date are not visible on the uploaded image. Upload the full front side of the license with all details showing.';
        }

        return $this->result($reasons, $notes, $checks, $extracted, $score);
    }

    private function result(array $reasons, array $notes, array $checks, array $extracted, int $score): array
    {
        $notes[] = 'Preliminary validation only. Authenticity has not been confirmed: no official LTO verification service is integrated, so PASS staff must review the document.';

        return [
            'decision' => $reasons ? 'needs_correction' : 'pending_review',
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

    private function containsDate(array $dates, CarbonImmutable $date): bool
    {
        foreach ($dates as $d) {
            if ($d->equalTo($date)) {
                return true;
            }
        }

        return false;
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
            fn ($t) => strlen($t) >= 2
        ));

        $first = $tokenize($firstName);
        $last = $tokenize($lastName);

        if (!$first || !$last) {
            return ['result' => 'missing_identity', 'found' => []];
        }

        $words = array_unique(preg_split('/[^A-Z]+/', $text, -1, PREG_SPLIT_NO_EMPTY));
        $found = [];

        $has = function (string $token) use ($words, &$found) {
            foreach ($words as $word) {
                if ($word === $token || (strlen($token) >= 4 && levenshtein($word, $token) <= 1)) {
                    $found[] = $token;
                    return true;
                }
            }
            return false;
        };

        $firstFound = count(array_filter($first, $has)) > 0;
        $lastFound = count(array_filter($last, $has)) === count($last);

        $result = $firstFound && $lastFound ? 'pass' : ($found ? 'partial' : 'none');

        return ['result' => $result, 'found' => array_values(array_unique($found))];
    }
}
