<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('tagihan', 'mekanisme_pembayaran')) {
            Schema::table('tagihan', function (Blueprint $table) {
                $table->string('mekanisme_pembayaran', 20)
                    ->default('LS_PIHAK_3')
                    ->after('total_netto');
                $table->index('mekanisme_pembayaran');
            });
        }

        // Backfill data lama: default LS_PIHAK_3 (sudah berlaku via default kolom,
        // tapi eksplisit update untuk memastikan konsistensi)
        DB::table('tagihan')
            ->whereNull('mekanisme_pembayaran')
            ->update(['mekanisme_pembayaran' => 'LS_PIHAK_3']);
    }

    public function down(): void
    {
        if (Schema::hasColumn('tagihan', 'mekanisme_pembayaran')) {
            Schema::table('tagihan', function (Blueprint $table) {
                $table->dropIndex(['mekanisme_pembayaran']);
                $table->dropColumn('mekanisme_pembayaran');
            });
        }
    }
};
