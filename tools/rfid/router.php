<?php
declare(strict_types=1);

use Pass\UsbRfid\BridgeFiles;
use Pass\UsbRfid\ScanState;
use Pass\UsbRfid\ScanTransport;

require_once __DIR__.'/BridgeSupport.php';

// This router is served ONLY by the local PHP development server on this PC.
ini_set('display_errors', '0');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header("Content-Security-Policy: default-src 'none'; script-src 'self'; style-src 'self'; connect-src 'self'; img-src 'self'; base-uri 'none'; frame-ancestors 'none'; form-action 'none'");
function reply(int $code, array $data): never { http_response_code($code); header('Content-Type: application/json; charset=utf-8'); echo json_encode($data, JSON_THROW_ON_ERROR); exit; }

if (PHP_SAPI !== 'cli-server' || ($_SERVER['HTTP_HOST'] ?? '') !== '127.0.0.1:8787'
    || !in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true)) reply(403, ['message' => 'Local reader access only.']);

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
// Reuse the owner's existing PASS logo; no external image or credential request.
if ($path === '/branding/pass-logo.png' && $method === 'GET') {
    $logo = dirname(__DIR__, 3).'/PASS-FE-REACT/public/branding/pass-logo.png';
    if (is_file($logo) && !is_link($logo)) { header('Content-Type: image/png'); readfile($logo); exit; }
    reply(404, ['message' => 'PASS logo is unavailable.']);
}
$assets = ['/' => ['bridge.html', 'text/html'], '/bridge.css' => ['bridge.css', 'text/css'], '/bridge.js' => ['bridge.js', 'text/javascript'], '/scan-core.js' => ['scan-core.js', 'text/javascript']];
if (isset($assets[$path]) && $method === 'GET') {
    header('Content-Type: '.$assets[$path][1].'; charset=utf-8'); readfile(__DIR__.'/'.$assets[$path][0]); exit;
}
if (!in_array($path, ['/api/session', '/api/control', '/api/scan'], true)) reply(404, ['message' => 'Not found.']);
if (($path === '/api/session' && $method !== 'GET') || ($path !== '/api/session' && $method !== 'POST')) reply(405, ['message' => 'Method not allowed.']);

