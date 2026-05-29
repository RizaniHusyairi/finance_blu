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
        Schema::table('document_signatures', function (Blueprint $table) {
            // Menggabungkan beberapa dokumen menjadi satu tautan publik per penerima
            // (vendor / tim pemeriksa). magic_token tetap unik per-dokumen.
            $table->string('group_token', 64)->nullable()->index()->after('magic_token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('document_signatures', function (Blueprint $table) {
            $table->dropIndex(['group_token']);
            $table->dropColumn('group_token');
        });
    }
};
