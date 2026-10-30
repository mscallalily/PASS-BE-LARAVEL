<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\LicenseVerification\LicenseDocumentReader;
use App\Services\LicenseVerification\LicenseFieldConfidence;
use App\Services\LicenseVerification\LicenseOcrRecovery;
use App\Services\LicenseVerification\LicenseValidationRules;
use App\Support\CampusTime;
use Illuminate\Http\UploadedFile;
use App\Services\Vehicles\VehicleDocumentFields;
use App\Services\Vehicles\VehicleDocumentReader;
use App\Services\Vehicles\VehicleDocumentRules;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Pre-fills the existing license and vehicle forms from an uploaded image.
 *
 * Reads PHP's temporary upload only: nothing is stored, approved or logged.
 * Only the fields the form asks for are returned, each with a flag when it
 * is missing or was read with low confidence. Approval always re-reads the
 * submitted files on the server (ProcessLicenseVerification /
 * ProcessVehicleDocumentCheck); values returned here are suggestions only.
 */
class DocumentAutofillController extends Controller
{
    /**
     * Fields each document must provide for the automatic check. Other
     * fields (color, year, vehicle type, and the OR's make) are returned
     * whenever they are read, but are not required from that document.
     */
    private const FORM_FIELDS = [
        'driver_license' => ['license_number', 'expiry_date'],
        'vehicle_or' => ['plate_number', 'registration_expiry'],
        'vehicle_cr' => ['plate_number', 'make', 'model'],
    ];

    public function __invoke(Request $request, LicenseDocumentReader $reader, VehicleDocumentReader $vehicleReader, VehicleDocumentFields $parser): JsonResponse
    {
        $user = $request->user();
        abort_unless($user && $user->account_status === 'active' && $user->roles()
            ->whereIn('role_code', ['student', 'faculty', 'employee'])->where('is_active', true)->exists(), 403, 'Only campus users can read documents for registration.');

        $data = $request->validate([
            'document_type' => ['required', 'in:driver_license,vehicle_or,vehicle_cr'],
            'document' => ['required', 'file', 'mimes:jpg,jpeg,png', 'mimetypes:image/jpeg,image/png', 'max:5120'],
        ], ['document.*' => 'Upload a JPG or PNG photo of 5 MB or less.']);

        if ($data['document_type'] === 'driver_license') {
            return $this->licenseAutofill($request->file('document'), $reader);
        }

        return $this->vehicleAutofill($request->file('document'), $data['document_type'], $vehicleReader, $parser);
    }

