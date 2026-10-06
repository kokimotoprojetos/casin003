<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Session\TokenMismatchException;
use Throwable;

class Handler extends ExceptionHandler
{
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function register()
    {
        //
    }

    public function report(Throwable $e)
    {
        // Suppress logging
    }

    public function render($request, Throwable $e)
    {
        if ($e instanceof TokenMismatchException) {
            return $this->handleSessionExpired($request);
        }

        return parent::render($request, $e);
    }

    protected function handleSessionExpired($request)
    {
        if ($request->expectsJson() || $request->is('api/*') || $request->is('admin-api/*')) {
            return response()->json(['error' => 'Sessão expirada', 'reload' => true], 419);
        }

        try {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        } catch (\Throwable $ignored) {
        }

        if ($request->is('muitomoney/*') || str_contains($request->path(), 'admin')) {
            return redirect()->route('admin.login');
        }

        return redirect()->route('user.login');
    }
}
