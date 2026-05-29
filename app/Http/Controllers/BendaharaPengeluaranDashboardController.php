<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Tagihan;
use App\Models\DokumenSpm;
use App\Models\DokumenNpi;
use App\Models\DokumenSp2d;
use App\Models\PotonganTagihan;
use App\Models\BukuKasUmum;
use App\Models\DetailMutasiBank;
use App\Models\RekonsiliasiBank;
use App\Models\LaporanPengesahanBlu;
use App\Models\LogStatusDokumen;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class BendaharaPengeluaranDashboardController extends Controller
{
    public function index()
    {
        $now = Carbon::now();
        $user = Auth::user();

        // 1. Verifikasi Tagihan (Perjaldin, Honorarium)
        $tagihanPerjaldin = Tagihan::where('tipe_tagihan', 'PERJALDIN')
            ->whereHas('workflowInstances.approvals', function ($q) {
                $q->where('role_code', 'Bendahara Pengeluaran')
                  ->where('status', 'PENDING');
            })
            ->with(['detailPerjaldin.pegawai', 'logs' => fn($q) => $q->latest()])
            ->latest()
            ->get();
            
        $tagihanHonorarium = Tagihan::where('tipe_tagihan', 'HONORARIUM')
            ->whereHas('workflowInstances.approvals', function ($q) {
                $q->where('role_code', 'Bendahara Pengeluaran')
                  ->where('status', 'PENDING');
            })
            ->with(['detailHonorarium', 'logs' => fn($q) => $q->latest()])
            ->latest()
            ->get();

        // 2. Pembuatan NPI (Siap dibuat = SPM Final, belum ada NPI atau NPI draft)
        // SPM Kontrak Siap Dibuat NPI
        $spmKontrakSiapNpi = DokumenSpm::whereHas('spp.tagihan', fn($q) => $q->where('tipe_tagihan', 'KONTRAK'))
            ->whereIn('status', [DokumenSpm::STATUS_DISETUJUI_FINAL, DokumenSpm::STATUS_APPROVED_KASUBAG])
            ->where(function ($q) {
                $q->whereDoesntHave('npi')
                  ->orWhereHas('npi', fn($sq) => $sq->where('status', DokumenNpi::STATUS_DRAFT));
            })
            ->with('spp.tagihan.detailKontrak.kontrakTermin.kontrak.vendor')
            ->latest()
            ->get();
            
        // SPM Perjaldin Siap Dibuat NPI
        $spmPerjaldinSiapNpi = DokumenSpm::whereHas('spp.tagihan', fn($q) => $q->where('tipe_tagihan', 'PERJALDIN'))
            ->whereIn('status', [DokumenSpm::STATUS_DISETUJUI_FINAL, DokumenSpm::STATUS_APPROVED_KASUBAG])
            ->where(function ($q) {
                $q->whereDoesntHave('npi')
                  ->orWhereHas('npi', fn($sq) => $sq->where('status', DokumenNpi::STATUS_DRAFT));
            })
            ->with('spp.tagihan.detailPerjaldin.pegawai')
            ->latest()
            ->get();

        // SPM Honor Siap Dibuat NPI
        $spmHonorSiapNpi = DokumenSpm::whereHas('spp.tagihan', fn($q) => $q->where('tipe_tagihan', 'HONORARIUM'))
            ->whereIn('status', [DokumenSpm::STATUS_DISETUJUI_FINAL, DokumenSpm::STATUS_APPROVED_KASUBAG])
            ->where(function ($q) {
                $q->whereDoesntHave('npi')
                  ->orWhereHas('npi', fn($sq) => $sq->where('status', DokumenNpi::STATUS_DRAFT));
            })
            ->with('spp.tagihan.detailHonorarium')
            ->latest()
            ->get();

        // Total NPI Siap Dibuat
        $totalNpiSiap = $spmKontrakSiapNpi->count() + $spmPerjaldinSiapNpi->count() + $spmHonorSiapNpi->count();

        // 3. Pencatatan SP2D (Siap dicatat = NPI Final, belum ada SP2D atau SP2D draft/revisi)
        $npiKontrakSiapSp2d = DokumenNpi::whereHas('spm.spp.tagihan', fn($q) => $q->where('tipe_tagihan', 'KONTRAK'))
            ->whereIn('status', [DokumenNpi::STATUS_DISETUJUI_FINAL, DokumenNpi::STATUS_APPROVED_KASUBAG])
            ->where(function ($q) {
                $q->whereDoesntHave('sp2d')
                  ->orWhereHas('sp2d', fn($sq) => $sq->whereIn('status', [DokumenSp2d::STATUS_DRAFT, DokumenSp2d::STATUS_REVISI]));
            })
            ->with('spm.spp.tagihan.detailKontrak.kontrakTermin.kontrak.vendor')
            ->latest()
            ->get();

        $npiPerjaldinSiapSp2d = DokumenNpi::whereHas('spm.spp.tagihan', fn($q) => $q->where('tipe_tagihan', 'PERJALDIN'))
            ->whereIn('status', [DokumenNpi::STATUS_DISETUJUI_FINAL, DokumenNpi::STATUS_APPROVED_KASUBAG])
            ->where(function ($q) {
                $q->whereDoesntHave('sp2d')
                  ->orWhereHas('sp2d', fn($sq) => $sq->whereIn('status', [DokumenSp2d::STATUS_DRAFT, DokumenSp2d::STATUS_REVISI]));
            })
            ->with('spm.spp.tagihan.detailPerjaldin.pegawai')
            ->latest()
            ->get();
            
        $npiHonorSiapSp2d = DokumenNpi::whereHas('spm.spp.tagihan', fn($q) => $q->where('tipe_tagihan', 'HONORARIUM'))
            ->whereIn('status', [DokumenNpi::STATUS_DISETUJUI_FINAL, DokumenNpi::STATUS_APPROVED_KASUBAG])
            ->where(function ($q) {
                $q->whereDoesntHave('sp2d')
                  ->orWhereHas('sp2d', fn($sq) => $sq->whereIn('status', [DokumenSp2d::STATUS_DRAFT, DokumenSp2d::STATUS_REVISI]));
            })
            ->with('spm.spp.tagihan.detailHonorarium')
            ->latest()
            ->get();

        $totalSp2dSiap = $npiKontrakSiapSp2d->count() + $npiPerjaldinSiapSp2d->count() + $npiHonorSiapSp2d->count();

        // 4. Penyetoran Pajak Kontrak
        $potonganPajak = PotonganTagihan::whereHas('tagihan', fn($q) => $q->where('tipe_tagihan', 'KONTRAK'))
            ->whereHas('tagihan.spps.spm.npi.sp2d', fn($q) => $q->where('status', DokumenSp2d::STATUS_DISETUJUI_FINAL))
            ->with(['tagihan', 'pajak'])
            ->latest()
            ->get();

        $pajakBelumBilling = $potonganPajak->whereNull('kode_billing');
        $pajakSudahBilling = $potonganPajak->whereNotNull('kode_billing')->whereNull('ntpn');
        $pajakSudahSetor = $potonganPajak->whereNotNull('ntpn');

        // 4b. Penyetoran Pajak Honorarium
        $potonganPajakHonor = PotonganTagihan::where('jenis_potongan', 'PAJAK')
            ->whereHas('tagihan', fn($q) => $q->where('tipe_tagihan', 'HONORARIUM')->where('status', 'SELESAI'))
            ->whereHas('tagihan.spps.spm.npi.sp2d', fn($q) => $q->where('status', DokumenSp2d::STATUS_EXECUTED))
            ->with(['tagihan', 'pajak'])
            ->latest()
            ->get();

        $pajakHonorBelumBilling = $potonganPajakHonor->whereNull('kode_billing');
        $pajakHonorSudahBilling = $potonganPajakHonor->whereNotNull('kode_billing')->whereNull('ntpn');
        $pajakHonorSudahSetor = $potonganPajakHonor->whereNotNull('ntpn');

        // 5. Pembukuan
        $bkuBulanIni = BukuKasUmum::whereMonth('tanggal_transaksi', $now->month)
            ->whereYear('tanggal_transaksi', $now->year)
            ->get();
            
        $totalPengeluaranBulanIni = $bkuBulanIni->sum('kredit');
        $saldoTerakhirBku = BukuKasUmum::latest('tanggal_transaksi')->latest('id')->value('saldo_akhir') ?? 0;
        $bkuTerbaru = BukuKasUmum::latest('tanggal_transaksi')->latest('id')->take(5)->get();

        $mutasiBelumRekon = DetailMutasiBank::doesntHave('rekonsiliasiBanks')->count();
        $rekonMatched = RekonsiliasiBank::where('selisih', 0)->count();
        $mutasiPending = DetailMutasiBank::doesntHave('rekonsiliasiBanks')->latest()->take(5)->get();

        $bungaRekeningBulanIni = DetailMutasiBank::whereMonth('tanggal_transaksi', $now->month)
            ->whereYear('tanggal_transaksi', $now->year)
            ->where(function($q) {
                $q->where('deskripsi', 'like', '%BUNGA%')
                  ->orWhere('deskripsi', 'like', '%INTEREST%');
            })->sum('kredit');
            
        $transaksiBunga = DetailMutasiBank::where(function($q) {
                $q->where('deskripsi', 'like', '%BUNGA%')
                  ->orWhere('deskripsi', 'like', '%INTEREST%');
            })->latest()->take(5)->get();

        $laporanPengesahan = LaporanPengesahanBlu::latest()->take(5)->get();

        // 6. Aktivitas & Notifikasi
        $aktivitasTerbaru = LogStatusDokumen::with('user')
            ->where('role_saat_itu', 'Bendahara Pengeluaran')
            ->latest()
            ->take(10)
            ->get();
            
        $notifikasi = collect(); // Or $user->unreadNotifications()->take(5)->get(); if enabled

        // Gabungan Antrean Verifikasi Saya
        $antreanList = collect();
        foreach ($tagihanPerjaldin as $t) {
            $antreanList->push((object)[
                'id' => $t->id,
                'jenis' => 'Tagihan Perjaldin',
                'nomor' => $t->nomor_tagihan,
                'uraian' => $t->deskripsi,
                'nominal' => $t->total_netto,
                'tanggal' => $t->created_at,
                'usia' => $t->created_at->diffInDays($now),
                'is_revisi' => $t->status == 'REVISI_BENDAHARA',
                'status' => $t->status,
                'url' => route('verifikasi-bendahara.perjaldin.show', $t->id),
                'tab' => 'perjaldin'
            ]);
        }
        foreach ($tagihanHonorarium as $t) {
            $antreanList->push((object)[
                'id' => $t->id,
                'jenis' => 'Tagihan Honorarium',
                'nomor' => $t->nomor_tagihan,
                'uraian' => $t->deskripsi,
                'nominal' => $t->total_netto,
                'tanggal' => $t->created_at,
                'usia' => $t->created_at->diffInDays($now),
                'is_revisi' => $t->status == 'REVISI_BENDAHARA',
                'status' => $t->status,
                'url' => route('verifikasi-bendahara.honorarium.show', $t->id),
                'tab' => 'honorarium'
            ]);
        }

        $prioritasHariIni = $antreanList->sortByDesc('is_revisi')
            ->sortByDesc('usia')
            ->sortByDesc('nominal')
            ->take(5);

        return view('dashboards.bendahara_pengeluaran', compact(
            'now', 'user',
            'tagihanPerjaldin', 'tagihanHonorarium',
            'spmKontrakSiapNpi', 'spmPerjaldinSiapNpi', 'spmHonorSiapNpi', 'totalNpiSiap',
            'npiKontrakSiapSp2d', 'npiPerjaldinSiapSp2d', 'npiHonorSiapSp2d', 'totalSp2dSiap',
            'potonganPajak', 'pajakBelumBilling', 'pajakSudahBilling', 'pajakSudahSetor',
            'potonganPajakHonor', 'pajakHonorBelumBilling', 'pajakHonorSudahBilling', 'pajakHonorSudahSetor',
            'bkuBulanIni', 'totalPengeluaranBulanIni', 'saldoTerakhirBku', 'bkuTerbaru',
            'mutasiBelumRekon', 'rekonMatched', 'mutasiPending',
            'bungaRekeningBulanIni', 'transaksiBunga',
            'laporanPengesahan',
            'aktivitasTerbaru', 'notifikasi',
            'antreanList', 'prioritasHariIni'
        ));
    }
}
