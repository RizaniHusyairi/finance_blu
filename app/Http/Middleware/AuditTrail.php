<?php

namespace App\Http\Middleware;

use App\Services\ActivityLogger;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Jejak audit otomatis: setiap request mutasi (POST/PUT/PATCH/DELETE) dari
 * user yang login dicatat ke activity_logs SETELAH response dihasilkan,
 * sehingga status code ikut terekam dan request tidak melambat di jalur kritis.
 *
 * Login/logout/login gagal dicatat terpisah lewat event listener auth
 * (lihat AppServiceProvider), jadi route auth dikecualikan di sini.
 */
class AuditTrail
{
    /** Nama route yang tidak perlu dicatat (dicatat mekanisme lain / noise). */
    private const EXCLUDED_ROUTES = [
        'login', 'logout', 'notifications.mark-read', 'notifications.fetch',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($this->shouldLog($request)) {
            ActivityLogger::logRequest($request, $response->getStatusCode());
        }

        return $response;
    }

    private function shouldLog(Request $request): bool
    {
        if ($request->isMethod('GET') || $request->isMethod('HEAD') || $request->isMethod('OPTIONS')) {
            return false;
        }

        if (! $request->user()) {
            return false;
        }

        $routeName = optional($request->route())->getName();

        return ! in_array($routeName, self::EXCLUDED_ROUTES, true);
    }
}
