<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\TransactionTax;
use Illuminate\Http\Request;

class TransactionTaxController extends Controller
{
    public function store(Request $request, Transaction $transaction)
    {
        if ($transaction->status !== 'Draft' && $transaction->status !== 'Rejected') {
            return back()->with('error', 'Pajak hanya dapat ditambahkan pada transaksi berstatus Draft.');
        }

        $validated = $request->validate([
            'tax_type' => 'required|string|max:50',
            'percentage' => 'required|numeric|min:0|max:100',
            'dpp_amount' => 'required|numeric|min:0',
            'tax_amount' => 'required|numeric|min:0',
            'description' => 'nullable|string',
        ]);

        $transaction->taxes()->create($validated);
        $transaction->syncNetAmount();

        return back()->with('success', 'Rincian pajak berhasil ditambahkan.');
    }

    public function destroy(Transaction $transaction, TransactionTax $tax)
    {
        if ($transaction->status !== 'Draft' && $transaction->status !== 'Rejected') {
            return back()->with('error', 'Pajak hanya dapat dihapus pada transaksi berstatus Draft.');
        }

        // Ensure the tax belongs to this transaction
        if ($tax->transaction_id !== $transaction->id) {
             abort(404);
        }

        $tax->delete();
        $transaction->syncNetAmount();

        return back()->with('success', 'Rincian pajak berhasil dihapus.');
    }
}
