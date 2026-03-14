<?php

namespace App\Http\Controllers;

use App\Models\ContractAddendum;
use App\Models\Contract;
use Illuminate\Http\Request;

class ContractAddendumController extends Controller
{
    public function store(Request $request, Contract $contract)
    {
        $validated = $request->validate([
            'addendum_number' => 'required|string|max:255',
            'date' => 'required|date',
            'new_total_amount' => 'nullable|numeric',
            'new_end_date' => 'nullable|date',
            'reason' => 'required|string',
        ]);

        $contract->addendums()->create($validated);

        // Update contract's current total amount and end date if provided in addendum
        $updates = [];
        if ($request->filled('new_total_amount')) {
            $updates['total_amount'] = $validated['new_total_amount'];
        }
        if ($request->filled('new_end_date')) {
            $updates['end_date'] = $validated['new_end_date'];
        }

        if (!empty($updates)) {
            $contract->update($updates);
        }

        return redirect()->route('contracts.show', $contract)->with('success', 'Addendum berhasil ditambahkan.');
    }

    public function destroy(Contract $contract, ContractAddendum $addendum)
    {
        $addendum->delete();
        // Note: Ideally deleting an addendum should revert the contract's total_amount/end_date to a previous state, 
        // but for simplicity in this prototype, we just delete the record.
        return redirect()->route('contracts.show', $contract)->with('success', 'Addendum berhasil dihapus.');
    }
}
