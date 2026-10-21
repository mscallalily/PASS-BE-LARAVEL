<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class NormalizeIdentityInput
{
    public function handle(Request $request, Closure $next)
    {
        foreach (['first_name', 'last_name', 'full_name', 'middle_initial', 'suffix', 'user_code', 'driver_name', 'plate_number', 'make', 'model', 'color'] as $key) {
            if (is_string($request->input($key))) $request->merge([$key => mb_strtoupper(trim($request->input($key)))]);
        }
        return $next($request);
    }
}
