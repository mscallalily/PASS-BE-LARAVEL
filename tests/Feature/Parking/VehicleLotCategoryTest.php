<?php

namespace Tests\Feature\Parking;

use Illuminate\Support\Facades\DB;

class VehicleLotCategoryTest extends ParkingTestCase
{
    public function test_all_supported_four_wheel_types_enter_car_lot_and_motorcycle_enters_motorcycle_lot(): void
    {
        $this->actingAsStaff();
        foreach (['sedan', 'suv', 'hatchback', 'pickup', 'van', 'motorcycle'] as $type) {
            $car = $this->campusVehicle($type); $result = $this->gate('entry', ['rfid_tag_uid' => $car['tag']]);
            $this->assertSame('granted', $result['status']);
            $this->assertStringStartsWith($type === 'motorcycle' ? 'M-' : 'C-', $result['slot_code']);
            $area = DB::table('parking_areas')->where('parking_area_id', $this->activeTransaction($car['vehicle_id'])->parking_area_id)->value('area_code');
            $this->assertSame($type === 'motorcycle' ? 'MC' : 'CAR', $area);
        }
    }
    public function test_wrong_type_mapping_is_denied_even_if_wrong_lot_has_free_slots(): void
    {
        $this->actingAsStaff(); $car = $this->campusVehicle('sedan');
        $mc = DB::table('parking_areas')->where('area_code', 'MC')->value('parking_area_id');
        DB::table('vehicle_types')->where('type_code', 'sedan')->update(['parking_area_id' => $mc]);
        $result = $this->gate('entry', ['rfid_tag_uid' => $car['tag']]);
        $this->assertSame('denied', $result['status']); $this->assertNull($this->activeTransaction($car['vehicle_id']));
    }
    public function test_full_car_lot_does_not_allocate_empty_motorcycle_slot(): void
    {
        $this->actingAsStaff(); $car = $this->campusVehicle('suv');
        $area = DB::table('parking_areas')->where('area_code', 'CAR')->value('parking_area_id');
        DB::table('parking_slots')->whereIn('zone_id', DB::table('parking_zones')->where('parking_area_id', $area)->select('zone_id'))->update(['slot_status' => 'maintenance']);
        $result = $this->gate('entry', ['rfid_tag_uid' => $car['tag']]);
        $this->assertSame('denied', $result['status']); $this->assertNull($this->activeTransaction($car['vehicle_id']));
        $bike = $this->campusVehicle('motorcycle');
        $this->assertSame('granted', $this->gate('entry', ['rfid_tag_uid' => $bike['tag']])['status']);
    }
}
