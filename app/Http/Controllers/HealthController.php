<?php

namespace App\Http\Controllers;

use App\Support\HealthCheck;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * MON-03 — endpoint observability kesehatan sistem.
 *
 * Tanpa token: hanya status agregat (ok|degraded|down) + waktu — aman untuk
 * monitor uptime publik; tetap mengembalikan HTTP 503 saat "down" agar memicu
 * alarm eksternal. Dengan token yang cocok (`MONITORING_HEALTH_TOKEN` via query
 * `?token=` atau header `X-Health-Token`): laporan lengkap per-probe.
 *
 * Liveness sederhana tetap di `/up` (bawaan Laravel). Endpoint ini = readiness
 * + detail kesehatan untuk observability.
 */
class HealthController extends Controller
{
    public function __invoke(Request $request, HealthCheck $health): JsonResponse
    {
        $report = $health->run();
        $httpStatus = $health->httpStatusFor($report);

        if (! $this->authorized($request)) {
            return response()->json([
                'status' => $report['status'],
                'checked_at' => $report['checked_at'],
            ], $httpStatus);
        }

        return response()->json($report, $httpStatus);
    }

    private function authorized(Request $request): bool
    {
        $token = (string) env('MONITORING_HEALTH_TOKEN', '');
        if ($token === '') {
            return false;
        }

        $provided = (string) ($request->query('token') ?? $request->header('X-Health-Token', ''));

        return $provided !== '' && hash_equals($token, $provided);
    }
}
