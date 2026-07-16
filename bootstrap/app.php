<?php

use App\Console\Commands\BackupDatabaseCommand;
use App\Console\Commands\DisableExpiredTemporaryUsersCommand;
use App\Console\Commands\ImportTarifLayananCommand;
use App\Console\Commands\MonitorHealthCommand;
use App\Console\Commands\MoveArsipToPrivateDiskCommand;
use App\Console\Commands\MovePublicColumnFilesToPrivateCommand;
use App\Http\Middleware\AjaxFlashToJson;
use App\Http\Middleware\AuditTrail;
use App\Http\Middleware\EnsureAccountIsActive;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withCommands([
        DisableExpiredTemporaryUsersCommand::class,
        ImportTarifLayananCommand::class,
        MoveArsipToPrivateDiskCommand::class,
        MovePublicColumnFilesToPrivateCommand::class,
        BackupDatabaseCommand::class,
        MonitorHealthCommand::class,
    ])
    ->withMiddleware(function (Middleware $middleware) {
        // Jejak audit: catat semua request mutasi user login ke activity_logs.
        $middleware->web(append: [AuditTrail::class]);

        // Form async: konversi respons redirect+flash menjadi JSON bila request
        // membawa header X-Async-Form (dikirim interceptor form async).
        $middleware->web(append: [AjaxFlashToJson::class]);

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
        $exceptions->render(function (Throwable $e, Request $request) {
            if (config('app.debug')) {
                return null; // dev: tampilkan halaman debug seperti biasa
            }

            // Exception yang sudah punya makna HTTP (404/403/419/429/503),
            // kegagalan validasi, dan autentikasi tetap dirender Laravel dengan
            // halaman/format standarnya masing-masing.
            if ($e instanceof HttpExceptionInterface
                || $e instanceof ValidationException
                || $e instanceof AuthenticationException) {
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
