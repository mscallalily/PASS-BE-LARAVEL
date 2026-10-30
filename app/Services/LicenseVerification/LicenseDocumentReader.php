<?php

namespace App\Services\LicenseVerification;

use Illuminate\Support\Facades\Process;
use Throwable;

/**
 * Reads an uploaded driver's license file: measures basic image quality and
 * extracts text with OCR. It only reports what it sees; LicenseValidationRules
 * decides what the result means.
 */
class LicenseDocumentReader
{
    /** Images wider than this are downscaled first to bound memory and OCR time. */
    private const MAX_WORK_WIDTH = 2400;

    /** Small images are upscaled to roughly this width, which suits Tesseract. */
    private const OCR_MIN_WIDTH = 1800;

    /** An upright license front yields dozens of confident words; upside-down text almost none. */
    private const UPRIGHT_WORDS = 12;

    /**
     * @return array{
     *     kind: string,
     *     engine_available: bool,
     *     engine_error: ?string,
     *     text: string,
     *     confidence: ?float,
     *     width: ?int,
     *     height: ?int,
     *     sharpness: ?float
     * }
     */
    /**
     * @param  bool  $orient  Also correct a photo taken sideways or upside down
     *                        (no EXIF flag): the result's "rotation" is the
     *                        clockwise turn applied, and width/height follow it.
     */
    public function read(string $path, string $mime, int $pageMode = 3, ?int $timeout = null, bool $orient = false): array
    {
        if ($mime === 'application/pdf') {
            return $this->readPdf($path);
        }

        return $this->readImage($path, $pageMode, $timeout, $orient);
    }

    /** Read an already prepared OCR region without re-measuring/downscaling it. */
    public function readTextImage(string $path, int $pageMode = 6, ?int $timeout = null): array
    {
        return $this->tesseract($path, $pageMode, $timeout ?? 20);
    }

    /**
     * Read several prepared images at once (independent Tesseract processes).
     *
     * @param  array<string, array{0: string, 1: int, 2?: int}>  $jobs  key => [image path, page mode, optional thresholding method]
     * @return array<string, array> key => result of readTextImage()
     */
    public function readTextImages(array $jobs, int $timeout = 20): array
    {
        $binary = config('services.license_ocr.tesseract_path', 'tesseract');
        try {
            $results = Process::pool(function ($pool) use ($jobs, $binary, $timeout) {
                foreach ($jobs as $key => $job) {
                    [$path, $mode] = $job;
                    // 2 = Sauvola (local) thresholding: keeps print legible on shaded or unevenly lit paper.
                    $threshold = isset($job[2]) ? ['-c', 'thresholding_method='.(int) $job[2]] : [];
                    $pool->as((string) $key)->timeout($timeout)->command([$binary, $path, 'stdout', '-l', 'eng', '--psm', (string) $mode, ...$threshold, 'tsv']);
                }
            })->start()->wait();
        } catch (Throwable) {
            return array_map(fn () => ['engine_available' => false, 'engine_error' => 'The OCR engine is not available on the server.'], $jobs);
        }
        $reads = [];
        foreach ($jobs as $key => $job) {
            $process = $results[(string) $key] ?? null;
            $reads[$key] = $process && $process->successful() ? $this->parseTsv($process->output())
                : ['engine_available' => false, 'engine_error' => 'The OCR engine could not process the image.'];
        }
        return $reads;
    }

