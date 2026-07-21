<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Kolom rincian POK: volume/satuan/harga satuan + flag blokir pada item DIPA,
 * dan sumber dana (RM/BLU) pada master COA — agar baris Detil POK dapat
 * disimpan utuh, bukan hanya hasil kali jumlah biayanya.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dipa_revision_items', function (Blueprint $table) {
            $table->decimal('volume', 14, 2)->nullable()->after('nilai_pagu');
            $table->string('satuan', 30)->nullable()->after('volume');
            $table->decimal('harga_satuan', 18, 2)->nullable()->after('satuan');
            $table->boolean('blokir')->default(false)->after('status_aktif');
        });

        Schema::table('master_coas', function (Blueprint $table) {
            $table->string('sumber_dana', 10)->nullable()->after('jenis_akun');
        });

        // Backfill: akun 525xxx = belanja BLU, selain itu (511/512/521-523 dst.) = RM.
        DB::table('master_coas')
            ->whereNotNull('kd_akun')
            ->where('kd_akun', 'like', '525%')
            ->update(['sumber_dana' => 'BLU']);

        DB::table('master_coas')
            ->whereNotNull('kd_akun')
            ->where('kd_akun', 'not like', '525%')
            ->update(['sumber_dana' => 'RM']);
    }

    public function down(): void
    {
        Schema::table('dipa_revision_items', function (Blueprint $table) {
            $table->dropColumn(['volume', 'satuan', 'harga_satuan', 'blokir']);
        });

        Schema::table('master_coas', function (Blueprint $table) {
            $table->dropColumn('sumber_dana');
        });
    }
};
