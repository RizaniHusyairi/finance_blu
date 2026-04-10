<?php

use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ContractController;
use App\Http\Controllers\ContractAddendumController;
use App\Http\Controllers\ContractTermController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CoaController;
use App\Http\Controllers\DipaController;
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

    // Manajemen Kontrak (Kontrak, Addendum, Termin)
    Route::middleware('role:Super Admin|Pejabat Pengadaan|PPK')->group(function () {
        Route::get('/tagihan/kontrak/create', [\App\Http\Controllers\TagihanController::class, 'createKontrak'])->name('tagihan.kontrak.create');
        Route::post('/tagihan/kontrak/store', [\App\Http\Controllers\TagihanController::class, 'storeKontrak'])->name('tagihan.kontrak.store');
        Route::get('/tagihan/kontrak/{id}', [\App\Http\Controllers\TagihanController::class, 'showKontrak'])->name('tagihan.kontrak.show');
        Route::post('/tagihan/kontrak/{id}/submit', [\App\Http\Controllers\TagihanController::class, 'submitKontrak'])->name('tagihan.kontrak.submit');
        Route::post('/tagihan/kontrak/{id}/arsip', [\App\Http\Controllers\TagihanController::class, 'uploadArsipKontrak'])->name('tagihan.kontrak.upload-arsip');
        Route::get('/tagihan/kontrak/{id}/export/{type}', [\App\Http\Controllers\TagihanController::class, 'exportPdfKontrak'])->name('tagihan.kontrak.export-pdf');

        Route::get('/contracts/verifikasi', [ContractController::class, 'verifikasiIndex'])->name('contracts.verifikasi');
        Route::get('/contracts/verifikasi/{id}', [ContractController::class, 'verifikasiShow'])->name('contracts.verifikasi.show');
        Route::get('/contracts/{contract}/ringkasan-kontrak/export-pdf', [ContractController::class, 'exportRingkasanKontrakPdf'])->name('contracts.ringkasan.export-pdf');
        Route::post('/contracts/{contract}/ringkasan-kontrak/upload-final', [ContractController::class, 'uploadRingkasanKontrakFinal'])->name('contracts.ringkasan.upload-final');
        Route::get('/contracts/{contract}/spk/export-pdf', [ContractController::class, 'exportSpkPdf'])->name('contracts.spk.export-pdf');
        Route::post('/contracts/{contract}/spk/upload-final', [ContractController::class, 'uploadSpkFinal'])->name('contracts.spk.upload-final');
        Route::get('/contracts/{contract}/spmk/export-pdf', [ContractController::class, 'exportSpmkPdf'])->name('contracts.spmk.export-pdf');
        Route::post('/contracts/{contract}/spmk/upload-final', [ContractController::class, 'uploadSpmkFinal'])->name('contracts.spmk.upload-final');
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
        Route::get('/honorarium/{id}', [HonorariumController::class, 'show'])->name('honorarium.show');
        Route::get('/honorarium/{id}/edit', [HonorariumController::class, 'edit'])->name('honorarium.edit');
        Route::put('/honorarium/{id}', [HonorariumController::class, 'update'])->name('honorarium.update');
        Route::delete('/honorarium/{id}', [HonorariumController::class, 'destroy'])->name('honorarium.destroy');
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
    });

    // Verifikasi Perjaldin & SPP — PPK
    Route::middleware('role:Super Admin|PPK')->group(function () {
        // Verifikasi Tagihan Kontrak (BAST)
        Route::get('/verifikasi-ppk/tagihan-kontrak', [\App\Http\Controllers\TagihanController::class, 'ppkIndex'])->name('ppk.tagihan.kontrak.index');
        Route::get('/verifikasi-ppk/tagihan-kontrak/{id}/verify', [\App\Http\Controllers\TagihanController::class, 'verifyKontrak'])->name('ppk.tagihan.kontrak.verify');
        Route::post('/verifikasi-ppk/tagihan-kontrak/{id}/approve', [\App\Http\Controllers\TagihanController::class, 'approveKontrak'])->name('ppk.tagihan.kontrak.approve');
        Route::post('/verifikasi-ppk/tagihan-kontrak/{id}/reject', [\App\Http\Controllers\TagihanController::class, 'rejectKontrak'])->name('ppk.tagihan.kontrak.reject');

        Route::get('/verifikasi-ppk', [\App\Http\Controllers\PerjaldinVerifikasiController::class, 'ppkIndex'])->name('verifikasi-ppk.index');
        Route::post('/verifikasi-ppk/{id}/approve', [\App\Http\Controllers\PerjaldinVerifikasiController::class, 'approve'])->name('verifikasi-ppk.approve');
        Route::post('/verifikasi-ppk/{id}/revisi', [\App\Http\Controllers\PerjaldinVerifikasiController::class, 'revisi'])->name('verifikasi-ppk.revisi');
        
        // Verifikasi SPP
        Route::get('/verifikasi-ppk/spp', [\App\Http\Controllers\SppVerifikasiController::class, 'sppIndex'])->name('verifikasi-ppk.spp.index');
        Route::post('/verifikasi-ppk/spp/{spp_id}/approve', [\App\Http\Controllers\SppVerifikasiController::class, 'approveSpp'])->name('verifikasi-ppk.spp.approve');
        Route::post('/verifikasi-ppk/spp/{spp_id}/revisi', [\App\Http\Controllers\SppVerifikasiController::class, 'revisiSpp'])->name('verifikasi-ppk.spp.revisi');

        // Verifikasi NPI
        Route::get('/verifikasi-ppk/npi', [\App\Http\Controllers\NpiController::class, 'verifikasiIndex'])->name('verifikasi-ppk.npi.index');
        Route::post('/verifikasi-ppk/npi/{npi_id}/approve', [\App\Http\Controllers\NpiController::class, 'approve'])->name('verifikasi-ppk.npi.approve');
        Route::post('/verifikasi-ppk/npi/{npi_id}/revisi', [\App\Http\Controllers\NpiController::class, 'revisi'])->name('verifikasi-ppk.npi.revisi');
    });

    // Verifikasi Perjaldin & SPP — Kasubag
    Route::middleware('role:Super Admin|Kepala Subbagian Keuangan dan Tata Usaha')->group(function () {
        Route::get('/verifikasi-kasubag', [\App\Http\Controllers\PerjaldinVerifikasiController::class, 'kasubagIndex'])->name('verifikasi-kasubag.index');
        Route::post('/verifikasi-kasubag/{id}/approve', [\App\Http\Controllers\PerjaldinVerifikasiController::class, 'approve'])->name('verifikasi-kasubag.approve');
        Route::post('/verifikasi-kasubag/{id}/revisi', [\App\Http\Controllers\PerjaldinVerifikasiController::class, 'revisi'])->name('verifikasi-kasubag.revisi');
        Route::get('/verifikasi-kasubag/npi', [\App\Http\Controllers\NpiController::class, 'kasubbagIndex'])->name('verifikasi-kasubag.npi.index');
        Route::post('/verifikasi-kasubag/npi/{npi_id}/approve', [\App\Http\Controllers\NpiController::class, 'approveKasubbag'])->name('verifikasi-kasubag.npi.approve');
        Route::post('/verifikasi-kasubag/npi/{npi_id}/revisi', [\App\Http\Controllers\NpiController::class, 'revisiKasubbag'])->name('verifikasi-kasubag.npi.revisi');
        
        // Verifikasi SPP Kasubbag
        Route::get('/verifikasi-kasubag/spp', [\App\Http\Controllers\SppVerifikasiController::class, 'kasubbagIndex'])->name('verifikasi-kasubag.spp.index');
        Route::get('/verifikasi-kasubag/spp/{id}', [\App\Http\Controllers\SppVerifikasiController::class, 'kasubbagShow'])->name('verifikasi-kasubag.spp.show');
        Route::post('/verifikasi-kasubag/spp/{id}/approve', [\App\Http\Controllers\SppVerifikasiController::class, 'approveKasubbag'])->name('verifikasi-kasubag.spp.approve');
        Route::post('/verifikasi-kasubag/spp/{id}/revisi', [\App\Http\Controllers\SppVerifikasiController::class, 'revisiKasubbag'])->name('verifikasi-kasubag.spp.revisi');
    });

    // ==== VERIFIKASI NPI — Bendahara Penerimaan (TTD) ====
    Route::middleware('role:Super Admin|Bendahara Penerimaan')->group(function () {
        Route::get('/verifikasi-bendahara-penerimaan/npi', [\App\Http\Controllers\NpiController::class, 'penerimaaIndex'])->name('verifikasi-bendahara-penerimaan.npi.index');
        Route::post('/verifikasi-bendahara-penerimaan/npi/{npi_id}/approve', [\App\Http\Controllers\NpiController::class, 'approvePenerimaan'])->name('verifikasi-bendahara-penerimaan.npi.approve');
        Route::post('/verifikasi-bendahara-penerimaan/npi/{npi_id}/revisi', [\App\Http\Controllers\NpiController::class, 'revisiPenerimaan'])->name('verifikasi-bendahara-penerimaan.npi.revisi');
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
        Route::post('/spps/kontrak/{contract}/submit', [\App\Http\Controllers\SppController::class, 'submitKontrakToPpk'])->name('spps.kontrak.submit');
    });

    // Cetak PDF SPP/SPM/NPI bisa diakses oleh berbagai role terkait
    Route::middleware('auth')->group(function () {
        Route::get('/spps/{spp}/pdf', [\App\Http\Controllers\SppController::class, 'cetakPdf'])->name('spps.cetak-pdf');
        Route::get('/spms/{spm_id}/pdf', [\App\Http\Controllers\SpmController::class, 'cetakPdfSpm'])->name('spms.cetak-pdf');
        Route::get('/npis/{npi_id}/pdf', [\App\Http\Controllers\NpiController::class, 'cetakPdf'])->name('npis.cetak-pdf');
    });

    // ==== MODUL PEMBUATAN SPM (BLU) ====
    Route::middleware('role:Super Admin|Operator BLU')->group(function () {
        Route::get('/spms', [\App\Http\Controllers\SpmController::class, 'index'])->name('spms.index');
        Route::get('/spms/perjaldin/{perjaldin_id}/detail', [\App\Http\Controllers\SpmController::class, 'detail'])->name('spms.perjaldin.detail');
        Route::post('/spms/spp/{spp_id}/store', [\App\Http\Controllers\SpmController::class, 'store'])->name('spms.store');

        // SPM Kontrak
        Route::get('/spms/kontrak', [\App\Http\Controllers\SpmKontrakController::class, 'index'])->name('spms.kontrak.index');
        Route::get('/spms/kontrak/{spp}/detail', [\App\Http\Controllers\SpmKontrakController::class, 'show'])->name('spms.kontrak.detail');
        Route::post('/spms/kontrak/{spp}/store', [\App\Http\Controllers\SpmKontrakController::class, 'store'])->name('spms.kontrak.store');
        Route::post('/spms/kontrak/{spp}/submit', [\App\Http\Controllers\SpmKontrakController::class, 'submit'])->name('spms.kontrak.submit');
    });

    // ==== MODUL VERIFIKASI SPM (PPSPM) ====
    Route::middleware('role:Super Admin|PPSPM')->group(function () {
        Route::get('/verifikasi-ppspm/spm', [\App\Http\Controllers\SpmVerifikasiController::class, 'index'])->name('verifikasi-ppspm.spm.index');
        Route::post('/verifikasi-ppspm/spm/{spm_id}/approve', [\App\Http\Controllers\SpmVerifikasiController::class, 'approve'])->name('verifikasi-ppspm.spm.approve');
        Route::post('/verifikasi-ppspm/spm/{spm_id}/revisi', [\App\Http\Controllers\SpmVerifikasiController::class, 'revisi'])->name('verifikasi-ppspm.spm.revisi');

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

        // Verifikasi SPM Kontrak
        Route::get('/verifikasi-kasubag/spm/kontrak', [\App\Http\Controllers\KasubbagSpmKontrakVerifikasiController::class, 'index'])->name('verifikasi-kasubag.spm.kontrak.index');
        Route::get('/verifikasi-kasubag/spm/kontrak/{id}', [\App\Http\Controllers\KasubbagSpmKontrakVerifikasiController::class, 'show'])->name('verifikasi-kasubag.spm.kontrak.show');
        Route::post('/verifikasi-kasubag/spm/kontrak/{id}/approve', [\App\Http\Controllers\KasubbagSpmKontrakVerifikasiController::class, 'approve'])->name('verifikasi-kasubag.spm.kontrak.approve');
        Route::post('/verifikasi-kasubag/spm/kontrak/{id}/revisi', [\App\Http\Controllers\KasubbagSpmKontrakVerifikasiController::class, 'revisi'])->name('verifikasi-kasubag.spm.kontrak.revisi');
    });

    // ==== MODUL NPI (Nota Pemindahbukuan Internal) — Bendahara Pengeluaran ====
    Route::middleware('role:Super Admin|Bendahara Pengeluaran')->group(function () {
        Route::get('/npis', [\App\Http\Controllers\NpiController::class, 'index'])->name('npis.index');
        Route::get('/npis/perjaldin/{perjaldin_id}/detail', [\App\Http\Controllers\NpiController::class, 'detail'])->name('npis.perjaldin.detail');
        Route::post('/npis/spm/{spm_id}/store', [\App\Http\Controllers\NpiController::class, 'store'])->name('npis.store');
    });

    // ==== MODUL SP2D & BKU — Bendahara Pengeluaran ====
    Route::middleware('role:Super Admin|Bendahara Pengeluaran')->group(function () {
        Route::get('/sp2ds', [\App\Http\Controllers\Sp2dController::class, 'index'])->name('sp2ds.index');
        Route::get('/sp2ds/perjaldin/{perjaldin_id}/detail', [\App\Http\Controllers\Sp2dController::class, 'detail'])->name('sp2ds.perjaldin.detail');
        Route::post('/sp2ds/npi/{npi_id}/store', [\App\Http\Controllers\Sp2dController::class, 'store'])->name('sp2ds.store');
        Route::post('/sp2ds/{sp2d_id}/approve', [\App\Http\Controllers\Sp2dController::class, 'approve'])->name('sp2ds.approve');
        Route::post('/sp2ds/{sp2d_id}/execute', [\App\Http\Controllers\Sp2dController::class, 'catatBku'])->name('sp2ds.catat-bku');
    });

    

    Route::middleware('role:PPK')->group(function () {
    Route::get('/honorarium/ppk/pending', [HonorariumController::class, 'pendingPpk'])
        ->name('honorarium.ppk.pending');

    Route::get('/honorarium/ppk/{honorarium}/verify', [HonorariumController::class, 'verifyPpk'])
        ->name('ppk.tagihan.honorarium.verify');

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
