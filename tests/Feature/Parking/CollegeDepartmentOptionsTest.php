<?php

namespace Tests\Feature\Parking;

use App\Models\Department;
use App\Services\Registration\InstitutionalIdReceipt;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

class CollegeDepartmentOptionsTest extends ParkingTestCase
{
    protected function setUp(): void
    {
        parent::setUp(); Cache::flush(); Storage::fake('private'); Notification::fake();
    }

    public function test_four_offices_cannot_be_submitted_as_college_departments(): void
    {
        $before = DB::table('users')->count();
        foreach (['Security Office', 'HR Department', 'IT Department', 'Registrar Office'] as $i => $office) {
            Department::updateOrCreate(['department_code' => 'QA-OFF'.$i], ['department_name' => $office, 'is_active' => true]);
            $this->post('/api/register', $this->payload('college', $office, $i), ['Accept' => 'application/json'])
                ->assertUnprocessable()->assertJsonValidationErrors('department');
        }
        $this->assertDatabaseCount('users', $before);
        $this->assertSame([], Storage::disk('private')->allFiles());
    }

    public function test_office_case_or_extra_spaces_do_not_bypass_college_restriction(): void
    {
        Department::updateOrCreate(['department_code' => 'QA-ODD'], ['department_name' => 'security  office', 'is_active' => true]);
        $this->post('/api/register', $this->payload('college', 'security  office', 1), ['Accept' => 'application/json'])
            ->assertUnprocessable()->assertJsonValidationErrors('department');
    }

    public function test_employee_can_still_register_with_each_of_the_four_offices(): void
    {
        foreach (['Security Office', 'HR Department', 'IT Department', 'Registrar Office'] as $i => $office) {
            Department::updateOrCreate(['department_code' => 'QA-OFF'.$i], ['department_name' => $office, 'is_active' => true]);
            $r = $this->post('/api/register', $this->payload('employee', $office, $i), ['Accept' => 'application/json'])
                ->assertCreated()->assertJsonPath('user.role', 'employee');
            $this->assertDatabaseHas('user_profiles', ['user_id' => (int) $r->json('user.id'), 'registration_unit' => $office]);
            $this->assertDatabaseHas('departments', ['department_name' => $office, 'is_active' => true]);
        }
    }

    private function payload(string $category, string $office, int $i): array
    {
        $id = $category === 'employee' ? '022'.(2000 + $i) : (string) (202420000 + $i);
        $fields = ['first_name' => 'MARIA BEATRICE', 'last_name' => 'DELA CRUZ', 'user_code' => $id];
        $file = UploadedFile::fake()->image('id.png', 1000, 1600);
        $e = ['kind' => 'image', 'engine_available' => true, 'width' => 1000, 'height' => 1600, 'sharpness' => 100,
            'school_marker' => true, 'fields' => $fields, 'field_confidence' => ['first_name' => 95, 'last_name' => 95, 'user_code' => 95]];
        return $fields + ['registration_category' => $category, 'email' => 'office-'.$i.'-'.$category.'@example.test',
            'password' => 'Password2026!', 'password_confirmation' => 'Password2026!', 'department' => $office,
            'contact_number' => '09170000000', 'agree' => true, 'school_id_file' => $file,
            'id_check_receipt' => app(InstitutionalIdReceipt::class)->issue($file, $category, $e)];
    }
}
