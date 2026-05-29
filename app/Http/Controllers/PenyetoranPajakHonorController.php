<?php

namespace App\Http\Controllers;

use App\Models\ArsipDokumen;
use App\Models\DetailHonorarium;
use App\Models\DokumenSp2d;
use App\Models\LogStatusDokumen;
use App\Models\MasterTarifPajak;
use App\Models\PotonganTagihan;
use App\Services\BkuPostingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * PenyetoranPajakHonorController
 *
 * Penyetoran PPh 21 honorarium oleh Bendahara Pengeluaran. Mengikuti pola
 * PenyetoranPajakKontrakController dengan penyesuaian: agregasi pajak per
 * tagihan honor dan finalisasi e-Bupot 21 per penerima.
 */
class PenyetoranPajakHonorController extends Controller
{
    /**
     * Daftar potongan PPh 21 dari tagihan HONORARIUM yang SP2D-nya sudah EXECUTED.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        if (! $user->hasRole('Bendahara Pengeluaran') && ! $user->hasRole('Super Admin')) {
            abort(403, 'Akses ditolak.');
        }

        $query = PotonganTagihan::with([
            'tagihan.detailHonorarium',
            'tagihan.spps.spm.npi.sp2d',
            'pajak',
            'akunPotongan',
        ])
        ->where('jenis_potongan', 'PAJAK')
        ->whereHas('tagihan', fn ($q) => $q->where('tipe_tagihan', 'HONORARIUM'))
        ->whereHas('tagihan', fn ($q) => $q->where('status', 'SELESAI'))
        ->whereHas('tagihan.spps.spm.npi.sp2d', fn ($q) => $q->where('status', DokumenSp2d::STATUS_EXECUTED));

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('kode_billing', 'like', "%{$search}%")
                  ->orWhere('ntpn', 'like', "%{$search}%")
                  ->orWhere('nama_pajak_snapshot', 'like', "%{$search}%")
                  ->orWhereHas('tagihan', fn ($sq) => $sq->where('nomor_tagihan', 'like', "%{$search}%")
                                                         ->orWhere('deskripsi', 'like', "%{$search}%"))
                  ->orWhereHas('tagihan.detailHonorarium', fn ($sq) => $sq->where('nama_personel', 'like', "%{$search}%"));
            });
        }

        $statusFilter = $request->input('status', 'semua');
        if ($statusFilter === 'belum_billing') {
            $query->whereNull('kode_billing');
        } elseif ($statusFilter === 'sudah_billing') {
            $query->whereNotNull('kode_billing')->whereNull('ntpn');
        } elseif ($statusFilter === 'sudah_setor') {
            $query->whereNotNull('kode_billing')->whereNotNull('ntpn');
        }

        $potonganList = $query->latest()->get();

        $allForSummary = PotonganTagihan::where('jenis_potongan', 'PAJAK')
            ->whereHas('tagihan', fn ($q) => $q->where('tipe_tagihan', 'HONORARIUM'))
            ->whereHas('tagihan', fn ($q) => $q->where('status', 'SELESAI'))
            ->whereHas('tagihan.spps.spm.npi.sp2d', fn ($q) => $q->where('status', DokumenSp2d::STATUS_EXECUTED))
            ->get(['id', 'kode_billing', 'ntpn']);

        $summary = [
            'belum_billing' => $allForSummary->filter(fn ($p) => ! $p->kode_billing)->count(),
            'sudah_billing' => $allForSummary->filter(fn ($p) => $p->kode_billing && ! $p->ntpn)->count(),
            'sudah_setor'   => $allForSummary->filter(fn ($p) => $p->kode_billing && $p->ntpn)->count(),
        ];

        return view('penyetoran_pajak_honor.index', compact('potonganList', 'summary', 'statusFilter', 'search'));
    }

    /**
     * Detail / workspace penyetoran pajak honorarium.
     */
    public function show($id)
    {
        $potongan = PotonganTagihan::with([
            'tagihan.detailHonorarium',
            'tagihan.dipaRevisionItem.coa',
            'tagihan.spps.spm.npi.sp2d.arsipDokumen',
            'pajak',
            'akunPotongan',
            'arsipDokumen.uploader',
        ])->findOrFail($id);

        $tagihan = $potongan->tagihan;
        abort_if($tagihan?->tipe_tagihan !== 'HONORARIUM', 404, 'Potongan ini bukan tipe honorarium.');
        abort_if($potongan->jenis_potongan !== 'PAJAK', 404, 'Potongan ini bukan potongan pajak honorarium.');

        $coa = $tagihan?->dipaRevisionItem?->coa ?? $potongan->akunPotongan;
        $coaCode = $coa?->kode_mak_lengkap ?? $coa?->kode_akun ?? $coa?->kd_akun ?? '-';
        $coaName = $coa?->nama_akun ?? '';

        $detailHonor = $tagihan?->detailHonorarium ?? collect();

        $spp = $tagihan?->spps?->sortByDesc('created_at')->first();
        $spm = $spp?->spm;
        $npi = $spm?->npi;
        $sp2d = $npi?->sp2d;

        $isSp2dExecuted = $sp2d && $sp2d->status === DokumenSp2d::STATUS_EXECUTED;
        $isTagihanSelesai = $tagihan?->status === 'SELESAI';
        $isReadyForPenyetoran = $isSp2dExecuted && $isTagihanSelesai;

        $statusSetor = 'Belum Billing';
        if ($potongan->ntpn) {
            $statusSetor = 'Sudah Setor';
        } elseif ($potongan->kode_billing) {
            $statusSetor = 'Sudah Billing';
        }

        $canInputBilling = $isReadyForPenyetoran && ! $potongan->ntpn;
        $canInputNtpn = $isReadyForPenyetoran && filled($potongan->kode_billing) && ! $potongan->ntpn;

        $selisihPph = round((float) $detailHonor->sum('pph') - (float) $potongan->nominal_potongan, 2);

        $arsipBilling = $potongan->arsipDokumen->where('jenis_dokumen', 'KODE_BILLING')->first();
        $arsipBpn = $potongan->arsipDokumen->where('jenis_dokumen', 'BUKTI_SETOR_PAJAK')->first();
        $arsipBppu = $potongan->arsipDokumen->where('jenis_dokumen', 'BPPU')->first();

        return view('penyetoran_pajak_honor.detail', compact(
            'potongan', 'tagihan', 'detailHonor', 'coa', 'coaCode', 'coaName',
            'spp', 'spm', 'npi', 'sp2d', 'isSp2dExecuted', 'isTagihanSelesai', 'isReadyForPenyetoran',
            'statusSetor', 'canInputBilling', 'canInputNtpn', 'selisihPph',
            'arsipBilling', 'arsipBpn', 'arsipBppu'
        ));
    }

