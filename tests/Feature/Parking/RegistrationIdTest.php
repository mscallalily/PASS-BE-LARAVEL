<?php

namespace Tests\Feature\Parking;

use App\Models\Department;
use App\Services\Registration\InstitutionalIdReader;
use App\Services\Registration\InstitutionalIdReceipt;
use App\Services\Registration\InstitutionalIdRules;
use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

// Legacy ID-reader/profile compatibility only. New signup contract: RegistrationSessionTest.
class RegistrationIdTest extends ParkingTestCase
{
    private string $department;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        Storage::fake('private');
        Notification::fake();
        CarbonImmutable::setTestNow('2026-10-19 02:00:00');
        $d = Department::updateOrCreate(['department_code' => 'QA-COL'], ['department_name' => 'QA College Department', 'is_active' => true]);
        $this->department = $d->department_name;
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    private function evidence(array $overrides = []): array
    {
        return $overrides + ['kind' => 'image', 'engine_available' => true, 'confidence' => 95, 'width' => 1000, 'height' => 1600, 'sharpness' => 200,
            'school_marker' => true, 'fields' => ['first_name' => 'Juan Miguel', 'last_name' => 'Dela Cruz', 'middle_initial' => 'S.', 'user_code' => '2024-01234'],
            'field_confidence' => ['first_name' => 95, 'last_name' => 95, 'user_code' => 95]];
    }

    private function payload(string $category = 'college', array $changes = []): array
    {
        return $changes + ($category === 'shs' ? ['year_level' => '12', 'section' => 'QA SECTION'] : []) + ['registration_category' => $category, 'first_name' => 'Juan Miguel', 'last_name' => 'Dela Cruz', 'middle_initial' => 'S.',
            'user_code' => $category === 'employee' ? '0221234' : '202401234', 'email' => 'registration-qa@example.test',
            'password' => 'Password2026!', 'password_confirmation' => 'Password2026!', 'department' => $this->department,
            'contact_number' => '09170000000', 'agree' => true, 'school_id_file' => UploadedFile::fake()->image('id.png', 1000, 1600)];
    }

    private function withReceipt(array $payload, ?array $evidence = null): array
    {
        $payload['id_check_receipt'] = app(InstitutionalIdReceipt::class)->issue($payload['school_id_file'], $payload['registration_category'], $evidence ?? $this->evidence());

        return $payload;
    }

    public function test_public_reader_returns_suggestions_and_bound_receipt_without_creating_account_or_storing_upload(): void
    {
        $this->mock(InstitutionalIdReader::class)->shouldReceive('read')->once()->andReturn($this->evidence());
        $data = $this->payload();
        $before = DB::table('users')->count();
        $response = $this->post('/api/registration/id-check', ['registration_category' => 'college', 'school_id_file' => $data['school_id_file']], ['Accept' => 'application/json'])->assertOk()->assertJsonPath('data.fields.last_name', 'DELA CRUZ')->assertJsonPath('data.status', 'matched');
        $this->assertNotEmpty($response->json('data.receipt'));
        $this->assertStringNotContainsString('Juan', $response->json('data.receipt'));
        $this->assertDatabaseCount('users', $before);
        $this->assertSame([], Storage::disk('private')->allFiles());
    }

    public function test_cached_evaluation_rechecks_edits_without_running_ocr_again(): void
    {
        $data = $this->withReceipt($this->payload());
        $this->mock(InstitutionalIdReader::class)->shouldNotReceive('read');
        $this->post('/api/registration/id-check', $data, ['Accept' => 'application/json'])->assertOk()->assertJsonPath('data.status', 'matched');
        $data['first_name'] = 'Someone Else';
        $this->post('/api/registration/id-check', $data, ['Accept' => 'application/json'])->assertOk()->assertJsonPath('data.status', 'needs_correction');
    }

    public function test_database_rejects_separator_aliases_for_existing_institutional_ids(): void
    {
        $this->user(overrides:['user_code'=>'2024-09876']);
        $this->expectException(\Illuminate\Database\UniqueConstraintViolationException::class);
        $this->user(overrides:['user_code'=>'2024 09876']);
    }

