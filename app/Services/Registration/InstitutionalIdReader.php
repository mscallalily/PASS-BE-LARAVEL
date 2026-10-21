<?php

namespace App\Services\Registration;

use App\Services\LicenseVerification\LicenseDocumentReader;

/** Uses field positions from the two supplied portrait DYCI templates, not highlight colors. */
class InstitutionalIdReader
{
    public function __construct(private LicenseDocumentReader $reader) {}

    public function read(string $path, string $mime, string $category): array
    {
        if ($category === 'shs') {
            return app(ShsIdReader::class)->read($path, $mime);
        }
        if ($mime !== 'image/jpeg' && $mime !== 'image/png') {
            return ['kind' => 'pdf', 'engine_available' => true, 'fields' => [], 'field_confidence' => []];
        }
        $full = $this->reader->read($path, $mime, 3, 20);
        $compact = preg_replace('/[^A-Z0-9]/', '', strtoupper($full['text'] ?? ''));
        $evidence = [
            'kind' => 'image', 'engine_available' => $full['engine_available'],
            'confidence' => $full['confidence'], 'width' => $full['width'], 'height' => $full['height'], 'sharpness' => $full['sharpness'],
            'school_marker' => InstitutionalIdSignals::school($full['text'] ?? ''),
            'fields' => [], 'field_confidence' => [], 'field_conflicts' => [],
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
            ? ['first_name' => [.52, .24, .98, .305], 'last_name' => [.40, .29, .995, .355], 'user_code' => [.59, .86, .98, .91], 'school' => [.5, .95, .99, .995]]
            : ['first_name' => [.015, .787, .995, .838], 'last_name' => [.015, .72, .995, .785], 'user_code' => [.23, .681, .8, .715], 'school' => [.25, .215, .75, .242]];
        try {
            foreach ($boxes as $key => [$left, $top, $right, $bottom]) {
                $crop = imagecrop($image, ['x' => (int) ($left * imagesx($image)), 'y' => (int) ($top * imagesy($image)),
                    'width' => max(1, (int) (($right - $left) * imagesx($image))), 'height' => max(1, (int) (($bottom - $top) * imagesy($image)))]);
                if (! $crop) {
                    continue;
                }
                $nameColor = in_array($key, ['first_name', 'last_name'], true) ? imagescale($crop, 1000, -1, IMG_BILINEAR_FIXED) : null;
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
                    $reads = [$this->reader->readTextImage($temp, 6) + ['source' => 'broad_gray'], $this->reader->readTextImage($temp, 7) + ['source' => 'broad_gray']];
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
                        $reads[] = $this->reader->readTextImage($temp, 6) + ['source' => 'broad_threshold'];
                        $reads[] = $this->reader->readTextImage($temp, 7) + ['source' => 'broad_threshold'];
                    }
                    if ($nameColor && max(array_map(fn ($r) => $this->fieldConfidence($r, $key, $category), $reads)) < 90) {
                        // Keep colored lettering/background information and try a
                        // different scale; never raise the stored OCR confidence.
                        $namePadded = imagecreatetruecolor(imagesx($nameColor) + 40, imagesy($nameColor) + 40);
                        imagefill($namePadded, 0, 0, imagecolorallocate($namePadded, 255, 255, 255));
                        imagecopy($namePadded, $nameColor, 20, 20, 0, 0, imagesx($nameColor), imagesy($nameColor));
                        imagepng($namePadded, $temp);
                        imagedestroy($namePadded);
                        $reads[] = $this->reader->readTextImage($temp, 7);
                    }
                    if ($key === 'user_code') {
                        // Number-only color crop avoids label/barcode interference and
                        // preserves colored ink/background separation lost in grayscale.
                        $numberBox = $category === 'employee' ? [.79, .867, .965, .895] : [.49, .683, .78, .713];
                        [$nl, $nt, $nr, $nb] = $numberBox;
                        $numberCrop = imagecrop($image, ['x' => (int) ($nl * imagesx($image)), 'y' => (int) ($nt * imagesy($image)),
                            'width' => max(1, (int) ($nr * imagesx($image)) - (int) ($nl * imagesx($image))), 'height' => max(1, (int) ($nb * imagesy($image)) - (int) ($nt * imagesy($image)))]);
                        if ($numberCrop) {
                            $color = imagescale($numberCrop, 1200, -1, IMG_BILINEAR_FIXED);
                            imagedestroy($numberCrop);
                            $padded = imagecreatetruecolor(imagesx($color) + 40, imagesy($color) + 40);
                            imagefill($padded, 0, 0, imagecolorallocate($padded, 255, 255, 255));
                            imagecopy($padded, $color, 20, 20, 0, 0, imagesx($color), imagesy($color));
                            imagedestroy($color);
                            imagepng($padded, $temp);
                            $numberRead = $this->reader->readTextImage($temp, 7) + ['source' => 'tight_color'];
                            $numberRead['number_confidence'] = $this->fieldConfidence($numberRead, 'user_code', $category);
                            $broadNumbers = array_values(array_unique(array_merge(...array_map(fn ($r) => self::numbers($r['text'] ?? '', $category), $reads))));
                            $narrowNumbers = self::numbers($numberRead['text'] ?? '', $category);
                            // Tight crops may clip digits. Boost only when a broader
                            // read independently contains the same complete number.
                            if (count($narrowNumbers) !== 1 || ! in_array($narrowNumbers[0], $broadNumbers, true)) {
                                $numberRead['confidence'] = min(79, $numberRead['confidence'] ?? 0);
                                foreach ($numberRead['words'] ?? [] as $i => $word) {
                                    $numberRead['words'][$i]['confidence'] = min(79, $word['confidence']);
                                }
                            }
                            $reads[] = $numberRead;
                            if ($this->fieldConfidence($numberRead, 'user_code', $category) < 80) {
                                imagefilter($padded, IMG_FILTER_GRAYSCALE);
                                imagepng($padded, $temp);
                                $reads[] = $this->reader->readTextImage($temp, 7) + ['source' => 'tight_gray'];
                                // A contrast-enhanced crop provides a further check;
                                // changing page segmentation alone is not another method.
                                imagefilter($padded, IMG_FILTER_CONTRAST, -60);
                                imagepng($padded, $temp);
                                $reads[] = $this->reader->readTextImage($temp, 7) + ['source' => 'tight_contrast'];
                            }
                            imagedestroy($padded);
                        }
                        if (max(array_map(fn ($r) => $this->fieldConfidence($r, 'user_code', $category), $reads)) < 80) {
                            $reads = array_merge($reads, $this->numberMarginReads($image, $category));
                        }
                        $reads[] = $full + ['source' => 'full_card'];
                        foreach ($reads as $i => $r) {
                            $reads[$i]['number_confidence'] ??= $this->fieldConfidence($r, 'user_code', $category);
                        }
                        $evidence['number_consensus'] = InstitutionalIdSignals::numberConsensus($reads, $category);
                        $evidence['field_conflicts'][$key] = $this->numberConflict($reads, $category);
                    }
                    // PASS_NAME_ROW_RECOVERY: preserve the complete line before ranking OCR reads.
                    if ($key === 'first_name' && max(array_map(fn ($r) => $this->fieldConfidence($r, $key, $category), $reads)) < 90) {
                        $reads = array_merge($reads, app(InstitutionalNameRows::class)->read($image, $category));
                    }
                    usort($reads, function ($a, $b) use ($key, $category) {
                        if ($key === 'user_code') {
                            $validA = count(self::numbers($a['text'] ?? '', $category)) === 1;
                            $validB = count(self::numbers($b['text'] ?? '', $category)) === 1;
                            if ($validA !== $validB) {
                                return $validB <=> $validA;
                            }
                        }

                        return $this->fieldConfidence($b, $key, $category) <=> $this->fieldConfidence($a, $key, $category);
                    });
                    $read = $reads[0];
                    $read['confidence'] = $this->fieldConfidence($read, $key, $category);
                    if ($key === 'school') {
                        foreach ($reads as $schoolRead) {
                            $compactRegion = preg_replace('/[^A-Z0-9]/', '', strtoupper($schoolRead['text'] ?? ''));
                            $evidence['school_marker'] = $evidence['school_marker'] || InstitutionalIdSignals::school($schoolRead['text'] ?? '');
                        }

                        continue;
                    }
                } finally {
                    @unlink($temp);
                    imagedestroy($canvas);
                    if ($nameColor) {
                        imagedestroy($nameColor);
                    }
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
                    $text = trim(preg_replace('/[^\pL\pM ,.\'-]/u', '', $text), " .-\t\r\n");
                    if ($text !== '' && mb_strlen($text) <= 100 && count(preg_split('/\s+/', $text)) <= 12
                        && ! preg_match('/COLLEGE|STUDENT|DEPARTMENT|LICENSE|MAINTENANCE|OFFICER|SERVICES|MAGIS/i', $text)) {
                        $parts = InstitutionalNameRows::fields($text, $key);
                        if (isset($parts['middle_initial'])) $evidence['field_confidence']['middle_initial'] = InstitutionalNameRows::initialConfidence($read, $parts['middle_initial']);
                        if (isset($parts['suffix'])) {
                            if (isset($evidence['fields']['suffix']) && $evidence['fields']['suffix'] !== $parts['suffix']) {
                                $evidence['field_conflicts']['suffix'] = true;
                            }
                            $evidence['field_confidence']['suffix'] = min($evidence['field_confidence']['suffix'] ?? 100, $read['confidence'] ?? 0);
                        }
                        $evidence['fields'] = array_replace($evidence['fields'], $parts);
                    }
                }
                $evidence['field_confidence'][$key] = $read['confidence'] ?? 0;
            }
        } finally {
            imagedestroy($image);
        }

