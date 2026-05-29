<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TagihanJasa;
use App\Models\WorkflowInstance;
use App\Models\WorkflowApproval;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Services\WorkflowService;

class TagihanJasaVerifikasiController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $workflowService = app(WorkflowService::class);
        
        $userRoles = $user->getRoleNames()->toArray();
        if (in_array('PLT/PLH', $userRoles, true) && !in_array('KPA', $userRoles, true)) {
            $userRoles[] = 'KPA';
        }
        if (in_array('KPA', $userRoles, true) && !in_array('PLT/PLH', $userRoles, true)) {
            $userRoles[] = 'PLT/PLH';
        }

        $tagihans = TagihanJasa::with(['mitra', 'mitraLegacy', 'creator', 'workflowInstance.approvals'])
            ->whereHas('workflowInstance', function ($q) use ($user, $userRoles) {
                $q->where('status', 'IN_PROGRESS')
                  ->whereHas('approvals', function ($q2) use ($userRoles) {
                      $q2->where('status', 'PENDING')
                         ->whereIn('role_code', $userRoles);
                  });
            })
            ->latest()
            ->get();
            
        // Additional filter for EXACT current step (safety check)
        $filteredTagihans = $tagihans->filter(function($tagihan) use ($workflowService, $user) {
            return $workflowService->hasPendingApprovalForUser($tagihan, $user->id);
        });

        return view('tagihan_jasa.verifikasi_index', ['tagihans' => $filteredTagihans]);
    }

    public function show($id)
    {
        $tagihan = TagihanJasa::with([
            'mitra',
            'mitraLegacy',
            'creator',
            'details.layananJasa.parent.parent.parent.parent.parent',
            'workflowInstance.approvals.actedByUser',
        ])->findOrFail($id);
        return view('tagihan_jasa.show', compact('tagihan'));
    }

    public function approve(Request $request, $id)
    {
        $request->validate([
            'catatan' => 'nullable|string',
        ]);

        $tagihan = TagihanJasa::findOrFail($id);
        $workflowInstance = $tagihan->workflowInstance;

        if (!$workflowInstance) {
            return back()->with('error', 'Workflow tidak ditemukan.');
        }

        try {
            DB::beginTransaction();

            $workflowService = app(WorkflowService::class);
            $workflowService->approveCurrentStep($tagihan, Auth::id(), $request->catatan);

            // Perbarui status tagihan berdasarkan step workflow yang baru disetujui.
            // Konvensi status menggambarkan ROLE YANG SEDANG MENUNGGU APPROVAL (next pending),
            // bukan role yang baru saja approve.
            $wfInstance = $tagihan->workflowInstance()->latest()->first();
            $currentApproval = $wfInstance
                ? $wfInstance->approvals()->where('urutan_step', $wfInstance->step_saat_ini)->first()
                : null;

            if ($wfInstance && $wfInstance->status === 'APPROVED') {
                // Seluruh tahap verifikasi (termasuk KPA / Kabandara) sudah selesai.
                $tagihan->status = 'DISETUJUI';
                $tagihan->save();
                $this->generateFinalSuratPengantar($tagihan);
            } elseif ($currentApproval) {
                $statusMap = [
                    'Koordinator Jasa'                          => 'VERIFIKASI_KOORDINATOR',
                    'Kepala Seksi Pelayanan dan Kerjasama'      => 'VERIFIKASI_KASI_JASA',
                    'Kepala Subbagian Keuangan dan Tata Usaha'  => 'VERIFIKASI_KASUBAG_TU',
                    'KPA'                                       => 'VERIFIKASI_KABANDARA',
                    'PLT/PLH'                                   => 'VERIFIKASI_KABANDARA',
                ];
                if (isset($statusMap[$currentApproval->role_code])) {
                    $tagihan->status = $statusMap[$currentApproval->role_code];
                    $tagihan->save();
                }
            }

            DB::commit();
            return back()->with('success', 'Tagihan Jasa berhasil disetujui.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    private function generateFinalSuratPengantar(TagihanJasa $tagihan): void
    {
        $approver = Auth::user();
        $pegawai = $approver?->pegawai;

        $tagihan->forceFill([
            'pejabat_penandatangan_nama' => $pegawai?->nama_lengkap ?: ($approver?->name ?: $tagihan->pejabat_penandatangan_nama),
            'pejabat_penandatangan_nip' => $pegawai?->nip ?: $tagihan->pejabat_penandatangan_nip,
            'pejabat_penandatangan_jabatan' => $pegawai?->jabatan
                ?: ($approver?->hasRole('PLT/PLH') ? 'PLT/PLH Kepala Badan Layanan Umum' : ($tagihan->pejabat_penandatangan_jabatan ?: 'Kepala Badan Layanan Umum')),
            'tanggal_surat_pengantar' => $tagihan->tanggal_surat_pengantar ?: $tagihan->tanggal_tagihan,
            'perihal_surat_pengantar' => $tagihan->perihal_surat_pengantar ?: 'Penyampaian Tagihan PNBP Jasa',
        ])->save();

        $signedTagihan = $tagihan->fresh([
            'mitra',
            'mitraLegacy',
            'kontrakMitraJasa',
            'creator',
            'details.layananJasa',
        ]);

        $pdf = Pdf::loadView('tagihan_jasa.surat_pengantar_pdf', [
            'tagihan' => $signedTagihan,
            'signed' => true,
        ])->setPaper('a4', 'portrait');

        $fileName = 'surat-pengantar-final-' . str_replace(['/', '\\'], '-', $signedTagihan->nomor_tagihan) . '-' . now()->format('YmdHis') . '.pdf';
        $path = 'tagihan-jasa/surat-pengantar-final/' . $fileName;

        $pdfContent = $pdf->output();
        Storage::disk('public')->put($path, $pdfContent);
        $this->archiveFinalSuratPengantar($tagihan, $fileName, $path, strlen($pdfContent));

        $tagihan->forceFill([
            'file_surat_pengantar_final' => $path,
            'uploaded_surat_pengantar_by' => Auth::id(),
            'uploaded_surat_pengantar_at' => now(),
            'status_dokumen_pengantar' => 'SUDAH_DITANDATANGANI',
        ])->save();
    }

    private function archiveFinalSuratPengantar(TagihanJasa $tagihan, string $fileName, string $path, ?int $size = null): void
    {
        $tagihan->arsipDokumen()
            ->where('jenis_dokumen', 'SURAT_PENGANTAR_FINAL_TTD')
            ->where('is_active', true)
            ->update(['is_active' => false]);

        $fullPath = Storage::disk('public')->path($path);

        $tagihan->arsipDokumen()->create([
            'jenis_dokumen' => 'SURAT_PENGANTAR_FINAL_TTD',
            'nama_file_asli' => $fileName,
            'path_file' => $path,
            'disk' => 'public',
            'mime_type' => 'application/pdf',
            'ukuran_file' => $size ?? (Storage::disk('public')->exists($path) ? Storage::disk('public')->size($path) : null),
            'checksum' => is_file($fullPath) ? hash_file('sha256', $fullPath) : null,
            'uploaded_by' => Auth::id(),
            'uploaded_at' => now(),
            'keterangan' => 'Arsip surat pengantar final bertanda tangan elektronik dari approval final.',
            'is_active' => true,
        ]);
    }

    public function reject(Request $request, $id)
    {
        $request->validate([
            'catatan' => 'required|string',
        ]);

        $tagihan = TagihanJasa::findOrFail($id);
        $workflowInstance = $tagihan->workflowInstance;

        if (!$workflowInstance) {
            return back()->with('error', 'Workflow tidak ditemukan.');
        }

        try {
            DB::beginTransaction();

            $workflowService = app(WorkflowService::class);
            $workflowService->rejectCurrentStep($tagihan, Auth::id(), $request->catatan);

            $tagihan->status = 'DITOLAK';
            $tagihan->save();

            DB::commit();
            return back()->with('success', 'Tagihan Jasa berhasil ditolak/dikembalikan.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function revision(Request $request, $id)
    {
        $request->validate([
            'catatan' => 'required|string',
        ]);

        $tagihan = TagihanJasa::findOrFail($id);
        $workflowInstance = $tagihan->workflowInstance;

        if (!$workflowInstance) {
            return back()->with('error', 'Workflow tidak ditemukan.');
        }

        try {
            DB::beginTransaction();

            $workflowService = app(WorkflowService::class);
            $workflowService->requestRevision($tagihan, Auth::id(), $request->catatan);

            $tagihan->status = 'REVISI';
            $tagihan->save();

            DB::commit();
            return back()->with('success', 'Tagihan Jasa dikembalikan untuk revisi.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }
}
