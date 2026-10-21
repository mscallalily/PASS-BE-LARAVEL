<?php

namespace App\Services\Registration;

use App\Support\CampusTime;

/** Document consistency only; no student/employment roster or issuer integration. */
class InstitutionalIdRules
{
    public const POLICY = 'dyci-id-v3';

    public const SHS_POLICY = 'dyci-shs-lrn-v1';

    public const CATEGORIES = ['college', 'employee', 'shs'];

    public static function policy(string $category): string
    {
        return $category === 'shs' ? self::SHS_POLICY : self::POLICY;
    }

    public static function digits(string $id): string
    {
        return preg_replace('/[\s-]+/u', '', trim($id));
    }

    public static function idProblem(string $category, string $id, ?int $year = null): ?string
    {
        $n = self::digits($id);
        $year ??= CampusTime::now()->year;
        if ($category === 'shs') {
            return preg_match('/^\d{12}$/D', $n) ? null : 'Enter exactly 12 digits for the Learner Reference Number (LRN). Keep any leading zero.';
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
            return $n;
        }
        $cut = $category === 'employee' ? 3 : 4;

        return substr($n, 0, $cut).'-'.substr($n, $cut);
    }

    public static function suffix(?string $value): string
    {
        $value = strtoupper(rtrim(trim($value ?? ''), '.'));

        return match ($value) {
            'JR' => 'Jr.', 'SR' => 'Sr.', default => $value
        };
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
        $checks['id_format'] = ['result' => $problem ? 'fail' : 'pass', 'detail' => $problem ?: ($category === 'shs' ? 'LRN contains exactly 12 digits. No enrollment-year pattern is applied.' : 'ID length and encoded year are valid for this category.')];
        if ($problem) {
            $reasons[] = $problem;
        }
        if (! ($evidence['engine_available'] ?? false)) {
            return $this->result('unavailable', $checks, ['The automatic reader could not complete. Your account will remain pending staff review.'], $category);
        }
        if (($evidence['kind'] ?? '') !== 'image') {
            return $this->result('manual_review', $checks, ['Upload a JPG/PNG for automatic reading, or submit this document for staff review.'], $category);
        }
        $quality = ($evidence['width'] ?? 0) >= 500 && ($evidence['height'] ?? 0) >= 300 && ($evidence['sharpness'] ?? 0) >= 60;
        $qualityDetail = sprintf('%d × %d px; sharpness %.1f. Required image size: 500 × 300 px. Sharpness reference: 60; readable matching text can override this advisory.', $evidence['width'] ?? 0, $evidence['height'] ?? 0, $evidence['sharpness'] ?? 0);
        $checks['image_quality'] = ['result' => $quality ? 'pass' : 'uncertain', 'detail' => ($quality ? 'Image quality passed. ' : 'Image quality needs review. ').$qualityDetail];
        $school = $evidence['school_marker'] ?? false;
        $checks['school'] = ['result' => $school ? 'pass' : 'uncertain', 'detail' => $school ? 'DYCI text was found on the document.' : 'DYCI text could not be confirmed.'];
        if ($category === 'shs') {
            $shs = ! empty($evidence['shs_marker']);
            $checks['registration_category'] = ['result' => $shs ? 'pass' : 'uncertain', 'detail' => $shs ? 'Senior High School text was found on the document.' : 'Senior High School text could not be confirmed.'];
        }
        $suggestions = $evidence['fields'] ?? [];
        foreach (['user_code' => 'ID number', 'first_name' => 'First name', 'last_name' => 'Surname'] as $key => $label) {
            $read = $suggestions[$key] ?? '';
            $corroborated = $key === 'user_code' && InstitutionalIdSignals::confirmsNumber($evidence['number_consensus'] ?? [], $read);
            $strong = (($evidence['field_confidence'][$key] ?? 0) >= 80 || $corroborated) && empty($evidence['field_conflicts'][$key]);
            $a = $key === 'user_code' ? self::digits($read) : trim(self::name($read));
            $b = $key === 'user_code' ? self::digits($submitted[$key]) : trim(self::name($submitted[$key]));
            $match = $a !== '' && $a === $b;
            $state = ! $strong || $a === '' ? 'uncertain' : ($match ? 'pass' : 'fail');
            $confidence = (float) ($evidence['field_confidence'][$key] ?? 0);
            $uncertain = ! empty($evidence['field_conflicts'][$key]) ? "{$label}: the OCR reads disagree; staff review is needed."
                : ($a === '' ? "{$label}: no complete value could be extracted; staff review is needed."
                : sprintf('%s: text was extracted, but OCR confidence is %.1f%% (minimum 80%%). Staff review is needed.', $label, $confidence));
            $checks[$key] = ['result' => $state, 'detail' => $state === 'pass' ? ($corroborated && $confidence < 80 ? 'ID number matches the document and agrees across at least three OCR methods.' : "{$label} matches the document text.") : ($state === 'fail' ? "{$label} differs from the document text." : $uncertain)];
            if ($state === 'fail') {
                $reasons[] = "{$label} does not match the uploaded ID. Review your entry or upload a clearer photo.";
            }
        }
        // Middle initial is optional. Never expand an initial into a guessed middle name.
        if (! empty($submitted['middle_initial'])) {
            $match = trim(self::name($submitted['middle_initial'])) === trim(self::name($suggestions['middle_initial'] ?? ''));
            $strong = ! empty($suggestions['middle_initial']) && ($evidence['field_confidence']['middle_initial'] ?? $evidence['field_confidence']['first_name'] ?? 0) >= 80;
            $checks['middle_initial'] = ['result' => ! $strong ? 'uncertain' : ($match ? 'pass' : 'fail'), 'detail' => ! $strong ? 'Middle initial could not be confirmed; staff review is needed.' : ($match ? 'Middle initial matches.' : 'Middle initial differs from the document.')];
            if ($strong && ! $match) {
                $reasons[] = 'Check your middle initial against the ID, or leave it blank if unavailable.';
            }
        }
        $suffixRead = self::suffix($suggestions['suffix'] ?? '');
        $suffixEntered = self::suffix($submitted['suffix'] ?? '');
        if ($suffixRead !== '' || $suffixEntered !== '') {
            $strong = $suffixRead !== '' && ($evidence['field_confidence']['suffix'] ?? 0) >= 80 && empty($evidence['field_conflicts']['suffix']);
            $state = ! $strong ? 'uncertain' : ($suffixRead === $suffixEntered ? 'pass' : 'fail');
            $checks['suffix'] = ['result' => $state, 'detail' => $state === 'pass' ? 'Name suffix matches.' : ($state === 'fail' ? 'Name suffix differs from the document.' : 'Suffix could not be confirmed; staff review is needed.')];
            if ($state === 'fail') {
                $reasons[] = 'Check the name suffix printed on your ID.';
            }
        }
        // Overall sharpness is a heuristic, not identity evidence. Do not let it
        // veto a sufficiently large image whose school/name/number checks agree.
        $resolution = ($evidence['width'] ?? 0) >= 500 && ($evidence['height'] ?? 0) >= 300;
        $readable = collect($checks)->except(['image_quality'])->every(fn ($c) => $c['result'] === 'pass');
        if (! $quality && $resolution && $readable) {
            $checks['image_quality'] = ['result' => 'pass', 'advisory' => true,
                'detail' => sprintf('Document text is readable and all required identity text checks passed. Overall sharpness %.1f is advisory and does not block this result.', $evidence['sharpness'] ?? 0)];
        }
        if ($reasons) {
            return $this->result('needs_correction', $checks, $reasons, $category);
        }
        $allPass = collect($checks)->every(fn ($c) => $c['result'] === 'pass');

        $uncertainDetails = array_column(array_filter($checks, fn ($c) => $c['result'] === 'uncertain'), 'detail');

        return $this->result($allPass ? 'matched' : 'manual_review', $checks, $allPass ? [] : $uncertainDetails, $category);
    }

    /** A disputed OCR reading may be sent to staff; it never becomes a match. */
    public function requestManualReview(array $result, bool $requested): array
    {
        if ($requested && ($result['checks']['id_format']['result'] ?? '') === 'pass') {
            $result['automatic_status'] = $result['status'];
            $result['status'] = 'manual_review';
            $result['manual_review_requested'] = true;
            array_unshift($result['reasons'], 'The registrant disputes the OCR reading and requested staff review. Inspect the ID and corrected details before account approval.');
        }

        return $result;
    }

    private function result(string $status, array $checks, array $reasons, string $category): array
    {
        return ['status' => $status, 'checks' => $checks, 'reasons' => array_values(array_unique($reasons)), 'policy_version' => self::policy($category),
            'requires_staff_approval' => $category === 'shs',
            'note' => $category === 'shs'
                ? 'SHS document checks compare printed details only. Email verification and staff approval are required before account access; current enrollment and ID authenticity are not confirmed by OCR.'
                : 'Document text checks do not confirm current enrollment/employment or ID authenticity. A matched ID check becomes eligible for account activation after email verification; uncertain checks require staff review.'];
    }
}
