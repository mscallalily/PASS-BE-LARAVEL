<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\ParkingMapLayout;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class ParkingLayoutExportController extends Controller
{
    public function show(Request $request)
    {
        abort_unless($request->user()->isAdmin(), 403);
        $positions = ParkingMapLayout::slotPositions(true);
        $slots = DB::table('parking_slots as s')->join('parking_zones as z', 'z.zone_id', '=', 's.zone_id')->join('parking_areas as a', 'a.parking_area_id', '=', 'z.parking_area_id')
            ->whereIn('a.area_code', ['CAR', 'MC'])->orderBy('s.slot_code')->get(['s.slot_id', 's.slot_code', 'a.parking_area_id', 'a.area_code', 'a.area_name'])
            ->map(function ($s) use ($positions) {
                $p = $positions[$s->slot_code] ?? null;
                return (array) $s + ['placed' => (bool) $p, 'x' => $p['x'] ?? null, 'y' => $p['y'] ?? null, 'w' => $p['w'] ?? null, 'h' => $p['h'] ?? null, 'rotation' => $p['rotation'] ?? 0, 'accessible' => false, 'slot_status' => 'available', 'status' => 'available', 'occupant' => null, 'incident' => null];
            });
        return response()->json(['title' => 'DYCI · PASS Parking Layout', 'generated_at' => now()->toIso8601String(), 'view_box' => ParkingMapLayout::VIEW_BOX,
            'geometry' => ParkingMapLayout::geometry(true), 'measured_layout' => ParkingMapLayout::measuredLayout(), 'slots' => $slots, 'extra_bays' => []])->header('Cache-Control', 'private, no-store');
    }
}
