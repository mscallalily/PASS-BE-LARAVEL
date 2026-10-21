<?php

/** Isolated concurrency probe. Never runs against the application's development/production DB. */
require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Role;
use App\Models\User;
use App\Notifications\PrivilegedAccountInvitation;
use App\Services\Auth\PrivilegedAccounts;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\Process\Process;

if (!app()->environment('testing') || !str_ends_with(DB::connection()->getDatabaseName(), '_concurrency_testing')) {
    throw new RuntimeException('A dedicated *_concurrency_testing database is required.');
}
if (($argv[1] ?? '') === '--worker') {
    try {
        $data = json_decode(stream_get_contents(STDIN), true, flags: JSON_THROW_ON_ERROR);
        $access = PersonalAccessToken::findToken($data['token']);
        if (!$access) { echo json_encode(['result' => 'blocked']); exit; }
        $user = $access->tokenable->withAccessToken($access);
        $request = Request::create('/api/account/setup/'.$data['action'], 'POST');
        $request->setUserResolver(fn () => $user);
        $service = app(PrivilegedAccounts::class);
        $ok = $data['action'] === 'verify' ? $service->verify($request, $data['code']) : (bool) $service->complete($request, $data['password']);
        echo json_encode(['result' => $ok ? 'completed' : 'blocked']);
    } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
        echo json_encode(['result' => in_array($e->getStatusCode(), [401,403,409], true) ? 'blocked' : 'error']);
    } catch (Throwable $e) { echo json_encode(['result' => 'error', 'exception_type' => get_class($e)]); }
    exit;
}

if (DB::table('users')->exists()) throw new RuntimeException('This probe requires a fresh, empty testing database. It does not reset data.');
config(['app.frontend_url' => 'http://localhost:8444']); Notification::fake();
$admin = User::create(['user_code' => 'QA-CONC-ADMIN', 'email' => 'qa-conc-admin@pass.test', 'password' => 'SyntheticAdminPass2026!', 'user_type' => 'admin', 'account_status' => 'active']);
$admin->forceFill(['email_verified_at' => now()])->save();
$admin->roles()->attach(Role::where('role_code', 'admin')->value('role_id'), ['assigned_at' => now()]);
$request = Request::create('/api/admin/account-invitations', 'POST'); $request->setUserResolver(fn () => $admin);
[$user] = app(PrivilegedAccounts::class)->create($request, ['first_name' => 'SYNTHETIC', 'last_name' => 'ADMIN', 'user_code' => 'QA-CONC-INVITED', 'email' => 'qa-conc-invited@pass.test', 'role' => 'admin']);
$mail = Notification::sent($user, PrivilegedAccountInvitation::class)->last();
$generation = DB::table('privileged_account_invitations')->where('user_id', $user->getKey())->value('generation');
$token = $user->createToken('concurrency-setup', ['account:setup', 'setup-generation:'.$generation], now()->addMinutes(30))->plainTextToken;
$results = [];
foreach (['verify', 'complete'] as $action) {
    $processes = [];
    for ($i = 0; $i < 8; $i++) {
        $p = new Process([PHP_BINARY, __FILE__, '--worker']); $p->setTimeout(30);
        $p->setInput(json_encode(['action' => $action, 'token' => $token, 'code' => $mail->code, 'password' => 'SyntheticFinalPassword2026!']));
        $p->start(); $processes[] = $p;
    }
    $counts = ['completed' => 0, 'blocked' => 0, 'error' => 0];
    foreach ($processes as $process) {
        $process->wait();
        $out = json_decode($process->getOutput(), true);
        $counts[$out['result'] ?? 'error']++;
    }
    if ($counts !== ['completed' => 1, 'blocked' => 7, 'error' => 0]) throw new RuntimeException('Concurrency failure: '.$action.' '.json_encode($counts));
    $results[$action] = $counts;
}
echo json_encode(['operations' => 16, 'scenarios' => 2, 'results' => $results, 'http_throughput_test' => false], JSON_PRETTY_PRINT).PHP_EOL;
