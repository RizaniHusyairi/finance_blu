<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DokumenNpi;
use App\Models\DokumenSp2d;
use App\Models\LogStatusDokumen;
use App\Models\WorkflowInstance;
use App\Models\User;
use App\Notifications\WorkflowNotification;
use App\Services\WorkflowService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class Sp2dPerjaldinController extends Controller
{
    protected WorkflowService $workflowService;

    public function __construct(WorkflowService $workflowService)
    {
        $this->workflowService = $workflowService;
    }

    /**
     * Halaman Daftar SP2D Perjaldin (Pencatatan by Bendahara Pengeluaran)
     */
    public function index(Request $request)
    {
        // 1. Tampilkan hanya Dokumen NPI dari SPM yang memiliki komponen perjaldin
        // Dan NPI tersebut HARUS sudah DISETUJUI FINAL atau status final legacy-nya.
        $query = DokumenNpi::with([
            'spm.spp.tagihan.detailPerjaldin.pegawai',
            'spm.spp.tagihan.detailPerjaldin.provinsi',
            'spm.spp.tagihan.komponenPerjaldin',
            'bendaharaPenerimaan',
            'sp2d',
            'sp2d.workflowInstances.approvals',
        ])
        ->whereHas('spm.spp', function($q) {
            $q->whereNotNull('tagihan_perjaldin_komponen_id');
        })
        ->whereIn('status', [
            DokumenNpi::STATUS_DISETUJUI_FINAL,
            DokumenNpi::STATUS_APPROVED_KASUBAG, // fallback legacy
            DokumenNpi::STATUS_NPI_TERBIT
        ]);

        $allNpis = $query->latest()->get();

        $processed = collect();
        foreach ($allNpis as $npi) {
            $sp2d = $npi->sp2d;
            $spm = $npi->spm;
            $spp = $spm?->spp;
            $tagihan = $spp?->tagihan;
            
            $npi->spmModel = $spm;
            $npi->sppModel = $spp;
            $npi->tagihanModel = $tagihan;
            $npi->nilai_npi = $spm?->nominal_spm ?? 0;
            
            if (!$sp2d) {
                $npi->status_sp2d = 'SIAP_DIBUAT';
            } else {
                $npi->status_sp2d = $sp2d->status;
            }

            $processed->push($npi);
        }

        // Filter status
        $statusFilter = $request->input('status', 'semua');
        $viewNpis = $processed;
        
        if ($statusFilter !== 'semua') {
            $viewNpis = match ($statusFilter) {
                'siap_dibuat' => $viewNpis->where('status_sp2d', 'SIAP_DIBUAT'),
                'draft'       => $viewNpis->where('status_sp2d', DokumenSp2d::STATUS_DRAFT),
                'revisi'      => $viewNpis->where('status_sp2d', DokumenSp2d::STATUS_REVISI),
                'menunggu'    => $viewNpis->where('status_sp2d', DokumenSp2d::STATUS_MENUNGGU_VERIFIKASI),
                'selesai'     => $viewNpis->whereIn('status_sp2d', [DokumenSp2d::STATUS_DISETUJUI_FINAL, DokumenSp2d::STATUS_EXECUTED]),
                default       => $viewNpis,
            };
        }

        // Search
        $search = $request->input('search');
        if ($search) {
            $viewNpis = $viewNpis->filter(function($item) use ($search) {
                $searchStr = strtolower($search);
                return str_contains(strtolower($item->nomor_npi), $searchStr) || 
                       str_contains(strtolower($item->sp2d?->nomor_sp2d ?? ''), $searchStr) ||
                       str_contains(strtolower($item->spmModel?->nomor_spm ?? ''), $searchStr) ||
                       str_contains(strtolower($item->sppModel?->nomor_spp ?? ''), $searchStr) ||
                       str_contains(strtolower($item->tagihanModel?->nomor_tagihan ?? ''), $searchStr) ||
                       str_contains(strtolower($item->tagihanModel?->deskripsi ?? ''), $searchStr);
            });
        }

        $summary = [
            'siap_dibuat' => $processed->where('status_sp2d', 'SIAP_DIBUAT')->count(),
            'draft'       => $processed->where('status_sp2d', DokumenSp2d::STATUS_DRAFT)->count(),
            'revisi'      => $processed->where('status_sp2d', DokumenSp2d::STATUS_REVISI)->count(),
            'menunggu'    => $processed->where('status_sp2d', DokumenSp2d::STATUS_MENUNGGU_VERIFIKASI)->count(),
            'selesai'     => $processed->whereIn('status_sp2d', [DokumenSp2d::STATUS_DISETUJUI_FINAL, DokumenSp2d::STATUS_EXECUTED])->count(),
        ];

        return view('sp2ds.perjaldin.index', compact('viewNpis', 'summary', 'statusFilter', 'search'));
    }

    /**
     * Halaman Detail Workspace SP2D Perjaldin
     */
    public function detail($npiId)
    {
        $npi = DokumenNpi::with([
            'spm.spp.tagihan.detailPerjaldin.pegawai',
            'spm.spp.tagihan.detailPerjaldin.provinsi',
            'spm.spp.tagihan.komponenPerjaldin',
            'spm.spp.tagihan.bkuPengeluaran.sumberRekening',
            'spm.spp.tagihanPerjaldinKomponen.dipaRevisionItem.coa',
            'bendaharaPenerimaan',
            'sp2d.logs.user',
            'sp2d.workflowInstances.approvals.actedByUser',
            'sp2d.workflowInstances.approvals.assignedUser'
        ])
        ->whereHas('spm.spp', function($q) {
            $q->whereNotNull('tagihan_perjaldin_komponen_id');
        })
        ->whereIn('status', [DokumenNpi::STATUS_DISETUJUI_FINAL, DokumenNpi::STATUS_APPROVED_KASUBAG, DokumenNpi::STATUS_NPI_TERBIT])
        ->findOrFail($npiId);

        $spm = $npi->spm;
        $spp = $spm?->spp;
        $tagihan = $spp?->tagihan;
        $komponen = $spp?->tagihanPerjaldinKomponen;
        $sp2d = $npi->sp2d;

        // Default Values if SP2D is missing
        $defaultNilai = $spm?->nominal_spm ?? 0;
        $defaultTahun = $spp?->tahun_anggaran ?? date('Y');
        
        // Cek Checklist
        $checks = [
            'npi_final'       => in_array($npi->status, [DokumenNpi::STATUS_DISETUJUI_FINAL, DokumenNpi::STATUS_APPROVED_KASUBAG]),
            'spp_tersedia'    => !is_null($spp),
            'spm_tersedia'    => !is_null($spm),
            'tagihan_ada'     => !is_null($tagihan),
            'peserta_ada'     => $tagihan && $tagihan->detailPerjaldin->count() > 0,
            'sp2d_tersimpan'  => !is_null($sp2d),
            'sp2d_valid'      => $sp2d && !empty($sp2d->nomor_sp2d) && !empty($sp2d->tanggal_sp2d)
        ];
        
        $isLengkap = !in_array(false, array_values($checks), true);
        
        $wf = $sp2d ? $sp2d->workflowInstances->sortByDesc('created_at')->first() : null;
        $approvals = collect($wf ? $wf->approvals : []);

        $ppkApproval = $approvals->firstWhere('role_code', 'PPK');
        $kasubbagApproval = $approvals->firstWhere('role_code', 'Kepala Subbagian Keuangan dan Tata Usaha');
        $koordinatorApproval = $approvals->firstWhere('role_code', 'Koordinator Keuangan');
        $ppspmApproval = $approvals->firstWhere('role_code', 'PPSPM');

        $autoNomorSp2d = \App\Services\DocumentNumberingService::generateDerivedNumber($spp->nomor_spp ?? '', 'SP2D');

        return view('sp2ds.perjaldin.detail', compact(
            'npi', 'spm', 'spp', 'tagihan', 'komponen', 'sp2d',
            'defaultNilai', 'defaultTahun', 'checks', 'isLengkap', 'wf', 'autoNomorSp2d', 'ppkApproval', 'kasubbagApproval', 'koordinatorApproval', 'ppspmApproval'
        ));
    }

    /**
     * Simpan Draft / Ubah SP2D
     */
    public function store(Request $request, $npiId)
    {
        $request->validate([
            'nomor_sp2d'     => 'required|string|max:100',
            'tanggal_sp2d'   => 'required|date',
            'catatan'        => 'nullable|string',
        ]);

        $npi = DokumenNpi::whereHas('spm.spp', function($q) {
            $q->whereNotNull('tagihan_perjaldin_komponen_id');
        })
        ->whereIn('status', [DokumenNpi::STATUS_DISETUJUI_FINAL, DokumenNpi::STATUS_APPROVED_KASUBAG, DokumenNpi::STATUS_NPI_TERBIT])
        ->findOrFail($npiId);

        $sp2d = $npi->sp2d;

        if ($sp2d && !in_array($sp2d->status, [DokumenSp2d::STATUS_DRAFT, DokumenSp2d::STATUS_REVISI])) {
            return back()->with('error', 'SP2D sudah diajukan atau selesai. Anda tidak dapat mengubah data ini lagi.');
        }

        DB::beginTransaction();
        try {
            if (!$sp2d) {
                // Create
                $sp2d = DokumenSp2d::create([
                    'npi_id'                   => $npi->id,
                    'bendahara_pengeluaran_id' => Auth::id(),
                    'nomor_sp2d'               => $request->nomor_sp2d,
                    'tanggal_sp2d'             => $request->tanggal_sp2d,
                    'status'                   => DokumenSp2d::STATUS_DRAFT,
                ]);

                LogStatusDokumen::create([
                    'dokumen_type'      => DokumenSp2d::class,
                    'dokumen_id'        => $sp2d->id,
                    'user_id'           => Auth::id(),
                    'role_saat_itu'     => 'Bendahara Pengeluaran',
                    'status_baru'       => DokumenSp2d::STATUS_DRAFT,
                    'aksi'              => 'CREATE_DRAFT',
                    'catatan'           => 'Bendahara Pengeluaran membuat draft SP2D Perjaldin',
                    'ip_address'        => $request->ip(),
                ]);
            } else {
                // Update
                $sp2d->update([
                    'nomor_sp2d'     => $request->nomor_sp2d,
                    'tanggal_sp2d'   => $request->tanggal_sp2d,
                ]);

                LogStatusDokumen::create([
                    'dokumen_type'      => DokumenSp2d::class,
                    'dokumen_id'        => $sp2d->id,
                    'user_id'           => Auth::id(),
                    'role_saat_itu'     => 'Bendahara Pengeluaran',
                    'status_sebelumnya' => $sp2d->status,
                    'status_baru'       => $sp2d->status, // unchanged (either Draft or Revisi)
                    'aksi'              => 'UPDATE_SP2D',
                    'catatan'           => $request->catatan ?: 'Pembaruan data referensi pada SP2D Perjaldin',
                    'ip_address'        => $request->ip(),
                ]);
            }

            DB::commit();
            return back()->with('success', 'Data draft SP2D Perjaldin berhasil disimpan.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menyimpan draft SP2D: ' . $e->getMessage());
        }
    }

    /**
     * Submit / Ajukan SP2D
     */
    public function submit(Request $request, $npiId)
    {
        $npi = DokumenNpi::with('sp2d')->findOrFail($npiId);
        $sp2d = $npi->sp2d;

        if (!$sp2d) {
            return back()->with('error', 'Simpan draft SP2D terlebih dahulu sebelum mengajukan!');
        }

        if (!in_array($sp2d->status, [DokumenSp2d::STATUS_DRAFT, DokumenSp2d::STATUS_REVISI])) {
            return back()->with('error', 'SP2D tidak dalam status draft/revisi sehingga tidak dapat diajukan ulang.');
        }

        // Pastikan mandatory fields are filled
        if (empty($sp2d->nomor_sp2d) || empty($sp2d->tanggal_sp2d)) {
            return back()->with('error', 'Pastikan Nomor dan Tanggal SP2D telah diisi dengan benar.');
        }

        DB::beginTransaction();
        try {
            $statusSebelum = $sp2d->status;
            
            // Generate/Start Workflow
            $this->workflowService->startWorkflow('SP2D_PERJALDIN', $sp2d);
            
            $sp2d->update(['status' => DokumenSp2d::STATUS_MENUNGGU_VERIFIKASI]);

            LogStatusDokumen::create([
                'dokumen_type'      => DokumenSp2d::class,
                'dokumen_id'        => $sp2d->id,
                'user_id'           => Auth::id(),
                'role_saat_itu'     => 'Bendahara Pengeluaran',
                'status_sebelumnya' => $statusSebelum,
                'status_baru'       => DokumenSp2d::STATUS_MENUNGGU_VERIFIKASI,
                'aksi'              => 'SUBMIT_SP2D',
                'catatan'           => 'SP2D Perjaldin diajukan. Menunggu verifikasi paralel.',
                'ip_address'        => $request->ip(),
            ]);

            // Notify PPK
            $ppks = User::role('PPK')->get();
            Notification::send($ppks, new WorkflowNotification([
                'title'   => 'Antrean Verifikasi SP2D Perjaldin Baru',
                'message' => "SP2D Perjaldin dengan Nomor {$sp2d->nomor_sp2d} telah diajukan dan membutuhkan persetujuan Anda.",
                'url'     => '#', // Nanti dialihkan ke route verifikasi PPK SP2D
                'icon'    => 'inventory',
                'color'   => 'primary',
            ]));

            // Notify Kasubbag
            $kasubbags = User::role('Kepala Subbagian Keuangan dan Tata Usaha')->get();
            Notification::send($kasubbags, new WorkflowNotification([
                'title'   => 'Antrean Verifikasi SP2D Perjaldin Baru',
                'message' => "SP2D Perjaldin dengan Nomor {$sp2d->nomor_sp2d} telah diajukan dan membutuhkan persetujuan Anda.",
                'url'     => '#',
                'icon'    => 'inventory',
                'color'   => 'primary',
            ]));

            // Notify PPSPM
            $ppspms = User::role('PPSPM')->get();
            Notification::send($ppspms, new WorkflowNotification([
                'title'   => 'Antrean Verifikasi SP2D Perjaldin Baru',
                'message' => "SP2D Perjaldin dengan Nomor {$sp2d->nomor_sp2d} telah diajukan dan membutuhkan persetujuan Anda.",
                'url'     => '#',
                'icon'    => 'inventory',
                'color'   => 'primary',
            ]));

            // Notify Koordinator Keuangan
            $koordinators = User::role('Koordinator Keuangan')->get();
            Notification::send($koordinators, new WorkflowNotification([
                'title'   => 'Antrean Verifikasi SP2D Perjaldin Baru',
                'message' => "SP2D Perjaldin dengan Nomor {$sp2d->nomor_sp2d} telah diajukan dan membutuhkan persetujuan Anda.",
                'url'     => route('verifikasi-sp2d.perjaldin.index'),
                'icon'    => 'inventory',
                'color'   => 'primary',
            ]));

            DB::commit();
            return back()->with('success', 'SP2D Perjaldin berhasil diajukan ke Verifikator.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Sistem gagal memulai workflow pengajuan: ' . $e->getMessage());
        }
    }

    /**
     * Cetak SP2D 
     */
    public function cetak(Request $request, $sp2dId)
    {
        $sp2d = DokumenSp2d::findOrFail($sp2dId);
        // Delegate ke DocumentController (reusing function)
        return redirect()->route('sp2ds.cetak-pdf', $sp2d->id);
    }
}