    /**
     * Reads an OR or CR with the same reader and parser as the automatic
     * check (ProcessVehicleDocumentCheck), so the review shows what the
     * check will see. Each field is judged on its own; a value only one read
     * found is marked for checking. Field notes are returned separately from
     * document issues: the review combines the OR and CR readings, so a field
     * this document could not confirm may still be supplied by the other one.
     */
    private function vehicleAutofill(UploadedFile $file, string $type, VehicleDocumentReader $reader, VehicleDocumentFields $parser): JsonResponse
    {
        $kind = $type === 'vehicle_or' ? 'or' : 'cr';
        $name = $kind === 'or' ? 'Official Receipt (OR)' : 'Certificate of Registration (CR)';
        try {
            $document = $reader->read($file->getRealPath(), (string) $file->getMimeType());
        } catch (Throwable $exception) {
            report($exception);
            return $this->respond('unavailable', [], [], ['The document reader is unavailable. Try again shortly; your documents are checked again when you submit.']);
        }

        $quality = VehicleDocumentRules::imageQuality($document);
        if ($quality['result'] === 'unavailable') {
            return $this->respond('unavailable', [], [], ['The document reader is unavailable. Try again shortly; your documents are checked again when you submit.']);
        }
        if (($document['width'] ?? null) === null) {
            return $this->respond('invalid_image', [], [], [ucfirst($quality['detail'])]);
        }
        if ($quality['result'] !== 'pass') {
            return $this->respond('retake', [], [], ["Retake the {$name}: {$quality['detail']}"]);
        }

        $parsed = $parser->parseDocument($document);
        if (!($parsed['markers'][$kind] ?? false)) {
            return $this->respond('wrong_document', [], [], ["This photo does not show the {$name} heading. Make sure you uploaded the {$name}, flat and in full."]);
        }
        $fields = array_filter($this->vehicleFields($parsed), fn ($value) => $value !== null);
        $agreement = ['plate_number' => 'plate', 'make' => 'make', 'model' => 'model', 'registration_expiry' => 'registration_expiry'];
        $readsUsed = count($document['reads'] ?? [1]);
        $printed = ['plate_number' => 'PLATE NO.', 'make' => 'MAKE/BRAND', 'model' => 'SERIES', 'registration_expiry' => 'validity ("valid until")'];
        $flags = [];
        $issues = [];
        $notes = [];
        foreach (self::FORM_FIELDS[$type] as $field) {
            if (!isset($fields[$field])) {
                $flags[$field] = 'missing';
                // Label found but value unreadable, or that part of the document not found at all.
                $notes[$field] = in_array($agreement[$field], $parsed['labels_found'] ?? [], true)
                    ? "The {$printed[$field]} area was located on the {$name} but could not be read clearly. Retake it flat, in focus and free of glare."
                    : "The {$printed[$field]} area could not be found on the {$name}. Make sure the whole document is in the photo.";
            } elseif (($readsUsed > 1 && ($parsed['agreement'][$agreement[$field]] ?? 0) < 2)
                // A series code with a digit next to a look-alike letter ("FU1SOMFZ") may be misread.
                || ($field === 'model' && preg_match('/\d[SOIZBQ]|[SOIZBQ]\d/', str_replace(' ', '', (string) $fields[$field])))) {
                $flags[$field] = 'low_confidence';
                $notes[$field] = "Read from the {$name} with limited certainty. Check it against the document.";
            }
        }
        // A plate already registered to someone else cannot be registered again.
        if (isset($fields['plate_number'])) {
            $owner = DB::table('vehicles')->whereNull('deleted_at')
                ->whereRaw("REPLACE(REPLACE(UPPER(plate_number), '-', ''), ' ', '') = ?", [VehicleDocumentFields::plateKey($fields['plate_number'])])->value('owner_user_id');
            if ($owner !== null && (int) $owner !== (int) request()->user()?->user_id) {
                $flags['plate_number'] = 'conflict';
                $issues[] = "Plate {$fields['plate_number']} is already registered in PASS to another account. A plate can be registered only once; contact PASS staff if this is your vehicle.";
            }
        }
        if (isset($fields['registration_expiry']) && $fields['registration_expiry'] < CampusTime::now()->toDateString()) {
            $issues[] = "The registration validity date read from the OR ({$fields['registration_expiry']}) has passed. An expired registration cannot be approved; renew it and upload the new OR.";
        }
        $required = count(self::FORM_FIELDS[$type]);
        $status = count(array_keys($flags, 'missing', true)) === $required ? 'retake' : ($flags ? 'partial' : 'ok');
        $message = match ($status) {
            'ok' => "{$name} read. Check the combined details in the review below.",
            // Read, with some details unclear on this photo alone: not a failure. The combined
            // review decides whether the other document confirms them or the owner must check.
            'partial' => "{$name} read. The review below combines it with the other document.",
            default => "Not enough could be read from this {$name}. Retake or re-upload a clearer photo of this document.",
        };

        return $this->respond($status, $fields, $flags, $issues, $notes, $message);
    }

