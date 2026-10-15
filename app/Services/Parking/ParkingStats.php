<?php

namespace App\Services\Parking;

use App\Support\CampusTime;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Occupancy figures calculated from MySQL:
 *   occupied    slots held by an active parking transaction
 *   maintenance slots marked maintenance or disabled with no vehicle in them
 *   available   capacity - occupied - maintenance
 */
class ParkingStats
{
    /**
     * @return array<int, array> Active parking areas that have slots, by area id.
     */
    public static function areas(): array
    {
        $slots = DB::table('parking_areas as a')
            ->join('parking_zones as z', fn ($j) => $j->on('z.parking_area_id', '=', 'a.parking_area_id')->where('z.is_active', true))
            ->join('parking_slots as s', 's.zone_id', '=', 'z.zone_id')
            ->leftJoin('parking_transactions as t', fn ($j) => $j->on('t.slot_id', '=', 's.slot_id')->where('t.transaction_status', 'active'))
            ->where('a.is_active', true)
            ->groupBy('a.parking_area_id', 'a.area_code', 'a.area_name')
            ->orderBy('a.area_code')
            ->selectRaw("a.parking_area_id, a.area_code, a.area_name,
                COUNT(DISTINCT s.slot_id) as capacity,
                COUNT(DISTINCT CASE WHEN s.slot_status IN ('maintenance', 'disabled') AND t.transaction_id IS NULL THEN s.slot_id END) as maintenance,
                COUNT(DISTINCT t.transaction_id) as occupied,
                COUNT(DISTINCT CASE WHEN t.slot_confirmed_at IS NOT NULL THEN t.transaction_id END) as confirmed")
            ->get();

        $areas = [];

        foreach ($slots as $row) {
            $capacity = (int) $row->capacity;
            $occupied = (int) $row->occupied;
            $maintenance = (int) $row->maintenance;

            $areas[$row->parking_area_id] = [
                'parking_area_id' => (int) $row->parking_area_id,
                'area_code' => $row->area_code,
                'area_name' => $row->area_name,
                'capacity' => $capacity,
                'occupied' => $occupied,
                'confirmed' => (int) $row->confirmed,
                'awaiting_confirmation' => $occupied - (int) $row->confirmed,
                'maintenance' => $maintenance,
                'available' => max(0, $capacity - $occupied - $maintenance),
                'occupancy_percent' => $capacity ? round($occupied / $capacity * 100, 1) : 0.0,
            ];
        }

        return $areas;
    }

    public static function area(int $areaId): ?array
    {
        return self::areas()[$areaId] ?? null;
    }

    public static function overall(array $areas): array
    {
        $sum = fn (string $key) => array_sum(array_column($areas, $key));
        $capacity = $sum('capacity');

        return [
            'capacity' => $capacity,
            'occupied' => $sum('occupied'),
            'confirmed' => $sum('confirmed'),
            'awaiting_confirmation' => $sum('awaiting_confirmation'),
            'maintenance' => $sum('maintenance'),
            'available' => $sum('available'),
            'occupancy_percent' => $capacity ? round($sum('occupied') / $capacity * 100, 1) : 0.0,
        ];
    }

    /**
     * Vehicles parked during each hour of today so far, per area. Hours are
     * campus hours (Asia/Manila); stored times are UTC.
     *
     * @return array<int, array{hour: string, total: int, areas: array<int, int>}>
     */
    public static function hourlyToday(array $areaIds): array
    {
        $now = CampusTime::now();
        $start = $now->startOfDay();

        $stays = DB::table('parking_transactions')
            ->where('entry_datetime', '<=', CampusTime::stored($now))
            ->where(fn ($q) => $q->whereNull('exit_datetime')->orWhere('exit_datetime', '>=', CampusTime::stored($start)))
            ->whereIn('transaction_status', ['active', 'completed', 'force_closed'])
            ->get(['parking_area_id', 'entry_datetime', 'exit_datetime']);

        $hours = [];

        for ($h = 0; $h <= (int) $now->format('G'); $h++) {
            $from = $start->addHours($h);
            $to = $from->addHour();
            $perArea = array_fill_keys($areaIds, 0);

            foreach ($stays as $stay) {
                $in = CarbonImmutable::parse($stay->entry_datetime);
                $out = $stay->exit_datetime ? CarbonImmutable::parse($stay->exit_datetime) : null;

                if ($in < $to && ($out === null || $out >= $from) && isset($perArea[$stay->parking_area_id])) {
                    $perArea[$stay->parking_area_id]++;
                }
            }

            $hours[] = ['hour' => $from->format('g A'), 'total' => array_sum($perArea), 'areas' => $perArea];
        }

        return $hours;
    }
}
