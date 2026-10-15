<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\CampusTime;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ParkingDashboardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        // Same staff rule as Parking Operations, Violations and License review.
        if (!$request->user()->canManageParking()) {
            return response()->json([
                'message' => 'Unauthorized access.'
            ], 403);
        }

        // "Today" is the campus day (Asia/Manila); stored times are UTC.
        [$start, $end] = CampusTime::dayRange();

        $todayEntries = DB::table('parking_transactions')
            ->where('entry_datetime', '>=', $start)
            ->where('entry_datetime', '<', $end)
            ->count();

        $todayExits = DB::table('parking_transactions')
            ->where('exit_datetime', '>=', $start)
            ->where('exit_datetime', '<', $end)
            ->count();

        $deniedAccess = DB::table('access_events')
            ->where('event_datetime', '>=', $start)
            ->where('event_datetime', '<', $end)
            ->where('auth_result', 'denied')
            ->count();

        // Same definition of "inside" as the Occupancy Monitor and Parking Layout.
        $activeVisitors = DB::table('parking_transactions')
            ->whereNotNull('visitor_id')
            ->where('transaction_status', 'active')
            ->distinct()
            ->count('visitor_id');

        $recentTransactions = DB::table('parking_transactions as pt')
            ->leftJoin('vehicles as v', 'pt.vehicle_id', '=', 'v.vehicle_id')
            ->select(
                'pt.transaction_id',
                'pt.ticket_number',
                'v.plate_number',
                'pt.entry_datetime',
                'pt.exit_datetime'
            )
            ->orderByDesc('pt.created_at')
            ->limit(10)
            ->get()
            ->map(function ($row) {
                $row->entry_datetime = ParkingOperationsController::iso($row->entry_datetime);
                $row->exit_datetime = ParkingOperationsController::iso($row->exit_datetime);

                return $row;
            });

        return response()->json([
            'today_entries' => $todayEntries,
            'today_exits' => $todayExits,
            'denied_access' => $deniedAccess,
            'active_visitors' => $activeVisitors,
            'recent_events' => $recentTransactions,
            'campus_date' => CampusTime::date(),
        ]);
    }
}
