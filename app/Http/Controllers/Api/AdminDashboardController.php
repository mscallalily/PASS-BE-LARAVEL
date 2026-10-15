<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminDashboardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $actor = $request->user();

        $isAdmin = $actor
            && $actor->account_status === 'active'
            && $actor->roles()
                ->whereIn('role_code', ['admin', 'parking_admin'])
                ->where('is_active', true)
                ->exists();

        if (! $isAdmin) {
            return response()->json([
                'message' => 'You are not authorized to view the admin dashboard.',
            ], 403);
        }

        $today = now()->toDateString();

$parkingOccupancy = DB::table('parking_zones as z')
    ->where('z.is_active', true)
    ->select([
        'z.zone_id',
        'z.zone_code',
        'z.zone_name',
        'z.declared_capacity',
    ])
    ->orderBy('z.zone_name')
    ->get()
    ->map(function ($zone) {
        $activeTransactions = DB::table('parking_transactions as pt')
            ->join('parking_slots as ps', 'ps.slot_id', '=', 'pt.slot_id')
            ->where('ps.zone_id', $zone->zone_id)
            ->where('pt.transaction_status', 'active')
            ->whereNull('pt.exit_datetime')
            ->whereNotNull('pt.slot_id');

        $occupied = (clone $activeTransactions)
            ->distinct()
            ->count('pt.slot_id');

        $occupiedSlotIds = DB::table('parking_transactions')
            ->select('slot_id')
            ->where('transaction_status', 'active')
            ->whereNull('exit_datetime')
            ->whereNotNull('slot_id');

        $available = DB::table('parking_slots')
            ->where('zone_id', $zone->zone_id)
            ->where('slot_status', 'available')
            ->whereNotIn('slot_id', $occupiedSlotIds)
            ->count();

        return [
            'zone_id' => $zone->zone_id,
            'zone' => $zone->zone_name,
            'capacity' => (int) $zone->declared_capacity,
            'occupied' => $occupied,
            'available' => $available,
        ];
    })
    ->values();


        return response()->json([
            'summary' => [
                'total_users' => User::count(),
                'total_vehicles' => Vehicle::count(),
                'total_rfid_tags' => DB::table('rfid_tags')->count(),
                'assigned_rfid_tags' => DB::table('rfid_assignments')
                    ->where('assignment_status', 'active')
                    ->whereNull('unassigned_at')
                    ->count(),
                'available_rfid_tags' => DB::table('rfid_tags')
                    ->where('tag_status', 'available')
                    ->count(),
                'pending_approvals' => User::where('account_status', 'pending')->count(),
                'pending_vehicle_authorizations' => DB::table('vehicle_authorizations')
                    ->where('authorization_status', 'pending')
                    ->count(),
            ],
            'today_activity' => [
                'entries' => DB::table('access_events')
                    ->where('direction', 'in')
                    ->whereDate('event_datetime', $today)
                    ->count(),
                'exits' => DB::table('access_events')
                    ->where('direction', 'out')
                    ->whereDate('event_datetime', $today)
                    ->count(),
            ],
            'parking_occupancy' => $parkingOccupancy,
        ]);
    }
}
