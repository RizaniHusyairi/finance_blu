<?php

use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BluPaymentSubmissionController;
use App\Http\Controllers\BudgetController;
use App\Http\Controllers\ContractController;
use App\Http\Controllers\ContractAddendumController;
use App\Http\Controllers\ContractTermController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\SupplierController;
use Illuminate\Support\Facades\Auth;

Auth::routes();

Route::get('/', function () {
    if (! Auth::check()) {
        return redirect()->route('login');
    }

    return Auth::user()->hasRole('Mitra')
        ? redirect()->route('mitra.dashboard')
        : redirect()->route('dashboard');
});

$internalRoles = 'Super Admin|KPA|Kepala Subbagian Keuangan dan Tata Usaha|Kepala Seksi Pelayanan dan Kerjasama|PPK|PPSPM|Bendahara Pengeluaran|Bendahara Penerimaan|Pejabat Pengadaan|Operator BLU|PPABP|Operator Perjaldin';

Route::middleware('auth')->group(function () use ($internalRoles) {

    // Internal Dashboard — all internal roles
    Route::middleware("role:$internalRoles")->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'internal'])->name('dashboard');
    });

    // Pengajuan Pembayaran BLU (dummy index + show)
    Route::middleware('role:Super Admin|Operator BLU')->group(function () {
        Route::get('/pengajuan-pembayaran-blu', [BluPaymentSubmissionController::class, 'index'])
            ->name('blu-payment-submissions.index');
        Route::get('/pengajuan-pembayaran-blu/{submissionNumber}', [BluPaymentSubmissionController::class, 'show'])
            ->name('blu-payment-submissions.show');
    });

    // Master Data — Pegawai & Pejabat
    Route::middleware('role:Super Admin|Operator BLU|PPABP')->group(function () {
        Route::resource('employees', EmployeeController::class);
    });

    // Master Data — Supplier / Mitra
    Route::middleware('role:Super Admin|Pejabat Pengadaan')->group(function () {
        Route::resource('suppliers', SupplierController::class);
    });

    // Master Data — Pagu Anggaran
    Route::middleware('role:Super Admin|KPA|Operator BLU|Kepala Subbagian Keuangan dan Tata Usaha|Kepala Seksi Pelayanan dan Kerjasama')->group(function () {
        Route::resource('budgets', BudgetController::class);
    });

    // Manajemen Kontrak (Kontrak, Addendum, Termin)
    Route::middleware('role:Super Admin|Pejabat Pengadaan|PPK')->group(function () {
        Route::resource('contracts', ContractController::class);
        Route::get('/contracts/{contract}/addendums/create', [ContractAddendumController::class, 'create'])->name('addendums.create');
        Route::post('/contracts/{contract}/addendums', [ContractAddendumController::class, 'store'])->name('addendums.store');
        Route::post('/contracts/{contract}/addendums/{addendum}/submit', [ContractAddendumController::class, 'submit'])->name('addendums.submit');
        Route::post('/contracts/{contract}/addendums/{addendum}/approve', [ContractAddendumController::class, 'approve'])->name('addendums.approve');
        Route::post('/contracts/{contract}/addendums/{addendum}/reject', [ContractAddendumController::class, 'reject'])->name('addendums.reject');
        Route::delete('/contracts/{contract}/addendums/{addendum}', [ContractAddendumController::class, 'destroy'])->name('addendums.destroy');
        Route::post('/contracts/{contract}/terms', [ContractTermController::class, 'store'])->name('terms.store');
        Route::delete('/contracts/{contract}/terms/{term}', [ContractTermController::class, 'destroy'])->name('terms.destroy');

        // Contract approval workflow
        Route::post('/contracts/{contract}/submit', [ContractController::class, 'submit'])->name('contracts.submit');
        Route::post('/contracts/{contract}/approve', [ContractController::class, 'approve'])->name('contracts.approve');
        Route::post('/contracts/{contract}/reject', [ContractController::class, 'reject'])->name('contracts.reject');
    });

    // Tagihan & Bayar — Pengajuan Pembayaran BLU (CRUD + Workflow)
    Route::middleware('role:Super Admin|Operator BLU|PPABP|PPK|PPSPM|Bendahara Pengeluaran|Bendahara Penerimaan')->group(function () {
        Route::get('/blu-payment-submissions/create', [BluPaymentSubmissionController::class, 'create'])->name('blu-payment-submissions.create');
        Route::post('/blu-payment-submissions', [BluPaymentSubmissionController::class, 'store'])->name('blu-payment-submissions.store');
        Route::get('/blu-payment-submissions/{blu_payment_submission}', [BluPaymentSubmissionController::class, 'showDetail'])->name('blu-payment-submissions.show-detail');
        Route::get('/blu-payment-submissions/{blu_payment_submission}/edit', [BluPaymentSubmissionController::class, 'edit'])->name('blu-payment-submissions.edit');
        Route::put('/blu-payment-submissions/{blu_payment_submission}', [BluPaymentSubmissionController::class, 'update'])->name('blu-payment-submissions.update');
        Route::delete('/blu-payment-submissions/{blu_payment_submission}', [BluPaymentSubmissionController::class, 'destroy'])->name('blu-payment-submissions.destroy');

        // Workflow actions
        Route::post('/blu-payment-submissions/{blu_payment_submission}/submit', [BluPaymentSubmissionController::class, 'submit'])->name('blu-payment-submissions.submit');
        Route::post('/blu-payment-submissions/{blu_payment_submission}/approve', [BluPaymentSubmissionController::class, 'approve'])->name('blu-payment-submissions.approve');
        Route::post('/blu-payment-submissions/{blu_payment_submission}/reject', [BluPaymentSubmissionController::class, 'reject'])->name('blu-payment-submissions.reject');

        // Tax management
        Route::post('/blu-payment-submissions/{blu_payment_submission}/taxes', [\App\Http\Controllers\BluPaymentSubmissionTaxController::class, 'store'])->name('blu-payment-submissions.taxes.store');
        Route::delete('/blu-payment-submissions/{blu_payment_submission}/taxes/{tax}', [\App\Http\Controllers\BluPaymentSubmissionTaxController::class, 'destroy'])->name('blu-payment-submissions.taxes.destroy');

        // Document Generation (PDF)
        Route::get('/blu-payment-submissions/{blu_payment_submission}/print-spp', [\App\Http\Controllers\DocumentController::class, 'printSpp'])->name('blu-payment-submissions.print.spp');
        Route::get('/blu-payment-submissions/{blu_payment_submission}/print-spm', [\App\Http\Controllers\DocumentController::class, 'printSpm'])->name('blu-payment-submissions.print.spm');
    });

    // Laporan BKU
    Route::middleware('role:Super Admin|KPA|Kepala Subbagian Keuangan dan Tata Usaha|Kepala Seksi Pelayanan dan Kerjasama|PPK|Bendahara Pengeluaran|Bendahara Penerimaan')->group(function () {
        Route::get('/reports/bku', [\App\Http\Controllers\ReportController::class, 'bku'])->name('reports.bku');
        Route::get('/reports/bku/pdf', [\App\Http\Controllers\ReportController::class, 'bkuPdf'])->name('reports.bku.pdf');
    });

    // Mitra Portal
    Route::middleware('role:Mitra')->group(function () {
        Route::get('/mitra', [DashboardController::class, 'mitra'])->name('mitra.dashboard');
    });
});
// Template Preview Route
Route::get('/template', [HomeController::class, 'index'])->name('template.dashboard');

Route::get('{any}', [HomeController::class, 'root'])->where('any', '.*');