    private function readImage(string $path, int $pageMode, ?int $timeout, bool $orient = false): array
    {
        $result = [
            'kind' => 'image',
            'engine_available' => false,
            'engine_error' => null,
            'text' => '',
            'confidence' => null,
            'width' => null,
            'height' => null,
            'sharpness' => null,
        ];

        $size = @getimagesize($path);
        if (!$size || $size[0] < 1 || $size[1] < 1 || $size[0] > 12000 || $size[1] > 12000 || $size[0] * $size[1] > 20000000) {
            $result['engine_error'] = 'Use a readable image no larger than 20 megapixels and 12000 pixels on either side.';
            return $result;
        }
        $image = @imagecreatefromstring((string) file_get_contents($path));

        if ($image === false) {
            $result['engine_error'] = 'The image file could not be decoded.';
            return $result;
        }

        $image = $size[2] === IMAGETYPE_JPEG ? $this->applyExifOrientation($image, $path) : $image;
        $result['width'] = imagesx($image);
        $result['height'] = imagesy($image);

        $scale = min(1, self::MAX_WORK_WIDTH / imagesx($image), 3200 / imagesy($image));
        if ($scale < 1) $image = $this->scaleToWidth($image, max(1, (int) floor(imagesx($image) * $scale)));

        imagefilter($image, IMG_FILTER_GRAYSCALE);
        $result['sharpness'] = $this->sharpness($image);

        // Straighten a tilted card and crop away the table/background around it.
        if ($orient && ($geometry = self::cardGeometry($image))) {
            $image = self::applyGeometry($image, $geometry);
            $result['geometry'] = $geometry;
            $result['width'] = (int) round(imagesx($image) / $scale);
            $result['height'] = (int) round(imagesy($image) / $scale);
        }

        if (imagesx($image) < self::OCR_MIN_WIDTH && imagesy($image) * self::OCR_MIN_WIDTH / imagesx($image) <= 3200) {
            $image = $this->scaleToWidth($image, self::OCR_MIN_WIDTH);
        }

        $tempFile = tempnam(sys_get_temp_dir(), 'lic');

        try {
            imagepng($image, $tempFile);
            $read = $this->tesseract($tempFile, $pageMode, $timeout);

            // Phone photos of a card are often sideways or upside down with no
            // EXIF flag. Tesseract's orientation detection proved unreliable on
            // license cards (hologram, photo, seal), so the turn is chosen by
            // evidence: the orientation that reads the most confident words.
            // A landscape image that already reads well is not re-read.
            $landscape = imagesx($image) >= imagesy($image);
            $turns = !$orient || empty($read['engine_available']) || ($landscape && self::confidentWords($read) >= self::UPRIGHT_WORDS)
                ? [] : ($landscape ? [180] : [90, 270, 180]);
            foreach ($turns as $turn) {
                $turned = imagerotate($image, 360 - $turn, 0);
                if (!$turned) continue;
                imagepng($turned, $tempFile);
                imagedestroy($turned);
                $candidate = $this->tesseract($tempFile, $pageMode, $timeout);
                if (!empty($candidate['engine_available']) && self::confidentWords($candidate) > self::confidentWords($read)) {
                    $read = $candidate;
                    $result['rotation'] = $turn;
                    if ($turn !== 180 && self::confidentWords($read) >= self::UPRIGHT_WORDS) break;
                }
            }
            if (($result['rotation'] ?? 0) % 180) [$result['width'], $result['height']] = [$result['height'], $result['width']];
            imagedestroy($image);

            return array_merge($result, $read);
        } finally {
            @unlink($tempFile);
        }
    }

