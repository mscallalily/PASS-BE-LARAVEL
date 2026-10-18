<?php

namespace App\Services\Registration;

use App\Services\LicenseVerification\LicenseDocumentReader;

/** Uses field positions from the two supplied portrait DYCI templates, not highlight colors. */
class InstitutionalIdReader
{
    public function __construct(private LicenseDocumentReader $reader) {}

    public function read(string $path, string $mime, string $category): array
    {
        if ($mime !== 'image/jpeg' && $mime !== 'image/png') {
            return ['kind' => 'pdf', 'engine_available' => true, 'fields' => [], 'field_confidence' => []];
        }
        $full = $this->reader->read($path, $mime, 3, 20);
        $compact = preg_replace('/[^A-Z0-9]/', '', strtoupper($full['text'] ?? ''));
        $evidence = [
            'kind' => 'image', 'engine_available' => $full['engine_available'],
            'confidence' => $full['confidence'], 'width' => $full['width'], 'height' => $full['height'], 'sharpness' => $full['sharpness'],
            'school_marker' => str_contains($compact, 'DYCIEDUPH') || (str_contains($compact, 'YANGA') && str_contains($compact, 'COLLEG')),
            'fields' => [], 'field_confidence' => [],
        ];
        if (! $full['engine_available']) {
            return $evidence;
        }
        $image = @imagecreatefromstring(file_get_contents($path));
        if (! $image) {
            return $evidence;
        }
        // Follow phone orientation before using relative template regions.
        if (function_exists('exif_read_data')) {
            $orientation = @exif_read_data($path)['Orientation'] ?? 1;
            $angle = match ((int) $orientation) {
                3 => 180, 6 => -90, 8 => 90, default => 0
            };
            if ($angle) {
                $rotated = imagerotate($image, $angle, 0);
                imagedestroy($image);
                $image = $rotated;
            }
        }
        $boxes = $category === 'employee'
            ? ['first_name' => [.52, .24, .98, .305], 'last_name' => [.52, .29, .98, .355], 'user_code' => [.59, .86, .98, .91], 'school' => [.5, .95, .99, .995]]
            : ['first_name' => [.22, .787, .85, .838], 'last_name' => [.1, .728, .98, .78], 'user_code' => [.23, .681, .8, .715], 'school' => [.25, .215, .75, .242]];
        try {
            foreach ($boxes as $key => [$left, $top, $right, $bottom]) {
                $crop = imagecrop($image, ['x' => (int) ($left * imagesx($image)), 'y' => (int) ($top * imagesy($image)),
                    'width' => max(1, (int) (($right - $left) * imagesx($image))), 'height' => max(1, (int) (($bottom - $top) * imagesy($image)))]);
                if (! $crop) {
                    continue;
                }
                $scaled = imagescale($crop, 1600, -1, IMG_BICUBIC);
                imagedestroy($crop);
                $canvas = imagecreatetruecolor(imagesx($scaled) + 40, imagesy($scaled) + 40);
                imagefill($canvas, 0, 0, imagecolorallocate($canvas, 255, 255, 255));
                imagecopy($canvas, $scaled, 20, 20, 0, 0, imagesx($scaled), imagesy($scaled));
                imagedestroy($scaled);
                imagefilter($canvas, IMG_FILTER_GRAYSCALE);
                $temp = tempnam(sys_get_temp_dir(), 'pass_id_');
                try {
                    imagepng($canvas, $temp);
                    $reads = [$this->reader->readTextImage($temp, 6), $this->reader->readTextImage($temp, 7)];
                    // A second contrast pass handles shaded or marked-up samples;
                    // neither color detection nor highlights are required by the template.
                    if (max(array_map(fn ($r) => $this->fieldConfidence($r, $key, $category), $reads)) < 90) {
                        for ($y = 0; $y < imagesy($canvas); $y++) {
                            for ($x = 0; $x < imagesx($canvas); $x++) {
                                $v = (imagecolorat($canvas, $x, $y) & 255) >= 120 ? 0xFFFFFF : 0;
                                imagesetpixel($canvas, $x, $y, $v);
                            }
                        }
                        imagepng($canvas, $temp);
                        $reads[] = $this->reader->readTextImage($temp, 6);
                        $reads[] = $this->reader->readTextImage($temp, 7);
                    }
                    usort($reads, fn ($a, $b) => $this->fieldConfidence($b, $key, $category) <=> $this->fieldConfidence($a, $key, $category));
                    $read = $reads[0];
                    $read['confidence'] = $this->fieldConfidence($read, $key, $category);
                    if ($key === 'school') {
                        foreach ($reads as $schoolRead) {
                            $compactRegion = preg_replace('/[^A-Z0-9]/', '', strtoupper($schoolRead['text'] ?? ''));
                            $evidence['school_marker'] = $evidence['school_marker'] || str_contains($compactRegion, 'DYCIEDUPH');
                        }

                        continue;
                    }
                } finally {
                    @unlink($temp);
                    imagedestroy($canvas);
                }
                $text = trim($read['text'] ?? '');
                if ($key === 'user_code') {
                    $candidates = self::numbers($text, $category);
                    if (! $candidates) {
                        // Full-card OCR is a fallback; never chop a longer number into a shorter ID.
                        $candidates = self::numbers($full['text'] ?? '', $category);
                        $read['confidence'] = $full['confidence'];
                    }
                    if (count($candidates) === 1) {
                        $evidence['fields'][$key] = InstitutionalIdRules::format($category, $candidates[0]);
                    }
                } else {
                    $text = trim(preg_replace('/[^\pL\pM .\'-]/u', '', $text), " .-\t\r\n");
                    if ($text !== '' && mb_strlen($text) <= 100 && count(preg_split('/\s+/', $text)) <= 6
                        && ! preg_match('/COLLEGE|STUDENT|DEPARTMENT|LICENSE|MAINTENANCE|OFFICER|SERVICES|MAGIS/i', $text)) {
                        if ($key === 'first_name' && preg_match('/^(.*?)\s+([\pL])\.?$/u', $text, $m)) {
                            $evidence['fields']['middle_initial'] = mb_strtoupper($m[2]).'.';
                            $text = trim($m[1]);
                        }
                        $evidence['fields'][$key] = $text;
                    }
                }
                $evidence['field_confidence'][$key] = $read['confidence'] ?? 0;
            }
        } finally {
            imagedestroy($image);
        }

        return $evidence;
    }

