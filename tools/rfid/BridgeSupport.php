<?php
declare(strict_types=1);

namespace Pass\UsbRfid;

use RuntimeException;

final class BridgeFiles
{
    public static function directory(string $project): string
    {
        $project = realpath($project) ?: throw new RuntimeException('Backend folder not found.');
        if (!is_dir($project.'/.git') || is_link($project.'/.git')) throw new RuntimeException('A local backend .git directory is required.');
        $dir = $project.'/.git/pass-usb-rfid';
        if (is_link($dir) || (file_exists($dir) && !is_dir($dir))) throw new RuntimeException('Unsafe reader settings folder.');
        return $dir;
    }

    public static function createPrivateDirectory(string $dir): void
    {
        if (!is_dir($dir) && !mkdir($dir, 0700, true)) throw new RuntimeException('Cannot create private reader settings.');
        if (PHP_OS_FAMILY === 'Windows') {
            $system = rtrim((string) getenv('SystemRoot'), '\\/').'\\System32\\';
            $run = static function (array $cmd): string {
                $p = proc_open($cmd, [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
                if (!is_resource($p)) throw new RuntimeException('Cannot secure the local reader key.');
                fclose($pipes[0]); $out = stream_get_contents($pipes[1]); $err = stream_get_contents($pipes[2]); fclose($pipes[1]); fclose($pipes[2]);
                if (proc_close($p) !== 0) throw new RuntimeException('Windows could not secure the reader settings folder.');
                return $out;
            };
            $row = str_getcsv(trim($run([$system.'whoami.exe', '/user', '/fo', 'csv', '/nh'])));
            $sid = $row[1] ?? '';
            if (!preg_match('/^S-\d+(?:-\d+)+$/D', $sid)) throw new RuntimeException('Cannot identify the Windows account for key protection.');
            $run([$system.'icacls.exe', $dir, '/inheritance:r', '/grant:r', '*'.$sid.':(OI)(CI)F', '*S-1-5-18:(OI)(CI)F']);
        } elseif (!chmod($dir, 0700)) throw new RuntimeException('Cannot protect the reader settings folder.');
    }

    public static function readJson(string $path): ?array
    {
        if (is_link($path) || (file_exists($path) && !is_file($path))) throw new RuntimeException('Unsafe reader settings file.');
        if (!is_file($path)) return null;
        $raw = file_get_contents($path);
        if ($raw === false || strlen($raw) > 65536) throw new RuntimeException('Cannot read reader settings.');
        $data = json_decode($raw, true, 32, JSON_THROW_ON_ERROR);
        if (!is_array($data)) throw new RuntimeException('Invalid reader settings.');
        return $data;
    }

    public static function writeJson(string $path, array $data): void
    {
        if (is_link($path) || (file_exists($path) && !is_file($path))) throw new RuntimeException('Unsafe reader settings file.');
        $temp = dirname($path).'/.write-'.bin2hex(random_bytes(8));
        $bytes = json_encode($data, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
        try {
            if (file_put_contents($temp, $bytes, LOCK_EX) !== strlen($bytes)) throw new RuntimeException('Reader settings write failed.');
            if (PHP_OS_FAMILY !== 'Windows' && !chmod($temp, 0600)) throw new RuntimeException('Cannot protect reader settings.');
            if (!rename($temp, $path)) throw new RuntimeException('Cannot install reader settings.');
        } finally { if (is_file($temp)) unlink($temp); }
    }

    public static function apiBase(string $url): string
    {
        $p = parse_url($url);
        $local = in_array(strtolower($p['host'] ?? ''), ['127.0.0.1', 'localhost', '[::1]'], true);
        if (!$p || !isset($p['host']) || !in_array($p['scheme'] ?? '', ['http', 'https'], true)
            || (($p['scheme'] ?? '') === 'http' && !$local) || isset($p['user'], $p['pass'])
            || isset($p['user']) || isset($p['pass']) || isset($p['query']) || isset($p['fragment'])
            || !in_array($p['path'] ?? '', ['', '/'], true)) throw new RuntimeException('Use the PASS API origin; HTTP is allowed only on localhost.');
        return rtrim($url, '/');
    }

    public static function config(string $dir): array
    {
        $data = self::readJson($dir.'/reader.json') ?? throw new RuntimeException('Run Setup-PassUsbReader.php --apply first.');
        if (!preg_match('/^[A-Z0-9_-]{1,30}$/D', $data['reader_code'] ?? '') || !preg_match('/^[a-f0-9]{64}$/D', $data['reader_key'] ?? '')) {
            throw new RuntimeException('Reader configuration is incomplete. Run setup again.');
        }
        $data['api_base'] = self::apiBase($data['api_base'] ?? '');
        return $data;
    }
}

final class ScanState
{
    public const QUIET_MS = 3000;
    public static function empty(): array { return ['armed' => false, 'direction' => 'in', 'seen' => [], 'uncertain' => false]; }
    public static function frame(string $raw): string
    {
        $tag = strtoupper(trim($raw));
        if (!preg_match('/^[A-F0-9]{24}$/D', $tag)) throw new RuntimeException('Scan one complete 24-character EPC, followed by Enter.');
        return $tag;
    }
    public static function observe(array &$state, string $tag, float $at): bool
    {
        $previous = $state['seen'][$tag] ?? null;
        $state['seen'] = array_filter($state['seen'], static fn ($t) => $t >= $at - 60000);
        $state['seen'][$tag] = $at;
        if (count($state['seen']) > 128) $state['seen'] = array_slice($state['seen'], -128, null, true);
        return $previous === null || $at - $previous >= self::QUIET_MS;
    }
}

final class ScanTransport
{
    /** No retries and no redirects: a lost response may already have committed a gate event. */
    public static function post(array $config, string $tag, string $direction): array
    {
        if (!function_exists('curl_init')) throw new RuntimeException('Enable the PHP curl extension, then restart this bridge.');
        $body = '';
        $ch = curl_init(BridgeFiles::apiBase($config['api_base']).'/api/devices/rfid/scan');
        curl_setopt_array($ch, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => json_encode(['tag_uid' => $tag, 'direction' => $direction], JSON_THROW_ON_ERROR),
            CURLOPT_HTTPHEADER => ['Accept: application/json', 'Content-Type: application/json', 'X-Reader-Code: '.$config['reader_code'], 'X-Reader-Key: '.$config['reader_key']],
            CURLOPT_CONNECTTIMEOUT_MS => 3000, CURLOPT_TIMEOUT_MS => 10000, CURLOPT_FOLLOWLOCATION => false, CURLOPT_MAXREDIRS => 0,
            CURLOPT_SSL_VERIFYPEER => true, CURLOPT_SSL_VERIFYHOST => 2, CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
            CURLOPT_WRITEFUNCTION => static function ($handle, string $chunk) use (&$body): int {
                if (strlen($body) + strlen($chunk) > 65536) return 0;
                $body .= $chunk; return strlen($chunk);
            }]);
        try {
            $ok = curl_exec($ch); $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE); $type = (string) curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
            if ($ok === false || $status >= 500 || $status < 200 || $status >= 300 || !str_contains(strtolower($type), 'application/json')) {
                $reason = match ($status) { 401 => 'The reader key was not accepted.', 423 => 'The reader is in maintenance mode.', 429 => 'The reader scan limit was reached.', 422 => 'PASS rejected this scan request.', default => 'PASS did not return a confirmed scan result.' };
                throw new RuntimeException($reason.' Scanning is paused. Check PASS access records before resuming.');
            }
            $result = json_decode($body, true, 32, JSON_THROW_ON_ERROR);
            if (!is_array($result) || !in_array($result['status'] ?? null, ['granted', 'completed', 'denied', 'already_inside', 'not_inside', 'not_found', 'full'], true)) {
                throw new RuntimeException('Unexpected PASS response. Check access records before resuming.');
            }
            // Do not expose credentials or unrelated response fields to the local page.
            return array_intersect_key($result, array_flip(['status', 'message', 'reason', 'plate_number', 'slot_code', 'access_event_id']));
        } finally { curl_close($ch); }
    }
}