    /**
     * Skew (degrees, GD's counter-clockwise sense) and card box (fractions of
     * the straightened image) for a photo of a card on a background, or null
     * when the photo is already straight and filled by the card.
     *
     * @return ?array{skew: float, crop: array{0: float, 1: float, 2: float, 3: float}}
     */
    public static function cardGeometry(\GdImage $gray): ?array
    {
        $small = imagescale($gray, 320, -1, IMG_BILINEAR_FIXED);
        if (!$small) return null;
        [$width, $height] = [imagesx($small), imagesy($small)];
        $lum = [];
        for ($y = 0; $y < $height; $y++) for ($x = 0; $x < $width; $x++) $lum[] = imagecolorat($small, $x, $y) & 0xFF;
        sort($lum);
        $dark = $lum[(int) (count($lum) * .1)];
        $light = $lum[(int) (count($lum) * .9)];
        if ($light - $dark < 40) { imagedestroy($small); return null; }
        $threshold = ($dark + $light) / 2;

        // Background = edge rows/columns that are almost uniformly dark (a
        // table), not a printed band with light lettering in it.
        $cardBox = function (\GdImage $img) use ($threshold): array {
            [$w, $h] = [imagesx($img), imagesy($img)];
            $rows = array_fill(0, $h, 0); $cols = array_fill(0, $w, 0);
            for ($y = 0; $y < $h; $y++) for ($x = 0; $x < $w; $x++) {
                if ((imagecolorat($img, $x, $y) & 0xFF) < $threshold) continue;
                $rows[$y]++; $cols[$x]++;
            }
            $span = function (array $counts, int $length): array {
                $inside = array_keys(array_filter($counts, fn ($n) => $n >= $length * .05));
                return $inside ? [min($inside), max($inside) + 1] : [0, count($counts)];
            };
            [$top, $bottom] = $span($rows, $w);
            [$left, $right] = $span($cols, $h);
            return [$left, $top, $right, $bottom];
        };

        // Text lines (horizontal, or vertical on a sideways card) give the
        // sharpest dark-pixel projection profile when level. Only the middle
        // of the card is scored so background corners do not dominate.
        [$l, $t, $r, $b] = $cardBox($small);
        // A card that already fills the photo is left as it is.
        if (($r - $l) * ($b - $t) > .85 * $width * $height) { imagedestroy($small); return null; }
        $inner =[$l + ($r - $l) * .15, $t + ($b - $t) * .15, $l + ($r - $l) * .85, $t + ($b - $t) * .85];
        $score = function (float $angle) use ($small, $threshold, $inner, $width, $height): float {
            $img = $angle == 0.0 ? $small : imagerotate($small, $angle, 0xFFFFFF);
            [$dx, $dy] = [(imagesx($img) - $width) / 2, (imagesy($img) - $height) / 2];
            $rows = []; $cols = [];
            for ($y = (int) ($inner[1] + $dy); $y < (int) ($inner[3] + $dy); $y++) for ($x = (int) ($inner[0] + $dx); $x < (int) ($inner[2] + $dx); $x++) {
                $dark = (imagecolorat($img, $x, $y) & 0xFF) < $threshold;
                $rows[$y] = ($rows[$y] ?? 0) + $dark; $cols[$x] = ($cols[$x] ?? 0) + $dark;
            }
            if ($img !== $small) imagedestroy($img);
            $variance = fn (array $v) => count($v) ? array_sum(array_map(fn ($n) => $n * $n, $v)) / count($v) - (array_sum($v) / count($v)) ** 2 : 0;
            return max($variance($rows), $variance($cols));
        };
        $best = 0.0; $bestScore = $score(0.0);
        foreach ([-8, -6, -4, -3, -2, -1, 1, 2, 3, 4, 6, 8] as $angle) {
            if (($s = $score((float) $angle)) > $bestScore * 1.05) [$best, $bestScore] = [(float) $angle, $s];
        }
        foreach ([$best - .5, $best + .5] as $angle) {
            if ($angle != 0.0 && ($s = $score($angle)) > $bestScore) [$best, $bestScore] = [$angle, $s];
        }
        $skew = abs($best) >= 1 ? $best : 0.0;

        // Fill the straightened corners with the background tone, not white.
        $img = $skew ? imagerotate($small, $skew, ($dark << 16) | ($dark << 8) | $dark) : $small;
        [$w, $h] = [imagesx($img), imagesy($img)];
        [$left, $top, $right, $bottom] = $cardBox($img);
        if ($img !== $small) imagedestroy($img);
        imagedestroy($small);
        $box = [$left / $w, $top / $h, ($right - $left) / $w, ($bottom - $top) / $h];
        $area = $box[2] * $box[3];
        $aspect = ($box[2] * $w) / max(1, $box[3] * $h);
        // Only a plausible card (ID-1 is about 1.59:1, either way up) is cropped.
        $crop = $area < .85 && $area > .2 && (($aspect > 1.3 && $aspect < 1.95) || ($aspect > .51 && $aspect < .77));
        if ($crop) {
            $pad = .015;
            $box = [max(0, $box[0] - $pad), max(0, $box[1] - $pad), min(1 - max(0, $box[0] - $pad), $box[2] + 2 * $pad), min(1 - max(0, $box[1] - $pad), $box[3] + 2 * $pad)];
        }

        return $skew || $crop ? ['skew' => $skew, 'crop' => $crop ? array_map(fn ($v) => round($v, 4), $box) : [0, 0, 1, 1]] : null;
    }

    /** Apply cardGeometry() to an image of the same photo at any scale. */
    public static function applyGeometry(\GdImage $image, array $geometry): \GdImage
    {
        if ($geometry['skew']) {
            $rotated = imagerotate($image, $geometry['skew'], 0xFFFFFF);
            if ($rotated) { imagedestroy($image); $image = $rotated; }
        }
        [$fx, $fy, $fw, $fh] = $geometry['crop'];
        if ($fw < 1 || $fh < 1) {
            $cropped = imagecrop($image, ['x' => (int) round($fx * imagesx($image)), 'y' => (int) round($fy * imagesy($image)),
                'width' => max(1, (int) round($fw * imagesx($image))), 'height' => max(1, (int) round($fh * imagesy($image)))]);
            if ($cropped) { imagedestroy($image); $image = $cropped; }
        }
        return $image;
    }

    /** Words recognized with high confidence: a document-neutral measure of readability. */
    private static function confidentWords(array $read): int
    {
        return count(array_filter($read['words'] ?? [], fn ($w) => $w['confidence'] >= 80 && preg_match('/[A-Za-z0-9]{2}/', $w['text'])));
    }

    /**
     * Text-based PDFs are read from their text layer. Scanned PDFs have no
     * text layer and cannot be rasterized here, so they need staff review.
     */
    private function readPdf(string $path): array
    {
        $result = [
            'kind' => 'pdf',
            'engine_available' => false,
            'engine_error' => null,
            'text' => '',
            'confidence' => null,
            'width' => null,
            'height' => null,
            'sharpness' => null,
        ];

        $binary = config('services.license_ocr.pdftotext_path');

        if (!$binary) {
            $result['engine_error'] = 'PDF text extraction is not configured on the server.';
            return $result;
        }

        try {
            $process = Process::timeout(config('services.license_ocr.timeout', 60))
                ->run([$binary, '-layout', $path, '-']);
        } catch (Throwable $e) {
            $result['engine_error'] = 'PDF text extraction could not run.';
            return $result;
        }

        if (!$process->successful()) {
            $result['engine_error'] = 'PDF text extraction failed.';
            return $result;
        }

        $result['text'] = trim($process->output());
        $result['engine_available'] = $result['text'] !== '';

        if (!$result['engine_available']) {
            $result['engine_error'] = 'The PDF has no text layer (it is likely a scanned image).';
        }

        return $result;
    }

