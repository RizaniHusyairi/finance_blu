<?php

namespace App\Http\Controllers;

use App\Models\MasterMitraVendor;
use App\Models\RekeningBank;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SupplierController extends Controller
{
    public function index()
    {
        // Performa: tabel kini SERVER-SIDE (lihat indexData); KPI via query agregat.
        return view('suppliers.index', [
            'totalSupplier'      => MasterMitraVendor::count(),
            'supplierAktif'      => MasterMitraVendor::where('status_aktif', true)->count(),
            'penyediaBarangJasa' => MasterMitraVendor::where('tipe_supplier', '02 - Penyedia/Badan Usaha')->count(),
            'dataBelumLengkap'   => MasterMitraVendor::whereNull('npwp')->count(),
        ]);
    }

    /**
     * Performa — sumber data SERVER-SIDE DataTables untuk master Mitra/Vendor.
     */
    public function indexData(Request $request)
    {
        return \App\Support\DataTable::respond(
            $request,
            MasterMitraVendor::query()->with('rekening'),
            orderable: [0 => null, 1 => 'nama_pihak', 2 => 'npwp', 3 => null, 4 => null],
            searchable: ['nama_pihak', 'nama_penanggung_jawab', 'npwp'],
            rowMapper: fn ($supplier, $no) => [
                '<div class="text-center">' . $no . '</div>',
                '<span class="fw-bold">' . e($supplier->nama_perusahaan) . '</span><br>'
                    . '<small><i class="bi bi-person me-1"></i>Dir: ' . e($supplier->nama_direktur ?: '-') . '</small>',
                '<span class="font-monospace">' . e($supplier->npwp ?: 'Belum Ada') . '</span>',
                view('suppliers.partials._cell_bank', ['supplier' => $supplier])->render(),
                view('suppliers.partials._actions', ['supplier' => $supplier])->render(),
            ],
        );
    }

    public function create()
    {
        return view('suppliers.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_perusahaan' => 'required|string|max:150',
            'nama_direktur' => 'nullable|string|max:150',
            'jabatan_penandatangan' => 'nullable|string|max:150',
            'npwp' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:150',
            'no_telepon' => 'nullable|string|max:30',
            'alamat' => 'nullable|string',
            'nama_bank' => 'required|string|max:100',
            'nomor_rekening' => 'required|string|max:50',
            'nama_rekening' => 'required|string|max:150',
        ]);

        try {
            DB::beginTransaction();

            $mitra = MasterMitraVendor::create([
                'kategori' => 'PENGELUARAN',
                'jenis_entitas' => 'BADAN_USAHA',
                'nama_perusahaan' => $validated['nama_perusahaan'],
                'nama_direktur' => $validated['nama_direktur'] ?? null,
                'jabatan_penandatangan' => $validated['jabatan_penandatangan'] ?? null,
                'npwp' => $validated['npwp'] ?? null,
                'email' => $validated['email'] ?? null,
                'no_telepon' => $validated['no_telepon'] ?? null,
                'alamat' => $validated['alamat'] ?? null,
                'status_aktif' => true,
            ]);

            $mitra->rekening()->create([
                'nama_bank' => $validated['nama_bank'],
                'nomor_rekening' => $validated['nomor_rekening'],
                'nama_rekening' => $validated['nama_rekening'],
                'is_default' => true,
                'status_aktif' => true,
            ]);

            DB::commit();

            return redirect()->route('suppliers.index')->with('success', 'Mitra & Vendor berhasil ditambahkan beserta rekening.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Gagal menyimpan data: ' . $e->getMessage()]);
        }
    }

    public function show($id)
    {
        $supplier = MasterMitraVendor::with('rekening')->findOrFail($id);
        return view('suppliers.show', compact('supplier'));
    }

    public function edit($id)
    {
        $supplier = MasterMitraVendor::with('rekening')->findOrFail($id);
        return view('suppliers.edit', compact('supplier'));
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'nama_perusahaan' => 'required|string|max:150',
            'nama_direktur' => 'nullable|string|max:150',
            'jabatan_penandatangan' => 'nullable|string|max:150',
            'npwp' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:150',
            'no_telepon' => 'nullable|string|max:30',
            'alamat' => 'nullable|string',
            'nama_bank' => 'required|string|max:100',
            'nomor_rekening' => 'required|string|max:50',
            'nama_rekening' => 'required|string|max:150',
        ]);

        try {
            DB::beginTransaction();

            $mitra = MasterMitraVendor::with('rekening')->findOrFail($id);
            $mitra->update([
                'nama_perusahaan' => $validated['nama_perusahaan'],
                'nama_direktur' => $validated['nama_direktur'] ?? null,
                'jabatan_penandatangan' => $validated['jabatan_penandatangan'] ?? null,
                'npwp' => $validated['npwp'] ?? null,
                'email' => $validated['email'] ?? null,
                'no_telepon' => $validated['no_telepon'] ?? null,
                'alamat' => $validated['alamat'] ?? null,
            ]);

            $rek = $mitra->rekening()->first();
            if ($rek) {
                $rek->update([
                    'nama_bank' => $validated['nama_bank'],
                    'nomor_rekening' => $validated['nomor_rekening'],
                    'nama_rekening' => $validated['nama_rekening'],
                ]);
            } else {
                $mitra->rekening()->create([
                    'nama_bank' => $validated['nama_bank'],
                    'nomor_rekening' => $validated['nomor_rekening'],
                    'nama_rekening' => $validated['nama_rekening'],
                    'is_default' => true,
                    'status_aktif' => true,
                ]);
            }

            DB::commit();

            return redirect()->route('suppliers.index')->with('success', 'Data Mitra & Vendor berhasil diperbarui.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Gagal memperbarui data: ' . $e->getMessage()]);
        }
    }

    public function destroy($id)
    {
        $supplier = MasterMitraVendor::findOrFail($id);
        $supplier->delete();

        return redirect()->route('suppliers.index')->with('success', 'Supplier deleted successfully.');
    }
}
