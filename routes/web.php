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
use App\Http\Controllers\TransactionController;
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
    Route::middleware("role:$internalRoles")->group(function () {
        // Internal Dashboard (KPA, PPK, Operator BLU, Bendahara)
        Route::get('/dashboard', [DashboardController::class, 'internal'])->name('dashboard');

        Route::middleware('role:Super Admin|Operator BLU')->group(function () {
            Route::get('/pengajuan-pembayaran-blu', [BluPaymentSubmissionController::class, 'index'])
                ->name('blu-payment-submissions.index');
            Route::get('/pengajuan-pembayaran-blu/{submissionNumber}', [BluPaymentSubmissionController::class, 'show'])
                ->name('blu-payment-submissions.show');
        });

        // Master Data Routes
        Route::resource('employees', EmployeeController::class);
        Route::resource('suppliers', SupplierController::class);
        Route::resource('budgets', BudgetController::class);

        // Contract Management Route
        Route::resource('contracts', ContractController::class);

        // Contract Addendums & Terms
        Route::post('/contracts/{contract}/addendums', [ContractAddendumController::class, 'store'])->name('addendums.store');
        Route::delete('/contracts/{contract}/addendums/{addendum}', [ContractAddendumController::class, 'destroy'])->name('addendums.destroy');

        Route::post('/contracts/{contract}/terms', [ContractTermController::class, 'store'])->name('terms.store');
        Route::delete('/contracts/{contract}/terms/{term}', [ContractTermController::class, 'destroy'])->name('terms.destroy');

        // Transaction Engine
        Route::resource('transactions', TransactionController::class);
        Route::post('/transactions/{transaction}/submit', [TransactionController::class, 'submit'])->name('transactions.submit');
        Route::post('/transactions/{transaction}/approve', [TransactionController::class, 'approve'])->name('transactions.approve');
        Route::post('/transactions/{transaction}/reject', [TransactionController::class, 'reject'])->name('transactions.reject');
        Route::post('/transactions/{transaction}/taxes', [\App\Http\Controllers\TransactionTaxController::class, 'store'])->name('transactions.taxes.store');
        Route::delete('/transactions/{transaction}/taxes/{tax}', [\App\Http\Controllers\TransactionTaxController::class, 'destroy'])->name('transactions.taxes.destroy');

        // Document Generation (PDF)
        Route::get('/transactions/{transaction}/print-spp', [\App\Http\Controllers\DocumentController::class, 'printSpp'])->name('transactions.print.spp');
        Route::get('/transactions/{transaction}/print-spm', [\App\Http\Controllers\DocumentController::class, 'printSpm'])->name('transactions.print.spm');

        // Reports
        Route::get('/reports/bku', [\App\Http\Controllers\ReportController::class, 'bku'])->name('reports.bku');
        Route::get('/reports/bku/pdf', [\App\Http\Controllers\ReportController::class, 'bkuPdf'])->name('reports.bku.pdf');
    });

    Route::middleware('role:Mitra')->group(function () {
        // Mitra Portal Dashboard
        Route::get('/mitra', [DashboardController::class, 'mitra'])->name('mitra.dashboard');
    });
});
// Template Preview Route
Route::get('/template', [HomeController::class, 'index'])->name('template.dashboard');

Route::get('{any}', [HomeController::class, 'root'])->where('any', '.*');
