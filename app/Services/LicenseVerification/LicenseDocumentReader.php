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
    public function read(string $path, string $mime): array
    {
        if ($mime === 'application/pdf') {
            return $this->readPdf($path);
        }

        return $this->readImage($path);
    }

    private function readImage(string $path): array
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

        $image = @imagecreatefromstring((string) file_get_contents($path));

        if ($image === false) {
            $result['engine_error'] = 'The image file could not be decoded.';
            return $result;
        }

        $image = $this->applyExifOrientation($image, $path);
        $result['width'] = imagesx($image);
        $result['height'] = imagesy($image);

        if ($result['width'] > self::MAX_WORK_WIDTH) {
            $image = $this->scaleToWidth($image, self::MAX_WORK_WIDTH);
        }

        imagefilter($image, IMG_FILTER_GRAYSCALE);
        $result['sharpness'] = $this->sharpness($image);

        if (imagesx($image) < self::OCR_MIN_WIDTH) {
            $image = $this->scaleToWidth($image, self::OCR_MIN_WIDTH);
        }

        $tempFile = tempnam(sys_get_temp_dir(), 'lic') . '.png';

        try {
            imagepng($image, $tempFile);
            imagedestroy($image);

            return array_merge($result, $this->tesseract($tempFile));
        } finally {
            @unlink($tempFile);
        }
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
    private function tesseract(string $imagePath): array
    {
        $binary = config('services.license_ocr.tesseract_path', 'tesseract');

        try {
            $process = Process::timeout(config('services.license_ocr.timeout', 60))
                ->run([$binary, $imagePath, 'stdout', '-l', 'eng', '--psm', '3', 'tsv']);
        } catch (Throwable $e) {
            return ['engine_available' => false, 'engine_error' => 'The OCR engine is not available on the server.'];
        }

        if (!$process->successful()) {
            return ['engine_available' => false, 'engine_error' => 'The OCR engine could not process the image.'];
        }

        // TSV columns: level page block par line word left top width height conf text
        $lines = [];
        $confidences = [];

        foreach (preg_split('/\R/', $process->output()) as $index => $row) {
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
        }

        return [
            'engine_available' => true,
            'engine_error' => null,
            'text' => implode("\n", array_map(fn ($words) => implode(' ', $words), $lines)),
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
