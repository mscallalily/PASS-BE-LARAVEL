<?php

namespace App\Services\Vehicles;

use App\Services\LicenseVerification\LicenseDocumentReader;

/** Plate suggestions only; no vehicle authorization or ownership decision. */
class VehiclePlateReader
{
    public function __construct(private LicenseDocumentReader $reader) {}

    public static function candidates(string $text): array
    {
        // Common printed formats only. Unrecognized/special plates stay editable.
        preg_match_all('/(?<![A-Z0-9])(?:[A-Z]{3}[ -]*[0-9]{3,4}|[A-Z]{2}[ -]*[0-9]{3,5}|[0-9]{3}[ -]*[A-Z]{2,3})(?![A-Z0-9])/i', $text, $m);

        return array_values(array_unique(array_map(fn ($v) => strtoupper(preg_replace('/[ -]+/', '', $v)), $m[0])));
    }

    public function read(string $path, string $mime): array
    {
        $full = $this->reader->read($path, $mime, 11, 20);
        $reads = [$full];
        $image = @imagecreatefromstring(file_get_contents($path));
        if ($image) {
            if (imagesx($image) > 2000) {
                $scaledInput = imagescale($image, 2000, -1, IMG_BILINEAR_FIXED);
                imagedestroy($image);
                $image = $scaledInput;
            }
            try {
                foreach ([[0, 0, 1, 1], [0, .4, 1, 1]] as [$l,$t,$r,$b]) {
                    $crop = imagecrop($image, ['x' => 0, 'y' => (int) ($t * imagesy($image)), 'width' => imagesx($image), 'height' => max(1, (int) (($b - $t) * imagesy($image)))]);
                    if (! $crop) {
                        continue;
                    }
                    $scaled = imagescale($crop, 1800, -1, IMG_BILINEAR_FIXED);
                    imagedestroy($crop);
                    $temp = tempnam(sys_get_temp_dir(), 'pass_plate_');
                    try {
                        imagefilter($scaled, IMG_FILTER_GRAYSCALE);
                        imagepng($scaled, $temp);
                        $reads[] = $this->reader->readTextImage($temp, 11);
                    } finally {
                        imagedestroy($scaled);
                        @unlink($temp);
                    }
                }
            } finally {
                imagedestroy($image);
            }
        }
        $found = [];
        $available = false;
        foreach ($reads as $read) {
            $available = $available || ($read['engine_available'] ?? false);
            foreach (self::candidates($read['text'] ?? '') as $candidate) {
                $scores = [];
                foreach ($read['words'] ?? [] as $word) {
                    if (in_array($candidate, self::candidates($word['text']), true)) {
                        $scores[] = (float) $word['confidence'];
                    }
                }
                // Never use a high full-photo mean to certify a plate token.
                if (! $scores) {
                    $words = $read['words'] ?? [];
                    for ($i = 0; $i < count($words); $i++) {
                        $joined = '';
                        $parts = [];
                        for ($j = $i; $j < min(count($words), $i + 3); $j++) {
                            $token = strtoupper(preg_replace('/[^A-Z0-9]/i', '', $words[$j]['text']));
                            if ($token === '') {
                                continue;
                            }
                            $joined .= $token;
                            $parts[] = (float) $words[$j]['confidence'];
                            if ($joined === $candidate) {
                                $scores[] = min($parts);
                                break;
                            }
                            if (! str_starts_with($candidate, $joined)) {
                                break;
                            }
                        }
                    }
                }
                $score = $scores ? min($scores) : 0;
                $found[$candidate] = max($found[$candidate] ?? 0, $score);
            }
        }
        arsort($found);
        $candidates = array_map(fn ($plate, $confidence) => ['plate' => $plate, 'confidence' => round($confidence, 1)], array_keys($found), array_values($found));
        $strong = array_values(array_filter($candidates, fn ($c) => $c['confidence'] >= 70));

        return ['status' => ! $available ? 'unavailable' : (count($strong) === 1 && count($candidates) === 1 ? 'suggested' : 'review_needed'),
            'plate_number' => count($strong) === 1 && count($candidates) === 1 ? $strong[0]['plate'] : null,
            'candidates' => array_slice($candidates, 0, 8),
            'message' => ! $available ? 'Plate reader is unavailable. Enter the plate manually.' : ($candidates ? 'Compare the suggested plate with the photo before applying it.' : 'The plate could not be read. Use a clear, closer photo or enter it manually.')];
    }
}
