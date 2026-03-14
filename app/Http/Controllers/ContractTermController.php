<?php

namespace App\Http\Controllers;

use App\Models\ContractTerm;
use App\Models\Contract;
use Illuminate\Http\Request;

class ContractTermController extends Controller
{
    public function store(Request $request, Contract $contract)
    {
        $validated = $request->validate([
            'term_name' => 'required|string|max:255',
            'percentage' => 'required|numeric|min:0|max:100',
            'amount' => 'required|numeric|min:0',
        ]);
        
        $validated['status'] = 'Unpaid';

        // Additional validation: Ensure total percentage does not exceed 100%
        $currentPercentage = $contract->terms()->sum('percentage');
        if (($currentPercentage + $validated['percentage']) > 100) {
            return back()->withErrors(['percentage' => 'Total persentase termin melebihi 100%.']);
        }

        $contract->terms()->create($validated);

        return redirect()->route('contracts.show', $contract)->with('success', 'Termin pembayaran berhasil ditambahkan.');
    }

    public function destroy(Contract $contract, ContractTerm $term)
    {
        if ($term->status === 'Paid') {
            return back()->with('error', 'Termin yang sudah dibayar tidak dapat dihapus.');
        }
        $term->delete();
        return redirect()->route('contracts.show', $contract)->with('success', 'Termin berhasil dihapus.');
    }
}
