<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessLicenseVerification;
use App\Models\Driver;
use App\Models\DriverLicense;
use App\Services\LicenseVerification\LicenseActivity;
use App\Services\LicenseVerification\LicenseValidationRules;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

/**
 * Driver's license submissions and their verification workflow.
 *
 * This is independent of vehicle authorization and RFID assignment:
 * verifying a license never grants parking access by itself.
 */
class DriverLicenseController extends Controller
{
    private const LICENSE_TYPES = ['student_permit', 'non_professional', 'professional', 'international'];

    private const STATUSES = ['processing', 'needs_correction', 'pending_review', 'verified'];

    /**
     * The signed-in user's current license submission, or null.
     */
    public function mine(Request $request): JsonResponse
    {
        $license = $this->currentLicenseFor($request->user()->user_id);

        return response()->json([
            'data' => $license ? $this->ownerPayload($license) : null,
        ]);
    }

    /**
     * Submit or resubmit the signed-in user's license, then run the automatic check.
     */
    public function submit(Request $request): JsonResponse
    {
        $user = $request->user();
        $today = now()->startOfDay();

        $data = $request->validate([
            'license_number' => ['required', 'string', 'max:20', function ($attribute, $value, $fail) {
                if (!LicenseValidationRules::isLtoFormat((string) $value)) {
                    $fail('Enter the license number in the LTO format, for example N01-23-456789.');
                }
            }],
            'license_type' => ['required', Rule::in(self::LICENSE_TYPES)],
            'expiry_date' => [
                'required',
                'date_format:Y-m-d',
                'after_or_equal:' . $today->toDateString(),
                // LTO licenses are issued for at most 10 years.
                'before_or_equal:' . $today->copy()->addYears(10)->addDay()->toDateString(),
            ],
            'license_file' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'mimetypes:image/jpeg,image/png,application/pdf', 'max:5120'],
        ], [
            'expiry_date.after_or_equal' => 'This license is already expired. Upload a current, unexpired license.',
            'expiry_date.before_or_equal' => 'LTO driver\'s licenses are valid for at most 10 years. Check the expiration date.',
            'license_file.max' => 'The file must be 5 MB or smaller.',
        ]);

        $profile = DB::table('user_profiles')->where('user_id', $user->user_id)->first();

        if (!$profile || !$profile->first_name || !$profile->last_name) {
            return response()->json([
                'message' => 'Your account has no registered name to compare with the license. Contact PASS staff.',
            ], 422);
        }

        $number = LicenseValidationRules::formatLicenseNumber($data['license_number']);
        $file = $request->file('license_file');
        $path = $file->store('driver-licenses', 'private');

        if (!$path) {
            return response()->json(['message' => 'The document could not be stored. Please try again.'], 500);
        }

        $runId = (string) Str::uuid();

        try {
            $outcome = DB::transaction(function () use ($user, $profile, $data, $number, $file, $path, $runId, $request) {
                $driver = Driver::firstOrCreate(
                    ['user_id' => $user->user_id],
                    [
                        'first_name' => $profile->first_name,
                        'middle_name' => $profile->middle_name,
                        'last_name' => $profile->last_name,
                        'contact_number' => $profile->contact_number,
                        'driver_status' => 'active',
                    ]
                );

                $license = DriverLicense::where('driver_id', $driver->driver_id)
                    ->orderByDesc('license_id')
                    ->lockForUpdate()
                    ->first();

                if ($license?->verification_status === 'processing') {
                    return response()->json([
                        'message' => 'Your previous submission is still being checked. Please wait a moment.',
                    ], 409);
                }

                if ($this->numberTakenByAnotherLicense($number, $license?->license_id)) {
                    return response()->json([
                        'message' => 'This license number is already registered to another account.',
                        'errors' => ['license_number' => ['This license number is already registered to another account.']],
                    ], 422);
                }

                $attributes = [
                    'license_number' => $number,
                    'license_type' => $data['license_type'],
                    'expiry_date' => $data['expiry_date'],
                    'license_image_path' => $path,
                    'original_file_name' => Str::limit($file->getClientOriginalName(), 250, ''),
                    'license_status' => 'valid',
                    'verification_status' => 'processing',
                    'verification_source' => 'automatic',
                    'verification_reason' => null,
                    'ocr_result' => null,
                    'verification_run_id' => $runId,
                    'submitted_at' => now(),
                    'checked_at' => null,
                    'reviewed_by' => null,
                    'reviewed_at' => null,
                ];

                $oldPath = null;

                if ($license) {
                    $old = LicenseActivity::snapshot($license);
                    $oldPath = $license->license_image_path;
                    $license->forceFill($attributes)->save();
                    LicenseActivity::audit('license_resubmitted', $license, $old, $user, $request);
                } else {
                    $license = DriverLicense::create($attributes + ['driver_id' => $driver->driver_id]);
                    LicenseActivity::audit('license_submitted', $license, [], $user, $request);
                }

                return [$license, $oldPath];
            });
        } catch (\Throwable $e) {
            Storage::disk('private')->delete($path);
            throw $e;
        }

