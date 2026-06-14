<?php

use App\Http\Controllers\Admin\MasterPegawaiController;
use App\Http\Controllers\Admin\NotifikasiWaController;
use App\Http\Controllers\Admin\RoleManagementController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\AdminJasaController;
use App\Http\Controllers\AdminJasaDashboardController;
use App\Http\Controllers\AdminJasaLayananController;
use App\Http\Controllers\AdminJasaTagihanController;
use App\Http\Controllers\AdminJasaUtilitasController;
use App\Http\Controllers\MonitoringPelaporanController;
use App\Http\Controllers\BendaharaHonorariumVerifikasiController;
use App\Http\Controllers\BendaharaPenerimaanDashboardController;
use App\Http\Controllers\BendaharaPengeluaranDashboardController;
use App\Http\Controllers\BtnPaymentCallbackController;
use App\Http\Controllers\BukuKasUmumController;
use App\Http\Controllers\BukuPembantuBankController;
use App\Http\Controllers\BukuPembantuBendaharaController;
use App\Http\Controllers\BukuPembantuBungaController;
use App\Http\Controllers\BukuPembantuPajakController;
use App\Http\Controllers\BukuPengesahanBelanjaController;
use App\Http\Controllers\BukuPengesahanPendapatanController;
use App\Http\Controllers\CoaController;
use App\Http\Controllers\RekeningBankController;
use App\Http\Controllers\ContractAddendumController;
use App\Http\Controllers\ContractController;
use App\Http\Controllers\ContractTermController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DipaController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\DocumentNumberController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\HonorariumController;
use App\Http\Controllers\JasaIntegrationSettingController;
use App\Http\Controllers\KontrakMitraJasaController;
use App\Http\Controllers\KpaApprovalController;
use App\Http\Controllers\MasterLayananJasaController;
use App\Http\Controllers\MasterTarifPajakController;
use App\Http\Controllers\MasterUangHarianPerjaldinController;
use App\Http\Controllers\MitraAccountController;
use App\Http\Controllers\MitraJasaController;
use App\Http\Controllers\MitraJasaKonsesiController;
use App\Http\Controllers\MitraJasaPenjualanController;
use App\Http\Controllers\MitraJasaPjp2uController;
use App\Http\Controllers\MitraLayananController;
use App\Http\Controllers\MitraPortalController;
use App\Http\Controllers\NomorTagihanJasaController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\NpiController;
use App\Http\Controllers\PengecekanPembayaranPiutangController;
use App\Http\Controllers\PenyetoranPajakController;
use App\Http\Controllers\PenyetoranPajakHonorController;
use App\Http\Controllers\PenyetoranPajakKontrakController;
use App\Http\Controllers\PerjaldinBluController;
use App\Http\Controllers\PerjaldinController;
use App\Http\Controllers\PerjaldinVerifikasiController;
use App\Http\Controllers\PerjaldinWorkflowController;
use App\Http\Controllers\PpkHonorariumVerifikasiController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicContractSignatureController;
use App\Http\Controllers\PublicContractVendorUploadController;
use App\Http\Controllers\PublicDocumentSignatureController;
use App\Http\Controllers\PublicMagicLinkSignatureController;
use App\Http\Controllers\PublicSppSignatureController;
use App\Http\Controllers\PublicTagihanActivityController;
use App\Http\Controllers\PublicTagihanJasaController;
use App\Http\Controllers\PublicTagihanJasaVerificationController;
use App\Http\Controllers\PublicTagihanSignatureController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ShortLinkController;
use App\Http\Controllers\SpmController;
use App\Http\Controllers\SppController;
use App\Http\Controllers\StandingInstructionKpaController;
use App\Http\Controllers\SuratNumberController;
use App\Http\Controllers\SuperAdminJasaDashboardController;
use App\Http\Controllers\SuperAdminJasaLaporanController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\TagihanController;
use App\Http\Controllers\TagihanHonorariumVerifikasiController;
use App\Http\Controllers\TagihanJasaController;
use App\Http\Controllers\TagihanJasaVerifikasiController;
use App\Http\Controllers\TagihanKontrakVerifikasiController;
use App\Http\Controllers\TagihanProsesController;
use App\Http\Controllers\TagihanTteController;
use App\Http\Controllers\TarifLayananController;
use App\Http\Controllers\UtilitasController;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Auth::routes();

Route::post('/integrations/btn/virtual-account/callback', BtnPaymentCallbackController::class)
    ->name('integrations.btn.virtual-account.callback')
    ->withoutMiddleware([ValidateCsrfToken::class]);
// Route publik (signed URL) untuk scan QR di PDF SPP — menampilkan aktivitas tagihan
Route::get('/aktivitas-tagihan/{id}', [PublicTagihanActivityController::class, 'show'])
    ->middleware('signed')
    ->name('public.tagihan.aktivitas');

Route::get('/tte/spp/{id}', [PublicSppSignatureController::class, 'show'])
    ->middleware('signed')
    ->name('public.spp-tte.show');
Route::get('/tte/spp/{id}/dokumen', [PublicSppSignatureController::class, 'document'])
    ->middleware('signed')
    ->name('public.spp-tte.document');
Route::get('/tte/{type}/{id}', [PublicDocumentSignatureController::class, 'show'])
    ->whereIn('type', ['spp', 'spm', 'npi', 'sp2d'])
    ->middleware('signed')
    ->name('public.document-tte.show');
Route::get('/tte/{type}/{id}/dokumen', [PublicDocumentSignatureController::class, 'document'])
    ->whereIn('type', ['spp', 'spm', 'npi', 'sp2d'])
    ->middleware('signed')
    ->name('public.document-tte.document');
Route::get('/tte/kontrak/{type}/{id}', [PublicContractSignatureController::class, 'show'])
    ->whereIn('type', ['spk', 'spmk', 'ringkasan_kontrak'])
    ->middleware('signed')
    ->name('public.contract-tte.show');
Route::get('/tte/kontrak/{type}/{id}/dokumen', [PublicContractSignatureController::class, 'document'])
    ->whereIn('type', ['spk', 'spmk', 'ringkasan_kontrak'])
    ->middleware('signed')
    ->name('public.contract-tte.document');

// TTE QR untuk dokumen turunan Tagihan (Perjaldin & Honorarium)
Route::get('/tte/tagihan/{type}/{id}', [PublicTagihanSignatureController::class, 'show'])
    ->whereIn('type', ['nominatif_perjaldin', 'daftar_nominatif_pembayaran_perjaldin', 'rekap_honorarium', 'nominatif_honorarium'])
    ->middleware('signed')
    ->name('public.tagihan-tte.show');
Route::get('/tte/tagihan/{type}/{id}/dokumen', [PublicTagihanSignatureController::class, 'document'])
    ->whereIn('type', ['nominatif_perjaldin', 'daftar_nominatif_pembayaran_perjaldin', 'rekap_honorarium', 'nominatif_honorarium'])
    ->middleware('signed')
    ->name('public.tagihan-tte.document');

// Route publik (signed URL) untuk Vendor Upload Dokumen Kontrak Final (TTD Basah)
Route::get('/p/kontrak/{id}/vendor-upload', [PublicContractVendorUploadController::class, 'show'])
    ->middleware('signed')
    ->name('public.vendor.contract-upload.show');
Route::post('/p/kontrak/{id}/vendor-upload', [PublicContractVendorUploadController::class, 'store'])
    ->middleware('signed')
    ->name('public.vendor.contract-upload.store');

// Route publik (signed URL) untuk Tagihan Jasa yang dikirim ke mitra via WhatsApp.
Route::get('/p/tagihan-jasa/{id}', [PublicTagihanJasaController::class, 'show'])
    ->middleware('signed')
    ->name('public.tagihan-jasa.show');
Route::get('/p/tagihan-jasa/{id}/pdf', [PublicTagihanJasaController::class, 'pdf'])
    ->middleware('signed')
    ->name('public.tagihan-jasa.pdf');
Route::get('/p/tagihan-jasa/{id}/verify', [PublicTagihanJasaVerificationController::class, 'show'])
    ->middleware('signed')
    ->name('public.tagihan-jasa.verify');
Route::get('/p/tagihan-jasa/{id}/surat-pengantar-tte', [PublicTagihanJasaVerificationController::class, 'document'])
    ->middleware('signed')
    ->name('public.tagihan-jasa.surat-pengantar-tte.document');

// Public Magic Link untuk TTE Dokumen Berita Acara (BAPP, BAST, BAP)
Route::get('/public/tte/sign/{token}', [PublicMagicLinkSignatureController::class, 'show'])->name('public.magic-link.show');
Route::get('/public/tte/document/{token}', [PublicMagicLinkSignatureController::class, 'documentPdf'])->name('public.magic-link.document');
Route::post('/public/tte/sign/{token}', [PublicMagicLinkSignatureController::class, 'sign'])->name('public.magic-link.sign');
Route::post('/public/tte/upload/{token}', [PublicMagicLinkSignatureController::class, 'uploadArsip'])->name('public.magic-link.upload');
Route::get('/public/tte/signed/{token}', [PublicMagicLinkSignatureController::class, 'signed'])->name('public.magic-link.signed');

