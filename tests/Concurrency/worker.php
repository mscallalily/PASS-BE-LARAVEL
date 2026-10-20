<?php
require __DIR__.'/../../vendor/autoload.php';
$app=require __DIR__.'/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
try {
    if (!str_ends_with(Illuminate\Support\Facades\DB::connection()->getDatabaseName(), '_concurrency_testing')) throw new RuntimeException('Dedicated concurrency database required.');
    $job=json_decode($argv[1],true,16,JSON_THROW_ON_ERROR);
    while(microtime(true)<$job['start']) usleep(1000);
    if (in_array($job['action'],['account-approve','account-reject'],true)) {
        $request=Illuminate\Http\Request::create('/api/admin/users/'.$job['id'],'PATCH');
        $request->setUserResolver(fn()=>App\Models\User::findOrFail($job['actor']));
        $action=$job['action']==='account-approve'?'approve':'reject';
        $response=app(App\Http\Controllers\Api\UserManagementController::class)->{$action}($request,(string)$job['id']);
        $status=$response->getStatusCode()===200?'decided':'conflict_'.$response->getStatusCode();
    } elseif ($job['action']==='approve') {
        $request=Illuminate\Http\Request::create('/api/visitors/requests/'.$job['id'].'/approve','POST',['documents_reviewed'=>true]);
        $request->setUserResolver(fn()=>App\Models\User::findOrFail($job['actor']));
        app(App\Http\Controllers\Api\VisitorAccessController::class)->approve($request,$job['id']);$status='approved';
    } elseif ($job['action']==='assign') {
        $request=Illuminate\Http\Request::create('/api/rfid/assignments','POST',$job['data']);
        $request->setUserResolver(fn()=>App\Models\User::findOrFail($job['actor']));
        $response=app(App\Http\Controllers\Api\RfidAssignmentController::class)->store($request);
        $status=$response->getStatusCode()===201?'assigned':'unexpected';
    } else {
        $status=app(App\Services\Parking\ParkingAccessService::class)->{$job['action']}($job['data'],null)['status'];
    }
    echo json_encode(['status'=>$status]);
} catch (Illuminate\Validation\ValidationException $e) { echo json_encode(['status'=>'validation_422']); }
catch (Symfony\Component\HttpKernel\Exception\HttpException $e) { echo json_encode(['status'=>'conflict_'.$e->getStatusCode()]); }
catch (Throwable $e) { echo json_encode(['status'=>'error','type'=>get_class($e),'db_code'=>$e instanceof Illuminate\Database\QueryException?($e->errorInfo[1]??null):null]); exit(1); }
