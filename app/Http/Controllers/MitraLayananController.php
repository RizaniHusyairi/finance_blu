<?php

namespace App\Http\Controllers;

use App\Models\MitraJasa;

class MitraLayananController extends Controller
{
    // Catatan: layanan mitra kini DITURUNKAN otomatis dari kontrak aktif
    // (lihat MitraLayananService::syncFromKontrak). Pengaturan manual dipensiunkan
    // agar tidak bertabrakan dengan recompute saat kontrak disimpan.

    public function edit(MitraJasa $mitra)
    {
        $this->abortUnlessCanManageMitraMaster();

        return redirect()
            ->route('jasa.mitra.show', $mitra)
            ->with('success', 'Layanan mitra kini ditentukan dari Kontrak aktif. Tambah/ubah layanan lewat menu Kontrak.');
    }

    public function update(MitraJasa $mitra)
    {
        $this->abortUnlessCanManageMitraMaster();

        return redirect()
            ->route('jasa.mitra.show', $mitra)
            ->with('success', 'Layanan mitra tidak diatur manual lagi — kelola lewat Kontrak aktif.');
    }

    private function abortUnlessCanManageMitraMaster(): void
    {
        abort_unless(
            auth()->user()?->hasAnyRole(['Super Admin', 'Super Admin Jasa', 'Koordinator Jasa']) === true,
            403
        );
    }
}