    /**
     * Reads the license with the same pipeline as the submission check
     * (orientation, straightening, field regions) and judges each field on
     * its own readability. There is no whole-image quality gate: a general
     * quality score only explains a field that could not be read.
     */
    private function licenseAutofill(UploadedFile $file, LicenseDocumentReader $reader): JsonResponse
    {
        try {
            $document = (new LicenseOcrRecovery($reader))->read($file->getRealPath(), (string) $file->getMimeType());
        } catch (Throwable $exception) {
            report($exception);
            return $this->respond('unavailable', [], [], ['The document reader is unavailable. Enter the details manually; your submission is checked again when you submit.']);
        }

        if (($document['width'] ?? null) === null) {
            return $this->respond('invalid_image', [], [], [$document['engine_error'] ?? 'The file could not be opened as an image. Upload a JPG or PNG photo of the license front.']);
        }
        if (!($document['engine_available'] ?? false)) {
            return $this->respond('unavailable', [], [], ['The document reader is unavailable. Enter the details manually; your submission is checked again when you submit.']);
        }

        $found = LicenseFieldConfidence::extract($document);
        $number = $found['license_number'];
        $expiry = $found['expiry_date'];
        $fields = array_filter(['license_number' => $number['value'] ?? null, 'expiry_date' => $expiry['value'] ?? null]);
        $flags = [];
        $issues = [];
        // "Reliable" means what the submission check will require, so the
        // owner can retake now instead of after submitting.
        $evidence = LicenseFieldConfidence::assess($document, ['license_number' => $number['value'] ?? '', 'expiry_date' => $expiry['value'] ?? '']);

        if (!$number) {
            $flags['license_number'] = 'missing';
            $issues[] = 'The license number could not be read. Please capture the License No. area clearly: in focus, without glare, and not covered by a finger.';
        } elseif ($number['conflict'] || !$number['labeled'] || $evidence['number_conflict']
            || (($evidence['fields']['license_number'] ?? 0) < LicenseFieldConfidence::RELIABLE && !$evidence['number_consensus'])) {
            $flags['license_number'] = 'low_confidence';
            $issues[] = 'The license number was read, but not clearly enough for automatic approval. Check it against your license, and retake the photo with the License No. area sharp and free of glare.';
        }
        if (!$expiry) {
            $flags['expiry_date'] = 'missing';
            $issues[] = 'The expiration date could not be read. Please capture the Expiration Date area clearly: in focus, without glare, and not covered by a finger.';
        } elseif ($expiry['conflict'] || $evidence['expiry_conflict'] || ($evidence['fields']['expiry_date'] ?? 0) < LicenseFieldConfidence::RELIABLE) {
            $flags['expiry_date'] = 'low_confidence';
            $issues[] = 'The expiration date was read, but not clearly enough for automatic approval. Check it against your license, and retake the photo with the Expiration Date area sharp and free of glare.';
        }
        if ($expiry && $expiry['value'] < CampusTime::now()->toDateString()) {
            $issues[] = "The expiration date read from the photo ({$expiry['value']}) has passed. An expired license cannot be approved; upload your current license.";
        }

        // Why a field could not be read, when the photo itself suggests it.
        $text = strtoupper((string) ($document['text'] ?? ''));
        $readable = strlen(preg_replace('/[^A-Z0-9]/', '', $text)) >= 60;
        if (count($fields) < 2) {
            if (!$readable) $issues[] = 'Almost no text could be read from the photo.';
            if (($document['sharpness'] ?? INF) < LicenseValidationRules::BLUR_THRESHOLD) $issues[] = 'The photo looks out of focus. Hold the camera steady and tap to focus on the license.';
            if (($document['width'] ?? INF) < LicenseValidationRules::MIN_WIDTH || ($document['height'] ?? INF) < LicenseValidationRules::MIN_HEIGHT) $issues[] = 'The photo resolution is low. Move closer so the license fills the photo.';
        }
        $looksLikeLto = str_contains($text, 'LAND TRANSPORTATION') || preg_match("/DRIVER'?S?\\s+LICENSE/", strtr($text, ['’' => "'"]));
        if ($readable && !$looksLikeLto) {
            $issues[] = "This does not look like the front of a Philippine LTO driver's license. Automatic approval needs the full front of your LTO license.";
        }

        $status = !$fields ? ($readable && !$looksLikeLto ? 'not_license' : 'retake') : ($flags ? 'partial' : 'ok');

        return $this->respond($status, $fields, $flags, $issues);
    }

    private function vehicleFields(array $parsed): array
    {
        $typeName = $parsed['type_code'] ? DB::table('vehicle_types')->where('type_code', $parsed['type_code'])->whereNotNull('parking_area_id')->value('type_name') : null;
        return [
            'plate_number' => $parsed['plate'],
            'make' => $parsed['make'],
            'model' => $parsed['model'],
            'color' => $parsed['color'],
            'year_model' => $parsed['year'],
            'vehicle_type' => $typeName,
            'registration_expiry' => $parsed['registration_expiry'],
        ];
    }

    private function respond(string $status, array $fields, array $flags, array $issues, array $notes = [], ?string $message = null): JsonResponse
    {
        $message ??= match ($status) {
            'ok' => 'Check the details read from your document and correct anything that is wrong. Your submission is checked again when you submit.',
            'partial' => 'Some details were read from your photo. Check them, then retake the photo or enter only the details marked below.',
            'retake' => 'Some details could not be read reliably. Retake or re-upload a clearer photo, or correct the details manually.',
            'not_license' => 'No license details were found in this image.',
            'wrong_document' => 'This photo does not look like the expected document.',
            'invalid_image' => 'This file could not be read as a photo.',
            default => 'The document could not be read now. Enter the details manually.',
        };
        return response()->json(['status' => $status, 'fields' => (object) $fields, 'flags' => (object) $flags, 'notes' => (object) $notes, 'issues' => $issues, 'message' => $message])
            ->header('Cache-Control', 'no-store, private');
    }
}
