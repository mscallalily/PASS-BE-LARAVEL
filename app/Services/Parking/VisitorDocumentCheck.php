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
        return [
            'license' => $validation,
            'plate' => [
                'result' => 'manual',
                'message' => 'Compare the submitted plate and vehicle details with the uploaded vehicle photo.',
                'submitted' => $plate,
            ],
            'preliminary' => true,
        ];
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
