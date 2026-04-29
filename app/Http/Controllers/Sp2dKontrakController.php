<?php

namespace App\Http\Controllers;

use App\Models\DokumenNpi;
use App\Models\DokumenSp2d;
use App\Models\LogStatusDokumen;
use App\Services\WorkflowService;
use App\Models\User;
use App\Notifications\WorkflowNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class Sp2dKontrakController extends Controller
{
    /**
     * Halaman antrean SP2D Kontrak untuk Bendahara Pengeluaran.
     */
    public function index(Request $request)
    {
        $statusFilter = $request->input('status', 'semua');
        $search = $request->input('search');

        // Query dasar: NPI Kontrak yang sudah DISETUJUI_FINAL
        // atau sudah memiliki SP2D Kontrak (meski status NPI sudah berubah misal karena sinkronisasi, meski normalnya tetap final)
        $query = DokumenNpi::with([
            'sp2d.workflowInstances.approvals',
            'spm.spp.tagihan.detailKontrak.kontrakTermin.kontrak.vendor'
        ])
        ->whereHas('spm.spp.tagihan', fn($q) => $q->where('tipe_tagihan', 'KONTRAK'))
        ->where(function ($q) {
            $q->where('status', DokumenNpi::STATUS_DISETUJUI_FINAL)
              ->orWhereHas('sp2d');
        });

        // Filter status SP2D
        if ($statusFilter === 'belum_dibuat') {
            $query->whereDoesntHave('sp2d');
        } elseif ($statusFilter === 'draft') {
            $query->whereHas('sp2d', fn($q) => $q->where('status', DokumenSp2d::STATUS_DRAFT));
        } elseif ($statusFilter === 'revisi') {
            $query->whereHas('sp2d', fn($q) => $q->where('status', DokumenSp2d::STATUS_REVISI));
        } elseif ($statusFilter === 'menunggu') {
            $query->whereHas('sp2d', fn($q) => $q->where('status', DokumenSp2d::STATUS_MENUNGGU_VERIFIKASI));
        } elseif ($statusFilter === 'selesai') {
            $query->whereHas('sp2d', fn($q) => $q->where('status', DokumenSp2d::STATUS_DISETUJUI_FINAL));
        }

        if ($search) {
            $s = strtolower($search);
            $query->where(function($q) use ($s) {
                $q->whereHas('sp2d', fn($sq) => $sq->whereRaw('LOWER(nomor_sp2d) LIKE ?', ["%{$s}%"]))
                  ->orWhereRaw('LOWER(nomor_npi) LIKE ?', ["%{$s}%"])
                  ->orWhereHas('spm', function($sq) use ($s) {
                      $sq->whereRaw('LOWER(nomor_spm) LIKE ?', ["%{$s}%"])
                         ->orWhereHas('spp', function($ssq) use ($s) {
                             $ssq->whereRaw('LOWER(nomor_spp) LIKE ?', ["%{$s}%"])
                                 ->orWhereHas('tagihan', function($sssq) use ($s) {
                                     $sssq->whereRaw('LOWER(nomor_tagihan) LIKE ?', ["%{$s}%"])
                                          ->orWhereHas('detailKontrak.kontrakTermin.kontrak', function($c) use ($s) {
                                              $c->whereRaw('LOWER(nomor_spk) LIKE ?', ["%{$s}%"])
                                                ->orWhereRaw('LOWER(nama_pekerjaan) LIKE ?', ["%{$s}%"])
                                                ->orWhereHas('vendor', fn($v) => $v->whereRaw('LOWER(nama_pihak) LIKE ?', ["%{$s}%"]));
                                          });
                                 });
                         });
                  });
            });
        }

        $npis = $query->latest()->get();

        // Siapkan atribut tambahan & counter
        $summary = [
            'belum_dibuat' => 0,
            'draft_revisi' => 0,
            'menunggu' => 0,
            'selesai' => 0,
        ];

        $listSp2d = $npis->map(function($npi) use (&$summary) {
            $sp2d = $npi->sp2d;
            $spm = $npi->spm;
            $spp = $spm?->spp;
            $tagihan = $spp?->tagihan;
            $kontrak = $tagihan?->detailKontrak?->kontrakTermin?->kontrak;
            
            $nominal = (float) ($spp?->nominal_spp ?? $tagihan?->total_netto ?? 0);

            // Tentukan status badge
            $statusBadge = 'Belum Dibuat';
            $statusClass = 'bg-warning text-dark';
            $ppkStatus = '-';
            $kasubbagStatus = '-';

            if (!$sp2d) {
                $summary['belum_dibuat']++;
            } else {
                if ($sp2d->status === DokumenSp2d::STATUS_DRAFT) {
                    $statusBadge = 'Draft';
                    $statusClass = 'bg-secondary';
                    $summary['draft_revisi']++;
                } elseif ($sp2d->status === DokumenSp2d::STATUS_REVISI) {
                    $statusBadge = 'Revisi';
                    $statusClass = 'bg-danger';
                    $summary['draft_revisi']++;
                } elseif ($sp2d->status === DokumenSp2d::STATUS_MENUNGGU_VERIFIKASI) {
                    $statusBadge = 'Menunggu Verifikasi';
                    $statusClass = 'bg-info';
                    $summary['menunggu']++;
                } elseif ($sp2d->status === DokumenSp2d::STATUS_DISETUJUI_FINAL) {
                    $statusBadge = 'Disetujui Final';
                    $statusClass = 'bg-success';
                    $summary['selesai']++;
                } elseif ($sp2d->status === DokumenSp2d::STATUS_EXECUTED) {
                    $statusBadge = 'Lunas / Executed';
                    $statusClass = 'bg-primary';
                    $summary['selesai']++;
                }

                // Cek status verifikasi (Workflow)
                $wf = $sp2d->workflowInstances->sortByDesc('created_at')->first();
                if ($wf) {
                    $approvals = collect($wf->approvals);
                    $ppkApproval = $approvals->firstWhere('role_code', 'PPK');
                    $ksbApproval = $approvals->firstWhere('role_code', 'Kepala Subbagian Keuangan dan Tata Usaha');
                    
                    $ppkStatus = $ppkApproval ? $ppkApproval->status : '-';
                    $kasubbagStatus = $ksbApproval ? $ksbApproval->status : '-';
                }
            }

            return (object) [
                'npi_id' => $npi->id,
                'sp2d_id' => $sp2d?->id,
                'nomor_npi' => $npi->nomor_npi,
                'tanggal_npi' => $npi->tanggal_npi,
                'nomor_sp2d' => $sp2d?->nomor_sp2d,
                'tanggal_sp2d' => $sp2d?->tanggal_sp2d,
                'nomor_spm' => $spm?->nomor_spm,
                'nomor_spp' => $spp?->nomor_spp,
                'nomor_spk' => $kontrak?->nomor_spk,
                'nama_vendor' => $kontrak?->vendor?->nama_pihak,
                'nama_pekerjaan' => $kontrak?->nama_pekerjaan,
                'nominal' => $nominal,
                'status_badge' => $statusBadge,
                'status_class' => $statusClass,
                'is_draft' => !$sp2d || in_array($sp2d->status, [DokumenSp2d::STATUS_DRAFT, DokumenSp2d::STATUS_REVISI]),
                'raw_status' => $sp2d?->status,
                'ppk_status' => $ppkStatus,
                'kasubbag_status' => $kasubbagStatus,
            ];
        });

        return view('sp2ds.kontrak_index', compact('listSp2d', 'summary', 'statusFilter', 'search'));
    }

    /**
     * Halaman detail workspace SP2D Kontrak.
     */
    public function show($npi_id)
    {
        $npi = DokumenNpi::with([
            'sp2d.workflowInstances.approvals.actedByUser',
            'sp2d.logs.user',
            'sp2d.arsipDokumen',
            'spm.spp.tagihan.detailKontrak.kontrakTermin.kontrak.vendor.rekening',
            'spm.spp.tagihan.potonganTagihan',
        ])->findOrFail($npi_id);

        $sp2d = $npi->sp2d;
        $spm = $npi->spm;
        $spp = $spm?->spp;
        $tagihan = $spp?->tagihan;
        $detailKontrak = $tagihan?->detailKontrak;
        $termin = $detailKontrak?->kontrakTermin;
        $kontrak = $termin?->kontrak;
        $vendor = $kontrak?->vendor;
        $rekening = $vendor?->rekening?->first();

        $nominalSp2d = (float) ($spp?->nominal_spp ?? $tagihan?->total_netto ?? 0);

        // Tracker status
        $statusSp2d = $sp2d ? $sp2d->status : 'BELUM DIBUAT';
        $isEditable = in_array($statusSp2d, ['BELUM DIBUAT', DokumenSp2d::STATUS_DRAFT, DokumenSp2d::STATUS_REVISI]);
        $canSubmit = $sp2d && in_array($sp2d->status, [DokumenSp2d::STATUS_DRAFT, DokumenSp2d::STATUS_REVISI]) 
                     && $sp2d->nomor_sp2d && $sp2d->tanggal_sp2d;

        $wf = $sp2d ? $sp2d->workflowInstances->sortByDesc('created_at')->first() : null;
        $approvals = collect($wf ? $wf->approvals : []);

        $ppkApproval = $approvals->firstWhere('role_code', 'PPK');
        $kasubbagApproval = $approvals->firstWhere('role_code', 'Kepala Subbagian Keuangan dan Tata Usaha');

        $revisionNotes = collect();
        if ($sp2d) {
            $revisionNotes = $sp2d->logs()
                ->whereIn('status_baru', [DokumenSp2d::STATUS_REVISI])
                ->latest()
                ->get()
                ->map(fn($l) => [
                    'user' => $l->user?->name ?? 'Sistem',
                    'role' => $l->role_saat_itu,
                    'catatan' => $l->catatan,
                    'time' => $l->created_at->format('d M Y H:i'),
                ]);
        }

        $autoNomorSp2d = \App\Services\DocumentNumberingService::generateDerivedNumber($spp->nomor_spp, 'SP2D');

        return view('sp2ds.kontrak_detail', compact(
            'npi', 'sp2d', 'spm', 'spp', 'tagihan', 'detailKontrak', 'termin', 'kontrak',
            'vendor', 'rekening', 'nominalSp2d', 'statusSp2d', 'isEditable', 'canSubmit',
            'wf', 'ppkApproval', 'kasubbagApproval', 'revisionNotes', 'autoNomorSp2d'
        ));
    }

    /**
     * Simpan Draft SP2D. (Tidak membuat workflow)
     */
    public function storeDraft(Request $request, $npi_id)
    {
        $npi = DokumenNpi::with('sp2d')->findOrFail($npi_id);

        $request->validate([
            'nomor_sp2d' => 'required|string|max:100',
            'tanggal_sp2d' => 'required|date',
            // Field opsional tambahan jika ada (e.g., tanggal_pencairan, referensi_bank, dll.)
        ]);

        DB::transaction(function () use ($request, $npi) {
            $statusSebelumnya = $npi->sp2d ? $npi->sp2d->status : null;
            
            // Jaga agar yang REVISI tetap DRAFT/REVISI sampai submit
            $newStatus = DokumenSp2d::STATUS_DRAFT;
            if ($statusSebelumnya === DokumenSp2d::STATUS_REVISI) {
                // Biarkan tetap revisi sampai diajukan
                $newStatus = DokumenSp2d::STATUS_REVISI;
            }

            $sp2d = $npi->sp2d()->updateOrCreate(
                ['npi_id' => $npi->id],
                [
                    'nomor_sp2d' => $request->nomor_sp2d,
                    'tanggal_sp2d' => $request->tanggal_sp2d,
                    'bendahara_pengeluaran_id' => Auth::id(),
                    // Update ke status draft, jika sebelumnya kosong.
                    'status' => $statusSebelumnya ?: DokumenSp2d::STATUS_DRAFT,
                ]
            );

            // Jika statusnya belum ada (baru create), log.
            if (!$statusSebelumnya) {
                $this->logStatus($sp2d, null, DokumenSp2d::STATUS_DRAFT, 'CREATE_DRAFT', 'Draft SP2D Kontrak dibuat.');
            } else {
                $this->logStatus($sp2d, $statusSebelumnya, $sp2d->status, 'UPDATE_DRAFT', 'Draft SP2D Kontrak diperbarui.');
            }
        });

        return redirect()->route('sp2ds.kontrak.detail', $npi_id)->with('success', 'Draft SP2D berhasil disimpan.');
    }

    /**
     * Ajukan Verifikasi (Membentuk instance Workflow Parallel)
     */
    public function submitVerification(Request $request, $npi_id)
    {
        $npi = DokumenNpi::with('sp2d')->findOrFail($npi_id);
        $sp2d = $npi->sp2d;

        if (!$sp2d || !in_array($sp2d->status, [DokumenSp2d::STATUS_DRAFT, DokumenSp2d::STATUS_REVISI])) {
            return back()->with('error', 'Hanya SP2D berstatus draft/revisi yang dapat diajukan verifikasi.');
        }

        if (!$sp2d->nomor_sp2d || !$sp2d->tanggal_sp2d) {
            return back()->with('error', 'Lengkapi Nomor dan Tanggal SP2D terlebih dahulu.');
        }

        DB::transaction(function () use ($sp2d) {
            $statusSebelumnya = $sp2d->status;
            
            // Start workflow!
            $workflowService = app(WorkflowService::class);
            
            // Define expected step count mapping so it returns correctly
            $expectedSteps = [
                'PPK' => 1,
                'Kepala Subbagian Keuangan dan Tata Usaha' => 1,
            ];

            $workflowService->startWorkflow('SP2D_KONTRAK', $sp2d);
            $sp2d->update(['status' => DokumenSp2d::STATUS_MENUNGGU_VERIFIKASI]);

            $this->logStatus(
                $sp2d,
                $statusSebelumnya,
                DokumenSp2d::STATUS_MENUNGGU_VERIFIKASI,
                'SUBMIT_VERIFICATION',
                'SP2D diajukan untuk diverifikasi secara paralel.'
            );

            // Notify Verifiers
            $ppkUsers = collect();
            $spp = optional(optional(optional($sp2d->npi)->spm)->spp);
            if ($spp && $spp->dibuat_oleh_id) {
                // If the PPK is strictly the one from SPP, we could send it to them.
                // Or broadcast to role 'PPK'
                $ppkUsers = User::role('PPK')->get();
            } else {
                $ppkUsers = User::role('PPK')->get();
            }

            $ksbUsers = User::role('Kepala Subbagian Keuangan dan Tata Usaha')->get();
            $verifiers = $ppkUsers->concat($ksbUsers)->unique('id');

            if ($verifiers->isNotEmpty()) {
                Notification::send($verifiers, new WorkflowNotification([
                    'title' => 'Verifikasi SP2D Kontrak',
                    'message' => "Ada pengajuan verifikasi SP2D Kontrak {$sp2d->nomor_sp2d} dari Bendahara Pengeluaran.",
                    'url' => '#', // Akan diganti setelah halaman mereka selesai
                    'icon' => 'fact_check',
                    'color' => 'primary',
                ]));
            }
        });

        return redirect()->route('sp2ds.kontrak.index')->with('success', 'SP2D berhasil diajukan untuk verifikasi.');
    }

    private function logStatus(DokumenSp2d $sp2d, ?string $statusSebelumnya, string $statusBaru, string $aksi, ?string $catatan = null)
    {
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
}