$lock = null;
try {
    $dir = BridgeFiles::directory(dirname(__DIR__, 2));
    $config = BridgeFiles::config($dir);
    if (is_link($dir.'/sessions')) throw new RuntimeException('Unsafe local session folder.');
    if (!is_dir($dir.'/sessions') && !mkdir($dir.'/sessions', 0700)) throw new RuntimeException('Cannot create local session folder.');
    session_name('PASS_USB_READER'); session_save_path($dir.'/sessions');
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Strict', 'path' => '/', 'secure' => false]);
    ini_set('session.use_strict_mode', '1');
    if (!session_start()) throw new RuntimeException('Cannot start local reader session.');
    $_SESSION['csrf'] ??= bin2hex(random_bytes(24));
    $csrf = $_SESSION['csrf']; session_write_close();
    if ($path === '/api/session') reply(200, ['csrf' => $csrf, 'reader_code' => $config['reader_code'], 'gate_name' => $config['gate_name'] ?? 'Main Entrance / Exit', 'api_base' => $config['api_base']]);

    if (($_SERVER['HTTP_ORIGIN'] ?? '') !== 'http://127.0.0.1:8787'
        || !hash_equals($csrf, (string) ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''))
        || !str_starts_with(strtolower($_SERVER['CONTENT_TYPE'] ?? ''), 'application/json')) reply(403, ['message' => 'Refresh this local reader page before scanning.']);
    $raw = file_get_contents('php://input', false, null, 0, 4097);
    if ($raw === false || strlen($raw) > 4096) reply(413, ['message' => 'Invalid reader request.']);
    $data = json_decode($raw, true, 16, JSON_THROW_ON_ERROR);
    if (!is_array($data) || !preg_match('/^[a-f0-9]{32}$/D', $data['client'] ?? '')) reply(422, ['message' => 'Invalid reader window. Refresh this page.']);
    if (is_link($dir.'/scan.lock')) throw new RuntimeException('Unsafe scan lock.');
    $lock = fopen($dir.'/scan.lock', 'c');
    if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) reply(409, ['message' => 'A scan is already being processed.']);
    $state = BridgeFiles::readJson($dir.'/state.json') ?? ScanState::empty();
    $at = microtime(true) * 1000;
    if (!is_array($state['seen'] ?? null) || !in_array($state['direction'] ?? '', ['in', 'out'], true)) throw new RuntimeException('Invalid local reader state. Run setup again.');
    $owner = ($state['owner'] ?? '') === $data['client'];
    $live = ($state['armed'] ?? false) && ($state['lease_until'] ?? 0) > $at;
    if ($path === '/api/control') {
        $action = $data['action'] ?? '';
        if (!in_array($action, ['arm', 'pause', 'heartbeat'], true)) reply(422, ['message' => 'Invalid reader action.']);
        if ($live && !$owner) reply(409, ['message' => 'Another reader window is active. Pause it or close it and wait 20 seconds.']);
        if ($action === 'arm') {
            if (!in_array($data['direction'] ?? '', ['in', 'out'], true)) reply(422, ['message' => 'Choose Entry or Exit.']);
            if (($state['uncertain'] ?? false) && ($data['reviewed'] ?? false) !== true) reply(409, ['uncertain' => true, 'message' => 'Check PASS access records, then confirm before resuming.']);
            $state['direction'] = $data['direction']; $state['armed'] = true; $state['owner'] = $data['client']; $state['lease_until'] = $at + 20000; $state['uncertain'] = false;
        } elseif ($action === 'pause') {
            $state['armed'] = false; $state['lease_until'] = 0;
        } elseif ($owner && $live) $state['lease_until'] = $at + 20000;
        else reply(409, ['message' => 'Reader paused. Press Start scanning again.']);
        BridgeFiles::writeJson($dir.'/state.json', $state);
        reply(200, ['armed' => $state['armed'], 'direction' => $state['direction'], 'uncertain' => $state['uncertain'] ?? false]);
    }

    if (!$owner || !$live || ($state['uncertain'] ?? false)) reply(409, ['message' => 'Reader paused. Press Start scanning again.']);
    $tag = ScanState::frame(is_string($data['tag'] ?? null) ? $data['tag'] : '');
    if (!ScanState::observe($state, $tag, $at)) {
        BridgeFiles::writeJson($dir.'/state.json', $state);
        reply(200, ['status' => 'ignored', 'message' => 'Repeated tag ignored. Remove it from range for at least 3 seconds.']);
    }
    $state['uncertain'] = true;
    // Persist before POST: a crash or lost response must require human reconciliation.
    BridgeFiles::writeJson($dir.'/state.json', $state);
    try {
        $result = ScanTransport::post($config, $tag, $state['direction']);
        $state['uncertain'] = false; $state['lease_until'] = microtime(true) * 1000 + 20000;
        BridgeFiles::writeJson($dir.'/state.json', $state);
        reply(200, $result);
    } catch (Throwable $e) {
        $state['armed'] = false; $state['lease_until'] = 0;
        BridgeFiles::writeJson($dir.'/state.json', $state);
        reply(502, ['uncertain' => true, 'message' => 'No confirmed result from PASS. Scanning paused. Check PASS access records before resuming. Also check that Laravel is running and the reader is enabled.']);
    }
} catch (Throwable $e) {
    reply(503, ['message' => 'Local reader setup is unavailable. Run Setup-PassUsbReader.php --apply in the backend terminal and refresh this page.']);
} finally {
    if (is_resource($lock)) { flock($lock, LOCK_UN); fclose($lock); }
}
