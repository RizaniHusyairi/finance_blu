<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('layanan_jasas')
            ->whereNull('parent_id')
            ->whereIn('nama_layanan', [
                'Tarif Jasa Kebandarudaraan yang bersifat domestik',
                'Tarif Jasa Kebandarudaraan yang bersifat Internasional',
                'TARIF JASA TERKAIT BANDAR UDARA BERDASARKAN TUGAS DAN FUNGSI',
                'Tarif pemeriksaan keamanan sisi kargo dan jasa penumpukan.',
                'TARIF PENERBITAN IZIN DI DAERAH KEAMANAN TERBATAS',
            ])
            ->delete();
    }

    public function down(): void
    {
        // Data lama hasil import tidak dibuat ulang di rollback karena sudah digantikan tree layanan baru.
    }
};
