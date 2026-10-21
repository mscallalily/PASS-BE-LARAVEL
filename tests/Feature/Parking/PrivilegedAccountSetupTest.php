<?php

namespace Tests\Feature\Parking;

use App\Models\Role;
use App\Models\User;
use App\Notifications\PrivilegedAccountInvitation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;

class PrivilegedAccountSetupTest extends ParkingTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['app.frontend_url' => 'http://localhost:8444']);
        Notification::fake();
    }

    private function admin(): User
    {
        $this->app['auth']->forgetGuards();
        $admin = $this->user('admin');
        $admin->roles()->attach(Role::where('role_code', 'admin')->value('role_id'), ['assigned_at' => now()]);
        Sanctum::actingAs($admin);
        return $admin;
    }

    private function invitation(string $role = 'parking_staff'): array
    {
        $admin = $this->admin();
        $key = strtoupper(bin2hex(random_bytes(4)));
        $data = ['first_name' => 'MARIA ANGELICA', 'last_name' => 'DELA CRUZ', 'email' => strtolower($key).'@pass.test', 'user_code' => 'INV-'.$key, 'role' => $role];
        $response = $this->postJson('/api/admin/account-invitations', $data)->assertCreated()->assertJsonPath('invitation_sent', true);
        $user = User::findOrFail($response->json('user.id'));
        $notification = Notification::sent($user, PrivilegedAccountInvitation::class)->last();
        return [$admin, $user, $notification, $response];
    }

    private function signIn(User $user, string $password): string
    {
        // Clear Sanctum's fake guard so later requests use the returned real bearer token.
        $this->app['auth']->forgetGuards();
        $token = $this->postJson('/api/login', ['identifier' => $user->email, 'password' => $password])->assertOk()->json('token');
        $this->app['auth']->forgetGuards();
        $this->withHeader('Authorization', 'Bearer '.$token);
        return $token;
    }

    public function test_admin_invites_both_roles_with_generated_hashed_secrets_and_no_secret_response(): void
    {
        foreach (['admin', 'parking_staff'] as $role) {
            [, $user, $mail, $response] = $this->invitation($role);
            $this->assertNull($user->email_verified_at);
            $this->assertTrue(Hash::check($mail->temporaryPassword, $user->password));
            $this->assertMatchesRegularExpression('/^[A-Za-z0-9]{24}$/D', $mail->temporaryPassword);
            $record = DB::table('privileged_account_invitations')->where('user_id', $user->getKey())->first();
            $this->assertTrue(Hash::check($mail->code, $record->code_hash));
            $this->assertNotSame($mail->code, $record->code_hash);
            $this->assertSame($role === 'admin' ? 'admin' : 'security', $user->roles->first()->role_code);
            $this->assertSame('MARIA ANGELICA', $user->profile->first_name);
            $this->assertStringNotContainsString($mail->temporaryPassword, $response->getContent());
            $this->assertStringNotContainsString($mail->code, $response->getContent());
            $audit = json_encode(DB::table('audit_logs')->where('auditable_id', $user->getKey())->get());
            $this->assertStringNotContainsString($mail->temporaryPassword, $audit);
            $this->assertStringNotContainsString($mail->code, $audit);
            $this->assertSame('http://localhost:8444/login', $mail->loginUrl);
        }
    }

    public function test_setup_session_cannot_access_dashboard_apis_before_or_after_email_activation(): void
    {
        [, $user, $mail] = $this->invitation('admin');
        $token = $this->signIn($user, $mail->temporaryPassword);
        $this->assertSame(['account:setup', 'setup-generation:'.DB::table('privileged_account_invitations')->where('user_id', $user->getKey())->value('generation')], $user->tokens()->first()->abilities);
        $this->getJson('/api/me')->assertOk()->assertJsonPath('user.accountSetup.required', true);
        foreach (['/api/admin/users', '/api/vehicles', '/api/parking/dashboard', '/api/sync/revision'] as $url) {
            $this->getJson($url)->assertForbidden()->assertJsonPath('code', 'account_setup_required');
        }
        $this->postJson('/api/account/setup/complete', ['password' => 'NewSecurePassword2026!', 'password_confirmation' => 'NewSecurePassword2026!'])->assertForbidden();
        $this->postJson('/api/account/setup/verify', ['code' => $mail->code])->assertOk()->assertJsonPath('accountSetup.step', 'change_password');
        $this->assertNotNull($user->fresh()->email_verified_at);
        $this->getJson('/api/admin/users')->assertForbidden()->assertJsonPath('code', 'account_setup_required');
    }

    public function test_complete_rotates_session_blocks_old_password_and_grants_only_assigned_role(): void
    {
        foreach (['admin', 'parking_staff'] as $role) {
            [, $user, $mail] = $this->invitation($role);
            $oldToken = $this->signIn($user, $mail->temporaryPassword);
            $this->postJson('/api/account/setup/verify', ['code' => $mail->code])->assertOk();
            $response = $this->postJson('/api/account/setup/complete', ['password' => 'NewSecurePassword2026!', 'password_confirmation' => 'NewSecurePassword2026!'])->assertOk();
            $this->app['auth']->forgetGuards();
            $this->withHeader('Authorization', 'Bearer '.$oldToken)->getJson('/api/me')->assertUnauthorized();
            $this->withHeader('Authorization', 'Bearer '.$response->json('token'));
            $this->getJson('/api/me')->assertOk()->assertJsonPath('user.accountSetup.required', false);
            $this->getJson('/api/parking/dashboard')->assertOk();
            if ($role === 'admin') $this->getJson('/api/admin/users')->assertOk();
            else $this->postJson('/api/admin/account-invitations', [])->assertForbidden();
            $this->app['auth']->forgetGuards();
            $this->postJson('/api/login', ['identifier' => $user->email, 'password' => $mail->temporaryPassword])->assertUnauthorized();
            $this->signIn($user, 'NewSecurePassword2026!');
        }
    }

    public function test_code_attempts_commit_and_five_failures_block_even_the_correct_code(): void
    {
        [, $user, $mail] = $this->invitation();
        $this->signIn($user, $mail->temporaryPassword);
        $wrong = $mail->code === '000000' ? '111111' : '000000';
        for ($i = 0; $i < 5; $i++) $this->postJson('/api/account/setup/verify', ['code' => $wrong])->assertUnprocessable();
        $this->assertSame(5, DB::table('privileged_account_invitations')->where('user_id', $user->getKey())->value('attempts'));
        $this->postJson('/api/account/setup/verify', ['code' => $mail->code])->assertUnprocessable();
        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_code_is_single_use_and_expires_then_authenticated_resend_replaces_it(): void
    {
        [, $user, $mail] = $this->invitation();
        $this->signIn($user, $mail->temporaryPassword);
        $this->postJson('/api/account/setup/resend-code')->assertUnprocessable();
        $this->travel(11)->minutes();
        $this->postJson('/api/account/setup/verify', ['code' => $mail->code])->assertUnprocessable();
        $this->postJson('/api/account/setup/resend-code')->assertOk();
        $new = Notification::sent($user, PrivilegedAccountInvitation::class)->last();
        $this->assertNull($new->temporaryPassword);
        $this->postJson('/api/account/setup/verify', ['code' => $new->code])->assertOk();
        $this->postJson('/api/account/setup/verify', ['code' => $new->code])->assertUnprocessable();
    }

    public function test_temporary_and_weak_or_overlong_passwords_cannot_complete_setup(): void
    {
        [, $user, $mail] = $this->invitation();
        $this->signIn($user, $mail->temporaryPassword);
        $this->postJson('/api/account/setup/verify', ['code' => $mail->code])->assertOk();
        foreach ([$mail->temporaryPassword, 'short', 'ALLUPPERCASE123', 'alllowercase123', str_repeat('é', 35).'Aa123'] as $password) {
            $this->postJson('/api/account/setup/complete', ['password' => $password, 'password_confirmation' => $password])->assertUnprocessable();
        }
        $this->assertNull(DB::table('privileged_account_invitations')->where('user_id', $user->getKey())->value('completed_at'));
    }

    public function test_admin_resend_rotates_password_code_generation_and_revokes_setup_tokens(): void
    {
        [$admin, $user, $mail] = $this->invitation();
        $old = $this->signIn($user, $mail->temporaryPassword);
        $this->travel(61)->seconds();
        $this->app['auth']->forgetGuards(); Sanctum::actingAs($admin);
        $this->postJson('/api/admin/account-invitations/'.$user->getKey().'/resend')->assertOk();
        $new = Notification::sent($user, PrivilegedAccountInvitation::class)->last();
        $this->assertFalse(Hash::check($mail->temporaryPassword, $user->fresh()->password));
        $this->assertTrue(Hash::check($new->temporaryPassword, $user->fresh()->password));
        $this->app['auth']->forgetGuards();
        $this->withHeader('Authorization', 'Bearer '.$old)->getJson('/api/me')->assertUnauthorized();
        $this->signIn($user, $new->temporaryPassword);
        $this->postJson('/api/account/setup/verify', ['code' => $mail->code])->assertUnprocessable();
        $this->postJson('/api/account/setup/verify', ['code' => $new->code])->assertOk();
    }

    public function test_mail_failure_preserves_incomplete_account_and_never_claims_delivery(): void
    {
        $admin = $this->admin();
        Notification::shouldReceive('sendNow')->once()->andThrow(new \RuntimeException('Synthetic SMTP failure'));
        $response = $this->postJson('/api/admin/account-invitations', ['first_name' => 'Test', 'last_name' => 'Staff', 'email' => 'delivery-failure@pass.test', 'user_code' => 'MAIL-FAIL', 'role' => 'parking_staff'])
            ->assertCreated()->assertJsonPath('invitation_sent', false)->assertJsonPath('accountSetup.delivery', 'failed');
        $user = User::findOrFail($response->json('user.id'));
        $this->assertNull($user->email_verified_at);
        $this->assertSame(0, $user->tokens()->count());
    }

    public function test_expired_or_deactivated_invitation_cannot_get_a_setup_session(): void
    {
        [, $user, $mail] = $this->invitation();
        $this->travel(25)->hours(); $this->app['auth']->forgetGuards();
        $this->postJson('/api/login', ['identifier' => $user->email, 'password' => $mail->temporaryPassword])->assertForbidden()->assertJsonPath('code', 'invitation_expired');
        $this->travelBack();
        $user->forceFill(['account_status' => 'deactivated'])->save();
        $this->postJson('/api/login', ['identifier' => $user->email, 'password' => $mail->temporaryPassword])->assertForbidden()->assertJsonPath('code', 'account_not_active');
    }

    public function test_staff_students_and_pending_invited_admin_cannot_create_accounts_or_choose_privilege_payload(): void
    {
        foreach (['security', 'student', 'employee'] as $type) {
            $this->app['auth']->forgetGuards(); Sanctum::actingAs($this->user($type));
            $this->postJson('/api/admin/account-invitations', [])->assertForbidden();
        }
        [, $user, $mail] = $this->invitation('admin');
        $this->signIn($user, $mail->temporaryPassword);
        $this->postJson('/api/admin/account-invitations', [])->assertForbidden()->assertJsonPath('code', 'account_setup_required');
        $this->admin();
        foreach (['password', 'account_status', 'email_verified_at', 'roles', 'user_type'] as $field) {
            $this->postJson('/api/admin/account-invitations', ['first_name' => 'Test', 'last_name' => 'User', 'email' => 'safe@pass.test', 'user_code' => 'SAFE-001', 'role' => 'admin', $field => 'malicious'])->assertUnprocessable();
        }
    }

    public function test_public_email_verification_does_not_bypass_privileged_activation_code(): void
    {
        [, $user, $mail] = $this->invitation('admin');
        $user->forceFill(['email_verified_at' => now()])->save();
        $this->signIn($user, $mail->temporaryPassword);
        $this->getJson('/api/admin/users')->assertForbidden()->assertJsonPath('code', 'account_setup_required');
        $this->postJson('/api/account/setup/complete', ['password' => 'NewSecurePassword2026!', 'password_confirmation' => 'NewSecurePassword2026!'])->assertForbidden();
    }

    public function test_existing_admin_keeps_normal_login_and_cannot_resend_a_completed_invitation(): void
    {
        $admin = $this->admin();
        $this->signIn($admin, 'password');
        $this->getJson('/api/admin/users')->assertOk();
        [$admin, $user, $mail] = $this->invitation('admin');
        $this->signIn($user, $mail->temporaryPassword);
        $this->postJson('/api/account/setup/verify', ['code' => $mail->code])->assertOk();
        $this->postJson('/api/account/setup/complete', ['password' => 'NewSecurePassword2026!', 'password_confirmation' => 'NewSecurePassword2026!'])->assertOk();
        $this->app['auth']->forgetGuards(); Sanctum::actingAs($admin);
        $this->postJson('/api/admin/account-invitations/'.$user->getKey().'/resend')->assertConflict();
    }
    public function test_legacy_creation_route_cannot_bypass_activation_with_manual_credentials(): void
    {
        $this->admin();
        $this->postJson('/api/admin/users', [
            'first_name' => 'LEGACY', 'last_name' => 'BYPASS', 'email' => 'bypass@example.test',
            'user_code' => 'LEGACY-BYPASS', 'role' => 'parking_staff',
            'password' => 'ChosenPassword2026!', 'password_confirmation' => 'ChosenPassword2026!',
            'email_verified_at' => now()->toIso8601String(),
        ])->assertUnprocessable()->assertJsonValidationErrors(['password', 'password_confirmation', 'email_verified_at']);
        $this->assertDatabaseMissing('users', ['email' => 'bypass@example.test']);
    }

    public function test_creation_alias_and_invitation_endpoint_share_the_same_write_budget(): void
    {
        $this->admin();
        for ($i = 0; $i < 10; $i++) {
            $this->postJson($i % 2 ? '/api/admin/users' : '/api/admin/account-invitations', [])->assertUnprocessable();
        }
        $this->postJson('/api/admin/users', [])->assertStatus(429);
        $this->postJson('/api/admin/account-invitations', [])->assertStatus(429);
    }

}
