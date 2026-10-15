<?php

namespace Database\Seeders;

use App\Support\ParkingMapLayout;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * The two PASS parking areas (configuration, not test data):
 *   Car Parking Area         55 slots, C-01 .. C-55
 *   Motorcycle Parking Area  84 slots, M-01 .. M-84
 *
 * plus the single shared Main Entrance/Exit gate and each slot's position on
 * the DYCI layout map (see App\Support\ParkingMapLayout).
 *
 * Safe to rerun: missing rows are added, existing slots keep their current
 * status, and vehicle types keep any area an administrator already set.
 */
class ParkingAreaSeeder extends Seeder
{
    public const CAR_AREA = 'CAR';

    public const MOTORCYCLE_AREA = 'MC';

    /** The single Main Entrance/Exit used by all vehicles. */
    public const MAIN_GATE = 'MAIN-GATE';

    /** Per-area gates from an earlier version; merged into MAIN-GATE. */
    private const LEGACY_GATES = ['CAR-GATE', 'MC-GATE'];

    private const AREAS = [
        self::CAR_AREA => [
            'name' => 'Car Parking Area',
            'description' => 'Cars, SUVs, vans and pickups',
            'zone_code' => 'C',
            'zone_name' => 'Car Slots',
            'zone_type' => null,
            'prefix' => 'C',
            'capacity' => 55,
            'columns' => 11,
            'vehicle_types' => ['sedan', 'suv', 'hatchback', 'pickup', 'van'],
        ],
        self::MOTORCYCLE_AREA => [
            'name' => 'Motorcycle Parking Area',
            'description' => 'Motorcycles only',
            'zone_code' => 'M',
            'zone_name' => 'Motorcycle Slots',
            'zone_type' => 'motorcycle',
            'prefix' => 'M',
            'capacity' => 84,
            'columns' => 12,
            'vehicle_types' => ['motorcycle'],
        ],
    ];

    public function run(): void
    {
        DB::transaction(function () {
            foreach (self::AREAS as $code => $area) {
                $this->seedArea($code, $area);
            }
        });

        DB::transaction(function () {
            $this->seedSharedGate();
            $this->applyLayout();
        });

        $others = DB::table('parking_areas')->whereNotIn('area_code', array_keys(self::AREAS))->pluck('area_code');
        if ($others->isNotEmpty() && $this->command) {
            $this->command->warn('Other parking areas exist and were left unchanged: ' . $others->implode(', '));
        }
    }

    private function seedArea(string $code, array $area): void
    {
        DB::table('parking_areas')->updateOrInsert(
            ['area_code' => $code],
            ['area_name' => $area['name'], 'description' => $area['description'], 'is_active' => true]
        );
        $areaId = DB::table('parking_areas')->where('area_code', $code)->value('parking_area_id');

        $zoneTypeId = $area['zone_type']
            ? DB::table('vehicle_types')->where('type_code', $area['zone_type'])->value('vehicle_type_id')
            : null;

        if ($area['zone_type'] && !$zoneTypeId) {
            throw new RuntimeException("Vehicle type '{$area['zone_type']}' not found. Run VehicleTypeSeeder first.");
        }

        DB::table('parking_zones')->updateOrInsert(
            ['parking_area_id' => $areaId, 'zone_code' => $area['zone_code']],
            [
                'zone_name' => $area['zone_name'],
                'vehicle_type_id' => $zoneTypeId,
                'declared_capacity' => $area['capacity'],
                'layout_width' => $area['columns'],
                'layout_height' => (int) ceil($area['capacity'] / $area['columns']),
                'is_active' => true,
                'updated_at' => now(),
            ]
        );
        $zoneId = DB::table('parking_zones')->where('parking_area_id', $areaId)->where('zone_code', $area['zone_code'])->value('zone_id');

        $existing = DB::table('parking_slots')->where('zone_id', $zoneId)->pluck('slot_code')->flip();
        $new = [];

        for ($i = 1; $i <= $area['capacity']; $i++) {
            $slotCode = sprintf('%s-%02d', $area['prefix'], $i);

            if (!isset($existing[$slotCode])) {
                $new[] = [
                    'zone_id' => $zoneId,
                    'slot_code' => $slotCode,
                    'vehicle_type_id' => $zoneTypeId,
                    'slot_status' => 'available',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        if ($new) {
            DB::table('parking_slots')->insert($new);
        }

        DB::table('vehicle_types')
            ->whereIn('type_code', $area['vehicle_types'])
            ->whereNull('parking_area_id')
            ->update(['parking_area_id' => $areaId]);
    }

    /**
     * One Main Entrance/Exit shared by cars and motorcycles (parking_area_id
     * NULL). Earlier per-area gates are merged into it: their access events
     * and readers are re-pointed, so no history is lost.
     */
    private function seedSharedGate(): void
    {
        DB::table('access_gates')->updateOrInsert(
            ['gate_code' => self::MAIN_GATE],
            ['parking_area_id' => null, 'gate_name' => 'Main Entrance / Exit', 'gate_type' => 'both', 'gate_status' => 'online', 'updated_at' => now()]
        );
        $mainId = DB::table('access_gates')->where('gate_code', self::MAIN_GATE)->value('gate_id');

        $legacy = DB::table('access_gates')->whereIn('gate_code', self::LEGACY_GATES)->pluck('gate_id');

        if ($legacy->isNotEmpty()) {
            DB::table('access_events')->whereIn('gate_id', $legacy)->update(['gate_id' => $mainId]);
            DB::table('rfid_readers')->whereIn('gate_id', $legacy)->update(['gate_id' => $mainId]);
            DB::table('access_gates')->whereIn('gate_id', $legacy)->delete();
        }
    }

    /**
     * Store each slot's position on the DYCI layout map (pos_x/pos_y = bay
     * centre, size and rotation; scaled by ParkingMapLayout::SCALE). Slots not
     * printed on the reference drawing get no position. Status is untouched.
     */
    private function applyLayout(): void
    {
        $positions = ParkingMapLayout::slotPositions();
        $scale = ParkingMapLayout::SCALE;

        $slots = DB::table('parking_slots as s')
            ->join('parking_zones as z', 'z.zone_id', '=', 's.zone_id')
            ->join('parking_areas as a', 'a.parking_area_id', '=', 'z.parking_area_id')
            ->whereIn('a.area_code', array_keys(self::AREAS))
            ->get(['s.slot_id', 's.slot_code']);

        foreach ($slots as $slot) {
            $bay = $positions[$slot->slot_code] ?? null;

            DB::table('parking_slots')->where('slot_id', $slot->slot_id)->update([
                'pos_x' => $bay ? (int) round($bay['x'] * $scale) : null,
                'pos_y' => $bay ? (int) round($bay['y'] * $scale) : null,
                'slot_width' => $bay ? (int) round($bay['w'] * $scale) : null,
                'slot_height' => $bay ? (int) round($bay['h'] * $scale) : null,
                'rotation_deg' => $bay ? (int) round($bay['rotation']) : 0,
            ]);

            // Mark the drawing's PWD bay as accessible; other flags are left as set.
            if ($bay && $bay['pwd']) {
                DB::table('parking_slots')->where('slot_id', $slot->slot_id)->update(['is_pwd_slot' => true]);
            }
        }
    }
}
