<?php
namespace Tests\Unit;

use App\Services\Registration\RegistrationLicenseCheck;
use Tests\TestCase;

class RegistrationLicenseCheckTest extends TestCase
{
    public static function identity(): array
    {
        return ['first_name'=>'MARIA ELENA', 'middle_name'=>'SANTOS', 'last_name'=>'DELA CRUZ', 'suffix'=>'', 'birth_date'=>'2000-04-18'];
    }
    public static function document(string $name='DELA CRUZ, MARIA ELENA SANTOS', string $expiry='2030/04/18', string $birth='2000/04/18', float $confidence=95): array
    {
        $lines=['REPUBLIC OF THE PHILIPPINES','DEPARTMENT OF TRANSPORTATION','LAND TRANSPORTATION OFFICE',"DRIVER'S LICENSE",'Last Name First Name Middle Name',$name,'Nationality Sex Date Of Birth','PHL F '.$birth,'License No. Expiration Date Agency Code','N01-23-456789 '.$expiry.' N01','Blood Type Restrictions Conditions','O+ 1 NONE'];
        $words=[];
        foreach($lines as $i=>$line) foreach(explode(' ',$line) as $word) $words[]=['text'=>$word,'confidence'=>$confidence,'line'=>'1-1-'.$i];
        return ['kind'=>'image','engine_available'=>true,'text'=>implode("\n",$lines),'words'=>$words,'confidence'=>$confidence,'width'=>1000,'height'=>650,'sharpness'=>150];
    }
    public function test_clear_labeled_fields_are_extracted_and_pass_without_user_supplied_license_values():void
    {
        $r=app(RegistrationLicenseCheck::class)->evaluate(self::document(),self::identity());
        $this->assertSame('passed',$r['status']); $this->assertSame('N01-23-456789',$r['fields']['license_number']);
        $this->assertSame('DELA CRUZ, MARIA ELENA SANTOS',$r['fields']['full_name']); $this->assertSame('2030-04-18',$r['fields']['expiry_date']);
        $this->assertSame('2000-04-18',$r['fields']['birth_date']);
    }
    public function test_expired_license_and_confident_identity_mismatches_are_blocked():void
    {
        foreach([self::document(expiry:'2020/04/18'),self::document(name:'DELA CRUZ, OTHER PERSON'),self::document(birth:'2001/04/18')] as $d) {
            $r=app(RegistrationLicenseCheck::class)->evaluate($d,self::identity()); $this->assertSame('blocked',$r['status']); $this->assertNotEmpty($r['reasons']);
        }
    }
    public function test_blurry_low_confidence_and_conflicting_reads_require_a_new_photo():void
    {
        $blur=self::document(); $blur['sharpness']=10;
        $conflict=self::document(); $conflict['field_reads']=[self::document(),self::document(expiry:'2031/04/18')];
        foreach([$blur,self::document(confidence:50),$conflict] as $d) $this->assertSame('retake',app(RegistrationLicenseCheck::class)->evaluate($d,self::identity())['status']);
    }
    public function test_missing_birth_field_is_not_filled_from_expiry_or_registration():void
    {
        $d=self::document(); $d['text']=str_replace('Date Of Birth','Other Field',$d['text']);
        foreach($d['words'] as &$word) if(in_array($word['text'],['Birth','Of'])) $word['text']='Other'; unset($word);
        $this->assertSame('retake',app(RegistrationLicenseCheck::class)->evaluate($d,self::identity())['status']);
    }
    public function test_number_consensus_requires_three_distinct_preparations_without_a_typed_number():void
    {
        $d=self::document();
        foreach($d['words'] as &$word) if($word['text']==='N01-23-456789')$word['confidence']=50;unset($word);
        $this->assertSame('retake',app(RegistrationLicenseCheck::class)->evaluate($d,self::identity())['status']);
        $d['field_reads']=[$d+['source'=>'full_gray'],$d+['source'=>'body_gray'],$d+['source'=>'number_color']];
        $this->assertSame('passed',app(RegistrationLicenseCheck::class)->evaluate($d,self::identity())['status']);
        $read=$d['field_reads'][0];$d['field_reads']=[$read,$read,$read];
        $this->assertSame('retake',app(RegistrationLicenseCheck::class)->evaluate($d,self::identity())['status']);
    }
    public function test_reader_outage_does_not_approve_or_label_a_license_invalid():void
    {
        $d=self::document();$d['engine_available']=false;
        $r=app(RegistrationLicenseCheck::class)->evaluate($d,self::identity());$this->assertSame('unavailable',$r['status']);$this->assertNull($r['fields']['license_number']);
    }
    public function test_school_initial_is_matched_and_complete_middle_name_and_birth_date_come_from_license():void
    {
        $identity=['identity_source'=>'institutional_id','first_name'=>'MARIA ELENA','last_name'=>'DELA CRUZ','middle_initial'=>'S.','suffix'=>''];
        $r=app(RegistrationLicenseCheck::class)->evaluate(self::document(),$identity);
        $this->assertSame('passed',$r['status']);$this->assertSame('SANTOS',$r['fields']['middle_name']);$this->assertSame('2000-04-18',$r['fields']['birth_date']);
        foreach(['DELA CRUZ, MARIA ELENA REYES','CRUZ, MARIA ELENA SANTOS','DELA CRUZ, MARIA SANTOS','DELA CRUZ MARIA ELENA S.'] as $name) {
            $this->assertSame('blocked',app(RegistrationLicenseCheck::class)->evaluate(self::document(name:$name),$identity)['status']);
        }
    }
    public function test_known_name_boundaries_and_suffixes_are_preserved_without_guessing():void
    {
        $identity=['first_name'=>'MARIA ELENA','last_name'=>'DELA CRUZ','middle_initial'=>'Ñ.','suffix'=>'JR.'];
        $this->assertSame([true,'ÑUEZ'],RegistrationLicenseCheck::institutionalName('DELA CRUZ JR., MARIA ELENA ÑUEZ',$identity));
        $this->assertSame([true,'ÑUEZ'],RegistrationLicenseCheck::institutionalName('DELA CRUZ, MARIA ELENA ÑUEZ JR.',$identity));
        $this->assertSame([false,null],RegistrationLicenseCheck::institutionalName('DELA CRUZ, MARIA ELENA ÑUEZ',$identity));
        $this->assertSame([false,null],RegistrationLicenseCheck::institutionalName('CRUZ, MARIA ELENA DELA ÑUEZ JR.',$identity));
    }
}
