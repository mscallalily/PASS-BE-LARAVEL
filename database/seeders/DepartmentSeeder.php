<?php
namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $departments = [
            ['CCS', 'College of Computer Studies'],
            ['COE', 'College of Education'],
            ['CHMT', 'College of Hospitality Management and Tourism'],
            ['CAS', 'College of Art and Sciences'],
            ['CME', 'College of Maritime Education'],
            ['CHS', 'College of Health Sciences'],
            ['SOP', 'School of Psychology'],
            ['SME', 'School of Mechanical Engineering'],
            ['COA', 'College of Accountancy'],
            ['CBA', 'College of Business Administration'],
        ];

        foreach ($departments as [$code, $name]) {
            Department::updateOrCreate(
                ['department_code' => $code],
                [
                    'department_name' => $name,
                    'is_active' => true,
                ]
            );
        }
    }
}