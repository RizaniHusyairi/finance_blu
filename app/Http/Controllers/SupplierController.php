<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function index()
    {
        $suppliers = Supplier::all();

        $totalSupplier = Supplier::count();
        $supplierAktif = Supplier::whereHas('contracts', function ($query) {
            $query->whereIn('status', ['Active', 'Draft']); // Adjust as needed, assuming active means having ongoing contracts. We use Active here to match contract status.
        })->count();
        $penyediaBarangJasa = Supplier::where('type', 'like', '%Barang%')
                                     ->orWhere('type', 'like', '%Jasa%')
                                     ->orWhere('type', 'like', '%Penyedia%')
                                     ->count();
        $dataBelumLengkap = Supplier::whereNull('npwp')
                                   ->orWhereNull('bank_account')
                                   ->orWhereNull('address')
                                   ->orWhereNull('phone')
                                   ->orWhere('npwp', '')
                                   ->orWhere('bank_account', '')
                                   ->count();

        return view('suppliers.index', compact(
            'suppliers', 'totalSupplier', 'supplierAktif', 'penyediaBarangJasa', 'dataBelumLengkap'
        ));
    }

    public function create()
    {
        $latest = Supplier::latest('id')->first();
        $nextId = $latest ? $latest->id + 1 : 1;
        $idSupplier = 'SUP-' . date('Y') . '-' . str_pad($nextId, 4, '0', STR_PAD_LEFT);

        return view('suppliers.create', compact('idSupplier'));
    }

    public function store(Request $request)
    {
        $rules = [
            'name' => 'required|string|max:255',
            'type' => 'required|string|max:255',
            'address' => 'required|string',
            'npwp_status' => 'required|string|in:Tersedia,Terlampir,Belum Ada',
            'rekening_status' => 'required|string|in:Tersedia,Terlampir,Belum Ada',
            'status' => 'required|string|in:Aktif,Nonaktif',
            'phone' => 'nullable|string|max:20',
            'catatan' => 'nullable|string',
            'id_supplier' => 'required|string',
        ];

        // Conditional validation
        if ($request->npwp_status === 'Tersedia') {
            $rules['npwp'] = 'required|string|max:255';
        } else {
            $rules['npwp'] = 'nullable|string|max:255';
        }

        if ($request->rekening_status === 'Tersedia') {
            $rules['bank_name'] = 'required|string|max:255';
            $rules['bank_account'] = 'required|string|max:255';
            $rules['account_name'] = 'required|string|max:255';
        } else {
            $rules['bank_name'] = 'nullable|string|max:255';
            $rules['bank_account'] = 'nullable|string|max:255';
            $rules['account_name'] = 'nullable|string|max:255';
        }

        $validated = $request->validate($rules);

        // Check duplicate name
        $existing = Supplier::where('name', $validated['name'])->exists();
        if ($existing && !$request->has('confirm_duplicate')) {
            return back()->withInput()->withErrors(['name' => 'Supplier dengan nama yang sama sudah ada. Centang konfirmasi duplikat untuk tetap menyimpan.']);
        }

        if ($request->has('simpan_draft')) {
            $validated['status'] = 'Nonaktif';
        }

        Supplier::create($validated);

        return redirect()->route('suppliers.index')->with('success', 'Supplier berhasil ditambahkan.');
    }

    public function show(Supplier $supplier)
    {
        return view('suppliers.show', compact('supplier'));
    }

    public function edit(Supplier $supplier)
    {
        return view('suppliers.edit', compact('supplier'));
    }

    public function update(Request $request, Supplier $supplier)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'npwp' => 'nullable|string|max:255',
            'bank_name' => 'nullable|string|max:255',
            'bank_account' => 'nullable|string|max:255',
            'account_name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:255',
        ]);

        $supplier->update($validated);

        return redirect()->route('suppliers.index')->with('success', 'Supplier updated successfully.');
    }

    public function destroy(Supplier $supplier)
    {
        $supplier->delete();

        return redirect()->route('suppliers.index')->with('success', 'Supplier deleted successfully.');
    }
}
