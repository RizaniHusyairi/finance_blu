<?php

namespace App\Http\Controllers;

use App\Models\DokumenSpm;
use App\Models\LogStatusDokumen;
use App\Models\Perjaldin;
use App\Models\Spp;
use App\Models\User;
use App\Notifications\WorkflowNotification;
use App\Support\PaymentPdfReference;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class SpmController extends Controller
{
    public function index()
    {
        $perjaldins = Perjaldin::with(['pejabats', 'spps.spm'])
            ->whereHas('spps', function ($q) {
                $q->whereIn('status', ['Disetujui PPK', 'DISETUJUI_SPP', 'APPROVED'])
                    ->orWhereHas('spm');
            })
            ->latest()
            ->get();

        return view('spms.index', compact('perjaldins'));
    }

    public function detail($perjaldin_id)
    {
        $perjaldin = Perjaldin::with(['pejabats', 'spps.spm.ppspm'])->findOrFail($perjaldin_id);
        $ppspms = User::role('PPSPM')->get();

        return view('spms.detail_perjaldin', compact('perjaldin', 'ppspms'));
    }

    public function store(Request $request, $spp_id)
    {
        $spp = Spp::with(['spm', 'tagihan'])->findOrFail($spp_id);

        $request->validate([
            'nomor_spm' => 'required|string|max:100',
            'tanggal_spm' => 'required|date',
            'ppspm_id' => 'required|exists:users,id',
        ]);

        DB::transaction(function () use ($request, $spp) {
            $spm = $spp->spm()->updateOrCreate(
                ['spp_id' => $spp->id],
                [
                    'nomor_spm' => $request->nomor_spm,
                    'tanggal_spm' => $request->tanggal_spm,
                    'ppspm_id' => $request->ppspm_id,
                    'status' => DokumenSpm::STATUS_SUBMITTED_PPSPM,
                ]
            );

            $this->logStatus(
                $spm,
                $spm->wasRecentlyCreated ? null : $spm->getOriginal('status'),
                DokumenSpm::STATUS_SUBMITTED_PPSPM,
                $spm->wasRecentlyCreated ? 'CREATE_AND_SUBMIT' : 'UPDATE_AND_RESUBMIT',
                'Draft SPM diajukan ke PPSPM.'
            );

            $this->notifyRoles(
                ['PPSPM'],
                'Verifikasi SPM Baru',
                "Ada SPM {$spm->nomor_spm} yang menunggu verifikasi PPSPM.",
                route('verifikasi-ppspm.spm.index')
            );
        });

        return back()->with('success', 'SPM berhasil dibuat dan dikirim ke meja PPSPM.');
    }

    public function cetakPdfSpm($spm_id)
    {
        require_once app_path('Helpers/TerbilangHelper.php');

        $spm = DokumenSpm::with([
            'spp.dipaRevisionItem.dipaRevision.masterDipa',
            'spp.tagihan.pihak.rekening',
            'spp.tagihan.detailKontrak.kontrakTermin.kontrak.vendor.rekening',
            'spp.tagihan.detailPerjaldin',
            'spp.tagihan.detailHonorarium',
            'spp.tagihan.dipa',
            'spp.tagihan.dipaRevisionItem.dipaRevision.masterDipa',
            'ppspm',
        ])->findOrFail($spm_id);
        $spp = $spm->spp;
        $sppable = $spp?->tagihan;
        $jumlahUang = (float) ($spp?->nominal_spp ?? 0);
        $uraianSupplier = $sppable?->deskripsi ?: ($spp?->uraian ?? 'Belanja Perjalanan Dinas');
        $terbilang = \terbilang_rupiah($jumlahUang);
        $pdfReference = PaymentPdfReference::forTagihan($spp?->tagihan);
        $dipaInfo = PaymentPdfReference::dipaForSpp($spp);
        $supplierInfo = PaymentPdfReference::supplierForTagihan($spp?->tagihan, $uraianSupplier);

        if (! $spm->nomor_spm) {
            $spm->nomor_spm = 'SPM-BLU/APTP-' . date('Y') . '/DRAFT';
        }

        if (! $spm->tanggal_spm) {
            $spm->tanggal_spm = now()->toDateString();
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
            'spms.pdf',
            compact('spp', 'spm', 'sppable', 'jumlahUang', 'terbilang', 'uraianSupplier', 'pdfReference', 'dipaInfo', 'supplierInfo')
        );
        $pdf->setPaper('a4', 'portrait');

        return $pdf->stream('SPM-BLU-' . str_replace('/', '-', $spm->nomor_spm) . '.pdf');
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
