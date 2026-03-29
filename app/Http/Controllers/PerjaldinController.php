<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Pejabat;
use App\Models\Perjaldin;
use App\Models\Employee;

class PerjaldinController extends Controller
{
    public function index()
    {
        $perjaldins = Perjaldin::with('pejabats')->latest()->get();
        return view('perjaldins.index', compact('perjaldins'));
    }

    public function create()
    {
        $employees = Employee::all();
        return view('perjaldins.create', compact('employees'));
    }

    public function store(Request $request)
    {
        // Strip out commas from currency inputs before validating
        $input = $request->all();
        if (isset($input['pejabats']) && is_array($input['pejabats'])) {
            foreach ($input['pejabats'] as &$pejabat) {
                if (isset($pejabat['tiket'])) $pejabat['tiket'] = str_replace(',', '', $pejabat['tiket']);
                if (isset($pejabat['transport'])) $pejabat['transport'] = str_replace(',', '', $pejabat['transport']);
                if (isset($pejabat['uang_harian'])) $pejabat['uang_harian'] = str_replace(',', '', $pejabat['uang_harian']);
                if (isset($pejabat['penginapan'])) $pejabat['penginapan'] = str_replace(',', '', $pejabat['penginapan']);
                if (isset($pejabat['uang_representasi'])) $pejabat['uang_representasi'] = str_replace(',', '', $pejabat['uang_representasi']);
            }
            $request->merge($input);
        }

        $request->validate([
            'uraian' => 'required|string|max:255',
            'no_bast' => 'nullable|string|max:255',
            'pejabats' => 'required|array|min:1',
            'pejabats.*.employee_id' => 'nullable|exists:employees,id',
            'pejabats.*.nama_pejabat' => 'required|string|max:255',
            'pejabats.*.nip' => 'nullable|string|max:50',
            'pejabats.*.no_spt' => 'required|string|max:255',
            'pejabats.*.no_sppd' => 'required|string|max:255',
            'pejabats.*.tujuan' => 'required|string|max:255',
            'pejabats.*.tanggal_berangkat' => 'required|date',
            'pejabats.*.lama_perjalanan_dinas' => 'required|integer|min:1',
            'pejabats.*.tiket' => 'nullable|numeric|min:0',
            'pejabats.*.transport' => 'nullable|numeric|min:0',
            'pejabats.*.uang_harian' => 'nullable|numeric|min:0',
            'pejabats.*.penginapan' => 'nullable|numeric|min:0',
            'pejabats.*.uang_representasi' => 'nullable|numeric|min:0',
            'pejabats.*.rekening' => 'nullable|string|max:255',
        ]);

        DB::transaction(function () use ($request) {
            $perjaldin = Perjaldin::create([
                'uraian' => $request->uraian,
                'no_bast' => $request->no_bast,
            ]);

            foreach ($request->pejabats as $pejabatData) {
                // Remove commas/formatting from monetary inputs if any
                $tiket = isset($pejabatData['tiket']) ? str_replace(',', '', $pejabatData['tiket']) : 0;
                $transport = isset($pejabatData['transport']) ? str_replace(',', '', $pejabatData['transport']) : 0;
                $uang_harian = isset($pejabatData['uang_harian']) ? str_replace(',', '', $pejabatData['uang_harian']) : 0;
                $penginapan = isset($pejabatData['penginapan']) ? str_replace(',', '', $pejabatData['penginapan']) : 0;
                $uang_representasi = isset($pejabatData['uang_representasi']) ? str_replace(',', '', $pejabatData['uang_representasi']) : 0;

                Pejabat::create([
                    'perjaldin_id' => $perjaldin->perjaldin_id,
                    'employee_id' => $pejabatData['employee_id'] ?? null,
                    'nama_pejabat' => $pejabatData['nama_pejabat'],
                    'nip' => $pejabatData['nip'] ?? null,
                    'no_spt' => $pejabatData['no_spt'],
                    'no_sppd' => $pejabatData['no_sppd'],
                    'tujuan' => $pejabatData['tujuan'],
                    'tanggal_berangkat' => $pejabatData['tanggal_berangkat'],
                    'lama_perjalanan_dinas' => $pejabatData['lama_perjalanan_dinas'],
                    'tiket' => $tiket ?: 0,
                    'transport' => $transport ?: 0,
                    'uang_harian' => $uang_harian ?: 0,
                    'penginapan' => $penginapan ?: 0,
                    'uang_representasi' => $uang_representasi ?: 0,
                    'rekening' => $pejabatData['rekening'] ?? null,
                    'status' => 'Draft',
                ]);
            }
        });

        return redirect()->route('perjaldins.index')->with('success', 'Data Perjaldin berhasil ditambahkan.');
    }

