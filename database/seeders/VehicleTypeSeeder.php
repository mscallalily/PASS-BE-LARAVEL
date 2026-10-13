<?php

namespace Database\Seeders;

use App\Models\VehicleType;
use Illuminate\Database\Seeder;

class VehicleTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['sedan',      'Sedan'],
            ['suv',        'SUV'],
            ['hatchback',  'Hatchback'],
            ['pickup',     'Pickup'],
            ['van',        'Van'],
            ['motorcycle', 'Motorcycle'],
        ];

        foreach ($types as [$code, $name]) {
            VehicleType::updateOrCreate(
                ['type_code' => $code],
                ['type_name' => $name, 'requires_assigned_slot' => false]
            );
        }
    }
}