<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class KycMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();
        if ($request->is('api/*') && ($user->kv == 0 || $user->kv == 2)) {
            $notify[] = 'Voce nao pode sacar devido a verificacao KYC';
            return response()->json([
                'remark'=>'kyc_verification',
                'status'=>'error',
                'message'=>['error'=>$notify],
            ]);
        }
        if ($user->kv == 0) {
            $notify[] = ['error','Voce nao esta verificado pelo KYC. Para ser verificado, por favor, forneeca estas informacoes'];
            return to_route('user.kyc.form')->withNotify($notify);
        }
        if ($user->kv == 2) {
            $notify[] = ['warning','Seus documentos para verificacao KYC estao em analise. Por favor, aguarde a aprovacao do administrador'];
            return to_route('user.home')->withNotify($notify);
        }
        return $next($request);
    }
}
