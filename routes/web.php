<?php

use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ContractController;
use App\Http\Controllers\ContractAddendumController;
use App\Http\Controllers\ContractTermController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CoaController;
use App\Http\Controllers\DipaController;
use App\Http\Controllers\DocumentNumberController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\SupplierController;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\HonorariumController;
use App\Http\Controllers\MasterTarifPajakController;

Auth::routes();

Route::get('/', function () {
    if (!Auth::check()) {
        return redirect()->route('login');
    }

    return Auth::user()->hasRole('Mitra')
        ? redirect()->route('mitra.dashboard')
        : redirect()->route('dashboard');
});

$internalRoles = 'Super Admin|KPA|Kepala Subbagian Keuangan dan Tata Usaha|Kepala Seksi Pelayanan dan Kerjasama|PPK|PPSPM|Bendahara Pengeluaran|Bendahara Penerimaan|Pejabat Pengadaan|Operator BLU|PPABP|Operator Perjaldin|Koordinator Keuangan';

Route::middleware('auth')->group(function () use ($internalRoles) {

    // Internal Dashboard — all internal roles
    Route::middleware("role:$internalRoles")->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'internal'])->name('dashboard');
        Route::get('/dashboard/bendahara-penerimaan', [\App\Http\Controllers\BendaharaPenerimaanDashboardController::class, 'index'])->name('dashboard.bendahara-penerimaan');
        Route::get('/dashboard/bendahara-pengeluaran', [\App\Http\Controllers\BendaharaPengeluaranDashboardController::class, 'index'])->name('dashboard.bendahara-pengeluaran');
    });

    // Workflow Engine General Routes
    Route::post('/workflow/approval/{approvalId}/approve', [\App\Http\Controllers\PerjaldinWorkflowController::class, 'approve'])->name('perjaldin.workflow.approve');
    Route::post('/workflow/approval/{approvalId}/revision', [\App\Http\Controllers\PerjaldinWorkflowController::class, 'revision'])->name('perjaldin.workflow.revision');
    Route::post('/workflow/approval/{approvalId}/reject', [\App\Http\Controllers\PerjaldinWorkflowController::class, 'reject'])->name('perjaldin.workflow.reject');

    // Notification Endpoints (AJAX Polling)
    Route::get('/notifications/fetch', [\App\Http\Controllers\NotificationController::class, 'fetch'])->name('notifications.fetch');
    Route::post('/notifications/mark-read', [\App\Http\Controllers\NotificationController::class, 'markAsRead'])->name('notifications.mark-read');

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
        Route::get('/document-numbers', [DocumentNumberController::class, 'index'])->name('document-numbers.index');
        Route::post('/document-numbers', [DocumentNumberController::class, 'store'])->name('document-numbers.store');
        Route::post('/document-numbers/reserve', [DocumentNumberController::class, 'reserve'])->name('document-numbers.reserve');
        Route::post('/document-numbers/{documentNumber}/release', [DocumentNumberController::class, 'release'])->name('document-numbers.release');
        Route::post('/document-numbers/{documentNumber}/mark-used', [DocumentNumberController::class, 'markUsed'])->name('document-numbers.mark-used');
        Route::post('/document-numbers/{documentNumber}/cancel', [DocumentNumberController::class, 'cancel'])->name('document-numbers.cancel');
        Route::get('/document-numbers/check', [DocumentNumberController::class, 'check'])->name('document-numbers.check');
    });

    // Master Data — DIPA
    Route::middleware('role:Super Admin|KPA|Operator BLU|Kepala Subbagian Keuangan dan Tata Usaha|Kepala Seksi Pelayanan dan Kerjasama')->group(function () {
        Route::get('/dipas', [DipaController::class, 'index'])->name('dipas.index');
        Route::get('/dipas/create', [DipaController::class, 'create'])->name('dipas.create');
        Route::post('/dipas', [DipaController::class, 'store'])->name('dipas.store');
        Route::get('/coas/create', [CoaController::class, 'create'])->name('coas.create');
        Route::post('/coas', [CoaController::class, 'store'])->name('coas.store');
        Route::get('/coas', [CoaController::class, 'index'])->name('coas.index');
        Route::get('/coas/{coa}', [CoaController::class, 'show'])->name('coas.show');
        Route::get('/coas/{coa}/edit', [CoaController::class, 'edit'])->name('coas.edit');
        Route::put('/coas/{coa}', [CoaController::class, 'update'])->name('coas.update');
        Route::post('/coas/{coa}/toggle', [CoaController::class, 'toggle'])->name('coas.toggle');
        Route::delete('/coas/{coa}', [CoaController::class, 'destroy'])->name('coas.destroy');
        Route::get('/dipas/{dipa}', [DipaController::class, 'show'])->name('dipas.show');
        Route::get('/dipas/{dipa}/edit', [DipaController::class, 'edit'])->name('dipas.edit');
        Route::get('/dipas/{dipa}/revisions', [DipaController::class, 'revisions'])->name('dipas.revisions');
        Route::get('/dipas/{dipa}/revisions/create', [DipaController::class, 'createRevision'])->name('dipas.revisions.create');
        Route::post('/dipas/{dipa}/revisions', [DipaController::class, 'storeRevision'])->name('dipas.revisions.store');
        Route::post('/dipas/{dipa}/toggle', [DipaController::class, 'toggle'])->name('dipas.toggle');
        Route::post('/dipas/{dipa}/items', [DipaController::class, 'storeItem'])->name('dipas.items.store');
        Route::post('/dipas/{dipa}/items/{item}/toggle', [DipaController::class, 'toggleItem'])->name('dipas.items.toggle');
        Route::delete('/dipas/{dipa}/items/{item}', [DipaController::class, 'destroyItem'])->name('dipas.items.destroy');
        Route::post('/dipas/{dipa}/revisions/{revision}/activate', [DipaController::class, 'activateRevision'])->name('dipas.revisions.activate');
    });

    // Master Data — Pajak
    Route::middleware('role:Super Admin|Operator BLU')->group(function () {
        Route::get('/master/pajak', [MasterTarifPajakController::class, 'index'])->name('master-pajak.index');
        Route::get('/master/pajak/create', [MasterTarifPajakController::class, 'create'])->name('master-pajak.create');
        Route::post('/master/pajak', [MasterTarifPajakController::class, 'store'])->name('master-pajak.store');
        Route::get('/master/pajak/{pajak}', [MasterTarifPajakController::class, 'show'])->name('master-pajak.show');
        Route::get('/master/pajak/{pajak}/edit', [MasterTarifPajakController::class, 'edit'])->name('master-pajak.edit');
        Route::put('/master/pajak/{pajak}', [MasterTarifPajakController::class, 'update'])->name('master-pajak.update');
        Route::post('/master/pajak/{pajak}/toggle', [MasterTarifPajakController::class, 'toggle'])->name('master-pajak.toggle');
    });

    // Manajemen Kontrak (Kontrak, Addendum, Termin)
    Route::middleware('role:Super Admin|Pejabat Pengadaan|PPK')->group(function () {
        Route::get('/tagihan/kontrak/create', [\App\Http\Controllers\TagihanController::class, 'createKontrak'])->name('tagihan.kontrak.create');
        Route::post('/tagihan/kontrak/store', [\App\Http\Controllers\TagihanController::class, 'storeKontrak'])->name('tagihan.kontrak.store');
        Route::get('/tagihan/kontrak/{id}', [\App\Http\Controllers\TagihanController::class, 'showKontrak'])->name('tagihan.kontrak.show');
        Route::post('/tagihan/kontrak/{id}/submit', [\App\Http\Controllers\TagihanController::class, 'submitKontrak'])->name('tagihan.kontrak.submit');
        Route::post('/tagihan/kontrak/{id}/arsip', [\App\Http\Controllers\TagihanController::class, 'uploadArsipKontrak'])->name('tagihan.kontrak.upload-arsip');
        Route::get('/tagihan/kontrak/{id}/arsip/{arsipId}', [\App\Http\Controllers\TagihanController::class, 'viewArsipKontrak'])->name('tagihan.kontrak.view-arsip');
        Route::get('/tagihan/kontrak/{id}/export/{type}', [\App\Http\Controllers\TagihanController::class, 'exportPdfKontrak'])->name('tagihan.kontrak.export-pdf');
    });

    // Verifikasi Tagihan Kontrak — multi-role (PPK, PPSPM, Koor.Keu, Bend×2, Kasubbag)
    Route::middleware('role:PPK|PPSPM|Koordinator Keuangan|Bendahara Pengeluaran|Bendahara Penerimaan|Kepala Subbagian Keuangan dan Tata Usaha|Super Admin')->group(function () {
        Route::get('/verifikasi-tagihan-kontrak', [\App\Http\Controllers\TagihanKontrakVerifikasiController::class, 'index'])->name('verifikasi-tagihan-kontrak.index');
        Route::get('/verifikasi-tagihan-kontrak/{id}', [\App\Http\Controllers\TagihanKontrakVerifikasiController::class, 'show'])->name('verifikasi-tagihan-kontrak.show');
        Route::get('/verifikasi-tagihan-kontrak/{id}/kontrak-arsip/{jenis}', [\App\Http\Controllers\TagihanKontrakVerifikasiController::class, 'viewKontrakArsip'])->name('verifikasi-tagihan-kontrak.kontrak-arsip');
        Route::post('/verifikasi-tagihan-kontrak/{id}/approve', [\App\Http\Controllers\TagihanKontrakVerifikasiController::class, 'approve'])->name('verifikasi-tagihan-kontrak.approve');
        Route::post('/verifikasi-tagihan-kontrak/{id}/revisi', [\App\Http\Controllers\TagihanKontrakVerifikasiController::class, 'revisi'])->name('verifikasi-tagihan-kontrak.revisi');
        Route::post('/verifikasi-tagihan-kontrak/{id}/reject', [\App\Http\Controllers\TagihanKontrakVerifikasiController::class, 'reject'])->name('verifikasi-tagihan-kontrak.reject');
    });

    Route::middleware('role:Super Admin|Pejabat Pengadaan|PPK')->group(function () {

        Route::get('/contracts/verifikasi', [ContractController::class, 'verifikasiIndex'])->name('contracts.verifikasi');
        Route::get('/contracts/verifikasi/{id}', [ContractController::class, 'verifikasiShow'])->name('contracts.verifikasi.show');
        Route::get('/contracts/{contract}/ringkasan-kontrak/export-pdf', [ContractController::class, 'exportRingkasanKontrakPdf'])->name('contracts.ringkasan.export-pdf');
        Route::post('/contracts/{contract}/ringkasan-kontrak/upload-final', [ContractController::class, 'uploadRingkasanKontrakFinal'])->name('contracts.ringkasan.upload-final');
        Route::get('/contracts/{contract}/spk/export-pdf', [ContractController::class, 'exportSpkPdf'])->name('contracts.spk.export-pdf');
        Route::post('/contracts/{contract}/spk/upload-gambar-rab', [ContractController::class, 'uploadSpkGambarRab'])->name('contracts.spk.upload-gambar-rab');
        Route::get('/contracts/{contract}/spk/gambar-rab', [ContractController::class, 'viewSpkGambarRab'])->name('contracts.spk.gambar-rab');
        Route::post('/contracts/{contract}/spk/upload-final', [ContractController::class, 'uploadSpkFinal'])->name('contracts.spk.upload-final');
        Route::get('/contracts/{contract}/spmk/export-pdf', [ContractController::class, 'exportSpmkPdf'])->name('contracts.spmk.export-pdf');
        Route::post('/contracts/{contract}/spmk/upload-final', [ContractController::class, 'uploadSpmkFinal'])->name('contracts.spmk.upload-final');
        Route::resource('contracts', ContractController::class);

        Route::post('/contracts/{contract}/submit', [ContractController::class, 'submit'])->name('contracts.submit');
        Route::post('/contracts/{contract}/approve', [ContractController::class, 'approve'])->name('contracts.approve');
        Route::post('/contracts/{contract}/reject', [ContractController::class, 'reject'])->name('contracts.reject');
        Route::get('/contracts/{contract}/addendums', [ContractAddendumController::class, 'index'])->name('addendums.index');
        Route::get('/contracts/{contract}/addendums/create', [ContractAddendumController::class, 'create'])->name('addendums.create');
        Route::post('/contracts/{contract}/addendums', [ContractAddendumController::class, 'store'])->name('addendums.store');
        Route::get('/contracts/{contract}/addendums/{addendum}', [ContractAddendumController::class, 'show'])->name('addendums.show');
        Route::get('/contracts/{contract}/addendums/{addendum}/edit', [ContractAddendumController::class, 'edit'])->name('addendums.edit');
        Route::put('/contracts/{contract}/addendums/{addendum}', [ContractAddendumController::class, 'update'])->name('addendums.update');
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
        Route::get('/honorarium/{id}', [HonorariumController::class, 'show'])->name('honorarium.show');
        Route::get('/honorarium/{id}/edit', [HonorariumController::class, 'edit'])->name('honorarium.edit');
        Route::put('/honorarium/{id}', [HonorariumController::class, 'update'])->name('honorarium.update');
        Route::delete('/honorarium/{id}', [HonorariumController::class, 'destroy'])->name('honorarium.destroy');
        Route::post('/honorarium/{id}/dokumen-upload', [HonorariumController::class, 'uploadDokumen'])->name('honorarium.dokumen.upload');
        Route::delete('/honorarium/{id}/dokumen-delete/{arsip_id}', [HonorariumController::class, 'deleteDokumen'])->name('honorarium.dokumen.delete');
        Route::post('/honorarium/{id}/submit-verifikasi', [HonorariumController::class, 'submitVerifikasi'])->name('honorarium.submit-verifikasi');
        Route::get('/honorarium/{id}/pdf', [HonorariumController::class, 'exportPdf'])->name('honorarium.pdf');
        Route::get('/honorarium/{id}/pdf-nominatif', [HonorariumController::class, 'exportNominatifPdf'])->name('honorarium.pdf-nominatif');
    });

    // Manajemen Perjaldin — Operator Perjaldin
    Route::middleware('role:Super Admin|Operator Perjaldin')->group(function () {
        Route::get('/perjaldins', [\App\Http\Controllers\PerjaldinController::class, 'index'])->name('perjaldins.index');
        Route::get('/perjaldins/create', [\App\Http\Controllers\PerjaldinController::class, 'create'])->name('perjaldins.create');
        Route::post('/perjaldins', [\App\Http\Controllers\PerjaldinController::class, 'store'])->name('perjaldins.store');
        Route::post('/perjaldins/bulk-submit', [\App\Http\Controllers\PerjaldinController::class, 'bulkSubmit'])->name('perjaldins.bulk-submit');
        Route::get('/perjaldins/{id}', [\App\Http\Controllers\PerjaldinController::class, 'show'])->name('perjaldins.show');
        Route::get('/perjaldins/{id}/edit', [\App\Http\Controllers\PerjaldinController::class, 'editPerjaldin'])->name('perjaldins.edit-perjaldin');
        Route::put('/perjaldins/{id}', [\App\Http\Controllers\PerjaldinController::class, 'updatePerjaldin'])->name('perjaldins.update-perjaldin');
        Route::delete('/perjaldins/{id}', [\App\Http\Controllers\PerjaldinController::class, 'destroyPerjaldin'])->name('perjaldins.destroy-perjaldin');
        Route::get('/perjaldins/{id}/pdf', [\App\Http\Controllers\PerjaldinController::class, 'exportPdf'])->name('perjaldins.pdf');

        // Master Data Uang Harian Perjaldin
        Route::resource('master-uang-harian-perjaldin', \App\Http\Controllers\MasterUangHarianPerjaldinController::class)
             ->names('master-uang-harian-perjaldin')
             ->except(['show']);        // Perjaldin Workflow
        Route::post('/perjaldins/{id}/workflow/submit', [\App\Http\Controllers\PerjaldinWorkflowController::class, 'submit'])->name('perjaldin.workflow.submit');
        Route::post('/perjaldins/workflow/approval/{approvalId}/approve', [\App\Http\Controllers\PerjaldinWorkflowController::class, 'approve'])->name('perjaldin.workflow.approve');
        Route::post('/perjaldins/workflow/approval/{approvalId}/revision', [\App\Http\Controllers\PerjaldinWorkflowController::class, 'revision'])->name('perjaldin.workflow.revision');
        Route::post('/perjaldins/workflow/approval/{approvalId}/reject', [\App\Http\Controllers\PerjaldinWorkflowController::class, 'reject'])->name('perjaldin.workflow.reject');

    });

    // Verifikasi Perjaldin & SPP — PPK
    Route::middleware('role:Super Admin|PPK')->group(function () {
        // Verifikasi Tagihan Kontrak (BAST)
        Route::get('/verifikasi-ppk/tagihan-kontrak', [\App\Http\Controllers\TagihanController::class, 'ppkIndex'])->name('ppk.tagihan.kontrak.index');
        Route::get('/verifikasi-ppk/tagihan-kontrak/{id}/verify', [\App\Http\Controllers\TagihanController::class, 'verifyKontrak'])->name('ppk.tagihan.kontrak.verify');
        Route::post('/verifikasi-ppk/tagihan-kontrak/{id}/approve', [\App\Http\Controllers\TagihanController::class, 'approveKontrak'])->name('ppk.tagihan.kontrak.approve');
        Route::post('/verifikasi-ppk/tagihan-kontrak/{id}/reject', [\App\Http\Controllers\TagihanController::class, 'rejectKontrak'])->name('ppk.tagihan.kontrak.reject');

        // Verifikasi Perjaldin — PPK (halaman baru)
        Route::get('/verifikasi-ppk/perjaldin', [\App\Http\Controllers\PerjaldinVerifikasiController::class, 'ppkIndex'])->name('verifikasi-ppk.perjaldin.index');
        Route::get('/verifikasi-ppk/perjaldin/{id}', [\App\Http\Controllers\PerjaldinVerifikasiController::class, 'ppkShow'])->name('verifikasi-ppk.perjaldin.show');
        Route::post('/verifikasi-ppk/perjaldin/{id}/approve', [\App\Http\Controllers\PerjaldinVerifikasiController::class, 'approve'])->name('verifikasi-ppk.perjaldin.approve');
        Route::post('/verifikasi-ppk/perjaldin/{id}/revisi', [\App\Http\Controllers\PerjaldinVerifikasiController::class, 'revisi'])->name('verifikasi-ppk.perjaldin.revisi');
        // Legacy redirect
        Route::get('/verifikasi-ppk', fn() => redirect()->route('verifikasi-ppk.perjaldin.index'))->name('verifikasi-ppk.index');
        

        // Verifikasi NPI
        Route::get('/verifikasi-ppk/npi', [\App\Http\Controllers\NpiController::class, 'verifikasiIndex'])->name('verifikasi-ppk.npi.index');
        Route::post('/verifikasi-ppk/npi/{npi_id}/approve', [\App\Http\Controllers\NpiController::class, 'approve'])->name('verifikasi-ppk.npi.approve');
        Route::post('/verifikasi-ppk/npi/{npi_id}/revisi', [\App\Http\Controllers\NpiController::class, 'revisi'])->name('verifikasi-ppk.npi.revisi');

        // NPI Kontrak — Verifikasi PPK (Parallel Workflow)
        Route::get('/verifikasi-ppk/npi/kontrak', [\App\Http\Controllers\PpkNpiKontrakVerifikasiController::class, 'index'])->name('verifikasi-ppk.npi.kontrak.index');
        Route::get('/verifikasi-ppk/npi/kontrak/{id}', [\App\Http\Controllers\PpkNpiKontrakVerifikasiController::class, 'show'])->name('verifikasi-ppk.npi.kontrak.show');
        Route::post('/verifikasi-ppk/npi/kontrak/{id}/approve', [\App\Http\Controllers\PpkNpiKontrakVerifikasiController::class, 'approve'])->name('verifikasi-ppk.npi.kontrak.approve');
        Route::post('/verifikasi-ppk/npi/kontrak/{id}/revisi', [\App\Http\Controllers\PpkNpiKontrakVerifikasiController::class, 'revisi'])->name('verifikasi-ppk.npi.kontrak.revisi');

        // SP2D Kontrak — Verifikasi PPK (Parallel Workflow)
        Route::get('/verifikasi-ppk/sp2d/kontrak', [\App\Http\Controllers\PpkSp2dKontrakVerifikasiController::class, 'index'])->name('verifikasi-ppk.sp2d.kontrak.index');
        Route::get('/verifikasi-ppk/sp2d/kontrak/{id}', [\App\Http\Controllers\PpkSp2dKontrakVerifikasiController::class, 'show'])->name('verifikasi-ppk.sp2d.kontrak.show');
        Route::post('/verifikasi-ppk/sp2d/kontrak/{id}/approve', [\App\Http\Controllers\PpkSp2dKontrakVerifikasiController::class, 'approve'])->name('verifikasi-ppk.sp2d.kontrak.approve');
        Route::post('/verifikasi-ppk/sp2d/kontrak/{id}/revisi', [\App\Http\Controllers\PpkSp2dKontrakVerifikasiController::class, 'revisi'])->name('verifikasi-ppk.sp2d.kontrak.revisi');

        // Honorarium - Verifikasi PPK (Parallel Workflow)
        Route::get('/verifikasi-ppk/honorarium', [\App\Http\Controllers\PpkHonorariumVerifikasiController::class, 'index'])->name('verifikasi-ppk.honorarium.index');
        Route::get('/verifikasi-ppk/honorarium/{id}', [\App\Http\Controllers\PpkHonorariumVerifikasiController::class, 'show'])->name('verifikasi-ppk.honorarium.show');
        Route::post('/verifikasi-ppk/honorarium/{id}/approve', [\App\Http\Controllers\PpkHonorariumVerifikasiController::class, 'approve'])->name('verifikasi-ppk.honorarium.approve');
        Route::post('/verifikasi-ppk/honorarium/{id}/revisi', [\App\Http\Controllers\PpkHonorariumVerifikasiController::class, 'revisi'])->name('verifikasi-ppk.honorarium.revisi');
    });

    // Verifikasi Perjaldin — Bendahara Pengeluaran (halaman baru)
    Route::middleware('role:Super Admin|Bendahara Pengeluaran')->group(function () {
        Route::get('/verifikasi-bendahara/perjaldin', [\App\Http\Controllers\PerjaldinVerifikasiController::class, 'bendaharaIndex'])->name('verifikasi-bendahara.perjaldin.index');
        Route::get('/verifikasi-bendahara/perjaldin/{id}', [\App\Http\Controllers\PerjaldinVerifikasiController::class, 'bendaharaShow'])->name('verifikasi-bendahara.perjaldin.show');
        Route::post('/verifikasi-bendahara/perjaldin/{id}/approve', [\App\Http\Controllers\PerjaldinVerifikasiController::class, 'bendaharaApprove'])->name('verifikasi-bendahara.perjaldin.approve');
        Route::post('/verifikasi-bendahara/perjaldin/{id}/revisi', [\App\Http\Controllers\PerjaldinVerifikasiController::class, 'bendaharaRevisi'])->name('verifikasi-bendahara.perjaldin.revisi');
        // Legacy redirect
        Route::get('/verifikasi-bendahara', fn() => redirect()->route('verifikasi-bendahara.perjaldin.index'))->name('verifikasi-bendahara.index');

        // Honorarium - Verifikasi Bendahara Pengeluaran (Parallel Workflow)
        Route::get('/verifikasi-bendahara/honorarium', [\App\Http\Controllers\BendaharaHonorariumVerifikasiController::class, 'index'])->name('verifikasi-bendahara.honorarium.index');
        Route::get('/verifikasi-bendahara/honorarium/{id}', [\App\Http\Controllers\BendaharaHonorariumVerifikasiController::class, 'show'])->name('verifikasi-bendahara.honorarium.show');
        Route::post('/verifikasi-bendahara/honorarium/{id}/approve', [\App\Http\Controllers\BendaharaHonorariumVerifikasiController::class, 'approve'])->name('verifikasi-bendahara.honorarium.approve');
        Route::post('/verifikasi-bendahara/honorarium/{id}/revisi', [\App\Http\Controllers\BendaharaHonorariumVerifikasiController::class, 'revisi'])->name('verifikasi-bendahara.honorarium.revisi');
    });

    // Verifikasi Perjaldin & SPP — Kasubag
    Route::middleware('role:Super Admin|Kepala Subbagian Keuangan dan Tata Usaha')->group(function () {
        Route::get('/verifikasi-kasubag', [\App\Http\Controllers\PerjaldinVerifikasiController::class, 'kasubagIndex'])->name('verifikasi-kasubag.index');
        Route::get('/verifikasi-kasubag/perjaldin/{id}', [\App\Http\Controllers\PerjaldinVerifikasiController::class, 'kasubagShow'])->name('verifikasi-kasubag.perjaldin.show');
        Route::post('/verifikasi-kasubag/perjaldin/{id}/approve', [\App\Http\Controllers\PerjaldinVerifikasiController::class, 'kasubagApprove'])->name('verifikasi-kasubag.perjaldin.approve');
        Route::post('/verifikasi-kasubag/perjaldin/{id}/revisi', [\App\Http\Controllers\PerjaldinVerifikasiController::class, 'kasubagRevisi'])->name('verifikasi-kasubag.perjaldin.revisi');
        Route::get('/verifikasi-kasubag/npi', [\App\Http\Controllers\NpiController::class, 'kasubbagIndex'])->name('verifikasi-kasubag.npi.index');
        Route::post('/verifikasi-kasubag/npi/{npi_id}/approve', [\App\Http\Controllers\NpiController::class, 'approveKasubbag'])->name('verifikasi-kasubag.npi.approve');
        Route::post('/verifikasi-kasubag/npi/{npi_id}/revisi', [\App\Http\Controllers\NpiController::class, 'revisiKasubbag'])->name('verifikasi-kasubag.npi.revisi');
        

        // NPI Kontrak — Verifikasi Kasubbag (Parallel Workflow)
        Route::get('/verifikasi-kasubag/npi/kontrak', [\App\Http\Controllers\KasubbagNpiKontrakVerifikasiController::class, 'index'])->name('verifikasi-kasubag.npi.kontrak.index');
        Route::get('/verifikasi-kasubag/npi/kontrak/{id}', [\App\Http\Controllers\KasubbagNpiKontrakVerifikasiController::class, 'show'])->name('verifikasi-kasubag.npi.kontrak.show');
        Route::post('/verifikasi-kasubag/npi/kontrak/{id}/approve', [\App\Http\Controllers\KasubbagNpiKontrakVerifikasiController::class, 'approve'])->name('verifikasi-kasubag.npi.kontrak.approve');
        Route::post('/verifikasi-kasubag/npi/kontrak/{id}/revisi', [\App\Http\Controllers\KasubbagNpiKontrakVerifikasiController::class, 'revisi'])->name('verifikasi-kasubag.npi.kontrak.revisi');

        // SP2D Kontrak — Verifikasi Kasubbag (Parallel Workflow)
        Route::get('/verifikasi-kasubag/sp2d/kontrak', [\App\Http\Controllers\KasubbagSp2dKontrakVerifikasiController::class, 'index'])->name('verifikasi-kasubag.sp2d.kontrak.index');
        Route::get('/verifikasi-kasubag/sp2d/kontrak/{id}', [\App\Http\Controllers\KasubbagSp2dKontrakVerifikasiController::class, 'show'])->name('verifikasi-kasubag.sp2d.kontrak.show');
        Route::post('/verifikasi-kasubag/sp2d/kontrak/{id}/approve', [\App\Http\Controllers\KasubbagSp2dKontrakVerifikasiController::class, 'approve'])->name('verifikasi-kasubag.sp2d.kontrak.approve');
        Route::post('/verifikasi-kasubag/sp2d/kontrak/{id}/revisi', [\App\Http\Controllers\KasubbagSp2dKontrakVerifikasiController::class, 'revisi'])->name('verifikasi-kasubag.sp2d.kontrak.revisi');
    });

    // Verifikasi SPP — Koordinator Keuangan
    Route::middleware('role:Super Admin|Koordinator Keuangan')->group(function () {

        // SPM Perjaldin
        Route::get('/verifikasi-koordinator/spm-perjaldin', [\App\Http\Controllers\SpmPerjaldinVerifikasiController::class, 'koordinatorIndex'])->name('verifikasi-koordinator.spm-perjaldin.index');
        Route::get('/verifikasi-koordinator/spm-perjaldin/{id}', [\App\Http\Controllers\SpmPerjaldinVerifikasiController::class, 'koordinatorShow'])->name('verifikasi-koordinator.spm-perjaldin.show');
        Route::post('/verifikasi-koordinator/spm-perjaldin/{id}/approve', [\App\Http\Controllers\SpmPerjaldinVerifikasiController::class, 'approve'])->name('verifikasi-koordinator.spm-perjaldin.approve');
        Route::post('/verifikasi-koordinator/spm-perjaldin/{id}/revisi', [\App\Http\Controllers\SpmPerjaldinVerifikasiController::class, 'revisi'])->name('verifikasi-koordinator.spm-perjaldin.revisi');

        // Tagihan Perjaldin — Verifikasi Koordinator Keuangan
        // SPM Kontrak
        Route::get('/verifikasi-koordinator/spm/kontrak', [\App\Http\Controllers\PpspmSpmKontrakVerifikasiController::class, 'index'])->name('verifikasi-koordinator.spm.kontrak.index');
        Route::get('/verifikasi-koordinator/spm/kontrak/{id}', [\App\Http\Controllers\PpspmSpmKontrakVerifikasiController::class, 'show'])->name('verifikasi-koordinator.spm.kontrak.show');
        Route::post('/verifikasi-koordinator/spm/kontrak/{id}/approve', [\App\Http\Controllers\PpspmSpmKontrakVerifikasiController::class, 'approve'])->name('verifikasi-koordinator.spm.kontrak.approve');
        Route::post('/verifikasi-koordinator/spm/kontrak/{id}/revisi', [\App\Http\Controllers\PpspmSpmKontrakVerifikasiController::class, 'revisi'])->name('verifikasi-koordinator.spm.kontrak.revisi');

        // NPI Kontrak
        Route::get('/verifikasi-koordinator/npi/kontrak', [\App\Http\Controllers\PpkNpiKontrakVerifikasiController::class, 'index'])->name('verifikasi-koordinator.npi.kontrak.index');
        Route::get('/verifikasi-koordinator/npi/kontrak/{id}', [\App\Http\Controllers\PpkNpiKontrakVerifikasiController::class, 'show'])->name('verifikasi-koordinator.npi.kontrak.show');
        Route::post('/verifikasi-koordinator/npi/kontrak/{id}/approve', [\App\Http\Controllers\PpkNpiKontrakVerifikasiController::class, 'approve'])->name('verifikasi-koordinator.npi.kontrak.approve');
        Route::post('/verifikasi-koordinator/npi/kontrak/{id}/revisi', [\App\Http\Controllers\PpkNpiKontrakVerifikasiController::class, 'revisi'])->name('verifikasi-koordinator.npi.kontrak.revisi');

        // SP2D Kontrak
        Route::get('/verifikasi-koordinator/sp2d/kontrak', [\App\Http\Controllers\PpkSp2dKontrakVerifikasiController::class, 'index'])->name('verifikasi-koordinator.sp2d.kontrak.index');
        Route::get('/verifikasi-koordinator/sp2d/kontrak/{id}', [\App\Http\Controllers\PpkSp2dKontrakVerifikasiController::class, 'show'])->name('verifikasi-koordinator.sp2d.kontrak.show');
        Route::post('/verifikasi-koordinator/sp2d/kontrak/{id}/approve', [\App\Http\Controllers\PpkSp2dKontrakVerifikasiController::class, 'approve'])->name('verifikasi-koordinator.sp2d.kontrak.approve');
        Route::post('/verifikasi-koordinator/sp2d/kontrak/{id}/revisi', [\App\Http\Controllers\PpkSp2dKontrakVerifikasiController::class, 'revisi'])->name('verifikasi-koordinator.sp2d.kontrak.revisi');

        Route::get('/verifikasi-koordinator/honorarium', [\App\Http\Controllers\PpkHonorariumVerifikasiController::class, 'index'])->name('verifikasi-koordinator.honorarium.index');
        Route::get('/verifikasi-koordinator/honorarium/{id}', [\App\Http\Controllers\PpkHonorariumVerifikasiController::class, 'show'])->name('verifikasi-koordinator.honorarium.show');
        Route::post('/verifikasi-koordinator/honorarium/{id}/approve', [\App\Http\Controllers\PpkHonorariumVerifikasiController::class, 'approve'])->name('verifikasi-koordinator.honorarium.approve');
        Route::post('/verifikasi-koordinator/honorarium/{id}/revisi', [\App\Http\Controllers\PpkHonorariumVerifikasiController::class, 'revisi'])->name('verifikasi-koordinator.honorarium.revisi');

        Route::get('/verifikasi-koordinator/perjaldin', [\App\Http\Controllers\PerjaldinVerifikasiController::class, 'koordinatorIndex'])->name('verifikasi-koordinator.perjaldin.index');
        Route::get('/verifikasi-koordinator/perjaldin/{id}', [\App\Http\Controllers\PerjaldinVerifikasiController::class, 'koordinatorShow'])->name('verifikasi-koordinator.perjaldin.show');
        Route::post('/verifikasi-koordinator/perjaldin/{id}/approve', [\App\Http\Controllers\PerjaldinVerifikasiController::class, 'koordinatorApprove'])->name('verifikasi-koordinator.perjaldin.approve');
        Route::post('/verifikasi-koordinator/perjaldin/{id}/revisi', [\App\Http\Controllers\PerjaldinVerifikasiController::class, 'koordinatorRevisi'])->name('verifikasi-koordinator.perjaldin.revisi');

        // SPP Kontrak — Verifikasi Koordinator Keuangan
        Route::get('/verifikasi-koordinator/spp/kontrak', [\App\Http\Controllers\SppVerifikasiController::class, 'koordinatorIndex'])->name('verifikasi-koordinator.spp.index');
        Route::get('/verifikasi-koordinator/spp/kontrak/{id}', [\App\Http\Controllers\SppVerifikasiController::class, 'koordinatorShow'])->name('verifikasi-koordinator.spp.show');
        Route::post('/verifikasi-koordinator/spp/kontrak/{id}/approve', [\App\Http\Controllers\SppVerifikasiController::class, 'approveKoordinator'])->name('verifikasi-koordinator.spp.approve');
        Route::post('/verifikasi-koordinator/spp/kontrak/{id}/revisi', [\App\Http\Controllers\SppVerifikasiController::class, 'revisiKoordinator'])->name('verifikasi-koordinator.spp.revisi');

        // SPP Perjaldin — Verifikasi Koordinator Keuangan
        Route::get('/verifikasi-koordinator/spp-perjaldin', [\App\Http\Controllers\SppPerjaldinVerifikasiController::class, 'index'])->name('verifikasi-koordinator.spp-perjaldin.index');
        Route::get('/verifikasi-koordinator/spp-perjaldin/{id}', [\App\Http\Controllers\SppPerjaldinVerifikasiController::class, 'show'])->name('verifikasi-koordinator.spp-perjaldin.show');
        Route::post('/verifikasi-koordinator/spp-perjaldin/{id}/approve', [\App\Http\Controllers\SppPerjaldinVerifikasiController::class, 'approve'])->name('verifikasi-koordinator.spp-perjaldin.approve');
        Route::post('/verifikasi-koordinator/spp-perjaldin/{id}/revisi', [\App\Http\Controllers\SppPerjaldinVerifikasiController::class, 'revisi'])->name('verifikasi-koordinator.spp-perjaldin.revisi');
    });

    // ==== VERIFIKASI SPP KONTRAK, PERJALDIN, HONORARIUM — Terpadu 3 Role (PPK, Koordinator Keuangan, Kasubbag) ====
    Route::middleware('role:Super Admin|PPK|Kepala Subbagian Keuangan dan Tata Usaha|Koordinator Keuangan')->group(function () {
        // Honor
        Route::get('/verifikasi-spp/honor', [\App\Http\Controllers\VerifikasiSppHonorController::class, 'index'])->name('verifikasi-spp.honor.index');
        Route::get('/verifikasi-spp/honor/{spp}/detail', [\App\Http\Controllers\VerifikasiSppHonorController::class, 'detail'])->name('verifikasi-spp.honor.detail');
        Route::post('/verifikasi-spp/honor/{spp}/approve', [\App\Http\Controllers\VerifikasiSppHonorController::class, 'approve'])->name('verifikasi-spp.honor.approve');
        Route::post('/verifikasi-spp/honor/{spp}/reject', [\App\Http\Controllers\VerifikasiSppHonorController::class, 'reject'])->name('verifikasi-spp.honor.reject');

        // Kontrak
        Route::get('/verifikasi-spp/kontrak', [\App\Http\Controllers\SppVerifikasiController::class, 'index'])->name('verifikasi-spp.kontrak.index');
        Route::get('/verifikasi-spp/kontrak/{id}', [\App\Http\Controllers\SppVerifikasiController::class, 'show'])->name('verifikasi-spp.kontrak.show');
        Route::post('/verifikasi-spp/kontrak/{id}/approve', [\App\Http\Controllers\SppVerifikasiController::class, 'approve'])->name('verifikasi-spp.kontrak.approve');
        Route::post('/verifikasi-spp/kontrak/{id}/revisi', [\App\Http\Controllers\SppVerifikasiController::class, 'revisi'])->name('verifikasi-spp.kontrak.revisi');

        // Perjaldin
        Route::get('/verifikasi-spp/perjaldin', [\App\Http\Controllers\SppPerjaldinVerifikasiController::class, 'index'])->name('verifikasi-spp.perjaldin.index');
        Route::get('/verifikasi-spp/perjaldin/{id}', [\App\Http\Controllers\SppPerjaldinVerifikasiController::class, 'show'])->name('verifikasi-spp.perjaldin.show');
        Route::post('/verifikasi-spp/perjaldin/{id}/approve', [\App\Http\Controllers\SppPerjaldinVerifikasiController::class, 'approve'])->name('verifikasi-spp.perjaldin.approve');
        Route::post('/verifikasi-spp/perjaldin/{id}/revisi', [\App\Http\Controllers\SppPerjaldinVerifikasiController::class, 'revisi'])->name('verifikasi-spp.perjaldin.revisi');
    });

    // ==== VERIFIKASI NPI — Bendahara Penerimaan (TTD) ====
    Route::middleware('role:Super Admin|Bendahara Penerimaan')->group(function () {
        Route::get('/verifikasi-bendahara-penerimaan/perjaldin', [\App\Http\Controllers\PerjaldinVerifikasiController::class, 'bendaharaPenerimaanIndex'])->name('verifikasi-bendahara-penerimaan.perjaldin.index');
        Route::get('/verifikasi-bendahara-penerimaan/perjaldin/{id}', [\App\Http\Controllers\PerjaldinVerifikasiController::class, 'bendaharaPenerimaanShow'])->name('verifikasi-bendahara-penerimaan.perjaldin.show');
        Route::post('/verifikasi-bendahara-penerimaan/perjaldin/{id}/approve', [\App\Http\Controllers\PerjaldinVerifikasiController::class, 'bendaharaPenerimaanApprove'])->name('verifikasi-bendahara-penerimaan.perjaldin.approve');
        Route::post('/verifikasi-bendahara-penerimaan/perjaldin/{id}/revisi', [\App\Http\Controllers\PerjaldinVerifikasiController::class, 'bendaharaPenerimaanRevisi'])->name('verifikasi-bendahara-penerimaan.perjaldin.revisi');

        Route::get('/verifikasi-bendahara-penerimaan/npi', [\App\Http\Controllers\NpiController::class, 'penerimaaIndex'])->name('verifikasi-bendahara-penerimaan.npi.index');
        Route::post('/verifikasi-bendahara-penerimaan/npi/{npi_id}/approve', [\App\Http\Controllers\NpiController::class, 'approvePenerimaan'])->name('verifikasi-bendahara-penerimaan.npi.approve');
        Route::post('/verifikasi-bendahara-penerimaan/npi/{npi_id}/revisi', [\App\Http\Controllers\NpiController::class, 'revisiPenerimaan'])->name('verifikasi-bendahara-penerimaan.npi.revisi');
        
        // NPI Kontrak — Verifikasi Bendahara Penerimaan (Parallel Workflow)
        Route::get('/verifikasi-bendahara-penerimaan/npi/kontrak', [\App\Http\Controllers\BenpenNpiKontrakVerifikasiController::class, 'index'])->name('verifikasi-bendahara-penerimaan.npi.kontrak.index');
        Route::get('/verifikasi-bendahara-penerimaan/npi/kontrak/{id}', [\App\Http\Controllers\BenpenNpiKontrakVerifikasiController::class, 'show'])->name('verifikasi-bendahara-penerimaan.npi.kontrak.show');
        Route::post('/verifikasi-bendahara-penerimaan/npi/kontrak/{id}/approve', [\App\Http\Controllers\BenpenNpiKontrakVerifikasiController::class, 'approve'])->name('verifikasi-bendahara-penerimaan.npi.kontrak.approve');
        Route::post('/verifikasi-bendahara-penerimaan/npi/kontrak/{id}/revisi', [\App\Http\Controllers\BenpenNpiKontrakVerifikasiController::class, 'revisi'])->name('verifikasi-bendahara-penerimaan.npi.kontrak.revisi');
    });

    // ==== VERIFIKASI NPI PERJALDIN & HONORARIUM — Terpadu 3 Role ====
    Route::middleware('role:Super Admin|PPK|Bendahara Penerimaan|Kepala Subbagian Keuangan dan Tata Usaha|Koordinator Keuangan')->group(function () {
        // NPI Perjaldin
        Route::get('/verifikasi-npi/perjaldin', [\App\Http\Controllers\VerifikasiNpiPerjaldinController::class, 'index'])->name('verifikasi-npi.perjaldin.index');
        Route::get('/verifikasi-npi/perjaldin/{id}/detail', [\App\Http\Controllers\VerifikasiNpiPerjaldinController::class, 'show'])->name('verifikasi-npi.perjaldin.detail');
        Route::post('/verifikasi-npi/perjaldin/{id}/approve', [\App\Http\Controllers\VerifikasiNpiPerjaldinController::class, 'approve'])->name('verifikasi-npi.perjaldin.approve');
        Route::post('/verifikasi-npi/perjaldin/{id}/revisi', [\App\Http\Controllers\VerifikasiNpiPerjaldinController::class, 'reject'])->name('verifikasi-npi.perjaldin.reject');

        // NPI Honorarium
        Route::get('/verifikasi-npi/honor', [\App\Http\Controllers\VerifikasiNpiHonorController::class, 'index'])->name('verifikasi-npi.honor.index');
        Route::get('/verifikasi-npi/honor/{id}/detail', [\App\Http\Controllers\VerifikasiNpiHonorController::class, 'show'])->name('verifikasi-npi.honor.detail');
        Route::post('/verifikasi-npi/honor/{id}/approve', [\App\Http\Controllers\VerifikasiNpiHonorController::class, 'approve'])->name('verifikasi-npi.honor.approve');
        Route::post('/verifikasi-npi/honor/{id}/revisi', [\App\Http\Controllers\VerifikasiNpiHonorController::class, 'reject'])->name('verifikasi-npi.honor.reject');
    });

    // ==== VERIFIKASI SP2D PERJALDIN — Terpadu 2 Role ====
    Route::middleware('role:Super Admin|PPK|Kepala Subbagian Keuangan dan Tata Usaha|Koordinator Keuangan')->group(function () {
        Route::get('/verifikasi-sp2d/perjaldin', [\App\Http\Controllers\VerifikasiSp2dPerjaldinController::class, 'index'])->name('verifikasi-sp2d.perjaldin.index');
        Route::get('/verifikasi-sp2d/perjaldin/{id}/detail', [\App\Http\Controllers\VerifikasiSp2dPerjaldinController::class, 'show'])->name('verifikasi-sp2d.perjaldin.detail');
        Route::post('/verifikasi-sp2d/perjaldin/{id}/approve', [\App\Http\Controllers\VerifikasiSp2dPerjaldinController::class, 'approve'])->name('verifikasi-sp2d.perjaldin.approve');
        Route::post('/verifikasi-sp2d/perjaldin/{id}/revisi', [\App\Http\Controllers\VerifikasiSp2dPerjaldinController::class, 'reject'])->name('verifikasi-sp2d.perjaldin.reject');

        // SP2D Honorarium
        Route::get('/verifikasi-sp2d/honor', [\App\Http\Controllers\VerifikasiSp2dHonorController::class, 'index'])->name('verifikasi-sp2d.honor.index');
        Route::get('/verifikasi-sp2d/honor/{id}/detail', [\App\Http\Controllers\VerifikasiSp2dHonorController::class, 'show'])->name('verifikasi-sp2d.honor.detail');
        Route::post('/verifikasi-sp2d/honor/{id}/approve', [\App\Http\Controllers\VerifikasiSp2dHonorController::class, 'approve'])->name('verifikasi-sp2d.honor.approve');
        Route::post('/verifikasi-sp2d/honor/{id}/revisi', [\App\Http\Controllers\VerifikasiSp2dHonorController::class, 'reject'])->name('verifikasi-sp2d.honor.reject');
    });

    // ==== MODUL PEMBUATAN SPP (BLU) ====
    Route::middleware('role:Super Admin|Operator BLU')->group(function () {
        // SPP Perjaldin
        Route::get('/spps/perjaldin', [\App\Http\Controllers\SppController::class, 'perjaldinIndex'])->name('spps.perjaldin.index');
        Route::get('/spps/perjaldin/{perjaldin}/detail', [\App\Http\Controllers\SppController::class, 'detailPerjaldin'])->name('spps.perjaldin.detail');
        Route::post('/spps/perjaldin/{perjaldin}', [\App\Http\Controllers\SppController::class, 'storePerjaldin'])->name('spps.perjaldin.store');

        // Komponen Perjaldin — COA & SPP per komponen
        Route::put('/perjaldins/komponen/{id}/coa', [\App\Http\Controllers\PerjaldinKomponenController::class, 'updateCoa'])->name('perjaldins.komponen.update-coa');
        Route::post('/perjaldins/komponen/{id}/spp', [\App\Http\Controllers\SppController::class, 'storeFromPerjaldinKomponen'])->name('spps.store-from-perjaldin-komponen');

        // SPP Workflow (submit/approve/revisi/reject)
        Route::post('/spps/{id}/workflow/submit', [\App\Http\Controllers\SppWorkflowController::class, 'submit'])->name('spps.workflow.submit');
        Route::post('/spps/workflow/approval/{approvalId}/approve', [\App\Http\Controllers\SppWorkflowController::class, 'approve'])->name('spps.workflow.approve');
        Route::post('/spps/workflow/approval/{approvalId}/revision', [\App\Http\Controllers\SppWorkflowController::class, 'revision'])->name('spps.workflow.revision');
        Route::post('/spps/workflow/approval/{approvalId}/reject', [\App\Http\Controllers\SppWorkflowController::class, 'reject'])->name('spps.workflow.reject');

        // SPP Honor
        Route::get('/spps/honor', [\App\Http\Controllers\SppController::class, 'honorIndex'])->name('spps.honor.index');
        Route::get('/spps/honor/{honorarium}/detail', [\App\Http\Controllers\SppController::class, 'detailHonor'])->name('spps.honor.detail');
        Route::post('/spps/honor/{honorarium}', [\App\Http\Controllers\SppController::class, 'storeHonor'])->name('spps.honor.store');
        Route::post('/spps/honor/{honorarium}/submit', [\App\Http\Controllers\SppController::class, 'submitHonorToPpk'])->name('spps.honor.submit');

        // SPP Kontrak
        Route::get('/spps/kontrak', [\App\Http\Controllers\SppController::class, 'kontrakIndex'])->name('spps.kontrak.index');
        Route::get('/spps/kontrak/{contract}/detail', [\App\Http\Controllers\SppController::class, 'detailKontrak'])->name('spps.kontrak.detail');
        Route::post('/spps/kontrak/{contract}', [\App\Http\Controllers\SppController::class, 'storeKontrak'])->name('spps.kontrak.store');
        Route::post('/spps/kontrak/{contract}/submit', [\App\Http\Controllers\SppController::class, 'submitKontrakToPpk'])->name('spps.kontrak.submit');
    });

    // Cetak PDF SPP/SPM/NPI bisa diakses oleh berbagai role terkait
    Route::middleware('auth')->group(function () {
        Route::get('/spps/{spp}/pdf', [\App\Http\Controllers\SppController::class, 'cetakPdf'])->name('spps.cetak-pdf');
        Route::get('/spms/{spm_id}/pdf', [\App\Http\Controllers\SpmController::class, 'cetakPdfSpm'])->name('spms.cetak-pdf');
        Route::get('/npis/{npi_id}/pdf', [\App\Http\Controllers\NpiController::class, 'cetakPdf'])->name('npis.cetak-pdf');
        Route::get('/sp2ds/{sp2d}/pdf', [\App\Http\Controllers\DocumentController::class, 'printSp2d'])->name('sp2ds.cetak-pdf');
        Route::get('/sp2ds/perjaldin/{sp2d}/cetak', [\App\Http\Controllers\Sp2dPerjaldinController::class, 'cetak'])->name('sp2ds.perjaldin.cetak');
    });

    // ==== MODUL PEMBUATAN SPM (BLU) ====
    Route::middleware('role:Super Admin|Operator BLU')->group(function () {
        Route::get('/spms', [\App\Http\Controllers\SpmController::class, 'index'])->name('spms.index');
        Route::post('/spms/spp/{spp_id}/store', [\App\Http\Controllers\SpmController::class, 'store'])->name('spms.store');

        // SPM Perjaldin (New Pattern)
        Route::get('/spms/perjaldin', [\App\Http\Controllers\SpmPerjaldinController::class, 'index'])->name('spms.perjaldin.index');
        Route::get('/spms/perjaldin/{spp}/detail', [\App\Http\Controllers\SpmPerjaldinController::class, 'show'])->name('spms.perjaldin.detail');
        Route::post('/spms/perjaldin/{spp}/store', [\App\Http\Controllers\SpmPerjaldinController::class, 'store'])->name('spms.perjaldin.store');
        Route::post('/spms/perjaldin/{spp}/submit', [\App\Http\Controllers\SpmPerjaldinController::class, 'submit'])->name('spms.perjaldin.submit');

        // SPM Kontrak
        Route::get('/spms/kontrak', [\App\Http\Controllers\SpmKontrakController::class, 'index'])->name('spms.kontrak.index');
        Route::get('/spms/kontrak/{spp}/detail', [\App\Http\Controllers\SpmKontrakController::class, 'show'])->name('spms.kontrak.detail');
        Route::post('/spms/kontrak/{spp}/store', [\App\Http\Controllers\SpmKontrakController::class, 'store'])->name('spms.kontrak.store');
        Route::post('/spms/kontrak/{spp}/submit', [\App\Http\Controllers\SpmKontrakController::class, 'submit'])->name('spms.kontrak.submit');

        // SPM Honorarium
        Route::get('/spms/honor', [\App\Http\Controllers\SpmHonorController::class, 'index'])->name('spms.honor.index');
        Route::get('/spms/honor/{spp}/detail', [\App\Http\Controllers\SpmHonorController::class, 'show'])->name('spms.honor.detail');
        Route::post('/spms/honor/{spp}/store', [\App\Http\Controllers\SpmHonorController::class, 'store'])->name('spms.honor.store');
        Route::post('/spms/honor/{spp}/submit', [\App\Http\Controllers\SpmHonorController::class, 'submit'])->name('spms.honor.submit');
    });

    // ==== MODUL VERIFIKASI SPM (PPSPM) ====
    Route::middleware('role:Super Admin|PPSPM')->group(function () {
        Route::get('/verifikasi-ppspm/perjaldin', [\App\Http\Controllers\PerjaldinVerifikasiController::class, 'ppspmIndex'])->name('verifikasi-ppspm.perjaldin.index');
        Route::get('/verifikasi-ppspm/perjaldin/{id}', [\App\Http\Controllers\PerjaldinVerifikasiController::class, 'ppspmShow'])->name('verifikasi-ppspm.perjaldin.show');
        Route::post('/verifikasi-ppspm/perjaldin/{id}/approve', [\App\Http\Controllers\PerjaldinVerifikasiController::class, 'ppspmApprove'])->name('verifikasi-ppspm.perjaldin.approve');
        Route::post('/verifikasi-ppspm/perjaldin/{id}/revisi', [\App\Http\Controllers\PerjaldinVerifikasiController::class, 'ppspmRevisi'])->name('verifikasi-ppspm.perjaldin.revisi');

        Route::get('/verifikasi-ppspm/spm', [\App\Http\Controllers\SpmVerifikasiController::class, 'index'])->name('verifikasi-ppspm.spm.index');
        Route::post('/verifikasi-ppspm/spm/{spm_id}/approve', [\App\Http\Controllers\SpmVerifikasiController::class, 'approve'])->name('verifikasi-ppspm.spm.approve');
        Route::post('/verifikasi-ppspm/spm/{spm_id}/revisi', [\App\Http\Controllers\SpmVerifikasiController::class, 'revisi'])->name('verifikasi-ppspm.spm.revisi');

        // Verifikasi SPM Perjaldin
        Route::get('/verifikasi-ppspm/spm/perjaldin', [\App\Http\Controllers\SpmPerjaldinVerifikasiController::class, 'ppspmIndex'])->name('verifikasi-ppspm.spm-perjaldin.index');
        Route::get('/verifikasi-ppspm/spm/perjaldin/{id}', [\App\Http\Controllers\SpmPerjaldinVerifikasiController::class, 'ppspmShow'])->name('verifikasi-ppspm.spm-perjaldin.show');
        Route::post('/verifikasi-ppspm/spm/perjaldin/{id}/approve', [\App\Http\Controllers\SpmPerjaldinVerifikasiController::class, 'approve'])->name('verifikasi-ppspm.spm-perjaldin.approve');
        Route::post('/verifikasi-ppspm/spm/perjaldin/{id}/revisi', [\App\Http\Controllers\SpmPerjaldinVerifikasiController::class, 'revisi'])->name('verifikasi-ppspm.spm-perjaldin.revisi');

        // Verifikasi SPM Kontrak
        Route::get('/verifikasi-ppspm/spm/kontrak', [\App\Http\Controllers\PpspmSpmKontrakVerifikasiController::class, 'index'])->name('verifikasi-ppspm.spm.kontrak.index');
        Route::get('/verifikasi-ppspm/spm/kontrak/{id}', [\App\Http\Controllers\PpspmSpmKontrakVerifikasiController::class, 'show'])->name('verifikasi-ppspm.spm.kontrak.show');
        Route::post('/verifikasi-ppspm/spm/kontrak/{id}/approve', [\App\Http\Controllers\PpspmSpmKontrakVerifikasiController::class, 'approve'])->name('verifikasi-ppspm.spm.kontrak.approve');
        Route::post('/verifikasi-ppspm/spm/kontrak/{id}/revisi', [\App\Http\Controllers\PpspmSpmKontrakVerifikasiController::class, 'revisi'])->name('verifikasi-ppspm.spm.kontrak.revisi');
    });

    Route::middleware('role:Super Admin|Kepala Subbagian Keuangan dan Tata Usaha')->group(function () {
        // Verifikasi SPM reguler (lama) / Perjaldin
        Route::get('/verifikasi-kasubag/spm', [\App\Http\Controllers\SpmVerifikasiController::class, 'kasubbagIndex'])->name('verifikasi-kasubag.spm.index');
        Route::post('/verifikasi-kasubag/spm/{spm_id}/approve', [\App\Http\Controllers\SpmVerifikasiController::class, 'approveKasubbag'])->name('verifikasi-kasubag.spm.approve');
        Route::post('/verifikasi-kasubag/spm/{spm_id}/revisi', [\App\Http\Controllers\SpmVerifikasiController::class, 'revisiKasubbag'])->name('verifikasi-kasubag.spm.revisi');

        // Verifikasi SPM Perjaldin
        Route::get('/verifikasi-kasubag/spm/perjaldin', [\App\Http\Controllers\SpmPerjaldinVerifikasiController::class, 'kasubbagIndex'])->name('verifikasi-kasubag.spm-perjaldin.index');
        Route::get('/verifikasi-kasubag/spm/perjaldin/{id}', [\App\Http\Controllers\SpmPerjaldinVerifikasiController::class, 'kasubbagShow'])->name('verifikasi-kasubag.spm-perjaldin.show');
        Route::post('/verifikasi-kasubag/spm/perjaldin/{id}/approve', [\App\Http\Controllers\SpmPerjaldinVerifikasiController::class, 'approve'])->name('verifikasi-kasubag.spm-perjaldin.approve');
        Route::post('/verifikasi-kasubag/spm/perjaldin/{id}/revisi', [\App\Http\Controllers\SpmPerjaldinVerifikasiController::class, 'revisi'])->name('verifikasi-kasubag.spm-perjaldin.revisi');

        // Verifikasi SPM Kontrak
        Route::get('/verifikasi-kasubag/spm/kontrak', [\App\Http\Controllers\KasubbagSpmKontrakVerifikasiController::class, 'index'])->name('verifikasi-kasubag.spm.kontrak.index');
        Route::get('/verifikasi-kasubag/spm/kontrak/{id}', [\App\Http\Controllers\KasubbagSpmKontrakVerifikasiController::class, 'show'])->name('verifikasi-kasubag.spm.kontrak.show');
        Route::post('/verifikasi-kasubag/spm/kontrak/{id}/approve', [\App\Http\Controllers\KasubbagSpmKontrakVerifikasiController::class, 'approve'])->name('verifikasi-kasubag.spm.kontrak.approve');
        Route::post('/verifikasi-kasubag/spm/kontrak/{id}/revisi', [\App\Http\Controllers\KasubbagSpmKontrakVerifikasiController::class, 'revisi'])->name('verifikasi-kasubag.spm.kontrak.revisi');
    });

    // ==== MODUL VERIFIKASI SPM HONORARIUM (PPSPM & KASUBBAG) ====
    Route::middleware('role:Super Admin|PPSPM|Kepala Subbagian Keuangan dan Tata Usaha|Koordinator Keuangan')->group(function () {
        Route::get('/verifikasi-spm/honor', [\App\Http\Controllers\VerifikasiSpmHonorController::class, 'index'])->name('verifikasi-spm.honor.index');
        Route::get('/verifikasi-spm/honor/{spm}/detail', [\App\Http\Controllers\VerifikasiSpmHonorController::class, 'show'])->name('verifikasi-spm.honor.detail');
        Route::post('/verifikasi-spm/honor/{spm}/approve', [\App\Http\Controllers\VerifikasiSpmHonorController::class, 'approve'])->name('verifikasi-spm.honor.approve');
        Route::post('/verifikasi-spm/honor/{spm}/reject', [\App\Http\Controllers\VerifikasiSpmHonorController::class, 'reject'])->name('verifikasi-spm.honor.reject');
    });

    // ==== MODUL NPI (Nota Pemindahbukuan Internal) — Bendahara Pengeluaran ====
    Route::middleware('role:Super Admin|Bendahara Pengeluaran')->group(function () {
        // NPI Perjaldin
        Route::get('/npis/perjaldin', [\App\Http\Controllers\NpiPerjaldinController::class, 'index'])->name('npis.perjaldin.index');
        Route::get('/npis/perjaldin/{id}/detail', [\App\Http\Controllers\NpiPerjaldinController::class, 'show'])->name('npis.perjaldin.detail');
        Route::post('/npis/perjaldin/{id}/store', [\App\Http\Controllers\NpiPerjaldinController::class, 'store'])->name('npis.perjaldin.store');
        Route::post('/npis/perjaldin/{id}/submit', [\App\Http\Controllers\NpiPerjaldinController::class, 'submit'])->name('npis.perjaldin.submit');

        // NPI Honorarium
        Route::get('/npis/honor', [\App\Http\Controllers\NpiHonorController::class, 'index'])->name('npis.honor.index');
        Route::get('/npis/honor/{spm}/detail', [\App\Http\Controllers\NpiHonorController::class, 'show'])->name('npis.honor.detail');
        Route::post('/npis/honor/{spm}/store', [\App\Http\Controllers\NpiHonorController::class, 'store'])->name('npis.honor.store');
        Route::post('/npis/honor/{spm}/submit', [\App\Http\Controllers\NpiHonorController::class, 'submit'])->name('npis.honor.submit');

        // NPI Legacy (if needed)
        Route::get('/npis', [\App\Http\Controllers\NpiController::class, 'index'])->name('npis.index');
        Route::post('/npis/spm/{spm_id}/store', [\App\Http\Controllers\NpiController::class, 'store'])->name('npis.store');
        
        // NPI Kontrak
        Route::get('/npis/kontrak', [\App\Http\Controllers\NpiKontrakController::class, 'index'])->name('npis.kontrak.index');
        Route::get('/npis/kontrak/{spm}/detail', [\App\Http\Controllers\NpiKontrakController::class, 'show'])->name('npis.kontrak.detail');
        Route::post('/npis/kontrak/{spm}/store', [\App\Http\Controllers\NpiKontrakController::class, 'store'])->name('npis.kontrak.store');
        Route::post('/npis/kontrak/{spm}/submit', [\App\Http\Controllers\NpiKontrakController::class, 'submit'])->name('npis.kontrak.submit');
    });

    // ==== MODUL SP2D & BKU — Bendahara Pengeluaran ====
    Route::middleware('role:Super Admin|Bendahara Pengeluaran')->group(function () {
        // SP2D Perjaldin
        Route::get('/sp2ds/perjaldin', [\App\Http\Controllers\Sp2dPerjaldinController::class, 'index'])->name('sp2ds.perjaldin.index');
        Route::get('/sp2ds/perjaldin/{npi_id}/detail', [\App\Http\Controllers\Sp2dPerjaldinController::class, 'detail'])->name('sp2ds.perjaldin.detail');
        Route::post('/sp2ds/perjaldin/{npi_id}/store', [\App\Http\Controllers\Sp2dPerjaldinController::class, 'store'])->name('sp2ds.perjaldin.store');
        Route::post('/sp2ds/perjaldin/{npi_id}/submit', [\App\Http\Controllers\Sp2dPerjaldinController::class, 'submit'])->name('sp2ds.perjaldin.submit');

        // SP2D Honorarium
        Route::get('/sp2ds/honor', [\App\Http\Controllers\Sp2dHonorController::class, 'index'])->name('sp2ds.honor.index');
        Route::get('/sp2ds/honor/{npi_id}/detail', [\App\Http\Controllers\Sp2dHonorController::class, 'detail'])->name('sp2ds.honor.detail');
        Route::post('/sp2ds/honor/{npi_id}/store', [\App\Http\Controllers\Sp2dHonorController::class, 'store'])->name('sp2ds.honor.store');
        Route::post('/sp2ds/honor/{npi_id}/submit', [\App\Http\Controllers\Sp2dHonorController::class, 'submit'])->name('sp2ds.honor.submit');

        Route::get('/sp2ds/kontrak', [\App\Http\Controllers\Sp2dKontrakController::class, 'index'])->name('sp2ds.kontrak.index');
        Route::post('/sp2ds/kontrak/npi/{npi_id}/draft', [\App\Http\Controllers\Sp2dKontrakController::class, 'storeDraft'])->name('sp2ds.kontrak.store');
        Route::post('/sp2ds/kontrak/npi/{npi_id}/submit', [\App\Http\Controllers\Sp2dKontrakController::class, 'submitVerification'])->name('sp2ds.kontrak.submit');
        Route::get('/sp2ds', [\App\Http\Controllers\Sp2dController::class, 'index'])->name('sp2ds.index');
        Route::get('/sp2ds/perjaldin/{perjaldin_id}/detail', [\App\Http\Controllers\Sp2dController::class, 'detail'])->name('sp2ds.perjaldin.detail');
        Route::post('/sp2ds/npi/{npi_id}/store', [\App\Http\Controllers\Sp2dController::class, 'store'])->name('sp2ds.store');
        Route::post('/sp2ds/{sp2d_id}/approve', [\App\Http\Controllers\Sp2dController::class, 'approve'])->name('sp2ds.approve');
        Route::post('/sp2ds/{sp2d_id}/execute', [\App\Http\Controllers\Sp2dController::class, 'catatBku'])->name('sp2ds.catat-bku');
        Route::get('/sp2ds/kontrak/{npi_id}/detail', [\App\Http\Controllers\Sp2dKontrakController::class, 'show'])->name('sp2ds.kontrak.detail');
    });

    // ==== MODUL PENYETORAN PAJAK — Bendahara Pengeluaran ====
    Route::middleware('role:Super Admin|Bendahara Pengeluaran|Bendahara Penerimaan')->group(function () {
        Route::get('/pembukuan/bku/pdf', [\App\Http\Controllers\BukuKasUmumController::class, 'pdf'])->name('pembukuan.bku.pdf');
        Route::get('/pembukuan/bku', [\App\Http\Controllers\BukuKasUmumController::class, 'index'])->name('pembukuan.bku.index');
        Route::get('/pembukuan/bku/{id}', [\App\Http\Controllers\BukuKasUmumController::class, 'show'])
            ->whereNumber('id')
            ->name('pembukuan.bku.show');

        Route::get('/pembukuan/bank/mutasi', [\App\Http\Controllers\BukuPembantuBankController::class, 'mutasi'])->name('pembukuan.bank.mutasi');
        Route::get('/pembukuan/bank/rekonsiliasi', [\App\Http\Controllers\BukuPembantuBankController::class, 'rekonsiliasi'])->name('pembukuan.bank.rekonsiliasi');
        Route::get('/pembukuan/bank', [\App\Http\Controllers\BukuPembantuBankController::class, 'index'])->name('pembukuan.bank.index');
        Route::get('/pembukuan/bank/{rekening}', [\App\Http\Controllers\BukuPembantuBankController::class, 'show'])
            ->whereNumber('rekening')
            ->name('pembukuan.bank.show');

        Route::get('/pembukuan/bendahara/pdf', [\App\Http\Controllers\BukuPembantuBendaharaController::class, 'pdf'])->name('pembukuan.bendahara.pdf');
        Route::get('/pembukuan/bendahara', [\App\Http\Controllers\BukuPembantuBendaharaController::class, 'index'])->name('pembukuan.bendahara.index');

        Route::get('/pembukuan/bunga-rekening/pdf', [\App\Http\Controllers\BukuPembantuBungaController::class, 'pdf'])->name('pembukuan.bunga.pdf');
        Route::get('/pembukuan/bunga-rekening', [\App\Http\Controllers\BukuPembantuBungaController::class, 'index'])->name('pembukuan.bunga.index');

        Route::get('/pembukuan/pajak/pdf', [\App\Http\Controllers\BukuPembantuPajakController::class, 'pdf'])->name('pembukuan.pajak.pdf');
        Route::get('/pembukuan/pajak', [\App\Http\Controllers\BukuPembantuPajakController::class, 'index'])->name('pembukuan.pajak.index');
        Route::get('/pembukuan/pajak/{potongan}', [\App\Http\Controllers\BukuPembantuPajakController::class, 'show'])
            ->whereNumber('potongan')
            ->name('pembukuan.pajak.show');

        Route::get('/pembukuan/pengesahan-belanja/pdf', [\App\Http\Controllers\BukuPengesahanBelanjaController::class, 'pdf'])->name('pembukuan.pengesahan.pdf');
        Route::get('/pembukuan/pengesahan-belanja', [\App\Http\Controllers\BukuPengesahanBelanjaController::class, 'index'])->name('pembukuan.pengesahan.index');
        Route::get('/pembukuan/pengesahan-belanja/{laporan}', [\App\Http\Controllers\BukuPengesahanBelanjaController::class, 'show'])
            ->whereNumber('laporan')
            ->name('pembukuan.pengesahan.show');

        Route::get('/pembukuan/piutang', [\App\Http\Controllers\PengecekanPembayaranPiutangController::class, 'index'])
            ->name('pembukuan.piutang.index');

        Route::get('/pajak-potongan', [\App\Http\Controllers\PenyetoranPajakController::class, 'index'])->name('pajak-potongan.index');
        Route::get('/pajak-potongan/{potongan}/detail', [\App\Http\Controllers\PenyetoranPajakController::class, 'show'])->name('pajak-potongan.detail');
        Route::post('/pajak-potongan/{potongan}/billing', [\App\Http\Controllers\PenyetoranPajakController::class, 'storeBilling'])->name('pajak-potongan.billing');
        Route::post('/pajak-potongan/{potongan}/ntpn', [\App\Http\Controllers\PenyetoranPajakController::class, 'storeNtpn'])->name('pajak-potongan.ntpn');
        Route::get('/pajak-potongan/{potongan}/cetak', [\App\Http\Controllers\PenyetoranPajakController::class, 'cetak'])->name('pajak-potongan.cetak');

        // Penyetoran Pajak — Kontrak
        Route::get('/pajak-potongan/kontrak', [\App\Http\Controllers\PenyetoranPajakKontrakController::class, 'index'])->name('pajak-potongan.kontrak.index');
        Route::get('/pajak-potongan/kontrak/{potongan}/detail', [\App\Http\Controllers\PenyetoranPajakKontrakController::class, 'show'])->name('pajak-potongan.kontrak.detail');
        Route::post('/pajak-potongan/kontrak/{potongan}/billing', [\App\Http\Controllers\PenyetoranPajakKontrakController::class, 'storeBilling'])->name('pajak-potongan.kontrak.billing');
        Route::post('/pajak-potongan/kontrak/{potongan}/ntpn', [\App\Http\Controllers\PenyetoranPajakKontrakController::class, 'storeNtpn'])->name('pajak-potongan.kontrak.ntpn');
        Route::get('/pajak-potongan/kontrak/{potongan}/cetak', [\App\Http\Controllers\PenyetoranPajakKontrakController::class, 'cetak'])->name('pajak-potongan.kontrak.cetak');
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
