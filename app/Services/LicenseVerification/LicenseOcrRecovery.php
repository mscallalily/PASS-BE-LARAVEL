<?php

namespace App\Services\LicenseVerification;

use GdImage;
use Throwable;

/** Bounded LTO-card regions; never guesses text or fills fields from user entries. */
final class LicenseOcrRecovery
{
    public function __construct(private readonly LicenseDocumentReader $reader) {}

    public function read(string $path, string $mime): array
    {
        $full = $this->reader->read($path, $mime);
        if (! in_array($mime, ['image/jpeg', 'image/png'], true) || empty($full['engine_available'])
            || empty($full['words'][0]['line']) || ($full['width'] ?? 0) < 500 || ($full['height'] ?? 0) < 300) return $full;
        $ratio = $full['width'] / $full['height'];
        if ($ratio < 1.25 || $ratio > 2.4) return $full;
        $deadline = microtime(true) + 12;
        $reads = [$full + ['source' => 'full_gray']];
        $image = @imagecreatefromstring((string) file_get_contents($path));
        if (! $image) return $full;
        try {
            $orientation = $mime === 'image/jpeg' && function_exists('exif_read_data') ? (@exif_read_data($path)['Orientation'] ?? 1) : 1;
            $angle = match ((int) $orientation) { 3 => 180, 6 => -90, 8 => 90, default => 0 };
            if ($angle) {
                $rotated = imagerotate($image, $angle, 0);
                if (! $rotated) return $full;
                imagedestroy($image); $image = $rotated;
            }
            $header = $this->region($image, [.21, .075, .78, .28], 'header_color', 1400, 6, false, $deadline);
            if ($header && self::headings($header['text']) < 3 && microtime(true) < $deadline) {
                $gray = $this->region($image, [.21, .075, .78, .28], 'header_gray', 1400, 6, true, $deadline);
                if ($gray && self::headings($gray['text']) > self::headings($header['text'])) $header = $gray;
            }
            // Keep small-card lettering at a useful reading size. Upscaling
            // cannot recover missing detail; normal confidence checks still apply.
            $bodyWidth = imagesx($image) < 1400 ? 1100 : null;
            if ((!$header || self::headings($header['text']) < 3) && microtime(true) < $deadline) {
                $wide = $this->region($image, [.24, .02, .84, .24], 'header_wide', 1400, 6, false, $deadline);
                if ($wide && (!$header || self::headings($wide['text']) > self::headings($header['text']))) $header = $wide;
            }
            $body = $this->region($image, [.33, .27, .98, .80], 'body_gray', $bodyWidth, 6, true, $deadline);
            if ($header) $reads[] = $header;
            if ($body) $reads[] = $body;
            $strongNumber = $body && collect(LicenseFieldConfidence::numberReadings($body))->contains(fn ($r) => $r['confidence'] >= 80);
            if (! $strongNumber && microtime(true) < $deadline) {
                $number = $this->region($image, [.34, .645, .59, .710], 'number_color', 1000, 7, false, $deadline);
                if ($number) $reads[] = $number;
            }
            if (!$strongNumber && microtime(true) < $deadline) {
                $numberGray = $this->region($image, [.36, .665, .595, .72], 'number_gray', 1000, 7, true, $deadline);
                if ($numberGray) $reads[] = $numberGray;
            }
            if ($body && (LicenseFieldConfidence::nameRegionConfidence($body) ?? $body['confidence'] ?? 0) < 80 && microtime(true) < $deadline) {
                $name = $this->region($image, [.35, .265, .99, .38], 'name_color', 1500, 6, false, $deadline);
                if ($name) $reads[] = $name;
            }
            // Use a coherent header + data-region reading, rather than noisy
            // symbols over the photo/signatures. All reads remain available for
            // identity confidence and strong-number conflict checks.
            if ($header && $body && self::headings($header['text']) >= 3) {
                $full['text'] = trim($header['text']."\n".$body['text']);
                $words = array_merge($header['words'], $body['words']);
                $full['confidence'] = $words ? round(array_sum(array_column($words, 'confidence')) / count($words), 1) : null;
                $full['words'] = $words;
            }
        } catch (Throwable) {
            // Keep the original evidence if a fallback cannot run.
        } finally { imagedestroy($image); }
        $full['field_reads'] = $reads;
        return $full;
    }

    private function region(GdImage $image, array $box, string $source, ?int $width, int $mode, bool $gray, float $deadline): ?array
    {
        if (microtime(true) >= $deadline) return null;
        [$l, $t, $r, $b] = $box;
        $x = (int) ($l * imagesx($image)); $y = (int) ($t * imagesy($image));
        $crop = imagecrop($image, ['x' => $x, 'y' => $y, 'width' => max(1, (int) ($r * imagesx($image)) - $x), 'height' => max(1, (int) ($b * imagesy($image)) - $y)]);
        if (! $crop) return null;
        $temp = null;
        try {
            if ($gray) imagefilter($crop, IMG_FILTER_GRAYSCALE);
            if ($width) {
                $scaled = imagescale($crop, $width, -1, IMG_BICUBIC);
                if (! $scaled) return null;
                imagedestroy($crop); $crop = $scaled;
            }
            $temp = tempnam(sys_get_temp_dir(), 'pass_license_');
            if ($temp === false || ! imagepng($crop, $temp)) return null;
            $read = $this->reader->readTextImage($temp, $mode, max(1, min(3, (int) ceil($deadline - microtime(true)))));
            if (empty($read['engine_available']) || empty($read['words'])) return null;
            $read['source'] = $source;
            return $read;
        } finally { imagedestroy($crop); if (is_string($temp)) @unlink($temp); }
    }

    private static function headings(string $text): int
    {
        $text = strtr(strtoupper($text), ['’' => "'", '‘' => "'"]);
        return (int) str_contains($text, 'LAND TRANSPORTATION')
            + (int) (bool) preg_match("/DRIVER'?S?\\s+LICENSE|STUDENT\\s+PERMIT/", $text)
            + (int) str_contains($text, 'DEPARTMENT OF TRANSPORTATION');
    }
}
