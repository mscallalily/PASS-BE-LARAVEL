<?php
namespace Tests\Feature\Parking;

use App\Models\RegistrationSession;
use App\Notifications\RegistrationEmailCode;
use App\Services\Registration\RegistrationLicenseCheck;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\Unit\RegistrationLicenseCheckTest as Evidence;

class RegistrationSessionTest extends ParkingTestCase
{
    protected function setUp():void { parent::setUp(); Storage::fake('private'); Notification::fake(); \App\Models\Department::updateOrCreate(['department_code'=>'QA-COL'],['department_name'=>'QA College Department','is_active'=>true]); }
    private function schoolEvidence(array $overrides=[]):array
    {
        return ['kind'=>'image','engine_available'=>true,'width'=>1000,'height'=>1400,'sharpness'=>150,'school_marker'=>true,'shs_marker'=>true,
            'fields'=>['first_name'=>$overrides['first_name']??'MARIA ELENA','last_name'=>'DELA CRUZ','middle_initial'=>'S.','suffix'=>'','user_code'=>$overrides['user_code']??'2026-01234'],
            'field_confidence'=>array_fill_keys(['first_name','last_name','middle_initial','suffix','user_code'],95),'field_conflicts'=>[],'profile_fields'=>[]];
    }
    private function draft(array $overrides=[]):array
    {
        $data=$overrides+['email'=>'maria@dyci.edu.ph','registration_category'=>'college',
            'department'=>DB::table('departments')->where('department_code','!=','SHS')->where('is_active',true)->value('department_name'),'contact_number'=>'09123456789'];
        $file=UploadedFile::fake()->image('school.jpg',1000,1400);
        $data['school_id_file']=$file;
        $data['id_check_receipt']=app(\App\Services\Registration\InstitutionalIdReceipt::class)->issue($file,$data['registration_category'],$this->schoolEvidence($overrides));
        unset($data['user_code'],$data['first_name']);
        return $data;
    }
    private function begin(array $overrides=[]):array { return $this->post('/api/registration/sessions',$this->draft($overrides),['Accept'=>'application/json'])->assertCreated()->json(); }
    private function headers(array $s):array { return ['X-Registration-Key'=>$s['owner_key']]; }
    private function verified(array $overrides=[]):array
    {
        $s=$this->begin($overrides); $code=Notification::sent(new \Illuminate\Notifications\AnonymousNotifiable, RegistrationEmailCode::class)->last()->code;
        $this->postJson('/api/registration/sessions/'.$s['session_id'].'/verify',['code'=>$code],$this->headers($s))->assertOk();
        return $s;
    }
    private function reader(?array $document=null):void
    {
        $checker=new RegistrationLicenseCheck(app(\App\Services\LicenseVerification\LicenseDocumentReader::class), app(\App\Services\LicenseVerification\LicenseValidationRules::class));
        $mock=\Mockery::mock(RegistrationLicenseCheck::class);$mock->shouldReceive('read')->andReturnUsing(fn($path,$mime,$identity)=>$checker->evaluate($document??Evidence::document(),$identity));$this->app->instance(RegistrationLicenseCheck::class,$mock);
    }
    private function photo(array $s, array $headers=[] ,string $prefix='sessions')
    {
        return $this->post('/api/registration/'.$prefix.'/'.$s['session_id'].'/license',['license_file'=>UploadedFile::fake()->image('license.jpg',1000,650)],['Accept'=>'application/json']+($headers?:$this->headers($s)));
    }
    private function finish(array $s, array $extra=[])
    {
        return $this->postJson('/api/register',$extra+['session_id'=>$s['session_id'],'password'=>'a-good-password','password_confirmation'=>'a-good-password','agree'=>true],$this->headers($s));
    }
    public function test_only_exact_institutional_domain_is_allowed_and_no_account_is_created_before_checks():void
    {
        foreach(['someone@gmail.com','someone@sub.dyci.edu.ph','someone@dyci.edu.ph.evil.test'] as $email) $this->postJson('/api/registration/sessions',$this->draft(['email'=>$email]))->assertUnprocessable()->assertJsonValidationErrors('email');
        $before=DB::table('users')->count();$s=$this->begin(['email'=>'MARIA@DYCI.EDU.PH']);
        $this->assertSame($before,DB::table('users')->count());$this->assertSame(64,strlen($s['owner_key']));
        $raw=DB::table('registration_sessions')->where('session_id',$s['session_id'])->first();
        $this->assertNotSame($s['owner_key'],$raw->owner_hash);$this->assertStringNotContainsString('MARIA ELENA',$raw->draft);
    }
    public function test_incorrect_code_attempts_persist_and_new_code_after_cooldown_can_be_verified():void
    {
        $s=$this->begin();$id=$s['session_id'];
        for($i=0;$i<5;$i++)$this->postJson('/api/registration/sessions/'.$id.'/verify',['code'=>'000000'],$this->headers($s))->assertUnprocessable();
        $this->assertSame(5,(int)RegistrationSession::findOrFail($id)->code_attempts);
        $this->postJson('/api/registration/sessions/'.$id.'/code',[],$this->headers($s))->assertStatus(429);
        $this->travel(61)->seconds();
        $this->postJson('/api/registration/sessions/'.$id.'/code',[],$this->headers($s))->assertOk();
        $latest=Notification::sent(new \Illuminate\Notifications\AnonymousNotifiable, RegistrationEmailCode::class)->last();
        $this->postJson('/api/registration/sessions/'.$id.'/verify',['code'=>$latest->code],$this->headers($s))->assertOk()->assertJsonPath('data.email_verified',true);
    }
    public function test_unverified_or_expired_session_and_wrong_owner_cannot_upload_or_create_account():void
    {
        $s=$this->begin();$this->photo($s)->assertForbidden();$this->finish($s)->assertForbidden();
        $this->getJson('/api/registration/sessions/'.$s['session_id'],['X-Registration-Key'=>str_repeat('0',64)])->assertNotFound();
        RegistrationSession::whereKey($s['session_id'])->update(['expires_at'=>now()->subMinute()]);
        $this->finish($s)->assertStatus(410);
    }
    public function test_expired_email_code_is_rejected():void
    {
        $s=$this->begin();$code=Notification::sent(new \Illuminate\Notifications\AnonymousNotifiable, RegistrationEmailCode::class)->last()->code;
        $this->travel(11)->minutes();$this->postJson('/api/registration/sessions/'.$s['session_id'].'/verify',['code'=>$code],$this->headers($s))->assertUnprocessable();
    }
    public function test_incomplete_legacy_or_forged_client_payload_cannot_bypass_automatic_checks():void
    {
        $this->postJson('/api/register',$this->draft()+['password'=>'a-good-password','password_confirmation'=>'a-good-password','agree'=>true])->assertUnprocessable();
        $s=$this->verified();$this->finish($s)->assertUnprocessable();
        $this->reader();$this->photo($s)->assertOk()->assertJsonPath('data.license.status','passed')->assertJsonMissingPath('data.license.evidence');
        $this->finish($s,['license_number'=>'N00-00-000000'])->assertUnprocessable();
        $this->finish($s,['role'=>'admin'])->assertUnprocessable();
    }
    public function test_passed_license_creates_one_active_account_and_license_and_preserves_private_document_on_retry():void
    {
        $s=$this->verified();$this->reader();$this->photo($s)->assertOk();
        $path=RegistrationSession::findOrFail($s['session_id'])->document_path;
        $r=$this->finish($s)->assertCreated()->assertJsonPath('user.accountStatus','active')->assertJsonPath('user.emailVerified',true);
        $id=$r->json('user.id');$this->assertDatabaseHas('drivers',['user_id'=>$id,'driver_status'=>'active']);
        $this->assertDatabaseHas('driver_licenses',['license_number'=>'N01-23-456789','verification_status'=>'verified','verification_source'=>'automatic']);
        $this->assertSame(['student'],DB::table('user_roles as ur')->join('roles as r','r.role_id','=','ur.role_id')->where('ur.user_id',$id)->pluck('r.role_code')->all());
        $this->assertDatabaseHas('user_profiles',['user_id'=>$id,'first_name'=>'MARIA ELENA','middle_name'=>'SANTOS','birth_date'=>'2000-04-18','school_id_check_status'=>'matched']);
        $this->finish($s)->assertOk()->assertJsonPath('user.id',$id);$this->assertSame(1,DB::table('users')->where('email','maria@dyci.edu.ph')->count());
        Storage::disk('private')->assertExists($path);$row=RegistrationSession::findOrFail($s['session_id']);$this->assertSame([],$row->draft);$this->assertNull($row->document_path);
    }
    public function test_shs_and_employee_use_same_automatic_policy_without_granting_staff_permissions():void
    {
        foreach([['registration_category'=>'shs','user_code'=>'123456789012','department'=>'Senior High School','year_level'=>'12','section'=>'STEM-A'],['registration_category'=>'employee','user_code'=>'026-1234','department'=>'Example Office']] as $i=>$fields) {
            // Distinct printed license number for each independent applicant.
            $s=$this->verified($fields+['email'=>'campus'.$i.'@dyci.edu.ph']);$this->reader();$this->photo($s)->assertOk();
            if($i===1)DB::table('driver_licenses')->update(['license_number'=>'N01-23-456780']);
            $r=$this->finish($s)->assertCreated();$user=\App\Models\User::find($r->json('user.id'));$this->assertFalse($user->canManageParking());$this->assertSame('active',$user->account_status);
            $this->assertFalse($user->profile->school_id_check_result['requires_staff_approval']);
            $this->assertSame(\App\Services\Registration\InstitutionalIdAutofill::POLICY,$user->profile->school_id_check_result['policy_version']);
        }
    }
    public function test_photo_link_has_no_owner_privileges_rotates_and_is_consumed_after_success():void
    {
        $s=$this->verified();$base='/api/registration/sessions/'.$s['session_id'].'/handoff';
        $old=$this->postJson($base,[],$this->headers($s))->assertOk()->json('capture_key');
        $key=$this->postJson($base,[],$this->headers($s))->assertOk()->json('capture_key');
        $this->getJson('/api/registration/capture/'.$s['session_id'],['X-Capture-Key'=>$old])->assertNotFound();
        $this->getJson('/api/registration/sessions/'.$s['session_id'],['X-Capture-Key'=>$key])->assertNotFound();
        $this->postJson('/api/register',['session_id'=>$s['session_id'],'password'=>'a-good-password','password_confirmation'=>'a-good-password','agree'=>true],['X-Capture-Key'=>$key])->assertNotFound();
        $this->reader();$this->photo($s,['X-Capture-Key'=>$key],'capture')->assertOk()->assertJsonPath('data.license.status','passed');
        $this->getJson('/api/registration/capture/'.$s['session_id'],['X-Capture-Key'=>$key])->assertNotFound();
        $this->getJson('/api/registration/sessions/'.$s['session_id'],$this->headers($s))->assertOk()->assertJsonPath('data.license.status','passed');
    }
    public function test_new_phone_capture_invalidates_the_previous_passed_photo():void
    {
        $s=$this->verified();$this->reader();$this->photo($s)->assertOk()->assertJsonPath('data.license.status','passed');
        $key=$this->postJson('/api/registration/sessions/'.$s['session_id'].'/handoff',[],$this->headers($s))->assertOk()->json('capture_key');
        $this->getJson('/api/registration/capture/'.$s['session_id'],['X-Capture-Key'=>$key])->assertOk()->assertJsonPath('data.license',null);
        $this->finish($s)->assertUnprocessable();
        $this->photo($s,['X-Capture-Key'=>$key],'capture')->assertOk()->assertJsonPath('data.license.status','passed');
        $this->finish($s)->assertCreated();
    }
    public function test_expired_cancelled_links_and_stale_in_flight_results_cannot_be_used():void
    {
        $s=$this->verified();$base='/api/registration/sessions/'.$s['session_id'].'/handoff';
        $key=$this->postJson($base,[],$this->headers($s))->assertOk()->json('capture_key');
        $this->travel(11)->minutes();$this->getJson('/api/registration/capture/'.$s['session_id'],['X-Capture-Key'=>$key])->assertStatus(410);
        $key=$this->postJson($base,[],$this->headers($s))->assertOk()->json('capture_key');
        $this->deleteJson($base,[],$this->headers($s))->assertOk();$this->getJson('/api/registration/capture/'.$s['session_id'],['X-Capture-Key'=>$key])->assertNotFound();
    }
    public function test_expired_mismatch_unreadable_and_reader_outage_never_create_an_account():void
    {
        $s=$this->verified();
        foreach([Evidence::document(expiry:'2020/04/18'),Evidence::document(name:'ANOTHER PERSON'),Evidence::document(confidence:40),['engine_available'=>false]] as $d) {
            $this->reader($d);$this->photo($s)->assertOk();$this->finish($s)->assertUnprocessable();
        }
        $this->assertDatabaseMissing('users',['email'=>'maria@dyci.edu.ph']);
    }
    public function test_license_expiring_between_photo_and_final_submission_and_duplicate_identifiers_are_rejected():void
    {
        $this->travelTo(\Carbon\Carbon::parse('2026-10-28 15:58:00','UTC'));
        $s=$this->verified();$this->reader(Evidence::document(expiry:\App\Support\CampusTime::date()));$this->photo($s)->assertOk();
        $this->travel(3)->minutes(); // Still inside the session hour, but already the next day in Manila.
        $this->finish($s)->assertUnprocessable();
    }
    public function test_stale_read_cannot_override_a_replaced_or_cancelled_photo_run():void
    {
        $s=$this->verified();
        $result=app(RegistrationLicenseCheck::class)->evaluate(Evidence::document(),Evidence::identity());
        $mock=\Mockery::mock(RegistrationLicenseCheck::class);
        $mock->shouldReceive('read')->andReturnUsing(function()use($s,$result){RegistrationSession::whereKey($s['session_id'])->update(['license_run_id'=>(string)\Illuminate\Support\Str::uuid()]);return $result;});
        $this->app->instance(RegistrationLicenseCheck::class,$mock);
        $this->photo($s)->assertStatus(409);$this->assertNull(RegistrationSession::find($s['session_id'])->license_result);
        $this->assertSame([],Storage::disk('private')->allFiles('registration-licenses'));$this->finish($s)->assertUnprocessable();
    }
    public function test_existing_license_or_identifier_cannot_be_registered_again():void
    {
        $s=$this->verified();$this->reader();$this->photo($s)->assertOk();
        $old=$this->campusVehicle();DB::table('driver_licenses')->where('driver_id',$old['driver_id'])->update(['license_number'=>'N01-23-456789']);
        $this->finish($s)->assertUnprocessable()->assertJsonValidationErrors('license_file');
        $this->assertDatabaseMissing('users',['email'=>'maria@dyci.edu.ph']);
    }
    public function test_storage_or_account_persistence_failure_does_not_partially_activate_a_user():void
    {
        $s=$this->verified();$this->reader();$this->photo($s)->assertOk();
        $path=RegistrationSession::find($s['session_id'])->document_path;
        \Illuminate\Support\Facades\Event::listen('eloquent.created: '.\App\Models\User::class, function(){throw new \RuntimeException('Synthetic persistence failure');});
        $this->finish($s)->assertStatus(500);$this->assertDatabaseMissing('users',['email'=>'maria@dyci.edu.ph']);
        $this->assertNull(RegistrationSession::find($s['session_id'])->consumed_at);Storage::disk('private')->assertExists($path);
    }
    public function test_photo_page_is_private_and_does_not_expose_keys_in_server_rendered_html():void
    {
        $r=$this->get('/api/registration/capture-page')->assertOk();$r->assertHeader('X-Frame-Options','DENY');
        $this->assertStringContainsString('frame-ancestors',$r->headers->get('Content-Security-Policy'));
        $this->assertStringContainsString('no-store',$r->headers->get('Cache-Control'));
        $this->assertStringContainsString('location.hash',$r->getContent());
    }
    public function test_cleanup_removes_expired_draft_photos_but_never_a_committed_license():void
    {
        $s=$this->verified();$this->reader();$this->photo($s)->assertOk();$path=RegistrationSession::findOrFail($s['session_id'])->document_path;
        $schoolPath=RegistrationSession::findOrFail($s['session_id'])->draft['school_id']['path'];
        $this->finish($s)->assertCreated();$this->travel(2)->hours();$this->artisan('registration:prune')->assertSuccessful();
        Storage::disk('private')->assertExists($schoolPath);
        Storage::disk('private')->assertExists($path);$this->assertDatabaseMissing('registration_sessions',['session_id'=>$s['session_id']]);
    }
    public function test_id_upload_fills_read_only_identity_and_never_accepts_typed_names():void
    {
        $mock=\Mockery::mock(\App\Services\Registration\InstitutionalIdReader::class);
        $mock->shouldReceive('read')->once()->andReturn($this->schoolEvidence());$this->app->instance(\App\Services\Registration\InstitutionalIdReader::class,$mock);
        $r=$this->post('/api/registration/institutional-id',['registration_category'=>'college','school_id_file'=>UploadedFile::fake()->image('id.jpg',1000,1400)],['Accept'=>'application/json'])->assertOk()
            ->assertJsonPath('data.status','passed')->assertJsonPath('data.fields.first_name','MARIA ELENA')->assertJsonPath('data.fields.user_code','2026-01234')->assertJsonMissingPath('data.evidence');
        $this->assertNotEmpty($r->json('data.receipt'));
        $this->post('/api/registration/institutional-id',['registration_category'=>'college','school_id_file'=>UploadedFile::fake()->image('id.jpg',1000,1400),'first_name'=>'FAKE'],['Accept'=>'application/json'])->assertUnprocessable()->assertJsonValidationErrors('first_name');
        $this->post('/api/registration/sessions',$this->draft()+['user_code'=>'2026-99999'],['Accept'=>'application/json'])->assertUnprocessable()->assertJsonValidationErrors('user_code');
        $this->post('/api/registration/sessions',$this->draft()+['birth_date'=>'2000-04-18'],['Accept'=>'application/json'])->assertUnprocessable()->assertJsonValidationErrors('birth_date');
    }
    public function test_id_receipt_is_bound_to_photo_bytes_category_and_expiry():void
    {
        $payload=$this->draft();$payload['school_id_file']=UploadedFile::fake()->image('other.jpg',1100,1400);
        $this->post('/api/registration/sessions',$payload,['Accept'=>'application/json'])->assertUnprocessable()->assertJsonValidationErrors('id_check_receipt');
        $payload=$this->draft();$payload['registration_category']='employee';
        $this->post('/api/registration/sessions',$payload,['Accept'=>'application/json'])->assertUnprocessable()->assertJsonValidationErrors('id_check_receipt');
        $payload=$this->draft();$this->travel(21)->minutes();
        $this->post('/api/registration/sessions',$payload,['Accept'=>'application/json'])->assertUnprocessable()->assertJsonValidationErrors('id_check_receipt');
        $this->assertDatabaseMissing('users',['email'=>'maria@dyci.edu.ph']);
    }
    public function test_uncertain_id_has_no_pass_receipt_and_cannot_be_fixed_with_typed_values():void
    {
        $evidence=$this->schoolEvidence();$evidence['field_confidence']['first_name']=40;
        $mock=\Mockery::mock(\App\Services\Registration\InstitutionalIdReader::class);$mock->shouldReceive('read')->andReturn($evidence);$this->app->instance(\App\Services\Registration\InstitutionalIdReader::class,$mock);
        $this->post('/api/registration/institutional-id',['registration_category'=>'college','school_id_file'=>UploadedFile::fake()->image('id.jpg',1000,1400)],['Accept'=>'application/json'])
            ->assertOk()->assertJsonPath('data.status','retake')->assertJsonPath('data.receipt',null);
        $payload=$this->draft();$payload['id_check_receipt']=app(\App\Services\Registration\InstitutionalIdReceipt::class)->issue($payload['school_id_file'],'college',$evidence);
        $this->post('/api/registration/sessions',$payload,['Accept'=>'application/json'])->assertUnprocessable()->assertJsonValidationErrors('school_id_file');
    }
    public function test_printed_department_and_shs_fields_autofill_only_when_read_confidently():void
    {
        $read=static function(array $lines,int $confidence=95):array {
            $words=[];foreach($lines as $i=>$line)foreach(explode(' ',$line) as $word)$words[]=['text'=>$word,'confidence'=>$confidence,'line'=>(string)$i];return ['words'=>$words];
        };
        $this->assertSame(['department'=>'QA College Department'],\App\Services\Registration\InstitutionalProfileFields::read($read(['QA College Department']),'college'));
        $this->assertSame([],\App\Services\Registration\InstitutionalProfileFields::read($read(['QA College Department'],50),'college'));
        $this->assertSame(['year_level'=>'12','section'=>'STEM-A'],\App\Services\Registration\InstitutionalProfileFields::read($read(['GRADE 12','SECTION: STEM-A']),'shs'));
        $this->assertSame([],\App\Services\Registration\InstitutionalProfileFields::read($read(['BSIT 3A','IN CASE OF EMERGENCY CALL 09999999999']),'college'));
        $this->assertSame([],\App\Services\Registration\InstitutionalProfileFields::read($read(['GRADE 11','GRADE 12','SECTION: STEM-A','SECTION: STEM-B']),'shs'));
        $payload=$this->draft();$evidence=$this->schoolEvidence();$evidence['profile_fields']=['department'=>'QA College Department'];
        $payload['id_check_receipt']=app(\App\Services\Registration\InstitutionalIdReceipt::class)->issue($payload['school_id_file'],'college',$evidence);$payload['department']='Changed department';
        $this->post('/api/registration/sessions',$payload,['Accept'=>'application/json'])->assertUnprocessable()->assertJsonValidationErrors('department');
    }
    public function test_cancel_deletes_both_private_photos_and_modified_school_photo_blocks_completion():void
    {
        $s=$this->verified();$this->reader();$this->photo($s)->assertOk();$row=RegistrationSession::findOrFail($s['session_id']);
        Storage::disk('private')->put($row->draft['school_id']['path'],'changed bytes');$this->finish($s)->assertUnprocessable();
        $this->deleteJson('/api/registration/sessions/'.$s['session_id'],[],$this->headers($s))->assertOk();
        $this->assertSame([],Storage::disk('private')->allFiles());
    }
}
