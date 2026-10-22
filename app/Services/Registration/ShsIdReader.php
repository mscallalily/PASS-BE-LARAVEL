<?php

namespace App\Services\Registration;

use App\Services\LicenseVerification\LicenseDocumentReader;
use GdImage;

/** Portrait DYCI Basic Education template. Reads text, never a roster or identity. */
class ShsIdReader
{
    public function __construct(private readonly LicenseDocumentReader $reader) {}

    public function read(string $path, string $mime): array
    {
        if (! in_array($mime, ['image/jpeg', 'image/png'], true)) {
            return ['kind' => 'pdf', 'engine_available' => true, 'fields' => [], 'field_confidence' => []];
        }
        $deadline = microtime(true) + 25;
        $full = $this->reader->read($path, $mime, 3, 5);
        $evidence = [
            'kind' => 'image', 'engine_available' => $full['engine_available'],
            'width' => $full['width'], 'height' => $full['height'], 'sharpness' => $full['sharpness'],
            'confidence' => $full['confidence'], 'school_marker' => InstitutionalIdSignals::school($full['text'] ?? ''),
            'shs_marker' => self::hasShsText($full['text'] ?? ''),
            'fields' => [], 'field_confidence' => [], 'field_conflicts' => [],
        ];
        if (! $full['engine_available']) return $evidence;
        $image = @imagecreatefromstring((string) file_get_contents($path));
        if (! $image) return $evidence;
        try {
            $orientation = function_exists('exif_read_data') ? (@exif_read_data($path)['Orientation'] ?? 1) : 1;
            if (in_array((int) $orientation, [2, 4, 5, 7], true)) imageflip($image, (int) $orientation === 4 ? IMG_FLIP_VERTICAL : IMG_FLIP_HORIZONTAL);
            $angle = match ((int) $orientation) { 3 => 180, 5, 6 => -90, 7, 8 => 90, default => 0 };
            if ($angle) {
                $rotated = imagerotate($image, $angle, 0);
                imagedestroy($image); $image = $rotated;
            }
            $scale = min(1, 2400 / imagesx($image), 3200 / imagesy($image));
            if ($scale < 1) {
                $scaled = imagescale($image, max(1, (int) floor(imagesx($image) * $scale)), -1, IMG_BILINEAR_FIXED);
                imagedestroy($image); $image = $scaled;
            }
            // Whole name rows retain second given names, compound surnames and the final initial.
            $regions = [
                'first_name' => [.08, .565, .94, .625],
                'last_name' => [.12, .515, .92, .575],
                'user_code' => [.24, .65, .79, .705],
                'school' => [.20, .015, .80, .165],
                'shs' => [.11, .918, .95, .972],
            ];
            foreach ($regions as $key => $box) {
                if (microtime(true) >= $deadline) break;
                $reads = $this->regionReads($image, $box, $key === 'school' ? 6 : 7, $deadline, $key === 'shs');
                if ($key === 'school' || $key === 'shs') {
                    foreach ($reads as $read) {
                        if ($key === 'school') $evidence['school_marker'] = $evidence['school_marker'] || InstitutionalIdSignals::school($read['text'] ?? '');
                        else $evidence['shs_marker'] = $evidence['shs_marker'] || self::hasShsText($read['text'] ?? '');
                    }
                    continue;
                }
                if ($key === 'user_code') $reads[] = $full;
                $candidates = array_values(array_filter(array_map(fn ($read) => self::candidate($read, $key), $reads)));
                if (! $candidates) continue;
                usort($candidates, fn ($a, $b) => $b['confidence'] <=> $a['confidence']);
                $best = $candidates[0];
                $strongValues = [];
                foreach ($candidates as $candidate) {
                    if ($candidate['confidence'] >= 80) $strongValues[json_encode($candidate['fields'])] = true;
                }
                $evidence['field_conflicts'][$key] = count($strongValues) > 1;
                if (isset($best['fields']['suffix'], $evidence['fields']['suffix']) && $best['fields']['suffix'] !== $evidence['fields']['suffix']) {
                    $evidence['field_conflicts']['suffix'] = true;
                }
                $evidence['fields'] = array_replace($evidence['fields'], $best['fields']);
                $evidence['field_confidence'][$key] = $best['confidence'];
                if (isset($best['fields']['middle_initial'])) $evidence['field_confidence']['middle_initial'] = $best['initial_confidence'];
                if (isset($best['fields']['suffix'])) $evidence['field_confidence']['suffix'] = min($evidence['field_confidence']['suffix'] ?? 100, $best['confidence']);
            }
        } finally {
            imagedestroy($image);
        }

        if (! $evidence['school_marker']) {
            $evidence['school_marker'] = (new InstitutionalSchoolBranding($this->reader))->confirm($path, 'shs', $deadline);
        }

        return $evidence;
    }

