<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pemakaian_garbarata', function (Blueprint $table) {
            $table->string('flight_arr', 50)->nullable()->after('nomor_penerbangan');
            $table->string('flight_dep', 50)->nullable()->after('flight_arr');
            $table->string('route', 150)->nullable()->after('registrasi_pesawat');
            $table->string('type_pesawat', 50)->nullable()->after('route');
            $table->decimal('bobot_ton', 12, 2)->nullable()->after('type_pesawat');
            $table->decimal('tarif_garbarata', 15, 2)->nullable()->after('jumlah_rentang');

            $table->index(['mitra_jasa_id', 'tanggal', 'status'], 'pemakaian_garbarata_mitra_tanggal_status_idx');
        });
    }

    public function down(): void
    {
        Schema::table('pemakaian_garbarata', function (Blueprint $table) {
            $table->dropIndex('pemakaian_garbarata_mitra_tanggal_status_idx');
            $table->dropColumn([
                'flight_arr',
                'flight_dep',
                'route',
                'type_pesawat',
                'bobot_ton',
                'tarif_garbarata',
            ]);
        });
    }
};
