<?php
namespace Tests\Feature\Parking;

use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;

class StagingMergeTest extends ParkingTestCase
{
    public function test_admin_modules_are_restored_with_real_occupancy_and_explicit_logo_todo(): void
    {
        $this->actingAsAdmin();
        $this->getJson('/api/admin/dashboard')->assertOk()->assertJsonStructure(['summary' => ['total_users', 'total_vehicles'], 'today_activity', 'parking_occupancy']);
        $this->getJson('/api/admin/parking/occupancy')->assertOk()->assertJsonPath('overall.capacity', 139);
        DB::table('audit_logs')->insert(['actor_label' => 'QA', 'action' => 'merge_test', 'new_values' => json_encode(['status' => 'approved']), 'created_at' => now()]);
        $this->getJson('/api/admin/audit-logs?search=merge_test')->assertOk()->assertJsonPath('data.0.new_values.status', 'approved');
        $this->getJson('/api/admin/system-settings')->assertOk();
        $this->postJson('/api/admin/system-settings/logo')->assertStatus(501);
        $this->putJson('/api/admin/system-settings', ['system_name' => 'PASS', 'institution_name' => 'DYCI', 'contact_email' => 'test@example.test', 'timezone' => 'Asia/Manila', 'date_format' => 'MM/DD/YYYY'])->assertOk();
        $this->assertDatabaseHas('system_settings', ['setting_key' => 'system_name', 'setting_value' => 'PASS']);
    }

    public function test_student_and_parking_staff_cannot_access_restored_admin_only_modules(): void
    {
        foreach (['student', 'security'] as $type) {
            Sanctum::actingAs($this->user($type));
            foreach (['/api/admin/dashboard', '/api/admin/parking/occupancy', '/api/admin/audit-logs', '/api/admin/system-settings'] as $url) $this->getJson($url)->assertForbidden();
            $this->putJson('/api/admin/system-settings', [])->assertForbidden();
            $this->postJson('/api/admin/system-settings/logo')->assertForbidden();
        }
    }

    public function test_slot_simulation_is_separate_from_actual_transactions(): void
    {
        $this->actingAsStaff();
        $before = DB::table('parking_transactions')->count();
        $this->getJson('/api/admin/parking/slots')->assertOk()->assertJsonPath('summary.total', 139);
        $this->patchJson('/api/admin/parking/slots', ['vehicle_type' => 'car', 'slot_number' => 1, 'is_occupied' => true])->assertOk();
        $this->getJson('/api/admin/parking/slots')->assertOk()->assertJsonPath('summary.occupied', 1);
        $this->getJson('/api/parking/overview')->assertOk()->assertJsonPath('overall.occupied', 0);
        $this->assertSame($before, DB::table('parking_transactions')->count());
        $this->patchJson('/api/admin/parking/slots', ['vehicle_type' => 'car', 'slot_number' => 56, 'is_occupied' => true])->assertStatus(422);
    }
}
