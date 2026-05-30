<?php

namespace App\Http\Controllers;

use App\Models\TagihanJasa;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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
            'mitraLegacy',
            'kontrakMitraJasa',
            'creator.profilable',
            'details.layananJasa.parent.parent.parent.parent.parent',
        ])
            ->whereIn('status', ['PUBLISHED', 'LUNAS'])
            ->findOrFail($id);

        $fileName = 'surat-pengantar-dan-nota-tagihan-' . str_replace(['/', '\\'], '-', $tagihan->nomor_tagihan) . '.pdf';

        if ($tagihan->file_surat_pengantar_final && Storage::disk('public')->exists($tagihan->file_surat_pengantar_final)) {
            return $request->boolean('download')
                ? Storage::disk('public')->download($tagihan->file_surat_pengantar_final, $fileName)
                : Storage::disk('public')->response($tagihan->file_surat_pengantar_final, $fileName);
        }

        $pdf = Pdf::loadView('tagihan_jasa.surat_pengantar_pdf', [
            'tagihan' => $tagihan,
            'signed' => true,
        ])
            ->setPaper('a4', 'portrait');

        return $request->boolean('download')
            ? $pdf->download($fileName)
            : $pdf->stream($fileName);
    }
}
