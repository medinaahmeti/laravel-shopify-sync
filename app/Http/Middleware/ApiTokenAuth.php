<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ApiTokenAuth
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken();
        abort_unless($token && hash_equals((string) config('app.api_token'), $token), 401);
        return $next($request);
    }
}
