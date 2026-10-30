<?php

namespace Tests\Feature\Parking;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;

/**
 * Single contextual Archive/Restore action: explicit operations, status
 * preservation on restore, guest behaviour, and the User Management listing.
 */
class ArchiveRestoreStatusTest extends ParkingTestCase
{
    private function adminWithRole(): User
    {
        $admin = $this->user('admin');
        DB::table('user_roles')->insert(['user_id' => $admin->user_id, 'role_id' => DB::table('roles')->where('role_code', 'admin')->value('role_id'), 'assigned_at' => now()]);
        Sanctum::actingAs($admin);

        return $admin;
    }

    private function withRole(User $user, string $role): User
    {
        DB::table('user_roles')->insert(['user_id' => $user->user_id, 'role_id' => DB::table('roles')->where('role_code', $role)->value('role_id'), 'assigned_at' => now()]);

        return $user;
    }

    private function archive(int $id): \Illuminate\Testing\TestResponse
    {
        return $this->postJson("/api/admin/record-archives/users/{$id}/archive", ['reason' => 'Account archived for testing.']);
    }

    private function restore(int $id): \Illuminate\Testing\TestResponse
    {
        return $this->postJson("/api/admin/record-archives/users/{$id}/restore", ['reason' => 'Account restored for testing.']);
    }

    public function test_restore_keeps_a_suspension_rejection_or_pending_state(): void
    {
        $this->adminWithRole();
        foreach (['suspended', 'deactivated', 'pending'] as $status) {
            $user = $this->withRole($this->user('student', ['account_status' => $status]), 'student');
            $this->archive($user->user_id)->assertOk();
            $this->assertDatabaseHas('users', ['user_id' => $user->user_id, 'account_status' => 'deactivated', 'pre_archive_status' => $status]);
            $this->restore($user->user_id)->assertOk()->assertJson(['archived' => false, 'status' => $status]);
            $this->assertDatabaseHas('users', ['user_id' => $user->user_id, 'deleted_at' => null, 'account_status' => $status, 'pre_archive_status' => null]);
        }
    }

    public function test_previously_active_campus_account_still_needs_fresh_approval(): void
    {
        $this->adminWithRole();
        $user = $this->withRole($this->user('student'), 'student');
        $this->archive($user->user_id)->assertOk();
        $this->restore($user->user_id)->assertOk()->assertJson(['status' => 'pending']);
        $this->assertDatabaseHas('users', ['user_id' => $user->user_id, 'account_status' => 'pending']);
    }

    public function test_guest_keeps_its_status_and_pending_visit_requests_stay_manual(): void
    {
        $this->adminWithRole();
        // Guest accounts are stored with user_type "other" and the guest role (as GuestAccountController creates them).
        $guest = $this->withRole($this->user('other'), 'guest');
        $this->archive($guest->user_id)->assertOk();
        $this->restore($guest->user_id)->assertOk()->assertJson(['status' => 'active']);
        $this->assertDatabaseHas('users', ['user_id' => $guest->user_id, 'account_status' => 'active', 'deleted_at' => null]);
        // Restoring never creates or approves visitor requests or passes.
        $this->assertFalse(DB::table('visitor_requests as r')->join('visitors as v', 'v.visitor_id', '=', 'r.visitor_id')
            ->where('v.user_id', $guest->user_id)->where('r.request_status', 'approved')->exists());
        $this->assertFalse(DB::table('qr_passes')->where('user_id', $guest->user_id)->where('pass_status', 'active')->exists());
    }

    public function test_legacy_archived_row_without_recorded_status_restores_as_pending(): void
    {
        $this->adminWithRole();
        // Archived before pre_archive_status existed: soft-deleted with no recorded status.
        $user = $this->user('student');
        DB::table('users')->where('user_id', $user->user_id)->update(['account_status' => 'deactivated', 'deleted_at' => now()]);
        $this->restore($user->user_id)->assertOk();
        $this->assertDatabaseHas('users', ['user_id' => $user->user_id, 'account_status' => 'pending', 'deleted_at' => null]);
    }

    public function test_repeated_and_stale_requests_conflict_without_reversing_state(): void
    {
        $this->adminWithRole();
        $user = $this->user('student');
        $this->archive($user->user_id)->assertOk();
        $this->archive($user->user_id)->assertStatus(409)->assertJson(['message' => 'This record is already archived.']);
        $this->assertNotNull(DB::table('users')->where('user_id', $user->user_id)->value('deleted_at'));
        $this->restore($user->user_id)->assertOk();
        $this->restore($user->user_id)->assertStatus(409)->assertJson(['message' => 'This record is already active.']);
        $this->assertNull(DB::table('users')->where('user_id', $user->user_id)->value('deleted_at'));
    }

    public function test_restore_reuses_the_same_record_and_keeps_relationships(): void
    {
        $car = $this->campusVehicle();
        $admin = $this->adminWithRole();
        $before = DB::table('users')->count();
        $this->archive($car['user']->user_id)->assertOk();
        $this->restore($car['user']->user_id)->assertOk();
        $this->assertSame($before, DB::table('users')->count());
        $this->assertDatabaseHas('vehicles', ['vehicle_id' => $car['vehicle_id'], 'owner_user_id' => $car['user']->user_id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'account_restored', 'auditable_id' => $car['user']->user_id, 'user_id' => $admin->user_id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'account_archived', 'auditable_id' => $car['user']->user_id, 'user_id' => $admin->user_id]);
    }

    public function test_non_admins_cannot_archive_or_restore(): void
    {
        $target = $this->user('student');
        foreach (['security', 'student'] as $type) {
            Sanctum::actingAs($this->user($type));
            $this->archive($target->user_id)->assertForbidden();
            $this->restore($target->user_id)->assertForbidden();
        }
        $this->assertDatabaseHas('users', ['user_id' => $target->user_id, 'deleted_at' => null, 'account_status' => 'active']);
    }

    public function test_user_management_lists_archived_accounts_only_on_request(): void
    {
        $this->adminWithRole();
        $user = $this->user('student');
        $this->archive($user->user_id)->assertOk();
        $active = $this->getJson('/api/admin/users?search='.$user->user_code)->assertOk()->json('users.data');
        $this->assertSame([], $active);
        $archived = $this->getJson('/api/admin/users?archive=archived&search='.$user->user_code)->assertOk()->json('users.data');
        $this->assertCount(1, $archived);
        $this->assertNotNull($archived[0]['archivedAt']);
        $this->getJson('/api/admin/users?archive=everything')->assertStatus(422);
    }
}
