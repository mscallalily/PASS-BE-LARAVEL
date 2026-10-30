<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Schema;
use Throwable;

class CheckRegistrationServices extends Command
{
    protected $signature = 'registration:check {--json}';
    protected $description = 'Read-only checks for the email and license registration services. Does not send mail.';

    public function handle(): int
    {
        $checks = ['image_support' => extension_loaded('gd') && extension_loaded('fileinfo'), 'encryption' => false,
            'registration_table' => false, 'ocr_engine' => false, 'email_transport_configured' => false];
        try { $checks['encryption'] = Crypt::decryptString(Crypt::encryptString('registration-check')) === 'registration-check'; } catch (Throwable) {}
        try { $checks['registration_table'] = Schema::hasTable('registration_sessions'); } catch (Throwable) {}
        try {
            $binary = config('services.license_ocr.tesseract_path', 'tesseract');
            $version = Process::timeout(5)->run([$binary, '--version']);
            $languages = Process::timeout(5)->run([$binary, '--list-langs']);
            $checks['ocr_engine'] = $version->successful() && $languages->successful()
                && (bool) preg_match('/^eng\s*$/m', $languages->output());
        } catch (Throwable) {}
        $transport = config('mail.default');
        $checks['email_transport_configured'] = is_string($transport) && !in_array($transport, ['log', 'array', 'failover'], true);
        $ready = !in_array(false, $checks, true);
        if ($this->option('json')) $this->line(json_encode(['ready' => $ready, 'checks' => $checks, 'mail_delivery_tested' => false]));
        else {
            foreach ($checks as $key => $passed) $this->line(($passed ? 'OK   ' : 'NEEDS SETUP   ').str_replace('_', ' ', $key));
            $this->comment('No email was sent. Confirm actual delivery by requesting a code with a DYCI test inbox.');
        }
        return $ready ? self::SUCCESS : self::FAILURE;
    }
}
