<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use Illuminate\Http\Request;

class BudgetController extends Controller
{
    public function index()
    {
        $budgets = Budget::all();
        return view('budgets.index', compact('budgets'));
    }

    public function create()
    {
        return view('budgets.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'program_code' => 'nullable|string|max:255',
            'activity_code' => 'nullable|string|max:255',
            'output_code' => 'nullable|string|max:255',
            'suboutput_code' => 'nullable|string|max:255',
            'component_code' => 'nullable|string|max:255',
            'subcomponent_code' => 'nullable|string|max:255',
            'account_code' => 'nullable|string|max:255',
            'item_code' => 'nullable|string|max:255',
            'coa' => 'required|string|max:255|unique:budgets,coa',
            'description' => 'required|string',
            'initial_budget' => 'required|numeric',
            'realized_budget' => 'nullable|numeric',
            'remaining_budget' => 'nullable|numeric',
            'year' => 'required|string|max:4',
        ]);

        $validated['realized_budget'] = 0;
        $validated['remaining_budget'] = $validated['initial_budget'];

        Budget::create($validated);

        return redirect()->route('budgets.index')->with('success', 'Budget created successfully.');
    }

    public function show(Budget $budget)
    {
        return view('budgets.show', compact('budget'));
    }

    public function edit(Budget $budget)
    {
        return view('budgets.edit', compact('budget'));
    }

    public function update(Request $request, Budget $budget)
    {
        $validated = $request->validate([
            'program_code' => 'nullable|string|max:255',
            'activity_code' => 'nullable|string|max:255',
            'output_code' => 'nullable|string|max:255',
            'suboutput_code' => 'nullable|string|max:255',
            'component_code' => 'nullable|string|max:255',
            'subcomponent_code' => 'nullable|string|max:255',
            'account_code' => 'nullable|string|max:255',
            'item_code' => 'nullable|string|max:255',
            'coa' => 'required|string|max:255|unique:budgets,coa,' . $budget->id,
            'description' => 'required|string',
            'initial_budget' => 'required|numeric',
            'year' => 'required|string|max:4',
        ]);

        // Recalculate remaining based on new initial and existing realized
        $validated['remaining_budget'] = $validated['initial_budget'] - $budget->realized_budget;

        $budget->update($validated);

        return redirect()->route('budgets.index')->with('success', 'Budget updated successfully.');
    }

    public function destroy(Budget $budget)
    {
        $budget->delete();

        return redirect()->route('budgets.index')->with('success', 'Budget deleted successfully.');
    }
}
