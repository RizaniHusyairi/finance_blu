<?php

namespace App\Http\Controllers;

use App\Models\ShortLink;
use App\Models\TagihanJasa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

/**
 * Resolver short link → redirect ke URL publik signed yang sebenarnya.
 *
 * Mitra menerima link pendek (mis. /i/aB3kZ9p) di WhatsApp, lalu di-redirect
 * 302 ke /p/tagihan-jasa/{id}?signature=... yang memuat halaman invoice.
 */
class ShortLinkController extends Controller
{
    public function show(Request $request, string $slug)
    {
        $link = ShortLink::where('slug', $slug)->firstOrFail();

        if ($link->isExpired()) {
            abort(410, 'Link sudah kedaluwarsa.');
        }

        $link->recordClick();

        $url = match ($link->target_type) {
            'tagihan_jasa' => $this->resolveTagihanJasa($link->target_id),
            default        => null,
        };

        abort_unless($url, 404);

        return redirect()->away($url);
    }

    private function resolveTagihanJasa(int $id): ?string
    {
        $tagihan = TagihanJasa::find($id);
        if (! $tagihan) {
            return null;
        }

        return URL::signedRoute('public.tagihan-jasa.show', ['id' => $tagihan->id]);
    }
}
