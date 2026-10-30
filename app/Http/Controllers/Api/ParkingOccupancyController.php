<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ParkingOccupancyController extends Controller
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
                'message' => 'You are not authorized to view parking occupancy.',
            ], 403);
        }

        $areas = DB::table('parking_areas')
            ->where('is_active', true)
            ->orderBy('area_name')
            ->get([
                'parking_area_id',
                'area_code',
                'area_name',
                'building',
                'floor_level',
            ]);

        $zones = DB::table('parking_zones')
            ->where('is_active', true)
            ->orderBy('zone_name')
            ->get([
                'zone_id',
                'parking_area_id',
                'zone_code',
                'zone_name',
                'declared_capacity',
                'layout_width',
                'layout_height',
            ]);

        $slots = DB::table('parking_slots')
            ->orderBy('slot_code')
            ->get([
                'slot_id',
                'zone_id',
                'slot_code',
                'slot_status',
                'is_pwd_slot',
                'pos_x',
                'pos_y',
                'slot_width',
                'slot_height',
                'rotation_deg',
            ]);

        $slotIds = $slots->pluck('slot_id');

        $sensors = DB::table('parking_sensors')
            ->whereIn('slot_id', $slotIds)
            ->orderBy('sensor_id')
            ->get([
                'sensor_id',
                'slot_id',
                'sensor_code',
                'sensor_type',
                'sensor_status',
                'last_seen_at',
            ]);

        $sensorIds = $sensors->pluck('sensor_id');

        $latestReadings = collect();

        if ($sensorIds->isNotEmpty()) {
            $latestReadingIds = DB::table('parking_sensor_readings')
                ->selectRaw('MAX(reading_id) as reading_id')
                ->whereIn('sensor_id', $sensorIds)
                ->groupBy('sensor_id');

            $latestReadings = DB::table('parking_sensor_readings')
                ->joinSub(
                    $latestReadingIds,
                    'latest',
                    fn ($join) => $join->on(
                        'parking_sensor_readings.reading_id',
                        '=',
                        'latest.reading_id'
                    )
                )
                ->get([
                    'parking_sensor_readings.reading_id',
                    'parking_sensor_readings.sensor_id',
                    'parking_sensor_readings.occupancy_status',
                    'parking_sensor_readings.detected_at',
                    'parking_sensor_readings.received_at',
                ])
                ->keyBy('sensor_id');
        }

        $sensorBySlot = $sensors->groupBy('slot_id');

        $readingMaxAgeMinutes = 5;

        $slotResults = $slots->map(function ($slot) use (
            $sensorBySlot,
            $latestReadings,
            $readingMaxAgeMinutes
        ) {
            $slotSensors = $sensorBySlot->get($slot->slot_id, collect());

            $sensorResults = $slotSensors->map(function ($sensor) use (
                $latestReadings,
                $readingMaxAgeMinutes
            ) {
                $reading = $latestReadings->get($sensor->sensor_id);

                $isFresh = $reading
                    && $reading->detected_at !== null
                    && strtotime($reading->detected_at) >= now()
                        ->subMinutes($readingMaxAgeMinutes)
                        ->timestamp
                    && strtotime($reading->detected_at) <= now()->timestamp;

                $status = 'unknown';

                if (
                    $sensor->sensor_status === 'online'
                    && $isFresh
                    && in_array(
                        $reading->occupancy_status,
                        ['occupied', 'vacant'],
                        true
                    )
                ) {
                    $status = $reading->occupancy_status;
                }

                return [
                    'sensor_id' => $sensor->sensor_id,
                    'sensor_code' => $sensor->sensor_code,
                    'sensor_status' => $sensor->sensor_status,
                    'last_seen_at' => $sensor->last_seen_at,
                    'occupancy_status' => $status,
                    'reading_detected_at' => $reading?->detected_at,
                ];
            })->values();

            $validStatuses = $sensorResults
                ->pluck('occupancy_status')
                ->filter(fn ($status) => $status !== 'unknown');

            $occupancyStatus = 'unknown';

            if ($validStatuses->isNotEmpty()) {
                $occupancyStatus = $validStatuses->contains('occupied')
                    ? 'occupied'
                    : (
                        $validStatuses->every(
                            fn ($status) => $status === 'vacant'
                        )
                            ? 'vacant'
                            : 'unknown'
                    );
            }

            return [
                'zone_id' => $slot->zone_id,
                'slot_id' => $slot->slot_id,
                'slot_code' => $slot->slot_code,
                'slot_status' => $slot->slot_status,
                'is_pwd_slot' => (bool) $slot->is_pwd_slot,
                'position' => [
                    'x' => $slot->pos_x,
                    'y' => $slot->pos_y,
                    'width' => $slot->slot_width,
                    'height' => $slot->slot_height,
                    'rotation_deg' => $slot->rotation_deg,
                ],
                'occupancy_status' => $occupancyStatus,
                'sensors' => $sensorResults,
            ];
        });

        $zonesWithSlots = $zones->map(function ($zone) use ($slotResults) {
            $zoneSlots = $slotResults
                ->where('zone_id', $zone->zone_id)
                ->values();

            return [
                'parking_area_id' => $zone->parking_area_id,
                'zone_id' => $zone->zone_id,
                'zone_code' => $zone->zone_code,
                'zone_name' => $zone->zone_name,
                'declared_capacity' => (int) $zone->declared_capacity,
                'layout' => [
                    'width' => $zone->layout_width,
                    'height' => $zone->layout_height,
                ],
                'slots' => $zoneSlots,
            ];
        });

        $areasWithZones = $areas->map(function ($area) use ($zonesWithSlots) {
            return [
                'parking_area_id' => $area->parking_area_id,
                'area_code' => $area->area_code,
                'area_name' => $area->area_name,
                'building' => $area->building,
                'floor_level' => $area->floor_level,
                'zones' => $zonesWithSlots
                    ->where('parking_area_id', $area->parking_area_id)
                    ->values(),
            ];
        });

        return response()->json([
            'data_source' => 'parking_sensors',
            'sensor_hardware_configured' => $sensors->contains(
    fn ($sensor) => $sensor->installed_at !== null
        || in_array(
            $sensor->sensor_status,
            ['online', 'offline', 'maintenance'],
            true
        )
),
            'reading_max_age_minutes' => $readingMaxAgeMinutes,
            'summary' => [
                'areas' => $areas->count(),
                'zones' => $zones->count(),
                'slots' => $slots->count(),
                'configured_sensors' => $sensors->count(),
                'occupied' => $slotResults
                    ->where('occupancy_status', 'occupied')
                    ->count(),
                'vacant' => $slotResults
                    ->where('occupancy_status', 'vacant')
                    ->count(),
                'unknown' => $slotResults
                    ->where('occupancy_status', 'unknown')
                    ->count(),
            ],
            'areas' => $areasWithZones->values(),
        ]);
    }
}
