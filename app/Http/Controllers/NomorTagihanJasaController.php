<?php

namespace App\Http\Controllers;

use App\Models\IntegrationSetting;
use App\Models\TagihanJasa;
use Illuminate\Http\Request;

/**
 * Menu Nomor Tagihan Jasa (role Super Admin Jasa).
 *
 * Fungsi:
 *  - Menentukan nomor urut awal penomoran tagihan jasa (digunakan generator
 *    saat membuat tagihan baru).
 *  - Memantau daftar nomor tagihan beserta pembuatnya (creator) dan tautan
 *    file nota/surat pengantar.
 */
class NomorTagihanJasaController extends Controller
{
    private const KEY_NOMOR_AWAL = 'tagihan_jasa.nomor_urut_awal';

    public function index(Request $request)
    {
        $nomorUrutAwal = (int) IntegrationSetting::getValue(self::KEY_NOMOR_AWAL, 1);

        $query = TagihanJasa::query()
            ->with(['creator.profilable', 'mitra', 'mitraLegacy', 'arsipDokumen'])
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = trim((string) $request->search);
                $q->where(function ($qq) use ($search) {
                    $qq->where('nomor_tagihan', 'like', "%{$search}%")
                        ->orWhere('nomor_surat_pengantar', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('tipe_pnbp'), fn ($q) => $q->where('tipe_pnbp', $request->tipe_pnbp));

        $tagihans = $query->orderByDesc('created_at')->paginate(15)->withQueryString();

        $summary = [
            'total' => TagihanJasa::count(),
            'bulan_ini' => TagihanJasa::whereYear('created_at', now()->year)
                ->whereMonth('created_at', now()->month)->count(),
            'nomor_awal' => $nomorUrutAwal,
        ];

        return view('nomor_tagihan_jasa.index', compact('tagihans', 'nomorUrutAwal', 'summary'));
    }

    /**
     * Simpan nomor urut awal yang ditentukan Super Admin Jasa.
     */
    public function updateNomorAwal(Request $request)
    {
        $validated = $request->validate([
            'nomor_urut_awal' => ['required', 'integer', 'min:1', 'max:9999'],
        ], [], [
            'nomor_urut_awal' => 'nomor urut awal',
        ]);

        IntegrationSetting::setValue(
            self::KEY_NOMOR_AWAL,
            (int) $validated['nomor_urut_awal'],
            'tagihan_jasa',
            'Nomor Urut Awal Tagihan Jasa',
            'integer'
        );

        return back()->with('success',
            'Nomor urut awal tagihan jasa diset ke ' . str_pad((string) $validated['nomor_urut_awal'], 4, '0', STR_PAD_LEFT)
            . '. Tagihan baru akan mulai dari nomor ini (atau lebih tinggi bila sudah ada nomor yang lebih besar).');
    }
}
