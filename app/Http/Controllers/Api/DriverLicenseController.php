<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DriverLicense;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class DriverLicenseController extends Controller
{
    private function canReviewLicenses(User $user): bool
    {
        return $user->account_status === 'active'
            && $user->roles()
                ->whereIn('role_code', ['admin', 'parking_admin'])
                ->where('is_active', true)
                ->exists();
    }

    /**
     * List real driver license submissions for the admin review queue.
     */
    public function index(Request $request): JsonResponse
    {
        $actor = $request->user();

        if (! $actor || ! $this->canReviewLicenses($actor)) {
            return response()->json([
                'message' => 'You are not authorized to review driver licenses.',
            ], 403);
        }

        $licenses = DriverLicense::with([
            'driver.user.profile',
        ])
            ->orderByRaw(
                "CASE WHEN review_status = 'pending' THEN 0 ELSE 1 END"
            )
            ->orderByDesc('created_at')
            ->get()
            ->map(function (DriverLicense $license) {
                $driver = $license->driver;
                $profile = $driver?->user?->profile;

                $owner = trim(implode(' ', array_filter([
                    $profile?->first_name ?? $driver?->first_name,
                    $profile?->last_name ?? $driver?->last_name,
                ])));

                return [
                    'id' => $license->license_id,
                    'owner' => $owner ?: 'Unknown user',
                    'licenseNo' => $license->license_number,
                    'expiry' => $license->expiry_date?->format('Y-m-d'),
                    'file' => $license->license_image_path
                        ? basename($license->license_image_path)
                        : null,
                    'submitted' => $license->created_at?->format('Y-m-d'),
                    'status' => $license->review_status,
                    'reviewNotes' => $license->review_notes,
                    'reviewedAt' => $license->reviewed_at?->toISOString(),
                ];
            });

        return response()->json(['data' => $licenses]);
    }

    /**
     * Persist a license review decision.
     */
    public function review(
        Request $request,
        int $id
    ): JsonResponse {
        $actor = $request->user();

        if (! $actor || ! $this->canReviewLicenses($actor)) {
            return response()->json([
                'message' => 'You are not authorized to review driver licenses.',
            ], 403);
        }

        $data = $request->validate([
            'status' => [
                'required',
                Rule::in(['verified', 'rejected']),
            ],
            'notes' => [
                Rule::requiredIf(
                    fn () => $request->input('status') === 'rejected'
                ),
                'nullable',
                'string',
                'max:2000',
            ],
        ]);

        $license = DB::transaction(function () use (
            $request,
            $id,
            $data
        ) {
            $license = DriverLicense::whereKey($id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($license->review_status !== 'pending') {
                abort(422, 'This license has already been reviewed.');
            }

            $license->forceFill([
                'review_status' => $data['status'],
                'review_notes' => $data['notes'] ?? null,
                'reviewed_by' => $request->user()->user_id,
                'reviewed_at' => now(),
            ])->save();

            return $license;
        });

        return response()->json([
            'message' => $license->review_status === 'verified'
                ? 'Driver license verified successfully.'
                : 'Driver license marked for correction.',
            'data' => [
                'id' => $license->license_id,
                'status' => $license->review_status,
                'reviewNotes' => $license->review_notes,
            ],
        ]);
    }
}
