<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/** Populate empty reference tables only. Never create demo accounts or reset active slots. */
class ProductionReferenceSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            'roles' => RoleSeeder::class,
            'departments' => DepartmentSeeder::class,
            'vehicle_types' => VehicleTypeSeeder::class,
            'violation_types' => ViolationTypeSeeder::class,
            'parking_areas' => ParkingAreaSeeder::class,
        ] as $table => $seeder) {
            if (DB::table($table)->count() === 0) $this->call($seeder);
        }
    }
}
