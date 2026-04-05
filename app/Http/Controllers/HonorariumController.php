<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\Tagihan;
use App\Models\DetailHonorarium;
use App\Models\MasterPersonelEksternal;
use App\Models\LogStatusDokumen;
use App\Models\User;
use Illuminate\Support\Facades\Notification;
use App\Support\DipaBudgetOptionService;
use App\Notifications\WorkflowNotification;

class HonorariumController extends Controller
{
    public function pendingPpk()
    {
        $tagihans = Tagihan::where('tipe_tagihan', 'HONORARIUM')
            ->where('status', 'PENDING_PPK')
            ->with(['detailHonorarium', 'logs' => fn($q) => $q->latest()])
            ->latest()
            ->get();

        return view('honorarium.ppk-pending', compact('tagihans'));
    }

    public function verifyPpk($id)
    {
        $tagihan = Tagihan::where('tipe_tagihan', 'HONORARIUM')
            ->with(['detailHonorarium', 'logs' => fn($q) => $q->latest()])
            ->findOrFail($id);

        if (!in_array($tagihan->status, ['PENDING_PPK', 'DISETUJUI_PPK', 'DITOLAK_PPK', 'PROSES_SPP', 'SPP_TERBIT'])) {
            return redirect()->route('honorarium.ppk.pending')
                ->with('error', 'Tagihan honorarium ini tidak tersedia dalam antrean verifikasi PPK.');
        }

        $dokumenPendukung = collect();

        return view('honorarium.ppk_verify', compact('tagihan', 'dokumenPendukung'));
    }

    public function index()
    {
        $tagihans = Tagihan::where('tipe_tagihan', 'HONORARIUM')
            ->with(['detailHonorarium', 'logs' => fn($q) => $q->latest()->limit(1)])
            ->latest()
            ->get();

        return view('honorarium.index', compact('tagihans'));
    }

    public function create()
    {
        $budgetGroups = DipaBudgetOptionService::groupedOptions();
        $nextNumber = $this->generateNextNumber();

        return view('honorarium.create', compact('budgetGroups', 'nextNumber'));
    }

