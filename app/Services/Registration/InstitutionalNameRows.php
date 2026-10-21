<?php

namespace App\Services\Registration;

use App\Services\LicenseVerification\LicenseDocumentReader;
use GdImage;

/** Extra line reads for clipped, long given names. Never substitutes a known person's name. */
class InstitutionalNameRows
{
    public function __construct(private readonly LicenseDocumentReader $reader) {}

    public function read(GdImage $image, string $category): array
    {
        // Generous horizontal margins retain second given names and the final initial.
        [$left, $top, $right, $bottom, $center] = $category === 'employee'
            ? [.40, .225, 1.0, .315, .272]
            : [0.0, .755, 1.0, .842, .800];
        $box = ['x' => (int) ($left * imagesx($image)), 'y' => (int) ($top * imagesy($image)),
            'width' => max(1, (int) (($right - $left) * imagesx($image))),
            'height' => max(1, (int) (($bottom - $top) * imagesy($image)))];
        $region = imagecrop($image, $box);
        if (!$region) return [];
        $reads = [];
        $temp = tempnam(sys_get_temp_dir(), 'pass_name_');
        $deadline = microtime(true) + 12;
        try {
            $scan = imagescale($region, 800, -1, IMG_BILINEAR_FIXED);
            $band = self::lineBand($scan, ($center - $top) / ($bottom - $top));
            imagedestroy($scan);
            if ($band) {
                $line = imagecrop($region, ['x' => 0, 'y' => (int) floor($band[0] * imagesy($region)),
                    'width' => imagesx($region), 'height' => max(1, (int) ceil(($band[1] - $band[0]) * imagesy($region)))]);
            } else {
                // No clear text band: return no extra evidence rather than guessing a name.
                return [];
            }
            if (!$line) return [];
            try {
                $scaled = imagescale($line, 1600, -1, IMG_BILINEAR_FIXED);
                $canvas = imagecreatetruecolor(1680, imagesy($scaled) + 80);
                imagefill($canvas, 0, 0, imagecolorallocate($canvas, 255, 255, 255));
                imagecopy($canvas, $scaled, 40, 40, 0, 0, imagesx($scaled), imagesy($scaled));
                imagedestroy($scaled);
                try {
                    foreach ([7, 13] as $mode) {
                        if (microtime(true) >= $deadline - 3) break;
                        imagepng($canvas, $temp);
                        $read = $this->reader->read($temp, 'image/png', $mode, 3);
                        $reads[] = $read + ['source' => 'projected_name_row'];
                    }
                    if (microtime(true) < $deadline - 3) {
                        imagefilter($canvas, IMG_FILTER_GRAYSCALE);
                        imagefilter($canvas, IMG_FILTER_CONTRAST, -35);
                        imagepng($canvas, $temp);
                        $reads[] = $this->reader->read($temp, 'image/png', 7, 3) + ['source' => 'projected_name_contrast'];
                    }
                } finally { imagedestroy($canvas); }
            } finally { imagedestroy($line); }
        } finally { imagedestroy($region); @unlink($temp); }
        return $reads;
    }

    /** Locate a complete ink band, retaining ascenders, descenders and punctuation. */
    public static function lineBand(GdImage $region, float $expectedCenter): ?array
    {
        $w = imagesx($region); $h = imagesy($region); $profile = []; $histogram = array_fill(0, 256, 0);
        for ($y = 0; $y < $h; $y++) {
            for ($x = 0; $x < $w; $x++) {
                $rgb = imagecolorat($region, $x, $y);
                $v = (int) round(.299 * (($rgb >> 16) & 255) + .587 * (($rgb >> 8) & 255) + .114 * ($rgb & 255));
                $histogram[$v]++;
            }
        }
        $sum = 0; $background = 255;
        foreach ($histogram as $level => $count) { $sum += $count; if ($sum >= $w * $h * .80) { $background = $level; break; } }
        // Faint blue/gray names may never reach a fixed black-ink threshold.
        // Use local paper contrast to locate the line; OCR confidence is unchanged.
        $inkThreshold = max(115, min(185, $background - 35));
        for ($y = 0; $y < $h; $y++) {
            $count = 0;
            for ($x = 0; $x < $w; $x++) {
                $rgb = imagecolorat($region, $x, $y);
                $luminance = .299 * (($rgb >> 16) & 255) + .587 * (($rgb >> 8) & 255) + .114 * ($rgb & 255);
                if ($luminance < $inkThreshold) $count++;
            }
            $profile[$y] = $count;
        }
        $peak = max($profile ?: [0]);
        if ($peak < 5 || $peak > $w * .90) return null;
        $threshold = max(3, $peak * .10); $runs = []; $start = null; $last = 0;
        for ($y = 0; $y < $h; $y++) {
            if ($profile[$y] >= $threshold) { $start ??= $y; $last = $y; }
            elseif ($start !== null && $y - $last > max(2, (int) round($h * .025))) { $runs[] = [$start, $last]; $start = null; }
        }
        if ($start !== null) $runs[] = [$start, $last];
        $runs = array_values(array_filter($runs, fn ($r) => $r[1] - $r[0] >= 3));
        if (!$runs) return null;
        usort($runs, fn ($a, $b) => abs(($a[0] + $a[1]) / (2 * $h) - $expectedCenter) <=> abs(($b[0] + $b[1]) / (2 * $h) - $expectedCenter));
        [$a, $b] = $runs[0]; $padding = max(3, (int) ceil(($b - $a) * .30));
        return [max(0, $a - $padding) / $h, min($h, $b + $padding + 1) / $h];
    }

    public static function fields(string $text, string $key): array
    {
        // Tesseract may insert a space before the initial's period/comma.
        $text = preg_replace('/(?<=\pL)[\h]+([.,])(?=\h|$)/u', '$1', $text);
        $text = preg_replace('/\s+([\pL]),$/u', ' $1.', trim($text));
        $fields = InstitutionalIdReader::nameFields($text, $key);
        if (isset($fields[$key])) $fields[$key] = mb_strtoupper($fields[$key]);
        return $fields;
    }

    public static function initialConfidence(array $read, string $initial): float
    {
        $letter = trim(InstitutionalIdRules::name($initial)); $matches = [];
        foreach ($read['words'] ?? [] as $word) {
            if (trim(InstitutionalIdRules::name($word['text'])) === $letter) $matches[] = (float) $word['confidence'];
        }
        // The first-name average must not hide an unreadable/missing initial.
        return $matches ? min($matches) : 0;
    }
}
