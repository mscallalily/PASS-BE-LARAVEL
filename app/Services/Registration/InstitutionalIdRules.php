<?php

namespace App\Services\Registration;

use App\Support\CampusTime;

/** Document consistency only; no student/employment roster or issuer integration. */
class InstitutionalIdRules
{
    public const POLICY = 'dyci-id-v1';

    public const CATEGORIES = ['college', 'employee', 'shs'];

    public static function digits(string $id): string
    {
        return preg_replace('/[\s-]+/u', '', trim($id));
    }

    public static function idProblem(string $category, string $id, ?int $year = null): ?string
    {
        $n = self::digits($id);
        $year ??= CampusTime::now()->year;
        if ($category === 'shs') {
            return preg_match('/^[A-Za-z0-9][A-Za-z0-9 -]{0,49}$/D', trim($id)) ? null : 'Enter the ID number printed on your SHS ID.';
        }
        $size = $category === 'employee' ? 7 : 9;
        if (! preg_match('/^\d{'.$size.'}$/D', $n)) {
            return "Enter exactly {$size} digits. Spaces or a hyphen are allowed; keep any leading zero.";
        }
        $start = $category === 'employee' ? 2000 + (int) substr($n, 1, 2) : (int) substr($n, 0, 4);
        if ($start < 1000 || $start > $year) {
            return "The year encoded in this ID must not be later than {$year}.";
        }

        return null;
    }

    public static function format(string $category, string $id): string
    {
        $n = self::digits($id);
        if ($category === 'shs') {
            return strtoupper(trim($id));
        }
        $cut = $category === 'employee' ? 3 : 4;

        return substr($n, 0, $cut).'-'.substr($n, $cut);
    }

    public static function name(string $value): string
    {
        $value = strtr(mb_strtoupper(trim($value), 'UTF-8'), ['Ñ' => 'N']);

        return preg_replace('/[^\pL\pM]+/u', ' ', $value);
    }

    public function evaluate(string $category, array $evidence, array $submitted): array
    {
        $checks = [];
        $reasons = [];
        $problem = self::idProblem($category, $submitted['user_code']);
        $checks['id_format'] = ['result' => $problem ? 'fail' : 'pass', 'detail' => $problem ?: 'ID length and encoded year are valid for this category.'];
        if ($problem) {
            $reasons[] = $problem;
        }
        if ($category === 'shs') {
            return $this->result('manual_review', $checks, ['SHS ID format is not configured yet. PASS staff must review this document.']);
        }
        if (! ($evidence['engine_available'] ?? false)) {
            return $this->result('unavailable', $checks, ['The automatic reader could not complete. Your account will remain pending staff review.']);
        }
        if (($evidence['kind'] ?? '') !== 'image') {
            return $this->result('manual_review', $checks, ['Upload a JPG/PNG for automatic reading, or submit this document for staff review.']);
        }
        $quality = ($evidence['width'] ?? 0) >= 500 && ($evidence['height'] ?? 0) >= 300 && ($evidence['sharpness'] ?? 0) >= 60;
        $checks['image_quality'] = ['result' => $quality ? 'pass' : 'uncertain', 'detail' => $quality ? 'Image size and sharpness checks passed.' : 'Image quality is uncertain; a clearer photo or staff review is needed.'];
        $school = $evidence['school_marker'] ?? false;
        $checks['school'] = ['result' => $school ? 'pass' : 'uncertain', 'detail' => $school ? 'DYCI text was found on the document.' : 'DYCI text could not be confirmed.'];
        if (! $school && ($evidence['confidence'] ?? 0) >= 80) {
            $checks['school']['result'] = 'fail';
            $reasons[] = 'DYCI school text was not found. Upload the full front of your school or employee ID.';
        }
        $suggestions = $evidence['fields'] ?? [];
        foreach (['user_code' => 'ID number', 'first_name' => 'First name', 'last_name' => 'Surname'] as $key => $label) {
            $read = $suggestions[$key] ?? '';
            $strong = ($evidence['field_confidence'][$key] ?? 0) >= 80;
            $a = $key === 'user_code' ? self::digits($read) : trim(self::name($read));
            $b = $key === 'user_code' ? self::digits($submitted[$key]) : trim(self::name($submitted[$key]));
            $match = $a !== '' && $a === $b;
            $state = ! $strong || $a === '' ? 'uncertain' : ($match ? 'pass' : 'fail');
            $checks[$key] = ['result' => $state, 'detail' => $state === 'pass' ? "{$label} matches the document text." : ($state === 'fail' ? "{$label} differs from the document text." : "{$label} could not be read confidently; staff review is needed.")];
            if ($state === 'fail') {
                $reasons[] = "{$label} does not match the uploaded ID. Review your entry or upload a clearer photo.";
            }
        }
        // Middle initial is optional. Never expand an initial into a guessed middle name.
        if (! empty($submitted['middle_initial']) && ! empty($suggestions['middle_initial']) && ($evidence['field_confidence']['first_name'] ?? 0) >= 80) {
            $match = trim(self::name($submitted['middle_initial'])) === trim(self::name($suggestions['middle_initial']));
            $checks['middle_initial'] = ['result' => $match ? 'pass' : 'fail', 'detail' => $match ? 'Middle initial matches.' : 'Middle initial differs from the document.'];
            if (! $match) {
                $reasons[] = 'Check your middle initial against the ID, or leave it blank if unavailable.';
            }
        }
        if ($reasons) {
            return $this->result('needs_correction', $checks, $reasons);
        }
        $allPass = collect($checks)->every(fn ($c) => $c['result'] === 'pass');

        return $this->result($allPass ? 'matched' : 'manual_review', $checks, $allPass ? [] : ['Some details could not be confirmed automatically. PASS staff must review the uploaded ID.']);
    }

    /** A disputed OCR reading may be sent to staff; it never becomes a match. */
    public function requestManualReview(array $result, bool $requested): array
    {
        if ($requested && $result['status'] === 'needs_correction' && ($result['checks']['id_format']['result'] ?? '') === 'pass') {
            $result['automatic_status'] = 'needs_correction';
            $result['status'] = 'manual_review';
            $result['manual_review_requested'] = true;
            array_unshift($result['reasons'], 'The registrant disputes the OCR reading and requested staff review. Inspect the ID and corrected details before account approval.');
        }
        return $result;
    }

    private function result(string $status, array $checks, array $reasons): array
    {
        return ['status' => $status, 'checks' => $checks, 'reasons' => array_values(array_unique($reasons)), 'policy_version' => self::POLICY,
            'note' => 'Document text checks do not confirm current enrollment/employment or ID authenticity. Email verification and account approval are separate.'];
    }
}
