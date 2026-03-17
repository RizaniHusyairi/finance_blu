<?php

namespace App\Http\Controllers;

use App\Models\BluPaymentSubmission;
use App\Models\TransactionTax;
use Illuminate\Http\Request;

class BluPaymentSubmissionTaxController extends Controller
{
    public function store(Request $request, BluPaymentSubmission $blu_payment_submission)
    {
        if ($blu_payment_submission->status !== 'Draft' && $blu_payment_submission->status !== 'Rejected') {
            return back()->with('error', 'Pajak hanya dapat ditambahkan pada pengajuan berstatus Draft.');
        }

        $validated = $request->validate([
            'tax_type' => 'required|string|max:50',
            'percentage' => 'required|numeric|min:0|max:100',
            'dpp_amount' => 'required|numeric|min:0',
            'tax_amount' => 'required|numeric|min:0',
            'description' => 'nullable|string',
        ]);

        $blu_payment_submission->taxes()->create([
            'tax_name'    => $validated['tax_type'],
            'tax_account' => $validated['description'],
            'amount'      => $validated['tax_amount'],
        ]);
        $blu_payment_submission->syncNetAmount();

        return back()->with('success', 'Rincian pajak berhasil ditambahkan.');
    }

    public function destroy(BluPaymentSubmission $blu_payment_submission, TransactionTax $tax)
    {
        if ($blu_payment_submission->status !== 'Draft' && $blu_payment_submission->status !== 'Rejected') {
            return back()->with('error', 'Pajak hanya dapat dihapus pada pengajuan berstatus Draft.');
        }

        // Ensure the tax belongs to this submission
        if ($tax->transaction_id !== $blu_payment_submission->id) {
             abort(404);
        }

        $tax->delete();
        $blu_payment_submission->syncNetAmount();

        return back()->with('success', 'Rincian pajak berhasil dihapus.');
    }
}
