<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('kontrak_addendum')) {
            return;
        }

        Schema::table('kontrak_addendum', function (Blueprint $table) {
            if (!Schema::hasColumn('kontrak_addendum', 'status_proses')) {
                $table->string('status_proses', 50)
                    ->nullable()
                    ->after('status_addendum');
            }

            if (!Schema::hasColumn('kontrak_addendum', 'catatan_perubahan_spesifikasi')) {
                $table->text('catatan_perubahan_spesifikasi')
                    ->nullable()
                    ->after('keterangan_alasan');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('kontrak_addendum')) {
            return;
        }

        Schema::table('kontrak_addendum', function (Blueprint $table) {
            $dropColumns = [];

            if (Schema::hasColumn('kontrak_addendum', 'status_proses')) {
                $dropColumns[] = 'status_proses';
            }

            if (Schema::hasColumn('kontrak_addendum', 'catatan_perubahan_spesifikasi')) {
                $dropColumns[] = 'catatan_perubahan_spesifikasi';
            }

            if (!empty($dropColumns)) {
                $table->dropColumn($dropColumns);
            }
        });
    }
};