        if (! $evidence['school_marker']) {
            $evidence['school_marker'] = $this->readSchoolBranding($path, $category);
        }

        return $evidence;
    }

    private function numberMarginReads(\GdImage $image, string $category): array
    {
        // Extra margins tolerate small framing/resize differences and soft edges.
        [$l, $t, $r, $b] = $category === 'employee' ? [.77, .855, .99, .905] : [.47, .675, .80, .722];
        $crop = imagecrop($image, ['x' => (int) ($l * imagesx($image)), 'y' => (int) ($t * imagesy($image)),
            'width' => max(1, (int) ($r * imagesx($image)) - (int) ($l * imagesx($image))),
            'height' => max(1, (int) ($b * imagesy($image)) - (int) ($t * imagesy($image)))]);
        if (! $crop) {
            return [];
        }
        $scaled = imagescale($crop, 1000, -1, IMG_BILINEAR_FIXED);
        imagedestroy($crop);
        $canvas = imagecreatetruecolor(imagesx($scaled) + 40, imagesy($scaled) + 40);
        imagefill($canvas, 0, 0, imagecolorallocate($canvas, 255, 255, 255));
        imagecopy($canvas, $scaled, 20, 20, 0, 0, imagesx($scaled), imagesy($scaled));
        imagedestroy($scaled);
        $temp = tempnam(sys_get_temp_dir(), 'pass_number_');
        try {
            imagepng($canvas, $temp);
            $reads = [$this->reader->readTextImage($temp, 7) + ['source' => 'tight_color']];
            imagefilter($canvas, IMG_FILTER_GRAYSCALE);
            imagepng($canvas, $temp);
            $reads[] = $this->reader->readTextImage($temp, 7) + ['source' => 'tight_gray'];
            [$bl,$bt,$br,$bb] = $category === 'employee' ? [.59, .85, .995, .91] : [.23, .675, .82, .722];
            $broad = imagecrop($image, ['x' => (int) ($bl * imagesx($image)), 'y' => (int) ($bt * imagesy($image)),
                'width' => max(1, (int) ($br * imagesx($image)) - (int) ($bl * imagesx($image))),
                'height' => max(1, (int) ($bb * imagesy($image)) - (int) ($bt * imagesy($image)))]);
            if ($broad) {
                $bscaled = imagescale($broad, 1000, -1, IMG_BILINEAR_FIXED);
                imagedestroy($broad);
                $bpadded = imagecreatetruecolor(imagesx($bscaled) + 40, imagesy($bscaled) + 40);
                imagefill($bpadded, 0, 0, imagecolorallocate($bpadded, 255, 255, 255));
                imagecopy($bpadded, $bscaled, 20, 20, 0, 0, imagesx($bscaled), imagesy($bscaled));
                imagedestroy($bscaled);
                imagepng($bpadded, $temp);
                imagedestroy($bpadded);
                $reads[] = $this->reader->readTextImage($temp, 7) + ['source' => 'broad_color'];
            }

            return $reads;
        } finally {
            imagedestroy($canvas);
            @unlink($temp);
        }
    }

    private function readSchoolBranding(string $path, string $category): bool
    {
        $image = @imagecreatefromstring(file_get_contents($path));
        if (! $image) {
            return false;
        }
        // Same orientation handling as field extraction.
        $orientation = function_exists('exif_read_data') ? (@exif_read_data($path)['Orientation'] ?? 1) : 1;
        $angle = match ((int) $orientation) {
            3 => 180, 6 => -90, 8 => 90, default => 0
        };
        if ($angle) {
            $rotated = imagerotate($image, $angle, 0);
            imagedestroy($image);
            $image = $rotated;
        }
        $boxes = $category === 'employee' ? [[.38, 0, .995, .23], [.48, .94, .995, 1]] : [[.02, 0, .995, .25]];
        try {
            foreach ($boxes as [$l, $t, $r, $b]) {
                $crop = imagecrop($image, ['x' => (int) ($l * imagesx($image)), 'y' => (int) ($t * imagesy($image)),
                    'width' => max(1, (int) (($r - $l) * imagesx($image))), 'height' => max(1, (int) (($b - $t) * imagesy($image)))]);
                if (! $crop) {
                    continue;
                }
                $scaled = imagescale($crop, 1600, -1, IMG_BILINEAR_FIXED);
                imagedestroy($crop);
                $temp = tempnam(sys_get_temp_dir(), 'pass_brand_');
                try {
                    imagepng($scaled, $temp);
                    $read = $this->reader->readTextImage($temp, 6);
                    if (InstitutionalIdSignals::school($read['text'] ?? '')) {
                        return true;
                    }
                } finally {
                    imagedestroy($scaled);
                    @unlink($temp);
                }
            }
        } finally {
            imagedestroy($image);
        }

        return false;
    }

    /** Keep suffix and initial separate without splitting compound surnames. */
    public static function nameFields(string $text, string $key): array
    {
        $text = trim(preg_replace('/\s+/u', ' ', $text));
        $fields = [];
        $suffixPattern = $key === 'last_name' ? 'JR|SR|VIII|VII|III|VI|IV|II|IX|X|V|I' : 'JR|SR|VIII|VII|III|VI|IV|II|IX';
        if (preg_match('/^(.*?)\s*,?\s+('.$suffixPattern.')\.?$/iu', $text, $m) && trim($m[1]) !== '') {
            $fields['suffix'] = InstitutionalIdRules::suffix($m[2]);
            $text = rtrim(trim($m[1]), ',');
        }
        if ($key === 'first_name' && preg_match('/^(.*?)\s+([\pL])\.?$/u', $text, $m)) {
            $fields['middle_initial'] = mb_strtoupper($m[2]).'.';
            $text = trim($m[1]);
        }
        $fields[$key] = $text;

        return $fields;
    }

    /** Conflicting strong reads must go to review, never select by the submitted ID. */
    public function numberConflict(array $reads, string $category): bool
    {
        $strong = [];
        foreach ($reads as $read) {
            if ($this->fieldConfidence($read, 'user_code', $category) >= 80) {
                $strong = array_merge($strong, self::numbers($read['text'] ?? '', $category));
            }
        }

        return count(array_unique($strong)) > 1;
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
        if ($category === 'shs') {
            // Read maximal numeric runs first so a longer spaced number cannot
            // be silently shortened to twelve digits.
            preg_match_all('/(?<![A-Z0-9])[0-9OIL]+(?:[ \t-]+[0-9OIL]+)*(?![A-Z0-9])/i', $text, $matches);
            $numbers = array_map(fn ($n) => strtr(preg_replace('/[ \t-]+/', '', strtoupper($n)), ['O' => '0', 'I' => '1', 'L' => '1']), $matches[0]);

            return array_values(array_unique(array_filter($numbers, fn ($n) => strlen($n) === 12)));
        }
        $a = $category === 'employee' ? 3 : 4;
        $b = $category === 'employee' ? 4 : 5;
        preg_match_all('/(?<![A-Z0-9])([0-9OIL]{'.$a.'})[ -]*([0-9OIL]{'.$b.'})(?![A-Z0-9])/i', $text, $matches, PREG_SET_ORDER);

        return array_values(array_unique(array_map(fn ($m) => strtr(strtoupper($m[1].$m[2]), ['O' => '0', 'I' => '1', 'L' => '1']), $matches)));
    }
}
