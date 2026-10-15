<?php

use App\Http\Middleware\EnsureAdmin;
use App\Http\Middleware\EnsureAdminOnly;
use App\Http\Middleware\EnsureUserActive;
use App\Http\Middleware\MaintenanceMode;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'user.active' => EnsureUserActive::class,
            'maintenance' => MaintenanceMode::class,
            'admin'       => EnsureAdmin::class,
            'admin.only'  => EnsureAdminOnly::class,
        ]);
        $middleware->trustProxies(at: '*');

        $middleware->statefulApi();
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Format error API konsisten: {message, errors}
        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['message' => 'Silakan login terlebih dahulu.'], 401);
            }
            // Redirect ke login admin jika bukan API
            return redirect()->route('admin.login');
        });

        $exceptions->render(function (\Illuminate\Validation\ValidationException $e, $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'message' => 'Data yang dikirim tidak valid.',
                    'errors'  => $e->errors(),
                ], 422);
            }
        });

        $exceptions->render(function (\Illuminate\Auth\Access\AuthorizationException $e, $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['message' => 'Akses tidak diizinkan.'], 403);
            }
        });

        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e, $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['message' => 'Data tidak ditemukan.'], 404);
            }
        });

        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException $e, $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['message' => 'Terlalu banyak permintaan. Coba lagi nanti.'], 429);
            }
        });
    })->create();
