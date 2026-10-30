<?php

namespace App\Http\Controllers\Api;


use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\VehicleAuthorization;
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
        'va.document_check_status',
        'va.document_checked_at',
        'va.approval_source',
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

    /**
     * Manual approval is retired for campus vehicles: the automatic OR/CR check
     * (ProcessVehicleDocumentCheck) approves only when every requirement passes,
     * otherwise the owner corrects or re-uploads. Staff keep monitoring,
     * suspension and lifting. Guest/visitor vehicles have no authorization
     * records; their visit requests keep the separate manual Admin approval.
     */
    public function review(Request $request, int $id): JsonResponse
    {
        $actor = $request->user();
        abort_unless($this->canManage($actor), 403);
        return response()->json([
            'message' => 'Vehicle authorizations are decided by the automatic OR/CR document check. The owner corrects or re-uploads documents when a check fails; staff can suspend or lift approved authorizations.',
            'code' => 'vehicle_decisions_automatic',
        ], 403);
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

            $blockers = app(\App\Services\Vehicles\VehicleApprovalEligibility::class)->liftBlockers($authorization);

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
