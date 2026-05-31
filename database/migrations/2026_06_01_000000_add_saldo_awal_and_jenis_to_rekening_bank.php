<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rekening_bank', function (Blueprint $table) {
            // Penanda peran rekening (PENERIMAAN/PENGELUARAN/LAINNYA) untuk resolusi BKU.
            $table->string('jenis_rekening', 20)->default('LAINNYA')->after('kode_bank');
            // Saldo awal pembukuan rekening + tanggal berlakunya (mis. saldo akhir
            // tahun lalu atau saldo riil sebelum sistem mulai mencatat).
            $table->decimal('saldo_awal', 18, 2)->default(0)->after('jenis_rekening');
            $table->date('saldo_awal_per_tanggal')->nullable()->after('saldo_awal');
        });
    }

    public function down(): void
    {
        Schema::table('rekening_bank', function (Blueprint $table) {
            $table->dropColumn(['jenis_rekening', 'saldo_awal', 'saldo_awal_per_tanggal']);
        });
    }
};
