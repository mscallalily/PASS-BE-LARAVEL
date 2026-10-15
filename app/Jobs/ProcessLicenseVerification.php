<?php

namespace App\Jobs;

use App\Models\DriverLicense;
use App\Services\LicenseVerification\LicenseActivity;
use App\Services\LicenseVerification\LicenseDocumentReader;
use App\Services\LicenseVerification\LicenseValidationRules;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Runs the automatic preliminary check on one license submission.
 *
 * Each submission gets a new run ID. This job only writes its result if that
 * run is still current and the license is still "processing", so a stale or
 * duplicate job can never overwrite a newer submission or a staff decision.
 */
class ProcessLicenseVerification implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 180;

    public function __construct(
        public int $licenseId,
        public string $runId,
    ) {
    }

    public function handle(LicenseDocumentReader $reader, LicenseValidationRules $rules): void
    {
        $license = DriverLicense::with('driver')->find($this->licenseId);

        if (!$this->isCurrent($license)) {
            return;
        }

        $document = $this->readDocument($license, $reader);

        $result = $rules->evaluate($document, [
            'license_number' => (string) $license->license_number,
            'expiry_date' => $license->expiry_date?->toDateString() ?? 'today',
            'first_name' => $license->driver?->first_name,
            'last_name' => $license->driver?->last_name,
        ]);

        $this->store($license, $result['decision'], $result['decision'] === 'needs_correction'
            ? implode("\n", $result['reasons'])
            : null, $result + [
                'engine' => $document['kind'] === 'pdf' ? 'pdftotext' : 'tesseract',
                'confidence' => $document['confidence'],
                'sharpness' => $document['sharpness'],
                'width' => $document['width'],
                'height' => $document['height'],
                'text' => Str::limit($document['text'], 4000),
            ]);
    }

    /**
     * An unexpected crash must not leave the submission stuck in "processing".
     */
    public function failed(?Throwable $exception): void
    {
        $license = DriverLicense::find($this->licenseId);

        if ($this->isCurrent($license)) {
            $this->store($license, 'pending_review', null, [
                'preliminary' => true,
                'official_verification' => 'unavailable',
                'notes' => ['The automatic check failed unexpectedly. Staff must review the document manually.'],
                'checks' => [],
            ]);
        }
    }

    private function isCurrent(?DriverLicense $license): bool
    {
        return $license
            && $license->verification_run_id === $this->runId
            && $license->verification_status === 'processing';
    }

    private function readDocument(DriverLicense $license, LicenseDocumentReader $reader): array
    {
        $unreadable = fn (string $error) => [
            'kind' => 'image',
            'engine_available' => false,
            'engine_error' => $error,
            'text' => '',
            'confidence' => null,
            'width' => null,
            'height' => null,
            'sharpness' => null,
        ];

        $disk = Storage::disk('private');
        $path = $license->license_image_path;

        if (!$path || !$disk->exists($path)) {
            return $unreadable('The uploaded file could not be found.');
        }

        try {
            return $reader->read($disk->path($path), (string) $disk->mimeType($path));
        } catch (Throwable $e) {
            report($e);

            return $unreadable('The document could not be processed.');
        }
    }

    /**
     * Compare-and-swap: only the current run may move the license out of "processing".
     */
    private function store(DriverLicense $license, string $status, ?string $reason, array $ocrResult): void
    {
        $old = LicenseActivity::snapshot($license);

        $updated = DriverLicense::whereKey($license->license_id)
            ->where('verification_run_id', $this->runId)
            ->where('verification_status', 'processing')
            ->update([
                'verification_status' => $status,
                'verification_source' => 'automatic',
                'verification_reason' => $reason,
                'ocr_result' => json_encode($ocrResult),
                'checked_at' => now(),
                'updated_at' => now(),
            ]);

        if (!$updated) {
            return;
        }

        $license->refresh();
        LicenseActivity::audit("license_auto_{$status}", $license, $old);
        LicenseActivity::notifyOwner($license, $status, $reason, automatic: true);
    }
}