    /**
     * Run Tesseract and return the recognized text with its mean word confidence.
     */
    private function tesseract(string $imagePath, int $pageMode = 3, ?int $timeout = null): array
    {
        $binary = config('services.license_ocr.tesseract_path', 'tesseract');

        try {
            $process = Process::timeout($timeout ?? config('services.license_ocr.timeout', 60))
                ->run([$binary, $imagePath, 'stdout', '-l', 'eng', '--psm', (string) $pageMode, 'tsv']);
        } catch (Throwable $e) {
            return ['engine_available' => false, 'engine_error' => 'The OCR engine is not available on the server.'];
        }

        if (!$process->successful()) {
            return ['engine_available' => false, 'engine_error' => 'The OCR engine could not process the image.'];
        }

        return $this->parseTsv($process->output());
    }

    private function parseTsv(string $output): array
    {
        // TSV columns: level page block par line word left top width height conf text
        $lines = [];
        $confidences = [];
        $words = [];

        foreach (preg_split('/\R/', $output) as $index => $row) {
            $cols = explode("\t", $row);

            if ($index === 0 || count($cols) < 12 || $cols[0] !== '5') {
                continue;
            }

            $word = trim($cols[11]);
            $conf = (float) $cols[10];

            if ($word === '' || $conf < 0) {
                continue;
            }

            $lines["{$cols[2]}-{$cols[3]}-{$cols[4]}"][] = $word;
            $confidences[] = $conf;
            // box: left, top, width, height in the read image's pixels (lets grid
            // forms such as the CR pair a value with the label above it).
            $words[] = ['text' => $word, 'confidence' => $conf, 'line' => "{$cols[2]}-{$cols[3]}-{$cols[4]}",
                'box' => [(int) $cols[6], (int) $cols[7], (int) $cols[8], (int) $cols[9]]];
        }

        return [
            'engine_available' => true,
            'engine_error' => null,
            'text' => implode("\n", array_map(fn ($words) => implode(' ', $words), $lines)),
            'words' => $words,
            'confidence' => $confidences ? round(array_sum($confidences) / count($confidences), 1) : null,
        ];
    }

    /**
     * Variance of the Laplacian on a downscaled grayscale copy. Low values mean
     * few sharp edges, i.e. a blurry or out-of-focus photo.
     */
    private function sharpness(\GdImage $image): float
    {
        $sample = $this->scaleToWidth($image, 800, keepOriginal: true);
        $width = imagesx($sample);
        $height = imagesy($sample);

        $row = function (int $y) use ($sample, $width): array {
            $values = [];
            for ($x = 0; $x < $width; $x++) {
                $values[] = imagecolorat($sample, $x, $y) & 0xFF;
            }
            return $values;
        };

        $sum = 0.0;
        $sumSquares = 0.0;
        $count = 0;

        // Keep only three rows in memory at a time.
        if ($height >= 3) {
            [$above, $current] = [$row(0), $row(1)];

            for ($y = 1; $y < $height - 1; $y++) {
                $below = $row($y + 1);

                for ($x = 1; $x < $width - 1; $x++) {
                    $value = 4 * $current[$x] - $above[$x] - $below[$x] - $current[$x - 1] - $current[$x + 1];
                    $sum += $value;
                    $sumSquares += $value * $value;
                    $count++;
                }

                [$above, $current] = [$current, $below];
            }
        }

        imagedestroy($sample);

        if ($count === 0) {
            return 0.0;
        }

        $mean = $sum / $count;

        return round($sumSquares / $count - $mean * $mean, 1);
    }

    private function scaleToWidth(\GdImage $image, int $width, bool $keepOriginal = false): \GdImage
    {
        $scaled = imagescale($image, $width, -1, IMG_BICUBIC);

        if (!$keepOriginal) {
            imagedestroy($image);
        }

        return $scaled;
    }

    /**
     * Phone photos are often stored sideways with an EXIF rotation flag.
     */
    private function applyExifOrientation(\GdImage $image, string $path): \GdImage
    {
        if (!function_exists('exif_read_data')) {
            return $image;
        }

        $exif = @exif_read_data($path);
        $angle = match ((int) ($exif['Orientation'] ?? 1)) {
            3 => 180,
            6 => -90,
            8 => 90,
            default => 0,
        };

        if ($angle === 0) {
            return $image;
        }

        $rotated = imagerotate($image, $angle, 0);
        imagedestroy($image);

        return $rotated;
    }
}
