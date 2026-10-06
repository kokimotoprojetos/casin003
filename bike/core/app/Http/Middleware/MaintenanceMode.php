<?php

namespace App\Http\Middleware;

use Closure;

class MaintenanceMode
{
    public function handle($request, Closure $next)
    {
        $general = gs();
        if ($general->maintenance_mode == 1) {

            if ($request->is('api/*')) {
                $notify[] = 'Nossa aplicacao esta atualmente em modo de manutencao';
                return response()->json([
                    'remark'=>'maintenance_mode',
                    'status'=>'error',
                    'message'=>['error'=>$notify]
                ]);
            }else{
                return to_route('maintenance');
            }
        }
        return $next($request);
    }
}
