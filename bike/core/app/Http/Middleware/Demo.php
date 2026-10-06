<?php

namespace App\Http\Middleware;

use Closure;

class Demo
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
        if ($request->isMethod('POST') || $request->isMethod('PUT') || $request->isMethod('DELETE')){
            $notify[] = ['warning', 'Voce nao pode alterar nada nesta demonstracao'];
            $notify[] = ['info', 'Esta versao e apenas para fins de demonstracao e algumas acoes estao bloqueadas'];
            return back()->withNotify($notify);
        }
        return $next($request);
    }
}
