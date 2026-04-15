<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Tagihan;
use App\Models\LogStatusDokumen;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class PerjaldinVerifikasiController extends Controller
{
    public function ppkIndex()
    {
        $tagihans = Tagihan::where('tipe_tagihan', 'PERJALDIN')
            ->whereNotIn('status', ['DRAFT'])
            ->with(['detailPerjaldin.pegawai', 'logs' => fn($q) => $q->latest()])
            ->latest()
            ->get();
        return view('verifikasi_ppk.index', compact('tagihans'));
    }

    public function kasubagIndex()
    {
        $tagihans = Tagihan::where('tipe_tagihan', 'PERJALDIN')
            ->whereNotIn('status', ['DRAFT'])
            ->with(['detailPerjaldin.pegawai', 'logs' => fn($q) => $q->latest()])
            ->latest()
            ->get();
        return view('verifikasi_kasubag.index', compact('tagihans'));
    }

    public function approve($id)
    {
        $tagihan = Tagihan::where('tipe_tagihan', 'PERJALDIN')->findOrFail($id);
        $user = Auth::user();

        if (!in_array($tagihan->status, ['PENDING_PPK'])) {
            return redirect()->back()->withErrors(['error' => 'Tagihan ini tidak dalam status menunggu persetujuan PPK.']);
        }

        $statusLama = $tagihan->status;
        // Setelah PPK setuju → teruskan ke Bendahara Pengeluaran
        $tagihan->update(['status' => 'PENDING_BENDAHARA']);

        LogStatusDokumen::create([
            'dokumen_type'      => Tagihan::class,
            'dokumen_id'        => $tagihan->id,
            'user_id'           => $user->id,
            'role_saat_itu'     => $user->getRoleNames()->first() ?? 'PPK',
            'status_sebelumnya' => $statusLama,
            'status_baru'       => 'PENDING_BENDAHARA',
            'aksi'              => 'APPROVE',
            'catatan'           => 'Disetujui oleh PPK. Diteruskan ke Bendahara Pengeluaran.',
            'ip_address'        => request()->ip(),
        ]);

        try {
            $operators = User::role('Operator Perjaldin')->get();
            \Illuminate\Support\Facades\Notification::send($operators, new \App\Notifications\WorkflowNotification([
                'title'   => 'Perjaldin Disetujui PPK',
                'message' => "Tagihan '{$tagihan->deskripsi}' telah disetujui PPK dan diteruskan ke Bendahara Pengeluaran.",
                'url'     => route('perjaldins.index'),
                'icon'    => 'check_circle',
                'color'   => 'success'
            ]));
        } catch (\Exception $e) {}

        return redirect()->back()->with('success', 'Persetujuan PPK berhasil disimpan. Dokumen diteruskan ke Bendahara Pengeluaran.');
    }

    public function revisi(Request $request, $id)
    {
        $request->validate([
            'catatan_revisi' => 'required|string',
        ]);

        $tagihan = Tagihan::where('tipe_tagihan', 'PERJALDIN')->findOrFail($id);
        $user = Auth::user();

        $statusLama = $tagihan->status;
        // PPK kembalikan → REVISI_PPK agar operator bisa edit dan ajukan ulang
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
            $operatorPerjaldin = User::role('Operator Perjaldin')->get();
            \Illuminate\Support\Facades\Notification::send($operatorPerjaldin, new \App\Notifications\WorkflowNotification([
                'title'   => 'Revisi dari PPK',
                'message' => "Tagihan Perjaldin dikembalikan. Catatan: {$request->catatan_revisi}",
                'url'     => route('perjaldins.index'),
                'icon'    => 'error',
                'color'   => 'danger'
            ]));
        } catch (\Exception $e) {}

        return redirect()->back()->with('success', 'Data berhasil dikembalikan untuk revisi. Operator Perjaldin telah dinotifikasi.');
    }

    // ═══ VERIFIKASI BENDAHARA PENGELUARAN ═══

    public function bendaharaIndex()
    {
        $tagihans = Tagihan::where('tipe_tagihan', 'PERJALDIN')
            ->whereIn('status', ['PENDING_BENDAHARA', 'REVISI_BENDAHARA', 'DISETUJUI_PERJALDIN'])
            ->with(['detailPerjaldin.pegawai', 'logs' => fn($q) => $q->latest()])
            ->latest()
            ->get();
        return view('verifikasi_bendahara.index', compact('tagihans'));
    }

    public function bendaharaApprove($id)
    {
        $tagihan = Tagihan::where('tipe_tagihan', 'PERJALDIN')->findOrFail($id);
        $user = Auth::user();

        if ($tagihan->status !== 'PENDING_BENDAHARA') {
            return redirect()->back()->withErrors(['error' => 'Tagihan ini tidak dalam status menunggu verifikasi Bendahara Pengeluaran.']);
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
            'catatan'           => 'Disetujui oleh Bendahara Pengeluaran. Dokumen Perjaldin selesai diverifikasi.',
            'ip_address'        => request()->ip(),
        ]);

        try {
            $operators = User::role('Operator Perjaldin')->get();
            \Illuminate\Support\Facades\Notification::send($operators, new \App\Notifications\WorkflowNotification([
                'title'   => 'Perjaldin Disetujui Penuh',
                'message' => "Tagihan '{$tagihan->deskripsi}' telah disetujui oleh Bendahara Pengeluaran.",
                'url'     => route('perjaldins.index'),
                'icon'    => 'check_circle',
                'color'   => 'success'
            ]));
        } catch (\Exception $e) {}

        return redirect()->back()->with('success', 'Dokumen Perjaldin telah disetujui penuh oleh Bendahara Pengeluaran.');
    }

    public function bendaharaRevisi(Request $request, $id)
    {
        $request->validate(['catatan_revisi' => 'required|string']);

        $tagihan = Tagihan::where('tipe_tagihan', 'PERJALDIN')->findOrFail($id);
        $user = Auth::user();

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
            $operators = User::role('Operator Perjaldin')->get();
            \Illuminate\Support\Facades\Notification::send($operators, new \App\Notifications\WorkflowNotification([
                'title'   => 'Revisi dari Bendahara Pengeluaran',
                'message' => "Tagihan Perjaldin dikembalikan oleh Bendahara. Catatan: {$request->catatan_revisi}",
                'url'     => route('perjaldins.index'),
                'icon'    => 'error',
                'color'   => 'danger'
            ]));
        } catch (\Exception $e) {}

        return redirect()->back()->with('success', 'Dokumen dikembalikan untuk revisi oleh Bendahara Pengeluaran.');
    }
}
