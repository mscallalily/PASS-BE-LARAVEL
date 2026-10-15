<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Parking\ParkingAccessService;
use App\Services\Parking\ParkingStats;
use App\Support\CampusTime;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Parking Staff Dashboard. Every figure comes from the same records as
 * Parking Operations: parking_transactions (entries, exits, vehicles inside),
 * access_events (denied attempts, recent activity), and ParkingStats
 * (occupancy, shared with the Occupancy Monitor and Parking Layout).
 */
class ParkingDashboardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        if (!$request->user()->canManageParking()) {
            return response()->json(['message' => 'Unauthorized access.'], 403);
        }

        // "Today" is the campus day (Asia/Manila); stored times are UTC.
        [$start, $end] = CampusTime::dayRange();

        // Successful entries: every granted entry creates one parking transaction.
        $entries = DB::table('parking_transactions as t')
            ->leftJoin('users as u', 'u.user_id', '=', 't.user_id')
            ->where('t.entry_datetime', '>=', $start)->where('t.entry_datetime', '<', $end)
            ->where('t.transaction_status', '<>', 'cancelled')
            ->selectRaw("COUNT(*) as total,
                SUM(t.access_method = 'rfid') as rfid, SUM(t.access_method = 'qr') as qr, SUM(t.access_method = 'manual') as staff_override,
                SUM(u.user_type = 'student') as student, SUM(u.user_type = 'faculty') as faculty, SUM(u.user_type = 'employee') as employee,
                SUM(t.visitor_id IS NOT NULL) as visitor")
            ->first();

        $exits = DB::table('parking_transactions')
            ->where('exit_datetime', '>=', $start)->where('exit_datetime', '<', $end)
            ->whereIn('transaction_status', ['completed', 'force_closed'])
            ->count();

        $denied = DB::table('access_events')
            ->where('event_datetime', '>=', $start)->where('event_datetime', '<', $end)
            ->where('auth_result', 'denied')
            ->count();

        $areas = ParkingStats::areas();
        $byCode = collect($areas)->keyBy('area_code');
        $inside = ParkingStats::inside();

        return response()->json([
            // Same keys as before, so existing screens keep working.
            'today_entries' => (int) $entries->total,
            'today_exits' => $exits,
            'denied_access' => $denied,
            'active_visitors' => $inside['visitors'],

            'today' => [
                'entries' => (int) $entries->total,
                'exits' => $exits,
                'denied' => $denied,
                'by_method' => ['rfid' => (int) $entries->rfid, 'qr' => (int) $entries->qr, 'manual' => (int) $entries->staff_override],
                'by_category' => [
                    'student' => (int) $entries->student, 'faculty' => (int) $entries->faculty,
                    'employee' => (int) $entries->employee, 'visitor' => (int) $entries->visitor,
                ],
            ],
            'inside' => $inside,
            'occupancy' => [
                'car' => $byCode->get('CAR'),
                'motorcycle' => $byCode->get('MC'),
                'overall' => ParkingStats::overall($areas),
            ],
            'recent_activity' => $this->recentActivity(),
            'campus_date' => CampusTime::date(),
            'generated_at' => now()->toIso8601String(),
        ]);
    }

    /** Latest gate events (granted and denied) and wrong-slot incidents, newest first. */
    private function recentActivity(int $limit = 12): array
    {
        $events = DB::table('access_events as e')
            ->leftJoin('vehicles as v', 'v.vehicle_id', '=', 'e.vehicle_id')
            ->leftJoin('users as u', 'u.user_id', '=', 'e.user_id')
            ->orderByDesc('e.event_datetime')->orderByDesc('e.event_id')
            ->limit($limit)
            ->get([
                'e.event_id', 'e.event_datetime', 'e.direction', 'e.access_method', 'e.auth_result', 'e.denial_reason', 'e.raw_payload', 'e.visitor_id',
                'v.plate_number', 'u.user_type',
                DB::raw('(SELECT s.slot_code FROM parking_transactions pt JOIN parking_slots s ON s.slot_id = pt.slot_id WHERE pt.entry_event_id = e.event_id OR pt.exit_event_id = e.event_id LIMIT 1) as slot_code'),
            ])
            ->map(function ($e) {
                $payload = json_decode($e->raw_payload ?? '{}', true) ?: [];

                return [
                    'kind' => 'access',
                    'id' => 'event-' . $e->event_id,
                    'at' => ParkingOperationsController::iso($e->event_datetime),
                    'direction' => $e->direction,
                    'access_method' => $e->access_method,
                    'result' => $e->auth_result,
                    'reason' => $e->denial_reason,
                    'plate_number' => $e->plate_number ?? ($payload['credential'] ?? null),
                    'category' => $e->visitor_id ? 'visitor' : $e->user_type,
                    'slot_code' => $e->slot_code,
                    'staff_override' => (bool) ($payload['staff_override'] ?? false),
                    'source' => $payload['source'] ?? null,
                ];
            });

        $incidents = DB::table('parking_violations as pv')
            ->join('violation_types as vt', 'vt.violation_type_id', '=', 'pv.violation_type_id')
            ->leftJoin('vehicles as v', 'v.vehicle_id', '=', 'pv.vehicle_id')
            ->leftJoin('parking_slots as obs', 'obs.slot_id', '=', 'pv.slot_id')
            ->leftJoin('parking_slots as exp', 'exp.slot_id', '=', 'pv.expected_slot_id')
            ->whereIn('vt.violation_code', [ParkingAccessService::WRONG_SLOT, ParkingAccessService::WRONG_AREA])
            ->orderByDesc('pv.reported_at')
            ->limit(5)
            ->get(['pv.violation_id', 'pv.reported_at', 'pv.violation_status', 'vt.violation_name', 'v.plate_number', 'obs.slot_code as observed', 'exp.slot_code as assigned'])
            ->map(fn ($i) => [
                'kind' => 'incident',
                'id' => 'violation-' . $i->violation_id,
                'at' => ParkingOperationsController::iso($i->reported_at),
                'result' => $i->violation_status,
                'reason' => "{$i->violation_name}: assigned " . ($i->assigned ?? '—') . ', found at ' . ($i->observed ?? '—'),
                'plate_number' => $i->plate_number,
                'slot_code' => $i->observed,
            ]);

        return $events->concat($incidents)->sortByDesc('at')->take($limit)->values()->all();
    }
}
