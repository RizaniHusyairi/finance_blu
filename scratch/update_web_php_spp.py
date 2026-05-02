file_path = "routes/web.php"
with open(file_path, "r") as f:
    content = f.read()

# Just a simple string replacement
old_honor_group = """    // ==== VERIFIKASI SPP HONORARIUM — Terpadu 3 Role (PPK, Koordinator Keuangan, Kasubbag) ====
    Route::middleware('role:Super Admin|PPK|Kepala Subbagian Keuangan dan Tata Usaha|Koordinator Keuangan')->group(function () {
        Route::get('/verifikasi-spp/honor', [\App\Http\Controllers\VerifikasiSppHonorController::class, 'index'])->name('verifikasi-spp.honor.index');
        Route::get('/verifikasi-spp/honor/{spp}/detail', [\App\Http\Controllers\VerifikasiSppHonorController::class, 'detail'])->name('verifikasi-spp.honor.detail');
        Route::post('/verifikasi-spp/honor/{spp}/approve', [\App\Http\Controllers\VerifikasiSppHonorController::class, 'approve'])->name('verifikasi-spp.honor.approve');
        Route::post('/verifikasi-spp/honor/{spp}/reject', [\App\Http\Controllers\VerifikasiSppHonorController::class, 'reject'])->name('verifikasi-spp.honor.reject');
    });"""

unified_group = """    // ==== VERIFIKASI SPP KONTRAK, PERJALDIN, HONORARIUM — Terpadu 3 Role (PPK, Koordinator Keuangan, Kasubbag) ====
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
    });"""

if old_honor_group in content:
    content = content.replace(old_honor_group, unified_group)
    with open(file_path, "w") as fw:
        fw.write(content)
    print("Updated web.php successfully")
else:
    print("Could not find old_honor_group in web.php")