    public function bulkSubmit(Request $request)
    {
        $request->validate([
            'perjaldin_ids'   => 'required|array',
            'perjaldin_ids.*' => 'exists:perjaldins,perjaldin_id',
        ]);

        $updatedCount = Perjaldin::whereIn('perjaldin_id', $request->perjaldin_ids)
                  ->whereIn('status', ['Draft', 'Revisi'])
                  ->update([
                      'status'              => 'Proses Verifikasi',
                      'is_ppk_approved'     => false,
                      'is_kasubag_approved' => false,
                      'catatan_revisi'      => null,
                      'revisi_oleh'         => null,
                  ]);

        if ($updatedCount > 0) {
            foreach($request->perjaldin_ids as $pId) {
                \App\Models\PerjaldinLog::create([
                    'perjaldin_id' => $pId,
                    'user_name' => auth()->user()->name,
                    'action' => 'Mengajukan',
                    'catatan' => 'Operator mengajukan dokumen untuk verifikasi.'
                ]);
            }

            $verifikators = \App\Models\User::role(['PPK', 'Kepala Subbagian Keuangan dan Tata Usaha'])->get();
            \Illuminate\Support\Facades\Notification::send($verifikators, new \App\Notifications\WorkflowNotification([
                'title' => 'Pengajuan Perjaldin Baru',
                'message' => "Ada {$updatedCount} Pengajuan Perjaldin baru menunggu verifikasi Anda.",
                'url' => route('verifikasi-ppk.index'),
                'icon' => 'assignment',
                'color' => 'warning'
            ]));
        }

        return redirect()->route('perjaldins.index')->with('success', 'Perjaldin berhasil diajukan dan sedang menunggu verifikasi PPK & Kasubag.');
    }

    public function show(Pejabat $pejabat)
    {
        $pejabat->load(['perjaldin', 'employee']);
        return view('perjaldins.show', compact('pejabat'));
    }

    public function edit(Pejabat $pejabat)
    {
        $pejabat->load('perjaldin');
        $employees = Employee::all();
        return view('perjaldins.edit', compact('pejabat', 'employees'));
    }