    public function store(Request $request)
    {
        // Strip commas from currency inputs
        $input = $request->all();
        if (isset($input['items']) && is_array($input['items'])) {
            foreach ($input['items'] as &$item) {
                foreach (['nilai_honor', 'pph'] as $field) {
                    if (isset($item[$field])) $item[$field] = str_replace(',', '', $item[$field]);
                }
            }
            $request->merge($input);
        }

        $request->validate([
            'deskripsi' => 'required|string|max:255',
            'dipa_revision_item_id' => 'required|exists:dipa_revision_items,id',
            'submit_type' => 'required|in:draft,submit_ppk',
            'items' => 'required|array|min:1',
            'items.*.nama_personel' => 'required|string|max:255',
            'items.*.nrp_nip' => 'nullable|string|max:100',
            'items.*.pangkat_korp' => 'nullable|string|max:100',
            'items.*.jabatan' => 'nullable|string|max:100',
            'items.*.nilai_honor' => 'required|numeric|min:0',
            'items.*.pph' => 'nullable|numeric|min:0',
            'items.*.rekening' => 'nullable|string|max:100',
            'items.*.jenis_bank' => 'nullable|string|max:50',
            'items.*.nama_rekening' => 'nullable|string|max:100',
        ]);

        try {
            DB::beginTransaction();
            $selectedItem = DipaBudgetOptionService::resolveActiveItem($request->dipa_revision_item_id);

            $totalBruto = collect($request->items)->sum(fn($i) => (float)($i['nilai_honor'] ?? 0));
            $totalPph = collect($request->items)->sum(fn($i) => (float)($i['pph'] ?? 0));
            $totalNetto = $totalBruto - $totalPph;

            $tahun = date('Y');
            $urut = Tagihan::whereYear('created_at', $tahun)->where('tipe_tagihan', 'HONORARIUM')->count() + 1;
            $nomorTagihan = 'HON-' . $tahun . '-' . str_pad($urut, 4, '0', STR_PAD_LEFT);

            $status = $request->submit_type === 'submit_ppk' ? 'PENDING_PPK' : 'DRAFT';

            $tagihan = Tagihan::create([
                'nomor_tagihan' => $nomorTagihan,
                'tipe_tagihan' => 'HONORARIUM',
                'master_dipa_id' => $selectedItem->dipaRevision->master_dipa_id,
                'dipa_revision_item_id' => $selectedItem->id,
                'deskripsi' => $request->deskripsi,
                'total_bruto' => $totalBruto,
                'total_potongan' => $totalPph,
                'total_netto' => $totalNetto,
                'status' => $status,
                'created_by' => auth()->id(),
            ]);

            foreach ($request->items as $itemData) {
                DetailHonorarium::create([
                    'tagihan_id' => $tagihan->id,
                    'nama_personel' => $itemData['nama_personel'],
                    'nrp_nip' => $itemData['nrp_nip'] ?? null,
                    'pangkat_korp' => $itemData['pangkat_korp'] ?? null,
                    'jabatan' => $itemData['jabatan'] ?? null,
                    'nilai_honor' => $itemData['nilai_honor'] ?? 0,
                    'pph' => $itemData['pph'] ?? 0,
                    'rekening' => $itemData['rekening'] ?? '',
                    'jenis_bank' => $itemData['jenis_bank'] ?? '',
                    'nama_rekening' => $itemData['nama_rekening'] ?? '',
                ]);
            }

            LogStatusDokumen::create([
                'dokumen_type' => Tagihan::class,
                'dokumen_id' => $tagihan->id,
                'user_id' => Auth::id(),
                'role_saat_itu' => Auth::user()->getRoleNames()->first() ?? '-',
                'status_sebelumnya' => null,
                'status_baru' => $status,
                'aksi' => $status === 'PENDING_PPK' ? 'SUBMIT' : 'SIMPAN_DRAFT',
                'catatan' => $status === 'PENDING_PPK'
                    ? 'Tagihan Honorarium langsung diajukan ke PPK.'
                    : 'Tagihan Honorarium dibuat sebagai Draft.',
                'ip_address' => $request->ip(),
            ]);

            if ($status === 'PENDING_PPK') {
                $this->notifyRoles(
                    ['PPK'],
                    'Tagihan Honorarium Baru',
                    "Tagihan {$tagihan->nomor_tagihan} baru diajukan dan menunggu verifikasi Anda.",
                    route('ppk.tagihan.honorarium.verify', $tagihan->id)
                );
            }

            DB::commit();
            return redirect()->route('honorarium.index')->with('success', 'Data honorarium berhasil disimpan.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Gagal menyimpan: ' . $e->getMessage()]);
        }
    }

    public function show($id)
    {
        $tagihan = Tagihan::where('tipe_tagihan', 'HONORARIUM')
            ->with(['detailHonorarium', 'logs' => fn($q) => $q->latest()])
            ->findOrFail($id);

        return view('honorarium.show', compact('tagihan'));
    }

    public function edit($id)
    {
        $tagihan = Tagihan::where('tipe_tagihan', 'HONORARIUM')
            ->with('detailHonorarium')
            ->findOrFail($id);

        if (!in_array($tagihan->status, ['DRAFT', 'DITOLAK_PPK'])) {
            return redirect()->route('honorarium.index')
                ->with('error', 'Data dengan status ' . $tagihan->status . ' tidak bisa diedit.');
        }

        $budgetGroups = DipaBudgetOptionService::groupedOptions();

        return view('honorarium.edit', compact('tagihan', 'budgetGroups'));
    }

    public function update(Request $request, $id)
    {
        $tagihan = Tagihan::where('tipe_tagihan', 'HONORARIUM')->findOrFail($id);

        if (!in_array($tagihan->status, ['DRAFT', 'DITOLAK_PPK'])) {
            return redirect()->route('honorarium.index')
                ->with('error', 'Data dengan status ' . $tagihan->status . ' tidak bisa diedit.');
        }

        $input = $request->all();
        if (isset($input['items']) && is_array($input['items'])) {
            foreach ($input['items'] as &$item) {
                foreach (['nilai_honor', 'pph'] as $field) {
                    if (isset($item[$field])) $item[$field] = str_replace(',', '', $item[$field]);
                }
            }
            $request->merge($input);
        }

        $request->validate([
            'deskripsi' => 'required|string|max:255',
            'dipa_revision_item_id' => 'required|exists:dipa_revision_items,id',
            'submit_type' => 'required|in:draft,submit_ppk',
            'items' => 'required|array|min:1',
            'items.*.nama_personel' => 'required|string|max:255',
            'items.*.nrp_nip' => 'nullable|string|max:100',
            'items.*.pangkat_korp' => 'nullable|string|max:100',
            'items.*.jabatan' => 'nullable|string|max:100',
            'items.*.nilai_honor' => 'required|numeric|min:0',
            'items.*.pph' => 'nullable|numeric|min:0',
            'items.*.rekening' => 'nullable|string|max:100',
            'items.*.jenis_bank' => 'nullable|string|max:50',
            'items.*.nama_rekening' => 'nullable|string|max:100',
        ]);

        try {
            DB::beginTransaction();
            $selectedItem = DipaBudgetOptionService::resolveActiveItem($request->dipa_revision_item_id);

            $totalBruto = collect($request->items)->sum(fn($i) => (float)($i['nilai_honor'] ?? 0));
            $totalPph = collect($request->items)->sum(fn($i) => (float)($i['pph'] ?? 0));
            $totalNetto = $totalBruto - $totalPph;

            $statusLama = $tagihan->status;
            $statusBaru = $request->submit_type === 'submit_ppk' ? 'PENDING_PPK' : 'DRAFT';

            $tagihan->update([
                'master_dipa_id' => $selectedItem->dipaRevision->master_dipa_id,
                'dipa_revision_item_id' => $selectedItem->id,
                'deskripsi' => $request->deskripsi,
                'total_bruto' => $totalBruto,
                'total_potongan' => $totalPph,
                'total_netto' => $totalNetto,
                'status' => $statusBaru,
            ]);

            // Delete old, re-insert
            DetailHonorarium::where('tagihan_id', $tagihan->id)->delete();

            foreach ($request->items as $itemData) {
                DetailHonorarium::create([
                    'tagihan_id' => $tagihan->id,
                    'nama_personel' => $itemData['nama_personel'],
                    'nrp_nip' => $itemData['nrp_nip'] ?? null,
                    'pangkat_korp' => $itemData['pangkat_korp'] ?? null,
                    'jabatan' => $itemData['jabatan'] ?? null,
                    'nilai_honor' => $itemData['nilai_honor'] ?? 0,
                    'pph' => $itemData['pph'] ?? 0,
                    'rekening' => $itemData['rekening'] ?? '',
                    'jenis_bank' => $itemData['jenis_bank'] ?? '',
                    'nama_rekening' => $itemData['nama_rekening'] ?? '',
                ]);
            }

            LogStatusDokumen::create([
                'dokumen_type' => Tagihan::class,
                'dokumen_id' => $tagihan->id,
                'user_id' => Auth::id(),
                'role_saat_itu' => Auth::user()->getRoleNames()->first() ?? '-',
                'status_sebelumnya' => $statusLama,
                'status_baru' => $statusBaru,
                'aksi' => $statusBaru === 'PENDING_PPK' ? 'UPDATE_SUBMIT' : 'UPDATE_DRAFT',
                'catatan' => 'Data honorarium diperbarui oleh PPABP.',
                'ip_address' => $request->ip(),
            ]);

            if ($statusBaru === 'PENDING_PPK') {
                $this->notifyRoles(
                    ['PPK'],
                    'Tagihan Honorarium Masuk Verifikasi',
                    "Tagihan {$tagihan->nomor_tagihan} diajukan ulang dan menunggu verifikasi Anda.",
                    route('ppk.tagihan.honorarium.verify', $tagihan->id)
                );
            }

            DB::commit();
            return redirect()->route('honorarium.index')->with('success', 'Data honorarium berhasil diupdate.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Gagal memperbarui: ' . $e->getMessage()]);
        }
    }

    public function destroy($id)
    {
        $tagihan = Tagihan::where('tipe_tagihan', 'HONORARIUM')->findOrFail($id);

        if (!in_array($tagihan->status, ['DRAFT', 'DITOLAK_PPK'])) {
            return redirect()->route('honorarium.index')
                ->with('error', 'Data dengan status ' . $tagihan->status . ' tidak bisa dihapus.');
        }

        DetailHonorarium::where('tagihan_id', $tagihan->id)->delete();
        $tagihan->logs()->delete();
        $tagihan->delete();

        return redirect()->route('honorarium.index')->with('success', 'Data honorarium berhasil dihapus.');
    }

    public function approvePpk($id)
    {
        $tagihan = Tagihan::where('tipe_tagihan', 'HONORARIUM')->findOrFail($id);
        $user = Auth::user();

        if ($tagihan->status !== 'PENDING_PPK') {
            return redirect()->back()->withErrors(['error' => 'Tagihan honorarium ini tidak sedang menunggu persetujuan PPK.']);
        }

        DB::beginTransaction();

        try {
            $statusLama = $tagihan->status;

            $tagihan->update([
                'status' => 'DISETUJUI_PPK',
            ]);

            LogStatusDokumen::create([
                'dokumen_type' => Tagihan::class,
                'dokumen_id' => $tagihan->id,
                'user_id' => $user->id,
                'role_saat_itu' => $user->getRoleNames()->first() ?? 'PPK',
                'status_sebelumnya' => $statusLama,
                'status_baru' => 'DISETUJUI_PPK',
                'aksi' => 'APPROVE',
                'catatan' => 'Tagihan honorarium disetujui oleh PPK dan diteruskan ke proses SPP.',
                'ip_address' => request()->ip(),
            ]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withErrors(['error' => 'Gagal menyetujui tagihan honorarium: ' . $e->getMessage()]);
        }

        try {
            $operators = User::role('PPABP')->get();
            Notification::send($operators, new \App\Notifications\WorkflowNotification([
                'title' => 'Tagihan Honorarium Disetujui PPK',
                'message' => "Tagihan '{$tagihan->nomor_tagihan}' telah disetujui PPK dan siap diproses ke SPP.",
                'url' => route('honorarium.index'),
                'icon' => 'check_circle',
                'color' => 'success',
            ]));
        } catch (\Exception $e) {
            // Kegagalan notifikasi tidak menghentikan proses utama.
        }

        return redirect()->route('honorarium.ppk.pending')->with('success', 'Tagihan honorarium berhasil disetujui.');
    }

    public function rejectPpk(Request $request, $id)
    {
        $request->validate([
            'catatan_revisi' => 'required|string',
        ]);

        $tagihan = Tagihan::where('tipe_tagihan', 'HONORARIUM')->findOrFail($id);
        $user = Auth::user();

        if ($tagihan->status !== 'PENDING_PPK') {
            return redirect()->back()->withErrors(['error' => 'Tagihan honorarium ini tidak sedang menunggu persetujuan PPK.']);
        }

        DB::beginTransaction();

        try {
            $statusLama = $tagihan->status;

            $tagihan->update([
                'status' => 'DITOLAK_PPK',
            ]);

            LogStatusDokumen::create([
                'dokumen_type' => Tagihan::class,
                'dokumen_id' => $tagihan->id,
                'user_id' => $user->id,
                'role_saat_itu' => $user->getRoleNames()->first() ?? 'PPK',
                'status_sebelumnya' => $statusLama,
                'status_baru' => 'DITOLAK_PPK',
                'aksi' => 'REJECT',
                'catatan' => $request->catatan_revisi,
                'ip_address' => request()->ip(),
            ]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withErrors(['error' => 'Gagal mengembalikan tagihan honorarium: ' . $e->getMessage()]);
        }

        try {
            $operators = User::role('PPABP')->get();
            Notification::send($operators, new \App\Notifications\WorkflowNotification([
                'title' => 'Revisi Tagihan Honorarium dari PPK',
                'message' => "Tagihan '{$tagihan->nomor_tagihan}' dikembalikan untuk revisi. Catatan: {$request->catatan_revisi}",
                'url' => route('honorarium.index'),
                'icon' => 'error',
                'color' => 'danger',
            ]));
        } catch (\Exception $e) {
            // Kegagalan notifikasi tidak menghentikan proses utama.
        }

        return redirect()->route('honorarium.ppk.pending')->with('success', 'Tagihan honorarium berhasil dikembalikan untuk revisi.');
    }

    private function generateNextNumber(): string
    {
        $year = now()->format('Y');
        $count = Tagihan::whereYear('created_at', $year)
            ->where('tipe_tagihan', 'HONORARIUM')
            ->count();
        return 'HON-' . $year . '-' . str_pad($count + 1, 4, '0', STR_PAD_LEFT);
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