        if ($outcome instanceof Response) {
            Storage::disk('private')->delete($path);
            return $outcome;
        }

        [$license, $oldPath] = $outcome;

        // The replaced document is no longer needed; keep personal data to a minimum.
        if ($oldPath && $oldPath !== $path) {
            Storage::disk('private')->delete($oldPath);
        }

        $this->dispatchCheck($license->license_id, $runId);

        return response()->json([
            'message' => 'License submitted. The automatic document check has started.',
            'data' => $this->ownerPayload($license->fresh()),
        ], 201);
    }

    /**
     * Staff queue of all submissions with their automatic check results.
     */
    public function index(Request $request): JsonResponse
    {
        if (!$request->user()->canManageParking()) {
            return response()->json(['message' => 'Unauthorized access.'], 403);
        }

        $query = DB::table('driver_licenses as dl')
            ->join('drivers as d', 'dl.driver_id', '=', 'd.driver_id')
            ->leftJoin('users as u', 'd.user_id', '=', 'u.user_id')
            ->leftJoin('users as reviewer', 'dl.reviewed_by', '=', 'reviewer.user_id')
            ->select(
                'dl.license_id',
                'dl.license_number',
                'dl.license_type',
                'dl.expiry_date',
                'dl.original_file_name',
                DB::raw('dl.license_image_path IS NOT NULL as has_file'),
                DB::raw('COALESCE(dl.submitted_at, dl.created_at) as submitted_at'),
                'dl.verification_status',
                'dl.verification_source',
                'dl.verification_reason',
                'dl.ocr_result',
                'dl.checked_at',
                'dl.reviewed_at',
                'reviewer.user_code as reviewer_code',
                'u.user_code as owner_code',
                DB::raw("TRIM(CONCAT_WS(' ', d.first_name, d.last_name)) as owner_name")
            );

        $status = $request->query('status');

        if (in_array($status, self::STATUSES, true)) {
            $query->where('dl.verification_status', $status);
        }

        $search = trim((string) $request->query('search', ''));

        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder->where('dl.license_number', 'like', "%{$search}%")
                    ->orWhere('u.user_code', 'like', "%{$search}%")
                    ->orWhere('d.first_name', 'like', "%{$search}%")
                    ->orWhere('d.last_name', 'like', "%{$search}%");
            });
        }

        $rows = $query
            ->orderByDesc(DB::raw('COALESCE(dl.submitted_at, dl.created_at)'))
            ->get()
            ->map(function ($row) {
                $row->has_file = (bool) $row->has_file;
                $row->ocr_result = $row->ocr_result ? json_decode($row->ocr_result, true) : null;
                return $row;
            });

        return response()->json(['data' => $rows]);
    }

    /**
     * Stream the uploaded document to staff or to its owner. Files live on a
     * private disk and are never publicly reachable.
     */
    public function file(Request $request, int $id)
    {
        $license = DriverLicense::with('driver')->find($id);

        if (!$license || !$license->license_image_path) {
            return response()->json(['message' => 'Document not found.'], 404);
        }

        $user = $request->user();
        $isOwner = $license->driver && (int) $license->driver->user_id === (int) $user->user_id;

        if (!$isOwner && !$user->canManageParking()) {
            return response()->json(['message' => 'Unauthorized access.'], 403);
        }

        $disk = Storage::disk('private');

        if (!$disk->exists($license->license_image_path)) {
            return response()->json(['message' => 'Document not found.'], 404);
        }

        return $disk->response(
            $license->license_image_path,
            $license->original_file_name ?: basename($license->license_image_path),
            [
                'Cache-Control' => 'no-store, private',
                'X-Content-Type-Options' => 'nosniff',
            ],
            'inline'
        );
    }

    /**
     * Staff decision: confirm, return, or send back to review. Also used to
     * correct an automatic decision.
     */
    public function review(Request $request, int $id): JsonResponse
    {
        $actor = $request->user();

        if (!$actor->canManageParking()) {
            return response()->json(['message' => 'You are not authorized to review driver\'s licenses.'], 403);
        }

        $data = $request->validate([
            'decision' => ['required', Rule::in(['verified', 'needs_correction', 'pending_review'])],
            'reason' => ['nullable', 'string', 'max:1000', 'required_if:decision,needs_correction'],
        ], [
            'reason.required_if' => 'Explain what the user needs to correct.',
        ]);

        return DB::transaction(function () use ($id, $actor, $data, $request) {
            $license = DriverLicense::lockForUpdate()->find($id);

            if (!$license) {
                return response()->json(['message' => 'License submission not found.'], 404);
            }

            if ($license->verification_status === 'processing') {
                return response()->json([
                    'message' => 'The automatic check is still running. Try again once it finishes.',
                ], 409);
            }

            if ($data['decision'] === 'verified' && $license->expiry_date?->lt(now()->startOfDay())) {
                return response()->json([
                    'message' => 'An expired license cannot be marked as verified.',
                ], 422);
            }

            $old = LicenseActivity::snapshot($license);

            $license->forceFill([
                'verification_status' => $data['decision'],
                'verification_source' => 'staff',
                'verification_reason' => $data['decision'] === 'needs_correction' ? $data['reason'] : null,
                'reviewed_by' => $actor->user_id,
                'reviewed_at' => now(),
            ])->save();

            LicenseActivity::audit("license_review_{$data['decision']}", $license, $old, $actor, $request);
            LicenseActivity::notifyOwner($license, $data['decision'], $license->verification_reason);

            return response()->json([
                'message' => match ($data['decision']) {
                    'verified' => 'License marked as verified.',
                    'needs_correction' => 'License returned to the user for correction.',
                    default => 'License moved back to pending review.',
                },
                'data' => $this->ownerPayload($license),
            ]);
        });
    }

    /**
     * Staff can re-run the automatic check, e.g. after an OCR outage.
     */
    public function recheck(Request $request, int $id): JsonResponse
    {
        $actor = $request->user();

        if (!$actor->canManageParking()) {
            return response()->json(['message' => 'Unauthorized access.'], 403);
        }

        $runId = (string) Str::uuid();

        $result = DB::transaction(function () use ($id, $runId, $actor, $request) {
            $license = DriverLicense::lockForUpdate()->find($id);

            if (!$license) {
                return response()->json(['message' => 'License submission not found.'], 404);
            }

            if ($license->verification_status === 'processing') {
                return response()->json(['message' => 'The automatic check is already running.'], 409);
            }

            if ($license->verification_status === 'verified') {
                return response()->json(['message' => 'Verified licenses are not re-checked automatically.'], 409);
            }

            $old = LicenseActivity::snapshot($license);
            $license->forceFill([
                'verification_status' => 'processing',
                'verification_source' => 'automatic',
                'verification_run_id' => $runId,
            ])->save();
            LicenseActivity::audit('license_recheck_requested', $license, $old, $actor, $request);

            return null;
        });

        if ($result) {
            return $result;
        }

        $this->dispatchCheck($id, $runId);

        return response()->json([
            'message' => 'Automatic check started again.',
            'data' => $this->ownerPayload(DriverLicense::find($id)),
        ]);
    }

    /**
     * With the sync queue the check runs inline. The job's failed() handler
     * already moves the license to pending review, so the request still succeeds.
     */
    private function dispatchCheck(int $licenseId, string $runId): void
    {
        try {
            ProcessLicenseVerification::dispatch($licenseId, $runId);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    private function currentLicenseFor(int $userId): ?DriverLicense
    {
        return DriverLicense::query()
            ->join('drivers', 'drivers.driver_id', '=', 'driver_licenses.driver_id')
            ->where('drivers.user_id', $userId)
            ->orderByDesc('driver_licenses.license_id')
            ->select('driver_licenses.*')
            ->first();
    }

    private function numberTakenByAnotherLicense(string $number, ?int $exceptId): bool
    {
        $normalized = LicenseValidationRules::normalizeLicenseNumber($number);

        return DB::table('driver_licenses')
            ->whereRaw("REPLACE(REPLACE(UPPER(license_number), '-', ''), ' ', '') = ?", [$normalized])
            ->when($exceptId, fn ($q) => $q->where('license_id', '!=', $exceptId))
            ->exists();
    }

    /**
     * What the owner may see. Raw OCR text and staff notes are not included.
     */
    private function ownerPayload(DriverLicense $license): array
    {
        return [
            'license_id' => $license->license_id,
            'license_number' => $license->license_number,
            'license_type' => $license->license_type,
            'expiry_date' => $license->expiry_date?->toDateString(),
            'original_file_name' => $license->original_file_name,
            'has_file' => (bool) $license->license_image_path,
            'submitted_at' => ($license->submitted_at ?? $license->created_at)?->toIso8601String(),
            'verification_status' => $license->verification_status,
            'verification_source' => $license->verification_source,
            'verification_reason' => $license->verification_reason,
            'checked_at' => $license->checked_at?->toIso8601String(),
            'reviewed_at' => $license->reviewed_at?->toIso8601String(),
        ];
    }
}
