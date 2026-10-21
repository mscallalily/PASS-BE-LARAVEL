<?php

namespace Tests\Feature\Parking;

use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

class RecordRetentionTest extends ParkingTestCase
{
    public function test_changed_business_values_are_retained_and_secrets_are_excluded(): void
    {
        $u = $this->user();
        $profile = DB::table('user_profiles')->insertGetId(['user_id' => $u->user_id, 'first_name' => 'ANGELICA PAULINE', 'last_name' => 'DELA CRUZ', 'contact_number' => '09170000001', 'created_at' => now(), 'updated_at' => now()], 'profile_id');
        DB::table('user_profiles')->where('profile_id', $profile)->update(['contact_number' => '09170000002']);
        $h = DB::table('record_history')->where('record_type', 'user_profiles')->where('record_id', $profile)->where('operation', 'updated')->first();
        $this->assertSame('09170000001', json_decode($h->before_values, true)['contact_number']);
        $this->assertSame('09170000002', json_decode($h->after_values, true)['contact_number']);
        $h = DB::table('record_history')->where('record_type', 'users')->where('record_id', $u->user_id)->first();
        $this->assertArrayNotHasKey('password', json_decode($h->after_values, true));
        $this->assertStringNotContainsString($u->password, $h->after_values);
    }

    public function test_history_cannot_be_updated_or_deleted(): void
    {
        $u = $this->user(); $h = DB::table('record_history')->where('record_type', 'users')->where('record_id', $u->user_id)->first();
        foreach (['update', 'delete'] as $action) {
            try { $q = DB::table('record_history')->where('revision_id', $h->revision_id); $action === 'update' ? $q->update(['operation' => 'forged']) : $q->delete(); $this->fail('History mutation was allowed.'); }
            catch (QueryException $e) { $this->assertStringContainsString('append-only', $e->getMessage()); }
        }
        $this->assertDatabaseHas('record_history', ['revision_id' => $h->revision_id, 'operation' => 'created']);
    }

    public function test_physical_deletion_retains_prior_account_and_child_profile_snapshot(): void
    {
        $u = $this->user(); $p = DB::table('user_profiles')->insertGetId(['user_id' => $u->user_id, 'first_name' => 'PRIOR', 'last_name' => 'NAME', 'created_at' => now(), 'updated_at' => now()], 'profile_id');
        DB::table('users')->where('user_id', $u->user_id)->delete();
        $this->assertDatabaseHas('record_history', ['record_type' => 'users', 'record_id' => (string) $u->user_id, 'operation' => 'deleted']);
        $this->assertDatabaseHas('record_history', ['record_type' => 'user_profiles', 'record_id' => (string) $p, 'operation' => 'created']);
    }

    public function test_account_archive_and_restore_keep_transactions_and_revoke_old_credentials(): void
    {
        $car = $this->campusVehicle(); $staff = $this->actingAsStaff();
        $this->assertSame('granted', $this->gate('entry', ['rfid_tag_uid' => $car['tag']])['status']);
        $txn = $this->activeTransaction($car['vehicle_id']);
        $this->assertSame('completed', $this->gate('exit', ['rfid_tag_uid' => $car['tag']])['status']);
        $u = $car['user']; $u->createToken('before-archive'); $admin = $this->actingAsAdmin();
        $this->postJson("/api/admin/record-archives/users/{$u->user_id}/archive", ['reason' => 'Graduated from the college.'])->assertOk();
        $this->assertDatabaseHas('parking_transactions', ['transaction_id' => $txn->transaction_id, 'transaction_status' => 'completed']);
        $this->assertDatabaseHas('rfid_assignments', ['vehicle_id' => $car['vehicle_id'], 'assignment_status' => 'revoked']);
        $this->assertFalse(DB::table('personal_access_tokens')->where('tokenable_id', $u->user_id)->exists());
        $this->getJson('/api/admin/record-archives/users')->assertOk()->assertJsonFragment(['identifier' => $u->user_code]);
        $this->postJson("/api/admin/record-archives/users/{$u->user_id}/restore", ['reason' => 'Returning student reapplication.'])->assertOk();
        $this->assertDatabaseHas('users', ['user_id' => $u->user_id, 'user_code' => $u->user_code, 'deleted_at' => null, 'account_status' => 'pending']);
        $this->assertDatabaseHas('rfid_tags', ['tag_uid' => $car['tag'], 'tag_status' => 'deactivated']);
        $this->assertDatabaseHas('record_history', ['record_type' => 'users', 'record_id' => (string) $u->user_id, 'operation' => 'archived', 'actor_user_id' => $admin->user_id]);
        $context = DB::selectOne('SELECT @pass_history_actor as actor, @pass_history_label as label');
        $this->assertNull($context->actor); $this->assertNull($context->label);
    }

    public function test_archive_is_blocked_while_vehicle_is_inside(): void
    {
        $car = $this->campusVehicle(); $this->actingAsStaff(); $this->gate('entry', ['rfid_tag_uid' => $car['tag']]); $this->actingAsAdmin();
        foreach (['users/'.$car['user']->user_id, 'vehicles/'.$car['vehicle_id']] as $record) $this->postJson('/api/admin/record-archives/'.$record.'/archive', ['reason' => 'Requested record retirement.'])->assertStatus(409);
        $this->assertDatabaseHas('users', ['user_id' => $car['user']->user_id, 'deleted_at' => null]);
        $this->assertDatabaseHas('vehicles', ['vehicle_id' => $car['vehicle_id'], 'deleted_at' => null]);
        $this->assertNotNull($this->activeTransaction($car['vehicle_id']));
        $this->assertDatabaseHas('rfid_assignments', ['vehicle_id' => $car['vehicle_id'], 'assignment_status' => 'active']);
    }

