<?php

namespace App\Http\Controllers;

use App\Models\MasterUangHarianPerjaldin;
use Illuminate\Http\Request;

class MasterUangHarianPerjaldinController extends Controller
{
    public function index()
    {
        $data = MasterUangHarianPerjaldin::orderBy('provinsi', 'asc')->get();
        return view('master-uang-harian-perjaldin.index', compact('data'));
    }

    public function create()
    {
        return view('master-uang-harian-perjaldin.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'provinsi' => 'required|string|max:255|unique:master_uang_harian_perjaldins,provinsi',
            'luar_kota' => 'required|numeric|min:0',
            'dalam_kota_lebih_8_jam' => 'required|numeric|min:0',
            'diklat' => 'nullable|numeric|min:0',
        ]);

        MasterUangHarianPerjaldin::create($request->all());

        return redirect()->route('master-uang-harian-perjaldin.index')
            ->with('success', 'Data Uang Harian berhasil ditambahkan.');
    }

    public function edit($id)
    {
        $data = MasterUangHarianPerjaldin::findOrFail($id);
        return view('master-uang-harian-perjaldin.edit', compact('data'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'provinsi' => 'required|string|max:255|unique:master_uang_harian_perjaldins,provinsi,' . $id,
            'luar_kota' => 'required|numeric|min:0',
            'dalam_kota_lebih_8_jam' => 'required|numeric|min:0',
            'diklat' => 'nullable|numeric|min:0',
        ]);

        $data = MasterUangHarianPerjaldin::findOrFail($id);
        $data->update($request->all());

        return redirect()->route('master-uang-harian-perjaldin.index')
            ->with('success', 'Data Uang Harian berhasil diubah.');
    }

    public function destroy($id)
    {
        $data = MasterUangHarianPerjaldin::findOrFail($id);
        $data->delete();

        return redirect()->route('master-uang-harian-perjaldin.index')
            ->with('success', 'Data Uang Harian berhasil dihapus.');
    }
}
