<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\CampusTime;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** A regular user's own records. No campus-wide figures or other owners' activity. */
class UserDashboardController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user->account_status === 'active' && in_array($user->user_type, ['student', 'faculty', 'employee'], true)
            && !$user->isGuest() && !$user->canManageParking(), 403);
        $userId = (int) $user->user_id;
        $today = CampusTime::date();
        [$start, $end] = CampusTime::dayRange();

        $vehicles = DB::table('vehicles as v')
            ->leftJoin('vehicle_types as vt', 'vt.vehicle_type_id', '=', 'v.vehicle_type_id')
            ->leftJoin('vehicle_authorizations as va', function ($join) {
                $join->on('va.vehicle_id', '=', 'v.vehicle_id')
                    ->whereRaw('va.authorization_id = (SELECT MAX(latest.authorization_id) FROM vehicle_authorizations latest WHERE latest.vehicle_id = v.vehicle_id)');
            })
            ->where('v.owner_user_id', $userId)->whereNull('v.deleted_at')
            ->orderByDesc('v.vehicle_id')
            ->get(['v.vehicle_id', 'v.plate_number', 'v.make', 'v.model', 'v.year_model', 'v.registration_status',
                'vt.type_name as vehicle_type', 'va.authorization_status', 'va.valid_from', 'va.valid_until'])
            ->map(function ($row) use ($today) {
                if ($row->authorization_status === 'approved' && $row->valid_until && $row->valid_until < $today) $row->authorization_status = 'expired';
                elseif ($row->authorization_status === 'approved' && $row->valid_from && $row->valid_from > $today) $row->authorization_status = 'scheduled';
                $row->authorization_status ??= 'pending';
                return $row;
            });
        $selectedId = DB::table('user_vehicle_selections')->where('user_id', $userId)->value('vehicle_id');
        $selected = $vehicles->first(fn ($row) => (int) $row->vehicle_id === (int) $selectedId);
        $selectedVehicle = $selected ? (array) $selected : null;
        if ($selectedVehicle) {
            $selectedVehicle['rfid_assigned'] = DB::table('rfid_assignments as a')
                ->join('rfid_tags as tag', 'tag.rfid_id', '=', 'a.rfid_id')
                ->where('a.vehicle_id', $selected->vehicle_id)->where('a.user_id', $userId)
                ->where('a.assignment_status', 'active')->whereNull('a.unassigned_at')->where('tag.tag_status', 'assigned')
                ->where(fn ($q) => $q->whereNull('a.valid_until')->orWhere('a.valid_until', '>=', $today))->exists();
        }

        $license = DB::table('driver_licenses as dl')->join('drivers as d', 'd.driver_id', '=', 'dl.driver_id')
            ->where('d.user_id', $userId)->orderByDesc('dl.license_id')
            ->first(['dl.verification_status', 'dl.expiry_date', 'dl.license_status', 'd.driver_status']);
        if ($license) $license->expired = !$license->expiry_date || $license->expiry_date < $today;

        // A historical session belongs to the user recorded at entry, even if vehicle ownership later changes.
        $ownTransactions = DB::table('parking_transactions')->where('user_id', $userId);
        $totals = (clone $ownTransactions)->selectRaw(
            "COUNT(CASE WHEN entry_datetime >= ? AND entry_datetime < ? AND transaction_status <> 'cancelled' THEN 1 END) AS entries,
             COUNT(CASE WHEN exit_datetime >= ? AND exit_datetime < ? AND transaction_status IN ('completed','force_closed') THEN 1 END) AS exits,
             COUNT(CASE WHEN transaction_status <> 'cancelled' THEN 1 END) AS total_sessions",
            [$start, $end, $start, $end]
        )->first();
        $sessions = DB::table('parking_transactions as t')
            ->leftJoin('vehicles as v', 'v.vehicle_id', '=', 't.vehicle_id')
            ->leftJoin('parking_slots as s', 's.slot_id', '=', 't.slot_id')
            ->leftJoin('parking_areas as a', 'a.parking_area_id', '=', 't.parking_area_id')
            ->where('t.user_id', $userId);
        $columns = ['t.transaction_id', 't.ticket_number', 't.entry_datetime', 't.exit_datetime', 't.duration_minutes',
            't.transaction_status as status', 't.access_method', 't.slot_confirmed_at', 'v.plate_number', 's.slot_code', 'a.area_name as parking_area'];
        $present = fn ($row) => [
            'transaction_id' => $row->transaction_id, 'ticket_number' => $row->ticket_number, 'plate_number' => $row->plate_number,
            'slot_code' => $row->slot_code, 'parking_area' => $row->parking_area, 'status' => $row->status, 'access_method' => $row->access_method,
            'entry_datetime' => ParkingOperationsController::iso($row->entry_datetime), 'exit_datetime' => ParkingOperationsController::iso($row->exit_datetime),
            'duration_minutes' => $row->duration_minutes === null ? null : (int) $row->duration_minutes,
            'slot_confirmed' => (bool) $row->slot_confirmed_at,
        ];
        $active = (clone $sessions)->where('t.transaction_status', 'active')->orderByDesc('t.entry_datetime')->orderByDesc('t.transaction_id')->get($columns)->map($present)->values();
        $recent = (clone $sessions)->orderByDesc('t.entry_datetime')->orderByDesc('t.transaction_id')->limit(5)->get($columns)->map($present)->values();
        $notifications = DB::table('notifications')->where('user_id', $userId)->where('channel', 'in_app');
        $unread = (clone $notifications)->where('is_read', false)->count();
        $updates = (clone $notifications)->orderByDesc('created_at')->orderByDesc('notification_id')->limit(3)
            ->get(['notification_id', 'title', 'body', 'is_read', 'created_at'])->map(function ($row) {
                $row->is_read = (bool) $row->is_read;
                $row->created_at = ParkingOperationsController::iso($row->created_at);
                return $row;
            });
        $pendingReleases = DB::table('vehicle_release_requests as r')
            ->join('parking_transactions as t', 't.transaction_id', '=', 'r.transaction_id')
            ->where('r.owner_user_id', $userId)->where('t.user_id', $userId)
            ->where('r.status', 'pending')->where('t.transaction_status', 'active')->where('r.expires_at', '>', now())->count();

        return response()->json([
            'summary' => ['today_entries' => (int) $totals->entries, 'today_exits' => (int) $totals->exits,
                'total_sessions' => (int) $totals->total_sessions, 'vehicles' => $vehicles->count(),
                'pending_vehicles' => $vehicles->where('authorization_status', 'pending')->count(),
                'rejected_vehicles' => $vehicles->where('authorization_status', 'rejected')->count(),
                'pending_releases' => $pendingReleases, 'unread_notifications' => $unread],
            'selected_vehicle' => $selectedVehicle, 'license' => $license,
            'active_sessions' => $active, 'recent_sessions' => $recent, 'notifications' => $updates,
            'campus_date' => $today, 'generated_at' => now()->toIso8601String(),
        ])->header('Cache-Control', 'private, no-store');
    }
}
