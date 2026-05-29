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
use App\Http\Controllers\BendaharaHonorariumVerifikasiController;
use App\Http\Controllers\BendaharaPenerimaanDashboardController;
use App\Http\Controllers\BendaharaPengeluaranDashboardController;
use App\Http\Controllers\BenpenNpiKontrakVerifikasiController;
use App\Http\Controllers\BtnPaymentCallbackController;
use App\Http\Controllers\BukuKasUmumController;
use App\Http\Controllers\BukuPembantuBankController;
use App\Http\Controllers\BukuPembantuBendaharaController;
use App\Http\Controllers\BukuPembantuBungaController;
use App\Http\Controllers\BukuPembantuPajakController;
use App\Http\Controllers\BukuPengesahanBelanjaController;
use App\Http\Controllers\CoaController;
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
use App\Http\Controllers\KasubbagNpiKontrakVerifikasiController;
use App\Http\Controllers\KasubbagSp2dKontrakVerifikasiController;
use App\Http\Controllers\KasubbagSpmKontrakVerifikasiController;
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
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\NpiController;
use App\Http\Controllers\NpiHonorController;
use App\Http\Controllers\NpiKontrakController;
use App\Http\Controllers\NpiPerjaldinController;
use App\Http\Controllers\PengecekanPembayaranPiutangController;
use App\Http\Controllers\PenyetoranPajakController;
use App\Http\Controllers\PenyetoranPajakHonorController;
use App\Http\Controllers\PenyetoranPajakKontrakController;
use App\Http\Controllers\PerjaldinBluController;
use App\Http\Controllers\PerjaldinController;
use App\Http\Controllers\PerjaldinKomponenController;
use App\Http\Controllers\PerjaldinVerifikasiController;
use App\Http\Controllers\PerjaldinWorkflowController;
use App\Http\Controllers\PpkHonorariumVerifikasiController;
use App\Http\Controllers\PpkNpiKontrakVerifikasiController;
use App\Http\Controllers\PpkSp2dKontrakVerifikasiController;
use App\Http\Controllers\PpspmSpmKontrakVerifikasiController;
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
use App\Http\Controllers\Sp2dController;
use App\Http\Controllers\Sp2dHonorController;
use App\Http\Controllers\Sp2dKontrakController;
use App\Http\Controllers\Sp2dPerjaldinController;
use App\Http\Controllers\SpmController;
use App\Http\Controllers\SpmHonorController;
use App\Http\Controllers\SpmKontrakController;
use App\Http\Controllers\SpmPerjaldinController;
use App\Http\Controllers\SpmPerjaldinVerifikasiController;
use App\Http\Controllers\SpmVerifikasiController;
use App\Http\Controllers\SppController;
use App\Http\Controllers\SppPerjaldinVerifikasiController;
use App\Http\Controllers\SppVerifikasiController;
use App\Http\Controllers\SppWorkflowController;
use App\Http\Controllers\SuperAdminJasaDashboardController;
use App\Http\Controllers\SuperAdminJasaLaporanController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\TagihanController;
use App\Http\Controllers\TagihanHonorariumVerifikasiController;
use App\Http\Controllers\TagihanJasaController;
use App\Http\Controllers\TagihanJasaVerifikasiController;
use App\Http\Controllers\TagihanKontrakVerifikasiController;
use App\Http\Controllers\TagihanTteController;
use App\Http\Controllers\TarifLayananController;
use App\Http\Controllers\UtilitasController;
use App\Http\Controllers\VerifikasiNpiHonorController;
use App\Http\Controllers\VerifikasiNpiPerjaldinController;
use App\Http\Controllers\VerifikasiSp2dHonorController;
use App\Http\Controllers\VerifikasiSp2dPerjaldinController;
use App\Http\Controllers\VerifikasiSpmHonorController;
use App\Http\Controllers\VerifikasiSppHonorController;
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
Route::get('/public/tte/signed/{token}', [PublicMagicLinkSignatureController::class, 'signed'])->name('public.magic-link.signed');

// QR Code Verification untuk Dokumen Berita Acara
Route::get('/p/tagihan/{id}/document-tte/{type}', [PublicMagicLinkSignatureController::class, 'verifyQr'])
    ->middleware('signed')
    ->name('public.tagihan-document-tte.show');

// KPA Tagihan Approval via WhatsApp (Magic Link)
Route::get('/p/kpa-approval/spp/{sppId}', [KpaApprovalController::class, 'showApproval'])
    ->middleware(['signed', 'web'])
    ->name('kpa.approval.show');
