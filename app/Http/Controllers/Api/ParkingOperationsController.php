<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Parking\ParkingAccessService;
use App\Services\Parking\ParkingStats;
use App\Support\CampusTime;
use App\Support\ParkingMapLayout;
use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Parking Operations: entry/exit records, occupancy, slot layout, gate
 * operations and staff slot observations. All figures come from MySQL.
 */
class ParkingOperationsController extends Controller
{
    public function __construct(private ParkingAccessService $access) {}

    private function forbidden(Request $request): ?JsonResponse
    {
        return $request->user()->canManageParking()
            ? null
            : response()->json(['message' => 'Only Parking Staff and administrators can access parking operations.'], 403);
    }

    /** Day-to-day layout mutations belong to Parking Staff; admins monitor only. */
    private function operationForbidden(Request $request): ?JsonResponse
    {
        $user = $request->user();

        return $user->canManageParking() && ! $user->isAdmin()
            ? null
            : response()->json(['message' => 'Only Parking Staff can record gate movements or update parking slots. Administrators have read-only parking access.'], 403);
    }

    /** Database datetimes are stored in the application timezone; send them with an offset. */
    public static function iso(?string $value): ?string
    {
        return $value ? CarbonImmutable::parse($value, config('app.timezone'))->toIso8601String() : null;
    }

    // ----------------------------------------------------------- statistics

    public function overview(Request $request): JsonResponse
    {
        if ($denied = $this->forbidden($request)) {
            return $denied;
        }

        $areas = ParkingStats::areas();

        return response()->json([
            'areas' => array_values($areas),
            'overall' => ParkingStats::overall($areas),
            'hourly' => ParkingStats::hourlyToday(array_keys($areas)),
            'inside' => ParkingStats::inside(),
            'open_incidents' => DB::table('parking_violations as pv')
                ->join('violation_types as vt', 'vt.violation_type_id', '=', 'pv.violation_type_id')
                ->whereIn('vt.violation_code', [ParkingAccessService::WRONG_SLOT, ParkingAccessService::WRONG_AREA])
                ->whereIn('pv.violation_status', ['open', 'under_review'])
                ->count(),
            'generated_at' => now()->toIso8601String(),
        ]);
    }

    /** Availability for every signed-in user (no plate numbers or personal data). */
    public function availability(): JsonResponse
    {
        $areas = array_map(fn ($a) => array_intersect_key($a, array_flip([
            'parking_area_id', 'area_code', 'area_name', 'capacity', 'occupied', 'maintenance', 'available', 'occupancy_percent',
        ])), ParkingStats::areas());

        return response()->json([
            'areas' => array_values($areas),
            'overall' => array_intersect_key(ParkingStats::overall($areas), array_flip(['capacity', 'occupied', 'maintenance', 'available', 'occupancy_percent'])),
            'generated_at' => now()->toIso8601String(),
        ]);
    }

    /** The signed-in user's vehicles that are currently parked, with their slot. */
    public function mySession(Request $request): JsonResponse
    {
        $userId = $request->user()->user_id;

        $rows = $this->transactionQuery()
            ->where('t.transaction_status', 'active')
            ->where(fn ($q) => $q->where('t.user_id', $userId)->orWhere('v.owner_user_id', $userId))
            ->get()
            ->map(fn ($row) => [
                'transaction_id' => $row->transaction_id,
                'plate_number' => $row->plate_number,
                'slot_code' => $row->slot_code,
                'parking_area' => $row->parking_area,
                'entry_datetime' => self::iso($row->entry_datetime),
                'slot_confirmed' => (bool) $row->slot_confirmed_at,
            ]);

        return response()->json(['data' => $rows]);
    }

    // ----------------------------------------------------------- slots

    public function slots(Request $request, int $areaId): JsonResponse
    {
        if ($denied = $this->forbidden($request)) {
            return $denied;
        }

        $area = DB::table('parking_areas')->where('parking_area_id', $areaId)->first(['parking_area_id', 'area_code', 'area_name']);

        if (! $area) {
            return response()->json(['message' => 'Parking area not found.'], 404);
        }

        $data = $this->slotRows($areaId);

        return response()->json([
            'area' => $area,
            'stats' => ParkingStats::area($areaId),
            'columns' => (int) (DB::table('parking_zones')->where('parking_area_id', $areaId)->value('layout_width') ?? 10),
            'data' => $data->values(),
        ]);
    }

