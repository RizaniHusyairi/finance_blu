<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Tagihan;
use App\Models\DetailPerjaldin;
use App\Models\MasterPegawai;
use App\Models\MasterUangHarianPerjaldin;
use App\Models\LogStatusDokumen;
use App\Support\DipaBudgetOptionService;

class PerjaldinController extends Controller
{
    /**
     * Daftar semua Tagihan Perjaldin (menggantikan Perjaldin lawas).
     */
    public function index()
    {
        $tagihans = Tagihan::where('tipe_tagihan', 'PERJALDIN')
            ->with(['detailPerjaldin.pegawai', 'logs' => fn($q) => $q->latest()->limit(1)])
            ->latest()
            ->get();

        return view('perjaldins.index', compact('tagihans'));
    }

    /**
     * Form tambah Perjaldin (menggunakan input manual Pegawai).
     */
    public function create()
    {
        $budgetGroups = DipaBudgetOptionService::groupedOptions();
        $masterProvinsi = MasterUangHarianPerjaldin::orderBy('provinsi')->get();
        return view('perjaldins.create', compact('budgetGroups', 'masterProvinsi'));
    }

    /**
     * Simpan data Perjaldin sebagai Tagihan + DetailPerjaldin.
     */
    public function store(Request $request)
    {
        // Strip commas from currency inputs
        $input = $request->all();
        if (isset($input['peserta']) && is_array($input['peserta'])) {
            foreach ($input['peserta'] as &$p) {
                foreach (['biaya_tiket', 'biaya_transport', 'biaya_penginapan', 'uang_harian', 'uang_representasi'] as $field) {
                    if (isset($p[$field])) $p[$field] = str_replace(',', '', $p[$field]);
                }
            }
            $request->merge($input);
        }

        $request->validate([
            'deskripsi' => 'required|string|max:255',
            'no_bast' => 'nullable|string|max:100',
            'dipa_revision_item_id' => 'required|exists:dipa_revision_items,id',
            'peserta' => 'required|array|min:1',
            'peserta.*.nama_pegawai' => 'required|string|max:100',
            'peserta.*.nip' => 'nullable|string|max:50',
            'peserta.*.provinsi_id' => 'nullable|exists:master_uang_harian_perjaldins,id',
            'peserta.*.tipe_perjalanan' => 'nullable|string|in:Luar Kota,Dalam Kota Lebih Dari 8 Jam,Diklat',
            'peserta.*.no_spt' => 'required|string|max:100',
            'peserta.*.tujuan' => 'nullable|string|max:255',
            'peserta.*.rekening' => 'nullable|string|max:100',
            'peserta.*.tgl_berangkat' => 'required|date',
            'peserta.*.lama_hari' => 'required|integer|min:1',
            'peserta.*.biaya_tiket' => 'nullable|numeric|min:0',
            'peserta.*.biaya_transport' => 'nullable|numeric|min:0',
            'peserta.*.biaya_penginapan' => 'nullable|numeric|min:0',
            'peserta.*.uang_harian' => 'nullable|numeric|min:0',
            'peserta.*.uang_representasi' => 'nullable|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();
            $selectedItem = DipaBudgetOptionService::resolveActiveItem($request->dipa_revision_item_id);

            // Hitung total bruto dari semua peserta
            $totalBruto = 0;
            foreach ($request->peserta as $p) {
                $totalBruto += ($p['biaya_tiket'] ?? 0) + ($p['biaya_transport'] ?? 0)
                             + ($p['biaya_penginapan'] ?? 0) + ($p['uang_harian'] ?? 0)
                             + ($p['uang_representasi'] ?? 0);
            }

            // Generate nomor tagihan
            $tahun = date('Y');
            $urut = Tagihan::whereYear('created_at', $tahun)->where('tipe_tagihan', 'PERJALDIN')->count() + 1;
            $nomorTagihan = 'PJD-' . $tahun . '-' . str_pad($urut, 4, '0', STR_PAD_LEFT);

            $tagihan = Tagihan::create([
                'nomor_tagihan' => $nomorTagihan,
                'tipe_tagihan' => 'PERJALDIN',
                'master_dipa_id' => $selectedItem->dipaRevision->master_dipa_id,
                'dipa_revision_item_id' => $selectedItem->id,
                'deskripsi' => $request->deskripsi . ($request->no_bast ? ' | BAST: ' . $request->no_bast : ''),
                'total_bruto' => $totalBruto,
                'total_potongan' => 0,
                'total_netto' => $totalBruto,
                'status' => 'DRAFT',
                'created_by' => auth()->id(),
            ]);

            // Insert detail per peserta
            foreach ($request->peserta as $pesertaData) {
                DetailPerjaldin::create([
                    'tagihan_id' => $tagihan->id,
                    'nama_pegawai' => $pesertaData['nama_pegawai'],
                    'nip' => $pesertaData['nip'] ?? null,
                    'no_spt' => $pesertaData['no_spt'],
                    'provinsi_id' => $pesertaData['provinsi_id'] ?? null,
                    'tipe_perjalanan' => $pesertaData['tipe_perjalanan'] ?? null,
                    'tujuan' => $pesertaData['tujuan'] ?? null,
                    'rekening' => $pesertaData['rekening'] ?? null,
                    'tgl_berangkat' => $pesertaData['tgl_berangkat'],
                    'lama_hari' => $pesertaData['lama_hari'],
                    'biaya_tiket' => $pesertaData['biaya_tiket'] ?? 0,
                    'biaya_transport' => $pesertaData['biaya_transport'] ?? 0,
                    'biaya_penginapan' => $pesertaData['biaya_penginapan'] ?? 0,
                    'uang_harian' => $pesertaData['uang_harian'] ?? 0,
                    'uang_representasi' => $pesertaData['uang_representasi'] ?? 0,
                ]);
            }

            // Log status awal
            LogStatusDokumen::create([
                'dokumen_type' => Tagihan::class,
                'dokumen_id' => $tagihan->id,
                'status_lama' => '-',
                'status_baru' => 'DRAFT',
                'diubah_oleh' => auth()->id(),
                'catatan' => 'Tagihan Perjaldin dibuat oleh Operator.',
            ]);

            DB::commit();
            return redirect()->route('perjaldins.index')->with('success', 'Data Perjaldin berhasil ditambahkan.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Gagal menyimpan: ' . $e->getMessage()]);
        }
    }

