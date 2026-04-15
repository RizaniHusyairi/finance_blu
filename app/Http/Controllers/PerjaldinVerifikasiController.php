<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Tagihan;
use App\Models\LogStatusDokumen;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class PerjaldinVerifikasiController extends Controller
{
    // ═══════════════════════════════════════════
    // SHARED HELPERS
    // ═══════════════════════════════════════════

    private function buildIndex(array $pendingStatuses, array $revisiStatuses, array $selesaiStatuses, string $view)
    {
        $search  = request('search');
        $periode = request('periode');

        $allStatuses = array_merge($pendingStatuses, $revisiStatuses, $selesaiStatuses);

        $base = Tagihan::where('tipe_tagihan', 'PERJALDIN')
            ->whereIn('status', $allStatuses)
            ->with(['detailPerjaldin.pegawai', 'logs' => fn($q) => $q->latest()])
            ->latest();

        if ($search) {
            $base->where(fn($q) => $q
                ->where('nomor_tagihan', 'like', "%{$search}%")
                ->orWhere('deskripsi', 'like', "%{$search}%")
            );
        }
        if ($periode) {
            $parts = explode('-', $periode);
            $yr = $parts[0] ?? null;
            $mo = isset($parts[1]) ? (int)$parts[1] : null;
            if ($yr && $mo) {
                $base->where('periode_tahun', $yr)->where('periode_bulan', $mo);
            }
        }

        $tagihans        = $base->get();
        $tagihansPerlu   = $tagihans->whereIn('status', $pendingStatuses)->values();
        $tagihansRiwayat = $tagihans->whereIn('status', array_merge($revisiStatuses, $selesaiStatuses))->values();

        return view($view, compact(
            'tagihans', 'tagihansPerlu', 'tagihansRiwayat',
            'pendingStatuses', 'revisiStatuses', 'selesaiStatuses'
        ));
    }

    private function buildShow(int $id, string $userRole, string $approveRoute, string $revisiRoute, string $indexRoute)
    {
        $tagihan = Tagihan::where('tipe_tagihan', 'PERJALDIN')
            ->with([
                'detailPerjaldin.pegawai',
                'detailPerjaldin.provinsi',
                'logs.user',
            ])
            ->findOrFail($id);

        return view('verifikasi_perjaldin.show', compact(
            'tagihan', 'userRole', 'approveRoute', 'revisiRoute', 'indexRoute'
        ));
    }

    // ═══════════════════════════════════════════
    // PPK
    // ═══════════════════════════════════════════

    public function ppkIndex()
    {
        return $this->buildIndex(
            pendingStatuses: ['PENDING_PPK'],
            revisiStatuses:  ['REVISI_PPK', 'DITOLAK_PPK'],
            selesaiStatuses: ['PENDING_BENDAHARA', 'REVISI_BENDAHARA', 'DISETUJUI_PERJALDIN'],
            view: 'verifikasi_perjaldin.index'
        )->with([
            'userRole'    => 'PPK',
            'detailRoute' => 'verifikasi-ppk.perjaldin.show',
        ]);
    }

    public function ppkShow(int $id)
    {
        return $this->buildShow(
            id:           $id,
            userRole:     'PPK',
            approveRoute: route('verifikasi-ppk.perjaldin.approve', $id),
            revisiRoute:  route('verifikasi-ppk.perjaldin.revisi', $id),
            indexRoute:   'verifikasi-ppk.perjaldin.index',
        );
    }

    public function approve(Request $request, $id)
    {
        $tagihan = Tagihan::where('tipe_tagihan', 'PERJALDIN')->findOrFail($id);
        $user    = Auth::user();

        if ($tagihan->status !== 'PENDING_PPK') {
            return redirect()->back()->withErrors(['error' => 'Dokumen tidak dalam status menunggu PPK.']);
        }

        $statusLama = $tagihan->status;
        $tagihan->update(['status' => 'PENDING_BENDAHARA']);

        LogStatusDokumen::create([
            'dokumen_type'      => Tagihan::class,
            'dokumen_id'        => $tagihan->id,
            'user_id'           => $user->id,
            'role_saat_itu'     => $user->getRoleNames()->first() ?? 'PPK',
            'status_sebelumnya' => $statusLama,
            'status_baru'       => 'PENDING_BENDAHARA',
            'aksi'              => 'APPROVE',
            'catatan'           => $request->catatan ?: 'Disetujui PPK. Diteruskan ke Bendahara Pengeluaran.',
            'ip_address'        => request()->ip(),
        ]);

        try {
            $ops = User::role('Operator Perjaldin')->get();
            \Illuminate\Support\Facades\Notification::send($ops, new \App\Notifications\WorkflowNotification([
                'title'   => 'Perjaldin Disetujui PPK',
                'message' => "Tagihan '{$tagihan->deskripsi}' disetujui PPK, menunggu Bendahara.",
                'url'     => route('perjaldins.index'),
                'icon'    => 'check_circle', 'color' => 'success',
            ]));
        } catch (\Exception $e) {}

        return redirect()->route('verifikasi-ppk.perjaldin.index')
            ->with('success', 'Dokumen disetujui PPK. Diteruskan ke Bendahara Pengeluaran.');
    }

    public function revisi(Request $request, $id)
    {
        $request->validate(['catatan_revisi' => 'required|string']);

        $tagihan = Tagihan::where('tipe_tagihan', 'PERJALDIN')->findOrFail($id);
        $user    = Auth::user();

        $statusLama = $tagihan->status;
        $tagihan->update(['status' => 'REVISI_PPK']);

        LogStatusDokumen::create([
            'dokumen_type'      => Tagihan::class,
            'dokumen_id'        => $tagihan->id,
            'user_id'           => $user->id,
            'role_saat_itu'     => $user->getRoleNames()->first() ?? 'PPK',
            'status_sebelumnya' => $statusLama,
            'status_baru'       => 'REVISI_PPK',
            'aksi'              => 'REVISION',
            'catatan'           => $request->catatan_revisi,
            'ip_address'        => request()->ip(),
        ]);

        try {
            $ops = User::role('Operator Perjaldin')->get();
            \Illuminate\Support\Facades\Notification::send($ops, new \App\Notifications\WorkflowNotification([
                'title'   => 'Revisi dari PPK',
                'message' => "Perjaldin dikembalikan PPK. Catatan: {$request->catatan_revisi}",
                'url'     => route('perjaldins.index'),
                'icon'    => 'error', 'color' => 'danger',
            ]));
        } catch (\Exception $e) {}

        return redirect()->route('verifikasi-ppk.perjaldin.index')
            ->with('success', 'Dokumen dikembalikan untuk revisi. Operator telah dinotifikasi.');
    }

    // ═══════════════════════════════════════════
    // BENDAHARA PENGELUARAN
    // ═══════════════════════════════════════════

    public function bendaharaIndex()
    {
        return $this->buildIndex(
            pendingStatuses: ['PENDING_BENDAHARA'],
            revisiStatuses:  ['REVISI_BENDAHARA'],
            selesaiStatuses: ['DISETUJUI_PERJALDIN'],
            view: 'verifikasi_perjaldin.index'
        )->with([
            'userRole'    => 'Bendahara Pengeluaran',
            'detailRoute' => 'verifikasi-bendahara.perjaldin.show',
        ]);
    }

    public function bendaharaShow(int $id)
    {
        return $this->buildShow(
            id:           $id,
            userRole:     'Bendahara Pengeluaran',
            approveRoute: route('verifikasi-bendahara.perjaldin.approve', $id),
            revisiRoute:  route('verifikasi-bendahara.perjaldin.revisi', $id),
            indexRoute:   'verifikasi-bendahara.perjaldin.index',
        );
    }

    public function bendaharaApprove(Request $request, $id)
    {
        $tagihan = Tagihan::where('tipe_tagihan', 'PERJALDIN')->findOrFail($id);
        $user    = Auth::user();

        if ($tagihan->status !== 'PENDING_BENDAHARA') {
            return redirect()->back()->withErrors(['error' => 'Dokumen tidak dalam status menunggu Bendahara Pengeluaran.']);
        }

        $statusLama = $tagihan->status;
        $tagihan->update(['status' => 'DISETUJUI_PERJALDIN']);

        LogStatusDokumen::create([
            'dokumen_type'      => Tagihan::class,
            'dokumen_id'        => $tagihan->id,
            'user_id'           => $user->id,
            'role_saat_itu'     => $user->getRoleNames()->first() ?? 'Bendahara Pengeluaran',
            'status_sebelumnya' => $statusLama,
            'status_baru'       => 'DISETUJUI_PERJALDIN',
            'aksi'              => 'APPROVE',
            'catatan'           => $request->catatan ?: 'Disetujui Bendahara Pengeluaran. Verifikasi selesai.',
            'ip_address'        => request()->ip(),
        ]);

        try {
            $ops = User::role('Operator Perjaldin')->get();
            \Illuminate\Support\Facades\Notification::send($ops, new \App\Notifications\WorkflowNotification([
                'title'   => 'Perjaldin Disetujui Penuh',
                'message' => "Tagihan '{$tagihan->deskripsi}' disetujui Bendahara Pengeluaran.",
                'url'     => route('perjaldins.index'),
                'icon'    => 'check_circle', 'color' => 'success',
            ]));
        } catch (\Exception $e) {}

        return redirect()->route('verifikasi-bendahara.perjaldin.index')
            ->with('success', 'Dokumen Perjaldin telah disetujui penuh.');
    }

    public function bendaharaRevisi(Request $request, $id)
    {
        $request->validate(['catatan_revisi' => 'required|string']);

        $tagihan = Tagihan::where('tipe_tagihan', 'PERJALDIN')->findOrFail($id);
        $user    = Auth::user();

        $statusLama = $tagihan->status;
        $tagihan->update(['status' => 'REVISI_BENDAHARA']);

        LogStatusDokumen::create([
            'dokumen_type'      => Tagihan::class,
            'dokumen_id'        => $tagihan->id,
            'user_id'           => $user->id,
            'role_saat_itu'     => $user->getRoleNames()->first() ?? 'Bendahara Pengeluaran',
            'status_sebelumnya' => $statusLama,
            'status_baru'       => 'REVISI_BENDAHARA',
            'aksi'              => 'REVISION',
            'catatan'           => $request->catatan_revisi,
            'ip_address'        => request()->ip(),
        ]);

        try {
            $ops = User::role('Operator Perjaldin')->get();
            \Illuminate\Support\Facades\Notification::send($ops, new \App\Notifications\WorkflowNotification([
                'title'   => 'Revisi dari Bendahara',
                'message' => "Perjaldin dikembalikan Bendahara. Catatan: {$request->catatan_revisi}",
                'url'     => route('perjaldins.index'),
                'icon'    => 'error', 'color' => 'danger',
            ]));
        } catch (\Exception $e) {}

        return redirect()->route('verifikasi-bendahara.perjaldin.index')
            ->with('success', 'Dokumen dikembalikan untuk revisi oleh Bendahara Pengeluaran.');
    }

    // ═══════════════════════════════════════════
    // LEGACY — Kasubag (dipertahankan)
    // ═══════════════════════════════════════════

    public function kasubagIndex()
    {
        $tagihans = Tagihan::where('tipe_tagihan', 'PERJALDIN')
            ->whereNotIn('status', ['DRAFT'])
            ->with(['detailPerjaldin.pegawai', 'logs' => fn($q) => $q->latest()])
            ->latest()->get();
        return view('verifikasi_kasubag.index', compact('tagihans'));
    }
}
