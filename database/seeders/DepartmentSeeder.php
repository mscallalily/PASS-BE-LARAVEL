<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        // PLACEHOLDERS from the Figma prototype. Replace with the official DYCI list.
        $departments = [
            ['COE', 'College of Engineering'],
            ['COS', 'College of Science'],
            ['COB', 'College of Business'],
            ['COA', 'College of Arts'],
            ['CON', 'College of Nursing'],
            ['REG', 'Registrar Office'],
            ['HR',  'HR Department'],
            ['IT',  'IT Department'],
            ['SEC', 'Security Office'],
        ];

        foreach ($departments as [$code, $name]) {
            Department::updateOrCreate(
                ['department_code' => $code],
                ['department_name' => $name, 'is_active' => true]
            );
        }
    }
}