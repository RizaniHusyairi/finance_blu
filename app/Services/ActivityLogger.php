<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Pencatat jejak audit terpusat. Semua penulisan activity_logs lewat sini
 * agar format seragam dan kegagalan logging tidak pernah menggagalkan
 * request utama (fail-safe: telan exception, catat ke laravel.log).
 */
class ActivityLogger
{
    /** Kunci input yang tidak boleh ikut tersimpan di jejak audit. */
    private const SENSITIVE_KEYS = [
        'password', 'password_confirmation', 'current_password',
        '_token', '_method', 'remember', 'otp', 'pin', 'passphrase',
    ];

    public static function log(array $data): void
    {
        try {
            ActivityLog::create($data);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /** Catat request HTTP mutasi (dipanggil dari middleware AuditTrail). */
    public static function logRequest(Request $request, int $statusCode): void
    {
        $user = $request->user();

        $routeName = optional($request->route())->getName();
        $event = match ($request->method()) {
            'POST' => 'create',
            'PUT', 'PATCH' => 'update',
            'DELETE' => 'delete',
            default => 'aksi',
        };

        // Route bermakna khusus menimpa tebakan berdasar method HTTP.
        if ($routeName && Str::contains($routeName, ['approve', 'reject', 'sign', 'setujui', 'tolak', 'verifikasi', 'kirim'])) {
            $event = 'aksi';
        }

        self::log([
            'user_id' => $user?->id,
            'user_name' => $user?->name,
            'user_role' => $user?->getRoleNames()->implode(', '),
            'event' => $event,
            'modul' => $routeName ? Str::before($routeName, '.') : null,
            'description' => self::describeRequest($request, $routeName, $event),
            'method' => $request->method(),
            'route' => $routeName,
            'url' => Str::limit($request->fullUrl(), 1000, ''),
            'ip' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 500, ''),
            'properties' => self::sanitizedInput($request),
            'status_code' => $statusCode,
        ]);
    }

    public static function logAuth(string $event, ?User $user, Request $request, ?string $identifier = null): void
    {
        $description = match ($event) {
            'login' => sprintf('%s masuk ke aplikasi', $user?->name ?? $identifier),
            'logout' => sprintf('%s keluar dari aplikasi', $user?->name ?? $identifier),
            'login_gagal' => sprintf('Percobaan login gagal untuk "%s"', $identifier ?? '-'),
            default => $event,
        };

        self::log([
            'user_id' => $user?->id,
            'user_name' => $user?->name ?? $identifier,
            'user_role' => $user?->getRoleNames()->implode(', '),
            'event' => $event,
            'modul' => 'auth',
            'description' => $description,
            'method' => $request->method(),
            'route' => optional($request->route())->getName(),
            'url' => Str::limit($request->fullUrl(), 1000, ''),
            'ip' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 500, ''),
            'status_code' => null,
        ]);
    }

    private static function describeRequest(Request $request, ?string $routeName, string $event): string
    {
        $aksi = ActivityLog::eventMeta()[$event]['label'] ?? $event;
        $target = $routeName ?: $request->path();

        $params = collect(optional($request->route())->parameters() ?? [])
            ->map(fn ($p) => is_object($p) ? (method_exists($p, 'getKey') ? class_basename($p).'#'.$p->getKey() : class_basename($p)) : $p)
            ->implode(', ');

        return trim(sprintf('%s — %s%s', $aksi, $target, $params !== '' ? " ({$params})" : ''));
    }

    private static function sanitizedInput(Request $request): ?array
    {
        $input = collect($request->except(self::SENSITIVE_KEYS))
            ->reject(fn ($v, $k) => Str::contains(strtolower((string) $k), ['password', 'token', 'secret', 'private_key']))
            ->map(function ($v) {
                if (is_array($v)) {
                    return '[array:'.count($v).']';
                }

                return Str::limit((string) $v, 200);
            })
            ->take(40);

        foreach (array_keys($request->allFiles()) as $fileKey) {
            $input->put($fileKey, '[file diunggah]');
        }

        return $input->isEmpty() ? null : $input->all();
    }
}