    /**
     * Ajukan beberapa Tagihan Perjaldin sekaligus ke PPK.
     */
    public function bulkSubmit(Request $request)
    {
        $request->validate([
            'tagihan_ids' => 'required|array',
            'tagihan_ids.*' => 'exists:tagihan,id',
        ]);

        $updatedCount = Tagihan::whereIn('id', $request->tagihan_ids)
            ->where('tipe_tagihan', 'PERJALDIN')
            ->whereIn('status', ['DRAFT', 'REVISI_PPK', 'DITOLAK_PPK'])
            ->update(['status' => 'PENDING_PPK']);

        if ($updatedCount > 0) {
            foreach ($request->tagihan_ids as $id) {
                LogStatusDokumen::create([
                    'dokumen_type' => Tagihan::class,
                    'dokumen_id' => $id,
                    'status_lama' => 'DRAFT',
                    'status_baru' => 'PENDING_PPK',
                    'diubah_oleh' => auth()->id(),
                    'catatan' => 'Operator mengajukan dokumen untuk verifikasi PPK.',
                ]);
            }

            // Kirim notifikasi ke PPK (jika class notifikasi tersedia)
            try {
                $verifikators = \App\Models\User::role('PPK')->get();
                \Illuminate\Support\Facades\Notification::send($verifikators, new \App\Notifications\WorkflowNotification([
                    'title' => 'Pengajuan Perjaldin Baru',
                    'message' => "Ada {$updatedCount} Pengajuan Perjaldin baru menunggu verifikasi Anda.",
                    'url' => route('verifikasi-ppk.index'),
                    'icon' => 'assignment',
                    'color' => 'warning'
                ]));
            } catch (\Exception $e) {
                // Notifikasi gagal tidak menghentikan proses
            }
        }

        return redirect()->route('perjaldins.index')
            ->with('success', "{$updatedCount} Perjaldin berhasil diajukan untuk verifikasi PPK.");
    }

