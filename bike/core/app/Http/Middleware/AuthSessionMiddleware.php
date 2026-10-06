<?php

namespace App\Http\Middleware;

use Closure;
use Auth;

class AuthSessionMiddleware
{
    public function handle($request, Closure $next)
    {
        if (Auth::guard('admin')->check()) {
            return $next($request);
        }
        return redirect()->route('admin.login');
    }
}