// QR Code Verification untuk Dokumen Berita Acara
Route::get('/p/tagihan/{id}/document-tte/{type}', [PublicMagicLinkSignatureController::class, 'verifyQr'])
    ->middleware('signed')
    ->name('public.tagihan-document-tte.show');

// KPA Tagihan Approval via WhatsApp (Magic Link)
Route::get('/p/kpa-approval/tagihan/{tagihanId}', [KpaApprovalController::class, 'showApproval'])
    ->middleware(['web'])
    ->name('kpa.approval.show');
Route::post('/p/kpa-approval/tagihan/{tagihanId}', [KpaApprovalController::class, 'processApproval'])
    ->middleware(['web', 'auth'])
    ->name('kpa.approval.process');

// Short link redirector — link pendek di WhatsApp di-resolve ke URL publik signed.
Route::get('/i/{slug}', [ShortLinkController::class, 'show'])
    ->where('slug', '[a-zA-Z0-9]+')
    ->name('short-link.resolve');

Route::get('/', function () {
    if (! Auth::check()) {
        return redirect()->route('login');
    }

    return Auth::user()->hasAnyRole(['Mitra', 'Mitra Jasa'])
        ? redirect()->route('mitra.dashboard')
        : redirect()->route('dashboard');
});

$internalRoles = 'Super Admin|Super Admin Jasa|KPA|PLT/PLH|Kepala Subbagian Keuangan dan Tata Usaha|Kepala Seksi Pelayanan dan Kerjasama|PPK|PPSPM|Bendahara Pengeluaran|Bendahara Penerimaan|Pejabat Pengadaan|Operator BLU|PPABP|Operator Perjaldin|Koordinator Keuangan|Admin Jasa|Admin Konsesi|Koordinator Jasa';