    private function regionReads(GdImage $image, array $box, int $mode, float $deadline, bool $lightText = false): array
    {
        [$l, $t, $r, $b] = $box;
        $crop = imagecrop($image, ['x' => (int) ($l * imagesx($image)), 'y' => (int) ($t * imagesy($image)),
            'width' => max(1, (int) (($r - $l) * imagesx($image))), 'height' => max(1, (int) (($b - $t) * imagesy($image)))]);
        if (! $crop) return [];
        $temp = tempnam(sys_get_temp_dir(), 'pass_shs_');
        $scaled = null;
        try {
            $scaled = imagescale($crop, 1200, -1, IMG_BILINEAR_FIXED);
            $reads = [];
            foreach ($lightText ? ['color', 'contrast', 'inverse'] : ['color', 'contrast'] as $variant) {
                if (microtime(true) >= $deadline) break;
                if ($variant === 'contrast') self::stretchContrast($scaled);
                if ($variant === 'inverse') imagefilter($scaled, IMG_FILTER_NEGATE);
                $canvas = imagecreatetruecolor(imagesx($scaled) + 60, imagesy($scaled) + 60);
                try {
                    imagefill($canvas, 0, 0, 0xFFFFFF);
                    imagecopy($canvas, $scaled, 30, 30, 0, 0, imagesx($scaled), imagesy($scaled));
                    imagepng($canvas, $temp);
                    $reads[] = $this->reader->readTextImage($temp, $mode, max(1, min(3, (int) ceil($deadline - microtime(true)))));
                } finally { imagedestroy($canvas); }
            }

            return $reads;
        } finally {
            imagedestroy($crop);
            if ($scaled) imagedestroy($scaled);
            @unlink($temp);
        }
    }

    /** Local contrast compensates for a shaded sleeve without inventing characters. */
    private static function stretchContrast(GdImage $image): void
    {
        imagefilter($image, IMG_FILTER_GRAYSCALE);
        $histogram = array_fill(0, 256, 0); $w = imagesx($image); $h = imagesy($image);
        for ($y = 0; $y < $h; $y++) for ($x = 0; $x < $w; $x++) $histogram[imagecolorat($image, $x, $y) & 255]++;
        $trim = (int) floor($w * $h * .01); $sum = 0; $low = 0; $high = 255;
        foreach ($histogram as $value => $count) { $sum += $count; if ($sum > $trim) { $low = $value; break; } }
        $sum = 0;
        for ($value = 255; $value >= 0; $value--) { $sum += $histogram[$value]; if ($sum > $trim) { $high = $value; break; } }
        if ($high <= $low) return;
        $lookup = [];
        for ($v = 0; $v < 256; $v++) { $n = max(0, min(255, (int) round(($v - $low) * 255 / ($high - $low)))); $lookup[$v] = ($n << 16) | ($n << 8) | $n; }
        for ($y = 0; $y < $h; $y++) for ($x = 0; $x < $w; $x++) imagesetpixel($image, $x, $y, $lookup[imagecolorat($image, $x, $y) & 255]);
    }

    public static function hasShsText(string $text): bool
    {
        $text = strtr(mb_strtoupper($text), ['0' => 'O', '1' => 'I']);
        if (str_contains(preg_replace('/[^A-Z]/', '', $text), 'SENIORHIGHSCHOOL')) return true;
        $words = preg_split('/[^A-Z]+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        for ($i = 0; $i + 2 < count($words); $i++) {
            // One glyph error per word is an OCR category clue, never proof of school identity.
            if (levenshtein($words[$i], 'SENIOR') <= 1 && levenshtein($words[$i + 1], 'HIGH') <= 1 && levenshtein($words[$i + 2], 'SCHOOL') <= 1) return true;
        }

        return false;
    }

    /** Keep confidence from actual OCR words; never compare against an expected person's name. */
    public static function candidate(array $read, string $key): ?array
    {
        if ($key === 'user_code') {
            $numbers = InstitutionalIdReader::numbers($read['text'] ?? '', 'shs');
            if (count($numbers) !== 1) return null;
            $words = array_filter($read['words'] ?? [], fn ($word) => preg_match('/^[0-9OIL -]+$/i', $word['text']));
            $confidence = $words ? min(array_column($words, 'confidence')) : ($read['confidence'] ?? 0);

            return ['fields' => ['user_code' => $numbers[0]], 'confidence' => (float) $confidence];
        }
        $words = [];
        foreach ($read['words'] ?? [] as $word) {
            if (preg_match("/^[\\pL\\pM][\\pL\\pM.'-]*$/u", $word['text'])) $words[] = $word;
            elseif (preg_match('/[\\pL\\pN]/u', $word['text'])) return null; // Never drop a damaged name token and claim a complete name.
        }
        $text = implode(' ', array_column($words, 'text'));
        if (! $words || mb_strlen($text) > 100 || preg_match('/\b(?:SENIOR|HIGH|SCHOOL|BASIC|GRADE|STRAND|LEARNER|REFERENCE|NUMBER)\b/i', $text)) return null;
        $fields = InstitutionalNameRows::fields($text, $key);
        if (mb_strlen($fields[$key] ?? '') < 2) return null;

        return ['fields' => $fields, 'confidence' => round(array_sum(array_column($words, 'confidence')) / count($words), 1),
            'initial_confidence' => isset($fields['middle_initial']) ? InstitutionalNameRows::initialConfidence($read, $fields['middle_initial']) : 0];
    }
}