    /**
     * Complete live slot diagram. Refreshed parking_slots coordinates place
     * every configured code; measured_layout is a separate static alternative.
     * Both views use the same live slot records and never change capacity.
     */
    public function map(Request $request): JsonResponse
    {
        if ($denied = $this->forbidden($request)) {
            return $denied;
        }

        $scale = ParkingMapLayout::SCALE;
        $slots = $this->slotRows(null);

        // Painted bays that are not PASS slots (duplicates, PWD bay, aisle, beyond capacity).
        $extraBays = array_values(array_map(
            fn ($b) => array_intersect_key($b, array_flip(['area', 'x', 'y', 'w', 'h', 'rotation', 'label', 'kind', 'note'])),
            array_filter(ParkingMapLayout::bays(), fn ($b) => $b['slot_number'] === null)
        ));

        $areas = ParkingStats::areas();

        return response()->json([
            'view_box' => ParkingMapLayout::VIEW_BOX,
            'geometry' => ParkingMapLayout::geometry(),
            'measured_layout' => ParkingMapLayout::measuredLayout(),
            'gate' => DB::table('access_gates')->where('gate_code', 'MAIN-GATE')->first(['gate_id', 'gate_code', 'gate_name', 'gate_status']),
            'slots' => $slots->map(fn ($s) => $s + [
                'placed' => $s['pos_x'] !== null,
                'x' => $s['pos_x'] !== null ? $s['pos_x'] / $scale : null,
                'y' => $s['pos_y'] !== null ? $s['pos_y'] / $scale : null,
                'w' => $s['slot_width'] !== null ? $s['slot_width'] / $scale : null,
                'h' => $s['slot_height'] !== null ? $s['slot_height'] / $scale : null,
            ])->values(),
            'extra_bays' => $extraBays,
            'areas' => array_values($areas),
            'overall' => ParkingStats::overall($areas),
            'inside' => ParkingStats::inside(),
            'ambiguities' => ParkingMapLayout::ambiguities(),
            'corrections' => ParkingMapLayout::corrections(),
            'generated_at' => now()->toIso8601String(),
        ]);
    }

    /** Signed-in availability only: never select identities, credentials or incident records. */
    public function availabilityMap(): JsonResponse
    {
        $scale = ParkingMapLayout::SCALE;
        $held = DB::table('parking_transactions')->where('transaction_status', 'active')
            ->whereNotNull('slot_id')->select('slot_id')->distinct();
        $slots = DB::table('parking_slots as s')
            ->join('parking_zones as z', 'z.zone_id', '=', 's.zone_id')
            ->join('parking_areas as a', 'a.parking_area_id', '=', 'z.parking_area_id')
            ->leftJoinSub($held, 'held', fn ($join) => $join->on('held.slot_id', '=', 's.slot_id'))
            ->where('a.is_active', true)->orderBy('a.area_code')->orderBy('s.slot_code')
            ->get(['s.slot_id', 's.slot_code', 's.slot_status', 's.pos_x', 's.pos_y', 's.slot_width', 's.slot_height', 's.rotation_deg', 's.is_pwd_slot',
                'a.parking_area_id', 'a.area_code', 'a.area_name', 'held.slot_id as held_slot'])
            ->map(fn ($s) => [
                'slot_id' => $s->slot_id, 'slot_code' => $s->slot_code,
                'parking_area_id' => $s->parking_area_id, 'area_code' => $s->area_code, 'area_name' => $s->area_name,
                'slot_status' => $s->slot_status,
                'status' => $s->held_slot ? 'occupied' : (in_array($s->slot_status, ['maintenance', 'disabled'], true) ? 'maintenance' : 'available'),
                'placed' => $s->pos_x !== null && $s->pos_y !== null,
                'x' => $s->pos_x !== null ? $s->pos_x / $scale : null,
                'y' => $s->pos_y !== null ? $s->pos_y / $scale : null,
                'w' => $s->slot_width !== null ? $s->slot_width / $scale : null,
                'h' => $s->slot_height !== null ? $s->slot_height / $scale : null,
                'rotation' => (int) $s->rotation_deg, 'accessible' => (bool) $s->is_pwd_slot,
            ]);
        $areas = ParkingStats::areas();

        return response()->json([
            'view_box' => ParkingMapLayout::VIEW_BOX, 'geometry' => ParkingMapLayout::geometry(),
            'measured_layout' => ParkingMapLayout::measuredLayout(),
            'extra_bays' => array_values(array_filter(ParkingMapLayout::bays(), fn ($b) => $b['slot_number'] === null)),
            'slots' => $slots, 'areas' => array_values($areas), 'overall' => ParkingStats::overall($areas),
            'generated_at' => now()->toIso8601String(),
        ])->header('Cache-Control', 'private, no-store');
    }

