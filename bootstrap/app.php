<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\EnsureAccountIsActive;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withCommands([
        \App\Console\Commands\DisableExpiredTemporaryUsersCommand::class,
        \App\Console\Commands\ImportTarifLayananCommand::class,
        \App\Console\Commands\MoveArsipToPrivateDiskCommand::class,
        \App\Console\Commands\MovePublicColumnFilesToPrivateCommand::class,
        \App\Console\Commands\BackupDatabaseCommand::class,
        \App\Console\Commands\MonitorHealthCommand::class,
    ])
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'account.active' => EnsureAccountIsActive::class,
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // BE-01: di luar mode debug, jangan pernah membocorkan detail exception
        // (stack trace, query SQL, path server, dump environment/secret) ke
        // pengguna. Saat APP_DEBUG=true (pengembangan lokal) handler bawaan
        // dibiarkan apa adanya agar developer tetap melihat detail.
        $exceptions->render(function (\Throwable $e, \Illuminate\Http\Request $request) {
            if (config('app.debug')) {
                return null; // dev: tampilkan halaman debug seperti biasa
            }

            // Exception yang sudah punya makna HTTP (404/403/419/429/503),
            // kegagalan validasi, dan autentikasi tetap dirender Laravel dengan
            // halaman/format standarnya masing-masing.
            if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface
                || $e instanceof \Illuminate\Validation\ValidationException
                || $e instanceof \Illuminate\Auth\AuthenticationException) {
                return null;
            }

            // Sisanya = error server tak terduga → respons aman tanpa detail.
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Terjadi kesalahan pada server. Silakan hubungi administrator.',
                ], 500);
            }

            return response()->view('errors.500', [], 500);
        });
    })->create();
