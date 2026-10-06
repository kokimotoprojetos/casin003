<?php

namespace App\Http\Middleware;

use Auth;
use Closure;

class CheckStatus
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (Auth::check()) {
            $user = auth()->user();
            if ($user->status) {
                return $next($request);
            } else {
                if ($request->is('api/*')) {
                    $notify[] = 'Conta banida. Sistema identificou uma tentativa de fraude em suas contas.';
                    return response()->json([
                        'remark'  => 'banned',
                        'status'  => 'error',
                        'message' => ['error' => $notify],
                        'data'    => [
                            'is_ban'          => $user->status,
                            'ban_reason'      => $user->ban_reason,
                        ],
                    ]);
                } else {
                    \Illuminate\Support\Facades\Auth::guard('web')->logout();
                    $notify[] = ['error', 'Conta banida. Sistema identificou uma tentativa de fraude em suas contas.'];
                    return to_route('user.login')->withNotify($notify);
                }
            }
        }
        abort(403);
    }
}
