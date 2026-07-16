<?php

namespace App\Http\Controllers;

use App\Models\LogStatusDokumen;
use App\Models\Tagihan;
use App\Models\User;
use App\Notifications\WorkflowNotification;
use App\Services\TagihanReadyForSppNotificationService;
use App\Support\PdfCompressor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

/**
 * Tagihan Kontrak Eksternal: tagihan per termin yang dibuat dari master
 * Kontrak Eksternal (KontrakEksternalController::storeTagihanTermin).
 * Data kontrak (SP, vendor, verifikator, skema termin) milik master —
 * di sini hanya deskripsi & dokumen pendukung per termin yang bisa diubah.
 * Setelah submit tagihan langsung READY_FOR_SPP; verifikasi terjadi pada
 * dokumen SPP/SPM/NPI.
 */
class TagihanKontrakEksternalController extends Controller
{
    public function index()
    {
        $tagihans = Tagihan::where('tipe_tagihan', 'KONTRAK_EKSTERNAL')
            ->with([
                'detailKontrakEksternal.kontrakEksternalTermin.kontrak',
                'pihak',
                'logs' => fn ($q) => $q->latest()->limit(1),
            ])
            ->latest()
            ->get();

        return view('tagihan_kontrak_eksternal.index', compact('tagihans'));
    }

    public function show($id)
    {
        $tagihan = Tagihan::with([
            'detailKontrakEksternal.arsipDokumen',
            'detailKontrakEksternal.kontrakEksternalTermin.kontrak.arsipDokumen',
            'pihak.rekening',
            'potonganTagihan.pajak',
            'logs.user',
        ])->findOrFail($id);
        abort_unless($tagihan->tipe_tagihan === 'KONTRAK_EKSTERNAL', 404);

        return view('tagihan_kontrak_eksternal.show', [
            'tagihan' => $tagihan,
            'detail' => $tagihan->detailKontrakEksternal,
            'isEditable' => $this->isEditable($tagihan),
        ]);
    }

    public function edit($id)
    {
        $tagihan = Tagihan::with([
            'detailKontrakEksternal.arsipDokumen',
            'detailKontrakEksternal.kontrakEksternalTermin.kontrak',
            'pihak.rekening',
        ])->findOrFail($id);
        abort_unless($tagihan->tipe_tagihan === 'KONTRAK_EKSTERNAL', 404);

        if (! $this->isEditable($tagihan)) {
            return redirect()->route('tagihan-kontrak-eksternal.show', $tagihan->id)
                ->withErrors(['error' => 'Tagihan sudah diajukan dan tidak dapat diubah lagi.']);
        }

        return view('tagihan_kontrak_eksternal.edit', [
            'tagihan' => $tagihan,
            'detail' => $tagihan->detailKontrakEksternal,
        ]);
    }

    /**
     * Edit per-tagihan bersifat deskriptif: deskripsi + dokumen pendukung
     * termin. Data kontrak (SP/vendor/verifikator/nilai) dikelola di master.
     */
    public function update(Request $request, $id)
    {
        $tagihan = Tagihan::with('detailKontrakEksternal')->findOrFail($id);
        abort_unless($tagihan->tipe_tagihan === 'KONTRAK_EKSTERNAL', 404);

        if (! $this->isEditable($tagihan)) {
            return redirect()->route('tagihan-kontrak-eksternal.show', $tagihan->id)
                ->withErrors(['error' => 'Tagihan sudah diajukan dan tidak dapat diubah lagi.']);
        }

        $validated = $request->validate([
            'deskripsi' => 'nullable|string|max:500',
            'file_invoice' => 'nullable|file|mimes:pdf|max:5120',
            'file_kwitansi' => 'nullable|file|mimes:pdf|max:5120',
            'file_bast' => 'nullable|file|mimes:pdf|max:10240',
        ]);

        try {
            DB::beginTransaction();

            if (filled($validated['deskripsi'] ?? null)) {
                $tagihan->update(['deskripsi' => $validated['deskripsi']]);
            }

            $this->replaceSupportFiles($request, $tagihan->detailKontrakEksternal);

            LogStatusDokumen::create([
                'dokumen_type' => Tagihan::class,
                'dokumen_id' => $tagihan->id,
                'user_id' => Auth::id(),
                'role_saat_itu' => Auth::user()->getRoleNames()->first() ?? 'PPK',
                'status_sebelumnya' => $tagihan->status,
                'status_baru' => $tagihan->status,
                'aksi' => 'DIPERBARUI',
                'catatan' => 'Data tagihan kontrak eksternal diperbarui sebelum diajukan.',
                'ip_address' => $request->ip(),
            ]);

            DB::commit();

            return redirect()->route('tagihan-kontrak-eksternal.show', $tagihan->id)
                ->with('success', 'Tagihan berhasil diperbarui.');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withInput()->withErrors(['error' => 'Gagal memperbarui tagihan: '.$e->getMessage()]);
        }
    }

