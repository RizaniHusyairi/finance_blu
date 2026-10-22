<?php

use App\Models\TagihanJasa;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Alur verifikasi kedua untuk tagihan jasa layanan aero:
 *   Koordinator Jasa -> Kasi Jasa -> Kasubag TU, lalu surat pengantar ber-TTE Kasi Jasa
 *   dan tagihan kembali ke Kasi Jasa untuk diterbitkan.
 * Alur yang dipakai disimpan per tagihan (alur_verifikasi) agar tagihan yang sedang
 * berjalan tidak terpengaruh bila Super Admin mengganti pengaturan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tagihan_jasas', function (Blueprint $table) {
            if (! Schema::hasColumn('tagihan_jasas', 'alur_verifikasi')) {
                $table->string('alur_verifikasi', 20)->default('KABANDARA')->after('status');
            }
        });

        if (! Schema::hasTable('workflow_definitions') || ! Schema::hasTable('workflow_definition_steps')) {
            return;
        }

        $now = now();
        $definisi = DB::table('workflow_definitions')->where('kode', 'TAGIHAN_JASA_KASI')->first();
        $definisiId = $definisi?->id ?? DB::table('workflow_definitions')->insertGetId([
            'kode' => 'TAGIHAN_JASA_KASI',
            'nama' => 'Alur Persetujuan Tagihan Jasa Aero (TTE Kasi Jasa)',
            'target_type' => TagihanJasa::class,
            'status_aktif' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        foreach (self::steps() as $step) {
            DB::table('workflow_definition_steps')->updateOrInsert(
                ['workflow_definition_id' => $definisiId, 'urutan_step' => $step['urutan_step']],
                $step + ['created_at' => $now, 'updated_at' => $now]
            );
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('workflow_definitions')) {
            $id = DB::table('workflow_definitions')->where('kode', 'TAGIHAN_JASA_KASI')->value('id');
            if ($id) {
                DB::table('workflow_definition_steps')->where('workflow_definition_id', $id)->delete();
                DB::table('workflow_definitions')->where('id', $id)->delete();
            }
        }

        Schema::table('tagihan_jasas', function (Blueprint $table) {
            if (Schema::hasColumn('tagihan_jasas', 'alur_verifikasi')) {
                $table->dropColumn('alur_verifikasi');
            }
        });
    }

    public static function steps(): array
    {
        $dasar = ['is_required' => true, 'can_reject' => true, 'can_request_revision' => true];

        return [
            ['urutan_step' => 1, 'role_code' => 'Koordinator Jasa', 'nama_step' => 'Verifikasi Koordinator Jasa'] + $dasar,
            ['urutan_step' => 2, 'role_code' => 'Kepala Seksi Pelayanan dan Kerjasama', 'nama_step' => 'Verifikasi Kepala Seksi Pelayanan dan Kerjasama'] + $dasar,
            ['urutan_step' => 3, 'role_code' => 'Kepala Subbagian Keuangan dan Tata Usaha', 'nama_step' => 'Verifikasi KASUBBAG TU (TTE otomatis Kasi Jasa)'] + $dasar,
        ];
    }
};
