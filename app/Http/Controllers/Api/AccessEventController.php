<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\CampusTime;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Read-only gate access records (access_events) and RFID credentials.
 * Entries and exits are written only by ParkingAccessService.
 */
class AccessEventController extends Controller
{
    private function forbidden(Request $request): ?JsonResponse
    {
        return $request->user()->canManageParking()
            ? null
            : response()->json(['message' => 'Only Parking Staff and administrators can view access records.'], 403);
    }

    /**
     * Gate access events, newest first. Filters: method (rfid|qr|manual),
     * direction (in|out), result (granted|denied), date (today = campus day),
     * search (plate, name, user code, RFID tag, denial reason).
     */
    public function index(Request $request): JsonResponse
    {
        if ($denied = $this->forbidden($request)) {
            return $denied;
        }

        $filters = $request->validate([
            'method' => ['nullable', Rule::in(['rfid', 'qr', 'manual'])],
            'direction' => ['nullable', Rule::in(['in', 'out'])],
            'result' => ['nullable', Rule::in(['granted', 'denied'])],
            'date' => ['nullable', Rule::in(['today'])],
            'search' => ['nullable', 'string', 'max:100'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:500'],
        ]);

        [$dayStart, $dayEnd] = CampusTime::dayRange();

        $query = DB::table('access_events as e')
            ->leftJoin('access_gates as g', 'g.gate_id', '=', 'e.gate_id')
            ->leftJoin('vehicles as v', 'v.vehicle_id', '=', 'e.vehicle_id')
            ->leftJoin('vehicle_types as vt', 'vt.vehicle_type_id', '=', 'v.vehicle_type_id')
            ->leftJoin('users as u', 'u.user_id', '=', 'e.user_id')
            ->leftJoin('user_profiles as up', 'up.user_id', '=', 'e.user_id')
            ->leftJoin('visitors as vis', 'vis.visitor_id', '=', 'e.visitor_id')
            ->leftJoin('rfid_tags as t', 't.rfid_id', '=', 'e.rfid_id')
            ->leftJoin('rfid_assignments as ra', 'ra.assignment_id', '=', 'e.rfid_assignment_id')
            ->leftJoin('users as holder', 'holder.user_id', '=', 'ra.user_id')
            ->leftJoin('qr_passes as q', 'q.qr_pass_id', '=', 'e.qr_pass_id')
            ->leftJoin('users as staff', 'staff.user_id', '=', 'e.processed_by_user_id')
            ->when($filters['method'] ?? null, fn ($q, $m) => $q->where('e.access_method', $m))
            ->when($filters['direction'] ?? null, fn ($q, $d) => $q->where('e.direction', $d))
            ->when($filters['result'] ?? null, fn ($q, $r) => $q->where('e.auth_result', $r))
            ->when(($filters['date'] ?? null) === 'today', fn ($q) => $q->where('e.event_datetime', '>=', $dayStart)->where('e.event_datetime', '<', $dayEnd))
            ->when(trim((string) ($filters['search'] ?? '')) !== '', function ($q) use ($filters) {
                $like = '%' . trim($filters['search']) . '%';
                $q->where(fn ($w) => $w->where('v.plate_number', 'like', $like)
                    ->orWhere('u.user_code', 'like', $like)
                    ->orWhere('t.tag_uid', 'like', $like)
                    ->orWhere('e.denial_reason', 'like', $like)
                    ->orWhereRaw("CONCAT_WS(' ', up.first_name, up.last_name) like ?", [$like])
                    ->orWhereRaw("CONCAT_WS(' ', vis.first_name, vis.last_name) like ?", [$like]));
            });

        $rows = $query->orderByDesc('e.event_datetime')->orderByDesc('e.event_id')
            ->limit($filters['limit'] ?? 300)
            ->get([
                'e.event_id', 'e.event_datetime', 'e.direction', 'e.access_method', 'e.auth_result', 'e.denial_reason', 'e.raw_payload',
                'g.gate_name', 'v.plate_number', 'vt.type_name as vehicle_type', 'u.user_code', 'u.user_type',
                DB::raw("NULLIF(TRIM(CONCAT_WS(' ', up.first_name, up.last_name)), '') as user_name"),
                DB::raw("NULLIF(TRIM(CONCAT_WS(' ', vis.first_name, vis.last_name)), '') as visitor_name"),
                'e.visitor_id', 't.tag_uid', 'e.rfid_assignment_id', 'ra.assignment_status', 'holder.user_code as holder_code',
                'q.pass_code', 'staff.user_code as processed_by',
                DB::raw('(SELECT transaction_id FROM parking_transactions pt WHERE pt.entry_event_id = e.event_id OR pt.exit_event_id = e.event_id LIMIT 1) as transaction_id'),
            ])
            ->map(function ($r) {
                $payload = json_decode($r->raw_payload ?? '{}', true) ?: [];

                return [
                    'event_id' => $r->event_id,
                    'event_datetime' => ParkingOperationsController::iso($r->event_datetime),
                    'direction' => $r->direction,
                    'access_method' => $r->access_method,
                    'auth_result' => $r->auth_result,
                    'denial_reason' => $r->denial_reason,
                    'gate_name' => $r->gate_name,
                    'plate_number' => $r->plate_number,
                    'vehicle_type' => $r->vehicle_type,
                    'party_type' => $r->visitor_id ? 'visitor' : ($r->user_code ? 'user' : null),
                    'party_name' => $r->user_name ?? $r->visitor_name,
                    'user_code' => $r->user_code,
                    'user_type' => $r->user_type,
                    'rfid_tag' => $r->tag_uid,
                    'rfid_assignment_id' => $r->rfid_assignment_id,
                    'rfid_assignment_status' => $r->assignment_status,
                    'rfid_holder_code' => $r->holder_code,
                    'qr_pass_code' => $r->pass_code,
                    // Unidentified credentials keep what was presented (e.g. an unknown tag).
                    'presented_credential' => $payload['credential'] ?? null,
                    'staff_override' => (bool) ($payload['staff_override'] ?? false),
                    'source' => $payload['source'] ?? null,
                    'processed_by' => $r->processed_by,
                    'transaction_id' => $r->transaction_id,
                ];
            });

        $today = DB::table('access_events')->where('event_datetime', '>=', $dayStart)->where('event_datetime', '<', $dayEnd)
            ->selectRaw("SUM(direction = 'in' AND auth_result = 'granted') as entries, SUM(direction = 'out' AND auth_result = 'granted') as exits,
                SUM(auth_result = 'denied') as denied, SUM(access_method = 'rfid') as rfid, SUM(access_method = 'qr') as qr, SUM(access_method = 'manual') as staff_override")
            ->first();

        return response()->json([
            'data' => $rows,
            'today' => array_map('intval', (array) $today),
            'campus_date' => CampusTime::date(),
        ]);
    }

    /** RFID tags with their current (or most recent) assignment. */
    public function rfidAssignments(Request $request): JsonResponse
    {
        if ($denied = $this->forbidden($request)) {
            return $denied;
        }

        $rows = DB::table('rfid_tags as t')
            ->leftJoin('rfid_assignments as a', fn ($j) => $j->on('a.rfid_id', '=', 't.rfid_id')
                ->whereRaw('a.assignment_id = (SELECT MAX(x.assignment_id) FROM rfid_assignments x WHERE x.rfid_id = t.rfid_id)'))
            ->leftJoin('users as u', 'u.user_id', '=', 'a.user_id')
            ->leftJoin('user_profiles as up', 'up.user_id', '=', 'a.user_id')
            ->leftJoin('vehicles as v', 'v.vehicle_id', '=', 'a.vehicle_id')
            ->orderBy('t.tag_uid')
            ->get([
                't.rfid_id', 't.tag_uid', 't.tag_type', 't.tag_status', 'a.assignment_id', 'a.assignment_status', 'a.assigned_at', 'a.valid_until',
                'u.user_code', 'u.user_type', DB::raw("NULLIF(TRIM(CONCAT_WS(' ', up.first_name, up.last_name)), '') as user_name"), 'v.plate_number',
                DB::raw('(SELECT MAX(event_datetime) FROM access_events e WHERE e.rfid_id = t.rfid_id) as last_used'),
            ])
            ->map(fn ($r) => [
                'rfid_id' => $r->rfid_id,
                'tag_uid' => $r->tag_uid,
                'tag_type' => $r->tag_type,
                'tag_status' => $r->tag_status,
                'assignment_id' => $r->assignment_id,
                'assignment_status' => $r->assignment_status,
                'assigned_at' => ParkingOperationsController::iso($r->assigned_at),
                'valid_until' => $r->valid_until,
                'expired' => $r->valid_until && $r->valid_until < CampusTime::date(),
                'user_code' => $r->user_code,
                'user_type' => $r->user_type,
                'user_name' => $r->user_name,
                'plate_number' => $r->plate_number,
                'last_used' => ParkingOperationsController::iso($r->last_used),
            ]);

        return response()->json(['data' => $rows]);
    }
}
