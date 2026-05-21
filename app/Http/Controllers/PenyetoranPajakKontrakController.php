<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PotonganTagihan;
use App\Models\DokumenSp2d;
use App\Models\ArsipDokumen;
use App\Models\LogStatusDokumen;
use App\Services\BkuPostingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PenyetoranPajakKontrakController extends Controller
{
    /**
     * Daftar potongan pajak dari tagihan KONTRAK yang SP2D-nya sudah final.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        if (!$user->hasRole('Bendahara Pengeluaran') && !$user->hasRole('Super Admin')) {
            abort(403, 'Akses ditolak.');
        }

        $query = PotonganTagihan::with([
            'tagihan.detailKontrak.kontrakTermin.kontrak.vendor',
            'tagihan.spps.spm.npi.sp2d',
            'tagihan.pihak',
            'pajak',
            'akunPotongan',
        ])
        ->where('jenis_potongan', 'PAJAK')
        ->whereHas('tagihan', fn($q) => $q->where('tipe_tagihan', 'KONTRAK'))
        ->whereHas('tagihan.spps.spm.npi.sp2d', function($q) {
            $q->where('status', DokumenSp2d::STATUS_EXECUTED);
        })
        ->whereHas('tagihan', function ($q) {
            $q->where('status', 'SELESAI');
        });

        // Search
        if ($search = $request->input('search')) {
            $query->where(function($q) use ($search) {
                $q->where('kode_billing', 'like', "%{$search}%")
                  ->orWhere('ntpn', 'like', "%{$search}%")
                  ->orWhere('jenis_potongan', 'like', "%{$search}%")
                  ->orWhere('nama_pajak_snapshot', 'like', "%{$search}%")
                  ->orWhereHas('tagihan', function($sq) use ($search) {
                      $sq->where('nomor_tagihan', 'like', "%{$search}%")
                         ->orWhere('deskripsi', 'like', "%{$search}%");
                  })
                  ->orWhereHas('tagihan.pihak', function($sq) use ($search) {
                      $sq->where('nama', 'like', "%{$search}%");
                  });
            });
        }

        // Filter status setor
        $statusFilter = $request->input('status', 'semua');
        if ($statusFilter === 'belum_billing') {
            $query->whereNull('kode_billing');
        } elseif ($statusFilter === 'sudah_billing') {
            $query->whereNotNull('kode_billing')->whereNull('ntpn');
        } elseif ($statusFilter === 'sudah_setor') {
            $query->whereNotNull('kode_billing')->whereNotNull('ntpn');
        }

        $potonganList = $query->latest()->get();

        // Summary dari total data (tanpa filter status)
        $allForSummary = PotonganTagihan::whereHas('tagihan', fn($q) => $q->where('tipe_tagihan', 'KONTRAK'))
            ->where('jenis_potongan', 'PAJAK')
            ->whereHas('tagihan', fn($q) => $q->where('status', 'SELESAI'))
            ->whereHas('tagihan.spps.spm.npi.sp2d', fn($q) => $q->where('status', DokumenSp2d::STATUS_EXECUTED))
            ->get(['id', 'kode_billing', 'ntpn']);

        $summary = [
            'belum_billing' => $allForSummary->filter(fn($p) => !$p->kode_billing)->count(),
            'sudah_billing' => $allForSummary->filter(fn($p) => $p->kode_billing && !$p->ntpn)->count(),
            'sudah_setor'   => $allForSummary->filter(fn($p) => $p->kode_billing && $p->ntpn)->count(),
        ];

        return view('penyetoran_pajak_kontrak.index', compact('potonganList', 'summary', 'statusFilter', 'search'));
    }

    /**
     * Detail / workspace penyetoran pajak kontrak.
     */
    public function show($id)
    {
        $potongan = PotonganTagihan::with([
            'tagihan.detailKontrak.kontrakTermin.kontrak.vendor',
            'tagihan.detailKontrak.kontrakTermin.kontrak.dipaRevisionItem.coa',
            'tagihan.dipaRevisionItem.coa',
            'tagihan.spps.standingInstruction',
            'tagihan.spps.spm.npi.sp2d.arsipDokumen',
            'tagihan.pihak',
            'pajak',
            'akunPotongan',
            'arsipDokumen.uploader',
        ])->findOrFail($id);

        $tagihan = $potongan->tagihan;
        abort_if($tagihan?->tipe_tagihan !== 'KONTRAK', 404, 'Potongan ini bukan tipe kontrak.');
        abort_if($potongan->jenis_potongan !== 'PAJAK', 404, 'Potongan ini bukan potongan pajak kontrak.');

        $detailKontrak = $tagihan?->detailKontrak;
        $kontrakTermin = $detailKontrak?->kontrakTermin;
        $kontrak = $kontrakTermin?->kontrak;
        $vendor = $kontrak?->vendor ?? $tagihan?->pihak;
        $coa = $kontrak?->dipaRevisionItem?->coa ?? $tagihan?->dipaRevisionItem?->coa ?? $potongan->akunPotongan;

        $nomorKontrak = $kontrak?->nomor_spk ?? $kontrak?->nomor_kontrak ?? '-';
        $judulKontrak = $kontrak?->nama_pekerjaan ?? $kontrak?->judul_kontrak ?? '-';
        $vendorName = $vendor?->nama_pihak ?? $vendor?->nama ?? $vendor?->nama_perusahaan ?? '-';
        $terminText = $kontrakTermin?->nama_termin ?? $kontrakTermin?->keterangan_termin;
        if (!$terminText && ($kontrakTermin?->termin_ke || $kontrakTermin?->jenis_termin)) {
            $terminText = trim(implode(' ', array_filter([
                $kontrakTermin?->termin_ke ? 'Termin ' . $kontrakTermin->termin_ke : null,
                $kontrakTermin?->jenis_termin ? '(' . str_replace('_', ' ', $kontrakTermin->jenis_termin) . ')' : null,
            ])));
        }
        $terminText = $terminText ?: '-';
        $coaCode = $coa?->kode_mak_lengkap ?? $coa?->kode_akun ?? $coa?->kd_akun ?? '-';
        $coaName = $coa?->nama_akun ?? '';

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

        $canInputBilling = $isReadyForPenyetoran && !$potongan->ntpn;
        $canInputNtpn = $isReadyForPenyetoran && filled($potongan->kode_billing);

        $arsipBilling = $potongan->arsipDokumen->where('jenis_dokumen', 'KODE_BILLING')->first();
        $arsipBpn = $potongan->arsipDokumen->where('jenis_dokumen', 'BUKTI_SETOR_PAJAK')->first();
        $arsipBppu = $potongan->arsipDokumen->where('jenis_dokumen', 'BPPU')->first();

        return view('penyetoran_pajak_kontrak.detail', compact(
            'potongan', 'tagihan', 'detailKontrak', 'kontrakTermin', 'kontrak', 'vendor',
            'coa', 'nomorKontrak', 'judulKontrak', 'vendorName', 'terminText', 'coaCode', 'coaName',
            'spp', 'spm', 'npi', 'sp2d', 'isSp2dExecuted', 'isTagihanSelesai', 'isReadyForPenyetoran', 'statusSetor',
            'canInputBilling', 'canInputNtpn',
            'arsipBilling', 'arsipBpn', 'arsipBppu'
        ));
    }

    /**
     * Simpan / Update Kode Billing.
     */
    public function storeBilling(Request $request, $id)
    {
        $potongan = $this->findKontrakPajakPotongan($id);

        if ($potongan->ntpn) {
            return back()->withErrors('Kode Billing tidak dapat diubah karena NTPN sudah terinput.');
        }

        if ($message = $this->penyetoranReadinessError($potongan)) {
            return back()->withErrors($message);
        }

        // E-Billing (cetakan DJP) wajib diunggah pertama kali; saat update boleh kosong
        // (akan mempertahankan file lama).
        $existingBilling = $potongan->arsipDokumen()
            ->where('jenis_dokumen', 'KODE_BILLING')
            ->exists();

        $request->validate([
            'kode_billing'  => 'required|string|max:50',
            'file_billing'  => ($existingBilling ? 'nullable' : 'required') . '|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ], [
            'file_billing.required' => 'File E-Billing (cetakan DJP) wajib diunggah.',
        ]);

        DB::transaction(function() use ($request, $potongan) {
            $potongan->update(['kode_billing' => $request->kode_billing]);

            if ($request->hasFile('file_billing')) {
                // Hapus arsip e-billing lama agar hanya menyimpan versi terbaru
                $potongan->arsipDokumen()
                    ->where('jenis_dokumen', 'KODE_BILLING')
                    ->delete();

                $file = $request->file('file_billing');
                $path = $file->store('arsip/pajak-kontrak', 'public');

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
     * Simpan / Update NTPN beserta bukti setor (wajib).
     */
    public function storeNtpn(Request $request, $id)
    {
        $request->validate([
            'ntpn'             => 'required|string|max:50',
            'file_bukti_setor' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'file_bppu'        => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $potongan = $this->findKontrakPajakPotongan($id);

        if (!$potongan->kode_billing) {
            return back()->withErrors('Tidak dapat menginput NTPN sebelum Kode Billing diisi.');
        }

        if ($potongan->ntpn) {
            return back()->withErrors('NTPN sudah terinput untuk potongan pajak ini.');
        }

        if ($message = $this->penyetoranReadinessError($potongan)) {
            return back()->withErrors($message);
        }

        $postedToBku = false;

        DB::transaction(function() use ($request, $potongan, &$postedToBku) {
            $potongan->update(['ntpn' => $request->ntpn]);

            // Bukti Setor (BPN) — wajib
            $file = $request->file('file_bukti_setor');
            $path = $file->store('arsip/pajak-kontrak', 'public');
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

            // BPPU — opsional
            if ($request->hasFile('file_bppu')) {
                $bppu = $request->file('file_bppu');
                $bppuPath = $bppu->store('arsip/pajak-kontrak', 'public');
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
        });

        return back()->with('success', $postedToBku
            ? 'NTPN dan Bukti Setor berhasil disimpan. Status: Sudah Setor. Tagihan kontrak sudah masuk BKU.'
            : 'NTPN dan Bukti Setor berhasil disimpan. Status: Sudah Setor. BKU akan dibuat setelah seluruh potongan pajak kontrak tersetor.');
    }

    /**
     * Cetak ringkasan (placeholder).
     */
    public function cetak($id)
    {
        $potongan = PotonganTagihan::with(['tagihan.detailKontrak.kontrakTermin.kontrak'])->findOrFail($id);
        return view('penyetoran_pajak_kontrak.cetak', compact('potongan'));
    }

    private function findKontrakPajakPotongan($id): PotonganTagihan
    {
        return PotonganTagihan::with([
            'tagihan.spps.standingInstruction',
            'tagihan.spps.spm.npi.sp2d',
            'tagihan.potonganTagihan',
        ])
            ->where('jenis_potongan', 'PAJAK')
            ->whereHas('tagihan', fn ($q) => $q->where('tipe_tagihan', 'KONTRAK'))
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
            'Pembayaran tagihan kontrak setelah bukti transfer SP2D dan seluruh setoran pajak lengkap.',
            (float) ($tagihan->total_bruto ?? $tagihan->total_netto ?? 0)
        );

        return true;
    }

    private function resolveSp2d(PotonganTagihan $potongan): ?DokumenSp2d
    {
        $spp = $potongan->tagihan?->spps?->sortByDesc('created_at')->first();

        return $spp?->spm?->npi?->sp2d;
    }
}
