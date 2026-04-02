<?php

namespace App\Http\Controllers;

use App\Models\DokumenSpm;
use App\Models\LogStatusDokumen;
use App\Models\User;
use App\Notifications\WorkflowNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;

class SpmVerifikasiController extends Controller
{
    public function index()
    {
        $spms = DokumenSpm::with(['spp.tagihan', 'ppspm'])
            ->whereIn('status', [
                DokumenSpm::STATUS_SUBMITTED_PPSPM,
                DokumenSpm::STATUS_SUBMITTED_KASUBAG,
                DokumenSpm::STATUS_APPROVED_KASUBAG,
            ])
            ->orderByRaw(
                "CASE 
                    WHEN status = ? THEN 1
                    WHEN status = ? THEN 2
                    ELSE 3
                END",
                [
                    DokumenSpm::STATUS_SUBMITTED_PPSPM,
                    DokumenSpm::STATUS_SUBMITTED_KASUBAG,
                ]
            )
            ->latest()
            ->get();

        return view('verifikasi_ppspm.spm_index', [
            'spms' => $spms,
            'stage' => 'ppspm',
            'pageTitle' => 'Verifikasi SPM',
            'pageSubtitle' => 'Pejabat Penandatangan SPM',
            'pendingCount' => $spms->where('status', DokumenSpm::STATUS_SUBMITTED_PPSPM)->count(),
            'approvedCount' => $spms->whereIn('status', [
                DokumenSpm::STATUS_SUBMITTED_KASUBAG,
                DokumenSpm::STATUS_APPROVED_KASUBAG,
            ])->count(),
            'approveRouteName' => 'verifikasi-ppspm.spm.approve',
            'rejectRouteName' => 'verifikasi-ppspm.spm.revisi',
        ]);
    }

    public function approve($spm_id)
    {
        $spm = DokumenSpm::with('spp')->findOrFail($spm_id);
        $statusSebelumnya = $spm->status;

        $spm->update([
            'status' => DokumenSpm::STATUS_SUBMITTED_KASUBAG,
        ]);

        $this->logStatus(
            $spm,
            $statusSebelumnya,
            DokumenSpm::STATUS_SUBMITTED_KASUBAG,
            'APPROVE_PPSPM',
            'SPM disetujui PPSPM dan diteruskan ke Kasubbag.'
        );

        $this->notifyRoles(
            ['Kepala Subbagian Keuangan dan Tata Usaha'],
            'SPM Menunggu Persetujuan Kasubbag',
            "SPM {$spm->nomor_spm} telah disetujui PPSPM dan menunggu persetujuan Kasubbag.",
            route('verifikasi-kasubag.spm.index')
        );

        return back()->with('success', 'SPM disetujui PPSPM dan diteruskan ke Kasubbag.');
    }

    public function revisi(Request $request, $spm_id)
    {
        $request->validate([
            'catatan_revisi' => 'required|string|max:1000',
        ]);

        $spm = DokumenSpm::with('spp')->findOrFail($spm_id);
        $statusSebelumnya = $spm->status;

        $spm->update([
            'status' => DokumenSpm::STATUS_REJECTED_PPSPM,
        ]);

        $this->logStatus(
            $spm,
            $statusSebelumnya,
            DokumenSpm::STATUS_REJECTED_PPSPM,
            'REJECT_PPSPM',
            $request->catatan_revisi
        );

        $this->notifyRoles(
            ['Operator BLU'],
            'SPM Direvisi PPSPM',
            "SPM {$spm->nomor_spm} dikembalikan PPSPM: {$request->catatan_revisi}",
            route('spms.index')
        );

        return back()->with('success', 'SPM dikembalikan ke Operator untuk diperbaiki.');
    }

    public function kasubbagIndex()
    {
        $spms = DokumenSpm::with(['spp.tagihan', 'ppspm'])
            ->whereIn('status', [
                DokumenSpm::STATUS_SUBMITTED_KASUBAG,
                DokumenSpm::STATUS_APPROVED_KASUBAG,
            ])
            ->orderByRaw(
                "CASE WHEN status = ? THEN 1 ELSE 2 END",
                [DokumenSpm::STATUS_SUBMITTED_KASUBAG]
            )
            ->latest()
            ->get();

        return view('verifikasi_ppspm.spm_index', [
            'spms' => $spms,
            'stage' => 'kasubbag',
            'pageTitle' => 'Verifikasi SPM',
            'pageSubtitle' => 'Kepala Subbagian Keuangan dan Tata Usaha',
            'pendingCount' => $spms->where('status', DokumenSpm::STATUS_SUBMITTED_KASUBAG)->count(),
            'approvedCount' => $spms->where('status', DokumenSpm::STATUS_APPROVED_KASUBAG)->count(),
            'approveRouteName' => 'verifikasi-kasubag.spm.approve',
            'rejectRouteName' => 'verifikasi-kasubag.spm.revisi',
        ]);
    }

    public function approveKasubbag($spm_id)
    {
        $spm = DokumenSpm::with('spp')->findOrFail($spm_id);
        $statusSebelumnya = $spm->status;

        $spm->update([
            'status' => DokumenSpm::STATUS_APPROVED_KASUBAG,
        ]);

        $this->logStatus(
            $spm,
            $statusSebelumnya,
            DokumenSpm::STATUS_APPROVED_KASUBAG,
            'APPROVE_KASUBAG',
            'SPM disetujui Kasubbag dan siap diproses ke tahap berikutnya.'
        );

        $this->notifyRoles(
            ['Operator BLU', 'Bendahara Pengeluaran'],
            'SPM Telah Final',
            "SPM {$spm->nomor_spm} telah disetujui Kasubbag.",
            route('spms.index')
        );

        return back()->with('success', 'SPM disetujui Kasubbag.');
    }

    public function revisiKasubbag(Request $request, $spm_id)
    {
        $request->validate([
            'catatan_revisi' => 'required|string|max:1000',
        ]);

        $spm = DokumenSpm::with('spp')->findOrFail($spm_id);
        $statusSebelumnya = $spm->status;

        $spm->update([
            'status' => DokumenSpm::STATUS_REJECTED_KASUBAG,
        ]);

        $this->logStatus(
            $spm,
            $statusSebelumnya,
            DokumenSpm::STATUS_REJECTED_KASUBAG,
            'REJECT_KASUBAG',
            $request->catatan_revisi
        );

        $this->notifyRoles(
            ['Operator BLU'],
            'SPM Direvisi Kasubbag',
            "SPM {$spm->nomor_spm} dikembalikan Kasubbag: {$request->catatan_revisi}",
            route('spms.index')
        );

        return back()->with('success', 'SPM dikembalikan oleh Kasubbag.');
    }

    private function logStatus(
        DokumenSpm $spm,
        ?string $statusSebelumnya,
        string $statusBaru,
        string $aksi,
        ?string $catatan = null
    ): void {
        $user = Auth::user();

        LogStatusDokumen::create([
            'dokumen_type' => DokumenSpm::class,
            'dokumen_id' => $spm->id,
            'user_id' => $user?->id,
            'role_saat_itu' => $user?->getRoleNames()->first() ?? 'SYSTEM',
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
