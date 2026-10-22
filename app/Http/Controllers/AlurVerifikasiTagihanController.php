<?php

namespace App\Http\Controllers;

use App\Models\IntegrationSetting;
use App\Models\TagihanJasa;
use App\Services\TagihanJasaAlurService;
use Illuminate\Http\Request;

/**
 * Pengaturan Super Admin: alur verifikasi yang dipakai tagihan jasa layanan AERO.
 * Tagihan non-aero (atau campuran) selalu memakai alur Kabandara.
 */
class AlurVerifikasiTagihanController extends Controller
{
    public function edit(TagihanJasaAlurService $alurService)
    {
        $berjalan = TagihanJasa::query()
            ->whereNotIn('status', ['PUBLISHED', 'LUNAS', 'BATAL', 'DITOLAK'])
            ->selectRaw('alur_verifikasi, COUNT(*) as jumlah')
            ->groupBy('alur_verifikasi')
            ->pluck('jumlah', 'alur_verifikasi');

        return view('super_admin_jasa.alur_verifikasi.edit', [
            'alurAktif' => $alurService->pengaturanAero(),
            'berjalan' => $berjalan,
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'alur_aero' => ['required', 'in:' . TagihanJasaAlurService::KABANDARA . ',' . TagihanJasaAlurService::KASI_JASA],
        ]);

        IntegrationSetting::setValue(TagihanJasaAlurService::SETTING_KEY, $validated['alur_aero'], 'tagihan_jasa', 'Alur verifikasi tagihan jasa layanan aero');

        return redirect()
            ->route('jasa.alur-verifikasi.edit')
            ->with('success', 'Alur verifikasi tagihan aero diubah menjadi ' . TagihanJasaAlurService::LABEL[$validated['alur_aero']] . '. Berlaku untuk tagihan yang diajukan setelah ini.');
    }
}