    /**
     * Ajukan tagihan: langsung READY_FOR_SPP (tanpa tahap verifikasi tagihan) —
     * verifikator terpilih menjadi penanda tangan dokumen SPP/SPM/NPI/SP2D.
     */
    public function submit(Request $request, $id)
    {
        $tagihan = Tagihan::with('detailKontrakEksternal.kontrakEksternalTermin')->findOrFail($id);
        abort_unless($tagihan->tipe_tagihan === 'KONTRAK_EKSTERNAL', 404);

        if ($tagihan->status !== 'DRAFT' && ! str_starts_with((string) $tagihan->status, 'REVISI_')) {
            return back()->withErrors(['error' => 'Tagihan tidak dalam status DRAFT/REVISI.']);
        }

        $missingVerif = collect([
            'PPK' => $tagihan->ppk_user_id,
            'PPSPM' => $tagihan->ppspm_user_id,
            'Koordinator Keuangan' => $tagihan->koordinator_keuangan_user_id,
            'Bendahara Pengeluaran' => $tagihan->bendahara_pengeluaran_user_id,
            'Bendahara Penerimaan' => $tagihan->bendahara_penerimaan_user_id,
            'Kasubbag' => $tagihan->kasubbag_user_id,
        ])->filter(fn ($v) => empty($v))->keys();

        if ($missingVerif->isNotEmpty()) {
            return back()->withErrors(['error' => 'Verifikator belum lengkap: '.$missingVerif->implode(', ')]);
        }

        if (! $tagihan->detailKontrakEksternal?->file_surat_pesanan) {
            return back()->withErrors(['error' => 'PDF Surat Pesanan bertanda tangan wajib diunggah sebelum tagihan diajukan.']);
        }

        $statusSebelumSubmit = $tagihan->status;

        try {
            DB::beginTransaction();

            $tagihan->update(['status' => 'READY_FOR_SPP']);

            // Termin master resmi tertagih (pola TagihanController::submitKontrak).
            $tagihan->detailKontrakEksternal?->kontrakEksternalTermin?->update(['status_termin' => 'SUDAH_DITAGIH']);

            LogStatusDokumen::create([
                'dokumen_type' => Tagihan::class,
                'dokumen_id' => $tagihan->id,
                'user_id' => Auth::id(),
                'role_saat_itu' => Auth::user()->getRoleNames()->first() ?? 'PPK',
                'status_sebelumnya' => $statusSebelumSubmit,
                'status_baru' => 'READY_FOR_SPP',
                'aksi' => 'DIAJUKAN',
                'catatan' => 'Tagihan kontrak eksternal diajukan — kontrak sudah ber-TTE di luar sistem, langsung siap diproses.',
                'ip_address' => $request->ip(),
            ]);

            if ($tagihan->ppk_user_id && ($ppkUser = User::find($tagihan->ppk_user_id))) {
                Notification::send($ppkUser, new WorkflowNotification([
                    'title' => 'Tagihan Kontrak Eksternal Siap Diproses',
                    'message' => "Tagihan {$tagihan->nomor_tagihan} siap diproses — silakan pilih COA pada halaman Proses Tagihan.",
                    'url' => route('proses-tagihan.show', $tagihan->id),
                    'icon' => 'receipt_long',
                    'color' => 'primary',
                ]));
            }
            app(TagihanReadyForSppNotificationService::class)
                ->notifyIfNewlyReady($tagihan->fresh(), $statusSebelumSubmit);

            DB::commit();

            return redirect()->route('tagihan-kontrak-eksternal.show', $tagihan->id)
                ->with('success', 'Tagihan berhasil diajukan dan langsung siap diproses. Lanjutkan pembebanan COA & pajak pada halaman Proses Tagihan.');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withErrors(['error' => 'Gagal mengajukan tagihan: '.$e->getMessage()]);
        }
    }

    // ─────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────

    private function isEditable(Tagihan $tagihan): bool
    {
        return $tagihan->status === 'DRAFT' || str_starts_with((string) $tagihan->status, 'REVISI_');
    }

    /** Ganti dokumen pendukung termin (arsip lama dinonaktifkan). */
    private function replaceSupportFiles(Request $request, $detail): void
    {
        $map = [
            'file_invoice' => ['jenis' => 'INVOICE', 'dir' => 'tagihan/kontrak_eksternal/invoice'],
            'file_kwitansi' => ['jenis' => 'KWITANSI', 'dir' => 'tagihan/kontrak_eksternal/kwitansi'],
            'file_bast' => ['jenis' => 'BAST', 'dir' => 'tagihan/kontrak_eksternal/bast'],
        ];

        foreach ($map as $field => $cfg) {
            if (! $detail || ! $request->hasFile($field)) {
                continue;
            }

            $file = $request->file($field);
            $path = PdfCompressor::storeCompressed($file, $cfg['dir'], 'local');

            $detail->arsipDokumen()->where('jenis_dokumen', $cfg['jenis'])->update(['is_active' => false]);
            $detail->arsipDokumen()->create([
                'jenis_dokumen' => $cfg['jenis'],
                'nama_file_asli' => $file->getClientOriginalName(),
                'path_file' => $path,
                'disk' => 'local',
                'mime_type' => $file->getMimeType(),
                'ukuran_file' => Storage::disk('local')->size($path),
                'uploaded_by' => Auth::id(),
                'uploaded_at' => now(),
                'is_active' => true,
            ]);
        }
    }
}
