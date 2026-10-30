<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Remove expired Sanctum login tokens (see config/sanctum.php expiration).
Schedule::command('sanctum:prune-expired --hours=24')->daily();

// Expired drafts include private pre-registration OCR evidence.
Schedule::command('registration:prune')->hourly();
