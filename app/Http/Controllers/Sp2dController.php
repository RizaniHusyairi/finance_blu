<?php

namespace App\Http\Controllers;

use App\Models\ArsipDokumen;
use App\Models\DokumenNpi;
use App\Models\DokumenSp2d;
use App\Models\LogStatusDokumen;
use App\Models\Perjaldin;
use App\Models\RealisasiAnggaran;
use App\Models\User;
use App\Notifications\WorkflowNotification;
use App\Services\BkuPostingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class Sp2dController extends Controller
{
    public function index()
    {
        $perjaldins = Perjaldin::with(['pejabats', 'spps.spm.npi.sp2d'])
            ->whereHas('spps', function ($q) {
                $q->whereHas('spm.npi', function ($npi) {
                    $npi->where('status', 'APPROVED_KASUBAG')
                        ->orWhereHas('sp2d');
                });
            })
            ->latest()
            ->get();

        return view('sp2ds.index', compact('perjaldins'));
    }

    public function detail($perjaldin_id)
    {
        $perjaldin = Perjaldin::with(['pejabats', 'spps.spm.npi.sp2d.arsipDokumen'])->findOrFail($perjaldin_id);

        return view('sp2ds.detail_perjaldin', compact('perjaldin'));
    }

    public function store(Request $request, $npi_id)
    {
        $npi = DokumenNpi::with(['spm.spp.tagihan', 'sp2d'])->findOrFail($npi_id);

        $request->validate([
            'nomor_sp2d' => 'required|string|max:100',
            'tanggal_sp2d' => 'required|date',
        ]);

        DB::transaction(function () use ($request, $npi) {
            $sp2d = $npi->sp2d()->updateOrCreate(
                ['npi_id' => $npi->id],
                [
                    'nomor_sp2d' => $request->nomor_sp2d,
                    'tanggal_sp2d' => $request->tanggal_sp2d,
                    'bendahara_pengeluaran_id' => Auth::id(),
                    'status' => DokumenSp2d::STATUS_DRAFT,
                ]
            );

            $this->logStatus(
                $sp2d,
                $sp2d->wasRecentlyCreated ? null : $sp2d->getOriginal('status'),
                DokumenSp2d::STATUS_DRAFT,
                $sp2d->wasRecentlyCreated ? 'GENERATE_DRAFT' : 'UPDATE_DRAFT',
                'Draft SP2D dibuat dari NPI yang telah final.'
            );
        });

        return back()->with('success', 'Draft SP2D berhasil dibuat.');
    }

    public function approve($sp2d_id)
    {
        $sp2d = DokumenSp2d::with('npi.spm.spp')->findOrFail($sp2d_id);
        $statusSebelumnya = $sp2d->status;

        $sp2d->update([
            'status' => DokumenSp2d::STATUS_APPROVED,
            'bendahara_pengeluaran_id' => $sp2d->bendahara_pengeluaran_id ?: Auth::id(),
        ]);

        $this->logStatus(
            $sp2d,
            $statusSebelumnya,
            DokumenSp2d::STATUS_APPROVED,
            'APPROVE_INTERNAL',
            'SP2D disetujui untuk dieksekusi.'
        );

        return back()->with('success', 'SP2D disetujui dan siap dieksekusi.');
    }

    public function catatBku(Request $request, $sp2d_id)
    {
        $sp2d = DokumenSp2d::with([
            'npi.spm.spp.tagihan.detailKontrak',
            'npi.spm.spp.tagihan.potonganTagihan',
            'npi.spm.spp.dipaRevisionItem',
            'npi.spm.spp.standingInstruction',
            'arsipDokumen',
        ])->findOrFail($sp2d_id);

        if (! $sp2d->nomor_sp2d || ! $sp2d->tanggal_sp2d) {
            return back()->with('error', 'Nomor dan tanggal SP2D wajib diisi sebelum eksekusi.');
        }

        $tagihanSumber = optional(optional(optional($sp2d->npi)->spm)->spp)->tagihan;
        if (
            $tagihanSumber?->tipe_tagihan === 'KONTRAK'
            && ! in_array($sp2d->status, [
                DokumenSp2d::STATUS_SP2D_TERBIT,
                DokumenSp2d::STATUS_DISETUJUI_FINAL,
                DokumenSp2d::STATUS_APPROVED,
            ], true)
        ) {
            return back()->with('error', 'SP2D kontrak harus disetujui final sebelum bukti transfer diunggah.');
        }

        $request->validate([
            'catatan_bku' => 'nullable|string|max:1000',
            'bukti_transfer' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $deferBkuUntilTax = false;
        $postedToBku = false;

        DB::transaction(function () use ($request, $sp2d, &$deferBkuUntilTax, &$postedToBku) {
            if ($sp2d->status === DokumenSp2d::STATUS_DRAFT) {
                $sp2d->update([
                    'status' => DokumenSp2d::STATUS_APPROVED,
                    'bendahara_pengeluaran_id' => $sp2d->bendahara_pengeluaran_id ?: Auth::id(),
                ]);

                $this->logStatus(
                    $sp2d,
                    DokumenSp2d::STATUS_DRAFT,
                    DokumenSp2d::STATUS_APPROVED,
                    'AUTO_APPROVE_ON_EXECUTION',
                    'SP2D di-approve otomatis saat eksekusi.'
                );
            }

            $statusSebelumEksekusi = $sp2d->status;

            $sp2d->update([
                'status' => DokumenSp2d::STATUS_EXECUTED,
                'bendahara_pengeluaran_id' => $sp2d->bendahara_pengeluaran_id ?: Auth::id(),
            ]);

            $this->syncBuktiTransfer($sp2d, $request);
            $this->syncRealisasiAnggaran($sp2d, $request->catatan_bku);
            $this->syncTagihanStatus($sp2d);

            $tagihan = optional(optional(optional($sp2d->npi)->spm)->spp)->tagihan;

            // Tagihan kontrak & honorarium yang memiliki potongan pajak (PPh) harus
            // menunda posting BKU sampai seluruh pajak tersetor (NTPN lengkap).
            $hasTax = $tagihan
                && in_array($tagihan->tipe_tagihan, ['KONTRAK', 'HONORARIUM'], true)
                && $tagihan->potonganTagihan()
                    ->where('jenis_potongan', 'PAJAK')
                    ->where('nominal_potongan', '>', 0)
                    ->exists();

            $hasUnsettledTax = $hasTax
                && $tagihan->potonganTagihan()
                    ->where('jenis_potongan', 'PAJAK')
                    ->where('nominal_potongan', '>', 0)
                    ->where(function ($q) {
                        $q->whereNull('ntpn')->orWhere('ntpn', '');
                    })
                    ->exists();

            $deferBkuUntilTax = $hasTax && $hasUnsettledTax;

            if ($tagihan && ! $deferBkuUntilTax) {
                app(BkuPostingService::class)->postTagihanPengeluaran(
                    $tagihan,
                    $sp2d,
                    $request->catatan_bku ?: null,
                    $hasTax ? (float) ($tagihan->total_bruto ?? $tagihan->total_netto ?? 0) : null
                );
                $postedToBku = true;
            }

            $this->logStatus(
                $sp2d,
                $statusSebelumEksekusi,
                DokumenSp2d::STATUS_EXECUTED,
                'EXECUTE_PAYMENT',
                $request->catatan_bku ?: 'Bukti transfer SP2D diunggah dan tagihan diselesaikan.'
            );

            $this->notifyRoles(
                ['Operator Perjaldin', 'Operator BLU'],
                'SP2D Final & Dana Cair',
                "SP2D {$sp2d->nomor_sp2d} telah final dieksekusi dan tercatat.",
                route('sp2ds.index')
            );
        });

        if ($deferBkuUntilTax) {
            return back()->with('success', 'Bukti transfer SP2D berhasil diunggah. Status tagihan sudah SELESAI. Lanjutkan penyetoran pajak kontrak agar tagihan masuk BKU.');
        }

        return back()->with('success', $postedToBku
            ? 'Eksekusi SP2D selesai. Tagihan masuk BKU, realisasi anggaran, dan status tagihan sudah diperbarui.'
            : 'Eksekusi SP2D selesai. Realisasi anggaran dan status tagihan sudah diperbarui.');
    }

    private function syncBuktiTransfer(DokumenSp2d $sp2d, Request $request): void
    {
        if (! $request->hasFile('bukti_transfer')) {
            return;
        }

        $sp2d->arsipDokumen()
            ->where('jenis_dokumen', 'BUKTI_TRANSFER_SP2D')
            ->where('is_active', true)
            ->update(['is_active' => false]);

        $file = $request->file('bukti_transfer');
        $path = $file->store('sp2d/bukti-transfer', 'public');

        $sp2d->arsipDokumen()->create([
            'jenis_dokumen' => 'BUKTI_TRANSFER_SP2D',
            'nama_file_asli' => $file->getClientOriginalName(),
            'path_file' => $path,
            'disk' => 'public',
            'mime_type' => $file->getMimeType(),
            'ukuran_file' => $file->getSize(),
            'uploaded_by' => Auth::id(),
            'uploaded_at' => now(),
            'keterangan' => 'Bukti transfer SP2D final.',
            'is_active' => true,
        ]);
    }

    private function syncRealisasiAnggaran(DokumenSp2d $sp2d, ?string $catatanBku = null): void
    {
        try {
            $budgetRealizationService = app(\App\Services\BudgetRealizationService::class);
            $budgetRealizationService->recordFromSp2d($sp2d);
        } catch (\Exception $e) {
            \Log::error('Gagal mencatat realisasi anggaran dari SP2D: ' . $e->getMessage());
            // Optionally, we can rethrow or just log. Since it's critical, maybe rethrow?
            throw $e;
        }
    }

    private function syncTagihanStatus(DokumenSp2d $sp2d): void
    {
        $tagihan = optional(optional(optional($sp2d->npi)->spm)->spp)->tagihan;

        if (! $tagihan) {
            return;
        }

        $statusSebelumnya = $tagihan->status;
        $tagihan->update(['status' => 'SELESAI']);

        LogStatusDokumen::create([
            'dokumen_type' => get_class($tagihan),
            'dokumen_id' => $tagihan->id,
            'user_id' => Auth::id(),
            'role_saat_itu' => Auth::user()?->getRoleNames()->first() ?? 'SYSTEM',
            'status_sebelumnya' => $statusSebelumnya,
            'status_baru' => 'SELESAI',
            'aksi' => 'SP2D_FINAL',
            'catatan' => 'Tagihan diselesaikan setelah SP2D final.',
            'ip_address' => request()->ip(),
        ]);
    }

    private function logStatus(
        DokumenSp2d $sp2d,
        ?string $statusSebelumnya,
        string $statusBaru,
        string $aksi,
        ?string $catatan = null
    ): void {
        LogStatusDokumen::create([
            'dokumen_type' => DokumenSp2d::class,
            'dokumen_id' => $sp2d->id,
            'user_id' => Auth::id(),
            'role_saat_itu' => Auth::user()?->getRoleNames()->first() ?? 'SYSTEM',
            'status_sebelumnya' => $statusSebelumnya,
            'status_baru' => $statusBaru,
            'aksi' => $aksi,
            'catatan' => $catatan,
            'ip_address' => request()->ip(),
        ]);
    }

    private function notifyRoles(array $roles, string $judul, string $pesan, ?string $linkUrl = null): void
    {
        Notification::send(User::role($roles)->get(), new WorkflowNotification([
            'title' => $judul,
            'message' => $pesan,
            'url' => $linkUrl,
            'icon' => 'notifications',
            'color' => 'primary',
        ]));
    }
}
