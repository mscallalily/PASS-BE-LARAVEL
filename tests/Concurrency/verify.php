<?php
require __DIR__.'/../../vendor/autoload.php';
$app=require __DIR__.'/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\DB;

function ensure(bool $condition,string $message): void { if (!$condition) {fwrite(STDERR,$message."\n");exit(1);} }
function wave(array $jobs): array {
    $children=[];$start=microtime(true)+1.5;
    foreach($jobs as $job) {
        $pipes=[];$process=proc_open([PHP_BINARY,__DIR__.'/worker.php',json_encode($job+['start'=>$start])],[['pipe','r'],['pipe','w'],['pipe','w']],$pipes);
        ensure(is_resource($process),'Could not start a worker.');fclose($pipes[0]);$children[]=[$process,$pipes];
    }
    $results=[];
    foreach($children as [$process,$pipes]) {
        $result=json_decode(stream_get_contents($pipes[1]),true);$stderr=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);$exit=proc_close($process);
        ensure($exit===0 && isset($result['status']) && $result['status']!=='error','Concurrent worker failed: '.json_encode($result));$results[]=$result['status'];
    }
    $counts=array_count_values($results);ksort($counts);return $counts;
}
function vehicle(int $i): array {
    $u=App\Models\User::create(['user_code'=>'RACE-OWNER-'.$i,'email'=>'race-'.$i.'@pass.test','password'=>'AuditPassword2026!','user_type'=>'student','account_status'=>'active']);
    $u->forceFill(['email_verified_at'=>now()])->save();
    $v=DB::table('vehicles')->insertGetId(['owner_user_id'=>$u->user_id,'vehicle_type_id'=>DB::table('vehicle_types')->where('type_code','sedan')->value('vehicle_type_id'),'plate_number'=>'RACE-CAR-'.$i,'registration_status'=>'registered','created_at'=>now(),'updated_at'=>now()],'vehicle_id');
    DB::table('vehicle_authorizations')->insert(['vehicle_id'=>$v,'authorization_status'=>'approved','requested_at'=>now(),'created_at'=>now(),'updated_at'=>now()]);
    $d=DB::table('drivers')->insertGetId(['user_id'=>$u->user_id,'first_name'=>'RACE','last_name'=>'OWNER','driver_status'=>'active','created_at'=>now(),'updated_at'=>now()],'driver_id');
    DB::table('vehicle_driver')->insert(['vehicle_id'=>$v,'driver_id'=>$d,'relationship'=>'owner','is_primary'=>true,'created_at'=>now(),'updated_at'=>now()]);
    DB::table('driver_licenses')->insert(['driver_id'=>$d,'license_number'=>'RACE-LICENSE-'.$i,'license_type'=>'non_professional','license_status'=>'valid','verification_status'=>'verified','expiry_date'=>now()->addYears(3)->toDateString(),'created_at'=>now(),'updated_at'=>now()]);
    $tag='RACE-RFID-'.$i;$rfid=DB::table('rfid_tags')->insertGetId(['tag_uid'=>$tag,'tag_type'=>'card','tag_status'=>'assigned','created_at'=>now(),'updated_at'=>now()],'rfid_id');
    DB::table('rfid_assignments')->insert(['rfid_id'=>$rfid,'user_id'=>$u->user_id,'vehicle_id'=>$v,'assigned_at'=>now(),'assignment_status'=>'active','created_at'=>now(),'updated_at'=>now()]);
    DB::table('user_vehicle_selections')->insert(['user_id'=>$u->user_id,'vehicle_id'=>$v,'updated_at'=>now()]);return ['id'=>$v,'tag'=>$tag];
}
try {
    ensure(str_ends_with(DB::connection()->getDatabaseName(),'_concurrency_testing'),'Dedicated concurrency database required.');
    ensure(DB::table('users')->count()===0 && DB::table('vehicles')->count()===0,'Use a new isolated concurrency database. Existing records will not be deleted.');
    $fixture=vehicle(1);$entry=['action'=>'enter','data'=>['rfid_tag_uid'=>$fixture['tag']]];
    $cases=[];$cases['same_vehicle_entry']=wave(array_fill(0,8,$entry));ensure($cases['same_vehicle_entry']===['already_inside'=>7,'granted'=>1],'Duplicate entry invariant failed.');
    ensure(DB::table('parking_transactions')->where('transaction_status','active')->count()===1,'Duplicate stays were created.');
    $cases['same_vehicle_exit']=wave(array_fill(0,8,['action'=>'exit','data'=>$entry['data']]));ensure($cases['same_vehicle_exit']===['completed'=>1,'not_inside'=>7],'Duplicate exit invariant failed.');
    ensure(DB::table('parking_transactions')->where('transaction_status','active')->count()===0,'Exit did not free the stay.');
    $area=DB::table('parking_areas')->where('area_code','CAR')->value('parking_area_id');
    ensure((bool)$area,'CAR configuration missing.');$slots=DB::table('parking_slots')->whereIn('zone_id',DB::table('parking_zones')->where('parking_area_id',$area)->select('zone_id'))->orderBy('slot_id')->limit(5)->pluck('slot_id');
    DB::table('parking_slots')->whereIn('zone_id',DB::table('parking_zones')->where('parking_area_id',$area)->select('zone_id'))->update(['slot_status'=>'maintenance']);DB::table('parking_slots')->whereIn('slot_id',$slots)->update(['slot_status'=>'available']);
    $jobs=[];for($i=2;$i<=9;$i++){ $f=vehicle($i);$jobs[]=['action'=>'enter','data'=>['rfid_tag_uid'=>$f['tag']]]; }
    $cases['eight_owners_five_slots']=wave($jobs);ensure($cases['eight_owners_five_slots']===['denied'=>3,'granted'=>5],'Capacity oversold or a valid entry failed.');
    $active=DB::table('parking_transactions')->where('transaction_status','active');ensure($active->count()===5 && $active->distinct()->count('slot_id')===5,'A slot was assigned twice.');
    $target=vehicle(10);DB::table('rfid_assignments')->where('vehicle_id',$target['id'])->update(['assignment_status'=>'revoked','unassigned_at'=>now()]);
    $admin=App\Models\User::create(['user_code'=>'RACE-ADMIN','email'=>'race-admin@pass.test','password'=>'AuditPassword2026!','user_type'=>'admin','account_status'=>'active']);$admin->forceFill(['email_verified_at'=>now()])->save();
    $jobs=[];for($i=1;$i<=8;$i++)$jobs[]=['action'=>'assign','actor'=>$admin->user_id,'data'=>['vehicle_id'=>$target['id'],'tag_uid'=>'RACE-ASSIGN-'.$i]];
    $cases['same_vehicle_rfid_assignment']=wave($jobs);ensure($cases['same_vehicle_rfid_assignment']===['assigned'=>1,'validation_422'=>7],'RFID assignment raced.');
    ensure(DB::table('rfid_assignments')->where('vehicle_id',$target['id'])->where('assignment_status','active')->count()===1,'Multiple active tags created.');

    // One guest account, two vehicle types: the owner lock must span both areas.
    DB::table('parking_slots')->whereIn('zone_id',DB::table('parking_zones')->where('parking_area_id',$area)->select('zone_id'))->update(['slot_status'=>'available']);
    $guest=App\Models\User::create(['user_code'=>'RACE-GUEST','email'=>'race-guest@pass.test','password'=>'AuditPassword2026!','user_type'=>'other','account_status'=>'active']);$guest->forceFill(['email_verified_at'=>now()])->save();
    $visitor=DB::table('visitors')->insertGetId(['user_id'=>$guest->user_id,'first_name'=>'RACE','last_name'=>'GUEST','is_blacklisted'=>false,'created_at'=>now(),'updated_at'=>now()],'visitor_id');
    $jobs=[];$guestVehicles=[];
    foreach(['sedan','motorcycle'] as $i=>$type) {
        $vehicle=DB::table('vehicles')->insertGetId(['owner_visitor_id'=>$visitor,'vehicle_type_id'=>DB::table('vehicle_types')->where('type_code',$type)->value('vehicle_type_id'),'plate_number'=>'RACE-GUEST-'.$i,'registration_status'=>'registered','created_at'=>now(),'updated_at'=>now()],'vehicle_id');$guestVehicles[]=$vehicle;
        $request=DB::table('visitor_requests')->insertGetId(['visitor_id'=>$visitor,'vehicle_id'=>$vehicle,'request_status'=>'approved','requested_valid_from'=>now()->subHour(),'requested_valid_until'=>now()->addHour(),'created_at'=>now(),'updated_at'=>now()],'request_id');
        $code=(string)Illuminate\Support\Str::uuid();DB::table('qr_passes')->insert(['pass_code'=>$code,'token_hash'=>hash('sha256',$code),'pass_type'=>'visitor','request_id'=>$request,'visitor_id'=>$visitor,'vehicle_id'=>$vehicle,'valid_from'=>now()->subHour(),'valid_until'=>now()->addHour(),'max_uses'=>2,'use_count'=>0,'pass_status'=>'active','issued_at'=>now(),'created_at'=>now(),'updated_at'=>now()]);
        $jobs[]=['action'=>'enter','data'=>['qr_pass_code'=>$code]];
    }
    $cases['same_guest_across_two_areas']=wave($jobs);ensure($cases['same_guest_across_two_areas']===['denied'=>1,'granted'=>1],'One guest entered using two vehicles.');
    ensure(DB::table('parking_transactions')->whereIn('vehicle_id',$guestVehicles)->where('transaction_status','active')->count()===1,'One guest has two active stays.');
    $photo='concurrency-fixtures/document.jpg';Illuminate\Support\Facades\Storage::disk('private')->put($photo,'synthetic concurrency fixture');
    $from=App\Support\CampusTime::now()->addDay()->startOfDay()->setHour(10);
    $request=DB::table('visitor_requests')->insertGetId(['visitor_id'=>$visitor,'vehicle_id'=>$guestVehicles[0],'request_status'=>'pending','requested_valid_from'=>$from,'requested_valid_until'=>$from->addHours(4),'driver_license_photo_path'=>$photo,'vehicle_photo_path'=>$photo,'driver_license_expiry'=>$from->addYear()->toDateString(),'created_at'=>now(),'updated_at'=>now()],'request_id');
    $cases['same_visitor_request_approval']=wave(array_fill(0,8,['action'=>'approve','actor'=>$admin->user_id,'id'=>$request]));
    ensure($cases['same_visitor_request_approval']===['approved'=>1,'conflict_409'=>7],'Visitor approval created conflicting outcomes.');
    ensure(DB::table('qr_passes')->where('request_id',$request)->count()===1,'Approval created duplicate QR passes.');
    Illuminate\Support\Facades\Storage::disk('private')->delete($photo);

    echo json_encode(['requests'=>42,'scenarios'=>6,'cases'=>$cases],JSON_PRETTY_PRINT)."\n";
} catch(Throwable $e) { fwrite(STDERR,get_class($e).': '.($e instanceof Illuminate\Database\QueryException?'DB code '.($e->errorInfo[1]??0):$e->getMessage())."\n");exit(1); }
