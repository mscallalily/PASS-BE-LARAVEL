<?php
namespace Tests\Feature\Parking;

use App\Services\Parking\ParkingAccessService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;

class PersonalDashboardTest extends ParkingTestCase
{
    public function test_authentication_and_regular_user_permission_are_required(): void
    {
        $this->getJson('/api/dashboard/mine')->assertUnauthorized();
        foreach (['student', 'faculty', 'employee'] as $type) {
            Sanctum::actingAs($this->user($type));
            $this->getJson('/api/dashboard/mine')->assertOk()->assertJsonPath('summary.vehicles', 0);
        }
        foreach (['admin', 'security', 'other'] as $type) {
            Sanctum::actingAs($this->user($type)); $this->getJson('/api/dashboard/mine')->assertForbidden();
        }
    }

    public function test_empty_account_cannot_inherit_another_users_data_even_with_a_query_parameter(): void
    {
        $other = $this->campusVehicle(); app(ParkingAccessService::class)->enter(['rfid_tag_uid' => $other['tag']], null);
        Sanctum::actingAs($this->user());
        $r = $this->getJson('/api/dashboard/mine?user_id='.$other['user']->getKey())->assertOk()
            ->assertJsonPath('summary.today_entries', 0)->assertJsonPath('summary.vehicles', 0)->assertJsonPath('summary.unread_notifications', 0)
            ->assertJsonPath('selected_vehicle', null)->assertJsonPath('license', null)->assertJsonCount(0, 'active_sessions')->assertJsonCount(0, 'recent_sessions');
        $this->assertStringNotContainsString($other['plate'], $r->getContent());
        $this->assertStringContainsString('no-store', $r->headers->get('Cache-Control'));
        $this->assertStringContainsString('private', $r->headers->get('Cache-Control'));
    }

    public function test_own_entries_exits_selected_vehicle_and_current_slot_reconcile(): void
    {
        $mine = $this->campusVehicle('motorcycle'); $other = $this->campusVehicle(); $access = app(ParkingAccessService::class);
        $access->enter(['rfid_tag_uid' => $mine['tag']], null); $access->exit(['rfid_tag_uid' => $mine['tag']], null);
        $entry = $access->enter(['rfid_tag_uid' => $mine['tag']], null); $access->enter(['rfid_tag_uid' => $other['tag']], null);
        Sanctum::actingAs($mine['user']);
        $r = $this->getJson('/api/dashboard/mine')->assertOk()->assertJsonPath('summary.today_entries', 2)->assertJsonPath('summary.today_exits', 1)
            ->assertJsonPath('summary.total_sessions', 2)->assertJsonPath('summary.vehicles', 1)->assertJsonPath('selected_vehicle.plate_number', $mine['plate'])
            ->assertJsonPath('selected_vehicle.rfid_assigned', true)->assertJsonPath('license.verification_status', 'verified')->assertJsonPath('license.expired', false)
            ->assertJsonCount(1, 'active_sessions')->assertJsonCount(2, 'recent_sessions')->assertJsonPath('active_sessions.0.slot_code', $entry['slot_code']);
        $this->assertStringStartsWith('M-', $r->json('active_sessions.0.slot_code'));
        foreach ([$other['plate'], 'license_number', 'api_key', 'license_image_path'] as $private) $this->assertStringNotContainsString($private, $r->getContent());
    }

