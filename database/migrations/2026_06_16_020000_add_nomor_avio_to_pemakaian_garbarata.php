<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pemakaian_garbarata', function (Blueprint $table) {
            $table->unsignedTinyInteger('nomor_avio')->nullable()->after('type_pesawat');
        });
    }

    public function down(): void
    {
        Schema::table('pemakaian_garbarata', function (Blueprint $table) {
            $table->dropColumn('nomor_avio');
        });
    }
};