Route::post('/p/kpa-approval/spp/{sppId}', [KpaApprovalController::class, 'processApproval'])
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

    // Unduh arsip keuangan sensitif (bukti setor pajak & bukti transfer SP2D) dari disk privat.
    // Otorisasi role (Bendahara Pengeluaran / Super Admin) dilakukan di dalam controller.
    Route::get('/arsip-sensitif/{arsip}/download', [DocumentController::class, 'downloadArsipSensitif'])
        ->name('arsip-sensitif.download');

    // Internal Dashboard — all internal roles
    Route::middleware("role:$internalRoles")->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'internal'])->name('dashboard');
        Route::get('/dashboard/bendahara-penerimaan', [BendaharaPenerimaanDashboardController::class, 'index'])->name('dashboard.bendahara-penerimaan');
        Route::get('/dashboard/bendahara-pengeluaran', [BendaharaPengeluaranDashboardController::class, 'index'])->name('dashboard.bendahara-pengeluaran');
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
    Route::post('/workflow/approval/{approvalId}/approve', [PerjaldinWorkflowController::class, 'approve'])->name('perjaldin.workflow.approve');
    Route::post('/workflow/approval/{approvalId}/revision', [PerjaldinWorkflowController::class, 'revision'])->name('perjaldin.workflow.revision');
    Route::post('/workflow/approval/{approvalId}/reject', [PerjaldinWorkflowController::class, 'reject'])->name('perjaldin.workflow.reject');

    // Notification Endpoints (AJAX Polling)
    Route::get('/notifications/fetch', [NotificationController::class, 'fetch'])->name('notifications.fetch');
    Route::post('/notifications/mark-read', [NotificationController::class, 'markAsRead'])->name('notifications.mark-read');

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
    });

    // Index Laporan Mitra (Konsesi & PJP2U) — read-only, dibuka untuk verifikator (KPA, PLT/PLH, Kasi PK, Kasubag TU)
    Route::middleware('role:Super Admin|Super Admin Jasa|Operator BLU|Koordinator Keuangan|Admin Jasa|Admin Konsesi|Koordinator Jasa|KPA|PLT/PLH|Kepala Seksi Pelayanan dan Kerjasama|Kepala Subbagian Keuangan dan Tata Usaha')->group(function () {
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
        Route::post('/tagihan-jasa', [TagihanJasaController::class, 'store'])->name('tagihan-jasa.store');
        Route::get('/tagihan-jasa/{id}/edit', [TagihanJasaController::class, 'edit'])->name('tagihan-jasa.edit');
        Route::put('/tagihan-jasa/{id}', [TagihanJasaController::class, 'update'])->name('tagihan-jasa.update');
        Route::post('/tagihan-jasa/{id}/resubmit', [TagihanJasaController::class, 'resubmit'])->name('tagihan-jasa.resubmit');
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
        Route::get('/perjaldins/{id}/pdf', [PerjaldinController::class, 'exportPdf'])->name('perjaldins.pdf');
        Route::get('/perjaldins/{id}/pdf/nominatif', [PerjaldinController::class, 'exportPdfNominatif'])->name('perjaldins.pdf-nominatif');
        Route::get('/perjaldins/{id}/pdf/lampiran', [PerjaldinController::class, 'exportPdfLampiran'])->name('perjaldins.pdf-lampiran');
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

        // Verifikasi NPI
        Route::get('/verifikasi-ppk/npi', [NpiController::class, 'verifikasiIndex'])->name('verifikasi-ppk.npi.index');
        Route::post('/verifikasi-ppk/npi/{npi_id}/approve', [NpiController::class, 'approve'])->name('verifikasi-ppk.npi.approve');
        Route::post('/verifikasi-ppk/npi/{npi_id}/revisi', [NpiController::class, 'revisi'])->name('verifikasi-ppk.npi.revisi');

        // NPI Kontrak — Verifikasi PPK (Parallel Workflow)
        Route::get('/verifikasi-ppk/npi/kontrak', [PpkNpiKontrakVerifikasiController::class, 'index'])->name('verifikasi-ppk.npi.kontrak.index');
        Route::get('/verifikasi-ppk/npi/kontrak/{id}', [PpkNpiKontrakVerifikasiController::class, 'show'])->name('verifikasi-ppk.npi.kontrak.show');
        Route::post('/verifikasi-ppk/npi/kontrak/{id}/approve', [PpkNpiKontrakVerifikasiController::class, 'approve'])->name('verifikasi-ppk.npi.kontrak.approve');
        Route::post('/verifikasi-ppk/npi/kontrak/{id}/revisi', [PpkNpiKontrakVerifikasiController::class, 'revisi'])->name('verifikasi-ppk.npi.kontrak.revisi');

        // SP2D Kontrak — Verifikasi PPK (Parallel Workflow)
        Route::get('/verifikasi-ppk/sp2d/kontrak', [PpkSp2dKontrakVerifikasiController::class, 'index'])->name('verifikasi-ppk.sp2d.kontrak.index');
        Route::get('/verifikasi-ppk/sp2d/kontrak/{id}', [PpkSp2dKontrakVerifikasiController::class, 'show'])->name('verifikasi-ppk.sp2d.kontrak.show');
        Route::post('/verifikasi-ppk/sp2d/kontrak/{id}/approve', [PpkSp2dKontrakVerifikasiController::class, 'approve'])->name('verifikasi-ppk.sp2d.kontrak.approve');
        Route::post('/verifikasi-ppk/sp2d/kontrak/{id}/revisi', [PpkSp2dKontrakVerifikasiController::class, 'revisi'])->name('verifikasi-ppk.sp2d.kontrak.revisi');

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
        Route::get('/verifikasi-kasubag/npi', [NpiController::class, 'kasubbagIndex'])->name('verifikasi-kasubag.npi.index');
        Route::post('/verifikasi-kasubag/npi/{npi_id}/approve', [NpiController::class, 'approveKasubbag'])->name('verifikasi-kasubag.npi.approve');
        Route::post('/verifikasi-kasubag/npi/{npi_id}/revisi', [NpiController::class, 'revisiKasubbag'])->name('verifikasi-kasubag.npi.revisi');

        // NPI Kontrak — Verifikasi Kasubbag (Parallel Workflow)
        Route::get('/verifikasi-kasubag/npi/kontrak', [KasubbagNpiKontrakVerifikasiController::class, 'index'])->name('verifikasi-kasubag.npi.kontrak.index');
        Route::get('/verifikasi-kasubag/npi/kontrak/{id}', [KasubbagNpiKontrakVerifikasiController::class, 'show'])->name('verifikasi-kasubag.npi.kontrak.show');
        Route::post('/verifikasi-kasubag/npi/kontrak/{id}/approve', [KasubbagNpiKontrakVerifikasiController::class, 'approve'])->name('verifikasi-kasubag.npi.kontrak.approve');
        Route::post('/verifikasi-kasubag/npi/kontrak/{id}/revisi', [KasubbagNpiKontrakVerifikasiController::class, 'revisi'])->name('verifikasi-kasubag.npi.kontrak.revisi');

        // SP2D Kontrak — Verifikasi Kasubbag (Parallel Workflow)
        Route::get('/verifikasi-kasubag/sp2d/kontrak', [KasubbagSp2dKontrakVerifikasiController::class, 'index'])->name('verifikasi-kasubag.sp2d.kontrak.index');
        Route::get('/verifikasi-kasubag/sp2d/kontrak/{id}', [KasubbagSp2dKontrakVerifikasiController::class, 'show'])->name('verifikasi-kasubag.sp2d.kontrak.show');
        Route::post('/verifikasi-kasubag/sp2d/kontrak/{id}/approve', [KasubbagSp2dKontrakVerifikasiController::class, 'approve'])->name('verifikasi-kasubag.sp2d.kontrak.approve');
        Route::post('/verifikasi-kasubag/sp2d/kontrak/{id}/revisi', [KasubbagSp2dKontrakVerifikasiController::class, 'revisi'])->name('verifikasi-kasubag.sp2d.kontrak.revisi');
    });

    // Verifikasi SPP — Koordinator Keuangan
    Route::middleware('role:Super Admin|Koordinator Keuangan')->group(function () {

        // SPM Perjaldin
        Route::get('/verifikasi-koordinator/spm-perjaldin', [SpmPerjaldinVerifikasiController::class, 'koordinatorIndex'])->name('verifikasi-koordinator.spm-perjaldin.index');
        Route::get('/verifikasi-koordinator/spm-perjaldin/{id}', [SpmPerjaldinVerifikasiController::class, 'koordinatorShow'])->name('verifikasi-koordinator.spm-perjaldin.show');
        Route::post('/verifikasi-koordinator/spm-perjaldin/{id}/approve', [SpmPerjaldinVerifikasiController::class, 'approve'])->name('verifikasi-koordinator.spm-perjaldin.approve');
        Route::post('/verifikasi-koordinator/spm-perjaldin/{id}/revisi', [SpmPerjaldinVerifikasiController::class, 'revisi'])->name('verifikasi-koordinator.spm-perjaldin.revisi');

        // Tagihan Perjaldin — Verifikasi Koordinator Keuangan
        // SPM Kontrak
        Route::get('/verifikasi-koordinator/spm/kontrak', [PpspmSpmKontrakVerifikasiController::class, 'index'])->name('verifikasi-koordinator.spm.kontrak.index');
        Route::get('/verifikasi-koordinator/spm/kontrak/{id}', [PpspmSpmKontrakVerifikasiController::class, 'show'])->name('verifikasi-koordinator.spm.kontrak.show');
        Route::post('/verifikasi-koordinator/spm/kontrak/{id}/approve', [PpspmSpmKontrakVerifikasiController::class, 'approve'])->name('verifikasi-koordinator.spm.kontrak.approve');
        Route::post('/verifikasi-koordinator/spm/kontrak/{id}/revisi', [PpspmSpmKontrakVerifikasiController::class, 'revisi'])->name('verifikasi-koordinator.spm.kontrak.revisi');

        // NPI Kontrak
        Route::get('/verifikasi-koordinator/npi/kontrak', [PpkNpiKontrakVerifikasiController::class, 'index'])->name('verifikasi-koordinator.npi.kontrak.index');
        Route::get('/verifikasi-koordinator/npi/kontrak/{id}', [PpkNpiKontrakVerifikasiController::class, 'show'])->name('verifikasi-koordinator.npi.kontrak.show');
        Route::post('/verifikasi-koordinator/npi/kontrak/{id}/approve', [PpkNpiKontrakVerifikasiController::class, 'approve'])->name('verifikasi-koordinator.npi.kontrak.approve');
        Route::post('/verifikasi-koordinator/npi/kontrak/{id}/revisi', [PpkNpiKontrakVerifikasiController::class, 'revisi'])->name('verifikasi-koordinator.npi.kontrak.revisi');

        // SP2D Kontrak
        Route::get('/verifikasi-koordinator/sp2d/kontrak', [PpkSp2dKontrakVerifikasiController::class, 'index'])->name('verifikasi-koordinator.sp2d.kontrak.index');
        Route::get('/verifikasi-koordinator/sp2d/kontrak/{id}', [PpkSp2dKontrakVerifikasiController::class, 'show'])->name('verifikasi-koordinator.sp2d.kontrak.show');
        Route::post('/verifikasi-koordinator/sp2d/kontrak/{id}/approve', [PpkSp2dKontrakVerifikasiController::class, 'approve'])->name('verifikasi-koordinator.sp2d.kontrak.approve');
        Route::post('/verifikasi-koordinator/sp2d/kontrak/{id}/revisi', [PpkSp2dKontrakVerifikasiController::class, 'revisi'])->name('verifikasi-koordinator.sp2d.kontrak.revisi');

        Route::get('/verifikasi-koordinator/honorarium', [PpkHonorariumVerifikasiController::class, 'index'])->name('verifikasi-koordinator.honorarium.index');
        Route::get('/verifikasi-koordinator/honorarium/{id}', [PpkHonorariumVerifikasiController::class, 'show'])->name('verifikasi-koordinator.honorarium.show');
        Route::post('/verifikasi-koordinator/honorarium/{id}/approve', [PpkHonorariumVerifikasiController::class, 'approve'])->name('verifikasi-koordinator.honorarium.approve');
        Route::post('/verifikasi-koordinator/honorarium/{id}/revisi', [PpkHonorariumVerifikasiController::class, 'revisi'])->name('verifikasi-koordinator.honorarium.revisi');

        Route::get('/verifikasi-koordinator/perjaldin', [PerjaldinVerifikasiController::class, 'koordinatorIndex'])->name('verifikasi-koordinator.perjaldin.index');
        Route::get('/verifikasi-koordinator/perjaldin/{id}', [PerjaldinVerifikasiController::class, 'koordinatorShow'])->name('verifikasi-koordinator.perjaldin.show');
        Route::post('/verifikasi-koordinator/perjaldin/{id}/approve', [PerjaldinVerifikasiController::class, 'koordinatorApprove'])->name('verifikasi-koordinator.perjaldin.approve');
        Route::post('/verifikasi-koordinator/perjaldin/{id}/revisi', [PerjaldinVerifikasiController::class, 'koordinatorRevisi'])->name('verifikasi-koordinator.perjaldin.revisi');

        // SPP Kontrak — Verifikasi Koordinator Keuangan
        Route::get('/verifikasi-koordinator/spp/kontrak', [SppVerifikasiController::class, 'koordinatorIndex'])->name('verifikasi-koordinator.spp.index');
        Route::get('/verifikasi-koordinator/spp/kontrak/{id}', [SppVerifikasiController::class, 'koordinatorShow'])->name('verifikasi-koordinator.spp.show');
        Route::post('/verifikasi-koordinator/spp/kontrak/{id}/approve', [SppVerifikasiController::class, 'approveKoordinator'])->name('verifikasi-koordinator.spp.approve');
        Route::post('/verifikasi-koordinator/spp/kontrak/{id}/revisi', [SppVerifikasiController::class, 'revisiKoordinator'])->name('verifikasi-koordinator.spp.revisi');

        // SPP Perjaldin — Verifikasi Koordinator Keuangan
        Route::get('/verifikasi-koordinator/spp-perjaldin', [SppPerjaldinVerifikasiController::class, 'index'])->name('verifikasi-koordinator.spp-perjaldin.index');
        Route::get('/verifikasi-koordinator/spp-perjaldin/{id}', [SppPerjaldinVerifikasiController::class, 'show'])->name('verifikasi-koordinator.spp-perjaldin.show');
        Route::post('/verifikasi-koordinator/spp-perjaldin/{id}/approve', [SppPerjaldinVerifikasiController::class, 'approve'])->name('verifikasi-koordinator.spp-perjaldin.approve');
        Route::post('/verifikasi-koordinator/spp-perjaldin/{id}/revisi', [SppPerjaldinVerifikasiController::class, 'revisi'])->name('verifikasi-koordinator.spp-perjaldin.revisi');
    });

    // ==== STANDING INSTRUCTION ====

    // ==== VERIFIKASI SPP KONTRAK, PERJALDIN, HONORARIUM — Terpadu 3 Role (PPK, Koordinator Keuangan, Kasubbag) ====
    Route::middleware('role:Super Admin|PPK|Kepala Subbagian Keuangan dan Tata Usaha|Koordinator Keuangan')->group(function () {
        // Honor
        Route::get('/verifikasi-spp/honor', [VerifikasiSppHonorController::class, 'index'])->name('verifikasi-spp.honor.index');
        Route::get('/verifikasi-spp/honor/{spp}/detail', [VerifikasiSppHonorController::class, 'detail'])->name('verifikasi-spp.honor.detail');
        Route::post('/verifikasi-spp/honor/{spp}/approve', [VerifikasiSppHonorController::class, 'approve'])->name('verifikasi-spp.honor.approve');
        Route::post('/verifikasi-spp/honor/{spp}/reject', [VerifikasiSppHonorController::class, 'reject'])->name('verifikasi-spp.honor.reject');

        // Kontrak
        Route::get('/verifikasi-spp/kontrak', [SppVerifikasiController::class, 'index'])->name('verifikasi-spp.kontrak.index');
        Route::get('/verifikasi-spp/kontrak/{id}', [SppVerifikasiController::class, 'show'])->name('verifikasi-spp.kontrak.show');
        Route::post('/verifikasi-spp/kontrak/{id}/approve', [SppVerifikasiController::class, 'approve'])->name('verifikasi-spp.kontrak.approve');
        Route::post('/verifikasi-spp/kontrak/{id}/revisi', [SppVerifikasiController::class, 'revisi'])->name('verifikasi-spp.kontrak.revisi');

        // KPA Approval Request dari PPK
        Route::post('/verifikasi-spp/kontrak/{id}/kpa-approval/send-wa', [KpaApprovalController::class, 'sendWa'])->name('kpa.approval.send-wa');

        // Perjaldin
        Route::get('/verifikasi-spp/perjaldin', [SppPerjaldinVerifikasiController::class, 'index'])->name('verifikasi-spp.perjaldin.index');
        Route::get('/verifikasi-spp/perjaldin/{id}', [SppPerjaldinVerifikasiController::class, 'show'])->name('verifikasi-spp.perjaldin.show');
        Route::post('/verifikasi-spp/perjaldin/{id}/approve', [SppPerjaldinVerifikasiController::class, 'approve'])->name('verifikasi-spp.perjaldin.approve');
        Route::post('/verifikasi-spp/perjaldin/{id}/revisi', [SppPerjaldinVerifikasiController::class, 'revisi'])->name('verifikasi-spp.perjaldin.revisi');
    });

    // ==== VERIFIKASI NPI — Bendahara Penerimaan (TTD) ====
    Route::middleware('role:Super Admin|Bendahara Penerimaan')->group(function () {
        Route::get('/verifikasi-bendahara-penerimaan/perjaldin', [PerjaldinVerifikasiController::class, 'bendaharaPenerimaanIndex'])->name('verifikasi-bendahara-penerimaan.perjaldin.index');
        Route::get('/verifikasi-bendahara-penerimaan/perjaldin/{id}', [PerjaldinVerifikasiController::class, 'bendaharaPenerimaanShow'])->name('verifikasi-bendahara-penerimaan.perjaldin.show');
        Route::post('/verifikasi-bendahara-penerimaan/perjaldin/{id}/approve', [PerjaldinVerifikasiController::class, 'bendaharaPenerimaanApprove'])->name('verifikasi-bendahara-penerimaan.perjaldin.approve');
        Route::post('/verifikasi-bendahara-penerimaan/perjaldin/{id}/revisi', [PerjaldinVerifikasiController::class, 'bendaharaPenerimaanRevisi'])->name('verifikasi-bendahara-penerimaan.perjaldin.revisi');

        Route::get('/verifikasi-bendahara-penerimaan/npi', [NpiController::class, 'penerimaaIndex'])->name('verifikasi-bendahara-penerimaan.npi.index');
        Route::post('/verifikasi-bendahara-penerimaan/npi/{npi_id}/approve', [NpiController::class, 'approvePenerimaan'])->name('verifikasi-bendahara-penerimaan.npi.approve');
        Route::post('/verifikasi-bendahara-penerimaan/npi/{npi_id}/revisi', [NpiController::class, 'revisiPenerimaan'])->name('verifikasi-bendahara-penerimaan.npi.revisi');

        // NPI Kontrak — Verifikasi Bendahara Penerimaan (Parallel Workflow)
        Route::get('/verifikasi-bendahara-penerimaan/npi/kontrak', [BenpenNpiKontrakVerifikasiController::class, 'index'])->name('verifikasi-bendahara-penerimaan.npi.kontrak.index');
        Route::get('/verifikasi-bendahara-penerimaan/npi/kontrak/{id}', [BenpenNpiKontrakVerifikasiController::class, 'show'])->name('verifikasi-bendahara-penerimaan.npi.kontrak.show');
        Route::post('/verifikasi-bendahara-penerimaan/npi/kontrak/{id}/approve', [BenpenNpiKontrakVerifikasiController::class, 'approve'])->name('verifikasi-bendahara-penerimaan.npi.kontrak.approve');
        Route::post('/verifikasi-bendahara-penerimaan/npi/kontrak/{id}/revisi', [BenpenNpiKontrakVerifikasiController::class, 'revisi'])->name('verifikasi-bendahara-penerimaan.npi.kontrak.revisi');
    });

    // ==== VERIFIKASI NPI PERJALDIN & HONORARIUM — Terpadu 3 Role ====
    Route::middleware('role:Super Admin|PPK|Bendahara Penerimaan|Kepala Subbagian Keuangan dan Tata Usaha|Koordinator Keuangan')->group(function () {
        // NPI Perjaldin
        Route::get('/verifikasi-npi/perjaldin', [VerifikasiNpiPerjaldinController::class, 'index'])->name('verifikasi-npi.perjaldin.index');
        Route::get('/verifikasi-npi/perjaldin/{id}/detail', [VerifikasiNpiPerjaldinController::class, 'show'])->name('verifikasi-npi.perjaldin.detail');
        Route::post('/verifikasi-npi/perjaldin/{id}/approve', [VerifikasiNpiPerjaldinController::class, 'approve'])->name('verifikasi-npi.perjaldin.approve');
        Route::post('/verifikasi-npi/perjaldin/{id}/revisi', [VerifikasiNpiPerjaldinController::class, 'reject'])->name('verifikasi-npi.perjaldin.reject');

        // NPI Honorarium
        Route::get('/verifikasi-npi/honor', [VerifikasiNpiHonorController::class, 'index'])->name('verifikasi-npi.honor.index');
        Route::get('/verifikasi-npi/honor/{id}/detail', [VerifikasiNpiHonorController::class, 'show'])->name('verifikasi-npi.honor.detail');
        Route::post('/verifikasi-npi/honor/{id}/approve', [VerifikasiNpiHonorController::class, 'approve'])->name('verifikasi-npi.honor.approve');
        Route::post('/verifikasi-npi/honor/{id}/revisi', [VerifikasiNpiHonorController::class, 'reject'])->name('verifikasi-npi.honor.reject');
    });

    // ==== VERIFIKASI SP2D PERJALDIN — Terpadu 4 Role ====
    Route::middleware('role:Super Admin|PPK|PPSPM|Kepala Subbagian Keuangan dan Tata Usaha|Koordinator Keuangan')->group(function () {
        Route::get('/verifikasi-sp2d/perjaldin', [VerifikasiSp2dPerjaldinController::class, 'index'])->name('verifikasi-sp2d.perjaldin.index');
        Route::get('/verifikasi-sp2d/perjaldin/{id}/detail', [VerifikasiSp2dPerjaldinController::class, 'show'])->name('verifikasi-sp2d.perjaldin.detail');
        Route::post('/verifikasi-sp2d/perjaldin/{id}/approve', [VerifikasiSp2dPerjaldinController::class, 'approve'])->name('verifikasi-sp2d.perjaldin.approve');
        Route::post('/verifikasi-sp2d/perjaldin/{id}/revisi', [VerifikasiSp2dPerjaldinController::class, 'reject'])->name('verifikasi-sp2d.perjaldin.reject');

        // SP2D Honorarium
        Route::get('/verifikasi-sp2d/honor', [VerifikasiSp2dHonorController::class, 'index'])->name('verifikasi-sp2d.honor.index');
        Route::get('/verifikasi-sp2d/honor/{id}/detail', [VerifikasiSp2dHonorController::class, 'show'])->name('verifikasi-sp2d.honor.detail');
        Route::post('/verifikasi-sp2d/honor/{id}/approve', [VerifikasiSp2dHonorController::class, 'approve'])->name('verifikasi-sp2d.honor.approve');
        Route::post('/verifikasi-sp2d/honor/{id}/revisi', [VerifikasiSp2dHonorController::class, 'reject'])->name('verifikasi-sp2d.honor.reject');
    });

    // ==== MODUL PEMBUATAN SPP (BLU) ====
    Route::middleware('role:Super Admin|Operator BLU')->group(function () {
        // SPP Perjaldin
        Route::get('/spps/perjaldin', [SppController::class, 'perjaldinIndex'])->name('spps.perjaldin.index');
        Route::get('/spps/perjaldin/{perjaldin}/detail', [SppController::class, 'detailPerjaldin'])->name('spps.perjaldin.detail');
        Route::post('/spps/perjaldin/{perjaldin}', [SppController::class, 'storePerjaldin'])->name('spps.perjaldin.store');
        Route::post('/spps/perjaldin/{perjaldin}/return-revision', [SppController::class, 'returnRevisionPerjaldin'])->name('spps.perjaldin.return-revision');

        // Dokumen ber-TTE Perjaldin (Nominatif & Daftar Pembayaran) — diakses dari Detail Multi-SPP
        Route::get('/spps/perjaldin/{id}/pdf/nominatif', [PerjaldinController::class, 'exportPdfNominatif'])->name('spps.perjaldin.pdf-nominatif');
        Route::get('/spps/perjaldin/{id}/pdf/lampiran', [PerjaldinController::class, 'exportPdfLampiran'])->name('spps.perjaldin.pdf-lampiran');

        // Komponen Perjaldin — COA & SPP per komponen
        Route::put('/perjaldins/komponen/{id}/coa', [PerjaldinKomponenController::class, 'updateCoa'])->name('perjaldins.komponen.update-coa');
        Route::post('/perjaldins/komponen/{id}/spp', [SppController::class, 'storeFromPerjaldinKomponen'])->name('spps.store-from-perjaldin-komponen');

        // SPP Workflow (submit/approve/revisi/reject)
        Route::post('/spps/{id}/workflow/submit', [SppWorkflowController::class, 'submit'])->name('spps.workflow.submit');
        Route::post('/spps/workflow/approval/{approvalId}/approve', [SppWorkflowController::class, 'approve'])->name('spps.workflow.approve');
        Route::post('/spps/workflow/approval/{approvalId}/revision', [SppWorkflowController::class, 'revision'])->name('spps.workflow.revision');
        Route::post('/spps/workflow/approval/{approvalId}/reject', [SppWorkflowController::class, 'reject'])->name('spps.workflow.reject');

        // SPP Honor
        Route::get('/spps/honor', [SppController::class, 'honorIndex'])->name('spps.honor.index');
        Route::get('/spps/honor/{honorarium}/detail', [SppController::class, 'detailHonor'])->name('spps.honor.detail');
        Route::post('/spps/honor/{honorarium}', [SppController::class, 'storeHonor'])->name('spps.honor.store');
        Route::post('/spps/honor/{honorarium}/submit', [SppController::class, 'submitHonorToPpk'])->name('spps.honor.submit');

        // SPP Kontrak
        Route::get('/spps/kontrak', [SppController::class, 'kontrakIndex'])->name('spps.kontrak.index');
        Route::get('/spps/kontrak/{contract}/detail', [SppController::class, 'detailKontrak'])->name('spps.kontrak.detail');
        Route::post('/spps/kontrak/{contract}', [SppController::class, 'storeKontrak'])->name('spps.kontrak.store');
        Route::post('/spps/kontrak/{contract}/submit', [SppController::class, 'submitKontrakToPpk'])->name('spps.kontrak.submit');

        // Upload SPP Bertandatangan (shared across all SPP types)
        Route::post('/spps/{spp}/upload-signed', [SppController::class, 'uploadSignedSpp'])->name('spps.upload-signed');
    });

    // Cetak PDF SPP/SPM/NPI bisa diakses oleh berbagai role terkait
    Route::middleware('auth')->group(function () {
        Route::get('/spps/{spp}/pdf', [SppController::class, 'cetakPdf'])->name('spps.cetak-pdf');
        Route::get('/spms/{spm_id}/pdf', [SpmController::class, 'cetakPdfSpm'])->name('spms.cetak-pdf');
        Route::get('/npis/{npi_id}/pdf', [NpiController::class, 'cetakPdf'])->name('npis.cetak-pdf');
        Route::get('/sp2ds/{sp2d}/pdf', [DocumentController::class, 'printSp2d'])->name('sp2ds.cetak-pdf');
        Route::get('/sp2ds/perjaldin/{sp2d}/cetak', [Sp2dPerjaldinController::class, 'cetak'])->name('sp2ds.perjaldin.cetak');
    });

    // ==== MODUL PEMBUATAN SPM (BLU) ====
    Route::middleware('role:Super Admin|Operator BLU')->group(function () {
        Route::get('/spms', [SpmController::class, 'index'])->name('spms.index');
        Route::post('/spms/spp/{spp_id}/store', [SpmController::class, 'store'])->name('spms.store');

        // SPM Perjaldin (New Pattern)
        Route::get('/spms/perjaldin', [SpmPerjaldinController::class, 'index'])->name('spms.perjaldin.index');
        Route::get('/spms/perjaldin/{spp}/detail', [SpmPerjaldinController::class, 'show'])->name('spms.perjaldin.detail');
        Route::post('/spms/perjaldin/{spp}/store', [SpmPerjaldinController::class, 'store'])->name('spms.perjaldin.store');
        Route::post('/spms/perjaldin/{spp}/submit', [SpmPerjaldinController::class, 'submit'])->name('spms.perjaldin.submit');

        // SPM Kontrak
        Route::get('/spms/kontrak', [SpmKontrakController::class, 'index'])->name('spms.kontrak.index');
        Route::get('/spms/kontrak/{spp}/detail', [SpmKontrakController::class, 'show'])->name('spms.kontrak.detail');
        Route::post('/spms/kontrak/{spp}/store', [SpmKontrakController::class, 'store'])->name('spms.kontrak.store');
        Route::post('/spms/kontrak/{spp}/submit', [SpmKontrakController::class, 'submit'])->name('spms.kontrak.submit');

        // SPM Honorarium
        Route::get('/spms/honor', [SpmHonorController::class, 'index'])->name('spms.honor.index');
        Route::get('/spms/honor/{spp}/detail', [SpmHonorController::class, 'show'])->name('spms.honor.detail');
        Route::post('/spms/honor/{spp}/store', [SpmHonorController::class, 'store'])->name('spms.honor.store');
        Route::post('/spms/honor/{spp}/submit', [SpmHonorController::class, 'submit'])->name('spms.honor.submit');
    });

    // ==== MODUL VERIFIKASI SPM (PPSPM) ====
    Route::middleware('role:Super Admin|PPSPM')->group(function () {
        Route::get('/verifikasi-ppspm/perjaldin', [PerjaldinVerifikasiController::class, 'ppspmIndex'])->name('verifikasi-ppspm.perjaldin.index');
        Route::get('/verifikasi-ppspm/perjaldin/{id}', [PerjaldinVerifikasiController::class, 'ppspmShow'])->name('verifikasi-ppspm.perjaldin.show');
        Route::post('/verifikasi-ppspm/perjaldin/{id}/approve', [PerjaldinVerifikasiController::class, 'ppspmApprove'])->name('verifikasi-ppspm.perjaldin.approve');
        Route::post('/verifikasi-ppspm/perjaldin/{id}/revisi', [PerjaldinVerifikasiController::class, 'ppspmRevisi'])->name('verifikasi-ppspm.perjaldin.revisi');

        Route::get('/verifikasi-ppspm/spm', [SpmVerifikasiController::class, 'index'])->name('verifikasi-ppspm.spm.index');
        Route::post('/verifikasi-ppspm/spm/{spm_id}/approve', [SpmVerifikasiController::class, 'approve'])->name('verifikasi-ppspm.spm.approve');
        Route::post('/verifikasi-ppspm/spm/{spm_id}/revisi', [SpmVerifikasiController::class, 'revisi'])->name('verifikasi-ppspm.spm.revisi');

        // Verifikasi SPM Perjaldin
        Route::get('/verifikasi-ppspm/spm/perjaldin', [SpmPerjaldinVerifikasiController::class, 'ppspmIndex'])->name('verifikasi-ppspm.spm-perjaldin.index');
        Route::get('/verifikasi-ppspm/spm/perjaldin/{id}', [SpmPerjaldinVerifikasiController::class, 'ppspmShow'])->name('verifikasi-ppspm.spm-perjaldin.show');
        Route::post('/verifikasi-ppspm/spm/perjaldin/{id}/approve', [SpmPerjaldinVerifikasiController::class, 'approve'])->name('verifikasi-ppspm.spm-perjaldin.approve');
        Route::post('/verifikasi-ppspm/spm/perjaldin/{id}/revisi', [SpmPerjaldinVerifikasiController::class, 'revisi'])->name('verifikasi-ppspm.spm-perjaldin.revisi');

        // Verifikasi SPM Kontrak
        Route::get('/verifikasi-ppspm/spm/kontrak', [PpspmSpmKontrakVerifikasiController::class, 'index'])->name('verifikasi-ppspm.spm.kontrak.index');
        Route::get('/verifikasi-ppspm/spm/kontrak/{id}', [PpspmSpmKontrakVerifikasiController::class, 'show'])->name('verifikasi-ppspm.spm.kontrak.show');
        Route::post('/verifikasi-ppspm/spm/kontrak/{id}/approve', [PpspmSpmKontrakVerifikasiController::class, 'approve'])->name('verifikasi-ppspm.spm.kontrak.approve');
        Route::post('/verifikasi-ppspm/spm/kontrak/{id}/revisi', [PpspmSpmKontrakVerifikasiController::class, 'revisi'])->name('verifikasi-ppspm.spm.kontrak.revisi');

        // Verifikasi SP2D Kontrak
        Route::get('/verifikasi-ppspm/sp2d/kontrak', [PpkSp2dKontrakVerifikasiController::class, 'index'])->name('verifikasi-ppspm.sp2d.kontrak.index');
        Route::get('/verifikasi-ppspm/sp2d/kontrak/{id}', [PpkSp2dKontrakVerifikasiController::class, 'show'])->name('verifikasi-ppspm.sp2d.kontrak.show');
        Route::post('/verifikasi-ppspm/sp2d/kontrak/{id}/approve', [PpkSp2dKontrakVerifikasiController::class, 'approve'])->name('verifikasi-ppspm.sp2d.kontrak.approve');
        Route::post('/verifikasi-ppspm/sp2d/kontrak/{id}/revisi', [PpkSp2dKontrakVerifikasiController::class, 'revisi'])->name('verifikasi-ppspm.sp2d.kontrak.revisi');
    });

    Route::middleware('role:Super Admin|Kepala Subbagian Keuangan dan Tata Usaha')->group(function () {
        // Verifikasi SPM reguler (lama) / Perjaldin
        Route::get('/verifikasi-kasubag/spm', [SpmVerifikasiController::class, 'kasubbagIndex'])->name('verifikasi-kasubag.spm.index');
        Route::post('/verifikasi-kasubag/spm/{spm_id}/approve', [SpmVerifikasiController::class, 'approveKasubbag'])->name('verifikasi-kasubag.spm.approve');
        Route::post('/verifikasi-kasubag/spm/{spm_id}/revisi', [SpmVerifikasiController::class, 'revisiKasubbag'])->name('verifikasi-kasubag.spm.revisi');

        // Verifikasi SPM Perjaldin
        Route::get('/verifikasi-kasubag/spm/perjaldin', [SpmPerjaldinVerifikasiController::class, 'kasubbagIndex'])->name('verifikasi-kasubag.spm-perjaldin.index');
        Route::get('/verifikasi-kasubag/spm/perjaldin/{id}', [SpmPerjaldinVerifikasiController::class, 'kasubbagShow'])->name('verifikasi-kasubag.spm-perjaldin.show');
        Route::post('/verifikasi-kasubag/spm/perjaldin/{id}/approve', [SpmPerjaldinVerifikasiController::class, 'approve'])->name('verifikasi-kasubag.spm-perjaldin.approve');
        Route::post('/verifikasi-kasubag/spm/perjaldin/{id}/revisi', [SpmPerjaldinVerifikasiController::class, 'revisi'])->name('verifikasi-kasubag.spm-perjaldin.revisi');

        // Verifikasi SPM Kontrak
        Route::get('/verifikasi-kasubag/spm/kontrak', [KasubbagSpmKontrakVerifikasiController::class, 'index'])->name('verifikasi-kasubag.spm.kontrak.index');
        Route::get('/verifikasi-kasubag/spm/kontrak/{id}', [KasubbagSpmKontrakVerifikasiController::class, 'show'])->name('verifikasi-kasubag.spm.kontrak.show');
        Route::post('/verifikasi-kasubag/spm/kontrak/{id}/approve', [KasubbagSpmKontrakVerifikasiController::class, 'approve'])->name('verifikasi-kasubag.spm.kontrak.approve');
        Route::post('/verifikasi-kasubag/spm/kontrak/{id}/revisi', [KasubbagSpmKontrakVerifikasiController::class, 'revisi'])->name('verifikasi-kasubag.spm.kontrak.revisi');
    });

    // ==== MODUL VERIFIKASI SPM HONORARIUM (PPSPM & KASUBBAG) ====
    Route::middleware('role:Super Admin|PPSPM|Kepala Subbagian Keuangan dan Tata Usaha|Koordinator Keuangan')->group(function () {
        Route::get('/verifikasi-spm/honor', [VerifikasiSpmHonorController::class, 'index'])->name('verifikasi-spm.honor.index');
        Route::get('/verifikasi-spm/honor/{spm}/detail', [VerifikasiSpmHonorController::class, 'show'])->name('verifikasi-spm.honor.detail');
        Route::post('/verifikasi-spm/honor/{spm}/approve', [VerifikasiSpmHonorController::class, 'approve'])->name('verifikasi-spm.honor.approve');
        Route::post('/verifikasi-spm/honor/{spm}/reject', [VerifikasiSpmHonorController::class, 'reject'])->name('verifikasi-spm.honor.reject');
    });

    // ==== MODUL NPI (Nota Pemindahbukuan Internal) — Bendahara Pengeluaran ====
    Route::middleware('role:Super Admin|Bendahara Pengeluaran')->group(function () {
        // NPI Perjaldin
        Route::get('/npis/perjaldin', [NpiPerjaldinController::class, 'index'])->name('npis.perjaldin.index');
        Route::get('/npis/perjaldin/{id}/detail', [NpiPerjaldinController::class, 'show'])->name('npis.perjaldin.detail');
        Route::post('/npis/perjaldin/{id}/store', [NpiPerjaldinController::class, 'store'])->name('npis.perjaldin.store');
        Route::post('/npis/perjaldin/{id}/submit', [NpiPerjaldinController::class, 'submit'])->name('npis.perjaldin.submit');

        // NPI Honorarium
        Route::get('/npis/honor', [NpiHonorController::class, 'index'])->name('npis.honor.index');
        Route::get('/npis/honor/{spm}/detail', [NpiHonorController::class, 'show'])->name('npis.honor.detail');
        Route::post('/npis/honor/{spm}/store', [NpiHonorController::class, 'store'])->name('npis.honor.store');
        Route::post('/npis/honor/{spm}/submit', [NpiHonorController::class, 'submit'])->name('npis.honor.submit');

        // NPI Legacy (if needed)
        Route::get('/npis', [NpiController::class, 'index'])->name('npis.index');
        Route::post('/npis/spm/{spm_id}/store', [NpiController::class, 'store'])->name('npis.store');

        // NPI Kontrak
        Route::get('/npis/kontrak', [NpiKontrakController::class, 'index'])->name('npis.kontrak.index');
        Route::get('/npis/kontrak/{spm}/detail', [NpiKontrakController::class, 'show'])->name('npis.kontrak.detail');
        Route::post('/npis/kontrak/{spm}/store', [NpiKontrakController::class, 'store'])->name('npis.kontrak.store');
        Route::post('/npis/kontrak/{spm}/submit', [NpiKontrakController::class, 'submit'])->name('npis.kontrak.submit');
    });

    // ==== MODUL SP2D & BKU — Bendahara Pengeluaran ====
    Route::middleware('role:Super Admin|Bendahara Pengeluaran')->group(function () {
        // SP2D Perjaldin
        Route::get('/sp2ds/perjaldin', [Sp2dPerjaldinController::class, 'index'])->name('sp2ds.perjaldin.index');
        Route::get('/sp2ds/perjaldin/{npi_id}/detail', [Sp2dPerjaldinController::class, 'detail'])->name('sp2ds.perjaldin.detail');
        Route::post('/sp2ds/perjaldin/{npi_id}/store', [Sp2dPerjaldinController::class, 'store'])->name('sp2ds.perjaldin.store');
        Route::post('/sp2ds/perjaldin/{npi_id}/submit', [Sp2dPerjaldinController::class, 'submit'])->name('sp2ds.perjaldin.submit');

        // SP2D Honorarium
        Route::get('/sp2ds/honor', [Sp2dHonorController::class, 'index'])->name('sp2ds.honor.index');
        Route::get('/sp2ds/honor/{npi_id}/detail', [Sp2dHonorController::class, 'detail'])->name('sp2ds.honor.detail');
        Route::post('/sp2ds/honor/{npi_id}/store', [Sp2dHonorController::class, 'store'])->name('sp2ds.honor.store');
        Route::post('/sp2ds/honor/{npi_id}/submit', [Sp2dHonorController::class, 'submit'])->name('sp2ds.honor.submit');

        Route::get('/sp2ds/kontrak', [Sp2dKontrakController::class, 'index'])->name('sp2ds.kontrak.index');
        Route::post('/sp2ds/kontrak/npi/{npi_id}/draft', [Sp2dKontrakController::class, 'storeDraft'])->name('sp2ds.kontrak.store');
        Route::post('/sp2ds/kontrak/npi/{npi_id}/submit', [Sp2dKontrakController::class, 'submitVerification'])->name('sp2ds.kontrak.submit');
        Route::post('/sp2ds/kontrak/{sp2d_id}/upload-signed-sp2d', [Sp2dKontrakController::class, 'uploadSignedSp2d'])->name('sp2ds.kontrak.upload-signed-sp2d');
        Route::get('/sp2ds', [Sp2dController::class, 'index'])->name('sp2ds.index');
        Route::get('/sp2ds/perjaldin/{perjaldin_id}/detail', [Sp2dController::class, 'detail'])->name('sp2ds.perjaldin.detail');
        Route::post('/sp2ds/npi/{npi_id}/store', [Sp2dController::class, 'store'])->name('sp2ds.store');
        Route::post('/sp2ds/{sp2d_id}/approve', [Sp2dController::class, 'approve'])->name('sp2ds.approve');
        Route::post('/sp2ds/{sp2d_id}/execute', [Sp2dController::class, 'catatBku'])->name('sp2ds.catat-bku');
        Route::get('/sp2ds/kontrak/{npi_id}/detail', [Sp2dKontrakController::class, 'show'])->name('sp2ds.kontrak.detail');
    });

    // ==== MODUL PENYETORAN PAJAK — Bendahara Pengeluaran ====
    Route::middleware('role:Super Admin|Bendahara Pengeluaran|Bendahara Penerimaan')->group(function () {
        Route::get('/pembukuan/bku/pdf', [BukuKasUmumController::class, 'pdf'])->name('pembukuan.bku.pdf');
        Route::get('/pembukuan/bku', [BukuKasUmumController::class, 'index'])->name('pembukuan.bku.index');
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

        Route::get('/pembukuan/pengesahan-belanja/pdf', [BukuPengesahanBelanjaController::class, 'pdf'])->name('pembukuan.pengesahan.pdf');
        Route::get('/pembukuan/pengesahan-belanja', [BukuPengesahanBelanjaController::class, 'index'])->name('pembukuan.pengesahan.index');
        Route::get('/pembukuan/pengesahan-belanja/{laporan}', [BukuPengesahanBelanjaController::class, 'show'])
            ->whereNumber('laporan')
            ->name('pembukuan.pengesahan.show');

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
