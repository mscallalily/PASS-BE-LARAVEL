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
        return $changes + ['registration_category' => $category, 'first_name' => 'Juan Miguel', 'last_name' => 'Dela Cruz', 'middle_initial' => 'S.',
            'user_code' => $category === 'employee' ? '0221234' : '202401234', 'email' => 'registration-qa@example.test',
            'password' => 'Password2026!', 'password_confirmation' => 'Password2026!', 'department' => $this->department,
            'contact_number' => '09170000000', 'agree' => true, 'school_id_file' => UploadedFile::fake()->image('id.png', 1000, 1600)];
    }

    private function withReceipt(array $payload, ?array $evidence = null): array
    {
        $payload['id_check_receipt'] = app(InstitutionalIdReceipt::class)->issue($payload['school_id_file'], $payload['registration_category'], $evidence ?? $this->evidence());

        return $payload;
    }

    private function register(array $payload)
    {
        return $this->post('/api/register', $payload, ['Accept' => 'application/json']);
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

    public function test_registration_is_pending_and_preserves_compound_name_initial_role_and_private_id_without_vehicle(): void
    {
        $payload = $this->withReceipt($this->payload());
        $vehicleCount = DB::table('vehicles')->count();
        $driverCount = DB::table('drivers')->count();
        $response = $this->register($payload)->assertCreated()->assertJsonPath('id_check.status', 'matched')->assertJsonPath('user.accountStatus', 'pending')->assertJsonPath('user.emailVerified', false);
        $id = (int) $response->json('user.id');
        $this->assertDatabaseHas('users', ['user_id' => $id, 'user_code' => '2024-01234', 'user_type' => 'student', 'account_status' => 'pending']);
        $this->assertDatabaseHas('user_profiles', ['user_id' => $id, 'first_name' => 'Juan Miguel', 'last_name' => 'Dela Cruz', 'middle_name' => 'S.', 'school_id_check_status' => 'matched', 'registration_category' => 'college']);
        $this->assertDatabaseCount('vehicles', $vehicleCount);
        $this->assertDatabaseCount('drivers', $driverCount);
        $this->assertDatabaseHas('email_verification_codes', ['user_id' => $id]);
        $path = DB::table('user_profiles')->where('user_id', $id)->value('school_id_document_path');
        Storage::disk('private')->assertExists($path);
        $this->assertStringNotContainsString('school-ids/', $response->getContent());
        $this->assertSame('student', DB::table('user_roles')->join('roles', 'roles.role_id', '=', 'user_roles.role_id')->where('user_id', $id)->value('role_code'));
    }

    public function test_employee_id_keeps_zero_and_can_store_an_office_not_in_college_departments(): void
    {
        $e = $this->evidence(['fields' => ['first_name' => 'Juan Miguel', 'last_name' => 'Dela Cruz', 'user_code' => '022-1234']]);
        $r = $this->register($this->withReceipt($this->payload('employee', ['department' => 'General Services', 'middle_initial' => '']), $e))->assertCreated()->assertJsonPath('user.idNum', '022-1234')->assertJsonPath('user.role', 'employee');
        $this->assertDatabaseHas('user_profiles', ['user_id' => (int) $r->json('user.id'), 'department_id' => null, 'registration_unit' => 'General Services', 'middle_name' => null]);
    }

    public function test_future_year_wrong_length_letters_and_admin_role_are_rejected_server_side(): void
    {
        foreach (['202701234', '20240123', '2024012345', '2024A1234'] as $id) {
            $this->register($this->withReceipt($this->payload(changes: ['user_code' => $id])))->assertUnprocessable()->assertJsonValidationErrors('user_code');
        }
        $this->register($this->withReceipt($this->payload(changes: ['user_type' => 'admin'])))->assertUnprocessable()->assertJsonValidationErrors('user_type');
        Cache::flush();
        $this->register($this->withReceipt($this->payload('employee', ['user_code' => '027-1234'])))->assertUnprocessable()->assertJsonValidationErrors('user_code');
    }

    public function test_duplicate_normalized_id_and_vehicle_data_cannot_bypass_new_registration(): void
    {
        $this->user(overrides: ['user_code' => '202401234']);
        $this->register($this->withReceipt($this->payload()))->assertUnprocessable()->assertJsonValidationErrors('user_code');
        $this->register($this->withReceipt($this->payload(changes: ['has_vehicle' => true, 'plate_number' => 'QA-123'])))->assertUnprocessable()->assertJsonValidationErrors(['has_vehicle', 'plate_number']);
    }

    public function test_swapped_file_category_tampered_or_expired_receipts_are_rejected(): void
    {
        $data = $this->withReceipt($this->payload());
        $swap = $data;
        $swap['school_id_file'] = UploadedFile::fake()->image('other.png', 1100, 1600);
        $this->register($swap)->assertUnprocessable()->assertJsonValidationErrors('id_check_receipt');
        $tamper = $data;
        $tamper['id_check_receipt'] .= 'tampered';
        $this->register($tamper)->assertUnprocessable()->assertJsonValidationErrors('id_check_receipt');
        $category = $data;
        $category['registration_category'] = 'employee';
        $category['user_code'] = '0221234';
        $this->register($category)->assertUnprocessable()->assertJsonValidationErrors('id_check_receipt');
        CarbonImmutable::setTestNow(now()->addMinutes(21));
        $this->register($data)->assertUnprocessable()->assertJsonValidationErrors('id_check_receipt');
    }

    public function test_edited_identity_is_rechecked_and_cannot_claim_a_client_side_match(): void
    {
        $data = $this->withReceipt($this->payload(changes: ['last_name' => 'Someone Else', 'id_check_status' => 'matched']));
        $this->register($data)->assertUnprocessable()->assertJsonValidationErrors('school_id_file');
        $this->assertDatabaseMissing('users', ['email' => $data['email']]);
        $this->assertSame([], Storage::disk('private')->allFiles());
    }

    public function test_shs_and_reader_outage_allow_only_pending_staff_review_without_invented_shs_format(): void
    {
        $shs = $this->payload('shs', ['user_code' => 'SHS-24-001', 'department' => 'Senior High School', 'middle_initial' => '']);
        $this->register($shs)->assertCreated()->assertJsonPath('id_check.status', 'manual_review')->assertJsonPath('user.accountStatus', 'pending')->assertJsonPath('user.role', 'student');
        $outage = $this->withReceipt($this->payload(changes: ['email' => 'outage@example.test']), $this->evidence(['engine_available' => false]));
        $this->register($outage)->assertCreated()->assertJsonPath('id_check.status', 'unavailable')->assertJsonPath('user.accountStatus', 'pending');
    }

    public function test_id_preview_and_check_evidence_are_admin_only(): void
    {
        $response = $this->register($this->withReceipt($this->payload()))->assertCreated();
        $id = $response->json('user.id');
        Sanctum::actingAs($this->user());
        $this->getJson("/api/admin/users/{$id}/school-id")->assertForbidden();
        $this->actingAsStaff();
        $this->getJson("/api/admin/users/{$id}/school-id")->assertForbidden();
        $admin = $this->actingAsAdmin();
        $role = DB::table('roles')->where('role_code', 'admin')->value('role_id');
        $admin->roles()->attach($role, ['assigned_at' => now()]);
        $this->getJson("/api/admin/users/{$id}/school-id")->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->getJson('/api/admin/users?search=registration-qa')->assertOk()->assertJsonPath('users.data.0.schoolIdCheck.status', 'matched');
    }

    public function test_cached_evaluation_rechecks_edits_without_running_ocr_again(): void
    {
        $data = $this->withReceipt($this->payload());
        $this->mock(InstitutionalIdReader::class)->shouldNotReceive('read');
        $this->post('/api/registration/id-check', $data, ['Accept' => 'application/json'])->assertOk()->assertJsonPath('data.status', 'matched');
        $data['first_name'] = 'Someone Else';
        $this->post('/api/registration/id-check', $data, ['Accept' => 'application/json'])->assertOk()->assertJsonPath('data.status', 'needs_correction');
    }

    public function test_low_confidence_or_unconfirmed_school_cannot_claim_match_or_activate(): void
    {
        $low = $this->evidence(['field_confidence' => ['first_name' => 55, 'last_name' => 90, 'user_code' => 90]]);
        $r = $this->register($this->withReceipt($this->payload(), $low))->assertCreated()->assertJsonPath('id_check.status', 'manual_review');
        $this->assertDatabaseHas('users', ['user_id' => (int) $r->json('user.id'), 'account_status' => 'pending']);
        $unrelated = $this->evidence(['school_marker' => false]);
        $unrelated['fields']['user_code']='2024-02345';
        $other=$this->register($this->withReceipt($this->payload(changes: ['email' => 'other@example.test', 'user_code' => '2024-02345']), $unrelated))->assertCreated()->assertJsonPath('id_check.status','manual_review');
        $this->verifyRegistration((int)$other->json('user.id'))->assertOk();
        $this->assertDatabaseHas('users',['user_id'=>(int)$other->json('user.id'),'account_status'=>'pending']);
    }

    public function test_failed_document_read_returns_retryable_staff_review_receipt(): void
    {
        $this->mock(InstitutionalIdReader::class)->shouldReceive('read')->once()->andThrow(new \RuntimeException('reader temporarily unavailable'));
        $data = $this->payload();
        $r = $this->post('/api/registration/id-check', ['registration_category' => 'college', 'school_id_file' => $data['school_id_file']], ['Accept' => 'application/json'])->assertOk()->assertJsonPath('data.status', 'unavailable');
        $data['id_check_receipt'] = $r->json('data.receipt');
        $this->register($data)->assertCreated()->assertJsonPath('id_check.status', 'unavailable')->assertJsonPath('user.accountStatus', 'pending');
    }

    public function test_persistence_failure_rolls_back_account_and_removes_private_upload(): void
    {
        DB::table('roles')->where('role_code','student')->update(['is_active'=>false]);
        $data=$this->withReceipt($this->payload());
        $this->register($data)->assertStatus(500);
        $this->assertDatabaseMissing('users',['email'=>$data['email']]);
        $this->assertSame([],Storage::disk('private')->allFiles());
    }

    public function test_email_delivery_failure_preserves_pending_registration_for_resend(): void
    {
        $this->mock(\App\Services\Auth\EmailVerificationCodes::class)->shouldReceive('send')->once()->andThrow(new \RuntimeException('mail temporarily unavailable'));
        $response=$this->register($this->withReceipt($this->payload()))->assertCreated()->assertJsonPath('email_verification_sent',false)->assertJsonPath('user.accountStatus','pending');
        $this->assertDatabaseHas('users',['user_id'=>(int)$response->json('user.id'),'account_status'=>'pending','email_verified_at'=>null]);
        $this->assertCount(1,Storage::disk('private')->allFiles());
    }

    public function test_disputed_ocr_can_be_submitted_for_staff_review_without_claiming_a_match(): void
    {
        $data=$this->withReceipt($this->payload(changes:['first_name'=>'Juan Manuel','request_manual_review'=>true]));
        $r=$this->register($data)->assertCreated()->assertJsonPath('id_check.status','manual_review')->assertJsonPath('user.accountStatus','pending');
        $profile=\App\Models\UserProfile::where('user_id',(int)$r->json('user.id'))->first();
        $this->assertSame('fail',$profile->school_id_check_result['checks']['first_name']['result']);
        $this->assertTrue($profile->school_id_check_result['manual_review_requested']);
    }

    public function test_manual_review_request_cannot_override_future_year_or_duplicate_id(): void
    {
        $data=$this->withReceipt($this->payload(changes:['user_code'=>'202701234','request_manual_review'=>true]));
        $this->register($data)->assertUnprocessable()->assertJsonValidationErrors('user_code');
        $this->user(overrides:['user_code'=>'202401234']);
        $this->register($this->withReceipt($this->payload(changes:['request_manual_review'=>true])))->assertUnprocessable()->assertJsonValidationErrors('user_code');
    }

    public function test_normalized_id_login_accepts_digits_only_after_email_verification_without_approving_account(): void
    {
        $r=$this->register($this->withReceipt($this->payload()))->assertCreated();
        $id=(int)$r->json('user.id');
        $this->postJson('/api/login',['identifier'=>'202401234','password'=>'Password2026!'])->assertForbidden()->assertJsonPath('code','email_unverified');
        DB::table('users')->where('user_id',$id)->update(['email_verified_at'=>now()]);
        foreach(['202401234','2024-01234'] as $number) $this->postJson('/api/login',['identifier'=>$number,'password'=>'Password2026!'])->assertOk()->assertJsonPath('user.id',(string)$id)->assertJsonPath('user.accountStatus','pending');
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

    public function test_id_reader_and_registration_have_separate_limits(): void
    {
        for ($i = 0; $i < 6; $i++) {
            $this->postJson('/api/registration/id-check',[])->assertUnprocessable();
        }
        $this->postJson('/api/registration/id-check',[])->assertStatus(429);
        $this->register($this->withReceipt($this->payload()))->assertCreated();
    }
    private function verifyRegistration(int $id)
    {
        $user=\App\Models\User::findOrFail($id);
        $code=Notification::sent($user, \App\Notifications\PassEmailVerificationNotification::class)->last()->code;
        return $this->postJson('/api/email/verify-code',['email'=>$user->email,'code'=>$code]);
    }

    public function test_matching_college_id_activates_only_after_valid_email_code_and_records_one_automatic_decision(): void
    {
        $r=$this->register($this->withReceipt($this->payload()))->assertCreated()->assertJsonPath('user.accountStatus','pending');
        $id=(int)$r->json('user.id');
        $this->postJson('/api/email/verify-code',['email'=>'registration-qa@example.test','code'=>'000000'])->assertUnprocessable();
        $this->assertDatabaseHas('users',['user_id'=>$id,'account_status'=>'pending','email_verified_at'=>null]);
        $this->verifyRegistration($id)->assertOk()->assertJsonPath('message','Email verified. Your account is active. You can now sign in.');
        $this->assertDatabaseHas('users',['user_id'=>$id,'account_status'=>'active']);
        $this->postJson('/api/login',['identifier'=>'202401234','password'=>'Password2026!'])->assertOk()->assertJsonPath('user.accountStatus','active');
        $this->verifyRegistration($id)->assertUnprocessable();
        $this->assertSame(1,DB::table('audit_logs')->where('action','account_auto_approved')->where('auditable_id',$id)->count());
        $this->assertDatabaseMissing('vehicles',['owner_user_id'=>$id]);
        $this->assertFalse(\App\Models\User::findOrFail($id)->isAdmin());
    }

    public function test_matching_employee_activates_via_email_code(): void
    {
        $e=$this->evidence(['fields'=>['first_name'=>'Juan Miguel','last_name'=>'Dela Cruz','user_code'=>'022-1234']]);
        $r=$this->register($this->withReceipt($this->payload('employee',['middle_initial'=>'']),$e))->assertCreated();
        $id=(int)$r->json('user.id');
        $this->verifyRegistration($id)->assertOk();
        $this->assertDatabaseHas('users',['user_id'=>$id,'account_status'=>'active','user_type'=>'employee']);
    }

    public function test_uncertain_disputed_unavailable_and_shs_ids_remain_pending_after_email_verification(): void
    {
        $cases=[
            ['college',[], $this->evidence(['field_confidence'=>['first_name'=>55,'last_name'=>95,'user_code'=>95]])],
            ['college',['first_name'=>'Juan Manuel','request_manual_review'=>true],$this->evidence()],
            ['college',[],$this->evidence(['engine_available'=>false])],
            ['shs',['user_code'=>'SHS-001','department'=>'Senior High School'],null],
        ];
        foreach($cases as $i=>[$category,$changes,$e]) {
            Cache::flush();
            $payload=$this->payload($category,$changes+['email'=>"review{$i}@example.test",'user_code'=>"20240123{$i}"]);
            if ($e) $e['fields']['user_code']=$payload['user_code'];
            $r=$this->register($category==='shs'?$payload:$this->withReceipt($payload,$e))->assertCreated();
            $id=(int)$r->json('user.id');
            $this->verifyRegistration($id)->assertOk()->assertJsonPath('message','Email verified. Your ID needs staff review; your account remains pending.');
            $this->assertDatabaseHas('users',['user_id'=>$id,'account_status'=>'pending']);
        }
    }

    public function test_identity_change_legacy_policy_missing_document_or_added_privilege_cannot_auto_activate(): void
    {
        foreach(['identity','legacy','file','role','suspended','rejected'] as $i=>$kind) {
            Cache::flush();
            $payload=$this->payload(changes:['email'=>"blocked{$i}@example.test",'user_code'=>"20240123{$i}"]);
            $e=$this->evidence(); $e['fields']['user_code']=$payload['user_code'];
            $r=$this->register($this->withReceipt($payload,$e))->assertCreated();
            $id=(int)$r->json('user.id'); $user=\App\Models\User::findOrFail($id); $profile=$user->profile;
            if($kind==='identity') $profile->forceFill(['last_name'=>'Changed'])->save();
            if($kind==='legacy') { $result=$profile->school_id_check_result; $result['policy_version']='dyci-id-v1'; $profile->forceFill(['school_id_check_result'=>$result])->save(); }
            if($kind==='file') Storage::disk('private')->delete($profile->school_id_document_path);
            if($kind==='role') $user->roles()->attach(DB::table('roles')->where('role_code','admin')->value('role_id'),['assigned_at'=>now()]);
            if(in_array($kind,['suspended','rejected'])) $user->forceFill(['account_status'=>$kind==='rejected'?'deactivated':'suspended'])->save();
            $this->verifyRegistration($id)->assertOk();
            $this->assertNotSame('active',$user->fresh()->account_status,$kind);
            $this->assertNotNull($user->fresh()->email_verified_at);
        }
    }

    public function test_legacy_signed_link_shares_activation_policy_and_replay_does_not_revive_suspended_account(): void
    {
        $r=$this->register($this->withReceipt($this->payload()))->assertCreated();
        $id=(int)$r->json('user.id'); $user=\App\Models\User::findOrFail($id);
        $url=\Illuminate\Support\Facades\URL::temporarySignedRoute('verification.verify',now()->addMinutes(10),['id'=>$id,'hash'=>sha1($user->email)]);
        $this->getJson($url)->assertOk();
        $this->assertSame('active',$user->fresh()->account_status);
        $user->forceFill(['account_status'=>'suspended'])->save();
        $this->getJson($url)->assertOk();
        $this->assertSame('suspended',$user->fresh()->account_status);
        $this->assertSame(1,DB::table('audit_logs')->where('action','account_auto_approved')->where('auditable_id',$id)->count());
    }

    public function test_suffix_is_saved_displayed_and_checked_before_automatic_activation(): void
    {
        $e=$this->evidence(); $e['fields']['suffix']='Jr.'; $e['field_confidence']['suffix']=95;
        $r=$this->register($this->withReceipt($this->payload(changes:['suffix'=>'jr']),$e))->assertCreated()->assertJsonPath('user.name','JUAN MIGUEL DELA CRUZ JR.');
        $id=(int)$r->json('user.id');
        $this->assertDatabaseHas('user_profiles',['user_id'=>$id,'suffix'=>'JR.','last_name'=>'DELA CRUZ']);
        $this->verifyRegistration($id)->assertOk();
        $this->assertSame('active',\App\Models\User::findOrFail($id)->account_status);
    }

    public function test_missing_or_changed_suffix_cannot_claim_an_automatic_match(): void
    {
        $e=$this->evidence(); $e['fields']['suffix']='III'; $e['field_confidence']['suffix']=95;
        $p=$this->withReceipt($this->payload(changes:['suffix'=>'']),$e);
        $this->post('/api/registration/id-check',$p,['Accept'=>'application/json'])->assertOk()->assertJsonPath('data.status','needs_correction');
        $this->register($p)->assertUnprocessable()->assertJsonValidationErrors('school_id_file');
        $p['request_manual_review']=true;
        $r=$this->register($p)->assertCreated()->assertJsonPath('user.accountStatus','pending');
        $this->verifyRegistration((int)$r->json('user.id'))->assertOk();
        $this->assertDatabaseHas('users',['user_id'=>(int)$r->json('user.id'),'account_status'=>'pending']);
    }

    public function test_unread_suffix_and_conflicting_number_reads_require_review_without_lowering_threshold(): void
    {
        $e=$this->evidence(['field_conflicts'=>['user_code'=>true]]);
        $r=$this->register($this->withReceipt($this->payload(changes:['suffix'=>'Jr.']),$e))->assertCreated()->assertJsonPath('id_check.status','manual_review');
        $this->verifyRegistration((int)$r->json('user.id'))->assertOk();
        $this->assertDatabaseHas('users',['user_id'=>(int)$r->json('user.id'),'account_status'=>'pending']);
    }

    public function test_explicit_staff_review_request_stays_pending_even_if_details_match(): void
    {
        $r=$this->register($this->withReceipt($this->payload(changes:['request_manual_review'=>true])))->assertCreated()->assertJsonPath('id_check.status','manual_review');
        $this->verifyRegistration((int)$r->json('user.id'))->assertOk();
        $this->assertDatabaseHas('users',['user_id'=>(int)$r->json('user.id'),'account_status'=>'pending']);
    }

    public function test_detailed_checks_stay_private_and_are_available_to_staff_in_saved_submission(): void
    {
        $e=$this->evidence(['sharpness'=>40,'school_marker'=>false,'confidence'=>50,'field_confidence'=>['first_name'=>77.2,'last_name'=>95,'user_code'=>95]]);
        $data=$this->withReceipt($this->payload(),$e);
        $r=$this->post('/api/registration/id-check',$data,['Accept'=>'application/json'])->assertOk()->assertJsonPath('data.status','manual_review');
        $this->assertEqualsCanonicalizing(['status','message','fields','receipt','current_year'], array_keys($r->json('data')));
        $registered=$this->register($data)->assertCreated();
        $private=DB::table('user_profiles')->where('user_id',$registered->json('user.id'))->value('school_id_check_result');
        $private=json_decode($private,true);
        $this->assertStringContainsString('77.2%', $private['checks']['first_name']['detail']);
        $this->assertStringContainsString('40.0', $private['checks']['image_quality']['detail']);
        $this->assertEqualsCanonicalizing(['status','message'], array_keys($registered->json('id_check')));
    }

    public function test_low_full_card_ocr_confidence_does_not_block_strong_matching_fields(): void
    {
        $e=$this->evidence(['confidence'=>30]);
        $this->register($this->withReceipt($this->payload(),$e))->assertCreated()->assertJsonPath('id_check.status','matched');
    }

    private function consensus(string $number='202401234'): array
    {
        return ['value'=>$number,'reliable'=>true,'sources'=>['broad_gray','broad_threshold','tight_color'],'groups'=>3,'conflicting_values'=>false];
    }

    public function test_reported_borderline_quality_and_repeated_matching_number_can_activate_after_email(): void
    {
        $e=$this->evidence(['width'=>1308,'height'=>2049,'sharpness'=>59.1,'field_confidence'=>['first_name'=>91.9,'last_name'=>96.8,'user_code'=>49.3],'number_consensus'=>$this->consensus()]);
        $r=$this->register($this->withReceipt($this->payload(),$e))->assertCreated()->assertJsonPath('id_check.status','matched');
        $this->verifyRegistration((int)$r->json('user.id'))->assertOk();
        $this->assertDatabaseHas('users',['user_id'=>(int)$r->json('user.id'),'account_status'=>'active']);
    }

    public function test_low_sharpness_is_not_a_veto_when_all_identity_text_checks_are_strong(): void
    {
        $e=$this->evidence(['sharpness'=>59.1]);
        $this->register($this->withReceipt($this->payload(),$e))->assertCreated()->assertJsonPath('id_check.status','matched');
    }

    public function test_single_low_confidence_number_missing_branding_or_low_resolution_still_require_review(): void
    {
        foreach(['single','branding','resolution','conflict','wrong_value','wrong_sources'] as $i=>$kind) {
            Cache::flush(); $number="20240123{$i}";
            $e=$this->evidence(['sharpness'=>59.1,'confidence'=>50,'field_confidence'=>['first_name'=>95,'last_name'=>95,'user_code'=>49.3],'number_consensus'=>$this->consensus($number)]);
            $e['fields']['user_code']=$number;
            if($kind==='single') unset($e['number_consensus']);
            if($kind==='branding') $e['school_marker']=false;
            if($kind==='resolution') $e['width']=400;
            if($kind==='conflict') $e['field_conflicts']=['user_code'=>true];
            if($kind==='wrong_value') $e['number_consensus']['value']='202400000';
            if($kind==='wrong_sources') $e['number_consensus']['sources']=['broad_gray','broad_gray','broad_gray'];
            $r=$this->register($this->withReceipt($this->payload(changes:['user_code'=>$number,'email'=>"decision{$i}@example.test"]),$e))->assertCreated()->assertJsonPath('id_check.status','manual_review');
            $this->verifyRegistration((int)$r->json('user.id'))->assertOk();
            $this->assertDatabaseHas('users',['user_id'=>(int)$r->json('user.id'),'account_status'=>'pending']);
        }
    }

    public function test_consensus_does_not_override_a_submitted_number_mismatch(): void
    {
        $e=$this->evidence(['sharpness'=>59.1,'field_confidence'=>['first_name'=>95,'last_name'=>95,'user_code'=>49.3],'number_consensus'=>$this->consensus()]);
        $this->register($this->withReceipt($this->payload(changes:['user_code'=>'202409999']),$e))->assertUnprocessable()->assertJsonValidationErrors('school_id_file');
    }

}
