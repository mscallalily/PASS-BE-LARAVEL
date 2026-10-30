<?php
// Real multi-process race for RFID inventory auto-assignment. Same rules as verify.php:
// a new, dedicated *_concurrency_testing database (migrated + configuration-seeded) is required.
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
        $result=json_decode(stream_get_contents($pipes[1]),true);fclose($pipes[1]);fclose($pipes[2]);$exit=proc_close($process);
        ensure($exit===0 && isset($result['status']) && $result['status']!=='error','Concurrent worker failed: '.json_encode($result));$results[]=$result['status'];
    }
    $counts=array_count_values($results);ksort($counts);return $counts;
}
/** A new campus registration that is approved and eligible for an inventory sticker. */
function registration(int $i): int {
    $u=App\Models\User::create(['user_code'=>'INV-OWNER-'.$i,'email'=>'inv-'.$i.'@pass.test','password'=>'AuditPassword2026!','user_type'=>'student','account_status'=>'active']);
    $u->forceFill(['email_verified_at'=>now()])->save();
    $v=DB::table('vehicles')->insertGetId(['owner_user_id'=>$u->user_id,'vehicle_type_id'=>DB::table('vehicle_types')->where('type_code','motorcycle')->value('vehicle_type_id'),'plate_number'=>'INV-MC-'.$i,'registration_status'=>'registered','rfid_requested_at'=>now(),'created_at'=>now(),'updated_at'=>now()],'vehicle_id');
    DB::table('vehicle_authorizations')->insert(['vehicle_id'=>$v,'authorization_status'=>'approved','requested_at'=>now(),'created_at'=>now(),'updated_at'=>now()]);
    $d=DB::table('drivers')->insertGetId(['user_id'=>$u->user_id,'first_name'=>'INV','last_name'=>'OWNER','driver_status'=>'active','created_at'=>now(),'updated_at'=>now()],'driver_id');
    DB::table('vehicle_driver')->insert(['vehicle_id'=>$v,'driver_id'=>$d,'relationship'=>'owner','is_primary'=>true,'created_at'=>now(),'updated_at'=>now()]);
    DB::table('driver_licenses')->insert(['driver_id'=>$d,'license_number'=>'INV-LICENSE-'.$i,'license_type'=>'non_professional','license_status'=>'valid','verification_status'=>'verified','expiry_date'=>now()->addYears(3)->toDateString(),'created_at'=>now(),'updated_at'=>now()]);
    return $v;
}
function sticker(int $i): void {
    DB::table('rfid_tags')->insert(['tag_uid'=>sprintf('E2806894CC00%012X',$i),'tag_type'=>'sticker','tag_status'=>'available','inventory_registered_at'=>now(),'inventory_source'=>'reader','created_at'=>now(),'updated_at'=>now()]);
}
try {
    ensure(str_ends_with(DB::connection()->getDatabaseName(),'_concurrency_testing'),'Dedicated concurrency database required.');
    ensure(DB::table('users')->count()===0 && DB::table('vehicles')->count()===0,'Use a new isolated concurrency database. Existing records will not be deleted.');
    $cases=[];
    // Ten new registrations approved at the same moment, three stickers left.
    for($i=1;$i<=3;$i++) sticker($i);
    $jobs=[];for($i=1;$i<=10;$i++) $jobs[]=['action'=>'auto-assign','id'=>registration($i)];
    $cases['ten_registrations_three_stickers']=wave($jobs);
    ensure($cases['ten_registrations_three_stickers']===['assigned'=>3,'not_assigned'=>7],'Stickers oversold or an available sticker was skipped.');
    $active=DB::table('rfid_assignments')->where('assignment_status','active');
    ensure($active->count()===3 && $active->distinct()->count('rfid_id')===3 && $active->distinct()->count('vehicle_id')===3,'A UID was assigned twice.');
    ensure(DB::table('rfid_tags')->where('tag_status','available')->count()===0,'Tag status and assignments disagree.');
    // The same vehicle processed by eight workers at once (e.g. a retried job): one sticker.
    for($i=4;$i<=8;$i++) sticker($i);
    $target=registration(11);
    $cases['same_vehicle_eight_workers']=wave(array_fill(0,8,['action'=>'auto-assign','id'=>$target]));
    ensure($cases['same_vehicle_eight_workers']===['assigned'=>1,'not_assigned'=>7],'One vehicle received several stickers.');
    ensure(DB::table('rfid_assignments')->where('vehicle_id',$target)->count()===1,'Multiple assignments for one vehicle.');
    echo json_encode(['passed'=>true,'cases'=>$cases],JSON_PRETTY_PRINT),"\n";
} catch (Throwable $e) { fwrite(STDERR,get_class($e).': '.$e->getMessage()."\n"); exit(1); }
