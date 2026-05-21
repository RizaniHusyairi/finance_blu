<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tagihan_jasa_details', function (Blueprint $table) {
            if (! Schema::hasColumn('tagihan_jasa_details', 'calculation_payload')) {
                $table->json('calculation_payload')->nullable()->after('keterangan');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tagihan_jasa_details', function (Blueprint $table) {
            if (Schema::hasColumn('tagihan_jasa_details', 'calculation_payload')) {
                $table->dropColumn('calculation_payload');
            }
        });
    }
};
