<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('kontrak_pengadaan', function (Blueprint $table) {
            if (!Schema::hasColumn('kontrak_pengadaan', 'ppk_user_id')) {
                $table->foreignId('ppk_user_id')
                    ->nullable()
                    ->after('vendor_id')
                    ->constrained('users')
                    ->nullOnDelete();
            }
        });

        $kontraks = DB::table('kontrak_pengadaan')
            ->select('id', 'nama_ppk', 'nip_ppk')
            ->get();

        foreach ($kontraks as $kontrak) {
            $ppkUserId = null;

            if (!empty($kontrak->nip_ppk)) {
                $ppkUserId = DB::table('master_pegawai')
                    ->where('nip', $kontrak->nip_ppk)
                    ->value('user_id');
            }

            if (!$ppkUserId && !empty($kontrak->nama_ppk)) {
                $ppkUserId = DB::table('master_pegawai')
                    ->where('nama_lengkap', $kontrak->nama_ppk)
                    ->value('user_id');
            }

            if (!$ppkUserId && !empty($kontrak->nama_ppk)) {
                $ppkUserId = DB::table('users')
                    ->where('name', $kontrak->nama_ppk)
                    ->value('id');
            }

            if ($ppkUserId) {
                DB::table('kontrak_pengadaan')
                    ->where('id', $kontrak->id)
                    ->update(['ppk_user_id' => $ppkUserId]);
            }
        }

        Schema::table('kontrak_pengadaan', function (Blueprint $table) {
            $dropColumns = [];

            if (Schema::hasColumn('kontrak_pengadaan', 'nama_ppk')) {
                $dropColumns[] = 'nama_ppk';
            }

            if (Schema::hasColumn('kontrak_pengadaan', 'nip_ppk')) {
                $dropColumns[] = 'nip_ppk';
            }

            if ($dropColumns !== []) {
                $table->dropColumn($dropColumns);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kontrak_pengadaan', function (Blueprint $table) {
            if (!Schema::hasColumn('kontrak_pengadaan', 'nama_ppk')) {
                $table->string('nama_ppk', 150)->nullable()->after('nama_pekerjaan');
            }

            if (!Schema::hasColumn('kontrak_pengadaan', 'nip_ppk')) {
                $table->string('nip_ppk', 50)->nullable()->after('nama_ppk');
            }
        });

        $kontraks = DB::table('kontrak_pengadaan')
            ->select('id', 'ppk_user_id')
            ->whereNotNull('ppk_user_id')
            ->get();

        foreach ($kontraks as $kontrak) {
            $pegawai = DB::table('master_pegawai')
                ->where('user_id', $kontrak->ppk_user_id)
                ->first(['nama_lengkap', 'nip']);

            $user = DB::table('users')
                ->where('id', $kontrak->ppk_user_id)
                ->first(['name']);

            DB::table('kontrak_pengadaan')
                ->where('id', $kontrak->id)
                ->update([
                    'nama_ppk' => $pegawai->nama_lengkap ?? $user->name ?? null,
                    'nip_ppk' => $pegawai->nip ?? null,
                ]);
        }

        Schema::table('kontrak_pengadaan', function (Blueprint $table) {
            if (Schema::hasColumn('kontrak_pengadaan', 'ppk_user_id')) {
                $table->dropConstrainedForeignId('ppk_user_id');
            }
        });
    }
};