    public function test_latest_authorization_expired_license_and_revoked_rfid_are_not_reported_ready(): void
    {
        $mine = $this->campusVehicle();
        DB::table('vehicle_authorizations')->insert(['vehicle_id' => $mine['vehicle_id'], 'authorization_status' => 'pending', 'requested_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
        DB::table('driver_licenses')->where('driver_id', $mine['driver_id'])->update(['expiry_date' => now()->subDay()->toDateString()]);
        DB::table('rfid_assignments')->where('vehicle_id', $mine['vehicle_id'])->update(['assignment_status' => 'revoked', 'unassigned_at' => now()]);
        Sanctum::actingAs($mine['user']);
        $this->getJson('/api/dashboard/mine')->assertOk()->assertJsonPath('summary.pending_vehicles', 1)
            ->assertJsonPath('selected_vehicle.authorization_status', 'pending')->assertJsonPath('selected_vehicle.rfid_assigned', false)->assertJsonPath('license.expired', true);
    }

    public function test_authorization_validity_dates_are_respected(): void
    {
        $mine = $this->campusVehicle(); Sanctum::actingAs($mine['user']);
        DB::table('vehicle_authorizations')->where('vehicle_id', $mine['vehicle_id'])->update(['valid_until' => now()->subDay()->toDateString()]);
        $this->getJson('/api/dashboard/mine')->assertOk()->assertJsonPath('selected_vehicle.authorization_status', 'expired');
        DB::table('vehicle_authorizations')->where('vehicle_id', $mine['vehicle_id'])->update(['valid_from' => now()->addDay()->toDateString(), 'valid_until' => now()->addYear()->toDateString()]);
        $this->getJson('/api/dashboard/mine')->assertOk()->assertJsonPath('selected_vehicle.authorization_status', 'scheduled');
    }

    public function test_notifications_are_own_in_app_only_and_are_not_marked_read_by_dashboard(): void
    {
        $mine = $this->user(); $other = $this->user();
        foreach ([[$mine->getKey(), 'in_app', 'OWN UPDATE'], [$mine->getKey(), 'email', 'MAIL ONLY'], [$other->getKey(), 'in_app', 'PRIVATE OTHER UPDATE']] as [$id, $channel, $title]) {
            DB::table('notifications')->insert(['user_id' => $id, 'channel' => $channel, 'notification_type' => 'test', 'title' => $title, 'body' => 'Test message', 'is_read' => false, 'created_at' => now(), 'updated_at' => now()]);
        }
        Sanctum::actingAs($mine);
        $r = $this->getJson('/api/dashboard/mine')->assertOk()->assertJsonPath('summary.unread_notifications', 1)->assertJsonCount(1, 'notifications')->assertJsonPath('notifications.0.title', 'OWN UPDATE');
        $this->assertStringNotContainsString('PRIVATE OTHER UPDATE', $r->getContent()); $this->assertStringNotContainsString('MAIL ONLY', $r->getContent());
        $this->assertSame(0, DB::table('notifications')->where('is_read', true)->count());
    }

    public function test_today_uses_philippine_midnight_and_actual_exit_time(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-22T00:30:00+08:00'));
        try {
            $mine = $this->campusVehicle(); $access = app(ParkingAccessService::class);
            $first = $access->enter(['rfid_tag_uid' => $mine['tag']], null); $access->exit(['rfid_tag_uid' => $mine['tag']], null);
            DB::table('parking_transactions')->where('transaction_id', $first['transaction_id'])->update(['entry_datetime' => '2026-10-21 15:59:59', 'exit_datetime' => '2026-10-21 16:05:00']);
            $second = $access->enter(['rfid_tag_uid' => $mine['tag']], null);
            DB::table('parking_transactions')->where('transaction_id', $second['transaction_id'])->update(['entry_datetime' => '2026-10-21 16:00:00']);
            Sanctum::actingAs($mine['user']);
            $this->getJson('/api/dashboard/mine')->assertOk()->assertJsonPath('campus_date', '2026-10-22')->assertJsonPath('summary.today_entries', 1)
                ->assertJsonPath('summary.today_exits', 1)->assertJsonPath('summary.total_sessions', 2);
        } finally { $this->travelBack(); }
    }

    public function test_history_stays_with_recorded_user_after_vehicle_transfer(): void
    {
        $original = $this->campusVehicle(); $next = $this->user(); app(ParkingAccessService::class)->enter(['rfid_tag_uid' => $original['tag']], null);
        DB::table('vehicles')->where('vehicle_id', $original['vehicle_id'])->update(['owner_user_id' => $next->getKey()]);
        Sanctum::actingAs($next);
        $this->getJson('/api/dashboard/mine')->assertOk()->assertJsonPath('summary.total_sessions', 0)->assertJsonCount(0, 'active_sessions')->assertJsonCount(0, 'recent_sessions');
        Sanctum::actingAs($original['user']);
        $this->getJson('/api/dashboard/mine')->assertOk()->assertJsonPath('summary.total_sessions', 1)->assertJsonCount(1, 'active_sessions')->assertJsonPath('selected_vehicle', null);
    }
}
