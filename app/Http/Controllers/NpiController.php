<?php

namespace App\Http\Controllers;

use App\Models\DokumenNpi;
use App\Models\LogStatusDokumen;
use App\Models\Perjaldin;
use App\Models\User;
use App\Notifications\WorkflowNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class NpiController extends Controller
{
    public function index()
    {
        $perjaldins = Perjaldin::with(['pejabats', 'spps.spm.npi'])
            ->whereHas('spps', function ($q) {
                $q->whereHas('spm', function ($spm) {
                    $spm->where('status', 'APPROVED_KASUBAG')
                        ->orWhereHas('npi');
                });
            })
            ->latest()
            ->get();

        $bendaharaPenerimaans = User::role('Bendahara Penerimaan')->get();

        return view('npis.index', compact('perjaldins', 'bendaharaPenerimaans'));
    }

    public function detail($perjaldin_id)
    {
        $perjaldin = Perjaldin::with(['pejabats', 'spps.spm.npi.bendaharaPenerimaan'])->findOrFail($perjaldin_id);
        $bendaharaPenerimaans = User::role('Bendahara Penerimaan')->get();

        return view('npis.detail_perjaldin', compact('perjaldin', 'bendaharaPenerimaans'));
    }

    public function store(Request $request, $spm_id)
    {
        $spm = \App\Models\DokumenSpm::with(['spp', 'npi'])->findOrFail($spm_id);

        $request->validate([
            'nomor_npi' => 'required|string|max:100',
            'tanggal_npi' => 'required|date',
            'bendahara_penerimaan_id' => 'required|exists:users,id',
        ]);

        DB::transaction(function () use ($request, $spm) {
            $npi = $spm->npi()->updateOrCreate(
                ['spm_id' => $spm->id],
                [
                    'nomor_npi' => $request->nomor_npi,
                    'tanggal_npi' => $request->tanggal_npi,
                    'bendahara_penerimaan_id' => $request->bendahara_penerimaan_id,
                    'status' => DokumenNpi::STATUS_SUBMITTED_BENPEN,
                ]
            );

            $this->logStatus(
                $npi,
                $npi->wasRecentlyCreated ? null : $npi->getOriginal('status'),
                DokumenNpi::STATUS_SUBMITTED_BENPEN,
                $npi->wasRecentlyCreated ? 'CREATE_AND_SUBMIT' : 'UPDATE_AND_RESUBMIT',
                'Draft NPI diajukan ke Bendahara Penerimaan.'
            );

            $this->notifyRoles(
                ['Bendahara Penerimaan'],
                'NPI Menunggu Verifikasi',
                "NPI {$npi->nomor_npi} menunggu verifikasi Bendahara Penerimaan.",
                route('verifikasi-bendahara-penerimaan.npi.index')
            );
        });

        return back()->with('success', 'NPI berhasil dibuat dan dikirim ke Bendahara Penerimaan.');
    }

    public function penerimaaIndex()
    {
        $npis = DokumenNpi::with(['spm.spp', 'bendaharaPenerimaan'])
            ->whereIn('status', [
                DokumenNpi::STATUS_SUBMITTED_BENPEN,
                DokumenNpi::STATUS_SUBMITTED_PPK,
                DokumenNpi::STATUS_SUBMITTED_KASUBAG,
                DokumenNpi::STATUS_APPROVED_KASUBAG,
            ])
            ->orderByRaw(
                "CASE WHEN status = ? THEN 1 ELSE 2 END",
                [DokumenNpi::STATUS_SUBMITTED_BENPEN]
            )
            ->latest()
            ->get();

        return view('verifikasi_bendahara_penerimaan.npi_index', [
            'npis' => $npis,
            'pageTitle' => 'Verifikasi NPI',
            'pageSubtitle' => 'Bendahara Penerimaan',
            'pendingCount' => $npis->where('status', DokumenNpi::STATUS_SUBMITTED_BENPEN)->count(),
            'approvedCount' => $npis->whereIn('status', [
                DokumenNpi::STATUS_SUBMITTED_PPK,
                DokumenNpi::STATUS_SUBMITTED_KASUBAG,
                DokumenNpi::STATUS_APPROVED_KASUBAG,
            ])->count(),
        ]);
    }

    public function approvePenerimaan($npi_id)
    {
        $npi = DokumenNpi::findOrFail($npi_id);
        $statusSebelumnya = $npi->status;

        $npi->update([
            'status' => DokumenNpi::STATUS_SUBMITTED_PPK,
        ]);

        $this->logStatus(
            $npi,
            $statusSebelumnya,
            DokumenNpi::STATUS_SUBMITTED_PPK,
            'APPROVE_BENDAHARA_PENERIMAAN',
            'NPI disetujui Bendahara Penerimaan dan diteruskan ke PPK.'
        );

        $this->notifyRoles(
            ['PPK'],
            'NPI Menunggu Persetujuan PPK',
            "NPI {$npi->nomor_npi} telah diverifikasi Bendahara Penerimaan dan menunggu PPK.",
            route('verifikasi-ppk.npi.index')
        );

        return back()->with('success', 'NPI diteruskan ke PPK.');
    }

    public function revisiPenerimaan(Request $request, $npi_id)
    {
        $request->validate(['catatan_revisi' => 'required|string|max:1000']);
        $npi = DokumenNpi::findOrFail($npi_id);
        $statusSebelumnya = $npi->status;

        $npi->update([
            'status' => DokumenNpi::STATUS_REJECTED_BENPEN,
        ]);

        $this->logStatus(
            $npi,
            $statusSebelumnya,
            DokumenNpi::STATUS_REJECTED_BENPEN,
            'REJECT_BENDAHARA_PENERIMAAN',
            $request->catatan_revisi
        );

        $this->notifyRoles(
            ['Bendahara Pengeluaran'],
            'NPI Dikembalikan Bendahara Penerimaan',
            "NPI {$npi->nomor_npi} perlu diperbaiki: {$request->catatan_revisi}",
            route('npis.index')
        );

        return back()->with('success', 'NPI dikembalikan ke Bendahara Pengeluaran.');
    }

    public function verifikasiIndex()
    {
        $npis = DokumenNpi::with(['spm.spp', 'bendaharaPenerimaan'])
            ->whereIn('status', [
                DokumenNpi::STATUS_SUBMITTED_PPK,
                DokumenNpi::STATUS_SUBMITTED_KASUBAG,
                DokumenNpi::STATUS_APPROVED_KASUBAG,
            ])
            ->orderByRaw(
                "CASE WHEN status = ? THEN 1 WHEN status = ? THEN 2 ELSE 3 END",
                [
                    DokumenNpi::STATUS_SUBMITTED_PPK,
                    DokumenNpi::STATUS_SUBMITTED_KASUBAG,
                ]
            )
            ->latest()
            ->get();

        return view('verifikasi_ppk.npi_index', [
            'npis' => $npis,
            'stage' => 'ppk',
            'pageTitle' => 'Verifikasi NPI',
            'pageSubtitle' => 'Pejabat Pembuat Komitmen',
            'pendingCount' => $npis->where('status', DokumenNpi::STATUS_SUBMITTED_PPK)->count(),
            'approvedCount' => $npis->whereIn('status', [
                DokumenNpi::STATUS_SUBMITTED_KASUBAG,
                DokumenNpi::STATUS_APPROVED_KASUBAG,
            ])->count(),
            'approveRouteName' => 'verifikasi-ppk.npi.approve',
            'rejectRouteName' => 'verifikasi-ppk.npi.revisi',
        ]);
    }

    public function approve($npi_id)
    {
        $npi = DokumenNpi::findOrFail($npi_id);
        $statusSebelumnya = $npi->status;

        $npi->update([
            'status' => DokumenNpi::STATUS_SUBMITTED_KASUBAG,
        ]);

        $this->logStatus(
            $npi,
            $statusSebelumnya,
            DokumenNpi::STATUS_SUBMITTED_KASUBAG,
            'APPROVE_PPK',
            'NPI disetujui PPK dan diteruskan ke Kasubbag.'
        );

        $this->notifyRoles(
            ['Kepala Subbagian Keuangan dan Tata Usaha'],
            'NPI Menunggu Verifikasi Kasubbag',
            "NPI {$npi->nomor_npi} telah disetujui PPK dan menunggu Kasubbag.",
            route('verifikasi-kasubag.npi.index')
        );

        return back()->with('success', 'NPI diteruskan ke Kasubbag.');
    }

    public function revisi(Request $request, $npi_id)
    {
        $request->validate(['catatan_revisi' => 'required|string|max:1000']);
        $npi = DokumenNpi::findOrFail($npi_id);
        $statusSebelumnya = $npi->status;

        $npi->update([
            'status' => DokumenNpi::STATUS_REJECTED_PPK,
        ]);

        $this->logStatus(
            $npi,
            $statusSebelumnya,
            DokumenNpi::STATUS_REJECTED_PPK,
            'REJECT_PPK',
            $request->catatan_revisi
        );

        $this->notifyRoles(
            ['Bendahara Pengeluaran'],
            'NPI Dikembalikan PPK',
            "NPI {$npi->nomor_npi} perlu diperbaiki: {$request->catatan_revisi}",
            route('npis.index')
        );

        return back()->with('success', 'NPI dikembalikan ke Bendahara Pengeluaran.');
    }

    public function kasubbagIndex()
    {
        $npis = DokumenNpi::with(['spm.spp', 'bendaharaPenerimaan'])
            ->whereIn('status', [
                DokumenNpi::STATUS_SUBMITTED_KASUBAG,
                DokumenNpi::STATUS_APPROVED_KASUBAG,
            ])
            ->orderByRaw(
                "CASE WHEN status = ? THEN 1 ELSE 2 END",
                [DokumenNpi::STATUS_SUBMITTED_KASUBAG]
            )
            ->latest()
            ->get();

        return view('verifikasi_ppk.npi_index', [
            'npis' => $npis,
            'stage' => 'kasubbag',
            'pageTitle' => 'Verifikasi NPI',
            'pageSubtitle' => 'Kepala Subbagian Keuangan dan Tata Usaha',
            'pendingCount' => $npis->where('status', DokumenNpi::STATUS_SUBMITTED_KASUBAG)->count(),
            'approvedCount' => $npis->where('status', DokumenNpi::STATUS_APPROVED_KASUBAG)->count(),
            'approveRouteName' => 'verifikasi-kasubag.npi.approve',
            'rejectRouteName' => 'verifikasi-kasubag.npi.revisi',
        ]);
    }

    public function approveKasubbag($npi_id)
    {
        $npi = DokumenNpi::findOrFail($npi_id);
        $statusSebelumnya = $npi->status;

        $npi->update([
            'status' => DokumenNpi::STATUS_APPROVED_KASUBAG,
        ]);

        $this->logStatus(
            $npi,
            $statusSebelumnya,
            DokumenNpi::STATUS_APPROVED_KASUBAG,
            'APPROVE_KASUBAG',
            'NPI disetujui Kasubbag dan siap dilanjutkan ke SP2D.'
        );

        $this->notifyRoles(
            ['Bendahara Pengeluaran'],
            'NPI Final Disetujui',
            "NPI {$npi->nomor_npi} telah final disetujui dan siap diproses ke SP2D.",
            route('sp2ds.index')
        );

        return back()->with('success', 'NPI disetujui Kasubbag.');
    }

    public function revisiKasubbag(Request $request, $npi_id)
    {
        $request->validate(['catatan_revisi' => 'required|string|max:1000']);
        $npi = DokumenNpi::findOrFail($npi_id);
        $statusSebelumnya = $npi->status;

        $npi->update([
            'status' => DokumenNpi::STATUS_REJECTED_KASUBAG,
        ]);

        $this->logStatus(
            $npi,
            $statusSebelumnya,
            DokumenNpi::STATUS_REJECTED_KASUBAG,
            'REJECT_KASUBAG',
            $request->catatan_revisi
        );

        $this->notifyRoles(
            ['Bendahara Pengeluaran'],
            'NPI Dikembalikan Kasubbag',
            "NPI {$npi->nomor_npi} perlu diperbaiki: {$request->catatan_revisi}",
            route('npis.index')
        );

        return back()->with('success', 'NPI dikembalikan oleh Kasubbag.');
    }

    public function cetakPdf($npi_id)
    {
        $npi = DokumenNpi::with(['spm.spp', 'bendaharaPenerimaan'])->findOrFail($npi_id);
        $spm = $npi->spm;
        $spp = $spm?->spp;

        if (! $npi->nomor_npi) {
            $npi->nomor_npi = 'NPI-BLU/APTP-' . date('Y') . '/DRAFT';
        }

        if (! $npi->tanggal_npi) {
            $npi->tanggal_npi = now()->toDateString();
        }

        $jumlahUang = (float) ($spp?->nominal_spp ?? 0);
        $terbilang = terbilang_rupiah($jumlahUang);

        $bendaharaPengeluaran = User::role('Bendahara Pengeluaran')->first();
        $bendaharaPenerimaan = $npi->bendaharaPenerimaan ?: User::role('Bendahara Penerimaan')->first();
        $ppk = User::role('PPK')->first();

        $penandatanganPengeluaran = $bendaharaPengeluaran->name ?? 'BENDAHARA PENGELUARAN';
        $nipPengeluaran = '-';
        $penandatanganPenerimaan = $bendaharaPenerimaan->name ?? 'BENDAHARA PENERIMAAN';
        $nipPenerimaan = '-';

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('npis.pdf', compact(
            'spp',
            'spm',
            'npi',
            'jumlahUang',
            'terbilang',
            'penandatanganPengeluaran',
            'nipPengeluaran',
            'penandatanganPenerimaan',
            'nipPenerimaan',
            'ppk'
        ));
        $pdf->setPaper('a4', 'portrait');

        return $pdf->stream('NPI-BLU-' . str_replace('/', '-', $npi->nomor_npi) . '.pdf');
    }

    private function logStatus(
        DokumenNpi $npi,
        ?string $statusSebelumnya,
        string $statusBaru,
        string $aksi,
        ?string $catatan = null
    ): void {
        $user = Auth::user();

        LogStatusDokumen::create([
            'dokumen_type' => DokumenNpi::class,
            'dokumen_id' => $npi->id,
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
