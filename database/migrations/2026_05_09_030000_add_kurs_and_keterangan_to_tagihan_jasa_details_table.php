<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tagihan_jasa_details', function (Blueprint $table) {
            if (! Schema::hasColumn('tagihan_jasa_details', 'kurs')) {
                $table->decimal('kurs', 20, 4)->default(1)->after('harga_satuan');
            }

            if (! Schema::hasColumn('tagihan_jasa_details', 'keterangan')) {
                $table->text('keterangan')->nullable()->after('subtotal');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tagihan_jasa_details', function (Blueprint $table) {
            if (Schema::hasColumn('tagihan_jasa_details', 'kurs')) {
                $table->dropColumn('kurs');
            }

            if (Schema::hasColumn('tagihan_jasa_details', 'keterangan')) {
                $table->dropColumn('keterangan');
            }
        });
    }
};
