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

class Sp2dHonorController extends Controller
{
    protected WorkflowService $workflowService;

    public function __construct(WorkflowService $workflowService)
    {
        $this->workflowService = $workflowService;
    }

    /**
     * Halaman Daftar SP2D Honorarium (Bendahara Pengeluaran)
     */
    public function index(Request $request)
    {
        $query = DokumenNpi::with([
            'spm.spp.tagihan.detailHonorarium',
            'bendaharaPenerimaan',
            'sp2d'
        ])
        ->whereHas('spm.spp.tagihan', function($q) {
            $q->where('tipe_tagihan', 'HONORARIUM');
        })
        ->whereIn('status', [
            DokumenNpi::STATUS_DISETUJUI_FINAL,
            DokumenNpi::STATUS_APPROVED_KASUBAG 
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

        // Apply Status Filter
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

        // Apply Search
        $search = $request->input('search');
        if ($search) {
            $viewNpis = $viewNpis->filter(function($item) use ($search) {
                $s = strtolower($search);
                return str_contains(strtolower($item->nomor_npi), $s) || 
                       str_contains(strtolower($item->sp2d?->nomor_sp2d ?? ''), $s) ||
                       str_contains(strtolower($item->spmModel?->nomor_spm ?? ''), $s) ||
                       str_contains(strtolower($item->sppModel?->nomor_spp ?? ''), $s) ||
                       str_contains(strtolower($item->tagihanModel?->nomor_tagihan ?? ''), $s) ||
                       str_contains(strtolower($item->tagihanModel?->deskripsi ?? ''), $s);
            });
        }

        $summary = [
            'siap_dibuat' => $processed->where('status_sp2d', 'SIAP_DIBUAT')->count(),
            'draft'       => $processed->where('status_sp2d', DokumenSp2d::STATUS_DRAFT)->count(),
            'revisi'      => $processed->where('status_sp2d', DokumenSp2d::STATUS_REVISI)->count(),
            'menunggu'    => $processed->where('status_sp2d', DokumenSp2d::STATUS_MENUNGGU_VERIFIKASI)->count(),
            'selesai'     => $processed->whereIn('status_sp2d', [DokumenSp2d::STATUS_DISETUJUI_FINAL, DokumenSp2d::STATUS_EXECUTED])->count(),
        ];

        return view('sp2ds.honor_index', compact('viewNpis', 'summary', 'statusFilter', 'search'));
    }

    /**
     * Halaman Detail Workspace SP2D Honorarium
     */
    public function detail($npiId)
    {
        $npi = DokumenNpi::with([
            'spm.spp.tagihan.detailHonorarium',
            'spm.spp.tagihan.dipaRevisionItem.coa',
            'spm.spp.tagihan.arsipDokumen',
            'bendaharaPenerimaan',
            'sp2d.logs.user',
            'sp2d.workflowInstances.approvals.actedByUser',
            'sp2d.workflowInstances.approvals.assignedUser'
        ])
        ->whereHas('spm.spp.tagihan', function($q) {
            $q->where('tipe_tagihan', 'HONORARIUM');
        })
        ->whereIn('status', [DokumenNpi::STATUS_DISETUJUI_FINAL, DokumenNpi::STATUS_APPROVED_KASUBAG])
        ->findOrFail($npiId);

        $spm = $npi->spm;
        $spp = $spm?->spp;
        $tagihan = $spp?->tagihan;
        $sp2d = $npi->sp2d;

        $defaultNilai = $spm?->nominal_spm ?? $tagihan?->total_netto ?? 0;
        $defaultTahun = $spp?->tahun_anggaran ?? date('Y');
        
        $rekeningBermasalah = count(array_filter($tagihan->detailHonorarium->toArray(), fn($p) => empty($p['rekening']) || empty($p['nama_rekening'])));

        $checks = [
            'npi_final'       => in_array($npi->status, [DokumenNpi::STATUS_DISETUJUI_FINAL, DokumenNpi::STATUS_APPROVED_KASUBAG]),
            'spp_tersedia'    => !is_null($spp),
            'spm_tersedia'    => !is_null($spm),
            'tagihan_ada'     => !is_null($tagihan),
            'peserta_ada'     => $tagihan && collect($tagihan->detailHonorarium)->count() > 0,
            'rekening_valid'  => $rekeningBermasalah === 0,
            'sp2d_tersimpan'  => !is_null($sp2d),
            'sp2d_valid'      => $sp2d && !empty($sp2d->nomor_sp2d) && !empty($sp2d->tanggal_sp2d)
        ];

        $rekeningPenerima = collect($tagihan->detailHonorarium)->map(function ($item) {
            return [
                'nama' => $item->nama_personel,
                'jabatan' => $item->jabatan,
                'bank' => $item->jenis_bank ?? 'BANK',
                'rekening' => $item->rekening ?? 'KOSONG',
                'nama_rekening' => $item->nama_rekening ?? 'KOSONG',
                'netto' => $item->netto ?? 0
            ];
        });

        // Timeline Variables
        $isNpiFinal = true; 
        $sp2dStatus = $sp2d?->status ?? 'BELUM_ADA';
        $progressStep = match ($sp2dStatus) {
            'BELUM_ADA', DokumenSp2d::STATUS_DRAFT, DokumenSp2d::STATUS_REVISI => 1,
            DokumenSp2d::STATUS_MENUNGGU_VERIFIKASI => 2,
            DokumenSp2d::STATUS_DISETUJUI_FINAL, DokumenSp2d::STATUS_EXECUTED => 3,
            default => 1,
        };

        $workflow = null;
        $ppkApproval = null;
        $kasubbagApproval = null;

        if ($sp2d) {
            $workflow = $sp2d->workflowInstances->first();
            $ppkApproval = collect($workflow?->approvals ?? [])->firstWhere('role_code', 'PPK');
            $kasubbagApproval = collect($workflow?->approvals ?? [])->firstWhere('role_code', 'Kepala Subbagian Keuangan dan Tata Usaha');
        }

        if ($progressStep == 3) {
            $isSP2DFinal = true;
        } else {
            $isSP2DFinal = false;
        }

        $autoNomorSp2d = \App\Services\DocumentNumberingService::generateDerivedNumber($spp->nomor_spp ?? '', 'SP2D');

        return view('sp2ds.honor_detail', compact(
            'npi', 'spm', 'spp', 'tagihan', 'sp2d',
            'checks', 'rekeningPenerima',
            'defaultNilai', 'defaultTahun',
            'progressStep', 'isSP2DFinal',
            'workflow', 'ppkApproval', 'kasubbagApproval', 'autoNomorSp2d'
        ));
    }

    /**
     * Simpan Draft SP2D Honorarium
     */
    public function store(Request $request, $npiId)
    {
        $npi = DokumenNpi::with('spm.spp')->findOrFail($npiId);
        
        $request->validate([
            'nomor_sp2d'   => 'required|string|max:100',
            'tanggal_sp2d' => 'required|date',
            'catatan'      => 'nullable|string',
        ]);

        $sp2d = DokumenSp2d::where('npi_id', $npi->id)->first();
        
        DB::beginTransaction();
        try {
            if (!$sp2d) {
                // Buat baru
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
                    'aksi'              => 'CREATE_SP2D',
                    'catatan'           => $request->catatan ?: 'Draft SP2D Honorarium dibuat',
                    'ip_address'        => $request->ip(),
                ]);
            } else {
                // Cek status, jangan edit jika sudah dikunci (menunggu/final)
                if (!in_array($sp2d->status, [DokumenSp2d::STATUS_DRAFT, DokumenSp2d::STATUS_REVISI])) {
                    return back()->with('error', 'Status SP2D sudah diajukan sehingga tidak bisa diubah formatnya!');
                }

                $sp2d->update([
                    'nomor_sp2d'   => $request->nomor_sp2d,
                    'tanggal_sp2d' => $request->tanggal_sp2d,
                ]);

                LogStatusDokumen::create([
                    'dokumen_type'      => DokumenSp2d::class,
                    'dokumen_id'        => $sp2d->id,
                    'user_id'           => Auth::id(),
                    'role_saat_itu'     => 'Bendahara Pengeluaran',
                    'status_sebelumnya' => $sp2d->status,
                    'status_baru'       => $sp2d->status, 
                    'aksi'              => 'UPDATE_SP2D',
                    'catatan'           => $request->catatan ?: 'Pembaruan data referensi pada SP2D Honorarium',
                    'ip_address'        => $request->ip(),
                ]);
            }

            DB::commit();
            return back()->with('success', 'Data draft SP2D Honorarium berhasil direkam aman.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal memahat draft SP2D Honor: ' . $e->getMessage());
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
            return back()->with('error', 'Simpan rincian draft SP2D terlebih dahulu sebelum melancarkan pengajuan!');
        }

        if (!in_array($sp2d->status, [DokumenSp2d::STATUS_DRAFT, DokumenSp2d::STATUS_REVISI])) {
            return back()->with('error', 'SP2D meleset dari status draft/revisi sehingga tidak dapat dirilis ulang.');
        }

        if (empty($sp2d->nomor_sp2d) || empty($sp2d->tanggal_sp2d)) {
            return back()->with('error', 'Yakinkan Nomor dan Tanggal SP2D telah bertinta mutlak sebelum penyerahan.');
        }

        DB::beginTransaction();
        try {
            $statusSebelum = $sp2d->status;
            
            // Initiate parallel workflow
            $this->workflowService->startWorkflow('SP2D_HONORARIUM', $sp2d);
            
            $sp2d->update(['status' => DokumenSp2d::STATUS_MENUNGGU_VERIFIKASI]);

            LogStatusDokumen::create([
                'dokumen_type'      => DokumenSp2d::class,
                'dokumen_id'        => $sp2d->id,
                'user_id'           => Auth::id(),
                'role_saat_itu'     => 'Bendahara Pengeluaran',
                'status_sebelumnya' => $statusSebelum,
                'status_baru'       => DokumenSp2d::STATUS_MENUNGGU_VERIFIKASI,
                'aksi'              => 'SUBMIT_SP2D',
                'catatan'           => 'SP2D Honorarium didorong mutlak. Menanti verifikasi PPK dan Kasubbag.',
                'ip_address'        => $request->ip(),
            ]);

            // Notify Parallels
            $ppks = User::role('PPK')->get();
            Notification::send($ppks, new WorkflowNotification([
                'title' => 'Tugas Verifikasi SP2D Honorarium',
                'message' => "SP2D Honorarium #{$sp2d->nomor_sp2d} dari NPI {$npi->nomor_npi} menuntut izin verifikasi.",
                'url' => route('verifikasi-ppk.sp2d.kontrak.index') // Note: Adjust link to verifikasi-sp2d if existing
            ]));

            $kasubbags = User::role('Kepala Subbagian Keuangan dan Tata Usaha')->get();
            Notification::send($kasubbags, new WorkflowNotification([
                'title' => 'Tugas Verifikasi SP2D Honorarium',
                'message' => "Mohon evaluasi paralelisasi penyelesaian SP2D Honorarium #{$sp2d->nomor_sp2d}.",
                'url' => route('verifikasi-kasubag.sp2d.kontrak.index') // Note: same
            ]));

            DB::commit();
            
            return redirect()->route('sp2ds.honor.detail', $npi->id)->with('success', 'Ajaib! SP2D Honorarium sukses meluncur menuju gerbang Para Pemangku Verifikator.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Mesin rilis gagal! ' . $e->getMessage());
        }
    }
}
