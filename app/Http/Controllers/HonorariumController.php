<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Tagihan;
use App\Models\DetailHonorarium;
use App\Models\MasterPersonelEksternal;
use App\Models\MasterDipa;
use App\Models\LogStatusDokumen;

class HonorariumController extends Controller
{
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
        $dipas = MasterDipa::orderByDesc('tahun_anggaran')->get();
        $nextNumber = $this->generateNextNumber();

        return view('honorarium.create', compact('dipas', 'nextNumber'));
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
            'master_dipa_id' => 'required|exists:master_dipas,id',
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
                'master_dipa_id' => $request->master_dipa_id,
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
                'status_lama' => '-',
                'status_baru' => $status,
                'diubah_oleh' => auth()->id(),
                'catatan' => $status === 'PENDING_PPK'
                    ? 'Tagihan Honorarium langsung diajukan ke PPK.'
                    : 'Tagihan Honorarium dibuat sebagai Draft.',
            ]);

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

        $dipas = MasterDipa::orderByDesc('tahun_anggaran')->get();

        return view('honorarium.edit', compact('tagihan', 'dipas'));
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
            'master_dipa_id' => 'required|exists:master_dipas,id',
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

            $totalBruto = collect($request->items)->sum(fn($i) => (float)($i['nilai_honor'] ?? 0));
            $totalPph = collect($request->items)->sum(fn($i) => (float)($i['pph'] ?? 0));
            $totalNetto = $totalBruto - $totalPph;

            $statusLama = $tagihan->status;
            $statusBaru = $request->submit_type === 'submit_ppk' ? 'PENDING_PPK' : 'DRAFT';

            $tagihan->update([
                'master_dipa_id' => $request->master_dipa_id,
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
                'status_lama' => $statusLama,
                'status_baru' => $statusBaru,
                'diubah_oleh' => auth()->id(),
                'catatan' => 'Data honorarium diperbarui oleh PPABP.',
            ]);

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

    private function generateNextNumber(): string
    {
        $year = now()->format('Y');
        $count = Tagihan::whereYear('created_at', $year)
            ->where('tipe_tagihan', 'HONORARIUM')
            ->count();
        return 'HON-' . $year . '-' . str_pad($count + 1, 4, '0', STR_PAD_LEFT);
    }
}