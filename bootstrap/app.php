<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Env;

// Keep .env values in the per-request $_ENV/$_SERVER only. Under Apache's
// threaded mod_php, putenv() writes to one process-wide environment that PHP
// clears when *another* request finishes, so a parallel request could lose
// APP_ENV/DB_* mid-flight and fall back to production + SQLite ("Server Error").
Env::disablePutenv();

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
