<?php

namespace App\Http\Controllers;

use App\Models\MasterDipa;
use App\Models\RiwayatRevisiDipa;
use App\Models\MasterCoa;
use App\Models\DetailDipa;
use App\Models\KontrakPengadaan;
use App\Models\Tagihan;
use App\Models\DetailPerjaldin;
use App\Models\MasterMitraVendor;
use App\Models\DokumenSpp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    /**
     * Internal Dashboard (KPA, Operator BLU, Bendahara, Kasubag, PPSPM, dll)
     */
    public function internal()
    {
        if (Auth::user()->hasRole('Pejabat Pengadaan')) {
            return $this->pejabatPengadaan();   
        }
        if (Auth::user()->hasRole('PPK')) {
            return $this->ppk();   
        }
        if (Auth::user()->hasRole('PPABP')) {
            return $this->ppabp();
        }
        if (Auth::user()->hasRole('Operator Perjaldin')) {
            return $this->operatorPerjaldin();
        }
        if (Auth::user()->hasRole('Bendahara Penerimaan')) {
            return redirect()->route('dashboard.bendahara-penerimaan');
        }
        if (Auth::user()->hasRole('Bendahara Pengeluaran')) {
            return redirect()->route('dashboard.bendahara-pengeluaran');
        }

        $now = now();

        // ============================================================
        // KPI CARDS
        // ============================================================
        $totalPagu = RiwayatRevisiDipa::where('is_active', true)->sum('total_pagu');
        $totalRealisasi = DB::table('realisasi_anggaran')->sum('nominal_cair');
        $sisaAnggaran = max(0, $totalPagu - $totalRealisasi);
        $persenRealisasi = $totalPagu > 0 ? round(($totalRealisasi / $totalPagu) * 100, 1) : 0;

        $totalKontrakAktif = KontrakPengadaan::where('status_kontrak', 'AKTIF')->count();
        $totalMitra = MasterMitraVendor::count();

        // Tagihan pipeline summary
        $tagihanPending = Tagihan::whereIn('status', ['DRAFT', 'PENDING_REVIEW', 'PENDING_BENDAHARA'])->count();
        $tagihanRevisi  = Tagihan::whereIn('status', ['DITOLAK_PPK', 'REVISI_BENDAHARA', 'REVISI'])->count();

        // SPP yang sudah diproses bulan ini
        $sppBulanIni = DokumenSpp::whereMonth('tanggal_spp', $now->month)
                        ->whereYear('tanggal_spp', $now->year)
                        ->count();

        // ============================================================
        // CHART: Serapan per Jenis Belanja (51, 52, 53, BLU)
        // ============================================================
        $jenisAkun = [
            ['label' => 'Belanja Pegawai (51)', 'pattern' => '51'],
            ['label' => 'Belanja Barang (52)',   'pattern' => '52'],
            ['label' => 'Belanja Modal (53)',    'pattern' => '53'],
            ['label' => 'Belanja BLU (525)',     'pattern' => '525'],
        ];

        $chartBarLabels = [];
        $chartBarPagu = [];
        $chartBarRealisasi = [];

        foreach ($jenisAkun as $ja) {
            $pagu = DB::table('dipa_revision_items')
                ->join('master_coas', 'dipa_revision_items.coa_id', '=', 'master_coas.id')
                ->where('master_coas.kd_akun', 'like', $ja['pattern'] . '%')
                ->sum('dipa_revision_items.nilai_pagu');
            
            $realisasi = DB::table('realisasi_anggaran')
                ->join('dipa_revision_items', 'realisasi_anggaran.dipa_revision_item_id', '=', 'dipa_revision_items.id')
                ->join('master_coas', 'dipa_revision_items.coa_id', '=', 'master_coas.id')
                ->where('master_coas.kd_akun', 'like', $ja['pattern'] . '%')
                ->sum('realisasi_anggaran.nominal_cair');
            
            $chartBarLabels[] = $ja['label'];
            $chartBarPagu[] = (float) $pagu;
            $chartBarRealisasi[] = (float) $realisasi;
        }

        $budgetItems = DetailDipa::query()
            ->join('master_coas', 'dipa_revision_items.coa_id', '=', 'master_coas.id')
            ->join('dipa_revisions', 'dipa_revision_items.dipa_revision_id', '=', 'dipa_revisions.id')
            ->join('master_dipas', 'dipa_revisions.master_dipa_id', '=', 'master_dipas.id')
            ->where('dipa_revisions.is_active', true)
            ->where('dipa_revision_items.status_aktif', true)
            ->select([
                'master_coas.kode_mak_lengkap as coa',
                'master_coas.nama_akun as description',
                'dipa_revision_items.nilai_pagu as initial_budget',
                'master_dipas.tahun_anggaran as year',
            ])
            ->latest('dipa_revision_items.updated_at')
            ->take(5)
            ->get();

        // ============================================================
        // CHART: Status Tagihan (Donut)
        // ============================================================
        $statusCounts = Tagihan::select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        // ============================================================
        // TABLE: Kontrak Aktif Terbaru
        // ============================================================
        $activeContracts = KontrakPengadaan::where('status_kontrak', 'AKTIF')
            ->with('vendor')
            ->latest()
            ->take(5)
            ->get();

        // ============================================================
        // TABLE: Tagihan Menunggu / Pending
        // ============================================================
        $pendingTagihan = Tagihan::whereIn('status', ['PENDING_REVIEW', 'PENDING_BENDAHARA'])
            ->latest()
            ->take(5)
            ->get();

        // ============================================================
        // TABLE: Kontrak Hampir Jatuh Tempo (H-14)
        // ============================================================
        $jatuhTempo = KontrakPengadaan::where('status_kontrak', 'AKTIF')
            ->where('tanggal_selesai', '<=', $now->copy()->addDays(14))
            ->with('vendor')
            ->orderBy('tanggal_selesai', 'asc')
            ->take(5)
            ->get();

        return view('dashboard.internal', compact(
            'totalPagu', 'totalRealisasi', 'sisaAnggaran', 'persenRealisasi',
            'totalKontrakAktif', 'totalMitra', 'tagihanPending', 'tagihanRevisi', 'sppBulanIni',
            'chartBarLabels', 'chartBarPagu', 'chartBarRealisasi',
            'statusCounts',
            'activeContracts', 'pendingTagihan', 'jatuhTempo', 'budgetItems'
        ));
    }

    /**
     * Mitra Portal Dashboard (External suppliers)
     */
    public function mitra()
    {
        $user = auth()->user();

        // Find vendor/mitra linked to this user via polymorphic profilable
        $profile = $user->profilable;
        $vendor = $profile instanceof \App\Models\MasterPihak
            ? MasterMitraVendor::find($profile->id)
            : null;

        $contracts = collect();
        $tagihan = collect();

        if ($vendor) {
            $contracts = KontrakPengadaan::where('vendor_id', $vendor->id)
                ->with(['termin'])
                ->latest()
                ->get();

            $contractIds = $contracts->pluck('id');

            // Tagihan terkait kontrak mitra ini (via detail_kontrak)
            $tagihan = Tagihan::where('tipe_tagihan', 'KONTRAK')
                ->whereHas('detailKontrak', function($q) use ($contractIds) {
                    $q->whereHas('kontrakTermin', function($q2) use ($contractIds) {
                        $q2->whereIn('kontrak_pengadaan_id', $contractIds);
                    });
                })
                ->latest()
                ->get();
        }

        // Summary stats for the mitra
        $totalKontrak = $contracts->count();
        $totalNilaiKontrak = $contracts->sum('nilai_total_kontrak');
        $totalDibayar = $tagihan->whereIn('status', ['CAIR', 'SP2D', 'SELESAI'])->sum('total_netto');
        $totalPending = $tagihan->whereNotIn('status', ['CAIR', 'SP2D', 'SELESAI', 'DITOLAK_PPK'])->sum('total_netto');

        return view('dashboard.mitra', compact(
            'vendor', 'contracts', 'tagihan',
            'totalKontrak', 'totalNilaiKontrak', 'totalDibayar', 'totalPending'
        ));
    }
    /**
     * Dashboard khusus Role Pejabat Pengadaan
     */
    private function pejabatPengadaan()
    {
        $data = \Illuminate\Support\Facades\Cache::remember('dash_pejabat_pengadaan_' . auth()->id(), 60, function() {
            $now = now();
            
            // KPI 1: Kontrak Aktif
            $kontrakAktif = \App\Models\KontrakPengadaan::where('status_kontrak', 'AKTIF')->count();
            $selesaiBulanIni = \App\Models\KontrakPengadaan::where('status_kontrak', 'SELESAI')
                                ->whereMonth('tanggal_selesai', $now->month)
                                ->whereYear('tanggal_selesai', $now->year)
                                ->count();
            
            // KPI 2: Total Nilai Berjalan
            $nilaiKontrakAktif = \App\Models\KontrakPengadaan::where('status_kontrak', 'AKTIF')
                                ->sum('nilai_total_kontrak');
                                
            // KPI 3: Tagihan Menunggu (Pending PPK/Bendahara)
            $tagihanMenunggu = \App\Models\Tagihan::whereIn('status', ['PENDING_REVIEW', 'PENDING_BENDAHARA'])->count();
            
            // KPI 4: Butuh Perhatian (Ditolak / Revisi)
            $tagihanRevisi = \App\Models\Tagihan::whereIn('status', ['DITOLAK_PPK', 'REVISI_BENDAHARA', 'REVISI'])->count();

            // CHART KIRI: Serapan Anggaran Kontrak (Pagu DIPA vs Kontrak)
            $totalPaguDipa = \App\Models\RiwayatRevisiDipa::where('is_active', true)->sum('total_pagu');
            $chartSerapan = [
                'Terpakai (Nilai Kontrak)' => \App\Models\KontrakPengadaan::where('status_kontrak', '!=', 'DIBATALKAN')->sum('nilai_total_kontrak'),
                'Sisa Pagu Khusus' => max(0, $totalPaguDipa - \App\Models\KontrakPengadaan::where('status_kontrak', '!=', 'DIBATALKAN')->sum('nilai_total_kontrak'))
            ];

            // CHART KANAN: Distribusi Status Tagihan
            $chartStatusTagihan = \App\Models\Tagihan::where('tipe_tagihan', 'KONTRAK')
                ->select('status', \Illuminate\Support\Facades\DB::raw('count(*) as total'))
                ->groupBy('status')
                ->pluck('total', 'status')
                ->toArray();

            // TABEL KIRI: Peringatan Jatuh Tempo (H-14)
            $jatuhTempo = \App\Models\KontrakPengadaan::where('status_kontrak', 'AKTIF')
                        ->where('tanggal_selesai', '<=', $now->copy()->addDays(14))
                        ->orderBy('tanggal_selesai', 'asc')
                        ->take(5)
                        ->get();

            // TABEL KANAN: Tagihan Dikembalikan / Revisi
            $tagihanBermasalah = \App\Models\Tagihan::whereIn('status', ['DITOLAK_PPK', 'REVISI_BENDAHARA', 'REVISI'])
                                ->with(['logs' => function($q) {
                                    $q->latest()->limit(1); // Ambil log penolakan terakhir
                                }])
                                ->latest()
                                ->take(5)
                                ->get();

            return [
                'kpi' => [
                    'kontrak_aktif' => $kontrakAktif,
                    'selesai_bulan_ini' => $selesaiBulanIni,
                    'nilai_kontrak_aktif' => $nilaiKontrakAktif,
                    'tagihan_menunggu' => $tagihanMenunggu,
                    'tagihan_revisi' => $tagihanRevisi,
                ],
                'chart_serapan_labels' => array_keys($chartSerapan),
                'chart_serapan_data' => array_values($chartSerapan),
                'chart_status_labels' => array_keys($chartStatusTagihan),
                'chart_status_data' => array_values($chartStatusTagihan),
                'table_jatuh_tempo' => $jatuhTempo,
                'table_tagihan_revisi' => $tagihanBermasalah,
            ];
        });

        return view('dashboard.pejabat_pengadaan', $data);
    }

    /**
     * Dashboard khusus Role PPK
     */
    private function ppk()
    {
        $data = \Illuminate\Support\Facades\Cache::remember('dash_ppk_' . auth()->id(), 60, function() {
            $now = now();
            
            // KPI 1: SPK Baru
            $kpi_kontrak_baru = \App\Models\KontrakPengadaan::where('status_kontrak', 'PENDING_REVIEW')->count();
            
            // KPI 2: Tagihan BAST
            $kpi_tagihan_bast = \App\Models\Tagihan::where('status', 'PENDING_REVIEW')->count();
            
            // KPI 3: Pencairan
            $spp = DB::table('dokumen_spp')->where('status', 'DRAFT')->count();
            $npi = DB::table('dokumen_npi')->where('status', 'DRAFT')->count();
            $sp2d = DB::table('dokumen_sp2d')->where('status', 'DRAFT')->count();
            $kpi_pencairan = $spp + $npi + $sp2d;

            // KPI 4: Sisa Pagu DIPA
            $totalPaguDipa = DB::table('dipa_revisions')->where('is_active', true)->sum('total_pagu');
            $totalRealisasi = DB::table('realisasi_anggaran')->sum('nominal_cair');
            $sisaPagu = max(0, $totalPaguDipa - $totalRealisasi);

            // Alert Kritis Belanja Modal 53
            $pagu53 = DB::table('dipa_revision_items')
                ->join('master_coas', 'dipa_revision_items.coa_id', '=', 'master_coas.id')
                ->where('master_coas.kode_mak_lengkap', 'like', '%53%')
                ->sum('dipa_revision_items.nilai_pagu');
            $realisasi53 = DB::table('realisasi_anggaran')
                ->join('dipa_revision_items', 'realisasi_anggaran.dipa_revision_item_id', '=', 'dipa_revision_items.id')
                ->join('master_coas', 'dipa_revision_items.coa_id', '=', 'master_coas.id')
                ->where('master_coas.kode_mak_lengkap', 'like', '%53%')
                ->sum('realisasi_anggaran.nominal_cair');
                
            $alertModal = null;
            if ($pagu53 > 0) {
                $sisa53 = $pagu53 - $realisasi53;
                $persen53 = ($sisa53 / $pagu53) * 100;
                if ($persen53 < 10) {
                    $alertModal = "Peringatan: Pagu DIPA untuk Belanja Modal (MAK 53) tersisa kurang dari 10% (Sisa " . number_format($persen53, 1) . "%)!";
                }
            }

            // Charts
            // Doughnut: Serapan DIPA
            $chartSerapan = [
                'Terserap (SP2D)' => $totalRealisasi,
                'Sisa Pagu' => $sisaPagu
            ];

            // Bar: Serapan 51, 52, 53
            $pagu51 = DB::table('dipa_revision_items')->join('master_coas', 'dipa_revision_items.coa_id', '=', 'master_coas.id')->where('master_coas.kode_mak_lengkap', 'like', '%51%')->sum('dipa_revision_items.nilai_pagu');
            $pagu52 = DB::table('dipa_revision_items')->join('master_coas', 'dipa_revision_items.coa_id', '=', 'master_coas.id')->where('master_coas.kode_mak_lengkap', 'like', '%52%')->sum('dipa_revision_items.nilai_pagu');
            
            $realisasi51 = DB::table('realisasi_anggaran')->join('dipa_revision_items', 'realisasi_anggaran.dipa_revision_item_id', '=', 'dipa_revision_items.id')->join('master_coas', 'dipa_revision_items.coa_id', '=', 'master_coas.id')->where('master_coas.kode_mak_lengkap', 'like', '%51%')->sum('realisasi_anggaran.nominal_cair');
            $realisasi52 = DB::table('realisasi_anggaran')->join('dipa_revision_items', 'realisasi_anggaran.dipa_revision_item_id', '=', 'dipa_revision_items.id')->join('master_coas', 'dipa_revision_items.coa_id', '=', 'master_coas.id')->where('master_coas.kode_mak_lengkap', 'like', '%52%')->sum('realisasi_anggaran.nominal_cair');

            $chartBarLabels = ['Belanja Pegawai (51)', 'Belanja Barang (52)', 'Belanja Modal (53)'];
            $chartBarPagu = [$pagu51, $pagu52, $pagu53];
            $chartBarRealisasi = [$realisasi51, $realisasi52, $realisasi53];

            // Tabs Data
            // Kontrak Baru
            $tabKontrak = \App\Models\KontrakPengadaan::where('status_kontrak', 'PENDING_REVIEW')->latest()->get();
            // Tagihan
            $tabTagihan = \App\Models\Tagihan::where('status', 'PENDING_REVIEW')->latest()->get();
            
            // Pencairan (Union of SPP, NPI, SP2D)
            $pembuatJoin = function ($query) {
                $query
                    ->leftJoin('master_pegawai', function ($join) {
                        $join->on('master_pegawai.id', '=', 'users.profilable_id')
                            ->where('users.profilable_type', '=', \App\Models\MasterPegawai::class);
                    })
                    ->leftJoin('master_pihak', function ($join) {
                        $join->on('master_pihak.id', '=', 'users.profilable_id')
                            ->whereIn('users.profilable_type', [\App\Models\MasterPihak::class, \App\Models\MasterMitraVendor::class]);
                    });
            };
            $pembuatExpr = DB::raw('COALESCE(master_pegawai.nama_lengkap, master_pihak.nama_pihak, users.email) as pembuat');

            $listSpp = DB::table('dokumen_spp')
                ->join('users', 'dokumen_spp.dibuat_oleh_id', '=', 'users.id')
                ->tap($pembuatJoin)
                ->select('dokumen_spp.id', 'dokumen_spp.nomor_spp as nomor', 'dokumen_spp.nominal_spp as nilai', $pembuatExpr)
                ->where('dokumen_spp.status', 'DRAFT')
                ->get()->map(function($i) { $i->jenis = 'SPP'; $i->prioritas = 'Sedang'; $i->url_reject = route('verifikasi-spp.kontrak.revisi', $i->id); $i->url_approve = route('verifikasi-spp.kontrak.approve', $i->id); return $i; });
                
            $listNpi = DB::table('dokumen_npi')
                ->join('users', 'dokumen_npi.bendahara_penerimaan_id', '=', 'users.id')
                ->tap($pembuatJoin)
                ->join('dokumen_spm', 'dokumen_npi.spm_id', '=', 'dokumen_spm.id')
                ->join('dokumen_spp', 'dokumen_spm.spp_id', '=', 'dokumen_spp.id')
                ->select('dokumen_npi.id', 'dokumen_npi.nomor_npi as nomor', 'dokumen_spp.nominal_spp as nilai', $pembuatExpr)
                ->where('dokumen_npi.status', 'DRAFT')
                ->get()->map(function($i) { $i->jenis = 'NPI'; $i->prioritas = 'Sedang'; $i->url_reject = route('verifikasi-ppk.npi.revisi', $i->id); $i->url_approve = route('verifikasi-ppk.npi.approve', $i->id);  return $i; });
                
            $listSp2d = DB::table('dokumen_sp2d')
                ->join('users', 'dokumen_sp2d.bendahara_pengeluaran_id', '=', 'users.id')
                ->tap($pembuatJoin)
                ->join('dokumen_npi', 'dokumen_sp2d.npi_id', '=', 'dokumen_npi.id')
                ->join('dokumen_spm', 'dokumen_npi.spm_id', '=', 'dokumen_spm.id')
                ->join('dokumen_spp', 'dokumen_spm.spp_id', '=', 'dokumen_spp.id')
                ->select('dokumen_sp2d.id', 'dokumen_sp2d.nomor_sp2d as nomor', 'dokumen_spp.nominal_spp as nilai', $pembuatExpr)
                ->where('dokumen_sp2d.status', 'DRAFT')
                ->get()->map(function($i) { $i->jenis = 'SP2D'; $i->prioritas = 'Tinggi'; $i->url_reject = '#'; $i->url_approve = '#'; return $i; });

            $tabPencairan = $listSpp->concat($listNpi)->concat($listSp2d);

            return [
                'alertModal' => $alertModal,
                'kpi' => [
                    'kontrak_baru' => $kpi_kontrak_baru,
                    'tagihan_bast' => $kpi_tagihan_bast,
                    'pencairan' => $kpi_pencairan,
                    'sisa_pagu' => $sisaPagu,
                    'total_pagu' => $totalPaguDipa,
                ],
                'chart_serapan_labels' => array_keys($chartSerapan),
                'chart_serapan_data' => array_values($chartSerapan),
                'chart_bar_labels' => $chartBarLabels,
                'chart_bar_pagu' => $chartBarPagu,
                'chart_bar_realisasi' => $chartBarRealisasi,
                'tab_kontrak' => $tabKontrak,
                'tab_tagihan' => $tabTagihan,
                'tab_pencairan' => $tabPencairan,
            ];
        });

        return view('dashboard.ppk', $data);
    }

    /**
     * Dashboard khusus Role PPABP (Petugas Pengelola Administrasi Belanja Pegawai)
     * Fokus: pengelolaan tagihan honorarium — draft, perlu revisi, dalam verifikasi, disetujui.
     */
    private function ppabp()
    {
        $userId = Auth::id();

        $data = \Illuminate\Support\Facades\Cache::remember('dash_ppabp_' . $userId, 60, function () use ($userId) {
            $now = now();
            $tahun = $now->year;
            $bulan = $now->month;

            // ============================================================
            // STATUS BUCKETING
            // ============================================================
            $draftStatuses = ['DRAFT'];
            $revisiStatuses = [
                'DITOLAK_PPK', 'DITOLAK_PPSPM', 'DITOLAK_KOORDINATOR_KEUANGAN',
                'DITOLAK_BENDAHARA_PENGELUARAN', 'DITOLAK_BENDAHARA_PENERIMAAN', 'DITOLAK_KASUBBAG',
                'REVISI_PPK', 'REVISI_PPSPM', 'REVISI_KOORDINATOR_KEUANGAN',
                'REVISI_BENDAHARA_PENGELUARAN', 'REVISI_BENDAHARA_PENERIMAAN', 'REVISI_KASUBBAG',
            ];
            $verifikasiStatuses = [
                'PENDING_VERIFIKASI_HONORARIUM', 'PENDING_KASUBBAG', 'PENDING_PPK', 'PENDING_PPSPM',
                'PENDING_KOORDINATOR_KEUANGAN', 'PENDING_BENDAHARA_PENGELUARAN', 'PENDING_BENDAHARA_PENERIMAAN',
            ];
            $disetujuiStatuses = ['DISETUJUI', 'PROSES_SPP', 'SPP_TERBIT', 'SEBAGIAN_SPP_TERBIT', 'SPP_LENGKAP', 'SELESAI'];

            $baseQuery = fn () => Tagihan::where('tipe_tagihan', 'HONORARIUM');

            // ============================================================
            // KPI CARDS
            // ============================================================
            $kpiDraft = (clone $baseQuery())->whereIn('status', $draftStatuses)->count();
            $kpiRevisi = (clone $baseQuery())->whereIn('status', $revisiStatuses)->count();
            $kpiVerifikasi = (clone $baseQuery())->whereIn('status', $verifikasiStatuses)->count();
            $kpiDisetujui = (clone $baseQuery())->whereIn('status', $disetujuiStatuses)->count();

            $totalNominalDraft = (clone $baseQuery())->whereIn('status', $draftStatuses)->sum('total_bruto');
            $totalNominalVerifikasi = (clone $baseQuery())->whereIn('status', $verifikasiStatuses)->sum('total_bruto');

            // Total bulan ini (dibuat bulan ini)
            $tagihanBulanIni = (clone $baseQuery())
                ->whereYear('created_at', $tahun)
                ->whereMonth('created_at', $bulan)
                ->count();
            $nominalBulanIni = (clone $baseQuery())
                ->whereYear('created_at', $tahun)
                ->whereMonth('created_at', $bulan)
                ->sum('total_bruto');

            // Total Personel Penerima Honor (unique nrp_nip yang dibayar tahun ini)
            $totalPenerimaTahunIni = DB::table('detail_honorarium')
                ->join('tagihan', 'detail_honorarium.tagihan_id', '=', 'tagihan.id')
                ->where('tagihan.tipe_tagihan', 'HONORARIUM')
                ->whereYear('tagihan.created_at', $tahun)
                ->whereNull('tagihan.deleted_at')
                ->whereNull('detail_honorarium.deleted_at')
                ->distinct('detail_honorarium.nrp_nip')
                ->count('detail_honorarium.nrp_nip');

            // ============================================================
            // CHART: Tren Tagihan Honor 6 Bulan Terakhir
            // ============================================================
            $trenLabels = [];
            $trenJumlah = [];
            $trenNominal = [];
            for ($i = 5; $i >= 0; $i--) {
                $month = $now->copy()->subMonths($i);
                $trenLabels[] = $month->isoFormat('MMM YY');
                $trenJumlah[] = (clone $baseQuery())
                    ->whereYear('created_at', $month->year)
                    ->whereMonth('created_at', $month->month)
                    ->count();
                $trenNominal[] = (float) (clone $baseQuery())
                    ->whereYear('created_at', $month->year)
                    ->whereMonth('created_at', $month->month)
                    ->sum('total_bruto');
            }

            // ============================================================
            // CHART: Distribusi Status Tagihan Honor (Doughnut)
            // ============================================================
            $statusDistribusi = [
                'Draft' => $kpiDraft,
                'Perlu Revisi' => $kpiRevisi,
                'Dalam Verifikasi' => $kpiVerifikasi,
                'Disetujui / Selesai' => $kpiDisetujui,
            ];

            // ============================================================
            // TABEL: Tagihan Perlu Tindakan (Draft + Revisi)
            // ============================================================
            $needsActionStatuses = array_merge($draftStatuses, $revisiStatuses);
            $perluTindakan = (clone $baseQuery())
                ->whereIn('status', $needsActionStatuses)
                ->with(['detailHonorarium', 'logs' => fn ($q) => $q->latest()->limit(1)])
                ->latest('updated_at')
                ->take(8)
                ->get();

            // ============================================================
            // TABEL: Tagihan Dalam Verifikasi (sedang diproses verifikator)
            // ============================================================
            $dalamVerifikasi = (clone $baseQuery())
                ->whereIn('status', $verifikasiStatuses)
                ->with(['detailHonorarium'])
                ->latest('updated_at')
                ->take(5)
                ->get();

            // ============================================================
            // TABEL: Honorarium Selesai Terbaru
            // ============================================================
            $selesaiTerbaru = (clone $baseQuery())
                ->whereIn('status', $disetujuiStatuses)
                ->latest('updated_at')
                ->take(5)
                ->get();

            return [
                'kpi' => [
                    'draft' => $kpiDraft,
                    'revisi' => $kpiRevisi,
                    'verifikasi' => $kpiVerifikasi,
                    'disetujui' => $kpiDisetujui,
                    'nominal_draft' => $totalNominalDraft,
                    'nominal_verifikasi' => $totalNominalVerifikasi,
                    'tagihan_bulan_ini' => $tagihanBulanIni,
                    'nominal_bulan_ini' => $nominalBulanIni,
                    'total_penerima_tahun_ini' => $totalPenerimaTahunIni,
                ],
                'tren_labels' => $trenLabels,
                'tren_jumlah' => $trenJumlah,
                'tren_nominal' => $trenNominal,
                'status_labels' => array_keys($statusDistribusi),
                'status_data' => array_values($statusDistribusi),
                'perlu_tindakan' => $perluTindakan,
                'dalam_verifikasi' => $dalamVerifikasi,
                'selesai_terbaru' => $selesaiTerbaru,
                'tahun' => $tahun,
            ];
        });

        return view('dashboard.ppabp', $data);
    }

    /**
     * Dashboard khusus Role Operator Perjaldin
     */
    private function operatorPerjaldin()
    {
        $userId = Auth::id();

        $data = \Illuminate\Support\Facades\Cache::remember('dash_operator_perjaldin_' . $userId, 60, function () use ($userId) {
            $now = now();
            $tahun = $now->year;
            $bulan = $now->month;

            // ============================================================
            // STATUS BUCKETING
            // ============================================================
            $draftStatuses = ['DRAFT'];
            $revisiStatuses = [
                'DITOLAK_PPK', 'DITOLAK_PPSPM', 'DITOLAK_KOORDINATOR_KEUANGAN',
                'DITOLAK_BENDAHARA_PENGELUARAN', 'DITOLAK_BENDAHARA_PENERIMAAN', 'DITOLAK_KASUBBAG',
                'REVISI_PPK', 'REVISI_PPSPM', 'REVISI_KOORDINATOR_KEUANGAN',
                'REVISI_BENDAHARA_PENGELUARAN', 'REVISI_BENDAHARA_PENERIMAAN', 'REVISI_KASUBBAG',
            ];
            $verifikasiStatuses = [
                'PENDING_VERIFIKASI_PERJALDIN', 'PENDING_KASUBBAG', 'PENDING_PPK', 'PENDING_PPSPM',
                'PENDING_KOORDINATOR_KEUANGAN', 'PENDING_BENDAHARA_PENGELUARAN', 'PENDING_BENDAHARA_PENERIMAAN',
            ];
            $menungguTtdStatuses = ['MENUNGGU_UPLOAD_NOMINATIF_TTD'];
            $selesaiStatuses = ['DISETUJUI_PERJALDIN', 'PROSES_SPP', 'SPP_TERBIT', 'SEBAGIAN_SPP_TERBIT', 'SPP_LENGKAP', 'SELESAI', 'CAIR', 'SP2D'];

            $baseQuery = fn () => Tagihan::where('tipe_tagihan', 'PERJALDIN');

            // ============================================================
            // KPI CARDS
            // ============================================================
            $kpiDraft = (clone $baseQuery())->whereIn('status', $draftStatuses)->count();
            $kpiRevisi = (clone $baseQuery())->whereIn('status', $revisiStatuses)->count();
            $kpiVerifikasi = (clone $baseQuery())->whereIn('status', $verifikasiStatuses)->count();
            $kpiMenungguTtd = (clone $baseQuery())->whereIn('status', $menungguTtdStatuses)->count();
            $kpiSelesai = (clone $baseQuery())->whereIn('status', $selesaiStatuses)->count();

            $totalNominalDraft = (clone $baseQuery())->whereIn('status', $draftStatuses)->sum('total_bruto');
            $totalNominalVerifikasi = (clone $baseQuery())->whereIn('status', $verifikasiStatuses)->sum('total_bruto');
            $totalNominalSelesai = (clone $baseQuery())->whereIn('status', $selesaiStatuses)->sum('total_bruto');

            // Total bulan ini (dibuat bulan ini)
            $tagihanBulanIni = (clone $baseQuery())
                ->whereYear('created_at', $tahun)
                ->whereMonth('created_at', $bulan)
                ->count();
            $nominalBulanIni = (clone $baseQuery())
                ->whereYear('created_at', $tahun)
                ->whereMonth('created_at', $bulan)
                ->sum('total_bruto');

            // ============================================================
            // CHART: Tren Tagihan Perjaldin 6 Bulan Terakhir
            // ============================================================
            $trenLabels = [];
            $trenJumlah = [];
            $trenNominal = [];
            for ($i = 5; $i >= 0; $i--) {
                $month = $now->copy()->subMonths($i);
                $trenLabels[] = $month->isoFormat('MMM YY');
                $trenJumlah[] = (clone $baseQuery())
                    ->whereYear('created_at', $month->year)
                    ->whereMonth('created_at', $month->month)
                    ->count();
                $trenNominal[] = (float) (clone $baseQuery())
                    ->whereYear('created_at', $month->year)
                    ->whereMonth('created_at', $month->month)
                    ->sum('total_bruto');
            }

            // ============================================================
            // CHART: Distribusi Biaya Komponen
            // ============================================================
            $components = DB::table('detail_perjaldin')
                ->join('tagihan', 'detail_perjaldin.tagihan_id', '=', 'tagihan.id')
                ->where('tagihan.tipe_tagihan', 'PERJALDIN')
                ->whereNull('tagihan.deleted_at')
                ->select([
                    DB::raw('SUM(detail_perjaldin.biaya_tiket) as tiket'),
                    DB::raw('SUM(detail_perjaldin.biaya_transport) as transport'),
                    DB::raw('SUM(detail_perjaldin.biaya_penginapan) as penginapan'),
                    DB::raw('SUM(detail_perjaldin.uang_harian + detail_perjaldin.uang_representasi + detail_perjaldin.uang_rapat) as uang_harian_representasi_rapat')
                ])
                ->first();

            $komponenData = [
                'Tiket Pesawat' => (float) ($components->tiket ?? 0),
                'Transportasi' => (float) ($components->transport ?? 0),
                'Penginapan' => (float) ($components->penginapan ?? 0),
                'Uang Harian & Rapat' => (float) ($components->uang_harian_representasi_rapat ?? 0),
            ];

            // ============================================================
            // TABEL DATA
            // ============================================================
            // 1. Tagihan Butuh Tindakan (Draft & Revisi)
            $needsActionStatuses = array_merge($draftStatuses, $revisiStatuses);
            $perluTindakan = (clone $baseQuery())
                ->whereIn('status', $needsActionStatuses)
                ->with(['logs' => fn ($q) => $q->latest()])
                ->latest('updated_at')
                ->take(10)
                ->get();

            // 2. Tagihan Menunggu Upload TTD
            $menungguTtd = (clone $baseQuery())
                ->whereIn('status', $menungguTtdStatuses)
                ->with(['arsipDokumen'])
                ->latest('updated_at')
                ->take(5)
                ->get();

            // 3. Tagihan Dalam Verifikasi (dengan approvals)
            $dalamVerifikasi = (clone $baseQuery())
                ->whereIn('status', $verifikasiStatuses)
                ->with(['workflowInstances.approvals.actedByUser', 'logs'])
                ->latest('updated_at')
                ->take(10)
                ->get();

            // 4. Tagihan Selesai Terbaru
            $selesaiTerbaru = (clone $baseQuery())
                ->whereIn('status', $selesaiStatuses)
                ->latest('updated_at')
                ->take(5)
                ->get();

            return [
                'kpi' => [
                    'draft' => $kpiDraft,
                    'revisi' => $kpiRevisi,
                    'verifikasi' => $kpiVerifikasi,
                    'menunggu_ttd' => $kpiMenungguTtd,
                    'selesai' => $kpiSelesai,
                    'nominal_draft' => $totalNominalDraft,
                    'nominal_verifikasi' => $totalNominalVerifikasi,
                    'nominal_selesai' => $totalNominalSelesai,
                    'tagihan_bulan_ini' => $tagihanBulanIni,
                    'nominal_bulan_ini' => $nominalBulanIni,
                ],
                'tren_labels' => $trenLabels,
                'tren_jumlah' => $trenJumlah,
                'tren_nominal' => $trenNominal,
                'komponen_labels' => array_keys($komponenData),
                'komponen_data' => array_values($komponenData),
                'perlu_tindakan' => $perluTindakan,
                'menunggu_ttd' => $menungguTtd,
                'dalam_verifikasi' => $dalamVerifikasi,
                'selesai_terbaru' => $selesaiTerbaru,
                'tahun' => $tahun,
            ];
        });

        return view('dashboard.operator_perjaldin', $data);
    }
}

