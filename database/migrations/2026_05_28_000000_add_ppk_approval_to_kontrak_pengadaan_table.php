<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kontrak_pengadaan', function (Blueprint $table) {
            if (! Schema::hasColumn('kontrak_pengadaan', 'diajukan_at')) {
                $table->timestamp('diajukan_at')->nullable()->after('status_kontrak');
            }
            if (! Schema::hasColumn('kontrak_pengadaan', 'ppk_approved_at')) {
                $table->timestamp('ppk_approved_at')->nullable()->after('diajukan_at');
            }
            if (! Schema::hasColumn('kontrak_pengadaan', 'ppk_approved_by')) {
                $table->unsignedBigInteger('ppk_approved_by')->nullable()->after('ppk_approved_at');
            }
            if (! Schema::hasColumn('kontrak_pengadaan', 'ppk_catatan')) {
                $table->text('ppk_catatan')->nullable()->after('ppk_approved_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('kontrak_pengadaan', function (Blueprint $table) {
            foreach (['diajukan_at', 'ppk_approved_at', 'ppk_approved_by', 'ppk_catatan'] as $col) {
                if (Schema::hasColumn('kontrak_pengadaan', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
