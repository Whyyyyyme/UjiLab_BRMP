<?php

use App\Http\Middleware\EnsureRole;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);
        $middleware->alias([
            'role' => \App\Http\Middleware\EnsureRole::class,
            'token.akses.valid' => \App\Http\Middleware\EnsureTokenAksesValid::class,
            'check.password.change' => \App\Http\Middleware\EnsurePasswordChanged::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->render(function (\Throwable $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                // Biarkan validasi formulir (422), autentikasi (401), dan otorisasi (403) menggunakan format standar Laravel
                if (
                    $e instanceof \Illuminate\Validation\ValidationException ||
                    $e instanceof \Illuminate\Auth\AuthenticationException ||
                    $e instanceof \Illuminate\Auth\Access\AuthorizationException
                ) {
                    return null;
                }

                if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpException) {
                    return response()->json([
                        'message' => $e->getMessage() ?: 'Terjadi kesalahan pada permintaan Anda.'
                    ], $e->getStatusCode());
                }

                // Catat detail exception aktual ke log server internal
                logger()->error('Unhandled API Exception: ' . $e->getMessage(), [
                    'exception' => $e
                ]);

                // Masking error query database / SQL agar tidak bocor ke user
                if ($e instanceof \Illuminate\Database\QueryException || $e instanceof \PDOException) {
                    return response()->json([
                        'message' => 'Terjadi kesalahan pada pemrosesan pangkalan data. Silakan hubungi administrator.'
                    ], 500);
                }

                // Masking error server umum
                return response()->json([
                    'message' => 'Terjadi kesalahan internal pada server. Silakan coba beberapa saat lagi.'
                ], 500);
            }
        });
    })->create();

