<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1) Tambah kolom polymorphic di tabel users (nullable dulu untuk backfill)
        if (Schema::hasTable('users') && ! Schema::hasColumn('users', 'profilable_type')) {
            Schema::table('users', function (Blueprint $table) {
                $table->nullableMorphs('profilable');
                // Pastikan satu pegawai/mitra hanya bisa dimiliki oleh satu user
                $table->unique(['profilable_type', 'profilable_id'], 'users_profilable_unique');
            });
        }

        // 2) Backfill dari master_pegawai.user_id → users.profilable_*
        if (Schema::hasTable('master_pegawai') && Schema::hasColumn('master_pegawai', 'user_id')) {
            DB::table('master_pegawai')
                ->whereNotNull('user_id')
                ->orderBy('id')
                ->select('id', 'user_id')
                ->chunk(200, function ($rows) {
                    foreach ($rows as $row) {
                        // Hindari menumpuk profilable di user yang sudah punya
                        $existing = DB::table('users')->where('id', $row->user_id)->first();
                        if (! $existing || $existing->profilable_id !== null) {
                            continue;
                        }
                        DB::table('users')->where('id', $row->user_id)->update([
                            'profilable_type' => \App\Models\MasterPegawai::class,
                            'profilable_id' => $row->id,
                        ]);
                    }
                });
        }

        // 3) Backfill dari master_pihak.user_id → users.profilable_* (hanya yang belum terisi)
        if (Schema::hasTable('master_pihak') && Schema::hasColumn('master_pihak', 'user_id')) {
            DB::table('master_pihak')
                ->whereNotNull('user_id')
                ->orderBy('id')
                ->select('id', 'user_id')
                ->chunk(200, function ($rows) {
                    foreach ($rows as $row) {
                        $existing = DB::table('users')->where('id', $row->user_id)->first();
                        if (! $existing || $existing->profilable_id !== null) {
                            continue;
                        }
                        DB::table('users')->where('id', $row->user_id)->update([
                            'profilable_type' => \App\Models\MasterPihak::class,
                            'profilable_id' => $row->id,
                        ]);
                    }
                });
        }

        // 4) Hapus kolom user_id dari master_pegawai (dan FK-nya)
        if (Schema::hasTable('master_pegawai') && Schema::hasColumn('master_pegawai', 'user_id')) {
            Schema::table('master_pegawai', function (Blueprint $table) {
                try { $table->dropForeign(['user_id']); } catch (\Throwable $e) {}
                $table->dropColumn('user_id');
            });
        }

        // 5) Hapus kolom user_id dari master_pihak (dan FK-nya)
        if (Schema::hasTable('master_pihak') && Schema::hasColumn('master_pihak', 'user_id')) {
            Schema::table('master_pihak', function (Blueprint $table) {
                try { $table->dropForeign(['user_id']); } catch (\Throwable $e) {}
                $table->dropColumn('user_id');
            });
        }
    }

    public function down(): void
    {
        // Kembalikan kolom user_id pada master_pegawai
        if (Schema::hasTable('master_pegawai') && ! Schema::hasColumn('master_pegawai', 'user_id')) {
            Schema::table('master_pegawai', function (Blueprint $table) {
                $table->foreignId('user_id')->nullable()->after('id')->constrained('users')->nullOnDelete();
            });
        }

        // Kembalikan kolom user_id pada master_pihak
        if (Schema::hasTable('master_pihak') && ! Schema::hasColumn('master_pihak', 'user_id')) {
            Schema::table('master_pihak', function (Blueprint $table) {
                $table->foreignId('user_id')->nullable()->after('kode_pihak')->constrained('users')->nullOnDelete();
            });
        }

        // Restore data balik dari users.profilable_*
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'profilable_type')) {
            DB::table('users')
                ->whereNotNull('profilable_type')
                ->whereNotNull('profilable_id')
                ->orderBy('id')
                ->select('id', 'profilable_type', 'profilable_id')
                ->chunk(200, function ($rows) {
                    foreach ($rows as $row) {
                        if ($row->profilable_type === \App\Models\MasterPegawai::class) {
                            DB::table('master_pegawai')->where('id', $row->profilable_id)->update(['user_id' => $row->id]);
                        } elseif (in_array($row->profilable_type, [\App\Models\MasterPihak::class, \App\Models\MasterMitraVendor::class], true)) {
                            DB::table('master_pihak')->where('id', $row->profilable_id)->update(['user_id' => $row->id]);
                        }
                    }
                });

            Schema::table('users', function (Blueprint $table) {
                try { $table->dropUnique('users_profilable_unique'); } catch (\Throwable $e) {}
                $table->dropMorphs('profilable');
            });
        }
    }
};
