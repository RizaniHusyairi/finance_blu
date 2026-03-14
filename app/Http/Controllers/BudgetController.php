<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use Illuminate\Http\Request;

class BudgetController extends Controller
{
    public function index()
    {
        $budgets = Budget::all();

        $totalPagu = $budgets->sum('initial_budget');
        $totalRealisasi = $budgets->sum('realized_budget');
        $sisaPagu = $budgets->sum('remaining_budget');
        $persenSerapan = $totalPagu > 0 ? round(($totalRealisasi / $totalPagu) * 100, 1) : 0;

        return view('budgets.index', compact('budgets', 'totalPagu', 'totalRealisasi', 'sisaPagu', 'persenSerapan'));
    }

    public function create()
    {
        $existingCoas = Budget::pluck('coa')->toArray();
        return view('budgets.create', compact('existingCoas'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'year' => 'required|integer',
            'program_code' => 'required|string|max:50',
            'activity_code' => 'required|string|max:50',
            'output_code' => 'required|string|max:50',
            'suboutput_code' => 'required|string|max:50',
            'component_code' => 'required|string|max:50',
            'subcomponent_code' => 'required|string|max:50',
            'account_code' => 'required|string|max:50',
            'item_code' => 'required|string|max:50',
            'description' => 'required|string',
            'initial_budget' => 'required|numeric|min:1',
            'status_pagu' => 'required|string|in:Aktif,Nonaktif',
            'catatan' => 'nullable|string',
        ]);

        // Build COA from code segments
        $coa = implode('.', [
            $validated['program_code'],
            $validated['activity_code'],
            $validated['output_code'],
            $validated['suboutput_code'],
            $validated['component_code'],
            $validated['subcomponent_code'],
            $validated['account_code'],
            $validated['item_code'],
        ]);

        // Check COA uniqueness
        if (Budget::where('coa', $coa)->exists()) {
            return back()->withInput()->withErrors(['coa' => 'Kombinasi kode COA "' . $coa . '" sudah digunakan.']);
        }

        if ($request->has('simpan_draft')) {
            $validated['status_pagu'] = 'Nonaktif';
        }

        Budget::create([
            'program_code' => $validated['program_code'],
            'activity_code' => $validated['activity_code'],
            'output_code' => $validated['output_code'],
            'suboutput_code' => $validated['suboutput_code'],
            'component_code' => $validated['component_code'],
            'subcomponent_code' => $validated['subcomponent_code'],
            'account_code' => $validated['account_code'],
            'item_code' => $validated['item_code'],
            'coa' => $coa,
            'description' => $validated['description'],
            'initial_budget' => $validated['initial_budget'],
            'realized_budget' => 0,
            'remaining_budget' => $validated['initial_budget'],
            'year' => $validated['year'],
            'status_pagu' => $validated['status_pagu'],
            'catatan' => $validated['catatan'] ?? null,
        ]);

        return redirect()->route('budgets.index')->with('success', 'Pagu anggaran berhasil ditambahkan.');
    }

    public function show(Budget $budget)
    {
        $pagu = $budget->initial_budget;
        $realisasi = $budget->realized_budget;
        $sisa = $budget->remaining_budget;
        $persen = $pagu > 0 ? round(($realisasi / $pagu) * 100, 2) : 0;

        // Status penggunaan
        if ($persen == 0) {
            $statusPenggunaan = 'Belum Terealisasi';
            $badgePenggunaan = 'secondary';
        } elseif ($persen < 80) {
            $statusPenggunaan = 'Sebagian';
            $badgePenggunaan = 'primary';
        } elseif ($persen < 100) {
            $statusPenggunaan = 'Hampir Habis';
            $badgePenggunaan = 'warning';
        } else {
            $statusPenggunaan = 'Habis';
            $badgePenggunaan = 'danger';
        }

        // Progress bar color
        if ($persen < 50) {
            $progressColor = 'bg-success';
        } elseif ($persen < 80) {
            $progressColor = 'bg-warning';
        } else {
            $progressColor = 'bg-danger';
        }

        return view('budgets.show', compact(
            'budget', 'pagu', 'realisasi', 'sisa', 'persen',
            'statusPenggunaan', 'badgePenggunaan', 'progressColor'
        ));
    }

    public function edit(Budget $budget)
    {
        $existingCoas = Budget::where('id', '!=', $budget->id)->pluck('coa')->toArray();
        return view('budgets.edit', compact('budget', 'existingCoas'));
    }

    public function update(Request $request, Budget $budget)
    {
        $validated = $request->validate([
            'year' => 'required|integer',
            'program_code' => 'required|string|max:50',
            'activity_code' => 'required|string|max:50',
            'output_code' => 'required|string|max:50',
            'suboutput_code' => 'required|string|max:50',
            'component_code' => 'required|string|max:50',
            'subcomponent_code' => 'required|string|max:50',
            'account_code' => 'required|string|max:50',
            'item_code' => 'required|string|max:50',
            'description' => 'required|string',
            'initial_budget' => 'required|numeric|min:1',
            'status_pagu' => 'required|string|in:Aktif,Nonaktif',
            'catatan' => 'nullable|string',
        ]);

        // Build COA
        $coa = implode('.', [
            $validated['program_code'],
            $validated['activity_code'],
            $validated['output_code'],
            $validated['suboutput_code'],
            $validated['component_code'],
            $validated['subcomponent_code'],
            $validated['account_code'],
            $validated['item_code'],
        ]);

        // Check COA uniqueness (exclude current)
        if (Budget::where('coa', $coa)->where('id', '!=', $budget->id)->exists()) {
            return back()->withInput()->withErrors(['coa' => 'Kombinasi kode COA "' . $coa . '" sudah digunakan.']);
        }

        // Recalculate remaining based on new initial and existing realized
        $remaining = $validated['initial_budget'] - $budget->realized_budget;

        $budget->update([
            'program_code' => $validated['program_code'],
            'activity_code' => $validated['activity_code'],
            'output_code' => $validated['output_code'],
            'suboutput_code' => $validated['suboutput_code'],
            'component_code' => $validated['component_code'],
            'subcomponent_code' => $validated['subcomponent_code'],
            'account_code' => $validated['account_code'],
            'item_code' => $validated['item_code'],
            'coa' => $coa,
            'description' => $validated['description'],
            'initial_budget' => $validated['initial_budget'],
            'remaining_budget' => $remaining,
            'year' => $validated['year'],
            'status_pagu' => $validated['status_pagu'],
            'catatan' => $validated['catatan'] ?? null,
        ]);

        return redirect()->route('budgets.index')->with('success', 'Pagu anggaran berhasil diperbarui.');
    }

    public function destroy(Budget $budget)
    {
        $budget->delete();

        return redirect()->route('budgets.index')->with('success', 'Budget deleted successfully.');
    }
}
