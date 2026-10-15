<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Parking\ParkingAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Parking violations, including wrong-slot incidents reported from Parking
 * Operations. Staff acknowledge, resolve or dismiss them here.
 */
class ViolationController extends Controller
{
    private const SLOT_TYPES = [ParkingAccessService::WRONG_SLOT, ParkingAccessService::WRONG_AREA];

    public function __construct(private ParkingAccessService $access)
    {
    }

    private function forbidden(Request $request): ?JsonResponse
    {
        return $request->user()->canManageParking()
            ? null
            : response()->json(['message' => 'Only Parking Staff and administrators can manage violations.'], 403);
    }

    public function types(Request $request): JsonResponse
    {
        if ($denied = $this->forbidden($request)) {
            return $denied;
        }

        return response()->json([
            'data' => DB::table('violation_types')->where('is_active', true)->orderBy('violation_type_id')
                ->get(['violation_type_id', 'violation_code', 'violation_name', 'severity']),
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        if ($denied = $this->forbidden($request)) {
            return $denied;
        }

        $query = DB::table('parking_violations as pv')
            ->join('violation_types as vt', 'vt.violation_type_id', '=', 'pv.violation_type_id')
            ->leftJoin('vehicles as v', 'v.vehicle_id', '=', 'pv.vehicle_id')
            ->leftJoin('parking_slots as obs', 'obs.slot_id', '=', 'pv.slot_id')
            ->leftJoin('parking_slots as exp', 'exp.slot_id', '=', 'pv.expected_slot_id')
            ->leftJoin('parking_areas as a', 'a.parking_area_id', '=', 'pv.parking_area_id')
            ->leftJoin('parking_transactions as t', 't.transaction_id', '=', 'pv.parking_transaction_id')
            ->leftJoin('user_profiles as up', 'up.user_id', '=', 'pv.user_id')
            ->leftJoin('visitors as vis', 'vis.visitor_id', '=', 'pv.visitor_id')
            ->leftJoin('users as rep', 'rep.user_id', '=', 'pv.reported_by')
            ->leftJoin('users as res', 'res.user_id', '=', 'pv.resolved_by')
            ->select([
                'pv.violation_id', 'pv.occurred_at', 'pv.reported_at', 'pv.description', 'pv.violation_status',
                'pv.resolved_at', 'pv.resolution_remarks', 'pv.parking_transaction_id',
                'vt.violation_code', 'vt.violation_name', 'vt.severity',
                'v.plate_number', 'obs.slot_code as observed_slot', 'exp.slot_code as assigned_slot', 'a.area_name as parking_area',
                't.transaction_status',
                'rep.user_code as reported_by', 'res.user_code as resolved_by',
                DB::raw("COALESCE(NULLIF(TRIM(CONCAT_WS(' ', up.first_name, up.last_name)), ''), NULLIF(TRIM(CONCAT_WS(' ', vis.first_name, vis.last_name)), '')) as party_name"),
            ]);

        if (in_array($status = $request->query('status'), ['open', 'under_review', 'resolved', 'dismissed', 'escalated'], true)) {
            $query->where('pv.violation_status', $status);
        }

        if (($search = trim((string) $request->query('search', ''))) !== '') {
            $like = "%{$search}%";
            $query->where(fn ($q) => $q->where('v.plate_number', 'like', $like)
                ->orWhere('vt.violation_name', 'like', $like)
                ->orWhere('obs.slot_code', 'like', $like)
                ->orWhereRaw("CONCAT_WS(' ', up.first_name, up.last_name) like ?", [$like])
                ->orWhereRaw("CONCAT_WS(' ', vis.first_name, vis.last_name) like ?", [$like]));
        }

        $rows = $query->orderByDesc('pv.occurred_at')->limit(300)->get()->map(function ($row) {
            $row->occurred_at = ParkingOperationsController::iso($row->occurred_at);
            $row->reported_at = ParkingOperationsController::iso($row->reported_at);
            $row->resolved_at = ParkingOperationsController::iso($row->resolved_at);
            $row->is_slot_incident = in_array($row->violation_code, self::SLOT_TYPES, true);
            return $row;
        });

        $counts = DB::table('parking_violations')->groupBy('violation_status')->selectRaw('violation_status, COUNT(*) c')->pluck('c', 'violation_status');

        return response()->json([
            'data' => $rows,
            'stats' => [
                'total' => (int) $counts->sum(),
                'open' => (int) ($counts['open'] ?? 0),
                'under_review' => (int) ($counts['under_review'] ?? 0),
                'resolved' => (int) ($counts['resolved'] ?? 0),
                'dismissed' => (int) ($counts['dismissed'] ?? 0),
            ],
        ]);
    }

    /**
     * Record a violation observed by staff. Wrong-slot types go through the
     * slot-observation workflow so they are de-duplicated and alerted the same way.
     */
    public function store(Request $request): JsonResponse
    {
        if ($denied = $this->forbidden($request)) {
            return $denied;
        }

        $data = $request->validate([
            'violation_code' => ['required', Rule::exists('violation_types', 'violation_code')->where('is_active', true)],
            'plate_number' => ['required', 'string', 'max:20'],
            'slot_code' => ['nullable', 'string', 'max:30', Rule::requiredIf(in_array($request->input('violation_code'), self::SLOT_TYPES, true))],
            'description' => ['required', 'string', 'max:2000'],
        ]);

        $plate = strtoupper(trim($data['plate_number']));
        $vehicle = DB::table('vehicles')->where('plate_number', $plate)->whereNull('deleted_at')->first();
        $slot = !empty($data['slot_code'])
            ? DB::table('parking_slots as s')->join('parking_zones as z', 'z.zone_id', '=', 's.zone_id')
                ->where('s.slot_code', strtoupper(trim($data['slot_code'])))->first(['s.slot_id', 'z.parking_area_id'])
            : null;

        if (!empty($data['slot_code']) && !$slot) {
            return response()->json(['message' => 'Unknown slot code.', 'errors' => ['slot_code' => ['Unknown slot code.']]], 422);
        }

        $active = $vehicle
            ? DB::table('parking_transactions')->where('vehicle_id', $vehicle->vehicle_id)->where('transaction_status', 'active')->first()
            : null;

        if (in_array($data['violation_code'], self::SLOT_TYPES, true)) {
            if (!$active) {
                return response()->json(['message' => "{$plate} has no active parking session, so a wrong-slot incident cannot be checked."], 422);
            }

            $result = $this->access->reportObservedSlot($active->transaction_id, $slot->slot_id, $request->user());

            return response()->json($result, $result['status'] === 'confirmed' ? 200 : 201);
        }

        $id = DB::table('parking_violations')->insertGetId([
            'violation_type_id' => DB::table('violation_types')->where('violation_code', $data['violation_code'])->value('violation_type_id'),
            'parking_transaction_id' => $active->transaction_id ?? null,
            'vehicle_id' => $vehicle->vehicle_id ?? null,
            'user_id' => $vehicle->owner_user_id ?? null,
            'visitor_id' => $vehicle && !$vehicle->owner_user_id ? $vehicle->owner_visitor_id : null,
            'slot_id' => $slot->slot_id ?? null,
            'parking_area_id' => $slot->parking_area_id ?? ($active->parking_area_id ?? null),
            'occurred_at' => now(),
            'reported_by' => $request->user()->user_id,
            'reported_at' => now(),
            'description' => $vehicle ? $data['description'] : "[Unregistered plate {$plate}] {$data['description']}",
            'violation_status' => 'open',
            'created_at' => now(),
            'updated_at' => now(),
        ], 'violation_id');

        return response()->json(['message' => "Violation recorded for {$plate}.", 'violation_id' => $id], 201);
    }

    /**
     * acknowledge: open -> under_review
     * resolve:     -> resolved. Wrong-slot incidents on an active session may
     *              also fix the slot: "relocated" (vehicle moved back to its
     *              assigned slot) or "reassigned" (assignment moved to the
     *              observed slot, if it is free).
     * dismiss:     -> dismissed (e.g. a mistaken report)
     */
    public function update(Request $request, int $id): JsonResponse
    {
        if ($denied = $this->forbidden($request)) {
            return $denied;
        }

        $data = $request->validate([
            'action' => ['required', Rule::in(['acknowledge', 'resolve', 'dismiss'])],
            'resolution' => ['nullable', Rule::in(['relocated', 'reassigned'])],
            'remarks' => ['nullable', 'string', 'max:255', 'required_if:action,dismiss'],
        ], ['remarks.required_if' => 'Explain why the violation is being dismissed.']);

        $actor = $request->user();

        return DB::transaction(function () use ($id, $data, $actor, $request) {
            $violation = DB::table('parking_violations as pv')
                ->join('violation_types as vt', 'vt.violation_type_id', '=', 'pv.violation_type_id')
                ->where('pv.violation_id', $id)->lockForUpdate()->first(['pv.*', 'vt.violation_code']);

            if (!$violation) {
                return response()->json(['message' => 'Violation not found.'], 404);
            }

            if (in_array($violation->violation_status, ['resolved', 'dismissed'], true)) {
                return response()->json(['message' => "This violation is already {$violation->violation_status}."], 409);
            }

            if ($data['action'] === 'acknowledge') {
                DB::table('parking_violations')->where('violation_id', $id)->update(['violation_status' => 'under_review', 'updated_at' => now()]);
                $this->audit($request, $id, 'violation_acknowledged', $violation->violation_status, 'under_review');

                return response()->json(['message' => 'Incident acknowledged and marked under review.']);
            }

            $remarks = $data['remarks'] ?? null;

            if ($data['action'] === 'resolve' && !empty($data['resolution'])) {
                if (!in_array($violation->violation_code, self::SLOT_TYPES, true)) {
                    return response()->json(['message' => 'Slot resolutions only apply to wrong-slot incidents.'], 422);
                }

                $outcome = $this->applySlotResolution($violation, $data['resolution'], $actor->user_id);

                if (isset($outcome['error'])) {
                    return response()->json(['message' => $outcome['error']], 409);
                }

                $remarks = trim($outcome['note'] . ' ' . ($remarks ?? ''));
            }

            $status = $data['action'] === 'resolve' ? 'resolved' : 'dismissed';

            DB::table('parking_violations')->where('violation_id', $id)->update([
                'violation_status' => $status,
                'resolved_by' => $actor->user_id,
                'resolved_at' => now(),
                'resolution_remarks' => $remarks ? Str::limit($remarks, 250) : null,
                'updated_at' => now(),
            ]);
            $this->audit($request, $id, "violation_{$status}", $violation->violation_status, $status, $remarks);

            return response()->json(['message' => $status === 'resolved' ? 'Violation resolved.' : 'Violation dismissed.']);
        });
    }

    private function applySlotResolution(object $violation, string $resolution, int $actorId): array
    {
        $txn = DB::table('parking_transactions')->where('transaction_id', $violation->parking_transaction_id)->lockForUpdate()->first();

        if (!$txn || $txn->transaction_status !== 'active') {
            return ['error' => 'The parking session has ended; resolve without a slot change.'];
        }

        if ($resolution === 'relocated') {
            DB::table('parking_transactions')->where('transaction_id', $txn->transaction_id)
                ->update(['slot_confirmed_at' => now(), 'slot_confirmed_by' => $actorId, 'updated_at' => now()]);

            return ['note' => 'Vehicle moved back to its assigned slot (confirmed).'];
        }

        $slot = DB::table('parking_slots as s')->join('parking_zones as z', 'z.zone_id', '=', 's.zone_id')
            ->where('s.slot_id', $violation->slot_id)->lockForUpdate()->first(['s.slot_id', 's.slot_code', 's.slot_status', 'z.parking_area_id']);
        $vehicleArea = DB::table('vehicles as v')->join('vehicle_types as vt', 'vt.vehicle_type_id', '=', 'v.vehicle_type_id')
            ->where('v.vehicle_id', $txn->vehicle_id)->value('vt.parking_area_id');

        if (!$slot || $slot->slot_status !== 'available') {
            return ['error' => 'The observed slot is not available for assignment.'];
        }

        if ((int) $slot->parking_area_id !== (int) $vehicleArea) {
            return ['error' => "Slot {$slot->slot_code} is in a parking area this vehicle type may not use. The vehicle must be moved."];
        }

        if (DB::table('parking_transactions')->where('slot_id', $slot->slot_id)->where('transaction_status', 'active')->exists()) {
            return ['error' => "Slot {$slot->slot_code} is assigned to another vehicle."];
        }

        DB::table('parking_transactions')->where('transaction_id', $txn->transaction_id)->update([
            'slot_id' => $slot->slot_id,
            'parking_area_id' => $slot->parking_area_id,
            'slot_confirmed_at' => now(),
            'slot_confirmed_by' => $actorId,
            'updated_at' => now(),
        ]);

        return ['note' => "Assignment moved to observed slot {$slot->slot_code} (confirmed)."];
    }

    private function audit(Request $request, int $id, string $action, string $from, string $to, ?string $remarks = null): void
    {
        DB::table('audit_logs')->insert([
            'user_id' => $request->user()->user_id,
            'actor_label' => $request->user()->user_code,
            'action' => $action,
            'auditable_type' => 'parking_violation',
            'auditable_id' => $id,
            'old_values' => json_encode(['violation_status' => $from]),
            'new_values' => json_encode(['violation_status' => $to, 'remarks' => $remarks]),
            'ip_address' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 252),
            'created_at' => now(),
        ]);
    }
}
