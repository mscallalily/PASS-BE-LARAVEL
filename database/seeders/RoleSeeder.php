<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['admin',         'System Administrator',          'Overall system administrator'],
            ['parking_admin', 'Parking Administrator',         'Manages parking operations'],
            ['security',      'Security / Parking Personnel',  'Gate and parking staff'],
            ['student',       'Student',                       'Registered student user'],
            ['faculty',       'Faculty',                       'Registered faculty user'],
            ['employee',      'Employee',                      'Registered employee user'],
        ];

        foreach ($roles as [$code, $name, $desc]) {
            Role::updateOrCreate(
                ['role_code' => $code],
                ['role_name' => $name, 'description' => $desc, 'is_system' => true, 'is_active' => true]
            );
        }
    }
}