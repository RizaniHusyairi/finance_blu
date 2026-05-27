<?php

namespace App\Http\Controllers;

use App\Models\MasterTarifPajak;
use Carbon\Carbon;
use Illuminate\Http\Request;

class MasterTarifPajakController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $statusFilter = $request->input('status_aktif');
        $berlakuFilter = $request->input('berlaku');

        $today = Carbon::today();

        $query = MasterTarifPajak::query()
            ->when($search, function ($q) use ($search) {
                // Pencarian hanya berdasarkan kode pajak.
                $s = strtolower(trim($search));
                $q->whereRaw('LOWER(kode_pajak) LIKE ?', ["%{$s}%"]);
            })
            ->when($statusFilter !== null && $statusFilter !== '', function ($q) use ($statusFilter) {
                $q->where('status_aktif', $statusFilter === 'aktif');
            })
            ->when($berlakuFilter, function ($q) use ($berlakuFilter, $today) {
                if ($berlakuFilter === 'berlaku') {
                    $q->where('status_aktif', true)
                      ->where(function ($sq) use ($today) {
                          $sq->where(function ($inner) use ($today) {
                              $inner->whereNotNull('berlaku_mulai')
                                    ->where('berlaku_mulai', '<=', $today);
                          })->orWhereNull('berlaku_mulai');
                      })
                      ->where(function ($sq) use ($today) {
                          $sq->where(function ($inner) use ($today) {
                              $inner->whereNotNull('berlaku_sampai')
                                    ->where('berlaku_sampai', '>=', $today);
                          })->orWhereNull('berlaku_sampai');
                      });
                } elseif ($berlakuFilter === 'belum') {
                    $q->whereNotNull('berlaku_mulai')
                      ->where('berlaku_mulai', '>', $today);
                } elseif ($berlakuFilter === 'expired') {
                    $q->whereNotNull('berlaku_sampai')
                      ->where('berlaku_sampai', '<', $today);
                }
            })
            ->orderByDesc('created_at');

        $pajaks = $query->paginate(15)->withQueryString();

        if ($request->ajax() && $request->boolean('partial')) {
            return response()->view('master-pajak._table', compact('pajaks', 'today'));
        }

        // Summary calculations
        $allPajaks = MasterTarifPajak::all();
        $summary = [
            'total' => $allPajaks->count(),
            'aktif' => $allPajaks->where('status_aktif', true)->count(),
            'nonaktif' => $allPajaks->where('status_aktif', false)->count(),
            'berlaku_sekarang' => $allPajaks->filter(function ($p) use ($today) {
                if (!$p->status_aktif) return false;
                $mulai = $p->berlaku_mulai ? Carbon::parse($p->berlaku_mulai) : null;
                $sampai = $p->berlaku_sampai ? Carbon::parse($p->berlaku_sampai) : null;
                if ($mulai && $mulai->gt($today)) return false;
                if ($sampai && $sampai->lt($today)) return false;
                return true;
            })->count(),
        ];

        return view('master-pajak.index', compact('pajaks', 'summary', 'search', 'statusFilter', 'berlakuFilter'));
    }

    public function create()
    {
        return view('master-pajak.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'kode_pajak' => 'required|string|max:30|unique:master_tarif_pajak,kode_pajak',
            'jenis_pajak' => 'required|string|max:50',
            'persentase' => 'required|numeric|min:0',
            'rumus' => 'nullable|string',
            'berlaku_mulai' => 'nullable|date',
            'berlaku_sampai' => 'nullable|date|after_or_equal:berlaku_mulai',
            'status_aktif' => 'nullable',
        ]);

        MasterTarifPajak::create([
            'kode_pajak' => strtoupper(trim($validated['kode_pajak'])),
            'jenis_pajak' => trim($validated['jenis_pajak']),
            'persentase' => $validated['persentase'],
            'rumus' => $validated['rumus'] ? trim($validated['rumus']) : null,
            'berlaku_mulai' => $validated['berlaku_mulai'] ?? null,
            'berlaku_sampai' => $validated['berlaku_sampai'] ?? null,
            'status_aktif' => $request->has('status_aktif'),
        ]);

        return redirect()
            ->route('master-pajak.index')
            ->with('success', 'Tarif pajak ' . strtoupper(trim($validated['kode_pajak'])) . ' berhasil ditambahkan.');
    }

    public function show(MasterTarifPajak $pajak)
    {
        return view('master-pajak.show', compact('pajak'));
    }

    public function edit(MasterTarifPajak $pajak)
    {
        return view('master-pajak.edit', compact('pajak'));
    }

    public function update(Request $request, MasterTarifPajak $pajak)
    {
        $validated = $request->validate([
            'kode_pajak' => 'required|string|max:30|unique:master_tarif_pajak,kode_pajak,' . $pajak->id,
            'jenis_pajak' => 'required|string|max:50',
            'persentase' => 'required|numeric|min:0',
            'rumus' => 'nullable|string',
            'berlaku_mulai' => 'nullable|date',
            'berlaku_sampai' => 'nullable|date|after_or_equal:berlaku_mulai',
            'status_aktif' => 'nullable',
        ]);

        $pajak->update([
            'kode_pajak' => strtoupper(trim($validated['kode_pajak'])),
            'jenis_pajak' => trim($validated['jenis_pajak']),
            'persentase' => $validated['persentase'],
            'rumus' => $validated['rumus'] ? trim($validated['rumus']) : null,
            'berlaku_mulai' => $validated['berlaku_mulai'] ?? null,
            'berlaku_sampai' => $validated['berlaku_sampai'] ?? null,
            'status_aktif' => $request->has('status_aktif'),
        ]);

        return redirect()
            ->route('master-pajak.index')
            ->with('success', 'Tarif pajak ' . $pajak->kode_pajak . ' berhasil diperbarui.');
    }

    public function toggle(MasterTarifPajak $pajak)
    {
        $pajak->update(['status_aktif' => !$pajak->status_aktif]);

        $status = $pajak->status_aktif ? 'diaktifkan' : 'dinonaktifkan';

        return redirect()
            ->route('master-pajak.index')
            ->with('success', 'Tarif pajak ' . ($pajak->kode_pajak ?? $pajak->jenis_pajak) . ' berhasil ' . $status . '.');
    }
}