    public function update(Request $request, Pejabat $pejabat)
    {
        // Strip commas
        $input = $request->all();
        if (isset($input['tiket'])) $input['tiket'] = str_replace(',', '', $input['tiket']);
        if (isset($input['transport'])) $input['transport'] = str_replace(',', '', $input['transport']);
        if (isset($input['uang_harian'])) $input['uang_harian'] = str_replace(',', '', $input['uang_harian']);
        if (isset($input['penginapan'])) $input['penginapan'] = str_replace(',', '', $input['penginapan']);
        if (isset($input['uang_representasi'])) $input['uang_representasi'] = str_replace(',', '', $input['uang_representasi']);
        $request->merge($input);

        $request->validate([
            'uraian' => 'required|string|max:255',
            'no_bast' => 'nullable|string|max:255',
            'employee_id' => 'nullable|exists:employees,id',
            'nama_pejabat' => 'required|string|max:255',
            'nip' => 'nullable|string|max:50',
            'no_spt' => 'required|string|max:255',
            'no_sppd' => 'required|string|max:255',
            'tujuan' => 'required|string|max:255',
            'tanggal_berangkat' => 'required|date',
            'lama_perjalanan_dinas' => 'required|integer|min:1',
            'tiket' => 'nullable|numeric|min:0',
            'transport' => 'nullable|numeric|min:0',
            'uang_harian' => 'nullable|numeric|min:0',
            'penginapan' => 'nullable|numeric|min:0',
            'uang_representasi' => 'nullable|numeric|min:0',
            'rekening' => 'nullable|string|max:255',
        ]);

        DB::transaction(function () use ($request, $pejabat) {
            // Update parent Perjaldin details if changed
            $pejabat->perjaldin->update([
                'uraian' => $request->uraian,
                'no_bast' => $request->no_bast,
            ]);

            // Update specific Pejabat details
            $pejabat->update([
                'employee_id' => $request->employee_id,
                'nama_pejabat' => $request->nama_pejabat,
                'nip' => $request->nip,
                'no_spt' => $request->no_spt,
                'no_sppd' => $request->no_sppd,
                'tujuan' => $request->tujuan,
                'tanggal_berangkat' => $request->tanggal_berangkat,
                'lama_perjalanan_dinas' => $request->lama_perjalanan_dinas,
                'tiket' => $request->tiket ?? 0,
                'transport' => $request->transport ?? 0,
                'uang_harian' => $request->uang_harian ?? 0,
                'penginapan' => $request->penginapan ?? 0,
                'uang_representasi' => $request->uang_representasi ?? 0,
                'rekening' => $request->rekening,
            ]);
        });

        return redirect()->route('perjaldins.index')->with('success', 'Data Pejabat / Perjaldin berhasil diperbarui.');
    }

    public function editPerjaldin(Perjaldin $perjaldin)
    {
        $perjaldin->load('pejabats.employee');
        $employees = Employee::all();
        return view('perjaldins.edit-perjaldin', compact('perjaldin', 'employees'));
    }

