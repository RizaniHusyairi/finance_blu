<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\Supplier;
use App\Models\Budget;
use Illuminate\Http\Request;

class ContractController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $contracts = Contract::with(['supplier', 'budget'])->get();
        return view('contracts.index', compact('contracts'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $suppliers = Supplier::all();
        $budgets = Budget::all();
        return view('contracts.create', compact('suppliers', 'budgets'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'contract_number' => 'required|string|max:255|unique:contracts,contract_number',
            'date' => 'required|date',
            'description' => 'required|string',
            'supplier_id' => 'required|exists:suppliers,id',
            'budget_id' => 'required|exists:budgets,id',
            'total_amount' => 'required|numeric',
            'status' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'type' => 'nullable|string',
        ]);

        Contract::create($validated);

        return redirect()->route('contracts.index')->with('success', 'Contract created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Contract $contract)
    {
        $contract->load(['supplier', 'budget', 'addendums', 'terms', 'transactions']);
        return view('contracts.show', compact('contract'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Contract $contract)
    {
        $suppliers = Supplier::all();
        $budgets = Budget::all();
        return view('contracts.edit', compact('contract', 'suppliers', 'budgets'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Contract $contract)
    {
         $validated = $request->validate([
            'contract_number' => 'required|string|max:255|unique:contracts,contract_number,' . $contract->id,
            'date' => 'required|date',
            'description' => 'required|string',
            'supplier_id' => 'required|exists:suppliers,id',
            'budget_id' => 'required|exists:budgets,id',
            'total_amount' => 'required|numeric',
            'status' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'type' => 'nullable|string',
        ]);

        $contract->update($validated);

        return redirect()->route('contracts.index')->with('success', 'Contract updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Contract $contract)
    {
        $contract->delete();

        return redirect()->route('contracts.index')->with('success', 'Contract deleted successfully.');
    }
}
