<?php

namespace App\Services\Parking;

use App\Support\ParkingMapLayout;
use Illuminate\Support\Facades\DB;

/** Update diagram coordinates only; keep access, occupancy, capacity and gate state. */
class ParkingLayoutUpdater
{
    public function apply(): int
    {
        return DB::transaction(function () {
            $positions = ParkingMapLayout::slotPositions();
            $scale = ParkingMapLayout::SCALE;
            $slots = DB::table('parking_slots as s')
                ->join('parking_zones as z', 'z.zone_id', '=', 's.zone_id')
                ->join('parking_areas as a', 'a.parking_area_id', '=', 'z.parking_area_id')
                ->whereIn('a.area_code', ['CAR', 'MC'])->get(['s.*', 'a.area_code']);
            $changed = 0;
            foreach ($slots as $slot) {
                $bay = $positions[$slot->slot_code] ?? null;
                if ($bay && $bay['area'] !== $slot->area_code) {
                    continue;
                }
                $values = [
                    'pos_x' => $bay ? (int) round($bay['x'] * $scale) : null,
                    'pos_y' => $bay ? (int) round($bay['y'] * $scale) : null,
                    'slot_width' => $bay ? (int) round($bay['w'] * $scale) : null,
                    'slot_height' => $bay ? (int) round($bay['h'] * $scale) : null,
                    'rotation_deg' => $bay ? (int) round($bay['rotation']) : 0,
                ];
                if ($bay && $bay['pwd']) {
                    $values['is_pwd_slot'] = true;
                }
                $different = collect($values)->contains(fn ($value, $key) => $value === null ? $slot->$key !== null : $slot->$key === null || (int) $slot->$key !== (int) $value);
                if ($different) {
                    DB::table('parking_slots')->where('slot_id', $slot->slot_id)->update($values + ['updated_at' => now()]);
                    $changed++;
                }
            }

            return $changed;
        });
    }
}
