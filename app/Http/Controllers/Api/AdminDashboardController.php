<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Parking\ParkingStats;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminDashboardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()?->isAdmin(), 403);
        $totalTags = DB::table('rfid_tags')->count();

        $assignedTags = DB::table('rfid_tags')
            ->where('tag_status', 'assigned')
            ->count();

        $today = now()->toDateString();

        return response()->json([
            'summary' => [
                'total_users' => DB::table('users')
                    ->whereNull('deleted_at')
                    ->count(),

                'total_vehicles' => DB::table('vehicles')
                    ->whereNull('deleted_at')
                    ->count(),

                'total_rfid_tags' => $totalTags,
                'assigned_rfid_tags' => $assignedTags,
                'available_rfid_tags' => DB::table('rfid_tags')
                    ->where('tag_status', 'available')
                    ->count(),

                'pending_approvals' => DB::table('users')
                    ->whereNull('deleted_at')
                    ->where('account_status', 'pending')
                    ->count(),

                'pending_vehicle_authorizations' => DB::table('vehicle_authorizations')
                    ->where('authorization_status', 'pending')
                    ->count(),
            ],

            'today_activity' => [
                'entries' => DB::table('parking_transactions')
                    ->whereDate('entry_datetime', $today)
                    ->count(),

                'exits' => DB::table('parking_transactions')
                    ->whereDate('exit_datetime', $today)
                    ->count(),
            ],

            'parking_occupancy' => $this->occupancyRows(),
        ]);
    }
    public function occupancy(Request $request): JsonResponse
    {
        abort_unless($request->user()?->isAdmin(), 403);
        $areas = ParkingStats::areas();
        return response()->json(['data' => $this->occupancyRows(), 'overall' => ParkingStats::overall($areas)]);
    }

    private function occupancyRows(): array
    {
        return array_values(array_map(fn ($area) => [
            'zone_id' => $area['parking_area_id'], 'zone' => $area['area_name'],
            'capacity' => $area['capacity'], 'occupied' => $area['occupied'], 'available' => $area['available'],
        ], ParkingStats::areas()));
    }
}
