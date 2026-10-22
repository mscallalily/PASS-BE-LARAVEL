<?php

namespace App\Services\Registration;

use App\Services\LicenseVerification\LicenseDocumentReader;
use Throwable;

/** Additional school-logo regions for portrait cards photographed in a holder. */
final class InstitutionalSchoolBranding
{
    public function __construct(private readonly LicenseDocumentReader $reader) {}

    public function confirm(string $path, string $category, ?float $deadline = null): bool
    {
        if (! in_array($category, InstitutionalIdRules::CATEGORIES, true)) return false;
        $deadline = min($deadline ?? INF, microtime(true) + 8);
        if (microtime(true) >= $deadline) return false;
        $size = @getimagesize($path);
        if (! $size || $size[0] < 1 || $size[1] < 1 || $size[0] > 12000 || $size[1] > 12000 || $size[0] * $size[1] > 20000000) return false;
        $image = @imagecreatefromstring((string) file_get_contents($path));
        if (! $image) return false;
        $temp = null;
        try {
            $orientation = function_exists('exif_read_data') ? (@exif_read_data($path)['Orientation'] ?? 1) : 1;
            if (in_array((int) $orientation, [2, 4, 5, 7], true)) imageflip($image, (int) $orientation === 4 ? IMG_FLIP_VERTICAL : IMG_FLIP_HORIZONTAL);
            $angle = match ((int) $orientation) { 3 => 180, 5, 6 => -90, 7, 8 => 90, default => 0 };
            if ($angle) {
                $rotated = imagerotate($image, $angle, 0);
                imagedestroy($image); $image = $rotated;
            }
            // Card-only, employee footer, and framed-card header. Do not use a
            // registrant's typed school/name/ID to manufacture school evidence.
            $boxes = $category === 'employee'
                ? [[.38, 0, .995, .23], [.48, .94, .995, 1], [.20, .25, .80, .40]]
                : [[.02, 0, .995, .25], [.20, .25, .80, .40], [.08, .14, .92, .42]];
            $temp = tempnam(sys_get_temp_dir(), 'pass_school_');
            if ($temp === false) return false;
            foreach ($boxes as [$l, $t, $r, $b]) {
                if (microtime(true) >= $deadline) break;
                $x = (int) ($l * imagesx($image)); $y = (int) ($t * imagesy($image));
                $crop = imagecrop($image, ['x' => $x, 'y' => $y,
                    'width' => max(1, (int) ($r * imagesx($image)) - $x),
                    'height' => max(1, (int) ($b * imagesy($image)) - $y)]);
                if (! $crop) continue;
                try {
                    foreach (['color', 'gray'] as $variant) {
                        if (microtime(true) >= $deadline) break;
                        // Grayscale before resizing preserves thin branding strokes.
                        if ($variant === 'gray') imagefilter($crop, IMG_FILTER_GRAYSCALE);
                        $scaled = imagescale($crop, $variant === 'gray' ? 900 : 1600, -1,
                            $variant === 'gray' ? IMG_BICUBIC_FIXED : IMG_BILINEAR_FIXED);
                        if (! $scaled) continue;
                        try {
                            if (! imagepng($scaled, $temp)) continue;
                            $read = $this->reader->readTextImage($temp, 6, max(1, min(2, (int) ceil($deadline - microtime(true)))));
                            if (InstitutionalIdSignals::school($read['text'] ?? '')) return true;
                            if (isset($read['engine_available']) && ! $read['engine_available']) return false;
                        } finally { imagedestroy($scaled); }
                    }
                } finally { imagedestroy($crop); }
            }
        } catch (Throwable) {
            // Failure to read is uncertainty; it must never become approval.
            return false;
        } finally {
            imagedestroy($image);
            if (is_string($temp)) @unlink($temp);
        }

        return false;
    }
}