    /**
     * Live state of every slot (optionally one area): status, occupant, and
     * any unresolved wrong-slot incident.
     */
    private function slotRows(?int $areaId)
    {
        $slots = DB::table('parking_slots as s')
            ->join('parking_zones as z', 'z.zone_id', '=', 's.zone_id')
            ->join('parking_areas as a', 'a.parking_area_id', '=', 'z.parking_area_id')
            ->leftJoin('parking_transactions as t', fn ($j) => $j->on('t.slot_id', '=', 's.slot_id')->where('t.transaction_status', 'active'))
            ->leftJoin('vehicles as v', 'v.vehicle_id', '=', 't.vehicle_id')
            ->leftJoin('vehicle_types as vt', 'vt.vehicle_type_id', '=', 'v.vehicle_type_id')
            ->leftJoin('user_profiles as up', 'up.user_id', '=', 't.user_id')
            ->leftJoin('visitors as vis', 'vis.visitor_id', '=', 't.visitor_id')
            ->leftJoin('users as u', 'u.user_id', '=', 't.user_id')
            ->where('a.is_active', true)
            ->when($areaId, fn ($q) => $q->where('z.parking_area_id', $areaId))
            ->orderBy('a.area_code')
            ->orderBy('z.zone_code')
            ->orderBy('s.slot_code')
            ->get([
                's.slot_id', 's.slot_code', 's.slot_status', 's.pos_x', 's.pos_y', 's.slot_width', 's.slot_height', 's.rotation_deg', 's.is_pwd_slot',
                'a.parking_area_id', 'a.area_code', 'a.area_name',
                't.transaction_id', 't.entry_datetime', 't.slot_confirmed_at', 't.access_method', 'v.plate_number', 'vt.type_name as vehicle_type', 'u.user_code as owner_code',
                DB::raw("CASE WHEN t.visitor_id IS NOT NULL THEN 'visitor' WHEN t.user_id IS NOT NULL THEN 'user' END as party_type"),
                DB::raw("COALESCE(NULLIF(TRIM(CONCAT_WS(' ', up.first_name, up.last_name)), ''), NULLIF(TRIM(CONCAT_WS(' ', vis.first_name, vis.last_name)), '')) as party_name"),
            ]);

        // Unresolved wrong-slot incidents (vehicle observed here, or assigned here)
        // for vehicles still parked. Incidents whose vehicle has exited stay open in
        // Violations but no longer mark a slot on the map.
        $incidents = DB::table('parking_violations as pv')
            ->join('violation_types as vt', 'vt.violation_type_id', '=', 'pv.violation_type_id')
            ->join('parking_transactions as it', fn ($j) => $j->on('it.transaction_id', '=', 'pv.parking_transaction_id')->where('it.transaction_status', 'active'))
            ->leftJoin('vehicles as v', 'v.vehicle_id', '=', 'pv.vehicle_id')
            ->leftJoin('parking_slots as obs', 'obs.slot_id', '=', 'pv.slot_id')
            ->leftJoin('parking_slots as exp', 'exp.slot_id', '=', 'pv.expected_slot_id')
            ->whereIn('vt.violation_code', [ParkingAccessService::WRONG_SLOT, ParkingAccessService::WRONG_AREA])
            ->whereIn('pv.violation_status', ['open', 'under_review'])
            ->get(['pv.violation_id', 'pv.slot_id', 'pv.expected_slot_id', 'pv.violation_status', 'v.plate_number', 'obs.slot_code as observed_slot', 'exp.slot_code as assigned_slot']);

        return $slots->map(function ($s) use ($incidents) {
            // A parked vehicle makes the slot occupied even if it is also flagged for
            // maintenance, so the three statuses always add up to capacity.
            $status = $s->transaction_id ? 'occupied' : (in_array($s->slot_status, ['maintenance', 'disabled'], true) ? 'maintenance' : 'available');
            $incident = $incidents->first(fn ($i) => (int) $i->slot_id === (int) $s->slot_id || (int) $i->expected_slot_id === (int) $s->slot_id);

            return [
                'slot_id' => $s->slot_id,
                'slot_code' => $s->slot_code,
                'parking_area_id' => $s->parking_area_id,
                'area_code' => $s->area_code,
                'area_name' => $s->area_name,
                'slot_status' => $s->slot_status,
                'status' => $status,
                'pos_x' => $s->pos_x,
                'pos_y' => $s->pos_y,
                'slot_width' => $s->slot_width,
                'slot_height' => $s->slot_height,
                'rotation' => (int) $s->rotation_deg,
                'accessible' => (bool) $s->is_pwd_slot,
                'occupant' => $s->transaction_id ? [
                    'transaction_id' => $s->transaction_id,
                    'plate_number' => $s->plate_number,
                    'vehicle_type' => $s->vehicle_type,
                    'party_type' => $s->party_type,
                    'party_name' => $s->party_name,
                    'owner_code' => $s->owner_code,
                    'access_method' => $s->access_method,
                    'entry_datetime' => self::iso($s->entry_datetime),
                    'confirmed' => (bool) $s->slot_confirmed_at,
                    'confirmed_at' => self::iso($s->slot_confirmed_at),
                ] : null,
                'incident' => $incident ? [
                    'violation_id' => $incident->violation_id,
                    'plate_number' => $incident->plate_number,
                    'assigned_slot' => $incident->assigned_slot,
                    'observed_slot' => $incident->observed_slot,
                    'violation_status' => $incident->violation_status,
                    'role' => (int) $incident->slot_id === (int) $s->slot_id ? 'observed_here' : 'assigned_here',
                ] : null,
            ];
        });
    }