    public function updatePerjaldin(Request $request, Perjaldin $perjaldin)
    {
        $input = $request->all();
        if (isset($input['pejabats']) && is_array($input['pejabats'])) {
            foreach ($input['pejabats'] as &$pejabat) {
                if (isset($pejabat['tiket'])) $pejabat['tiket'] = str_replace(',', '', $pejabat['tiket']);
                if (isset($pejabat['transport'])) $pejabat['transport'] = str_replace(',', '', $pejabat['transport']);
                if (isset($pejabat['uang_harian'])) $pejabat['uang_harian'] = str_replace(',', '', $pejabat['uang_harian']);
                if (isset($pejabat['penginapan'])) $pejabat['penginapan'] = str_replace(',', '', $pejabat['penginapan']);
                if (isset($pejabat['uang_representasi'])) $pejabat['uang_representasi'] = str_replace(',', '', $pejabat['uang_representasi']);
            }
            $request->merge($input);
        }

        $request->validate([
            'uraian' => 'required|string|max:255',
            'no_bast' => 'nullable|string|max:255',
            'pejabats' => 'required|array|min:1',
            'pejabats.*.pejabat_id' => 'nullable|exists:pejabats,pejabat_id',
            'pejabats.*.employee_id' => 'nullable|exists:employees,id',
            'pejabats.*.nama_pejabat' => 'required|string|max:255',
            'pejabats.*.nip' => 'nullable|string|max:50',
            'pejabats.*.no_spt' => 'required|string|max:255',
            'pejabats.*.no_sppd' => 'required|string|max:255',
            'pejabats.*.tujuan' => 'required|string|max:255',
            'pejabats.*.tanggal_berangkat' => 'required|date',
            'pejabats.*.lama_perjalanan_dinas' => 'required|integer|min:1',
            'pejabats.*.tiket' => 'nullable|numeric|min:0',
            'pejabats.*.transport' => 'nullable|numeric|min:0',
            'pejabats.*.uang_harian' => 'nullable|numeric|min:0',
            'pejabats.*.penginapan' => 'nullable|numeric|min:0',
            'pejabats.*.uang_representasi' => 'nullable|numeric|min:0',
            'pejabats.*.rekening' => 'nullable|string|max:255',
        ]);

        DB::transaction(function () use ($request, $perjaldin) {
            $perjaldin->update([
                'uraian' => $request->uraian,
                'no_bast' => $request->no_bast,
                // Reset approval workflow jika diedit saat revisi
                'status' => 'Draft',
                'is_ppk_approved'     => false,
                'is_kasubag_approved' => false,
            ]);

            $existingIds = $perjaldin->pejabats->pluck('pejabat_id')->toArray();
            $submittedIds = collect($request->pejabats)->pluck('pejabat_id')->filter()->toArray();

            // Delete removed pejabats
            $toDelete = array_diff($existingIds, $submittedIds);
            if (!empty($toDelete)) {
                Pejabat::whereIn('pejabat_id', $toDelete)->delete();
            }

            foreach ($request->pejabats as $pejabatData) {
                $tiket = isset($pejabatData['tiket']) ? str_replace(',', '', $pejabatData['tiket']) : 0;
                $transport = isset($pejabatData['transport']) ? str_replace(',', '', $pejabatData['transport']) : 0;
                $uang_harian = isset($pejabatData['uang_harian']) ? str_replace(',', '', $pejabatData['uang_harian']) : 0;
                $penginapan = isset($pejabatData['penginapan']) ? str_replace(',', '', $pejabatData['penginapan']) : 0;
                $uang_representasi = isset($pejabatData['uang_representasi']) ? str_replace(',', '', $pejabatData['uang_representasi']) : 0;

                $data = [
                    'employee_id' => $pejabatData['employee_id'] ?? null,
                    'nama_pejabat' => $pejabatData['nama_pejabat'],
                    'nip' => $pejabatData['nip'] ?? null,
                    'no_spt' => $pejabatData['no_spt'],
                    'no_sppd' => $pejabatData['no_sppd'],
                    'tujuan' => $pejabatData['tujuan'],
                    'tanggal_berangkat' => $pejabatData['tanggal_berangkat'],
                    'lama_perjalanan_dinas' => $pejabatData['lama_perjalanan_dinas'],
                    'tiket' => $tiket ?: 0,
                    'transport' => $transport ?: 0,
                    'uang_harian' => $uang_harian ?: 0,
                    'penginapan' => $penginapan ?: 0,
                    'uang_representasi' => $uang_representasi ?: 0,
                    'rekening' => $pejabatData['rekening'] ?? null,
                    'status' => 'Draft',
                ];

                if (!empty($pejabatData['pejabat_id'])) {
                    // Update existing
                    Pejabat::where('pejabat_id', $pejabatData['pejabat_id'])->update($data);
                } else {
                    // Create new
                    $data['perjaldin_id'] = $perjaldin->perjaldin_id;
                    Pejabat::create($data);
                }
            }
        });

        return redirect()->route('perjaldins.index')->with('success', 'Data Perjaldin berhasil diperbarui.');
    }

    public function destroy(Pejabat $pejabat)
    {
        $pejabat->delete();
        return redirect()->route('perjaldins.index')->with('success', 'Data Pejabat berhasil dihapus dari daftar keberangkatan.');
    }

    public function destroyPerjaldin(Perjaldin $perjaldin)
    {
        $perjaldin->pejabats()->delete();
        $perjaldin->delete();
        return redirect()->route('perjaldins.index')->with('success', 'Perjaldin beserta seluruh data pejabatnya berhasil dihapus.');
    }
}
