<?php

namespace App\Http\Controllers;

use App\Models\BluPaymentSubmission;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class DocumentController extends Controller
{
    // Generate SPP (Surat Permintaan Pembayaran)
    public function printSpp(BluPaymentSubmission $blu_payment_submission)
    {
        // Require state to be at least Verified
        if (in_array($blu_payment_submission->status, ['Draft', 'Rejected'])) {
            return back()->with('error', 'Dokumen SPP belum dapat dicetak pada tahap ini.');
        }

        $blu_payment_submission->load(['contract.supplier', 'budget', 'taxes', 'term']);
        
        $pdf = Pdf::loadView('blu-payment-submissions.documents.spp', ['transaction' => $blu_payment_submission]);
        // A4 Paper, portrait
        $pdf->setPaper('a4', 'portrait');
        
        return $pdf->stream('SPP_'.$blu_payment_submission->transaction_number.'.pdf');
    }

    // Generate SPM (Surat Perintah Membayar)
    public function printSpm(BluPaymentSubmission $blu_payment_submission)
    {
        // Require state to be at least Approved SPP
        if (in_array($blu_payment_submission->status, ['Draft', 'Rejected', 'Verified'])) {
            return back()->with('error', 'Dokumen SPM belum dapat dicetak pada tahap ini.');
        }

        $blu_payment_submission->load(['contract.supplier', 'budget', 'taxes', 'term']);
        
        $pdf = Pdf::loadView('blu-payment-submissions.documents.spm', ['transaction' => $blu_payment_submission]);
        $pdf->setPaper('a4', 'portrait');
        
        return $pdf->stream('SPM_'.$blu_payment_submission->transaction_number.'.pdf');
    }
}
