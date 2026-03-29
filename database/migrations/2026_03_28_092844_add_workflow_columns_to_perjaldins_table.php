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
        Schema::table('perjaldins', function (Blueprint $table) {
            $table->string('status')->default('Draft')->after('no_bast');
            $table->boolean('is_ppk_approved')->default(false)->after('status');
            $table->boolean('is_kasubag_approved')->default(false)->after('is_ppk_approved');
            $table->text('catatan_revisi')->nullable()->after('is_kasubag_approved');
            $table->string('revisi_oleh')->nullable()->after('catatan_revisi');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('perjaldins', function (Blueprint $table) {
            $table->dropColumn(['status', 'is_ppk_approved', 'is_kasubag_approved', 'catatan_revisi', 'revisi_oleh']);
        });
    }
};
