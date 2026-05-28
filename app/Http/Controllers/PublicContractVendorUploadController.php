<?php

namespace App\Http\Controllers;

use App\Models\KontrakPengadaan;
use App\Support\ContractDocumentTte;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class PublicContractVendorUploadController extends Controller
{
    /**
     * Tampilkan halaman portal upload vendor
     */
    public function show(Request $request, $id)
    {
        $kontrak = KontrakPengadaan::with(['vendor', 'arsipDokumen'])->findOrFail($id);

        abort_unless(
            $kontrak->status_kontrak === 'AKTIF' && ContractDocumentTte::isApproved($kontrak),
            403,
            'Kontrak belum disetujui secara elektronik.'
        );

        // Ambil arsip yang sudah ada (jika pernah diupload sebelumnya)
        $arsipAktif = $kontrak->arsipDokumen->where('is_active', true);
        $spkFinal = $arsipAktif->firstWhere('jenis_dokumen', 'SPK_FINAL_TTD');
        $spmkFinal = $arsipAktif->firstWhere('jenis_dokumen', 'SPMK_FINAL_TTD');
        $ringkasanFinal = $arsipAktif->firstWhere('jenis_dokumen', 'RINGKASAN_KONTRAK_FINAL_TTD');

        // Check kelengkapan keseluruhan
        $isComplete = $kontrak->hasVendorUploadedFinalDocs();

        return view('public.vendor-contract-upload', compact(
            'kontrak', 'spkFinal', 'spmkFinal', 'ringkasanFinal', 'isComplete'
        ));
    }

    /**
     * Proses unggah dokumen oleh vendor
     */
    public function store(Request $request, $id)
    {
        $kontrak = KontrakPengadaan::with(['arsipDokumen'])->findOrFail($id);

        abort_unless(
            $kontrak->status_kontrak === 'AKTIF' && ContractDocumentTte::isApproved($kontrak),
            403,
            'Kontrak belum disetujui secara elektronik.'
        );

        $request->validate([
            'file_spk_final' => 'nullable|file|mimes:pdf|max:10240',
            'file_spmk_final' => 'nullable|file|mimes:pdf|max:10240',
            'file_ringkasan_final' => 'nullable|file|mimes:pdf|max:10240',
        ], [
            'mimes' => 'Hanya diperbolehkan format PDF.',
            'max' => 'Ukuran file maksimal 10MB.'
        ]);

        $uploadedCount = 0;

        if ($request->hasFile('file_spk_final')) {
            $this->replaceKontrakArsipAktif(
                $kontrak,
                'SPK_FINAL_TTD',
                $request->file('file_spk_final')->store('kontrak/spk-final-ttd', 'public'),
                $request->file('file_spk_final')->getClientOriginalName()
            );
            $uploadedCount++;
        }

        if ($request->hasFile('file_spmk_final')) {
            $this->replaceKontrakArsipAktif(
                $kontrak,
                'SPMK_FINAL_TTD',
                $request->file('file_spmk_final')->store('kontrak/spmk-final-ttd', 'public'),
                $request->file('file_spmk_final')->getClientOriginalName()
            );
            $uploadedCount++;
        }

        if ($request->hasFile('file_ringkasan_final')) {
            $this->replaceKontrakArsipAktif(
                $kontrak,
                'RINGKASAN_KONTRAK_FINAL_TTD',
                $request->file('file_ringkasan_final')->store('kontrak/ringkasan-kontrak-final-ttd', 'public'),
                $request->file('file_ringkasan_final')->getClientOriginalName()
            );
            $uploadedCount++;
        }

        if ($uploadedCount > 0) {
            return back()->with('success', "Berhasil mengunggah {$uploadedCount} dokumen final.");
        }

        return back()->with('error', 'Tidak ada file yang dipilih untuk diunggah.');
    }

    /**
     * Helper untuk replace dokumen arsip lama jika vendor mengupload ulang
     */
    private function replaceKontrakArsipAktif(KontrakPengadaan $kontrak, string $jenisDokumen, string $path, string $originalName)
    {
        $kontrak->arsipDokumen()
            ->where('jenis_dokumen', $jenisDokumen)
            ->where('is_active', true)
            ->get()
            ->each(function ($arsip) {
                $arsip->update(['is_active' => false]);
            });

        return $kontrak->arsipDokumen()->create([
            'jenis_dokumen' => $jenisDokumen,
            'nama_file_asli' => $originalName,
            'path_file' => $path,
            'disk' => 'public',
            // Kita pakai ID 0 atau ID Vendor jika sistem mencatat siapa pengunggahnya.
            // Karena ini portal publik vendor, kita anggap null atau ID pembuat (opsional).
            'uploaded_by' => null, 
            'uploaded_at' => now(),
            'is_active' => true,
        ]);
    }
}
