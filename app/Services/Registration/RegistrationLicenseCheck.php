<?php

namespace App\Services\Registration;

use App\Services\LicenseVerification\LicenseDocumentReader;
use App\Services\LicenseVerification\LicenseFieldConfidence;
use App\Services\LicenseVerification\LicenseOcrRecovery;
use App\Services\LicenseVerification\LicenseValidationRules;
use App\Support\CampusTime;

/** Extract only printed values. Submitted values are comparisons, never OCR suggestions. */
class RegistrationLicenseCheck
{
    public const POLICY = 'dyci-id-email-license-v2';

    public function __construct(private LicenseDocumentReader $reader, private LicenseValidationRules $rules) {}

    public function read(string $path, string $mime, array $identity): array
    {
        return $this->evaluate((new LicenseOcrRecovery($this->reader))->read($path, $mime), $identity);
    }

    public function evaluate(array $document, array $identity): array
    {
        $out = ['status' => 'retake', 'message' => 'Take a clear photo of your driver\'s license. Keep all four corners visible, hold the camera straight, and avoid glare.',
            'fields' => ['full_name' => null, 'license_number' => null, 'birth_date' => null, 'expiry_date' => null],
            'checks' => [], 'reasons' => [], 'policy_version' => self::POLICY];
        if (empty($document['engine_available'])) {
            return array_replace($out, ['status' => 'unavailable', 'message' => 'The license reader is temporarily unavailable. Try again shortly; no account has been approved.']);
        }
        if (($document['kind'] ?? '') !== 'image' || ($document['width'] ?? 0) < 500 || ($document['height'] ?? 0) < 300 || ($document['sharpness'] ?? 0) < 60) return $out;

        $reads = $document['field_reads'] ?? [$document];
        $numbers = []; $numberSources = []; $expiries = []; $births = []; $names = [];
        foreach ($reads as $read) {
            foreach (LicenseFieldConfidence::numberReadings($read) as $r) {
                if ($r['confidence'] >= 80) $numbers[$r['value']] = true;
                if ($r['confidence'] >= 40 && in_array($read['source'] ?? 'full_gray', ['full_gray', 'body_gray', 'number_color'], true)) {
                    $numberSources[$r['value']][$read['source'] ?? 'full_gray'] = true;
                }
            }
            foreach (LicenseFieldConfidence::expiryReadings($read) as $r) if ($r['confidence'] >= 80) $expiries[$r['value']] = true;
            foreach (LicenseFieldConfidence::birthReadings($read) as $r) if ($r['confidence'] >= 80) $births[$r['value']] = true;
            foreach (LicenseFieldConfidence::printedNameReadings($read) as $r) if ($r['confidence'] >= 80) $names[self::nameKey($r['value'])] = $r['value'];
        }
        // Reuse the established number policy: three distinct preparations may
        // corroborate a low-scoring printed number. No form entry is used to choose it.
        if (!$numbers) foreach ($numberSources as $number => $sources) {
            if (isset($sources['full_gray'], $sources['body_gray'], $sources['number_color'])) $numbers[$number] = true;
        }
        if (count($numbers) !== 1 || count($expiries) !== 1 || count($births) !== 1 || count($names) !== 1) {
            $out['reasons'] = ['The name, birth date, license number and expiry must each be readable without conflicting OCR results. Retake the photo; these fields cannot be typed or edited.'];
            return $out;
        }
        $number = array_key_first($numbers); $expiry = array_key_first($expiries); $birth = array_key_first($births); $name = array_values($names)[0];
        $out['fields'] = ['full_name' => $name, 'license_number' => LicenseValidationRules::formatLicenseNumber($number), 'birth_date' => $birth, 'expiry_date' => $expiry];
        $expectedName = implode(' ', array_filter([$identity['first_name'], $identity['middle_name'] ?? '', $identity['last_name'], $identity['suffix'] ?? '']));
        $nameMatch = self::nameKey($expectedName) === self::nameKey($name);
        if (($identity['identity_source'] ?? '') === 'institutional_id') {
            [$nameMatch, $middleName] = self::institutionalName($name, $identity);
            if ($nameMatch) $out['fields'] += ['first_name' => $identity['first_name'], 'last_name' => $identity['last_name'], 'middle_name' => $middleName, 'suffix' => $identity['suffix'] ?? ''];
        }
        // When DOB is absent from the institutional ID, read it from the license.
        // Do not ask the user to type it or pretend there was a second DOB source.
        $birthMatch = (!empty($identity['birth_date']) ? $birth === $identity['birth_date'] : ($identity['identity_source'] ?? '') === 'institutional_id') && $birth < CampusTime::date();
        $validDate = $expiry >= CampusTime::date();
        $result = $this->rules->evaluate($document, ['license_number' => $number, 'expiry_date' => $expiry, 'first_name' => $identity['first_name'], 'last_name' => $identity['last_name']]);
        $out['checks'] = $result['checks'] + [
            'full_name' => ['result' => $nameMatch ? 'pass' : 'fail', 'detail' => 'The printed license name must match the name read from your institutional ID.'],
            'birth_date' => ['result' => $birthMatch ? 'pass' : 'fail', 'detail' => !empty($identity['birth_date']) ? 'The printed birth date must match the identity document.' : 'Birth date was read from the labeled field on the license.'],
        ];
        if (!$validDate || !$nameMatch || !$birthMatch) {
            $out['status'] = 'blocked';
            $out['reasons'] = array_values(array_filter([
                !$validDate ? 'This license has expired. Use a current, unexpired driver\'s license.' : null,
                !$nameMatch ? 'The name on the license does not match your registration. Use your own license and make sure both ID photos show the same person’s name.' : null,
                !$birthMatch ? 'The birth date could not be validated against the available identity details.' : null,
            ]));
            $out['message'] = 'Registration cannot continue with this license.';
        } elseif ($result['decision'] === 'verified') {
            $out['status'] = 'passed';
            $out['message'] = 'License checks passed. Review the read-only details, then create your account.';
        } else {
            $out['reasons'] = ['The full license front and all required fields must be confirmed. Retake the photo in good light, without glare or cropped edges.'];
        }
        $out['evidence'] = $result + ['registration_policy' => self::POLICY, 'printed_full_name' => $name, 'printed_birth_date' => $birth,
            'confidence' => $document['confidence'] ?? null, 'text' => mb_substr((string) ($document['text'] ?? ''), 0, 4000)];
        return $out;
    }

