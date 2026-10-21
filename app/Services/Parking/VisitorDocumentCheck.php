<?php

namespace App\Services\Parking;

use App\Services\LicenseVerification\LicenseDocumentReader;
use App\Services\LicenseVerification\LicenseValidationRules;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;

class VisitorDocumentCheck
{
    public function __construct(private LicenseDocumentReader $reader, private LicenseValidationRules $rules) {}

    public function check(object $request, string $plate): array
    {
        $names = preg_split('/\s+/', trim($request->driver_name ?? ''));
        $last = count($names) > 1 ? array_pop($names) : null;
        $license = $this->read($request->driver_license_photo_path);
        $validation = !empty($request->driver_license_encrypted) && !empty($request->driver_license_expiry)
            ? $this->rules->evaluate($license, [
                'license_number' => Crypt::decryptString($request->driver_license_encrypted),
                'expiry_date' => $request->driver_license_expiry,
                'first_name' => implode(' ', $names), 'last_name' => $last,
            ]) : ['checks' => [], 'notes' => ['Declared driver details are missing.'], 'extracted' => []];
        $vehicle = $this->read($request->vehicle_photo_path);
        return [
            'license' => $validation,
            'plate' => self::comparePlate($vehicle['text'] ?? '', $plate, $vehicle['confidence'] ?? null),
            'preliminary' => true,
        ];
    }

    public static function comparePlate(string $text, string $submitted, ?float $confidence): array
    {
        // Whole plate candidates only: never match a partial number or silently correct OCR characters.
        preg_match_all('/(?<![A-Z0-9])(?:[A-Z]{2,3}[\s-]*\d{3,4}|\d{3}[\s-]*[A-Z]{2,3})(?![A-Z0-9])/i', $text, $matches);
        $normalize = fn ($value) => strtoupper(preg_replace('/[^A-Z0-9]/i', '', $value));
        $candidates = array_values(array_unique(array_map($normalize, $matches[0])));
        $matched = in_array($normalize($submitted), $candidates, true);
        $result = $matched ? 'pass' : (count($candidates) === 1 && $confidence >= 70 ? 'fail' : 'uncertain');
        return ['result' => $result, 'message' => $matched ? 'The plate read from the photo matches the submitted plate. Confirm it visually.' : ($result === 'fail' ? 'The plate read from the photo differs from the submitted plate.' : 'The plate could not be matched confidently. Inspect the photo manually.'), 'submitted' => $submitted, 'candidates' => $candidates, 'confidence' => $confidence];
    }

    private function read(?string $path): array
    {
        $disk = Storage::disk('private');
        if (!$path || !$disk->exists($path)) return ['kind' => 'image', 'engine_available' => false, 'engine_error' => 'Photo is not on file.', 'text' => '', 'width' => null, 'confidence' => null];
        try {
            return $this->reader->read($disk->path($path), $disk->mimeType($path) ?: 'image/jpeg');
        } catch (\Throwable $error) {
            report($error);
            return ['kind' => 'image', 'engine_available' => false, 'engine_error' => 'Photo could not be processed. Review it manually.', 'text' => '', 'width' => null, 'confidence' => null];
        }
    }
}
