<?php

use App\Models\DokumenNpi;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Proses Upload NPI Bertanda Tangan sudah dihapus (NPI cukup ber-TTE QR).
     * NPI lama yang sempat berstatus MENUNGGU_UPLOAD harus dipromosikan ke
     * DISETUJUI_FINAL agar bisa dilanjutkan ke pembuatan SP2D.
     */
    public function up(): void
    {
        DB::table('dokumen_npi')
            ->where('status', DokumenNpi::STATUS_MENUNGGU_UPLOAD)
            ->update(['status' => DokumenNpi::STATUS_DISETUJUI_FINAL]);
    }

    public function down(): void
    {
        // Tidak dapat dikembalikan secara akurat; status final dibiarkan apa adanya.
    }
};
