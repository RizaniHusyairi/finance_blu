<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class DocumentController extends Controller
{
    // Generate SPP (Surat Permintaan Pembayaran)
    public function printSpp(Transaction $transaction)
    {
        // Require state to be at least Verified
        if (in_array($transaction->status, ['Draft', 'Rejected'])) {
            return back()->with('error', 'Dokumen SPP belum dapat dicetak pada tahap ini.');
        }

        $transaction->load(['contract.supplier', 'budget', 'taxes', 'term']);
        
        $pdf = Pdf::loadView('transactions.documents.spp', compact('transaction'));
        // A4 Paper, portrait
        $pdf->setPaper('a4', 'portrait');
        
        return $pdf->stream('SPP_'.$transaction->transaction_number.'.pdf');
    }

    // Generate SPM (Surat Perintah Membayar)
    public function printSpm(Transaction $transaction)
    {
        // Require state to be at least Approved SPP
        if (in_array($transaction->status, ['Draft', 'Rejected', 'Verified'])) {
            return back()->with('error', 'Dokumen SPM belum dapat dicetak pada tahap ini.');
        }

        $transaction->load(['contract.supplier', 'budget', 'taxes', 'term']);
        
        $pdf = Pdf::loadView('transactions.documents.spm', compact('transaction'));
        $pdf->setPaper('a4', 'portrait');
        
        return $pdf->stream('SPM_'.$transaction->transaction_number.'.pdf');
    }
}