    /**
     * Simpan / Update Kode Billing + arsip E-Billing.
     */
    public function storeBilling(Request $request, $id)
    {
        $potongan = $this->findHonorPajakPotongan($id);

        if ($potongan->ntpn) {
            return back()->withErrors('Kode Billing tidak dapat diubah karena NTPN sudah terinput.');
        }

        if ($message = $this->penyetoranReadinessError($potongan)) {
            return back()->withErrors($message);
        }

        $existingBilling = $potongan->arsipDokumen()
            ->where('jenis_dokumen', 'KODE_BILLING')
            ->exists();

        $request->validate([
            'kode_billing' => 'required|string|max:50',
            'file_billing' => ($existingBilling ? 'nullable' : 'required') . '|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ], [
            'file_billing.required' => 'File E-Billing (cetakan DJP) wajib diunggah.',
        ]);

        DB::transaction(function () use ($request, $potongan) {
            $potongan->update(['kode_billing' => $request->kode_billing]);

            if ($request->hasFile('file_billing')) {
                $file = $request->file('file_billing');
                $path = $file->store('arsip/pajak-honor', 'public');

                ArsipDokumen::create([
                    'documentable_type' => PotonganTagihan::class,
                    'documentable_id'   => $potongan->id,
                    'jenis_dokumen'     => 'KODE_BILLING',
                    'nama_file_asli'    => $file->getClientOriginalName(),
                    'path_file'         => $path,
                    'mime_type'         => $file->getClientMimeType(),
                    'ukuran_file'       => $file->getSize(),
                    'uploaded_by'       => auth()->id(),
                    'uploaded_at'       => now(),
                    'keterangan'        => 'E-Billing (cetakan DJP) Kode Billing',
                ]);

                // Hapus arsip e-billing lama (selain yang baru) agar menyimpan versi terbaru saja.
                $potongan->arsipDokumen()
                    ->where('jenis_dokumen', 'KODE_BILLING')
                    ->where('path_file', '!=', $path)
                    ->delete();
            }

            LogStatusDokumen::create([
                'dokumen_type' => PotonganTagihan::class,
                'dokumen_id'   => $potongan->id,
                'user_id'      => auth()->id(),
                'role_saat_itu'=> 'Bendahara Pengeluaran',
                'status_baru'  => 'SUDAH_BILLING',
                'aksi'         => 'INPUT_KODE_BILLING',
                'catatan'      => 'Input Kode Billing: ' . $request->kode_billing,
                'ip_address'   => request()->ip(),
            ]);
        });

        return back()->with('success', 'Kode Billing berhasil disimpan.');
    }