    /** Mark a slot as under maintenance or return it to service. */
    public function updateSlot(Request $request, int $slotId): JsonResponse
    {
        if ($denied = $this->operationForbidden($request)) {
            return $denied;
        }

        $data = $request->validate([
            'slot_status' => ['required', Rule::in(['available', 'maintenance'])],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        return DB::transaction(function () use ($slotId, $data, $request) {
            $slot = DB::table('parking_slots')->where('slot_id', $slotId)->lockForUpdate()->first();

            if (! $slot) {
                return response()->json(['message' => 'Parking slot not found.'], 404);
            }

            $occupiedBy = DB::table('parking_transactions as t')->join('vehicles as v', 'v.vehicle_id', '=', 't.vehicle_id')
                ->where('t.slot_id', $slotId)->where('t.transaction_status', 'active')->value('v.plate_number');

            if ($data['slot_status'] === 'maintenance' && $occupiedBy) {
                return response()->json([
                    'message' => "Slot {$slot->slot_code} is assigned to {$occupiedBy}. Record the vehicle's exit before marking it for maintenance.",
                ], 409);
            }

            DB::table('parking_slots')->where('slot_id', $slotId)->update(['slot_status' => $data['slot_status'], 'updated_at' => now()]);

            DB::table('audit_logs')->insert([
                'user_id' => $request->user()->user_id,
                'actor_label' => $request->user()->user_code,
                'action' => $data['slot_status'] === 'maintenance' ? 'parking_slot_maintenance' : 'parking_slot_available',
                'auditable_type' => 'parking_slot',
                'auditable_id' => $slotId,
                'old_values' => json_encode(['slot_status' => $slot->slot_status]),
                'new_values' => json_encode(['slot_status' => $data['slot_status'], 'reason' => $data['reason'] ?? null]),
                'ip_address' => $request->ip(),
                'user_agent' => Str::limit((string) $request->userAgent(), 252),
                'created_at' => now(),
            ]);

            return response()->json([
                'message' => $data['slot_status'] === 'maintenance'
                    ? "Slot {$slot->slot_code} is now under maintenance and will not be assigned."
                    : "Slot {$slot->slot_code} is available again.",
            ]);
        });
    }

    // ----------------------------------------------------------- transactions

    public function transactions(Request $request): JsonResponse
    {
        if ($denied = $this->forbidden($request)) {
            return $denied;
        }

        $query = $this->transactionQuery();

        if ($area = $request->query('area')) {
            $query->where('t.parking_area_id', $area);
        }

        if (in_array($method = $request->query('method'), ['rfid', 'qr', 'manual'], true)) {
            $query->where('t.access_method', $method);
        }

        if ($category = $request->query('category')) {
            $category === 'visitor'
                ? $query->whereNotNull('t.visitor_id')
                : $query->where('u.user_type', $category);
        }

        if (in_array($status = $request->query('status'), ['active', 'completed', 'force_closed', 'cancelled'], true)) {
            $query->where('t.transaction_status', $status);
        }

        if ($request->query('date') === 'today') {
            // The campus day (Asia/Manila), expressed in stored UTC time.
            [$start] = CampusTime::dayRange();
            $query->where(fn ($q) => $q->where('t.entry_datetime', '>=', $start)->orWhere('t.exit_datetime', '>=', $start));
        }

        if (($search = trim((string) $request->query('search', ''))) !== '') {
            $query->where(function ($q) use ($search) {
                $like = "%{$search}%";
                $q->where('v.plate_number', 'like', $like)
                    ->orWhere('t.ticket_number', 'like', $like)
                    ->orWhere('s.slot_code', 'like', $like)
                    ->orWhere('u.user_code', 'like', $like)
                    ->orWhereRaw("CONCAT_WS(' ', d.first_name, d.last_name) like ?", [$like])
                    ->orWhereRaw("CONCAT_WS(' ', up.first_name, up.last_name) like ?", [$like])
                    ->orWhereRaw("CONCAT_WS(' ', vis.first_name, vis.last_name) like ?", [$like]);
            });
        }

        $now = CarbonImmutable::now();

        $rows = $query->orderByDesc('t.entry_datetime')->limit(300)->get()->map(function ($row) use ($now) {
            $minutes = $row->duration_minutes !== null
                ? (int) $row->duration_minutes
                : (int) CarbonImmutable::parse($row->entry_datetime)->diffInMinutes($now);

            return [
                'transaction_id' => $row->transaction_id,
                'ticket_number' => $row->ticket_number,
                'plate_number' => $row->plate_number,
                'vehicle_type' => $row->vehicle_type,
                'party_type' => $row->party_type,
                'party_name' => $row->party_name,
                'owner_code' => $row->owner_code,
                // Student / faculty / employee for campus users, visitor for guests.
                'user_category' => $row->party_type === 'visitor' ? 'visitor' : $row->user_type,
                'slot_id' => $row->slot_id,
                'slot_code' => $row->slot_code,
                'parking_area_id' => $row->parking_area_id,
                'parking_area' => $row->parking_area,
                'entry_datetime' => self::iso($row->entry_datetime),
                'exit_datetime' => self::iso($row->exit_datetime),
                'duration_minutes' => $minutes,
                'duration' => ParkingAccessService::formatDuration($minutes),
                'access_method' => $row->access_method,
                'transaction_status' => $row->transaction_status,
                'slot_confirmed' => (bool) $row->slot_confirmed_at,
                'open_incident_id' => $row->open_incident_id,
            ];
        });

        return response()->json(['data' => $rows]);
    }

    private function transactionQuery(): Builder
    {
        return DB::table('parking_transactions as t')
            ->join('vehicles as v', 'v.vehicle_id', '=', 't.vehicle_id')
            ->leftJoin('vehicle_types as vt', 'vt.vehicle_type_id', '=', 'v.vehicle_type_id')
            ->leftJoin('parking_slots as s', 's.slot_id', '=', 't.slot_id')
            ->leftJoin('parking_areas as a', 'a.parking_area_id', '=', 't.parking_area_id')
            ->leftJoin('drivers as d', 'd.driver_id', '=', 't.driver_id')
            ->leftJoin('user_profiles as up', 'up.user_id', '=', 't.user_id')
            ->leftJoin('users as u', 'u.user_id', '=', 't.user_id')
            ->leftJoin('visitors as vis', 'vis.visitor_id', '=', 't.visitor_id')
            ->select([
                't.transaction_id', 't.ticket_number', 't.slot_id', 't.parking_area_id', 't.entry_datetime', 't.exit_datetime',
                't.duration_minutes', 't.access_method', 't.transaction_status', 't.slot_confirmed_at',
                'v.plate_number', 'vt.type_name as vehicle_type', 's.slot_code', 'a.area_name as parking_area', 'u.user_code as owner_code', 'u.user_type',
                DB::raw("CASE WHEN t.visitor_id IS NOT NULL THEN 'visitor' ELSE 'user' END as party_type"),
                DB::raw("COALESCE(NULLIF(TRIM(CONCAT_WS(' ', d.first_name, d.last_name)), ''), NULLIF(TRIM(CONCAT_WS(' ', up.first_name, up.last_name)), ''), NULLIF(TRIM(CONCAT_WS(' ', vis.first_name, vis.last_name)), '')) as party_name"),
                DB::raw("(SELECT pv.violation_id FROM parking_violations pv WHERE pv.parking_transaction_id = t.transaction_id AND pv.violation_status IN ('open', 'under_review') ORDER BY pv.violation_id DESC LIMIT 1) as open_incident_id"),
            ]);
    }

    // ----------------------------------------------------------- gate operations

    public function entry(Request $request): JsonResponse
    {
        return $this->gate($request, 'in');
    }

    public function exit(Request $request): JsonResponse
    {
        return $this->gate($request, 'out');
    }

    /**
     * Staff-operated gate: identify the vehicle by RFID tag, QR pass code, or
     * plate number. Every decided outcome (granted, denied, already inside)
     * returns 200 with a status so the gate screen can display it.
     */
    private function gate(Request $request, string $direction): JsonResponse
    {
        if ($denied = $this->operationForbidden($request)) {
            return $denied;
        }

        $data = $request->validate([
            'rfid_tag_uid' => ['nullable', 'string', 'max:50', 'required_without_all:qr_pass_code,plate_number'],
            'qr_pass_code' => ['nullable', 'string', 'max:64'],
            'plate_number' => ['nullable', 'string', 'max:20'],
            'gate_id' => ['nullable', 'integer', 'exists:access_gates,gate_id'],
        ], [
            'rfid_tag_uid.required_without_all' => 'Scan an RFID tag, enter a QR pass code, or type the plate number.',
        ]);

        $credential = array_filter(array_intersect_key($data, array_flip(['rfid_tag_uid', 'qr_pass_code', 'plate_number'])));
        $context = array_filter(['gate_id' => $data['gate_id'] ?? null, 'source' => 'staff_gate']);

        $result = $direction === 'in'
            ? $this->access->enter($credential, $request->user(), $context)
            : $this->access->exit($credential, $request->user(), $context);

        return response()->json($result);
    }

    /**
     * Staff report where an active vehicle is actually parked.
     */
    public function observedSlot(Request $request, int $transactionId): JsonResponse
    {
        if ($denied = $this->operationForbidden($request)) {
            return $denied;
        }

        $data = $request->validate([
            'slot_id' => ['nullable', 'integer', 'required_without:slot_code'],
            'slot_code' => ['nullable', 'string', 'max:30'],
        ]);

        $slotId = $data['slot_id'] ?? DB::table('parking_slots')->where('slot_code', strtoupper(trim($data['slot_code'])))->value('slot_id');

        if (! $slotId) {
            return response()->json(['message' => 'Slot '.($data['slot_code'] ?? '').' does not exist.', 'errors' => ['slot_code' => ['Unknown slot code.']]], 422);
        }

        $result = $this->access->reportObservedSlot($transactionId, (int) $slotId, $request->user());

        $code = match ($result['status']) {
            'not_found' => 404,
            'not_active' => 409,
            'error' => 500,
            default => 200,
        };

        return response()->json($result, $code);
    }
}
