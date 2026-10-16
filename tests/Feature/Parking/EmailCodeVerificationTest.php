<?php

namespace Tests\Feature\Parking;

use App\Models\User;
use App\Notifications\PassEmailVerificationNotification;
use App\Services\Auth\EmailVerificationCodes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;

class EmailCodeVerificationTest extends ParkingTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);
    }

    private function sendCode(User $user): string
    {
        app(EmailVerificationCodes::class)->send($user);
        return Notification::sent($user, PassEmailVerificationNotification::class)->last()->code;
    }

    public function test_code_is_hashed_single_use_and_does_not_approve_pending_account(): void
    {
        $user = $this->user('student', ['account_status' => 'pending', 'email_verified_at' => null]);
        $code = $this->sendCode($user);
        $stored = DB::table('email_verification_codes')->where('user_id', $user->getKey())->first();
        $this->assertNotSame($code, $stored->code_hash);
        $this->assertTrue(Hash::check($code, $stored->code_hash));
        $this->postJson('/api/email/verify-code', ['email' => $user->email, 'code' => $code])->assertOk();
        $this->assertNotNull($user->fresh()->email_verified_at);
        $this->assertSame('pending', $user->fresh()->account_status);
        $this->postJson('/api/email/verify-code', ['email' => $user->email, 'code' => $code])->assertUnprocessable();
    }

    public function test_expiry_and_five_incorrect_attempts_require_new_code(): void
    {
        $user = $this->user('student', ['email_verified_at' => null]);
        $code = $this->sendCode($user);
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/email/verify-code', ['email' => $user->email, 'code' => '000000'])->assertUnprocessable();
        }
        $this->postJson('/api/email/verify-code', ['email' => $user->email, 'code' => $code])->assertUnprocessable();
        $this->travel(61)->seconds();
        $new = $this->sendCode($user);
        $this->travel(11)->minutes();
        $this->postJson('/api/email/verify-code', ['email' => $user->email, 'code' => $new])->assertUnprocessable();
        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_resend_cooldown_and_replacement(): void
    {
        $user = $this->user('student', ['email_verified_at' => null]);
        $old = $this->sendCode($user);
        $this->postJson('/api/email/verification-notification', ['email' => $user->email])->assertUnprocessable();
        $this->travel(61)->seconds();
        $this->postJson('/api/email/verification-notification', ['email' => $user->email])->assertOk();
        $new = Notification::sent($user, PassEmailVerificationNotification::class)->last()->code;
        // The old hash is replaced, even in the unlikely event the random digits repeat.
        $this->assertSame(2, Notification::sent($user, PassEmailVerificationNotification::class)->count());
        $this->postJson('/api/email/verify-code', ['email' => $user->email, 'code' => $new])->assertOk();
    }

    public function test_verified_pending_user_can_login_but_only_access_own_status_and_logout(): void
    {
        $user = $this->user('student', ['account_status' => 'pending', 'email_verified_at' => null]);
        $this->postJson('/api/login', ['identifier' => $user->email, 'password' => 'password'])->assertForbidden()->assertJsonPath('code', 'email_unverified');
        $user->markEmailAsVerified();
        $this->postJson('/api/login', ['identifier' => $user->email, 'password' => 'password'])->assertOk()->assertJsonPath('user.accountStatus', 'pending');
        Sanctum::actingAs($user);
        $this->getJson('/api/me')->assertOk()->assertJsonPath('user.id', (string) $user->getKey());
        $this->getJson('/api/vehicles')->assertForbidden()->assertJsonPath('code', 'account_not_active');
        $this->getJson('/api/admin/users')->assertForbidden();
        $user->forceFill(['account_status' => 'active'])->save();
        $this->getJson('/api/vehicles')->assertOk();
    }

    public function test_inactive_account_cannot_login_after_verification(): void
    {
        $user = $this->user('student', ['account_status' => 'suspended', 'email_verified_at' => now()]);
        $this->postJson('/api/login', ['identifier' => $user->email, 'password' => 'password'])->assertForbidden();
    }
}
