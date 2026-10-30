<?php

namespace App\Services\Vehicles;

use App\Services\LicenseVerification\LicenseDocumentReader;
use GdImage;
use Throwable;

/**
 * Reads an OR or CR photo for VehicleDocumentFields.
 *
 * OR/CR photos are often taken sideways, and the CR is a dense grid printed
 * over a security pattern: no single Tesseract pass reads every cell. So the
 * document is first turned upright (the orientation that reads the most
 * confident words), then read with several page-segmentation modes on the
 * same upright image. Their word boxes share one coordinate system, so the
 * parser can pair a value with the label above it and combine what each pass
 * read. Passes run concurrently. The original upload is not modified; only
 * temporary copies are processed.
 */
class VehicleDocumentReader
{
    /** Long edge of the upright working image, a good size for document text. */
    private const WORK_EDGE = 2400;

    /** Long edge of the quick copies used only to choose the orientation. */
    private const PROBE_EDGE = 1400;

    /**
     * Automatic layout, columns, uniform block, sparse text; each also with
     * Sauvola (local) thresholding. A crumpled or shaded OR/CR defeats the
     * default global threshold: whole cells (series, make, validity) vanish.
     */
    private const READS = ['auto' => [3], 'columns' => [4], 'block' => [6], 'sparse' => [11],
        'auto_local' => [3, 2], 'columns_local' => [4, 2], 'sparse_local' => [11, 2]];

    public function __construct(private readonly LicenseDocumentReader $reader, private readonly VehicleDocumentFields $fields) {}

    /** @return array LicenseDocumentReader result plus "reads" (each with source, text, words) and "rotation". */
    public function read(string $path, string $mime): array
    {
        $first = $this->reader->read($path, $mime);
        $single = $first + ['reads' => [$first + ['source' => 'auto']], 'rotation' => 0];
        // Unreadable file, OCR unavailable, or a reader without word geometry: one read only.
        if (empty($first['engine_available']) || !isset($first['words'][0]['box']) || !in_array($mime, ['image/jpeg', 'image/png'], true)) {
            return $single;
        }

        $image = @imagecreatefromstring((string) file_get_contents($path));
        if (!$image) return $single;
        $temps = [];
        try {
            if (!imageistruecolor($image)) imagepalettetotruecolor($image);
            $image = $this->scale($this->exif($image, $path, $mime), self::WORK_EDGE);
            imagefilter($image, IMG_FILTER_GRAYSCALE);

            $turn = $this->orientation($image, $temps);
            if ($turn) {
                $rotated = imagerotate($image, 360 - $turn, 0xFFFFFF);
                if ($rotated) { imagedestroy($image); $image = $rotated; }
            }
            $upright = $temps[] = $this->temp($image);
            $results = $upright ? $this->reader->readTextImages(array_map(fn ($mode) => [$upright, ...$mode], self::READS), 30) : [];
            $reads = [];
            foreach ($results as $source => $read) {
                if (!empty($read['engine_available']) && !empty($read['words'])) $reads[] = $read + ['source' => $source];
            }
            // Targeted retry: a field whose label was found but whose value was
            // not read is read again from its own cell, enlarged, as one line.
            array_push($reads, ...$this->cellReads($image, $reads, $temps));
        } catch (Throwable $e) {
            report($e);
            return $single;
        } finally {
            imagedestroy($image);
            foreach ($temps as $temp) if (is_string($temp)) @unlink($temp);
        }
        if (!$reads) return $single;

        return array_merge($first, [
            'text' => $reads[0]['text'], 'words' => $reads[0]['words'],
            // Readability is the best pass, not one pass dragged down by the background pattern.
            'confidence' => max(array_map(fn ($r) => (float) ($r['confidence'] ?? 0), array_filter($reads, fn ($r) => !isset($r['field'])))),
            'rotation' => $turn, 'reads' => $reads,
        ]);
    }

    /** Clockwise turn (0, 90, 180 or 270) whose quick read has the most confident words. */
    private function orientation(GdImage $image, array &$temps): int
    {
        $ratio = min(1, self::PROBE_EDGE / max(imagesx($image), imagesy($image)));
        $probe = imagescale($image, max(1, (int) round(imagesx($image) * $ratio)), -1, IMG_BILINEAR_FIXED);
        if (!$probe) return 0;
        $jobs = [];
        foreach ([0, 90, 180, 270] as $turn) {
            $candidate = $turn ? imagerotate($probe, 360 - $turn, 0xFFFFFF) : $probe;
            if (!$candidate) continue;
            if ($path = $temps[] = $this->temp($candidate)) $jobs[$turn] = [$path, 3];
            if ($candidate !== $probe) imagedestroy($candidate);
        }
        imagedestroy($probe);
        $scores = array_map(fn ($read) => self::confidentWords($read), $this->reader->readTextImages($jobs, 20));
        if (!$scores) return 0;
        arsort($scores);
        $turn = (int) array_key_first($scores);
        // Keep the photo as taken unless another orientation is clearly better.
        return $turn !== 0 && $scores[$turn] > ($scores[0] ?? 0) * 1.2 ? $turn : 0;
    }

