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
use App\Http\Controllers\HonorariumController;

Auth::routes();

Route::get('/', function () {
    if (!Auth::check()) {
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

    // Notification Endpoints (AJAX Polling)
    Route::get('/notifications/fetch', [\App\Http\Controllers\NotificationController::class, 'fetch'])->name('notifications.fetch');
    Route::post('/notifications/mark-read', [\App\Http\Controllers\NotificationController::class, 'markAsRead'])->name('notifications.mark-read');

    // Pengajuan Pembayaran BLU (dummy index + show)
    Route::middleware('role:Super Admin|Operator BLU')->group(function () {
        Route::get('/pengajuan-pembayaran-blu', [BluPaymentSubmissionController::class, 'index'])
            ->name('blu-payment-submissions.index');
        Route::get('/pengajuan-pembayaran-blu/{submissionNumber}', [BluPaymentSubmissionController::class, 'show'])
            ->name('blu-payment-submissions.show');
    });

    // Verifikasi Perjaldin - hanya PPK
    Route::middleware('role:Super Admin|PPK')->group(function () {
        Route::get('/perjaldin-blu', [\App\Http\Controllers\PerjaldinBluController::class, 'index'])->name('perjaldin-blu.index');
        Route::get('/perjaldin-blu/riwayat', [\App\Http\Controllers\PerjaldinBluController::class, 'history'])->name('perjaldin-blu.history');
        Route::get('/perjaldin-blu/{id}', [\App\Http\Controllers\PerjaldinBluController::class, 'show'])->name('perjaldin-blu.show');
        Route::post('/perjaldin-blu/{id}/approve', [\App\Http\Controllers\PerjaldinBluController::class, 'approve'])->name('perjaldin-blu.approve');
        Route::post('/perjaldin-blu/{id}/reject', [\App\Http\Controllers\PerjaldinBluController::class, 'reject'])->name('perjaldin-blu.reject');
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
        Route::get('/contracts/verifikasi', [ContractController::class, 'verifikasiIndex'])->name('contracts.verifikasi');
        Route::resource('contracts', ContractController::class);
        Route::post('/contracts/{contract}/submit', [ContractController::class, 'submit'])->name('contracts.submit');
        Route::post('/contracts/{contract}/approve', [ContractController::class, 'approve'])->name('contracts.approve');
        Route::post('/contracts/{contract}/reject', [ContractController::class, 'reject'])->name('contracts.reject');
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
    // Honorarium Routes
    Route::middleware('role:Super Admin|PPABP')->group(function () {
        Route::get('/honorarium', [HonorariumController::class, 'index'])->name('honorarium.index');
        Route::get('/honorarium/create', [HonorariumController::class, 'create'])->name('honorarium.create');
        Route::post('/honorarium', [HonorariumController::class, 'store'])->name('honorarium.store');

        Route::get('/honorarium/{honorarium}', [HonorariumController::class, 'show'])->name('honorarium.show');
        Route::get('/honorarium/{honorarium}/edit', [HonorariumController::class, 'edit'])->name('honorarium.edit');
        Route::put('/honorarium/{honorarium}', [HonorariumController::class, 'update'])->name('honorarium.update');
        Route::delete('/honorarium/{honorarium}', [HonorariumController::class, 'destroy'])->name('honorarium.destroy');
    });

    // Manajemen Perjaldin — Operator Perjaldin
    Route::middleware('role:Super Admin|Operator Perjaldin')->group(function () {
        Route::get('/perjaldins', [\App\Http\Controllers\PerjaldinController::class, 'index'])->name('perjaldins.index');
        Route::get('/perjaldins/create', [\App\Http\Controllers\PerjaldinController::class, 'create'])->name('perjaldins.create');
        Route::post('/perjaldins', [\App\Http\Controllers\PerjaldinController::class, 'store'])->name('perjaldins.store');
        Route::post('/perjaldins/bulk-submit', [\App\Http\Controllers\PerjaldinController::class, 'bulkSubmit'])->name('perjaldins.bulk-submit');
        // Edit/Delete entire Perjaldin (header + all pejabats)
        Route::get('/perjaldins-header/{perjaldin}/edit', [\App\Http\Controllers\PerjaldinController::class, 'editPerjaldin'])->name('perjaldins.edit-perjaldin');
        Route::put('/perjaldins-header/{perjaldin}', [\App\Http\Controllers\PerjaldinController::class, 'updatePerjaldin'])->name('perjaldins.update-perjaldin');
        Route::delete('/perjaldins-header/{perjaldin}', [\App\Http\Controllers\PerjaldinController::class, 'destroyPerjaldin'])->name('perjaldins.destroy-perjaldin');
        // Single Pejabat detail/edit/delete
        Route::get('/perjaldins/{pejabat}', [\App\Http\Controllers\PerjaldinController::class, 'show'])->name('perjaldins.show');
        Route::get('/perjaldins/{pejabat}/edit', [\App\Http\Controllers\PerjaldinController::class, 'edit'])->name('perjaldins.edit');
        Route::put('/perjaldins/{pejabat}', [\App\Http\Controllers\PerjaldinController::class, 'update'])->name('perjaldins.update');
        Route::delete('/perjaldins/{pejabat}', [\App\Http\Controllers\PerjaldinController::class, 'destroy'])->name('perjaldins.destroy');
    });

    // Verifikasi Perjaldin & SPP — PPK
    Route::middleware('role:Super Admin|PPK')->group(function () {
        Route::get('/verifikasi-ppk', [\App\Http\Controllers\PerjaldinVerifikasiController::class, 'ppkIndex'])->name('verifikasi-ppk.index');
        Route::post('/verifikasi-ppk/{id}/approve', [\App\Http\Controllers\PerjaldinVerifikasiController::class, 'approve'])->name('verifikasi-ppk.approve');
        Route::post('/verifikasi-ppk/{id}/revisi', [\App\Http\Controllers\PerjaldinVerifikasiController::class, 'revisi'])->name('verifikasi-ppk.revisi');
        
        // Verifikasi SPP
        Route::get('/verifikasi-ppk/spp', [\App\Http\Controllers\SppVerifikasiController::class, 'sppIndex'])->name('verifikasi-ppk.spp.index');
        Route::post('/verifikasi-ppk/spp/{spp_id}/approve', [\App\Http\Controllers\SppVerifikasiController::class, 'approveSpp'])->name('verifikasi-ppk.spp.approve');
        Route::post('/verifikasi-ppk/spp/{spp_id}/revisi', [\App\Http\Controllers\SppVerifikasiController::class, 'revisiSpp'])->name('verifikasi-ppk.spp.revisi');

        // Verifikasi NPI
        Route::get('/verifikasi-ppk/npi', [\App\Http\Controllers\NpiController::class, 'verifikasiIndex'])->name('verifikasi-ppk.npi.index');
        Route::post('/verifikasi-ppk/npi/{spp_id}/approve', [\App\Http\Controllers\NpiController::class, 'approve'])->name('verifikasi-ppk.npi.approve');
        Route::post('/verifikasi-ppk/npi/{spp_id}/revisi', [\App\Http\Controllers\NpiController::class, 'revisi'])->name('verifikasi-ppk.npi.revisi');
    });

    // Verifikasi Perjaldin — Kasubag
    Route::middleware('role:Super Admin|Kepala Subbagian Keuangan dan Tata Usaha')->group(function () {
        Route::get('/verifikasi-kasubag', [\App\Http\Controllers\PerjaldinVerifikasiController::class, 'kasubagIndex'])->name('verifikasi-kasubag.index');
        Route::post('/verifikasi-kasubag/{id}/approve', [\App\Http\Controllers\PerjaldinVerifikasiController::class, 'approve'])->name('verifikasi-kasubag.approve');
        Route::post('/verifikasi-kasubag/{id}/revisi', [\App\Http\Controllers\PerjaldinVerifikasiController::class, 'revisi'])->name('verifikasi-kasubag.revisi');
    });

    // ==== VERIFIKASI NPI — Bendahara Penerimaan (TTD) ====
    Route::middleware('role:Super Admin|Bendahara Penerimaan')->group(function () {
        Route::get('/verifikasi-bendahara-penerimaan/npi', [\App\Http\Controllers\NpiController::class, 'penerimaaIndex'])->name('verifikasi-bendahara-penerimaan.npi.index');
        Route::post('/verifikasi-bendahara-penerimaan/npi/{spp_id}/approve', [\App\Http\Controllers\NpiController::class, 'approvePenerimaan'])->name('verifikasi-bendahara-penerimaan.npi.approve');
    });

    // ==== MODUL PEMBUATAN SPP (BLU) ====
    Route::middleware('role:Super Admin|Operator BLU')->group(function () {
        // SPP Perjaldin
        Route::get('/spps/perjaldin', [\App\Http\Controllers\SppController::class, 'perjaldinIndex'])->name('spps.perjaldin.index');
        Route::get('/spps/perjaldin/{perjaldin}/detail', [\App\Http\Controllers\SppController::class, 'detailPerjaldin'])->name('spps.perjaldin.detail');
        Route::post('/spps/perjaldin/{perjaldin}', [\App\Http\Controllers\SppController::class, 'storePerjaldin'])->name('spps.perjaldin.store');

        // SPP Honor
        Route::get('/spps/honor', [\App\Http\Controllers\SppController::class, 'honorIndex'])->name('spps.honor.index');
        Route::get('/spps/honor/{honorarium}/detail', [\App\Http\Controllers\SppController::class, 'detailHonor'])->name('spps.honor.detail');
        Route::post('/spps/honor/{honorarium}', [\App\Http\Controllers\SppController::class, 'storeHonor'])->name('spps.honor.store');

        // SPP Kontrak
        Route::get('/spps/kontrak', [\App\Http\Controllers\SppController::class, 'kontrakIndex'])->name('spps.kontrak.index');
        Route::get('/spps/kontrak/{contract}/detail', [\App\Http\Controllers\SppController::class, 'detailKontrak'])->name('spps.kontrak.detail');
        Route::post('/spps/kontrak/{contract}', [\App\Http\Controllers\SppController::class, 'storeKontrak'])->name('spps.kontrak.store');
    });

    // Cetak PDF SPP/SPM/NPI bisa diakses oleh berbagai role terkait
    Route::middleware('auth')->group(function () {
        Route::get('/spps/{spp}/pdf', [\App\Http\Controllers\SppController::class, 'cetakPdf'])->name('spps.cetak-pdf');
        Route::get('/spms/{spp_id}/pdf', [\App\Http\Controllers\SpmController::class, 'cetakPdfSpm'])->name('spms.cetak-pdf');
        Route::get('/npis/{spp_id}/pdf', [\App\Http\Controllers\NpiController::class, 'cetakPdf'])->name('npis.cetak-pdf');
    });

    // ==== MODUL PEMBUATAN SPM (BLU) ====
    Route::middleware('role:Super Admin|Operator BLU')->group(function () {
        Route::get('/spms', [\App\Http\Controllers\SpmController::class, 'index'])->name('spms.index');
        Route::get('/spms/perjaldin/{perjaldin_id}/detail', [\App\Http\Controllers\SpmController::class, 'detail'])->name('spms.perjaldin.detail');
        Route::post('/spms/spp/{spp_id}/store', [\App\Http\Controllers\SpmController::class, 'store'])->name('spms.store');
    });

    // ==== MODUL VERIFIKASI SPM (PPSPM) ====
    Route::middleware('role:Super Admin|PPSPM')->group(function () {
        Route::get('/verifikasi-ppspm/spm', [\App\Http\Controllers\SpmVerifikasiController::class, 'index'])->name('verifikasi-ppspm.spm.index');
        Route::post('/verifikasi-ppspm/spm/{spp_id}/approve', [\App\Http\Controllers\SpmVerifikasiController::class, 'approve'])->name('verifikasi-ppspm.spm.approve');
        Route::post('/verifikasi-ppspm/spm/{spp_id}/revisi', [\App\Http\Controllers\SpmVerifikasiController::class, 'revisi'])->name('verifikasi-ppspm.spm.revisi');
    });

    // ==== MODUL NPI (Nota Pemindahbukuan Internal) — Bendahara Pengeluaran ====
    Route::middleware('role:Super Admin|Bendahara Pengeluaran')->group(function () {
        Route::get('/npis', [\App\Http\Controllers\NpiController::class, 'index'])->name('npis.index');
        Route::get('/npis/perjaldin/{perjaldin_id}/detail', [\App\Http\Controllers\NpiController::class, 'detail'])->name('npis.perjaldin.detail');
        Route::post('/npis/spp/{spp_id}/store', [\App\Http\Controllers\NpiController::class, 'store'])->name('npis.store');
    });

    // ==== MODUL SP2D & BKU — Bendahara Pengeluaran ====
    Route::middleware('role:Super Admin|Bendahara Pengeluaran')->group(function () {
        Route::get('/sp2ds', [\App\Http\Controllers\Sp2dController::class, 'index'])->name('sp2ds.index');
        Route::get('/sp2ds/perjaldin/{perjaldin_id}/detail', [\App\Http\Controllers\Sp2dController::class, 'detail'])->name('sp2ds.perjaldin.detail');
        Route::post('/sp2ds/spp/{spp_id}/store', [\App\Http\Controllers\Sp2dController::class, 'store'])->name('sp2ds.store');
        Route::post('/sp2ds/spp/{spp_id}/bku', [\App\Http\Controllers\Sp2dController::class, 'catatBku'])->name('sp2ds.catat-bku');
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

    Route::middleware('role:PPK')->group(function () {
    Route::get('/honorarium/ppk/pending', [HonorariumController::class, 'pendingPpk'])
        ->name('honorarium.ppk.pending');

    Route::post('/honorarium/{honorarium}/approve-ppk', [HonorariumController::class, 'approvePpk'])
        ->name('honorarium.approve-ppk');

    Route::post('/honorarium/{honorarium}/reject-ppk', [HonorariumController::class, 'rejectPpk'])
        ->name('honorarium.reject-ppk');
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
