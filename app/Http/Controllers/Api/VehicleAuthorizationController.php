<?php

namespace App\Http\Controllers\Api;


use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\VehicleAuthorization;
use App\Support\CampusTime;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class VehicleAuthorizationController extends Controller
{
    /**
     * Columns returned for every authorization row, shared by all endpoints
     * so the staff table and the owner's status tab stay in sync.
     */
    private const COLUMNS = [
        'va.authorization_id',
        'va.sticker_number',
        'va.authorization_status',
        'va.valid_from',
        'va.valid_until',
        'va.requested_at',
        'va.reviewed_at',
        'va.remarks',
        'v.vehicle_id',
        'v.plate_number',
        'v.make',
        'v.model',
        'v.color',
        'v.year_model',
        'vt.type_name as vehicle_type',
        'pa.area_name as parking_area',
    ];

    /**
     * Only active administrators and parking/security staff may manage
     * vehicle authorizations.
     */
    private function canManage(?User $user): bool
    {
        return $user?->canManageParking() ?? false;
    }

    /**
     * Base query for staff views: authorizations with vehicle, owner, and area.
     */
    private function staffQuery(): Builder
    {
        return DB::table('vehicle_authorizations as va')
            ->join('vehicles as v', 'va.vehicle_id', '=', 'v.vehicle_id')
            ->leftJoin('vehicle_types as vt', 'v.vehicle_type_id', '=', 'vt.vehicle_type_id')
            ->leftJoin('users as u', 'v.owner_user_id', '=', 'u.user_id')
            ->leftJoin('user_profiles as up', 'v.owner_user_id', '=', 'up.user_id')
            ->leftJoin('visitors as vis', 'v.owner_visitor_id', '=', 'vis.visitor_id')
            ->leftJoin('parking_areas as pa', 'va.parking_area_id', '=', 'pa.parking_area_id')
            ->whereNull('v.deleted_at')
            ->whereRaw('va.authorization_id = (SELECT MAX(latest.authorization_id) FROM vehicle_authorizations latest WHERE latest.vehicle_id = v.vehicle_id)')
            ->select(self::COLUMNS)
            ->addSelect(
                'u.user_code as owner_code',
                DB::raw("CASE WHEN v.owner_user_id IS NOT NULL THEN 'user' ELSE 'visitor' END as owner_kind"),
                DB::raw("TRIM(CONCAT_WS(' ', COALESCE(up.first_name, vis.first_name), COALESCE(up.last_name, vis.last_name))) as owner_name")
            );
    }

    /**
     * List vehicle authorizations with vehicle, owner, and parking area details.
     */
    public function index(Request $request): JsonResponse
    {
        if (!$this->canManage($request->user())) {
            return response()->json([
                'message' => 'Unauthorized access.'
            ], 403);
        }

        $query = $this->staffQuery();

        // Optional status filter.
        $status = $request->query('status');

        if (in_array($status, ['pending', 'approved', 'rejected', 'revoked', 'expired', 'suspended'], true)) {
            $query->where('va.authorization_status', $status);
        }

        // Optional plate, sticker, or owner search.
        $search = trim((string) $request->query('search', ''));

        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder->where('v.plate_number', 'like', "%{$search}%")
                    ->orWhere('va.sticker_number', 'like', "%{$search}%")
                    ->orWhere('u.user_code', 'like', "%{$search}%")
                    ->orWhere('up.first_name', 'like', "%{$search}%")
                    ->orWhere('up.last_name', 'like', "%{$search}%")
                    ->orWhere('vis.first_name', 'like', "%{$search}%")
                    ->orWhere('vis.last_name', 'like', "%{$search}%");
            });
        }

        $authorizations = $query
            ->orderByDesc('va.requested_at')
            ->get();

        return response()->json([
            'data' => $authorizations,
        ]);
    }

    /**
     * List the authenticated user's own vehicles with their latest authorization.
     * Vehicles without an authorization record are included with null status.
     */
    public function mine(Request $request): JsonResponse
    {
        $user = $request->user();

        $vehicles = DB::table('vehicles as v')
            ->leftJoin('vehicle_authorizations as va', function ($join) {
                $join->on('va.vehicle_id', '=', 'v.vehicle_id')
                    ->whereRaw('va.authorization_id = (
                        SELECT MAX(latest.authorization_id)
                        FROM vehicle_authorizations AS latest
                        WHERE latest.vehicle_id = v.vehicle_id
                    )');
            })
            ->leftJoin('vehicle_types as vt', 'v.vehicle_type_id', '=', 'vt.vehicle_type_id')
            ->leftJoin('parking_areas as pa', 'va.parking_area_id', '=', 'pa.parking_area_id')
            ->where('v.owner_user_id', $user->user_id)
            ->whereNull('v.deleted_at')
            ->select(self::COLUMNS)
            ->orderBy('v.plate_number')
            ->get();

        return response()->json([
            'data' => $vehicles,
        ]);
    }

    /** Review a pending vehicle authorization; RFID assignment stays separate. */
    public function review(Request $request, int $id): JsonResponse
    {
        $actor = $request->user();
        abort_unless($this->canManage($actor), 403);
        $data = $request->validate([
            'decision' => ['required', 'in:approved,rejected'],
            'reason' => ['required_if:decision,rejected', 'nullable', 'string', 'max:255'],
        ]);
        return DB::transaction(function () use ($request, $actor, $data, $id) {
            $authorization = VehicleAuthorization::lockForUpdate()->findOrFail($id);
            if ($authorization->authorization_status !== 'pending') {
                return response()->json(['message' => 'Only pending authorizations can be reviewed. Refresh the list.'], 409);
            }
            $vehicle = DB::table('vehicles')->where('vehicle_id', $authorization->vehicle_id)->lockForUpdate()->first();
            if (!$vehicle || $vehicle->deleted_at || $vehicle->registration_status !== 'registered') {
                return response()->json(['message' => 'Vehicle is no longer registered.'], 422);
            }
            $latestId = DB::table('vehicle_authorizations')->where('vehicle_id', $vehicle->vehicle_id)->max('authorization_id');
            if ((int) $latestId !== (int) $authorization->authorization_id) {
                return response()->json(['message' => 'A newer submission exists. Refresh and review the latest vehicle details and photo.'], 409);
            }
            if ($data['decision'] === 'approved') {
                $blockers = $this->liftBlockers($authorization);
                if (!$vehicle->vehicle_photo_path || !\Illuminate\Support\Facades\Storage::disk('private')->exists($vehicle->vehicle_photo_path)) {
                    $blockers[] = 'A vehicle photo showing the plate is required before approval.';
                }
                $eligibleDriver = DB::table('vehicle_driver as vd')
                    ->join('drivers as d', 'vd.driver_id', '=', 'd.driver_id')
                    ->join('driver_licenses as dl', 'd.driver_id', '=', 'dl.driver_id')
                    ->where('vd.vehicle_id', $vehicle->vehicle_id)->where('d.driver_status', 'active')
                    ->whereRaw('dl.license_id = (SELECT MAX(latest.license_id) FROM driver_licenses latest WHERE latest.driver_id = d.driver_id)')
                    ->where('dl.verification_status', 'verified')->where('dl.license_status', 'valid')
                    ->where('dl.expiry_date', '>=', CampusTime::date())
                    ->where(fn ($q) => $q->whereNull('vd.authorized_from')->orWhere('vd.authorized_from', '<=', CampusTime::date()))
                    ->where(fn ($q) => $q->whereNull('vd.authorized_until')->orWhere('vd.authorized_until', '>=', CampusTime::date()))
                    ->exists();
                if (!$eligibleDriver) $blockers[] = 'The latest license of an eligible linked driver must be verified and unexpired.';

                if ($blockers) return response()->json(['message' => implode(' ', $blockers)], 422);
            }
            $old = $authorization->only(['authorization_status', 'remarks', 'reviewed_by', 'reviewed_at']);
            $authorization->forceFill([
                'authorization_status' => $data['decision'],
                'remarks' => $data['reason'] ?? null,
                'reviewed_by' => $actor->user_id,
                'reviewed_at' => now(),
            ])->save();
            $this->audit($request, $actor, 'authorization_'.$data['decision'], $authorization, $old);
            return response()->json([
                'message' => 'Vehicle authorization '.$data['decision'].'. RFID assignment remains separate.',
                'data' => $this->staffQuery()->where('va.authorization_id', $id)->first(),
            ]);
        });
    }

    /**
     * Suspend an approved authorization so the vehicle is treated as unauthorized.
     */
    public function suspend(Request $request, int $id): JsonResponse
    {
        $actor = $request->user();

        if (!$this->canManage($actor)) {
            return response()->json([
                'message' => 'You are not authorized to suspend vehicle authorizations.',
            ], 403);
        }

        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $result = DB::transaction(function () use ($id, $actor, $data, $request) {
            $authorization = VehicleAuthorization::lockForUpdate()->find($id);

            if (!$authorization) {
                return response()->json(['message' => 'Vehicle authorization not found.'], 404);
            }

            if ($authorization->authorization_status !== 'approved') {
                return response()->json([
                    'message' => 'Only approved authorizations can be suspended. This one is '
                        . $authorization->authorization_status . '.',
                ], 409);
            }

            $old = $authorization->only(['authorization_status', 'remarks', 'reviewed_by', 'reviewed_at']);

            $authorization->forceFill([
                'authorization_status' => 'suspended',
                'remarks' => $data['reason'] ?? 'Suspended by parking staff.',
                'reviewed_by' => $actor->user_id,
                'reviewed_at' => now(),
            ])->save();

            $this->audit($request, $actor, 'suspended', $authorization, $old);

            return null;
        });

        if ($result) {
            return $result;
        }

        return response()->json([
            'message' => 'Vehicle authorization suspended.',
            'data' => $this->staffQuery()->where('va.authorization_id', $id)->first(),
        ]);
    }

    /**
     * Lift a suspension and restore the authorization to approved, provided
     * the vehicle still meets every approval and access requirement.
     */
    public function liftSuspension(Request $request, int $id): JsonResponse
    {
        $actor = $request->user();

        if (!$this->canManage($actor)) {
            return response()->json([
                'message' => 'You are not authorized to lift vehicle suspensions.',
            ], 403);
        }

        return DB::transaction(function () use ($id, $actor, $request) {
            $authorization = VehicleAuthorization::lockForUpdate()->find($id);

            if (!$authorization) {
                return response()->json(['message' => 'Vehicle authorization not found.'], 404);
            }

            if ($authorization->authorization_status !== 'suspended') {
                return response()->json([
                    'message' => 'This authorization is not suspended.',
                ], 409);
            }

            $blockers = $this->liftBlockers($authorization);

            if ($blockers) {
                return response()->json([
                    'message' => 'The suspension cannot be lifted: ' . implode(' ', $blockers),
                    'reasons' => $blockers,
                ], 422);
            }

            $old = $authorization->only(['authorization_status', 'remarks', 'reviewed_by', 'reviewed_at']);

            // Only approved authorizations can be suspended, so approved is the
            // status this record held before the suspension.
            $authorization->forceFill([
                'authorization_status' => 'approved',
                'remarks' => null,
                'reviewed_by' => $actor->user_id,
                'reviewed_at' => now(),
            ])->save();

            $this->audit($request, $actor, 'suspension_lifted', $authorization, $old);

            return response()->json([
                'message' => 'Suspension lifted. The vehicle authorization is approved again.',
                'data' => $this->staffQuery()->where('va.authorization_id', $id)->first(),
            ]);
        });
    }

    /**
     * Reasons the authorization cannot return to approved. Empty when eligible.
     *
     * @return string[]
     */
    private function liftBlockers(VehicleAuthorization $authorization): array
    {
        $today = CampusTime::date();
        $blockers = [];

        $vehicle = DB::table('vehicles')
            ->where('vehicle_id', $authorization->vehicle_id)
            ->first();

        if (!$vehicle || $vehicle->deleted_at !== null || $vehicle->registration_status !== 'registered') {
            return ['The vehicle is no longer registered.'];
        }

        if ($authorization->valid_until && $authorization->valid_until < $today) {
            $blockers[] = "The authorization validity ended on {$authorization->valid_until}.";
        }

        if ($vehicle->owner_user_id) {
            $ownerStatus = DB::table('users')
                ->where('user_id', $vehicle->owner_user_id)
                ->whereNull('deleted_at')
                ->value('account_status');

            if ($ownerStatus !== 'active') {
                $blockers[] = "The owner's account is " . ($ownerStatus ?? 'missing') . '.';
            }
        } elseif (DB::table('visitors')->where('visitor_id', $vehicle->owner_visitor_id)->value('is_blacklisted')) {
            $blockers[] = 'The visitor who owns this vehicle is blacklisted.';
        }

        $latestId = DB::table('vehicle_authorizations')->where('vehicle_id', $authorization->vehicle_id)->max('authorization_id');
        if ((int) $latestId !== (int) $authorization->authorization_id) {
            $blockers[] = 'A newer authorization exists. Review the current vehicle record.';
        }

        // When drivers are linked, at least one must be active, still
        // authorized for this vehicle, and hold a verified, valid, unexpired license
        // (the same rule the gate applies at entry).
        $hasLinkedDrivers = DB::table('vehicle_driver')
            ->where('vehicle_id', $authorization->vehicle_id)
            ->exists();

        if ($hasLinkedDrivers) {
            $hasEligibleDriver = DB::table('vehicle_driver as vd')
                ->join('drivers as d', 'vd.driver_id', '=', 'd.driver_id')
                ->join('driver_licenses as dl', 'dl.driver_id', '=', 'd.driver_id')
                ->where('vd.vehicle_id', $authorization->vehicle_id)
                ->where('d.driver_status', 'active')
                ->where(fn ($q) => $q->whereNull('vd.authorized_until')->orWhere('vd.authorized_until', '>=', $today))
                ->where('dl.license_status', 'valid')
                ->where('dl.verification_status', 'verified')
                ->where(fn ($q) => $q->whereNull('dl.expiry_date')->orWhere('dl.expiry_date', '>=', $today))
                ->exists();

            if (!$hasEligibleDriver) {
                $blockers[] = 'No linked driver has an active record and a verified, valid, unexpired license.';
            }
        }

        return $blockers;
    }

    /**
     * Record the status change in the existing audit_logs table.
     */
    private function audit(Request $request, User $actor, string $action, VehicleAuthorization $authorization, array $old): void
    {
        DB::table('audit_logs')->insert([
            'user_id' => $actor->user_id,
            'actor_label' => $actor->user_code,
            'action' => $action,
            'auditable_type' => VehicleAuthorization::class,
            'auditable_id' => $authorization->authorization_id,
            'old_values' => json_encode($old),
            'new_values' => json_encode($authorization->only(['authorization_status', 'remarks', 'reviewed_by', 'reviewed_at'])),
            'ip_address' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 252),
            'created_at' => now(),
        ]);
    }
}
