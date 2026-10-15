<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ViolationTypeSeeder extends Seeder
{
    public const WRONG_SLOT = 'WRONG_SLOT';

    public const WRONG_AREA = 'WRONG_AREA';

    public function run(): void
    {
        $types = [
            [self::WRONG_SLOT, 'Parked in Wrong Slot', 'Vehicle was observed in a slot other than the one assigned at entry.', 'minor'],
            [self::WRONG_AREA, 'Parked in Wrong Parking Area', 'Vehicle was observed in a parking area not meant for its vehicle type.', 'major'],
            ['UNAUTHORIZED_PARKING', 'Unauthorized Parking', 'Vehicle parked without a valid authorization or pass.', 'major'],
            ['OVERSTAYING', 'Overstaying', 'Vehicle stayed beyond the permitted time.', 'minor'],
            ['INVALID_AUTHORIZATION', 'Invalid Vehicle Authorization', 'Vehicle used an expired, suspended or rejected authorization.', 'major'],
            ['TAILGATING', 'Tailgating / Improper Gate Access', 'Vehicle followed another through the gate without its own access check.', 'severe'],
            ['BLOCKING_LANE', 'Blocking Access Lane', 'Vehicle obstructed a driveway, lane or gate.', 'minor'],
            ['OTHER', 'Other', 'Other parking incident.', 'minor'],
        ];

        foreach ($types as [$code, $name, $description, $severity]) {
            DB::table('violation_types')->updateOrInsert(
                ['violation_code' => $code],
                ['violation_name' => $name, 'description' => $description, 'severity' => $severity, 'is_active' => true]
            );
        }
    }
}
