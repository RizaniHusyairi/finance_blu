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
        Schema::table('detail_kontrak', function (Blueprint $table) {
            $table->string('wa_pemeriksa', 30)->nullable()->after('jabatan_pemeriksa');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('detail_kontrak', function (Blueprint $table) {
            $table->dropColumn('wa_pemeriksa');
        });
    }
};
