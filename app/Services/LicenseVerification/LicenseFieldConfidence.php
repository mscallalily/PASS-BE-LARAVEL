<?php

namespace App\Services\LicenseVerification;

/** Confidence in printed identity fields, never evidence of issuer authenticity. */
final class LicenseFieldConfidence
{
    public static function assess(array $document, array $submitted): array
    {
        $reads = $document['field_reads'] ?? [$document + ['source' => 'full_gray']];
        $scores = ['name' => null, 'license_number' => null, 'expiry_date' => null];
        $numberSources = []; $strongNumbers = []; $strongDates = [];
        $numberReadings = []; $expiryReadings = [];
        $available = array_key_exists('field_reads', $document) || isset($document['words'][0]['line']);
        $expected = LicenseValidationRules::normalizeLicenseNumber($submitted['license_number']);
        foreach ($reads as $read) {
            $lines = self::lines($read);
            if (! $lines) continue;
            $available = true;
            $name = self::nameConfidence($lines, $submitted);
            if ($name !== null) $scores['name'] = max($scores['name'] ?? 0, $name);
            foreach (self::numberReadings($read) as $number) {
                $numberReadings[] = $number + ['source' => $read['source'] ?? 'full_gray'];
                if ($number['confidence'] >= 80) $strongNumbers[$number['value']] = true;
                if ($number['value'] !== $expected) continue;
                $scores['license_number'] = max($scores['license_number'] ?? 0, $number['confidence']);
                if ($number['confidence'] >= 40 && in_array($read['source'] ?? '', ['full_gray', 'body_gray', 'number_color'], true)) {
                    $numberSources[$read['source']] = true;
                }
            }
            foreach (self::expiryReadings($read) as $date) {
                $expiryReadings[] = $date + ['source' => $read['source'] ?? 'full_gray'];
                if ($date['confidence'] >= 80) $strongDates[$date['value']] = true;
                if ($date['value'] === $submitted['expiry_date']) $scores['expiry_date'] = max($scores['expiry_date'] ?? 0, $date['confidence']);
            }
        }
        $conflict = count($strongNumbers) > 1 || ($strongNumbers && ! isset($strongNumbers[$expected]));
        // Three distinct preparations must include a whole-card, a broad field
        // region and a color number region. Repeated segmentation is not a vote.
        $consensus = isset($numberSources['full_gray'], $numberSources['body_gray'], $numberSources['number_color']) && ! $conflict;
        $numberReliable = ($scores['license_number'] ?? 0) >= 80 || $consensus;

        $expiryConflict = count($strongDates) > 1;
        return ['available' => $available, 'reliable' => ! $conflict && ! $expiryConflict && ($scores['name'] ?? 0) >= 80
            && $numberReliable && ($scores['expiry_date'] ?? 0) >= 80,
            'fields' => $scores, 'number_consensus' => $consensus,
            'number_sources' => array_keys($numberSources), 'number_conflict' => (bool) $conflict,
            'number_readings' => $numberReadings, 'expiry_readings' => $expiryReadings, 'expiry_conflict' => $expiryConflict];
    }

    /** Dates must come from the expiry block; the birth date is not a fallback. */
    public static function expiryReadings(array $read): array
    {
        $lines = self::lines($read);
        if (! $lines) return [];
        $dates = [];
        foreach (self::near($lines, '/EXPIRATION|EXPIRY/', '/DATE OF BIRTH|NATIONALITY|BLOOD|EYES|CONDITIONS/') as $line) {
            if (preg_match('/DATE OF BIRTH|NATIONALITY/', self::normalize(self::text($line)))) continue;
            foreach (self::spans($line) as [$text, $confidence]) {
                $date = self::date($text);
                if ($date !== null) $dates[] = ['value' => $date, 'confidence' => $confidence];
            }
        }
        return $dates;
    }

    public static function readTexts(array $document): array
    {
        return array_values(array_map(fn ($read) => $read['text'], array_filter(
            $document['field_reads'] ?? [$document], fn ($read) => self::lines($read) !== []
        )));
    }

    public static function numberReadings(array $read): array
    {
        $lines = self::lines($read);
        if (! $lines) return [];
        // "Licenge" is a common C/G OCR confusion in the printed label. This
        // tolerance identifies the field only; its value must still match.
        $region = in_array($read['source'] ?? '', ['number_color', 'number_gray'], true) ? $lines : self::near($lines, '/\bLICEN[CSG]E\s*(?:NO\b|NUMBER\b)/');
        $numbers = [];
        foreach ($region as $line) foreach (self::spans($line) as [$text, $confidence]) {
            $value = self::number($text);
            if ($value !== null) $numbers[] = ['value' => $value, 'confidence' => $confidence];
        }
        return $numbers;
    }

