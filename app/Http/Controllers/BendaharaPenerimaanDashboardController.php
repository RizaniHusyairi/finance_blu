<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Tagihan;
use App\Models\DokumenNpi;
use App\Models\BukuKasUmum;
use App\Models\DetailMutasiBank;
use App\Models\RekonsiliasiBank;
use App\Models\LaporanPengesahanBlu;
use App\Models\LogStatusDokumen;
use App\Models\TransaksiPenerimaan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class BendaharaPenerimaanDashboardController extends Controller
{
    public function index()
    {
        $now = Carbon::now();
        $user = Auth::user();

        // 1. Tagihan Perjaldin Menunggu Verifikasi
        $tagihanPerjaldin = Tagihan::where('tipe_tagihan', 'PERJALDIN')
            ->whereHas('workflowInstances.approvals', function ($q) {
                $q->where('role_code', 'Bendahara Penerimaan')
                  ->where('status', 'PENDING');
            })
            ->with([
                'detailPerjaldin.pegawai',
                'logs' => fn ($q) => $q->latest()
            ])
            ->latest()
            ->get();
            
        // 2. NPI Kontrak Menunggu Verifikasi
        $npiKontrak = DokumenNpi::whereHas('spm.spp.tagihan', fn($q) => $q->where('tipe_tagihan', 'KONTRAK'))
            ->whereHas('workflowInstances.approvals', function ($q) {
                $q->where('role_code', 'Bendahara Penerimaan')
                  ->where('status', 'PENDING');
            })
            ->with([
                'spm.spp.tagihan.detailKontrak.kontrakTermin.kontrak.vendor',
                'workflowInstances.approvals'
            ])
            ->latest()
            ->get();

        // 3. NPI Perjaldin Menunggu Verifikasi
        $npiPerjaldin = DokumenNpi::whereHas('spm.spp', fn($q) => $q->whereNotNull('tagihan_perjaldin_komponen_id'))
            ->whereHas('workflowInstances.approvals', function ($q) {
                $q->where('role_code', 'Bendahara Penerimaan')
                  ->where('status', 'PENDING');
            })
            ->with([
                'spm.spp.tagihan.detailPerjaldin.pegawai',
                'workflowInstances.approvals'
            ])
            ->latest()
            ->get();

        // 4. NPI Honor Menunggu Verifikasi (using general NPI for now, if it matches status)
        // Usually NPI Honor uses status STATUS_SUBMITTED_BENPEN or workflow.
        // Assuming workflow for Bendahara Penerimaan
        $npiHonor = DokumenNpi::whereHas('spm.spp.tagihan', fn($q) => $q->where('tipe_tagihan', 'HONORARIUM'))
            ->where(function($q) {
                $q->where('status', DokumenNpi::STATUS_SUBMITTED_BENPEN)
                  ->orWhereHas('workflowInstances.approvals', function ($sq) {
                      $sq->where('role_code', 'Bendahara Penerimaan')
                        ->where('status', 'PENDING');
                  });
            })
            ->with([
                'spm.spp.tagihan.detailHonorarium'
            ])
            ->latest()
            ->get();

        // 5. Pembukuan Summary
        $bkuBulanIni = BukuKasUmum::whereMonth('tanggal_transaksi', $now->month)
            ->whereYear('tanggal_transaksi', $now->year)
            ->get();
            
        $totalDebitBulanIni = $bkuBulanIni->sum('debit');
        $totalKreditBulanIni = $bkuBulanIni->sum('kredit');
        $saldoTerakhirBku = BukuKasUmum::latest('tanggal_transaksi')->latest('id')->value('saldo_akhir') ?? 0;
        
        $bkuTerbaru = BukuKasUmum::latest('tanggal_transaksi')->latest('id')->take(5)->get();

        // Mutasi & Rekonsiliasi
        $mutasiBelumRekon = DetailMutasiBank::doesntHave('rekonsiliasiBanks')->count();
        $rekonMatched = RekonsiliasiBank::where('selisih', 0)->count();
        
        $bukuBank = RekonsiliasiBank::with('detailMutasiBank')->latest()->take(5)->get();
        $mutasiPending = DetailMutasiBank::doesntHave('rekonsiliasiBanks')->latest()->take(5)->get();

        // Bunga Rekening
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

        // Laporan Pengesahan
        $laporanPengesahan = LaporanPengesahanBlu::latest()->take(5)->get();

        // 6. Piutang / Transaksi Penerimaan
        $piutangPending = TransaksiPenerimaan::whereIn('status_pembayaran', ['UNPAID', 'PARTIAL'])->get();
        $totalPiutangPending = $piutangPending->count();
        $nominalPiutangOutstanding = $piutangPending->sum(function($item) {
            return $item->nominal_tagihan - $item->total_dibayar;
        });
        
        $piutangTable = TransaksiPenerimaan::whereIn('status_pembayaran', ['UNPAID', 'PARTIAL'])
            ->with('mitra')
            ->orderBy('tanggal_jatuh_tempo', 'asc')
            ->take(5)->get();

        // 7. Aktivitas Terbaru
        $aktivitasTerbaru = LogStatusDokumen::with('user')
            ->where('role_saat_itu', 'Bendahara Penerimaan')
            ->latest()
            ->take(10)
            ->get();
            
        // Notifikasi (dummy if not using db notifications, or fetch from auth()->user()->unreadNotifications)
        $notifikasi = $user->unreadNotifications()->take(5)->get();

        // Combine Verifikasi List for the big widget
        // Map to standard format
        $antreanList = collect();
        
        foreach ($tagihanPerjaldin as $t) {
            $isRevisi = $t->status == 'REVISI_BENDAHARA';
            $antreanList->push((object)[
                'id' => $t->id,
                'jenis' => 'Tagihan Perjaldin',
                'nomor' => $t->nomor_tagihan,
                'uraian' => $t->deskripsi,
                'nominal' => $t->total_netto,
                'tanggal' => $t->created_at,
                'usia' => $t->created_at->diffInDays($now),
                'is_revisi' => $isRevisi,
                'status' => $t->status,
                'url' => route('verifikasi-bendahara-penerimaan.perjaldin.show', $t->id),
                'tab' => 'perjaldin'
            ]);
        }
        
        foreach ($npiKontrak as $n) {
            $isRevisi = $n->status == DokumenNpi::STATUS_REVISI;
            $antreanList->push((object)[
                'id' => $n->id,
                'jenis' => 'NPI Kontrak',
                'nomor' => $n->nomor_npi ?? '-',
                'uraian' => $n->spm?->spp?->tagihan?->deskripsi ?? 'NPI Kontrak',
                'nominal' => $n->spm?->nominal_spm ?? 0,
                'tanggal' => $n->created_at,
                'usia' => $n->created_at->diffInDays($now),
                'is_revisi' => $isRevisi,
                'status' => $n->status,
                'url' => route('verifikasi-bendahara-penerimaan.npi.kontrak.show', $n->id),
                'tab' => 'npi_kontrak'
            ]);
        }

        foreach ($npiPerjaldin as $n) {
            $isRevisi = $n->status == DokumenNpi::STATUS_REVISI;
            $antreanList->push((object)[
                'id' => $n->id,
                'jenis' => 'NPI Perjaldin',
                'nomor' => $n->nomor_npi ?? '-',
                'uraian' => 'Verifikasi NPI Perjaldin',
                'nominal' => $n->spm?->nominal_spm ?? 0,
                'tanggal' => $n->created_at,
                'usia' => $n->created_at->diffInDays($now),
                'is_revisi' => $isRevisi,
                'status' => $n->status,
                'url' => '#', // route('verifikasi-bendahara-penerimaan.npi.perjaldin.show', $n->id) // Assuming route exists
                'tab' => 'npi_perjaldin'
            ]);
        }

        foreach ($npiHonor as $n) {
            $isRevisi = $n->status == DokumenNpi::STATUS_REVISI;
            $antreanList->push((object)[
                'id' => $n->id,
                'jenis' => 'NPI Honor',
                'nomor' => $n->nomor_npi ?? '-',
                'uraian' => 'Verifikasi NPI Honorarium',
                'nominal' => $n->spm?->nominal_spm ?? 0,
                'tanggal' => $n->created_at,
                'usia' => $n->created_at->diffInDays($now),
                'is_revisi' => $isRevisi,
                'status' => $n->status,
                'url' => '#', // route('verifikasi-bendahara-penerimaan.npi.honor.show', $n->id) // Assuming route exists
                'tab' => 'npi_honor'
            ]);
        }

        // Sort Prioritas Hari Ini
        $prioritasHariIni = $antreanList->sortByDesc('is_revisi')
            ->sortByDesc('usia')
            ->sortByDesc('nominal')
            ->take(5);

        return view('dashboards.bendahara_penerimaan', compact(
            'now', 'user',
            'tagihanPerjaldin', 'npiKontrak', 'npiPerjaldin', 'npiHonor',
            'bkuBulanIni', 'totalDebitBulanIni', 'totalKreditBulanIni', 'saldoTerakhirBku', 'bkuTerbaru',
            'mutasiBelumRekon', 'rekonMatched', 'bukuBank', 'mutasiPending',
            'bungaRekeningBulanIni', 'transaksiBunga',
            'laporanPengesahan',
            'piutangPending', 'totalPiutangPending', 'nominalPiutangOutstanding', 'piutangTable',
            'aktivitasTerbaru', 'notifikasi',
            'antreanList', 'prioritasHariIni'
        ));
    }
}
