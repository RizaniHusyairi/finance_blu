<?php

namespace App\Http\Controllers;

use App\Models\TagihanJasa;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

/**
 * Halaman publik untuk Tagihan Jasa yang dikirim ke mitra via WhatsApp.
 *
 * Tidak memerlukan login. Hanya bisa diakses melalui signed URL yang
 * di-generate saat tagihan dipublish.
 */
class PublicTagihanJasaController extends Controller
{
    public function show(Request $request, $id)
    {
        $tagihan = TagihanJasa::with([
            'mitra',
            'kontrakMitraJasa',
            'details.layananJasa.parent.parent.parent.parent.parent',
        ])
            ->whereIn('status', ['PUBLISHED', 'LUNAS'])
            ->findOrFail($id);

        $mitra = $tagihan->mitra;

        return view('public.tagihan-jasa-show', [
            'tagihan'    => $tagihan,
            'mitra'      => $mitra,
            'publicView' => true,
        ]);
    }

    public function pdf(Request $request, $id)
    {
        $tagihan = TagihanJasa::with([
            'mitra',
            'kontrakMitraJasa',
            'details.layananJasa.parent.parent.parent.parent.parent',
        ])
            ->whereIn('status', ['PUBLISHED', 'LUNAS'])
            ->findOrFail($id);

        $terbilang = function_exists('terbilang_rupiah')
            ? terbilang_rupiah((float) $tagihan->total_tagihan)
            : trim(terbilang((float) $tagihan->total_tagihan)) . ' Rupiah';

        $pdf = Pdf::loadView('tagihan_jasa.pdf', compact('tagihan', 'terbilang'))
            ->setPaper('a4', 'portrait');

        $fileName = 'nota-tagihan-' . str_replace(['/', '\\'], '-', $tagihan->nomor_tagihan) . '.pdf';

        return $request->boolean('download')
            ? $pdf->download($fileName)
            : $pdf->stream($fileName);
    }
}