    /** Match known given/surname boundaries; an ID initial is never expanded by guessing. */
    public static function institutionalName(string $printed, array $identity): array
    {
        $words = static function (string $value): array {
            preg_match_all('/[\pL\pM]+/u', mb_strtoupper($value), $matches, PREG_OFFSET_CAPTURE);
            return $matches[0];
        };
        $keys = static fn (array $tokens) => array_map(static fn ($token) => strtr($token[0], ['Ñ' => 'N']), $tokens);
        $first = $keys($words($identity['first_name'])); $last = $keys($words($identity['last_name']));
        $suffix = $keys($words($identity['suffix'] ?? ''));
        $printed = mb_strtoupper(trim($printed)); $parts = explode(',', $printed);
        if (count($parts) > 2 || !$first || !$last) return [false, null];
        $tailText = trim(count($parts) === 2 ? $parts[1] : $parts[0]);
        $tail = $words($tailText); $tailKeys = $keys($tail); $suffixFound = false;
        if ($suffix && array_slice($tailKeys, -count($suffix)) === $suffix) {
            $suffixFound = true; $tail = array_slice($tail, 0, -count($suffix)); $tailKeys = $keys($tail);
        }
        if (count($parts) === 2) {
            $printedLast = $keys($words($parts[0]));
            if ($suffix && array_slice($printedLast, -count($suffix)) === $suffix) {
                if ($suffixFound) return [false, null];
                $suffixFound = true; $printedLast = array_slice($printedLast, 0, -count($suffix));
            }
            if ($printedLast !== $last || array_slice($tailKeys, 0, count($first)) !== $first) return [false, null];
            $start = count($first);
        } else {
            $prefix = array_merge($last, $first);
            if (array_slice($tailKeys, 0, count($prefix)) !== $prefix) return [false, null];
            $start = count($prefix);
        }
        if ($suffix && !$suffixFound) return [false, null];
        $middle = array_slice($tail, $start); $middleKeys = $keys($middle);
        // Suffixes are separate identity fields, never a leftover middle name.
        if (array_intersect($middleKeys, ['JR', 'SR', 'II', 'III', 'IV', 'VI', 'VII', 'VIII', 'IX'])) return [false, null];
        $initial = trim(InstitutionalIdRules::name($identity['middle_initial'] ?? ''));
        if ($initial !== '' && (!$middleKeys || mb_substr($middleKeys[0], 0, 1) !== $initial)) return [false, null];
        if ($middle && mb_strlen($middle[0][0]) === 1) return [false, null];
        if (!empty($identity['middle_name']) && self::nameKey($identity['middle_name']) !== self::nameKey(implode(' ', $middleKeys))) return [false, null];
        $middleName = $middle ? trim(substr($tailText, $middle[0][1], $middle[count($middle)-1][1] + strlen($middle[count($middle)-1][0]) - $middle[0][1])) : null;
        return [true, $middleName];
    }

    public static function nameKey(string $value): string
    {
        $tokens = preg_split('/[^\pL\pM]+/u', strtr(mb_strtoupper($value), ['Ñ' => 'N']), -1, PREG_SPLIT_NO_EMPTY);
        sort($tokens, SORT_STRING);
        return implode(' ', $tokens);
    }
}
