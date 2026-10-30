<?php

namespace Tests\Feature\Parking;

use App\Support\AuditActivity;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;

class AuditExperienceTest extends ParkingTestCase
{
    private function activity(string $action, string $type = 'users', array $extra = []): int
    {
        return DB::table('audit_logs')->insertGetId($extra + ['actor_label' => 'UX-ACTOR', 'action' => $action, 'auditable_type' => $type, 'auditable_id' => 1, 'created_at' => now()], 'log_id');
    }

    private function revision(string $type, string $operation, array $values, string $id = 'UX-RECORD'): int
    {
        return DB::table('record_history')->insertGetId(['record_type' => $type, 'record_id' => $id, 'operation' => $operation, 'source' => 'UX test fixture', 'after_values' => json_encode($values), 'recorded_at' => now()], 'revision_id');
    }

    public function test_normal_compound_actions_are_completed_and_unknown_actions_are_neutral(): void
    {
        $cases = ['login' => 'completed', 'logout' => 'completed', 'rfid_assigned' => 'completed', 'account_auto_approved' => 'completed',
            'authorization_rejected' => 'completed', 'license_review_rejected' => 'completed', 'vehicle_release_requested' => 'recorded',
            'custom_future_action' => 'recorded', 'access_denied' => 'denied', 'reader_write_failed' => 'failed', 'hardware_offline' => 'attention'];
        $this->actingAsAdmin();
        foreach ($cases as $action => $outcome) { $this->activity($action); $this->assertSame($outcome, AuditActivity::outcome($action)); }
        $r = $this->getJson('/api/admin/audit-logs?search=UX-ACTOR')->assertOk();
        foreach ($r->json('data') as $row) $this->assertSame($cases[$row['action']], $row['presentation']['outcome']);
        $this->assertSame('RFID assigned', AuditActivity::label('rfid_assigned'));
        $this->assertSame('Accounts & access', AuditActivity::MODULES[AuditActivity::module('App\\Models\\User')]);
    }

    public function test_result_filters_and_counts_cover_other_pages(): void
    {
        $this->actingAsAdmin();
        for ($i = 0; $i < 26; $i++) $this->activity('rfid_assigned', 'rfid_assignments');
        $this->activity('future_activity', 'rfid_assignments'); $this->activity('profile_contact_updated', 'users');
        $r = $this->getJson('/api/admin/audit-logs?search=UX-ACTOR&module=rfid&outcome=recorded')->assertOk();
        $r->assertJsonPath('total', 1)->assertJsonPath('summary.completed', 26)->assertJsonPath('summary.recorded', 1)->assertJsonPath('data.0.action', 'future_activity');
        $this->getJson('/api/admin/audit-logs?search=UX-ACTOR&module=accounts')->assertOk()->assertJsonPath('total', 1);
    }

    public function test_activity_search_supports_actor_names_and_redacts_secrets(): void
    {
        $admin = $this->actingAsAdmin();
        DB::table('user_profiles')->insert(['user_id' => $admin->user_id, 'first_name' => 'Synthetic', 'last_name' => 'Administrator', 'created_at' => now(), 'updated_at' => now()]);
        $this->activity('account_updated', 'users', ['user_id' => $admin->user_id, 'new_values' => json_encode(['user_code' => 'UX-ACCOUNT', 'password' => 'DO-NOT-EXPOSE', 'nested' => ['api_key' => 'NEVER-EXPOSE', 'status' => 'active']])]);
        $this->getJson('/api/admin/audit-logs?search=Synthetic%20Administrator')->assertOk()->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.actor_name', 'Synthetic Administrator')->assertDontSee('DO-NOT-EXPOSE')->assertDontSee('NEVER-EXPOSE');
    }

    public function test_end_date_alone_uses_campus_day_boundaries_and_invalid_ranges_are_rejected(): void
    {
        $this->actingAsAdmin();
        $this->activity('login', 'users', ['created_at' => '2026-10-23 15:59:59']);
        $this->activity('logout', 'users', ['created_at' => '2026-10-23 16:00:00']);
        $this->getJson('/api/admin/audit-logs?search=UX-ACTOR&date_to=2026-10-23')->assertOk()->assertJsonPath('total', 1)->assertJsonPath('data.0.action', 'login');
        $this->getJson('/api/admin/audit-logs?date_from=2026-10-24&date_to=2026-10-23')->assertUnprocessable();
        $this->getJson('/api/admin/record-history?date_to=2026-10-23')->assertOk();
    }

    public function test_history_search_can_hide_baselines_and_system_revisions_without_removing_them(): void
    {
        $this->actingAsAdmin();
        $baseline = $this->revision('vehicles', 'baseline', ['plate_number' => 'UX 9001']);
        $changed = $this->revision('vehicles', 'updated', ['plate_number' => 'UX 9001', 'color' => 'Blue']);
        $system = $this->revision('audit_logs', 'created', ['actor_label' => 'UX 9001']);
        $this->getJson('/api/admin/record-history?scope=business&include_baseline=0&search=UX%209001')->assertOk()
            ->assertJsonPath('history.total', 1)->assertJsonPath('history.data.0.revision_id', $changed)->assertJsonPath('history.data.0.record_label', 'UX 9001');
        $this->getJson('/api/admin/record-history?scope=all&include_baseline=1&search=UX%209001')->assertOk()->assertJsonPath('history.total', 3);
        $this->getJson('/api/admin/record-history?scope=business&include_baseline=0&revision_id='.$baseline)->assertOk()->assertJsonPath('history.total', 1);
        foreach ([$baseline, $changed, $system] as $id) $this->assertDatabaseHas('record_history', ['revision_id' => $id]);
    }

    public function test_archive_lists_show_owner_names_counts_and_preserve_archive_controls(): void
    {
        $car = $this->campusVehicle(); $this->actingAsAdmin();
        DB::table('user_profiles')->insert(['user_id' => $car['user']->user_id, 'first_name' => 'Synthetic', 'last_name' => 'Owner', 'created_at' => now(), 'updated_at' => now()]);
        $url = '/api/admin/record-archives/vehicles?search='.urlencode($car['plate']);
        $this->getJson($url.'&state=active')->assertOk()->assertJsonPath('data.0.owner', 'Synthetic Owner')->assertJsonPath('summary.active', 1)->assertJsonPath('summary.archived', 0);
        $this->postJson('/api/admin/record-archives/vehicles/'.$car['vehicle_id'].'/archive', ['reason' => 'Archived synthetic test vehicle.'])->assertOk();
        $this->getJson($url.'&state=archived')->assertOk()->assertJsonPath('summary.active', 0)->assertJsonPath('summary.archived', 1);
    }

    public function test_filters_are_validated_and_admin_records_stay_admin_only(): void
    {
        $this->actingAsAdmin();
        foreach (['outcome=made_up', 'module=made_up', 'page=-1'] as $query) $this->getJson('/api/admin/audit-logs?'.$query)->assertUnprocessable();
        foreach (['scope=made_up', 'include_baseline=banana'] as $query) $this->getJson('/api/admin/record-history?'.$query)->assertUnprocessable();
        foreach (['student', 'security'] as $type) {
            Sanctum::actingAs($this->user($type));
            foreach (['audit-logs', 'record-history', 'record-archives/users', 'record-archives/vehicles'] as $path) $this->getJson('/api/admin/'.$path)->assertForbidden();
        }
    }
}
