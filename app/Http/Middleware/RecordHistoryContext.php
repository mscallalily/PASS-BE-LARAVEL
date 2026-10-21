<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class RecordHistoryContext
{
    public function handle(Request $request, Closure $next, string $mode = 'request')
    {
        if (DB::connection()->getDriverName() !== 'mysql') return $next($request);
        if ($mode === 'actor') {
            DB::statement('SET @pass_history_actor = ?, @pass_history_label = ?', [$request->user()?->user_id, $request->user()?->user_code]);
            return $next($request);
        }
        DB::statement('SET @pass_history_actor = NULL, @pass_history_label = NULL, @pass_history_source = ?, @pass_history_ip = ?', [
            mb_substr($request->method().' '.($request->route()?->uri() ?? 'api'), 0, 150), $request->ip(),
        ]);
        try { return $next($request); }
        finally { DB::statement('SET @pass_history_actor = NULL, @pass_history_label = NULL, @pass_history_source = NULL, @pass_history_ip = NULL'); }
    }
}