    private function fieldConfidence(array $read, string $key, string $category): float
    {
        $tokens = $read['words'] ?? [];
        $valid = array_filter($tokens, function ($token) use ($key, $category) {
            if ($key === 'user_code') {
                return count(self::numbers($token['text'], $category)) === 1;
            }
            $text = trim(InstitutionalIdRules::name($token['text']));

            return mb_strlen($text) >= 2 && ! preg_match('/COLLEGE|STUDENT|DEPARTMENT|MAINTENANCE|OFFICER|SERVICES|MAGIS/', $text);
        });

        return $valid ? round(array_sum(array_column($valid, 'confidence')) / count($valid), 1) : (float) ($read['confidence'] ?? 0);
    }

    public static function numbers(string $text, string $category): array
    {
        $a = $category === 'employee' ? 3 : 4;
        $b = $category === 'employee' ? 4 : 5;
        preg_match_all('/(?<![A-Z0-9])([0-9OIL]{'.$a.'})[ -]*([0-9OIL]{'.$b.'})(?![A-Z0-9])/i', $text, $matches, PREG_SET_ORDER);

        return array_values(array_unique(array_map(fn ($m) => strtr(strtoupper($m[1].$m[2]), ['O' => '0', 'I' => '1', 'L' => '1']), $matches)));
    }
}
