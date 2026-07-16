<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Jembatan form async: controller lama tetap merespons redirect()->back()
 * dengan session flash, tapi bila request datang dari interceptor form async
 * (header X-Async-Form), redirect dikonversi menjadi JSON berisi pesan flash.
 *
 * Flash diambil dengan pull() agar TERHAPUS dari session — mencegah pesan
 * yang sama muncul lagi (dobel-toast) saat halaman dimuat berikutnya.
 *
 * ValidationException tidak pernah sampai ke sini sebagai redirect: dengan
 * Accept: application/json, Laravel merendernya sebagai 422 JSON native.
 */
class AjaxFlashToJson
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $request->headers->has('X-Async-Form')
            || ! $response instanceof RedirectResponse
            || ! $request->hasSession()) {
            return $response;
        }

        $session = $request->session();

        $messages = [];
        foreach (['success', 'warning', 'info', 'error'] as $level) {
            if ($session->has($level)) {
                $messages[] = [
                    'level' => $level === 'error' ? 'danger' : $level,
                    'text' => (string) $session->pull($level),
                ];
            }
        }

        // Sebagian controller (billing/NTPN pajak) memakai back()->withErrors():
        // pesan kegagalan bisnis hidup di errors bag, bukan flash 'error'.
        if ($session->has('errors')) {
            $bag = $session->pull('errors');
            $first = collect($bag->getBag('default')->all())->first();
            if ($first) {
                $messages[] = ['level' => 'danger', 'text' => (string) $first];
            }
        }

        return response()->json([
            'ok' => ! collect($messages)->contains(fn ($m) => $m['level'] === 'danger'),
            'messages' => $messages,
            'bulk_approved' => (bool) $session->pull('bulk_approved', false),
            'redirect' => $response->getTargetUrl(),
        ]);
    }
}