    /**
     * Detail satu Tagihan Perjaldin.
     */
    public function show($id)
    {
        $tagihan = Tagihan::where('tipe_tagihan', 'PERJALDIN')
            ->with(['detailPerjaldin.pegawai', 'logs' => fn($q) => $q->latest()])
            ->findOrFail($id);
        return view('perjaldins.show', compact('tagihan'));
    }

    /**
     * Form edit Tagihan Perjaldin.
     */
    public function editPerjaldin($id)
    {
        $tagihan = Tagihan::where('tipe_tagihan', 'PERJALDIN')
            ->with('detailPerjaldin.pegawai')
            ->findOrFail($id);

        // Hanya boleh di-edit saat DRAFT atau REVISI
        if (!in_array($tagihan->status, ['DRAFT', 'REVISI_PPK', 'DITOLAK_PPK'])) {
            return redirect()->route('perjaldins.index')
                ->withErrors(['error' => 'Tagihan tidak bisa diedit karena statusnya sudah: ' . $tagihan->status]);
        }

        $budgetGroups = DipaBudgetOptionService::groupedOptions();
        $masterProvinsi = MasterUangHarianPerjaldin::orderBy('provinsi')->get();
        return view('perjaldins.edit-perjaldin', compact('tagihan', 'budgetGroups', 'masterProvinsi'));
    }

