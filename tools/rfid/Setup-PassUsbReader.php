<?php
declare(strict_types=1);

namespace Pass\UsbRfid;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Throwable;

require_once __DIR__.'/BridgeSupport.php';

final class ReaderSetup
{
    public const CODE = 'USB-MAIN-GATE-01';

    /** The test harness may explicitly use its isolated transaction-backed database. */
    public static function configure(string $project, bool $apply, ?int $gateId = null, string $apiBase = 'http://127.0.0.1:8000', bool $testHarness = false): array
    {
        $connection = DB::connection(); $host = $connection->getConfig('host'); $database = $connection->getDatabaseName();
        if ($connection->getDriverName() !== 'mysql' || !in_array($host, ['localhost', '127.0.0.1', '::1'], true)
            || (!$testHarness && (app()->environment('testing') || str_ends_with($database, '_testing')))) {
            throw new RuntimeException('This setup is for the local PASS MySQL database. Test/remote databases are refused by the CLI.');
        }
        $apiBase = BridgeFiles::apiBase($apiBase);
        if (!function_exists('curl_init')) throw new RuntimeException('Enable extension=curl in your loaded PHP configuration first.');
        $dir = BridgeFiles::directory($project);
        $existing = DB::table('rfid_readers')->where('reader_code', self::CODE)->first();
        $gate = DB::table('access_gates')->where('gate_id', $gateId ?? $existing?->gate_id)
            ->when($gateId === null && $existing === null, fn ($q) => $q->orWhere('gate_code', 'MAIN-GATE'))->first();
        if (!$gate || $gate->gate_type !== 'both' || $gate->gate_status !== 'online') throw new RuntimeException('An online shared Main Entrance / Exit gate is required. No gate or parking configuration was changed.');
        $saved = BridgeFiles::readJson($dir.'/reader.json');
        if ($saved && (($saved['reader_code'] ?? '') !== self::CODE || !preg_match('/^[a-f0-9]{64}$/D', $saved['reader_key'] ?? ''))) throw new RuntimeException('Existing reader settings are invalid; they were not overwritten.');
        if ($existing && (!$saved || !Hash::check($saved['reader_key'], $existing->api_key_hash ?? '')
            || (int) $existing->gate_id !== (int) $gate->gate_id || $existing->reader_direction !== 'both' || $existing->reader_status === 'maintenance')) {
            throw new RuntimeException('That reader code already has different credentials/configuration or is in maintenance. Existing reader credentials were not replaced.');
        }
        $info = ['database' => $database, 'reader_code' => self::CODE, 'gate_id' => $gate->gate_id, 'gate_name' => $gate->gate_name, 'api_base' => $apiBase, 'applied' => false];
        if (!$apply) return $info;
        BridgeFiles::createPrivateDirectory($dir);
        $lockPath = $dir.'/setup.lock';
        if (is_link($lockPath)) throw new RuntimeException('Unsafe setup lock.');
        $lock = fopen($lockPath, 'c');
        if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) throw new RuntimeException('Another reader setup is running.');
        try {
            $saved = BridgeFiles::readJson($dir.'/reader.json');
            $key = $saved['reader_key'] ?? bin2hex(random_bytes(32));
            $settings = ['version' => 1, 'reader_code' => self::CODE, 'reader_key' => $key, 'gate_id' => (int) $gate->gate_id,
                'gate_name' => $gate->gate_name, 'api_base' => $apiBase];
            // Retain the local key if a DB write fails, so a retry never silently rotates it.
            BridgeFiles::writeJson($dir.'/reader.json', $settings);
            DB::transaction(function () use ($gate, $key) {
                $currentGate = DB::table('access_gates')->where('gate_id', $gate->gate_id)->lockForUpdate()->first();
                if (!$currentGate || $currentGate->gate_type !== 'both' || $currentGate->gate_status !== 'online') throw new RuntimeException('Gate configuration changed. Reader setup stopped.');
                $reader = DB::table('rfid_readers')->where('reader_code', self::CODE)->lockForUpdate()->first();
                if ($reader) {
                    if (!Hash::check($key, $reader->api_key_hash ?? '') || (int) $reader->gate_id !== (int) $gate->gate_id
                        || $reader->reader_direction !== 'both' || $reader->reader_status === 'maintenance') throw new RuntimeException('Reader configuration changed. Existing credentials were not replaced.');
                    return;
                }
                DB::table('rfid_readers')->insert(['reader_code' => self::CODE, 'gate_id' => $gate->gate_id, 'device_name' => 'PASS USB UHF reader via local PC',
                    'api_key_hash' => Hash::make($key), 'reader_direction' => 'both', 'reader_status' => 'offline', 'created_at' => now(), 'updated_at' => now()]);
            }, 3);
            if (!is_file($dir.'/state.json')) BridgeFiles::writeJson($dir.'/state.json', ScanState::empty());
            return array_replace($info, ['applied' => true]);
        } finally { flock($lock, LOCK_UN); fclose($lock); }
    }
}

if (PHP_SAPI === 'cli' && realpath((string) ($_SERVER['SCRIPT_FILENAME'] ?? '')) === __FILE__) {
    try {
        $options = getopt('', ['apply', 'gate-id:', 'api-base:', 'help']);
        if (isset($options['help'])) { echo "Usage: php tools/rfid/Setup-PassUsbReader.php [--apply] [--gate-id=N] [--api-base=http://127.0.0.1:8000]\n"; exit(0); }
        $project = dirname(__DIR__, 2);
        require $project.'/vendor/autoload.php';
        $app = require $project.'/bootstrap/app.php'; $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
        $info = ReaderSetup::configure($project, isset($options['apply']), isset($options['gate-id']) ? (int) $options['gate-id'] : null, $options['api-base'] ?? 'http://127.0.0.1:8000');
        echo 'Database: '.$info['database'].PHP_EOL.'Gate: '.$info['gate_name'].PHP_EOL.'Reader: '.$info['reader_code'].PHP_EOL;
        echo isset($options['apply']) ? "Reader configured. Key saved privately outside Git; it is not printed. Existing tags, vehicle assignments and parking records were not changed.\nStart the bridge in another terminal: php -S 127.0.0.1:8787 -t tools/rfid tools/rfid/router.php\nOpen http://127.0.0.1:8787/ on this PC.\n" : "Preview only. Add --apply to configure this reader.\n";
    } catch (Throwable $error) { fwrite(STDERR, 'STOP: '.$error->getMessage().PHP_EOL); exit(1); }
}
