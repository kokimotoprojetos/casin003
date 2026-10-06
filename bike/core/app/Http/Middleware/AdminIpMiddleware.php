<?php

namespace App\Http\Middleware;

use Closure;

class AdminIpMiddleware
{
    public function handle($request, Closure $next)
    {
        $allowedIps = config('admin.allowed_ips', []);

        // If no IPs configured, skip check (dev mode)
        if (empty($allowedIps)) {
            return $next($request);
        }

        // Wildcard: allow all IPs
        if (in_array('*', $allowedIps, true)) {
            return $next($request);
        }

        $clientIp = $request->ip();

        if (!in_array($clientIp, $allowedIps, true)) {
            return response()->json(['error' => 'Acesso negado'], 403);
        }

        return $next($request);
    }
}
