<?php

namespace Tests\Feature\Parking;

use Illuminate\Support\Facades\DB;

class AuditPriorityTest extends ParkingTestCase
{
    private function failures(int $count, bool $old = false): void
    {
        for ($i = 0; $i < $count; $i++) DB::table('login_attempts')->insert(['identifier' => 'SYNTHETIC@example.test', 'ip_address' => '192.0.2.10', 'was_successful' => false, 'attempted_at' => $old ? now()->subHour() : now()]);
    }

    public function test_priority_thresholds_and_critical_ordering(): void
    {
        $this->actingAsAdmin(); $this->failures(4);
        $this->assertEmpty(collect($this->getJson('/api/admin/audit-alerts')->assertOk()->json('data'))->where('rule', 'repeated_signin')->all());
        $this->failures(1); $r = $this->getJson('/api/admin/audit-alerts')->assertOk(); $this->assertSame(1, $r->json('summary.high'));
        $this->failures(5); $this->getJson('/api/admin/audit-alerts')->assertOk()->assertJsonPath('data.0.severity', 'critical')->assertJsonPath('summary.critical', 1);
    }

    public function test_review_retains_evidence_and_new_occurrence_requires_another_review(): void
    {
        $admin = $this->actingAsAdmin(); $this->failures(5); $a = collect($this->getJson('/api/admin/audit-alerts')->json('data'))->firstWhere('rule', 'repeated_signin');
        $attempts = DB::table('login_attempts')->count();
        $this->postJson('/api/admin/audit-alerts/'.$a['key'].'/review', ['note' => 'Confirmed repeated typing errors with the account owner.'])->assertOk();
        $this->assertSame($attempts, DB::table('login_attempts')->count());
        $this->assertDatabaseHas('audit_alert_reviews', ['alert_key' => $a['key'], 'reviewed_by' => $admin->user_id]);
        $this->getJson('/api/admin/audit-alerts')->assertOk()->assertJsonPath('summary.high', 0);
        $this->failures(1); $b = collect($this->getJson('/api/admin/audit-alerts')->json('data'))->firstWhere('rule', 'repeated_signin');
        $this->assertNotSame($a['key'], $b['key']); $this->assertNull($b['review']);
        $this->postJson('/api/admin/audit-alerts/'.$a['key'].'/review', ['note' => 'This occurrence is no longer current.'])->assertStatus(409);
        $this->assertDatabaseHas('audit_alert_reviews', ['alert_key' => $a['key']]);
    }

    public function test_old_or_successful_signins_are_not_priority_failures(): void
    {
        $this->actingAsAdmin(); $this->failures(12, true);
        DB::table('login_attempts')->insert(['identifier' => 'SYNTHETIC@example.test', 'was_successful' => true, 'attempted_at' => now()]);
        $this->assertEmpty(collect($this->getJson('/api/admin/audit-alerts')->json('data'))->where('rule', 'repeated_signin')->all());
    }

    public function test_qr_denial_alerts_do_not_expose_the_credential(): void
    {
        $this->actingAsStaff();
        for ($i = 0; $i < 3; $i++) { $this->gate('entry', ['qr_pass_code' => 'SECRET-QR-EXAMPLE']); $this->travel(11)->seconds(); }
        $this->actingAsAdmin(); $r = $this->getJson('/api/admin/audit-alerts')->assertOk()->assertDontSee('SECRET-QR-EXAMPLE');
        $a = collect($r->json('data'))->firstWhere('rule', 'repeated_denial'); $this->assertNotNull($a); $this->assertSame('medium', $a['severity']);
        $this->assertSame(0, DB::table('parking_transactions')->count());
    }

    public function test_archiving_is_not_a_physical_deletion_alert(): void
    {
        $u = $this->user(); $this->actingAsAdmin();
        $this->postJson("/api/admin/record-archives/users/{$u->user_id}/archive", ['reason' => 'Retired test account.'])->assertOk();
        $this->assertEmpty(collect($this->getJson('/api/admin/audit-alerts')->json('data'))->where('rule', 'physical_record_change')->all());
        DB::table('users')->where('user_id', $u->user_id)->delete();
        $this->assertNotEmpty(collect($this->getJson('/api/admin/audit-alerts')->json('data'))->where('rule', 'physical_record_change')->all());
    }
}
