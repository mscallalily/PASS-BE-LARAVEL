<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class CheckProduction extends Command
{
    protected $signature = 'pass:check-production {--railway : Check the Railway private-storage volume configuration}';
    protected $description = 'Validate production configuration without sending mail or changing records.';

    public function handle(): int
    {
        $checks = [];
        $check = function (bool $passed, string $name) use (&$checks) { $checks[] = $passed; $this->line(($passed ? 'PASS: ' : 'FAIL: ').$name); };
        $check(config('app.env') === 'production', 'APP_ENV is production');
        $check(config('app.debug') === false, 'APP_DEBUG is disabled');
        $key = (string) config('app.key');
        if (str_starts_with($key, 'base64:')) $key = base64_decode(substr($key, 7), true) ?: '';
        $check(Encrypter::supported($key, config('app.cipher', 'AES-256-CBC')), 'A valid APP_KEY is configured; preserve it across deployments');
        foreach (['app.url' => 'APP_URL', 'app.frontend_url' => 'FRONTEND_URL'] as $config => $name) {
            $check($this->httpsOrigin((string) config($config)), $name.' is an HTTPS origin');
        }
        $check(config('database.default') === 'mysql', 'The PASS MySQL connection is selected');
        $check(!config('pass.simulator.enabled'), 'Development gate simulation is disabled');
        foreach (['pdo_mysql', 'gd', 'exif', 'mbstring', 'fileinfo', 'openssl'] as $extension) {
            $check(extension_loaded($extension), 'PHP extension '.$extension.' is available');
        }
        foreach ([[(string) config('services.license_ocr.tesseract_path', 'tesseract'), '--version'], [(string) config('services.license_ocr.pdftotext_path', 'pdftotext'), '-v']] as $command) {
            try { $available = $command[0] !== '' && Process::timeout(5)->run($command)->successful(); }
            catch (Throwable) { $available = false; }
            $check($available, $command[1] === '--version' ? 'Tesseract is available' : 'PDF text extraction is available');
        }
        $mailer = config('mail.default');
        $ready = match ($mailer) {
            'resend' => filled(config('services.resend.key')),
            'postmark' => filled(config('services.postmark.token')),
            'ses' => filled(config('services.ses.key')) && filled(config('services.ses.secret')),
            'smtp' => filled(config('mail.mailers.smtp.host')) && filled(config('mail.mailers.smtp.username')) && filled(config('mail.mailers.smtp.password')),
            default => false,
        };
        $check($ready, 'A real mail transport is configured; log/array mail is not delivery');
        $check(filter_var(config('mail.from.address'), FILTER_VALIDATE_EMAIL) !== false && !str_ends_with((string) config('mail.from.address'), '@example.com'), 'MAIL_FROM_ADDRESS is configured');
        if ($this->option('railway') && $mailer === 'smtp') $this->warn('Railway SMTP requires Pro or above. Otherwise configure an HTTPS mailer such as Resend. Inbox delivery still needs a real test.');
        $probe = '.production-check-'.Str::uuid();
        try { $disk = Storage::disk('private'); $writable = $disk->put($probe, 'PASS storage check') && $disk->exists($probe); }
        catch (Throwable) { $writable = false; }
        finally { try { Storage::disk('private')->delete($probe); } catch (Throwable) {} }
        $check($writable, 'Private document storage is writable');
        $check(!in_array(config('cache.default'), ['array', 'null'], true), 'Rate limits use a persistent cache store');
        if ($this->option('railway')) {
            $check(config('queue.default') === 'sync', 'The single-service Railway profile runs document jobs synchronously');
            $mount = (string) config('pass.deployment.volume_mount_path');
            $storage = realpath(storage_path());
            $check($mount !== '' && $storage !== false && realpath($mount) === $storage, 'A Railway volume is mounted at the Laravel storage directory');
        }
        if (in_array(false, $checks, true)) { $this->error('Production configuration checks failed. Fix the named settings before starting.'); return self::FAILURE; }
        $this->info('Configuration checks passed. Database migration, deployed API tests and real inbox delivery still need verification.');
        return self::SUCCESS;
    }

    private function httpsOrigin(string $value): bool
    {
        $parts = parse_url($value);
        return filter_var($value, FILTER_VALIDATE_URL) !== false && ($parts['scheme'] ?? '') === 'https'
            && !in_array(strtolower($parts['host'] ?? ''), ['', 'localhost', '127.0.0.1', '[::1]'], true)
            && in_array($parts['path'] ?? '', ['', '/'], true)
            && !isset($parts['user']) && !isset($parts['pass']) && !isset($parts['query']) && !isset($parts['fragment']);
    }
}
