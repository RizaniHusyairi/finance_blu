<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tagihan', function (Blueprint $table) {
            if (! Schema::hasColumn('tagihan', 'koordinator_keuangan_user_id')) {
                $table->foreignId('koordinator_keuangan_user_id')
                    ->nullable()
                    ->after('kasubbag_nip_snapshot')
                    ->constrained('users')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('tagihan', 'koordinator_keuangan_nama_snapshot')) {
                $table->string('koordinator_keuangan_nama_snapshot', 150)
                    ->nullable()
                    ->after('koordinator_keuangan_user_id');
            }

            if (! Schema::hasColumn('tagihan', 'koordinator_keuangan_nip_snapshot')) {
                $table->string('koordinator_keuangan_nip_snapshot', 100)
                    ->nullable()
                    ->after('koordinator_keuangan_nama_snapshot');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tagihan', function (Blueprint $table) {
            if (Schema::hasColumn('tagihan', 'koordinator_keuangan_user_id')) {
                try { $table->dropForeign(['koordinator_keuangan_user_id']); } catch (\Throwable $e) {}
            }

            $columns = array_filter([
                Schema::hasColumn('tagihan', 'koordinator_keuangan_user_id') ? 'koordinator_keuangan_user_id' : null,
                Schema::hasColumn('tagihan', 'koordinator_keuangan_nama_snapshot') ? 'koordinator_keuangan_nama_snapshot' : null,
                Schema::hasColumn('tagihan', 'koordinator_keuangan_nip_snapshot') ? 'koordinator_keuangan_nip_snapshot' : null,
            ]);

            if (! empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};