    public function test_vehicle_restore_keeps_plate_and_requires_fresh_authorization(): void
    {
        $car = $this->campusVehicle(); $this->actingAsAdmin(); $base = '/api/admin/record-archives/vehicles/'.$car['vehicle_id'];
        $this->postJson($base.'/archive', ['reason' => 'Vehicle was retired.'])->assertOk();
        $this->postJson($base.'/archive', ['reason' => 'Vehicle was retired.'])->assertStatus(409);
        $this->postJson($base.'/restore', ['reason' => 'Vehicle is in service again.'])->assertOk();
        $this->assertDatabaseHas('vehicles', ['vehicle_id' => $car['vehicle_id'], 'plate_number' => $car['plate'], 'deleted_at' => null]);
        $this->assertDatabaseHas('vehicle_authorizations', ['vehicle_id' => $car['vehicle_id'], 'authorization_status' => 'pending']);
        $this->assertDatabaseHas('rfid_assignments', ['vehicle_id' => $car['vehicle_id'], 'assignment_status' => 'revoked']);
    }

    public function test_admin_cannot_archive_self_and_reason_cannot_be_blank(): void
    {
        $admin = $this->actingAsAdmin(); $u = $this->user();
        $this->postJson("/api/admin/record-archives/users/{$admin->user_id}/archive", ['reason' => 'Close my own account.'])->assertStatus(409);
        $this->postJson("/api/admin/record-archives/users/{$u->user_id}/archive", ['reason' => '       '])->assertStatus(422);
    }

    public function test_regular_users_and_staff_cannot_access_retention_or_priority_controls(): void
    {
        foreach (['student', 'security', 'employee', 'faculty'] as $type) {
            Sanctum::actingAs($this->user($type));
            foreach (['record-history', 'record-history/1/documents/vehicle_photo_path?side=before', 'record-archives/users', 'record-archives/vehicles', 'audit-alerts', 'parking-layout/export'] as $uri) $this->getJson('/api/admin/'.$uri)->assertForbidden();
            $this->postJson('/api/admin/record-archives/users/1/archive', ['reason' => 'Not an administrator.'])->assertForbidden();
            $this->postJson('/api/admin/audit-alerts/'.str_repeat('a', 64).'/review', ['note' => 'Not an administrator.'])->assertForbidden();
        }
    }

    public function test_replaced_vehicle_photo_remains_private_and_history_download_is_admin_only(): void
    {
        Storage::fake('private'); $car = $this->campusVehicle();
        $car['user']->roles()->attach(DB::table('roles')->where('role_code', 'student')->value('role_id'), ['assigned_at' => now()]);
        $old = 'vehicle-photos/old.png'; Storage::disk('private')->put($old, UploadedFile::fake()->image('old.png')->getContent());
        DB::table('vehicles')->where('vehicle_id', $car['vehicle_id'])->update(['vehicle_photo_path' => $old]);
        DB::table('vehicle_authorizations')->where('vehicle_id', $car['vehicle_id'])->update(['authorization_status' => 'pending']);
        Sanctum::actingAs($car['user']);
        $this->post('/api/vehicles/'.$car['vehicle_id'].'/photo', ['vehicle_photo' => UploadedFile::fake()->image('new.png')], ['Accept' => 'application/json'])->assertOk();
        Storage::disk('private')->assertExists($old);
        $h = DB::table('record_history')->where('record_type', 'vehicles')->where('record_id', $car['vehicle_id'])->orderByDesc('revision_id')->first();
        $this->assertSame($old, json_decode($h->before_values, true)['vehicle_photo_path']);
        $url = '/api/admin/record-history/'.$h->revision_id.'/documents/vehicle_photo_path?side=before';
        $this->getJson($url)->assertForbidden(); $this->actingAsAdmin();
        $this->getJson('/api/admin/record-history?record_type=vehicles&record_id='.$car['vehicle_id'])->assertOk()->assertDontSee($old)->assertDontSee('password');
        $this->get($url)->assertOk()->assertHeader('Cache-Control', 'no-store, private');
    }
    public function test_archive_revokes_temporary_qr_pass_without_removing_its_record(): void
    {
        $car = $this->campusVehicle();
        $pass = $this->rawPass(['user_id' => $car['user']->user_id, 'vehicle_id' => $car['vehicle_id'], 'pass_type' => 'user_temporary']);
        $this->actingAsAdmin();
        $this->postJson('/api/admin/record-archives/users/'.$car['user']->user_id.'/archive', ['reason' => 'The account is being retired.'])->assertOk();
        $this->assertDatabaseHas('qr_passes', ['pass_code' => $pass, 'pass_status' => 'revoked']);
        $history = DB::table('record_history')->where('record_type', 'qr_passes')->orderByDesc('revision_id')->first();
        $this->assertStringNotContainsString($pass, $history->after_values);
        $this->assertArrayNotHasKey('token_hash', json_decode($history->after_values, true));
    }

}