    /**
     * Update Tagihan Perjaldin beserta detail pesertanya.
     */
    public function updatePerjaldin(Request $request, $id)
    {
        $tagihan = Tagihan::where('tipe_tagihan', 'PERJALDIN')->findOrFail($id);

        if (!in_array($tagihan->status, ['DRAFT', 'REVISI_PPK', 'DITOLAK_PPK'])) {
            return redirect()->route('perjaldins.index')
                ->withErrors(['error' => 'Tagihan tidak bisa diedit karena statusnya sudah: ' . $tagihan->status]);
        }

        // Strip commas
        $input = $request->all();
        if (isset($input['peserta']) && is_array($input['peserta'])) {
            foreach ($input['peserta'] as &$p) {
                foreach (['biaya_tiket', 'biaya_transport', 'biaya_penginapan', 'uang_harian', 'uang_representasi'] as $field) {
                    if (isset($p[$field])) $p[$field] = str_replace(',', '', $p[$field]);
                }
            }
            $request->merge($input);
        }

        $request->validate([
            'deskripsi' => 'required|string|max:255',
            'no_bast' => 'nullable|string|max:100',
            'dipa_revision_item_id' => 'required|exists:dipa_revision_items,id',
            'peserta' => 'required|array|min:1',
            'peserta.*.detail_id' => 'nullable|exists:detail_perjaldin,id',
            'peserta.*.nama_pegawai' => 'required|string|max:100',
            'peserta.*.nip' => 'nullable|string|max:50',
            'peserta.*.provinsi_id' => 'nullable|exists:master_uang_harian_perjaldins,id',
            'peserta.*.tipe_perjalanan' => 'nullable|string|in:Luar Kota,Dalam Kota Lebih Dari 8 Jam,Diklat',
            'peserta.*.no_spt' => 'required|string|max:100',
            'peserta.*.tujuan' => 'nullable|string|max:255',
            'peserta.*.rekening' => 'nullable|string|max:100',
            'peserta.*.tgl_berangkat' => 'required|date',
            'peserta.*.lama_hari' => 'required|integer|min:1',
            'peserta.*.biaya_tiket' => 'nullable|numeric|min:0',
            'peserta.*.biaya_transport' => 'nullable|numeric|min:0',
            'peserta.*.biaya_penginapan' => 'nullable|numeric|min:0',
            'peserta.*.uang_harian' => 'nullable|numeric|min:0',
            'peserta.*.uang_representasi' => 'nullable|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();
            $selectedItem = DipaBudgetOptionService::resolveActiveItem($request->dipa_revision_item_id);

            // Hitung total bruto
            $totalBruto = 0;
            foreach ($request->peserta as $p) {
                $totalBruto += ($p['biaya_tiket'] ?? 0) + ($p['biaya_transport'] ?? 0)
                             + ($p['biaya_penginapan'] ?? 0) + ($p['uang_harian'] ?? 0)
                             + ($p['uang_representasi'] ?? 0);
            }

            $tagihan->update([
                'master_dipa_id' => $selectedItem->dipaRevision->master_dipa_id,
                'dipa_revision_item_id' => $selectedItem->id,
                'deskripsi' => $request->deskripsi . ($request->no_bast ? ' | BAST: ' . $request->no_bast : ''),
                'total_bruto' => $totalBruto,
                'total_netto' => $totalBruto - $tagihan->total_potongan,
                'status' => 'DRAFT', // Reset ke draft saat diedit
            ]);

            // Hapus detail lama lalu re-insert
            DetailPerjaldin::where('tagihan_id', $tagihan->id)->delete();

            foreach ($request->peserta as $pesertaData) {
                DetailPerjaldin::create([
                    'tagihan_id' => $tagihan->id,
                    'nama_pegawai' => $pesertaData['nama_pegawai'],
                    'nip' => $pesertaData['nip'] ?? null,
                    'no_spt' => $pesertaData['no_spt'],
                    'provinsi_id' => $pesertaData['provinsi_id'] ?? null,
                    'tipe_perjalanan' => $pesertaData['tipe_perjalanan'] ?? null,
                    'tujuan' => $pesertaData['tujuan'] ?? null,
                    'rekening' => $pesertaData['rekening'] ?? null,
                    'tgl_berangkat' => $pesertaData['tgl_berangkat'],
                    'lama_hari' => $pesertaData['lama_hari'],
                    'biaya_tiket' => $pesertaData['biaya_tiket'] ?? 0,
                    'biaya_transport' => $pesertaData['biaya_transport'] ?? 0,
                    'biaya_penginapan' => $pesertaData['biaya_penginapan'] ?? 0,
                    'uang_harian' => $pesertaData['uang_harian'] ?? 0,
                    'uang_representasi' => $pesertaData['uang_representasi'] ?? 0,
                ]);
            }

            LogStatusDokumen::create([
                'dokumen_type' => Tagihan::class,
                'dokumen_id' => $tagihan->id,
                'status_lama' => $tagihan->getOriginal('status'),
                'status_baru' => 'DRAFT',
                'diubah_oleh' => auth()->id(),
                'catatan' => 'Data perjaldin diperbarui oleh Operator.',
            ]);

            DB::commit();
            return redirect()->route('perjaldins.index')->with('success', 'Data Perjaldin berhasil diperbarui.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Gagal memperbarui: ' . $e->getMessage()]);
        }
    }

    /**
     * Hapus Tagihan Perjaldin beserta seluruh detail.
     */
    public function destroyPerjaldin($id)
    {
        $tagihan = Tagihan::where('tipe_tagihan', 'PERJALDIN')->findOrFail($id);

        if (!in_array($tagihan->status, ['DRAFT', 'REVISI_PPK', 'DITOLAK_PPK'])) {
            return redirect()->route('perjaldins.index')
                ->withErrors(['error' => 'Tidak dapat menghapus tagihan dengan status: ' . $tagihan->status]);
        }

        DetailPerjaldin::where('tagihan_id', $tagihan->id)->delete();
        $tagihan->logs()->delete();
        $tagihan->delete();

        return redirect()->route('perjaldins.index')->with('success', 'Perjaldin beserta seluruh datanya berhasil dihapus.');
    }
}

