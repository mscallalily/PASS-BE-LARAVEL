<?php

namespace App\Services\Registration;

/** Corroboration from OCR passes, not a probability of identity/authenticity. */
class InstitutionalIdSignals
{
    private const SOURCES = ['broad_gray', 'broad_threshold', 'broad_color', 'full_card', 'tight_color', 'tight_gray', 'tight_contrast'];

    public static function numberConsensus(array $reads, string $category): array
    {
        $values = [];
        $sources = [];
        foreach ($reads as $read) {
            if (($read['number_confidence'] ?? 0) < 40) {
                continue;
            }
            $numbers = InstitutionalIdReader::numbers($read['text'] ?? '', $category);
            foreach ($numbers as $number) {
                $values[$number] = true;
            }
            if (count($numbers) === 1 && in_array($read['source'] ?? '', self::SOURCES, true)) {
                $sources[$read['source']] = true;
            }
        }
        $sources = array_keys($sources);
        $value = count($values) === 1 ? (string) array_key_first($values) : null;
        $groups = count($sources);
        $broad = count(array_intersect($sources, ['broad_gray', 'broad_threshold', 'broad_color', 'full_card'])) > 0;
        $tight = count(array_intersect($sources, ['tight_color', 'tight_gray', 'tight_contrast'])) > 0;

        return ['value' => $value, 'sources' => $sources, 'groups' => $groups,
            'reliable' => $value !== null && $groups >= 3 && $broad && $tight,
            'conflicting_values' => count($values) > 1];
    }

    public static function confirmsNumber(array $signal, string $number): bool
    {
        $sources = array_values(array_intersect(array_unique($signal['sources'] ?? []), self::SOURCES));

        return ! empty($signal['reliable']) && empty($signal['conflicting_values'])
            && ($signal['value'] ?? '') === InstitutionalIdRules::digits($number)
            && count($sources) >= 3
            && count(array_intersect($sources, ['broad_gray', 'broad_threshold', 'broad_color', 'full_card'])) > 0
            && count(array_intersect($sources, ['tight_color', 'tight_gray', 'tight_contrast'])) > 0;
    }

    public static function school(string $text): bool
    {
        $compact = preg_replace('/[^A-Z0-9]/', '', strtoupper($text));
        // Common OCR glyph substitutions are confined to school branding.
        $compact = strtr($compact, ['0' => 'O', '1' => 'I']);
        if (str_contains($compact, 'YANGA') && str_contains($compact, 'COLLEG')) {
            return true;
        }
        if (str_contains($compact, 'DYCIEDUPH')) {
            return true;
        }
        // One OCR edit in the full official domain is allowed, not a guessed domain.
        for ($offset = 0; ($at = strpos($compact, 'DYC', $offset)) !== false; $offset = $at + 3) {
            foreach ([8, 9, 10] as $length) {
                if (strlen($compact) - $at >= $length && levenshtein(substr($compact, $at, $length), 'DYCIEDUPH') <= 1) {
                    return true;
                }
            }
        }

        return str_contains($compact, 'DYCI') && str_contains($compact, 'MAGISQUAMSCHOLA');
    }
}