    /**
     * Simpan NTPN + bukti setor (BPN wajib, BPPU opsional); auto post BKU &
     * finalisasi e-Bupot bila seluruh pajak honor tagihan tersetor.
     */
    public function storeNtpn(Request $request, $id)
    {
        $request->validate([
            'ntpn'             => 'required|string|max:50',
            'file_bukti_setor' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'file_bppu'        => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $potongan = $this->findHonorPajakPotongan($id);

        if (! $potongan->kode_billing) {
            return back()->withErrors('Tidak dapat menginput NTPN sebelum Kode Billing diisi.');
        }

        if ($potongan->ntpn) {
            return back()->withErrors('NTPN sudah terinput untuk potongan pajak ini.');
        }

        if ($message = $this->penyetoranReadinessError($potongan)) {
            return back()->withErrors($message);
        }

        $postedToBku = false;

        DB::transaction(function () use ($request, $potongan, &$postedToBku) {
            $potongan->update(['ntpn' => $request->ntpn]);

            $file = $request->file('file_bukti_setor');
            $path = $file->store('arsip/pajak-honor', 'public');
            ArsipDokumen::create([
                'documentable_type' => PotonganTagihan::class,
                'documentable_id'   => $potongan->id,
                'jenis_dokumen'     => 'BUKTI_SETOR_PAJAK',
                'nama_file_asli'    => $file->getClientOriginalName(),
                'path_file'         => $path,
                'mime_type'         => $file->getClientMimeType(),
                'ukuran_file'       => $file->getSize(),
                'uploaded_by'       => auth()->id(),
                'uploaded_at'       => now(),
                'keterangan'        => 'Bukti Penerimaan Negara (BPN)',
            ]);

            if ($request->hasFile('file_bppu')) {
                $bppu = $request->file('file_bppu');
                $bppuPath = $bppu->store('arsip/pajak-honor', 'public');
                ArsipDokumen::create([
                    'documentable_type' => PotonganTagihan::class,
                    'documentable_id'   => $potongan->id,
                    'jenis_dokumen'     => 'BPPU',
                    'nama_file_asli'    => $bppu->getClientOriginalName(),
                    'path_file'         => $bppuPath,
                    'mime_type'         => $bppu->getClientMimeType(),
                    'ukuran_file'       => $bppu->getSize(),
                    'uploaded_by'       => auth()->id(),
                    'uploaded_at'       => now(),
                    'keterangan'        => 'Bukti Pemotongan/Pemungutan Pajak (BPPU)',
                ]);
            }

            LogStatusDokumen::create([
                'dokumen_type' => PotonganTagihan::class,
                'dokumen_id'   => $potongan->id,
                'user_id'      => auth()->id(),
                'role_saat_itu'=> 'Bendahara Pengeluaran',
                'status_baru'  => 'SUDAH_SETOR',
                'aksi'         => 'INPUT_NTPN',
                'catatan'      => 'Input NTPN: ' . $request->ntpn,
                'ip_address'   => request()->ip(),
            ]);

            $postedToBku = $this->postBkuIfAllPajakSettled($potongan);
            $this->finalizeBupotIfAllPajakSettled($potongan);
        });

        return back()->with('success', $postedToBku
            ? 'NTPN dan Bukti Setor berhasil disimpan. Tagihan honorarium sudah masuk BKU dan e-Bupot 21 difinalisasi.'
            : 'NTPN dan Bukti Setor berhasil disimpan. BKU akan dibuat setelah seluruh pajak honor tersetor.');
    }

    /**
     * Cetak ringkasan satu baris penyetoran pajak honor.
     */
    public function cetak($id)
    {
        $potongan = PotonganTagihan::with([
            'tagihan.detailHonorarium',
            'tagihan.spps.spm.npi.sp2d',
            'pajak',
        ])->findOrFail($id);

        abort_if($potongan->tagihan?->tipe_tagihan !== 'HONORARIUM', 404, 'Potongan ini bukan tipe honorarium.');
        abort_if($potongan->jenis_potongan !== 'PAJAK', 404, 'Potongan ini bukan potongan pajak honorarium.');

        $tagihan = $potongan->tagihan;
        $spp = $tagihan?->spps?->sortByDesc('created_at')->first();
        $sp2d = $spp?->spm?->npi?->sp2d;

        return view('penyetoran_pajak_honor.cetak', compact('potongan', 'tagihan', 'spp', 'sp2d'));
    }

    /**
     * Cetak e-Bupot 21 per penerima honorarium.
     */
    public function bupot($detailHonorariumId)
    {
        $detail = DetailHonorarium::with([
            'tagihan.spps.spm.npi.sp2d',
            'tagihan.potonganTagihan',
        ])->findOrFail($detailHonorariumId);

        $tagihan = $detail->tagihan;
        abort_if($tagihan?->tipe_tagihan !== 'HONORARIUM', 404, 'Detail ini bukan honorarium.');

        $spp = $tagihan?->spps?->sortByDesc('created_at')->first();
        $sp2d = $spp?->spm?->npi?->sp2d;

        $pajak = MasterTarifPajak::where('status_aktif', true)
            ->where('kode_pajak', 'PPH21-TER')
            ->first()
            ?? MasterTarifPajak::where('status_aktif', true)
                ->where('jenis_pajak', 'like', '%21%')
                ->orderByDesc('berlaku_mulai')
                ->first();

        // NTPN agregat dari seluruh baris pajak honor pada tagihan.
        $ntpnList = $tagihan?->potonganTagihan
            ->where('jenis_potongan', 'PAJAK')
            ->pluck('ntpn')
            ->filter()
            ->unique()
            ->values();

        $pemotong = \App\Models\User::role('Bendahara Pengeluaran')->with('profilable')->first();

        $isFinal = $detail->bupot_status === 'FINAL';

        return view('penyetoran_pajak_honor.bupot', compact(
            'detail', 'tagihan', 'spp', 'sp2d', 'pajak', 'ntpnList', 'pemotong', 'isFinal'
        ));
    }

    // ===================================================================
    // Helpers
    // ===================================================================

    private function findHonorPajakPotongan($id): PotonganTagihan
    {
        return PotonganTagihan::with([
            'tagihan.detailHonorarium',
            'tagihan.spps.spm.npi.sp2d',
            'tagihan.potonganTagihan',
        ])
            ->where('jenis_potongan', 'PAJAK')
            ->whereHas('tagihan', fn ($q) => $q->where('tipe_tagihan', 'HONORARIUM'))
            ->findOrFail($id);
    }

    private function penyetoranReadinessError(PotonganTagihan $potongan): ?string
    {
        $tagihan = $potongan->tagihan;
        $sp2d = $this->resolveSp2d($potongan);

        if (! $sp2d || $sp2d->status !== DokumenSp2d::STATUS_EXECUTED) {
            return 'Upload bukti transfer SP2D terlebih dahulu. Setelah SP2D dieksekusi, status tagihan akan menjadi SELESAI dan penyetoran pajak dapat diisi.';
        }

        if ($tagihan?->status !== 'SELESAI') {
            return 'Status tagihan belum SELESAI. Selesaikan proses upload bukti transfer SP2D terlebih dahulu.';
        }

        return null;
    }

    private function resolveSp2d(PotonganTagihan $potongan): ?DokumenSp2d
    {
        $spp = $potongan->tagihan?->spps?->sortByDesc('created_at')->first();

        return $spp?->spm?->npi?->sp2d;
    }

    private function postBkuIfAllPajakSettled(PotonganTagihan $potongan): bool
    {
        $tagihan = $potongan->tagihan;

        if (! $tagihan) {
            return false;
        }

        $hasUnsettledTax = $tagihan->potonganTagihan()
            ->where('jenis_potongan', 'PAJAK')
            ->where('nominal_potongan', '>', 0)
            ->where(function ($q) {
                $q->whereNull('ntpn')->orWhere('ntpn', '');
            })
            ->exists();

        if ($hasUnsettledTax) {
            return false;
        }

        app(BkuPostingService::class)->postTagihanPengeluaran(
            $tagihan,
            $this->resolveSp2d($potongan),
            'Pembayaran tagihan honorarium setelah bukti transfer SP2D dan seluruh setoran pajak honor lengkap.',
            (float) ($tagihan->total_bruto ?? $tagihan->total_netto ?? 0)
        );

        return true;
    }

    private function finalizeBupotIfAllPajakSettled(PotonganTagihan $potongan): void
    {
        $tagihan = $potongan->tagihan;
        if (! $tagihan) {
            return;
        }

        $hasUnsettledTax = $tagihan->potonganTagihan()
            ->where('jenis_potongan', 'PAJAK')
            ->where('nominal_potongan', '>', 0)
            ->where(function ($q) {
                $q->whereNull('ntpn')->orWhere('ntpn', '');
            })
            ->exists();

        if ($hasUnsettledTax) {
            return;
        }

        $tahun = (int) (optional($this->resolveSp2d($potongan)?->tanggal_sp2d)->format('Y') ?? now()->format('Y'));

        $details = $tagihan->detailHonorarium()->where('bupot_status', '!=', 'FINAL')->get();
        foreach ($details as $detail) {
            $detail->update([
                'bupot_status' => 'FINAL',
                'nomor_bupot'  => $this->generateNomorBupot($tahun),
            ]);
        }

        if ($details->isNotEmpty()) {
            LogStatusDokumen::create([
                'dokumen_type' => DetailHonorarium::class,
                'dokumen_id'   => $tagihan->id,
                'user_id'      => auth()->id(),
                'role_saat_itu'=> 'Bendahara Pengeluaran',
                'status_baru'  => 'BUPOT_FINAL',
                'aksi'         => 'FINALIZE_BUPOT_HONOR',
                'catatan'      => 'Finalisasi e-Bupot 21 untuk ' . $details->count() . ' penerima honorarium.',
                'ip_address'   => request()->ip(),
            ]);
        }
    }

    private function generateNomorBupot(int $tahun): string
    {
        $prefix = "BP21/{$tahun}/";

        $last = DetailHonorarium::where('nomor_bupot', 'like', $prefix . '%')
            ->orderByDesc('nomor_bupot')
            ->value('nomor_bupot');

        $next = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        return $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }
}