    /** @return array<int, array> reads tagged with the one field they belong to */
    private function cellReads(GdImage $image, array $reads, array &$temps): array
    {
        $jobs = [];
        foreach ($this->fields->fieldCells(['reads' => $reads]) as $field => [$x, $y, $w, $h]) {
            $w = min($w, imagesx($image) - $x); $h = min($h, imagesy($image) - $y);
            if ($w < 10 || $h < 10) continue;
            $cell = imagecrop($image, ['x' => $x, 'y' => $y, 'width' => $w, 'height' => $h]);
            if (!$cell) continue;
            // Small print reads better enlarged; cap the size of the crop.
            $scale = min(3, 1200 / max(1, $w));
            $big = $scale > 1.05 ? imagescale($cell, (int) round($w * $scale), -1, IMG_BICUBIC) : $cell;
            if ($path = $temps[] = $this->temp($big ?: $cell)) {
                $jobs["{$field}|7"] = [$path, 7];
                $jobs["{$field}|6"] = [$path, 6];
                $jobs["{$field}|7l"] = [$path, 7, 2];
            }
            // A contrast-stretched copy: the security pattern fades, the print stays dark.
            $strong = $big ?: $cell;
            imagefilter($strong, IMG_FILTER_CONTRAST, -45);
            if ($path = $temps[] = $this->temp($strong)) {
                $jobs["{$field}|7c"] = [$path, 7];
                $jobs["{$field}|6c"] = [$path, 6];
            }
            if ($big && $big !== $cell) imagedestroy($big);
            imagedestroy($cell);
        }
        $cellReads = [];
        foreach ($jobs ? $this->reader->readTextImages($jobs, 15) : [] as $key => $read) {
            [$field, $mode] = explode('|', $key);
            $mode = rtrim($mode, 'cl') . (str_ends_with($mode, 'c') ? '_contrast' : (str_ends_with($mode, 'l') ? '_local' : ''));
            if (!empty($read['engine_available']) && trim((string) ($read['text'] ?? '')) !== '') {
                $cellReads[] = ['text' => $read['text'], 'confidence' => $read['confidence'], 'field' => $field, 'source' => "cell_{$field}_psm{$mode}"];
            }
        }
        return $cellReads;
    }

    private function temp(GdImage $image): ?string
    {
        $temp = tempnam(sys_get_temp_dir(), 'pass_orcr_');
        if ($temp === false) return null;
        if (imagepng($image, $temp)) return $temp;
        @unlink($temp);
        return null;
    }

    /**
     * Confident words laid out horizontally. Tesseract can read sideways text
     * by itself, but its word boxes are then vertical, which defeats pairing
     * values with labels by position; so only level words count.
     */
    private static function confidentWords(array $read): int
    {
        return count(array_filter($read['words'] ?? [], fn ($w) => $w['confidence'] >= 80 && preg_match('/[A-Za-z]{3}/', $w['text'])
            && (!isset($w['box']) || $w['box'][2] > $w['box'][3])));
    }

    /** Fit the long edge to $edge; small photos are enlarged to 1800 px, which suits Tesseract. */
    private function scale(GdImage $image, int $edge): GdImage
    {
        $long = max(imagesx($image), imagesy($image));
        $target = $long > $edge ? $edge : max($long, 1800);
        if ($target === $long) return $image;
        $scaled = imagescale($image, max(1, (int) round(imagesx($image) * $target / $long)), -1, IMG_BICUBIC);
        if (!$scaled) return $image;
        imagedestroy($image);
        return $scaled;
    }

    private function exif(GdImage $image, string $path, string $mime): GdImage
    {
        if ($mime !== 'image/jpeg' || !function_exists('exif_read_data')) return $image;
        $angle = match ((int) (@exif_read_data($path)['Orientation'] ?? 1)) { 3 => 180, 6 => -90, 8 => 90, default => 0 };
        if (!$angle) return $image;
        $rotated = imagerotate($image, $angle, 0);
        if (!$rotated) return $image;
        imagedestroy($image);
        return $rotated;
    }
}