    /** Confidence in the printed name block, independent of low-scoring background text. */
    public static function nameRegionConfidence(array $read): ?float
    {
        $scores = [];
        foreach (self::near(self::lines($read), '/(?:LAST|FIRST|FULL)\s*NAME/', '/NATIONALITY|DATE OF BIRTH|LICENSE NO|EXPIRATION|\bSEX\b/') as $line) {
            if (preg_match('/(?:LAST|FIRST|FULL)\s*NAME/', self::normalize(self::text($line)))) continue;
            foreach ($line as $word) if (self::tokens($word['text'])) $scores[] = (float) $word['confidence'];
        }
        return $scores ? min($scores) : null;
    }

    /** Word metadata must reproduce the OCR text; missing metadata never promotes. */
    private static function lines(array $read): array
    {
        $lines = [];
        foreach ($read['words'] ?? [] as $word) {
            if (! is_string($word['text'] ?? null) || ! is_string($word['line'] ?? null)
                || ! is_numeric($word['confidence'] ?? null) || $word['confidence'] < 0 || $word['confidence'] > 100) return [];
            $lines[$word['line']][] = $word;
        }
        $text = implode("\n", array_map(fn ($line) => self::text($line), $lines));
        if (self::normalize($text) !== self::normalize($read['text'] ?? '')) return [];
        return array_values($lines);
    }

    private static function nameConfidence(array $lines, array $submitted): ?float
    {
        $required = array_merge(self::tokens($submitted['first_name'] ?? ''), self::tokens($submitted['last_name'] ?? ''));
        if (! $required || empty($submitted['first_name']) || empty($submitted['last_name'])) return null;
        $found = [];
        foreach (self::near($lines, '/(?:LAST|FIRST|FULL)\s*NAME/', '/NATIONALITY|DATE OF BIRTH|LICENSE NO|EXPIRATION|\bSEX\b/') as $line) {
            if (preg_match('/(?:LAST|FIRST|FULL)\s*NAME/', self::normalize(self::text($line)))) continue;
            foreach ($line as $word) foreach (self::tokens($word['text']) as $token) {
                $found[$token] = max($found[$token] ?? 0, (float) $word['confidence']);
            }
        }
        $scores = [];
        foreach ($required as $token) {
            if (! array_key_exists($token, $found)) return null;
            $scores[] = $found[$token];
        }
        return min($scores);
    }

    private static function near(array $lines, string $pattern, ?string $stop = null): array
    {
        foreach ($lines as $i => $line) {
            if (! preg_match($pattern, self::normalize(self::text($line)))) continue;
            $region = [$line];
            foreach (array_slice($lines, $i + 1, 2) as $next) {
                if ($stop && preg_match($stop, self::normalize(self::text($next)))) break;
                $region[] = $next;
            }
            return $region;
        }
        return [];
    }

    private static function spans(array $line): array
    {
        $out = [];
        for ($i = 0; $i < count($line); $i++) for ($size = 1; $size <= min(5, count($line) - $i); $size++) {
            $words = array_slice($line, $i, $size);
            $out[] = [implode('', array_column($words, 'text')), min(array_column($words, 'confidence'))];
        }
        return $out;
    }

    private static function number(string $text): ?string
    {
        $n = preg_replace('/[^A-Z0-9]/', '', strtoupper($text));
        if (! preg_match('/^[A-Z0-9][0-9OILSB]{10}$/D', $n)) return null;
        $first = strtr($n[0], ['0' => 'O', '1' => 'I', '2' => 'Z', '5' => 'S', '6' => 'G', '8' => 'B']);
        $n = $first.strtr(substr($n, 1), ['O' => '0', 'I' => '1', 'L' => '1', 'S' => '5', 'B' => '8']);
        return preg_match('/^[A-Z][0-9]{10}$/D', $n) ? $n : null;
    }

    private static function date(string $text): ?string
    {
        if (preg_match('/^(19\d{2}|20\d{2})[\/.-](\d{1,2})[\/.-](\d{1,2})$/D', trim($text), $m)) [$year, $month, $day] = [(int) $m[1], (int) $m[2], (int) $m[3]];
        elseif (preg_match('/^(\d{1,2})[\/.-](\d{1,2})[\/.-](19\d{2}|20\d{2})$/D', trim($text), $m)) [$year, $month, $day] = [(int) $m[3], (int) $m[1], (int) $m[2]];
        else return null;
        return checkdate($month, $day, $year) ? sprintf('%04d-%02d-%02d', $year, $month, $day) : null;
    }

    private static function tokens(string $text): array { return preg_split('/[^A-Z]+/', self::normalize($text), -1, PREG_SPLIT_NO_EMPTY); }
    private static function text(array $words): string { return implode(' ', array_column($words, 'text')); }
    private static function normalize(string $text): string { return trim(preg_replace('/\s+/u', ' ', strtr(strtoupper($text), ['Ñ' => 'N', 'ñ' => 'N', '’' => "'", '‘' => "'"]))); }
}
