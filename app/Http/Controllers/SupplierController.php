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
        // Eager load the polymorphic relationship `rekening` to prevent N+1 Queries
        $suppliers = MasterMitraVendor::with('rekening')->get();
        
        $totalSupplier = $suppliers->count();
        // Cukup ambil data total untuk stat card atas (bisa disesuaikan nanti)
        $supplierAktif = $suppliers->where('kategori', 'VENDOR_PENGELUARAN')->count(); // Misal: supplier aktif = vendor pengeluaran yg ready
        $penyediaBarangJasa = $suppliers->where('tipe_supplier', '02 - Penyedia/Badan Usaha')->count();
        $dataBelumLengkap = $suppliers->whereNull('npwp')->count();

        return view('suppliers.index', compact(
            'suppliers', 'totalSupplier', 'supplierAktif', 'penyediaBarangJasa', 'dataBelumLengkap'
        ));
    }

    public function create()
    {
        return view('suppliers.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            // Validasi Mitra/Vendor
            'kategori' => 'required|in:VENDOR_PENGELUARAN,MITRA_PENERIMAAN,KEDUANYA',
            'tipe_supplier' => 'required|string|max:50',
            'nama_perusahaan' => 'required|string|max:150',
            'nama_direktur' => 'nullable|string|max:100',
            'npwp' => 'nullable|string|max:30',
            'no_telepon' => 'nullable|string|max:30',
            'alamat' => 'nullable|string',
            
            // Validasi Rekening Bank
            'nama_bank' => 'required|string|max:50',
            'nomor_rekening' => 'required|string|max:50',
            'nama_rekening' => 'required|string|max:150',
        ]);

        try {
            DB::beginTransaction();

            $mitra = MasterMitraVendor::create([
                'kategori' => $validated['kategori'],
                'tipe_supplier' => $validated['tipe_supplier'],
                'nama_perusahaan' => $validated['nama_perusahaan'],
                'nama_direktur' => $validated['nama_direktur'],
                'npwp' => $validated['npwp'],
                'no_telepon' => $validated['no_telepon'],
                'alamat' => $validated['alamat'],
            ]);

            RekeningBank::create([
                'pemilik_type' => MasterMitraVendor::class,
                'pemilik_id' => $mitra->id,
                'nama_bank' => $validated['nama_bank'],
                'nomor_rekening' => $validated['nomor_rekening'],
                'nama_rekening' => $validated['nama_rekening'],
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
        $supplier = MasterMitraVendor::findOrFail($id);
        return view('suppliers.show', compact('supplier'));
    }

    public function edit($id)
    {
        $supplier = MasterMitraVendor::findOrFail($id);
        return view('suppliers.edit', compact('supplier'));
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            // Validasi Mitra/Vendor
            'kategori' => 'required|in:VENDOR_PENGELUARAN,MITRA_PENERIMAAN,KEDUANYA',
            'tipe_supplier' => 'required|string|max:50',
            'nama_perusahaan' => 'required|string|max:150',
            'nama_direktur' => 'nullable|string|max:100',
            'npwp' => 'nullable|string|max:30',
            'no_telepon' => 'nullable|string|max:30',
            'alamat' => 'nullable|string',
            
            // Validasi Rekening Bank
            'nama_bank' => 'required|string|max:50',
            'nomor_rekening' => 'required|string|max:50',
            'nama_rekening' => 'required|string|max:150',
        ]);

        try {
            DB::beginTransaction();

            $mitra = MasterMitraVendor::findOrFail($id);
            $mitra->update([
                'kategori' => $validated['kategori'],
                'tipe_supplier' => $validated['tipe_supplier'],
                'nama_perusahaan' => $validated['nama_perusahaan'],
                'nama_direktur' => $validated['nama_direktur'],
                'npwp' => $validated['npwp'],
                'no_telepon' => $validated['no_telepon'],
                'alamat' => $validated['alamat'],
            ]);

            // Update atau Create Rekening Bank
            $rek = $mitra->rekening()->first();
            if ($rek) {
                $rek->update([
                    'nama_bank' => $validated['nama_bank'],
                    'nomor_rekening' => $validated['nomor_rekening'],
                    'nama_rekening' => $validated['nama_rekening'],
                ]);
            } else {
                RekeningBank::create([
                    'pemilik_type' => MasterMitraVendor::class,
                    'pemilik_id' => $mitra->id,
                    'nama_bank' => $validated['nama_bank'],
                    'nomor_rekening' => $validated['nomor_rekening'],
                    'nama_rekening' => $validated['nama_rekening'],
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