Route::middleware(['auth', 'account.active'])->group(function () use ($internalRoles) {

    // Universal Profile
    Route::get('/profile', [ProfileController::class, 'index'])->name('profile.index');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password.update');

    // Unduh arsip keuangan sensitif (bukti setor pajak & bukti transfer SP2D) dari disk privat.
    // Otorisasi role (Bendahara Pengeluaran / Super Admin) dilakukan di dalam controller.
    Route::get('/arsip-sensitif/{arsip}/download', [DocumentController::class, 'downloadArsipSensitif'])
        ->name('arsip-sensitif.download');

    // Internal Dashboard — all internal roles
    Route::middleware("role:$internalRoles")->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'internal'])->name('dashboard');
        Route::get('/dashboard/bendahara-penerimaan', [BendaharaPenerimaanDashboardController::class, 'index'])->name('dashboard.bendahara-penerimaan');
        Route::get('/dashboard/bendahara-pengeluaran', [BendaharaPengeluaranDashboardController::class, 'index'])->name('dashboard.bendahara-pengeluaran');
        Route::get('/dashboard/ppspm', [DashboardController::class, 'ppspmDashboard'])->name('dashboard.ppspm');
        Route::get('/dashboard/koordinator-keuangan', [DashboardController::class, 'koordinatorKeuanganDashboard'])->name('dashboard.koordinator-keuangan');
        Route::get('/super-admin-jasa/dashboard', [SuperAdminJasaDashboardController::class, 'index'])
            ->middleware('role:Super Admin|Super Admin Jasa')
            ->name('super-admin-jasa.dashboard');
        Route::get('/koordinator-jasa', [SuperAdminJasaDashboardController::class, 'index'])
            ->middleware('role:Koordinator Jasa')
            ->name('koordinator-jasa.dashboard');
    });

    Route::middleware('role:Super Admin|Super Admin Jasa|Koordinator Jasa|Admin Jasa')
        ->prefix('admin-jasa')
        ->name('admin-jasa.')
        ->group(function () {
            Route::get('/dashboard', [AdminJasaDashboardController::class, 'index'])
                ->name('dashboard');
            Route::get('/mitra', [AdminJasaTagihanController::class, 'mitra'])
                ->name('mitra');
            Route::view('/panduan', 'admin_jasa.panduan')
                ->name('panduan');
        });

    // Jatuh Tempo — diperluas ke KPA/PLT/PLH (read-only akses untuk monitoring)
    Route::middleware('role:Super Admin|Super Admin Jasa|Koordinator Jasa|Admin Jasa|KPA|PLT/PLH')
        ->prefix('admin-jasa')
        ->name('admin-jasa.')
        ->group(function () {
            Route::get('/tagihan/jatuh-tempo', [AdminJasaTagihanController::class, 'jatuhTempo'])
                ->name('tagihan.jatuh-tempo');
        });

    // Log Tagihan Bulanan — diperluas ke verifikator jasa (Koord. Jasa, Kasi PK, Kasubbag TU, KPA, PLT/PLH)
    Route::middleware('role:Super Admin|Super Admin Jasa|Koordinator Jasa|Admin Jasa|Kepala Seksi Pelayanan dan Kerjasama|Kepala Subbagian Keuangan dan Tata Usaha|KPA|PLT/PLH')
        ->prefix('admin-jasa')
        ->name('admin-jasa.')
        ->group(function () {
            Route::get('/tagihan/log-bulanan', [AdminJasaTagihanController::class, 'logBulanan'])
                ->name('tagihan.log-bulanan');
            Route::get('/tagihan/log-bulanan/export/{format}', [AdminJasaTagihanController::class, 'exportLogBulanan'])
                ->where('format', 'pdf|excel')
                ->name('tagihan.log-bulanan.export');
        });

    // Laporan — Super Admin Jasa: rekap tagihan, terima setor, pembayaran, piutang, performa mitra
    Route::middleware('role:Super Admin|Super Admin Jasa|Bendahara Penerimaan')
        ->prefix('super-admin-jasa/laporan')
        ->name('super-admin-jasa.laporan.')
        ->group(function () {
            Route::get('/rekap-tagihan', [SuperAdminJasaLaporanController::class, 'rekapTagihan'])
                ->name('rekap-tagihan');
            Route::get('/rekap-layanan', [SuperAdminJasaLaporanController::class, 'rekapLayanan'])
                ->name('rekap-layanan');
            Route::get('/rekap-terima-setor', [SuperAdminJasaLaporanController::class, 'rekapTerimaSetor'])
                ->name('rekap-terima-setor');
            Route::get('/rekap-pembayaran', [SuperAdminJasaLaporanController::class, 'rekapPembayaran'])
                ->name('rekap-pembayaran');
            Route::get('/rekap-piutang', [SuperAdminJasaLaporanController::class, 'rekapPiutang'])
                ->name('rekap-piutang');
            Route::get('/performa-mitra', [SuperAdminJasaLaporanController::class, 'performaMitra'])
                ->name('performa-mitra');
            Route::get('/{report}/export/{format}', [SuperAdminJasaLaporanController::class, 'export'])
                ->where('report', 'rekap-tagihan|rekap-layanan|rekap-terima-setor|rekap-pembayaran|rekap-piutang|performa-mitra')
                ->where('format', 'pdf|excel')
                ->name('export');
        });

    // Workflow Engine General Routes


    // Notification Endpoints (AJAX Polling)
    Route::get('/notifications/fetch', [NotificationController::class, 'fetch'])->name('notifications.fetch');
    Route::post('/notifications/mark-read', [NotificationController::class, 'markAsRead'])->name('notifications.mark-read');
    Route::post('/notifications/{id}/mark-read', [NotificationController::class, 'markOneAsRead'])->name('notifications.mark-read-one');

    Route::middleware('role:Super Admin|Operator BLU|PPK|PPSPM|Bendahara Pengeluaran|Bendahara Penerimaan|Koordinator Keuangan|Kepala Subbagian Keuangan dan Tata Usaha|KPA')
        ->prefix('proses-tagihan')
        ->name('proses-tagihan.')
        ->group(function () {
            Route::get('/', [TagihanProsesController::class, 'index'])->name('index');
            Route::get('/{tagihan}', [TagihanProsesController::class, 'show'])->name('show');
            Route::post('/{tagihan}/coa', [TagihanProsesController::class, 'simpanCoa'])->name('coa');
            Route::post('/{tagihan}/pajak-kontrak', [TagihanProsesController::class, 'simpanPajak'])->name('pajak-kontrak');
            Route::post('/{tagihan}/spp/ajukan', [TagihanProsesController::class, 'ajukanSpp'])->name('spp.ajukan');
            Route::post('/{tagihan}/spm/ajukan', [TagihanProsesController::class, 'ajukanSpm'])->name('spm.ajukan');
            Route::post('/{tagihan}/npi/ajukan', [TagihanProsesController::class, 'ajukanNpi'])->name('npi.ajukan');
            Route::post('/{tagihan}/dokumen/{jenis}/aksi', [TagihanProsesController::class, 'aksiDokumen'])
                ->whereIn('jenis', ['spp', 'spm', 'npi', 'sp2d'])
                ->name('dokumen.aksi');
            Route::post('/{tagihan}/bukti-transfer', [TagihanProsesController::class, 'uploadBuktiTransfer'])->name('bukti-transfer');
            Route::post('/{tagihan}/batalkan-rantai', [TagihanProsesController::class, 'batalkanRantai'])->name('batalkan-rantai');
        });

    // Verifikasi Perjaldin - hanya PPK
    Route::middleware('role:Super Admin|PPK')->group(function () {
        Route::get('/perjaldin-blu', [PerjaldinBluController::class, 'index'])->name('perjaldin-blu.index');
        Route::get('/perjaldin-blu/riwayat', [PerjaldinBluController::class, 'history'])->name('perjaldin-blu.history');
        Route::get('/perjaldin-blu/{id}', [PerjaldinBluController::class, 'show'])->name('perjaldin-blu.show');
        Route::post('/perjaldin-blu/{id}/approve', [PerjaldinBluController::class, 'approve'])->name('perjaldin-blu.approve');
        Route::post('/perjaldin-blu/{id}/reject', [PerjaldinBluController::class, 'reject'])->name('perjaldin-blu.reject');
    });

    // Master Data — Pegawai & Pejabat (legacy alias → diarahkan ke modul Administrasi baru)
    Route::middleware('role:Super Admin|Operator BLU|PPABP')->group(function () {
        Route::get('/employees', fn () => redirect()->route('admin.pegawai.index'))->name('employees.index');
    });

    // === Modul Administrasi (khusus Super Admin) ===
    Route::middleware('role:Super Admin')->prefix('admin')->name('admin.')->group(function () {
        // Master Pegawai
        Route::resource('pegawai', MasterPegawaiController::class)
            ->parameters(['pegawai' => 'pegawai']);
        Route::patch('pegawai/{pegawai}/toggle', [MasterPegawaiController::class, 'toggle'])
            ->name('pegawai.toggle');

        // User Management
        Route::resource('users', UserManagementController::class)
            ->parameters(['users' => 'user']);
        Route::post('users/{user}/reset-password', [UserManagementController::class, 'resetPassword'])
            ->name('users.reset-password');
        Route::patch('users/{user}/roles', [UserManagementController::class, 'syncRoles'])
            ->name('users.roles.sync');

        // Roles (read-only)
        Route::get('roles', [RoleManagementController::class, 'index'])->name('roles.index');
        Route::get('roles/{role}', [RoleManagementController::class, 'show'])->name('roles.show');

        // Manajemen Notifikasi WhatsApp
        Route::get('notifikasi-wa', [NotifikasiWaController::class, 'index'])->name('notifikasi-wa.index');
        Route::put('notifikasi-wa', [NotifikasiWaController::class, 'update'])->name('notifikasi-wa.update');
        Route::post('notifikasi-wa/test', [NotifikasiWaController::class, 'test'])->name('notifikasi-wa.test');
        Route::post('notifikasi-wa/run-now', [NotifikasiWaController::class, 'runReminderNow'])->name('notifikasi-wa.run-now');
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

    // Cek ketersediaan nomor urut KU (AJAX) — dipakai form Honorarium (PPABP)
    // dan Perjaldin (Operator Perjaldin) saat user mengetik nomor urut manual.
    Route::middleware('role:Super Admin|PPABP|Operator Perjaldin|Koordinator Keuangan')
        ->get('/ku-numbers/check', [SuratNumberController::class, 'check'])
        ->name('ku-numbers.check');

    // Manajemen Nomor Surat KU — role Koordinator Keuangan (Honorarium, Perjaldin, Surat Pengantar Jasa)
    Route::middleware('role:Super Admin|Koordinator Keuangan')->group(function () {
        Route::get('/surat-numbers', [SuratNumberController::class, 'index'])->name('surat-numbers.index');
        Route::get('/surat-numbers/check', [SuratNumberController::class, 'check'])->name('surat-numbers.check');
        Route::post('/surat-numbers', [SuratNumberController::class, 'store'])->name('surat-numbers.store');
        Route::post('/surat-numbers/{suratNumber}/cancel', [SuratNumberController::class, 'cancel'])->name('surat-numbers.cancel');
    });

    // Master Data — Rekening Bank (kelola rekening + saldo awal). Diakses oleh
    // admin master data dan bendahara (yang menetapkan saldo awal rekeningnya).
    Route::middleware('role:Super Admin|Operator BLU|Kepala Subbagian Keuangan dan Tata Usaha|Bendahara Penerimaan|Bendahara Pengeluaran')->group(function () {
        Route::get('/rekening-bank/create', [RekeningBankController::class, 'create'])->name('rekening-bank.create');
        Route::post('/rekening-bank', [RekeningBankController::class, 'store'])->name('rekening-bank.store');
        Route::get('/rekening-bank', [RekeningBankController::class, 'index'])->name('rekening-bank.index');
        Route::get('/rekening-bank/{rekening}', [RekeningBankController::class, 'show'])->whereNumber('rekening')->name('rekening-bank.show');
        Route::get('/rekening-bank/{rekening}/edit', [RekeningBankController::class, 'edit'])->whereNumber('rekening')->name('rekening-bank.edit');
        Route::put('/rekening-bank/{rekening}', [RekeningBankController::class, 'update'])->whereNumber('rekening')->name('rekening-bank.update');
        Route::post('/rekening-bank/{rekening}/toggle', [RekeningBankController::class, 'toggle'])->whereNumber('rekening')->name('rekening-bank.toggle');
        Route::delete('/rekening-bank/{rekening}', [RekeningBankController::class, 'destroy'])->whereNumber('rekening')->name('rekening-bank.destroy');
    });

    // Master Data — DIPA
    Route::middleware('role:Super Admin|KPA|PLT/PLH|Operator BLU|PPK|Kepala Subbagian Keuangan dan Tata Usaha|Kepala Seksi Pelayanan dan Kerjasama')->group(function () {
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

    // Master Data — Layanan Jasa
    Route::middleware('role:Super Admin|Super Admin Jasa|Operator BLU|Koordinator Keuangan|Admin Jasa|Koordinator Jasa')->group(function () {
        Route::resource('master-layanan-jasa', MasterLayananJasaController::class);
        Route::get('/layanan-tarif-jasa', [TarifLayananController::class, 'index'])->name('tarif-layanan.index');
        Route::get('/layanan-tarif-jasa/kategori/{kategori}', [TarifLayananController::class, 'showKategori'])->name('tarif-layanan.kategori.show');
        Route::get('/layanan-tarif-jasa/item/{item}', [TarifLayananController::class, 'showItem'])->name('tarif-layanan.item.show');
        Route::resource('/jasa/mitra', MitraJasaController::class)->except(['destroy'])->names('jasa.mitra');
        Route::delete('/jasa/mitra/{mitra}', [MitraJasaController::class, 'destroy'])->name('jasa.mitra.destroy');
        Route::post('/jasa/mitra/{mitra}/account', [MitraAccountController::class, 'store'])->name('jasa.mitra.account.store');
        Route::post('/jasa/mitra/{mitra}/account/reset', [MitraAccountController::class, 'reset'])->name('jasa.mitra.account.reset');
        Route::get('/jasa/mitra/{mitra}/kontrak/create', [KontrakMitraJasaController::class, 'create'])->name('jasa.mitra.kontrak.create');
        Route::post('/jasa/mitra/{mitra}/kontrak', [KontrakMitraJasaController::class, 'store'])->name('jasa.mitra.kontrak.store');
        Route::get('/jasa/mitra/{mitra}/kontrak/{kontrak}', [KontrakMitraJasaController::class, 'show'])->name('jasa.mitra.kontrak.show');
        Route::get('/jasa/mitra/{mitra}/kontrak/{kontrak}/edit', [KontrakMitraJasaController::class, 'edit'])->name('jasa.mitra.kontrak.edit');
        Route::put('/jasa/mitra/{mitra}/kontrak/{kontrak}', [KontrakMitraJasaController::class, 'update'])->name('jasa.mitra.kontrak.update');
        Route::delete('/jasa/mitra/{mitra}/kontrak/{kontrak}', [KontrakMitraJasaController::class, 'destroy'])->name('jasa.mitra.kontrak.destroy');
        Route::get('/jasa/mitra/{mitra}/kontrak/{kontrak}/download', [KontrakMitraJasaController::class, 'download'])->name('jasa.mitra.kontrak.download');
        Route::get('/jasa/konsesi', [MitraJasaKonsesiController::class, 'index'])->name('jasa.konsesi.index');
        Route::get('/jasa/mitra/{mitra}/konsesi/create', [MitraJasaKonsesiController::class, 'create'])->name('jasa.mitra.konsesi.create');
        Route::post('/jasa/mitra/{mitra}/konsesi', [MitraJasaKonsesiController::class, 'store'])->name('jasa.mitra.konsesi.store');
        Route::get('/jasa/mitra/{mitra}/konsesi/{konsesi}/edit', [MitraJasaKonsesiController::class, 'edit'])->name('jasa.mitra.konsesi.edit');
        Route::put('/jasa/mitra/{mitra}/konsesi/{konsesi}', [MitraJasaKonsesiController::class, 'update'])->name('jasa.mitra.konsesi.update');
        Route::patch('/jasa/mitra/{mitra}/konsesi/{konsesi}/deactivate', [MitraJasaKonsesiController::class, 'deactivate'])->name('jasa.mitra.konsesi.deactivate');
        Route::get('/jasa/mitra/{mitra}/pjp2u/create', [MitraJasaPjp2uController::class, 'create'])->name('jasa.mitra.pjp2u.create');
        Route::post('/jasa/mitra/{mitra}/pjp2u', [MitraJasaPjp2uController::class, 'store'])->name('jasa.mitra.pjp2u.store');
        Route::get('/jasa/mitra/{mitra}/pjp2u/{pjp2u}/edit', [MitraJasaPjp2uController::class, 'edit'])->name('jasa.mitra.pjp2u.edit');
        Route::put('/jasa/mitra/{mitra}/pjp2u/{pjp2u}', [MitraJasaPjp2uController::class, 'update'])->name('jasa.mitra.pjp2u.update');
        Route::patch('/jasa/mitra/{mitra}/pjp2u/{pjp2u}/deactivate', [MitraJasaPjp2uController::class, 'deactivate'])->name('jasa.mitra.pjp2u.deactivate');
        Route::get('/jasa/mitra/{mitra}/layanan', [MitraLayananController::class, 'edit'])->name('jasa.mitra.layanan.edit');
        Route::put('/jasa/mitra/{mitra}/layanan', [MitraLayananController::class, 'update'])->name('jasa.mitra.layanan.update');
        Route::resource('/jasa/admin', AdminJasaController::class)->except(['destroy'])->parameters(['admin' => 'admin'])->names('jasa.admin');
        Route::delete('/jasa/admin/{admin}', [AdminJasaController::class, 'destroy'])->name('jasa.admin.destroy');
        Route::get('/jasa/admin/{user}/layanan', [AdminJasaLayananController::class, 'edit'])->name('jasa.admin.layanan.edit');
        Route::put('/jasa/admin/{user}/layanan', [AdminJasaLayananController::class, 'update'])->name('jasa.admin.layanan.update');
    });

    // Integrasi API — hanya Super Admin
    Route::middleware('role:Super Admin')->group(function () {
        Route::get('/jasa/integrasi', [JasaIntegrationSettingController::class, 'index'])->name('jasa.integrasi.index');
        Route::put('/jasa/integrasi', [JasaIntegrationSettingController::class, 'update'])->name('jasa.integrasi.update');
        Route::post('/jasa/integrasi/whatsapp/test', [JasaIntegrationSettingController::class, 'testWhatsapp'])->name('jasa.integrasi.whatsapp.test');
        Route::post('/jasa/integrasi/email/test', [JasaIntegrationSettingController::class, 'testEmail'])->name('jasa.integrasi.email.test');
    });

    // Monitoring Pelaporan — read-only, dibuka untuk oversight (KPA, PLT/PLH, Kasi PK, Kasubag TU) + pelaku verifikasi laporan.
    Route::middleware('role:Super Admin|Super Admin Jasa|Operator BLU|Koordinator Keuangan|Admin Jasa|Admin Konsesi|Koordinator Jasa|KPA|PLT/PLH|Kepala Seksi Pelayanan dan Kerjasama|Kepala Subbagian Keuangan dan Tata Usaha')->group(function () {
        Route::get('/jasa/monitoring-pelaporan', [MonitoringPelaporanController::class, 'index'])->name('jasa.monitoring-pelaporan.index');
        Route::get('/jasa/monitoring-pelaporan/export/{format}', [MonitoringPelaporanController::class, 'export'])->name('jasa.monitoring-pelaporan.export');
    });

    // Verifikasi Laporan Mitra (Konsesi & PJP2U) + aksi ingatkan — hanya pelaku verifikasi laporan,
    // BUKAN verifikator tagihan berjenjang (Kasi/Kasubag/KPA hanya boleh memantau via Monitoring di atas).
    Route::middleware('role:Super Admin|Super Admin Jasa|Operator BLU|Koordinator Keuangan|Admin Jasa|Admin Konsesi|Koordinator Jasa')->group(function () {
        Route::post('/jasa/monitoring-pelaporan/ingatkan', [MonitoringPelaporanController::class, 'remind'])->name('jasa.monitoring-pelaporan.remind');
        Route::post('/jasa/monitoring-pelaporan/ingatkan-semua', [MonitoringPelaporanController::class, 'remindAll'])->name('jasa.monitoring-pelaporan.remind-all');
        Route::get('/jasa/laporan-penjualan', [MitraJasaPenjualanController::class, 'index'])->name('jasa.mitra.penjualan.index');
        Route::get('/jasa/laporan-pjp2u', [MitraJasaPenjualanController::class, 'indexPjp2u'])->name('jasa.mitra.pjp2u.index');
        Route::get('/jasa/laporan-pjp2u/rekap/{mitra}/{layanan}/{tahun}/{bulan}', [MitraJasaPenjualanController::class, 'showPjp2uRekap'])
            ->whereNumber('tahun')
            ->whereNumber('bulan')
            ->name('jasa.mitra.pjp2u.rekap.show');
        Route::get('/jasa/mitra/{mitra}/penjualan/{penjualan}', [MitraJasaPenjualanController::class, 'show'])->name('jasa.mitra.penjualan.show');
    });

    Route::middleware('role:Super Admin|Super Admin Jasa|Operator BLU|Koordinator Keuangan|Admin Jasa|Admin Konsesi|Koordinator Jasa')->group(function () {
        Route::get('/jasa/mitra/{mitra}/penjualan/create', [MitraJasaPenjualanController::class, 'create'])->name('jasa.mitra.penjualan.create');
        Route::post('/jasa/mitra/{mitra}/penjualan', [MitraJasaPenjualanController::class, 'store'])->name('jasa.mitra.penjualan.store');
        Route::get('/jasa/mitra/{mitra}/penjualan/{penjualan}/edit', [MitraJasaPenjualanController::class, 'edit'])->name('jasa.mitra.penjualan.edit');
        Route::put('/jasa/mitra/{mitra}/penjualan/{penjualan}', [MitraJasaPenjualanController::class, 'update'])->name('jasa.mitra.penjualan.update');
        Route::post('/jasa/mitra/{mitra}/penjualan/{penjualan}/submit', [MitraJasaPenjualanController::class, 'submit'])->name('jasa.mitra.penjualan.submit');
        Route::post('/jasa/mitra/{mitra}/penjualan/{penjualan}/verify', [MitraJasaPenjualanController::class, 'verify'])->name('jasa.mitra.penjualan.verify');
        Route::post('/jasa/mitra/{mitra}/penjualan/{penjualan}/reject', [MitraJasaPenjualanController::class, 'reject'])->name('jasa.mitra.penjualan.reject');
    });

    // Manajemen Kontrak (Kontrak, Addendum, Termin)
    Route::middleware('role:Super Admin|Pejabat Pengadaan|PPK')->group(function () {
        Route::get('/tagihan/kontrak/create', [TagihanController::class, 'createKontrak'])->name('tagihan.kontrak.create');
        Route::post('/tagihan/kontrak/store', [TagihanController::class, 'storeKontrak'])->name('tagihan.kontrak.store');
        Route::get('/tagihan/kontrak/{id}', [TagihanController::class, 'showKontrak'])->name('tagihan.kontrak.show');
        Route::post('/tagihan/kontrak/{id}/send-tte', [TagihanTteController::class, 'sendTte'])->name('tagihan.kontrak.send-tte');
        Route::post('/tagihan/kontrak/{id}/submit', [TagihanController::class, 'submitKontrak'])->name('tagihan.kontrak.submit');
        Route::post('/tagihan/kontrak/{id}/arsip', [TagihanController::class, 'uploadArsipKontrak'])->name('tagihan.kontrak.upload-arsip');
        Route::get('/tagihan/kontrak/{id}/arsip/{arsipId}', [TagihanController::class, 'viewArsipKontrak'])->name('tagihan.kontrak.view-arsip');
        Route::get('/tagihan/kontrak/{id}/export/{type}', [TagihanController::class, 'exportPdfKontrak'])->name('tagihan.kontrak.export-pdf');
    });

    // Tagihan Jasa (PNBP) — pembuatan tagihan dibatasi ke role pembuat (admin),
    // verifikator jasa hanya boleh melihat/approve.
    Route::middleware('role:Super Admin|Admin Jasa|Admin Konsesi')->group(function () {
        Route::get('/tagihan-jasa/create', [TagihanJasaController::class, 'create'])->name('tagihan-jasa.create');
        Route::get('/tagihan-jasa/preview-nomor', [TagihanJasaController::class, 'previewNomorTagihan'])->name('tagihan-jasa.preview-nomor');
        Route::post('/tagihan-jasa', [TagihanJasaController::class, 'store'])->name('tagihan-jasa.store');
        Route::get('/tagihan-jasa/{id}/edit', [TagihanJasaController::class, 'edit'])->name('tagihan-jasa.edit');
        Route::put('/tagihan-jasa/{id}', [TagihanJasaController::class, 'update'])->name('tagihan-jasa.update');
        Route::post('/tagihan-jasa/{id}/resubmit', [TagihanJasaController::class, 'resubmit'])->name('tagihan-jasa.resubmit');
    });

    // Menu Nomor Tagihan Jasa — Super Admin Jasa (set nomor urut awal + monitoring)
    Route::middleware('role:Super Admin|Super Admin Jasa')->group(function () {
        Route::get('/nomor-tagihan-jasa', [NomorTagihanJasaController::class, 'index'])->name('nomor-tagihan-jasa.index');
        Route::post('/nomor-tagihan-jasa/nomor-awal', [NomorTagihanJasaController::class, 'updateNomorAwal'])->name('nomor-tagihan-jasa.nomor-awal');
    });

    Route::middleware('role:Super Admin|Super Admin Jasa|Admin Jasa|Admin Konsesi|Koordinator Jasa|Kepala Seksi Pelayanan dan Kerjasama|Kepala Subbagian Keuangan dan Tata Usaha|KPA|PLT/PLH')->group(function () {
        Route::get('/tagihan-jasa', [TagihanJasaController::class, 'index'])->name('tagihan-jasa.index');
        Route::get('/tagihan-jasa/{id}/surat-pengantar', [TagihanJasaController::class, 'generateSuratPengantarPdf'])->name('tagihan-jasa.surat-pengantar');
        Route::put('/tagihan-jasa/{id}/surat-pengantar', [TagihanJasaController::class, 'updateSuratPengantarDraft'])->name('tagihan-jasa.surat-pengantar.update');
        Route::get('/tagihan-jasa/{id}/surat-pengantar-final', [TagihanJasaController::class, 'viewSuratPengantarFinal'])->name('tagihan-jasa.surat-pengantar-final.view');
        Route::post('/tagihan-jasa/{id}/surat-pengantar-final', [TagihanJasaController::class, 'uploadSuratPengantarFinal'])->name('tagihan-jasa.surat-pengantar-final.upload');
        Route::get('/tagihan-jasa/{id}/surat-pengantar-arsip/{arsipId}', [TagihanJasaController::class, 'viewSuratPengantarArchive'])->name('tagihan-jasa.surat-pengantar-arsip.view');
        Route::get('/tagihan-jasa/{id}', [TagihanJasaController::class, 'show'])->name('tagihan-jasa.show');
        Route::get('/tagihan-jasa/{id}/pdf', [TagihanJasaController::class, 'generateInvoicePdf'])->name('tagihan-jasa.pdf');
        Route::post('/tagihan-jasa/{id}/publish', [TagihanJasaController::class, 'publish'])->name('tagihan-jasa.publish');
        Route::post('/tagihan-jasa/{id}/mark-lunas', [TagihanJasaController::class, 'markAsPaid'])->name('tagihan-jasa.mark-lunas');
        Route::post('/tagihan-jasa/{id}/auto-approve', [TagihanJasaController::class, 'autoApproveAll'])->name('tagihan-jasa.auto-approve');
        Route::delete('/tagihan-jasa/{id}', [TagihanJasaController::class, 'destroy'])->name('tagihan-jasa.destroy');
        Route::post('/tagihan-jasa/{id}/cancel', [TagihanJasaController::class, 'cancel'])->name('tagihan-jasa.cancel');

        // Verifikasi Tagihan Jasa
        Route::post('/tagihan-jasa/{id}/approve', [TagihanJasaVerifikasiController::class, 'approve'])->name('tagihan-jasa.approve');
        Route::post('/tagihan-jasa/{id}/revision', [TagihanJasaVerifikasiController::class, 'revision'])->name('tagihan-jasa.revision');
        Route::post('/tagihan-jasa/{id}/reject', [TagihanJasaVerifikasiController::class, 'reject'])->name('tagihan-jasa.reject');
    });

    // Verifikasi Tagihan Kontrak — multi-role (PPK, PPSPM, Koor.Keu, Bend×2, Kasubbag)
    Route::middleware('role:PPK|PPSPM|Koordinator Keuangan|Bendahara Pengeluaran|Bendahara Penerimaan|Kepala Subbagian Keuangan dan Tata Usaha|Super Admin')->group(function () {
        Route::get('/verifikasi-tagihan-kontrak', [TagihanKontrakVerifikasiController::class, 'index'])->name('verifikasi-tagihan-kontrak.index');
        Route::get('/verifikasi-tagihan-kontrak/{id}', [TagihanKontrakVerifikasiController::class, 'show'])->name('verifikasi-tagihan-kontrak.show');
        Route::get('/verifikasi-tagihan-kontrak/{id}/kontrak-arsip/{jenis}', [TagihanKontrakVerifikasiController::class, 'viewKontrakArsip'])->name('verifikasi-tagihan-kontrak.kontrak-arsip');
        Route::get('/verifikasi-tagihan-kontrak/{id}/arsip/{arsipId}', [TagihanKontrakVerifikasiController::class, 'viewArsip'])->name('verifikasi-tagihan-kontrak.arsip');
        Route::post('/verifikasi-tagihan-kontrak/{id}/approve', [TagihanKontrakVerifikasiController::class, 'approve'])->name('verifikasi-tagihan-kontrak.approve');
        Route::post('/verifikasi-tagihan-kontrak/{id}/revisi', [TagihanKontrakVerifikasiController::class, 'revisi'])->name('verifikasi-tagihan-kontrak.revisi');
        Route::post('/verifikasi-tagihan-kontrak/{id}/reject', [TagihanKontrakVerifikasiController::class, 'reject'])->name('verifikasi-tagihan-kontrak.reject');

        // Verifikasi Tagihan Honorarium — multi-role (pola sama dengan Kontrak)
        Route::get('/verifikasi-tagihan-honorarium', [TagihanHonorariumVerifikasiController::class, 'index'])->name('verifikasi-tagihan-honorarium.index');
        Route::get('/verifikasi-tagihan-honorarium/{id}', [TagihanHonorariumVerifikasiController::class, 'show'])->name('verifikasi-tagihan-honorarium.show');
        Route::get('/verifikasi-tagihan-honorarium/{id}/arsip/{arsipId}', [TagihanHonorariumVerifikasiController::class, 'viewArsip'])->name('verifikasi-tagihan-honorarium.arsip');
        Route::post('/verifikasi-tagihan-honorarium/{id}/approve', [TagihanHonorariumVerifikasiController::class, 'approve'])->name('verifikasi-tagihan-honorarium.approve');
        Route::post('/verifikasi-tagihan-honorarium/{id}/revisi', [TagihanHonorariumVerifikasiController::class, 'revisi'])->name('verifikasi-tagihan-honorarium.revisi');
        Route::post('/verifikasi-tagihan-honorarium/{id}/reject', [TagihanHonorariumVerifikasiController::class, 'reject'])->name('verifikasi-tagihan-honorarium.reject');
    });

    // Verifikasi Tagihan Jasa - multi-role
    Route::middleware('role:Super Admin|Super Admin Jasa|Koordinator Jasa|Kepala Seksi Pelayanan dan Kerjasama|Kepala Subbagian Keuangan dan Tata Usaha|KPA|PLT/PLH')->group(function () {
        Route::get('/verifikasi-tagihan-jasa', [TagihanJasaVerifikasiController::class, 'index'])->name('verifikasi-tagihan-jasa.index');
        Route::get('/verifikasi-tagihan-jasa/{id}', [TagihanJasaVerifikasiController::class, 'show'])->name('verifikasi-tagihan-jasa.show');
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
        Route::post('/contracts/{contract}/send-wa-vendor', [ContractController::class, 'sendWaVendor'])->name('contracts.send-wa-vendor');
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
    });

    // PDF Honorarium (Rekap & Nominatif) — read-only untuk pembuat, Operator BLU,
    // dan KPA/PLT saat meninjau lampiran dari halaman persetujuan tagihan.
    Route::middleware('role:Super Admin|PPABP|Operator BLU|KPA|PLT/PLH|PPSPM|Koordinator Keuangan|Kepala Subbagian Keuangan dan Tata Usaha')->group(function () {
        Route::get('/honorarium/{id}/pdf', [HonorariumController::class, 'exportPdf'])->name('honorarium.pdf');
        Route::get('/honorarium/{id}/pdf-nominatif', [HonorariumController::class, 'exportNominatifPdf'])->name('honorarium.pdf-nominatif');
    });

    // Manajemen Perjaldin — Operator Perjaldin
    Route::middleware('role:Super Admin|Operator Perjaldin')->group(function () {
        Route::get('/perjaldins', [PerjaldinController::class, 'index'])->name('perjaldins.index');
        Route::get('/perjaldins/create', [PerjaldinController::class, 'create'])->name('perjaldins.create');
        Route::post('/perjaldins', [PerjaldinController::class, 'store'])->name('perjaldins.store');
        Route::post('/perjaldins/bulk-submit', [PerjaldinController::class, 'bulkSubmit'])->name('perjaldins.bulk-submit');
        Route::get('/perjaldins/{id}', [PerjaldinController::class, 'show'])->name('perjaldins.show');
        Route::get('/perjaldins/{id}/edit', [PerjaldinController::class, 'editPerjaldin'])->name('perjaldins.edit-perjaldin');
        Route::put('/perjaldins/{id}', [PerjaldinController::class, 'updatePerjaldin'])->name('perjaldins.update-perjaldin');
        Route::delete('/perjaldins/{id}', [PerjaldinController::class, 'destroyPerjaldin'])->name('perjaldins.destroy-perjaldin');
        Route::post('/perjaldins/{id}/upload-nominatif-ttd', [PerjaldinController::class, 'uploadNominatifTtd'])->name('perjaldins.upload-nominatif-ttd');
        Route::get('/perjaldins/{id}/nominatif-ttd/{arsipId}', [PerjaldinController::class, 'viewNominatifTtd'])->name('perjaldins.view-nominatif-ttd');

        // Master Data Uang Harian Perjaldin
        Route::resource('master-uang-harian-perjaldin', MasterUangHarianPerjaldinController::class)
            ->names('master-uang-harian-perjaldin')
            ->except(['show']);        // Perjaldin Workflow
        Route::post('/perjaldins/{id}/workflow/submit', [PerjaldinWorkflowController::class, 'submit'])->name('perjaldin.workflow.submit');
        Route::post('/perjaldins/workflow/approval/{approvalId}/approve', [PerjaldinWorkflowController::class, 'approve'])->name('perjaldin.workflow.approve');
        Route::post('/perjaldins/workflow/approval/{approvalId}/revision', [PerjaldinWorkflowController::class, 'revision'])->name('perjaldin.workflow.revision');
        Route::post('/perjaldins/workflow/approval/{approvalId}/reject', [PerjaldinWorkflowController::class, 'reject'])->name('perjaldin.workflow.reject');

    });

    // PDF Perjaldin (Nominatif & Daftar Nominatif Pembayaran) — read-only untuk
    // pembuat, verifikator, dan KPA saat meninjau lampiran dari Dokumen Tagihan.
    Route::middleware('role:Super Admin|Operator Perjaldin|Operator BLU|PPK|PPSPM|Bendahara Pengeluaran|Bendahara Penerimaan|Koordinator Keuangan|Kepala Subbagian Keuangan dan Tata Usaha|KPA|PLT/PLH')->group(function () {
        Route::get('/perjaldins/{id}/pdf', [PerjaldinController::class, 'exportPdf'])->name('perjaldins.pdf');
        Route::get('/perjaldins/{id}/pdf/nominatif', [PerjaldinController::class, 'exportPdfNominatif'])->name('perjaldins.pdf-nominatif');
        Route::get('/perjaldins/{id}/pdf/lampiran', [PerjaldinController::class, 'exportPdfLampiran'])->name('perjaldins.pdf-lampiran');
        Route::get('/perjaldins/{id}/pdf/perincian/{detail}', [PerjaldinController::class, 'exportPdfPerincian'])->name('perjaldins.pdf-perincian');
    });

    // Verifikasi Perjaldin & SPP — PPK
    Route::middleware('role:Super Admin|PPK')->group(function () {
        // Verifikasi Tagihan Kontrak (BAST)
        Route::get('/verifikasi-ppk/tagihan-kontrak', [TagihanController::class, 'ppkIndex'])->name('ppk.tagihan.kontrak.index');
        Route::get('/verifikasi-ppk/tagihan-kontrak/{id}/verify', [TagihanController::class, 'verifyKontrak'])->name('ppk.tagihan.kontrak.verify');
        Route::post('/verifikasi-ppk/tagihan-kontrak/{id}/approve', [TagihanController::class, 'approveKontrak'])->name('ppk.tagihan.kontrak.approve');
        Route::post('/verifikasi-ppk/tagihan-kontrak/{id}/reject', [TagihanController::class, 'rejectKontrak'])->name('ppk.tagihan.kontrak.reject');

        // Verifikasi Perjaldin — PPK (halaman baru)
        Route::get('/verifikasi-ppk/perjaldin', [PerjaldinVerifikasiController::class, 'ppkIndex'])->name('verifikasi-ppk.perjaldin.index');
        Route::get('/verifikasi-ppk/perjaldin/{id}', [PerjaldinVerifikasiController::class, 'ppkShow'])->name('verifikasi-ppk.perjaldin.show');
        Route::post('/verifikasi-ppk/perjaldin/{id}/approve', [PerjaldinVerifikasiController::class, 'approve'])->name('verifikasi-ppk.perjaldin.approve');
        Route::post('/verifikasi-ppk/perjaldin/{id}/revisi', [PerjaldinVerifikasiController::class, 'revisi'])->name('verifikasi-ppk.perjaldin.revisi');
        // Legacy redirect
        Route::get('/verifikasi-ppk', fn () => redirect()->route('verifikasi-ppk.perjaldin.index'))->name('verifikasi-ppk.index');

        // Honorarium - Verifikasi PPK (Parallel Workflow)
        Route::get('/verifikasi-ppk/honorarium', [PpkHonorariumVerifikasiController::class, 'index'])->name('verifikasi-ppk.honorarium.index');
        Route::get('/verifikasi-ppk/honorarium/{id}', [PpkHonorariumVerifikasiController::class, 'show'])->name('verifikasi-ppk.honorarium.show');
        Route::post('/verifikasi-ppk/honorarium/{id}/approve', [PpkHonorariumVerifikasiController::class, 'approve'])->name('verifikasi-ppk.honorarium.approve');
        Route::post('/verifikasi-ppk/honorarium/{id}/revisi', [PpkHonorariumVerifikasiController::class, 'revisi'])->name('verifikasi-ppk.honorarium.revisi');
    });

    // Verifikasi Perjaldin — Bendahara Pengeluaran (halaman baru)
    Route::middleware('role:Super Admin|Bendahara Pengeluaran')->group(function () {
        Route::get('/verifikasi-bendahara/perjaldin', [PerjaldinVerifikasiController::class, 'bendaharaIndex'])->name('verifikasi-bendahara.perjaldin.index');
        Route::get('/verifikasi-bendahara/perjaldin/{id}', [PerjaldinVerifikasiController::class, 'bendaharaShow'])->name('verifikasi-bendahara.perjaldin.show');
        Route::post('/verifikasi-bendahara/perjaldin/{id}/approve', [PerjaldinVerifikasiController::class, 'bendaharaApprove'])->name('verifikasi-bendahara.perjaldin.approve');
        Route::post('/verifikasi-bendahara/perjaldin/{id}/revisi', [PerjaldinVerifikasiController::class, 'bendaharaRevisi'])->name('verifikasi-bendahara.perjaldin.revisi');
        // Legacy redirect
        Route::get('/verifikasi-bendahara', fn () => redirect()->route('verifikasi-bendahara.perjaldin.index'))->name('verifikasi-bendahara.index');

        // Honorarium - Verifikasi Bendahara Pengeluaran (Parallel Workflow)
        Route::get('/verifikasi-bendahara/honorarium', [BendaharaHonorariumVerifikasiController::class, 'index'])->name('verifikasi-bendahara.honorarium.index');
        Route::get('/verifikasi-bendahara/honorarium/{id}', [BendaharaHonorariumVerifikasiController::class, 'show'])->name('verifikasi-bendahara.honorarium.show');
        Route::post('/verifikasi-bendahara/honorarium/{id}/approve', [BendaharaHonorariumVerifikasiController::class, 'approve'])->name('verifikasi-bendahara.honorarium.approve');
        Route::post('/verifikasi-bendahara/honorarium/{id}/revisi', [BendaharaHonorariumVerifikasiController::class, 'revisi'])->name('verifikasi-bendahara.honorarium.revisi');
    });

    // Verifikasi Perjaldin & SPP — Kasubag
    Route::middleware('role:Super Admin|Kepala Subbagian Keuangan dan Tata Usaha')->group(function () {
        Route::get('/verifikasi-kasubag', [PerjaldinVerifikasiController::class, 'kasubagIndex'])->name('verifikasi-kasubag.index');
        Route::get('/verifikasi-kasubag/perjaldin/{id}', [PerjaldinVerifikasiController::class, 'kasubagShow'])->name('verifikasi-kasubag.perjaldin.show');
        Route::post('/verifikasi-kasubag/perjaldin/{id}/approve', [PerjaldinVerifikasiController::class, 'kasubagApprove'])->name('verifikasi-kasubag.perjaldin.approve');
        Route::post('/verifikasi-kasubag/perjaldin/{id}/revisi', [PerjaldinVerifikasiController::class, 'kasubagRevisi'])->name('verifikasi-kasubag.perjaldin.revisi');
    });

    // Verifikasi Tagihan — Koordinator Keuangan
    Route::middleware('role:Super Admin|Koordinator Keuangan')->group(function () {
        Route::get('/verifikasi-koordinator/honorarium', [PpkHonorariumVerifikasiController::class, 'index'])->name('verifikasi-koordinator.honorarium.index');
        Route::get('/verifikasi-koordinator/honorarium/{id}', [PpkHonorariumVerifikasiController::class, 'show'])->name('verifikasi-koordinator.honorarium.show');
        Route::post('/verifikasi-koordinator/honorarium/{id}/approve', [PpkHonorariumVerifikasiController::class, 'approve'])->name('verifikasi-koordinator.honorarium.approve');
        Route::post('/verifikasi-koordinator/honorarium/{id}/revisi', [PpkHonorariumVerifikasiController::class, 'revisi'])->name('verifikasi-koordinator.honorarium.revisi');

        Route::get('/verifikasi-koordinator/perjaldin', [PerjaldinVerifikasiController::class, 'koordinatorIndex'])->name('verifikasi-koordinator.perjaldin.index');
        Route::get('/verifikasi-koordinator/perjaldin/{id}', [PerjaldinVerifikasiController::class, 'koordinatorShow'])->name('verifikasi-koordinator.perjaldin.show');
        Route::post('/verifikasi-koordinator/perjaldin/{id}/approve', [PerjaldinVerifikasiController::class, 'koordinatorApprove'])->name('verifikasi-koordinator.perjaldin.approve');
        Route::post('/verifikasi-koordinator/perjaldin/{id}/revisi', [PerjaldinVerifikasiController::class, 'koordinatorRevisi'])->name('verifikasi-koordinator.perjaldin.revisi');
    });

    // ==== STANDING INSTRUCTION ====
    // Daftar SPP (Standing Instruction) yang diajukan PPK ke KPA — monitoring KPA.
    Route::middleware('role:Super Admin|KPA|PLT/PLH')->group(function () {
        Route::get('/standing-instruction', [StandingInstructionKpaController::class, 'index'])->name('standing-instruction.index');
    });

    // KPA Approval Request dari PPK — diajukan dari halaman Proses Tagihan / verifikasi tagihan
    Route::middleware('role:Super Admin|PPK')->group(function () {
        Route::post('/verifikasi-tagihan/{tagihanId}/kpa-approval/send-wa', [KpaApprovalController::class, 'sendWa'])->name('kpa.approval.send-wa');
    });

    // ==== VERIFIKASI TAGIHAN PERJALDIN — Bendahara Penerimaan ====
    Route::middleware('role:Super Admin|Bendahara Penerimaan')->group(function () {
        Route::get('/verifikasi-bendahara-penerimaan/perjaldin', [PerjaldinVerifikasiController::class, 'bendaharaPenerimaanIndex'])->name('verifikasi-bendahara-penerimaan.perjaldin.index');
        Route::get('/verifikasi-bendahara-penerimaan/perjaldin/{id}', [PerjaldinVerifikasiController::class, 'bendaharaPenerimaanShow'])->name('verifikasi-bendahara-penerimaan.perjaldin.show');
        Route::post('/verifikasi-bendahara-penerimaan/perjaldin/{id}/approve', [PerjaldinVerifikasiController::class, 'bendaharaPenerimaanApprove'])->name('verifikasi-bendahara-penerimaan.perjaldin.approve');
        Route::post('/verifikasi-bendahara-penerimaan/perjaldin/{id}/revisi', [PerjaldinVerifikasiController::class, 'bendaharaPenerimaanRevisi'])->name('verifikasi-bendahara-penerimaan.perjaldin.revisi');
    });

    // Cetak PDF SPP/SPM/NPI/SP2D bisa diakses oleh berbagai role terkait
    Route::middleware('auth')->group(function () {
        Route::get('/spps/{spp}/pdf', [SppController::class, 'cetakPdf'])->name('spps.cetak-pdf');
        Route::get('/spms/{spm_id}/pdf', [SpmController::class, 'cetakPdfSpm'])->name('spms.cetak-pdf');
        Route::get('/npis/{npi_id}/pdf', [NpiController::class, 'cetakPdf'])->name('npis.cetak-pdf');
        Route::get('/sp2ds/{sp2d}/pdf', [DocumentController::class, 'printSp2d'])->name('sp2ds.cetak-pdf');
    });

    // ==== MODUL VERIFIKASI TAGIHAN PERJALDIN (PPSPM) ====
    Route::middleware('role:Super Admin|PPSPM')->group(function () {
        Route::get('/verifikasi-ppspm/perjaldin', [PerjaldinVerifikasiController::class, 'ppspmIndex'])->name('verifikasi-ppspm.perjaldin.index');
        Route::get('/verifikasi-ppspm/perjaldin/{id}', [PerjaldinVerifikasiController::class, 'ppspmShow'])->name('verifikasi-ppspm.perjaldin.show');
        Route::post('/verifikasi-ppspm/perjaldin/{id}/approve', [PerjaldinVerifikasiController::class, 'ppspmApprove'])->name('verifikasi-ppspm.perjaldin.approve');
        Route::post('/verifikasi-ppspm/perjaldin/{id}/revisi', [PerjaldinVerifikasiController::class, 'ppspmRevisi'])->name('verifikasi-ppspm.perjaldin.revisi');
    });

    // ==== MODUL PENYETORAN PAJAK — Bendahara Pengeluaran ====
    Route::middleware('role:Super Admin|Bendahara Pengeluaran|Bendahara Penerimaan')->group(function () {
        Route::get('/pembukuan/bku/pdf', [BukuKasUmumController::class, 'pdf'])->name('pembukuan.bku.pdf');
        Route::get('/pembukuan/bku/excel', [BukuKasUmumController::class, 'excel'])->name('pembukuan.bku.excel');
        Route::get('/pembukuan/bku', [BukuKasUmumController::class, 'index'])->name('pembukuan.bku.index');
        Route::post('/pembukuan/bku/saldo-awal', [BukuKasUmumController::class, 'storeSaldoAwal'])->name('pembukuan.bku.saldo-awal.store');
        Route::get('/pembukuan/bku/{id}', [BukuKasUmumController::class, 'show'])
            ->whereNumber('id')
            ->name('pembukuan.bku.show');

        Route::get('/pembukuan/bank/mutasi', [BukuPembantuBankController::class, 'mutasi'])->name('pembukuan.bank.mutasi');
        Route::get('/pembukuan/bank/rekonsiliasi', [BukuPembantuBankController::class, 'rekonsiliasi'])->name('pembukuan.bank.rekonsiliasi');
        Route::get('/pembukuan/bank', [BukuPembantuBankController::class, 'index'])->name('pembukuan.bank.index');
        Route::get('/pembukuan/bank/{rekening}', [BukuPembantuBankController::class, 'show'])
            ->whereNumber('rekening')
            ->name('pembukuan.bank.show');

        Route::get('/pembukuan/bendahara/pdf', [BukuPembantuBendaharaController::class, 'pdf'])->name('pembukuan.bendahara.pdf');
        Route::get('/pembukuan/bendahara', [BukuPembantuBendaharaController::class, 'index'])->name('pembukuan.bendahara.index');

        Route::get('/pembukuan/bunga-rekening/pdf', [BukuPembantuBungaController::class, 'pdf'])->name('pembukuan.bunga.pdf');
        Route::get('/pembukuan/bunga-rekening', [BukuPembantuBungaController::class, 'index'])->name('pembukuan.bunga.index');

        Route::get('/pembukuan/pajak/pdf', [BukuPembantuPajakController::class, 'pdf'])->name('pembukuan.pajak.pdf');
        Route::get('/pembukuan/pajak', [BukuPembantuPajakController::class, 'index'])->name('pembukuan.pajak.index');
        Route::get('/pembukuan/pajak/{potongan}', [BukuPembantuPajakController::class, 'show'])
            ->whereNumber('potongan')
            ->name('pembukuan.pajak.show');

        // Pengesahan Belanja khusus Bendahara Pengeluaran — Bendahara Penerimaan
        // memakai Buku Pengesahan Pendapatan.
        Route::middleware('role:Super Admin|Bendahara Pengeluaran')->group(function () {
            Route::get('/pembukuan/pengesahan-belanja/pdf', [BukuPengesahanBelanjaController::class, 'pdf'])->name('pembukuan.pengesahan.pdf');
            Route::get('/pembukuan/pengesahan-belanja', [BukuPengesahanBelanjaController::class, 'index'])->name('pembukuan.pengesahan.index');
            Route::get('/pembukuan/pengesahan-belanja/{laporan}', [BukuPengesahanBelanjaController::class, 'show'])
                ->whereNumber('laporan')
                ->name('pembukuan.pengesahan.show');
        });

        // Pengesahan Pendapatan khusus Bendahara Penerimaan — lensa sisi
        // penerimaan atas record laporan_pengesahan_blu yang sama.
        Route::middleware('role:Super Admin|Bendahara Penerimaan')->group(function () {
            Route::get('/pembukuan/pengesahan-pendapatan/pdf', [BukuPengesahanPendapatanController::class, 'pdf'])->name('pembukuan.pengesahan-pendapatan.pdf');
            Route::get('/pembukuan/pengesahan-pendapatan', [BukuPengesahanPendapatanController::class, 'index'])->name('pembukuan.pengesahan-pendapatan.index');
            Route::get('/pembukuan/pengesahan-pendapatan/{laporan}', [BukuPengesahanPendapatanController::class, 'show'])
                ->whereNumber('laporan')
                ->name('pembukuan.pengesahan-pendapatan.show');
        });

        // Generator laporan pengesahan per periode — record bersama, boleh
        // dibuat oleh kedua bendahara.
        Route::post('/pembukuan/pengesahan/generate', [BukuPengesahanPendapatanController::class, 'generate'])
            ->name('pembukuan.pengesahan.generate');

        Route::get('/pembukuan/piutang', [PengecekanPembayaranPiutangController::class, 'index'])
            ->name('pembukuan.piutang.index');

        // Penyetoran Pajak — Honor (didefinisikan SEBELUM route /pajak-potongan/{potongan}/... agar matching benar)
        Route::middleware('role:Super Admin|Bendahara Pengeluaran')->group(function () {
            Route::get('/pajak-potongan/honor', [PenyetoranPajakHonorController::class, 'index'])
                ->name('pajak-potongan.honor.index');
            Route::get('/pajak-potongan/honor/bupot/{detail_honorarium}', [PenyetoranPajakHonorController::class, 'bupot'])
                ->name('pajak-potongan.honor.bupot');
            Route::get('/pajak-potongan/honor/{potongan}/detail', [PenyetoranPajakHonorController::class, 'show'])
                ->name('pajak-potongan.honor.detail');
            Route::post('/pajak-potongan/honor/{potongan}/billing', [PenyetoranPajakHonorController::class, 'storeBilling'])
                ->name('pajak-potongan.honor.billing');
            Route::post('/pajak-potongan/honor/{potongan}/ntpn', [PenyetoranPajakHonorController::class, 'storeNtpn'])
                ->name('pajak-potongan.honor.ntpn');
            Route::get('/pajak-potongan/honor/{potongan}/cetak', [PenyetoranPajakHonorController::class, 'cetak'])
                ->name('pajak-potongan.honor.cetak');
        });

        Route::get('/pajak-potongan', [PenyetoranPajakController::class, 'index'])->name('pajak-potongan.index');
        Route::get('/pajak-potongan/{potongan}/detail', [PenyetoranPajakController::class, 'show'])->name('pajak-potongan.detail');
        Route::post('/pajak-potongan/{potongan}/billing', [PenyetoranPajakController::class, 'storeBilling'])->name('pajak-potongan.billing');
        Route::post('/pajak-potongan/{potongan}/ntpn', [PenyetoranPajakController::class, 'storeNtpn'])->name('pajak-potongan.ntpn');
        Route::get('/pajak-potongan/{potongan}/cetak', [PenyetoranPajakController::class, 'cetak'])->name('pajak-potongan.cetak');

        // Penyetoran Pajak — Kontrak
        Route::get('/pajak-potongan/kontrak', [PenyetoranPajakKontrakController::class, 'index'])->name('pajak-potongan.kontrak.index');
        Route::get('/pajak-potongan/kontrak/{potongan}/detail', [PenyetoranPajakKontrakController::class, 'show'])->name('pajak-potongan.kontrak.detail');
        Route::post('/pajak-potongan/kontrak/{potongan}/billing', [PenyetoranPajakKontrakController::class, 'storeBilling'])->name('pajak-potongan.kontrak.billing');
        Route::post('/pajak-potongan/kontrak/{potongan}/ntpn', [PenyetoranPajakKontrakController::class, 'storeNtpn'])->name('pajak-potongan.kontrak.ntpn');
        Route::get('/pajak-potongan/kontrak/{potongan}/cetak', [PenyetoranPajakKontrakController::class, 'cetak'])->name('pajak-potongan.kontrak.cetak');
    });

    // Laporan BKU
    Route::middleware('role:Super Admin|KPA|PLT/PLH|Kepala Subbagian Keuangan dan Tata Usaha|Kepala Seksi Pelayanan dan Kerjasama|PPK|Bendahara Pengeluaran|Bendahara Penerimaan')->group(function () {
        Route::get('/reports/bku', [ReportController::class, 'bku'])->name('reports.bku');
        Route::get('/reports/bku/pdf', [ReportController::class, 'bkuPdf'])->name('reports.bku.pdf');
    });

    // Admin Listrik & Admin Air Portal
    Route::middleware('role:Admin Listrik|Admin Air')->group(function () {
        Route::get('/utilitas/dashboard', [UtilitasController::class, 'index'])->name('utilitas.dashboard');
        Route::get('/utilitas/last-stan-akhir', [UtilitasController::class, 'getLastStanAkhir'])->name('utilitas.last-stan-akhir');
        Route::post('/utilitas/laporan', [UtilitasController::class, 'store'])->name('utilitas.store');
        Route::post('/utilitas/laporan/{id}/submit', [UtilitasController::class, 'submit'])->name('utilitas.submit');
        Route::delete('/utilitas/laporan/{id}', [UtilitasController::class, 'destroy'])->name('utilitas.destroy');
    });

    // Admin Jasa - Verifikasi & Tagihan Utilitas
    Route::middleware('role:Super Admin Jasa|Admin Jasa')->group(function () {
        Route::get('/jasa/utilitas', [AdminJasaUtilitasController::class, 'index'])->name('jasa.utilitas.index');
        Route::get('/jasa/utilitas/{id}', [AdminJasaUtilitasController::class, 'show'])->name('jasa.utilitas.show');
        Route::post('/jasa/utilitas/{id}/buat-tagihan', [AdminJasaUtilitasController::class, 'buatTagihan'])->name('jasa.utilitas.buat-tagihan');
        Route::post('/jasa/utilitas/{id}/tolak', [AdminJasaUtilitasController::class, 'tolak'])->name('jasa.utilitas.tolak');
    });

    // Mitra Portal
    Route::middleware('role:Mitra|Mitra Jasa')->group(function () {
        Route::get('/mitra', [DashboardController::class, 'mitra'])->name('mitra.dashboard');
        Route::get('/mitra/profil', [MitraPortalController::class, 'profile'])->name('mitra.profile');
        Route::put('/mitra/profil/password', [MitraPortalController::class, 'updatePassword'])->name('mitra.profile.password.update');
        Route::get('/mitra/layanan-aktif', [MitraPortalController::class, 'layananAktif'])->name('mitra.layanan-aktif');
        Route::get('/mitra/konsesi-penjualan', [MitraPortalController::class, 'konsesiPenjualan'])->name('mitra.konsesi-penjualan');
        Route::get('/mitra/pjp2u-penjualan', [MitraPortalController::class, 'pjp2uPenjualan'])->name('mitra.pjp2u-penjualan');
        Route::get('/mitra/laporan-penjualan/create', [MitraPortalController::class, 'createPenjualan'])->name('mitra.penjualan.create');
        Route::post('/mitra/laporan-penjualan', [MitraPortalController::class, 'storePenjualan'])->name('mitra.penjualan.store');
        Route::get('/mitra/laporan-penjualan/{penjualan}', [MitraPortalController::class, 'showPenjualan'])->name('mitra.penjualan.show');
        Route::delete('/mitra/laporan-penjualan/{penjualan}', [MitraPortalController::class, 'destroyPenjualan'])->name('mitra.penjualan.destroy');
        Route::post('/mitra/laporan-penjualan/{penjualan}/submit', [MitraPortalController::class, 'submitPenjualan'])->name('mitra.penjualan.submit');

        // Pax Input for PJP2U
        Route::get('/mitra/laporan-pax/create', [MitraPortalController::class, 'createPax'])->name('mitra.pax.create');
        Route::post('/mitra/laporan-pax', [MitraPortalController::class, 'storePax'])->name('mitra.pax.store');
        Route::get('/mitra/tagihan-jasa/{id}', [MitraPortalController::class, 'showTagihanJasa'])->name('mitra.tagihan-jasa.show');
        Route::get('/mitra/tagihan-jasa/{id}/pdf', [MitraPortalController::class, 'invoiceTagihanJasaPdf'])->name('mitra.tagihan-jasa.pdf');
        Route::get('/mitra/tagihan-jasa/{id}/surat-pengantar-final', [MitraPortalController::class, 'downloadSuratPengantarFinal'])->name('mitra.tagihan-jasa.surat-final');
        Route::get('/mitra/kontrak-jasa/{kontrak}/download', [MitraPortalController::class, 'downloadKontrak'])->name('mitra.kontrak-jasa.download');
    });
});
// Template Preview Route
Route::get('/template', [HomeController::class, 'index'])->name('template.dashboard');

Route::get('{any}', [HomeController::class, 'root'])->where('any', '.*');