    public function test_manila_year_rollover_uses_current_year_not_hard_coded_2026(): void
    {
        CarbonImmutable::setTestNow('2026-12-31 16:05:00 UTC');
        $this->getJson('/api/registration/options')->assertOk()->assertJsonPath('current_year', 2027);
        $this->assertNull(InstitutionalIdRules::idProblem('employee', '0271234'));
        $this->assertNull(InstitutionalIdRules::idProblem('college', '202701234'));
    }

    public function test_shs_reader_preserves_compound_name_and_lrn_without_exposing_internal_checks(): void
    {
        $e = $this->evidence(['shs_marker' => true]);
        $e['fields'] = ['first_name' => 'Alyssa Beatrice', 'last_name' => 'Dela Cruz', 'middle_initial' => 'P.', 'user_code' => '000123456789'];
        $this->mock(InstitutionalIdReader::class)->shouldReceive('read')->once()->withArgs(fn ($path, $mime, $category) => $category === 'shs')->andReturn($e);
        $file = UploadedFile::fake()->image('shs.png', 1000, 1600);
        $before = DB::table('users')->count();
        $r = $this->post('/api/registration/id-check', ['registration_category' => 'shs', 'school_id_file' => $file], ['Accept' => 'application/json'])
            ->assertOk()->assertJsonPath('data.status', 'matched')->assertJsonPath('data.fields.first_name', 'ALYSSA BEATRICE')
            ->assertJsonPath('data.fields.middle_initial', 'P.')->assertJsonPath('data.fields.user_code', '000123456789');
        $this->assertArrayNotHasKey('checks', $r->json('data'));
        $this->assertArrayNotHasKey('field_confidence', $r->json('data'));
        $this->assertArrayNotHasKey('reasons', $r->json('data'));
        $this->assertNotEmpty($r->json('data.receipt'));
        $this->assertDatabaseCount('users', $before);
        $this->assertSame([], Storage::disk('private')->allFiles());
    }

    public function test_shs_academic_profile_updates_are_scoped_validated_and_preserved_in_history(): void
    {
        $user = $this->user();
        $user->profile()->create(['first_name' => 'QA', 'last_name' => 'STUDENT', 'registration_category' => 'shs', 'year_level' => '11', 'section' => 'OLD SECTION', 'contact_number' => '09170000000']);
        Sanctum::actingAs($user);
        $this->getJson('/api/profile')->assertOk()->assertJsonPath('data.year_level', '11')->assertJsonPath('data.section', 'OLD SECTION');
        $this->patchJson('/api/profile', ['contact_number' => '09170000000', 'year_level' => '12', 'section' => ' new  section '])->assertOk();
        $this->assertDatabaseHas('user_profiles', ['user_id' => $user->user_id, 'year_level' => '12', 'section' => 'NEW SECTION']);
        $revision = DB::table('record_history')->where('record_type', 'user_profiles')->where('record_id', (string) $user->profile->profile_id)->where('operation', 'updated')->orderByDesc('revision_id')->first();
        $this->assertNotNull($revision);
        $this->assertSame('OLD SECTION', json_decode($revision->before_values, true)['section']);
        $this->assertSame('NEW SECTION', json_decode($revision->after_values, true)['section']);
        $this->patchJson('/api/profile', ['contact_number' => '09170000000', 'year_level' => '13', 'section' => 'BAD'])->assertUnprocessable()->assertJsonValidationErrors('year_level');
        $other = $this->user(); $other->profile()->create(['first_name' => 'OTHER', 'last_name' => 'USER', 'registration_category' => 'college']);
        Sanctum::actingAs($other);
        $this->getJson('/api/profile')->assertOk()->assertJsonPath('data.section', null);
        $this->patchJson('/api/profile', ['contact_number' => '09170000000', 'registration_category' => 'shs', 'year_level' => '12', 'section' => 'BAD'])->assertUnprocessable();
        $this->assertDatabaseHas('user_profiles', ['user_id' => $user->user_id, 'section' => 'NEW SECTION']);
        $admin = $this->actingAsAdmin(); $admin->roles()->attach(DB::table('roles')->where('role_code', 'admin')->value('role_id'), ['assigned_at' => now()]);
        $list = $this->getJson('/api/admin/users?search='.$user->email)->assertOk();
        $this->assertSame('NEW SECTION', $list->json('users.data.0.section'));
    }

}
