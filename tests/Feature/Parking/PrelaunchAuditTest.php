<?php

namespace Tests\Feature\Parking;

use App\Models\User;
use App\Services\Parking\ParkingAccessService;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;

class PrelaunchAuditTest extends ParkingTestCase
{
    private function admin(): User
    {
        $user = $this->actingAsAdmin();
        $user->roles()->attach(DB::table('roles')->where('role_code', 'admin')->value('role_id'), ['assigned_at' => now()]);
        return $user;
    }

    public function test_recovery_generates_a_real_trusted_frontend_link(): void
    {
        Notification::fake();
        config(['app.frontend_url' => 'https://pass.example.test']);
        $user = $this->user();
        $this->postJson('/api/forgot-password', ['email' => $user->email])->assertOk();
        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
            $url = $notification->toMail($user)->actionUrl;
            $this->assertStringStartsWith('https://pass.example.test/reset-password?', $url);
            $this->assertStringContainsString('token=', $url);
            return true;
        });
    }

    public function test_legacy_add_user_route_uses_invitation_and_requires_activation(): void
    {
        Notification::fake();
        config(['app.frontend_url' => 'http://localhost:8444']);
        $this->admin();
        $this->postJson('/api/admin/users', [
            'first_name' => 'AUDIT PARKING', 'last_name' => 'STAFF',
            'email' => 'audit-staff@example.test', 'user_code' => 'SEC-AUDIT-001',
            'contact_number' => '09170000000', 'role' => 'parking_staff',
        ])->assertCreated()->assertJsonPath('accountSetup.required', true);
        $created = User::where('email', 'audit-staff@example.test')->firstOrFail();
        $this->assertSame('security', $created->user_type);
        $this->assertNull($created->email_verified_at);
        $this->assertFalse($created->isAdmin());
        $this->assertTrue($created->roles()->where('role_code', 'security')->exists());
        $this->assertDatabaseHas('privileged_account_invitations', ['user_id' => $created->user_id, 'completed_at' => null]);
    }

    public function test_profile_contact_is_persisted_and_identity_cannot_be_changed(): void
    {
        $user = $this->user();
        $user->profile()->create(['first_name' => 'AUDIT', 'last_name' => 'USER']);
        Sanctum::actingAs($user);
        $this->getJson('/api/profile')->assertOk();
        $this->patchJson('/api/profile', ['contact_number' => '09171234567'])->assertOk();
        $this->assertDatabaseHas('user_profiles', ['user_id' => $user->user_id, 'contact_number' => '09171234567']);
        $this->patchJson('/api/profile', ['contact_number' => '09171234567', 'email' => 'hijack@example.test', 'account_status' => 'active'])->assertUnprocessable();
        $this->assertNotSame('hijack@example.test', $user->fresh()->email);
    }

    public function test_history_is_real_owned_data_not_other_users_history(): void
    {
        $a = $this->campusVehicle(); $b = $this->campusVehicle();
        $service = app(ParkingAccessService::class);
        $service->enter(['rfid_tag_uid' => $a['tag']], null);
        $service->enter(['rfid_tag_uid' => $b['tag']], null);
        Sanctum::actingAs($a['user']);
        $this->getJson('/api/parking/history')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.plate_number', $a['plate']);
    }

    public function test_sync_polls_do_not_consume_rfid_write_budget(): void
    {
        $this->admin();
        for ($i=0; $i<22; $i++) $this->getJson('/api/sync/revision')->assertOk();
        $this->postJson('/api/rfid/assignments', [])->assertUnprocessable();
    }

    public function test_revocation_between_identification_and_area_lock_cannot_admit_vehicle(): void
    {
        $fixture = $this->campusVehicle(); $revoked = false;
        DB::listen(function ($query) use (&$revoked, $fixture) {
            if (!$revoked && str_contains($query->sql, 'parking_areas') && str_contains($query->sql, 'for update')) {
                $revoked = true;
                DB::table('rfid_tags')->where('tag_uid', $fixture['tag'])->update(['tag_status' => 'deactivated']);
            }
        });
        $result = app(ParkingAccessService::class)->enter(['rfid_tag_uid' => $fixture['tag']], null);
        $this->assertTrue($revoked);
        $this->assertSame('denied', $result['status']);
        $this->assertNull($this->activeTransaction($fixture['vehicle_id']));
    }

    public function test_api_errors_remain_json_without_an_accept_header(): void
    {
        $this->get('/api/me')->assertUnauthorized()->assertHeader('Content-Type', 'application/json');
    }

    public function test_password_change_checks_current_password_and_revokes_all_sessions(): void
    {
        $user = $this->user();
        $user->createToken('device-one'); $user->createToken('device-two');
        Sanctum::actingAs($user);
        $this->postJson('/api/profile/password', ['current_password' => 'wrong-password', 'password' => 'NewPassword2026!', 'password_confirmation' => 'NewPassword2026!'])->assertUnprocessable();
        $this->assertSame(2, $user->tokens()->count());
        $this->postJson('/api/profile/password', ['current_password' => 'password', 'password' => 'NewPassword2026!', 'password_confirmation' => 'NewPassword2026!'])->assertOk();
        $this->assertSame(0, $user->tokens()->count());
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('NewPassword2026!', $user->fresh()->password));
    }

    public function test_inactive_linked_guest_cannot_enter_using_previously_approved_qr(): void
    {
        $guest = $this->user('other', ['account_status' => 'deactivated']);
        $v = $this->visitorVehicle();
        DB::table('visitors')->where('visitor_id', $v['visitor_id'])->update(['user_id' => $guest->user_id]);
        $requestId = DB::table('visitor_requests')->insertGetId(['visitor_id' => $v['visitor_id'], 'vehicle_id' => $v['vehicle_id'], 'requested_valid_from' => now()->subHour(), 'requested_valid_until' => now()->addHour(), 'request_status' => 'approved', 'created_at' => now(), 'updated_at' => now()], 'request_id');
        $code = $this->rawPass(['visitor_id' => $v['visitor_id'], 'vehicle_id' => $v['vehicle_id'], 'request_id' => $requestId]);
        $result = app(ParkingAccessService::class)->enter(['qr_pass_code' => $code], null);
        $this->assertSame('denied', $result['status']);
        $this->assertNull($this->activeTransaction($v['vehicle_id']));
    }

    public function test_visitor_cannot_register_a_campus_plate_under_another_separator(): void
    {
        $campus = $this->campusVehicle();
        DB::table('vehicles')->where('vehicle_id', $campus['vehicle_id'])->update(['plate_number' => 'ABC-9876']);
        $visitor = $this->visitorVehicle();
        $type = DB::table('vehicle_types')->where('type_code', 'sedan')->value('vehicle_type_id');
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        \App\Http\Controllers\Api\VisitorAccessController::registerVisitorVehicle($visitor['visitor_id'], 'ABC9876', $type);
    }

    public function test_oversized_login_identifiers_are_rejected_before_database_logging(): void
    {
        $this->postJson('/api/login', ['identifier' => str_repeat('x', 1000), 'password' => 'anything'])->assertUnprocessable()->assertJsonValidationErrors('identifier');
    }

    public function test_unrecognized_exit_is_preserved_in_the_security_audit(): void
    {
        $result = app(ParkingAccessService::class)->exit(['rfid_tag_uid' => 'AUDIT-UNKNOWN-EXIT'], null);
        $this->assertSame('not_found', $result['status']);
        $this->assertDatabaseHas('access_events', ['direction' => 'out', 'auth_result' => 'denied']);
        $this->assertDatabaseCount('parking_transactions', 0);
    }

    public function test_multibyte_passwords_cannot_exceed_bcrypt_byte_limit(): void
    {
        Sanctum::actingAs($this->user());
        $password = str_repeat('é', 40).'123';
        $this->postJson('/api/profile/password', ['current_password' => 'password', 'password' => $password, 'password_confirmation' => $password])->assertUnprocessable()->assertJsonValidationErrors('password');
    }

    public function test_guest_password_array_is_a_validation_error_not_a_server_error(): void
    {
        $this->postJson('/api/guest/register', ['full_name' => 'AUDIT GUEST', 'email' => 'invalid-password@pass.test', 'contact_number' => '09170000000', 'password' => ['bad'], 'password_confirmation' => ['bad'], 'agree' => true])->assertUnprocessable()->assertJsonValidationErrors('password');
    }

    public function test_real_password_reset_is_single_use_and_revokes_existing_sessions(): void
    {
        $user = $this->user(); $user->createToken('existing-session');
        $token = \Illuminate\Support\Facades\Password::broker()->createToken($user);
        $payload = ['token' => $token, 'email' => $user->email, 'password' => 'ResetPassword2026!', 'password_confirmation' => 'ResetPassword2026!'];
        $this->postJson('/api/reset-password', $payload)->assertOk();
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('ResetPassword2026!', $user->fresh()->password));
        $this->assertSame(0, $user->tokens()->count());
        $this->assertDatabaseHas('audit_logs', ['action' => 'password_reset', 'auditable_id' => $user->user_id]);
        $this->postJson('/api/reset-password', $payload)->assertUnprocessable();
    }

    public function test_invalid_reset_token_does_not_change_password_or_existing_sessions(): void
    {
        $user = $this->user(); $user->createToken('existing-session');
        $this->postJson('/api/reset-password', ['token' => 'invalid-token', 'email' => $user->email, 'password' => 'ResetPassword2026!', 'password_confirmation' => 'ResetPassword2026!'])->assertUnprocessable();
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('password', $user->fresh()->password));
        $this->assertSame(1, $user->tokens()->count());
    }
}
