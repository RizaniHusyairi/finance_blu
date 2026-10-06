<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // The original internal column is a MySQL ENUM; make both contract
        // types use the same extensible status representation.
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE kontrak_termin MODIFY COLUMN status_termin VARCHAR(32) NOT NULL DEFAULT 'LOCKED'");
        }

        foreach (['kontrak_termin', 'kontrak_eksternal_termin'] as $table) {
            DB::table($table)->where('status_termin', 'SUDAH_DITAGIH')
                ->update(['status_termin' => 'DALAM_PROSES']);
        }

        // Legacy submitted bills that already have an executed SP2D are paid.
        $paidTagihanIds = DB::table('dokumen_spp as spp')
            ->join('dokumen_spm as spm', 'spm.spp_id', '=', 'spp.id')
            ->join('dokumen_npi as npi', 'npi.spm_id', '=', 'spm.id')
            ->join('dokumen_sp2d as sp2d', 'sp2d.npi_id', '=', 'npi.id')
            ->where('sp2d.status', 'EXECUTED')
            ->whereNull('sp2d.deleted_at')
            ->pluck('spp.tagihan_id');

        foreach ($paidTagihanIds->chunk(500) as $tagihanIds) {
            DB::table('kontrak_termin')->whereIn('id', DB::table('detail_kontrak')
                ->whereIn('tagihan_id', $tagihanIds)->select('kontrak_termin_id'))
                ->update(['status_termin' => 'LUNAS']);

            DB::table('kontrak_eksternal_termin')->whereIn('id', DB::table('detail_kontrak_eksternal')
                ->whereIn('tagihan_id', $tagihanIds)->select('kontrak_eksternal_termin_id'))
                ->update(['status_termin' => 'LUNAS']);
        }
    }

    public function down(): void
    {
        foreach (['kontrak_termin', 'kontrak_eksternal_termin'] as $table) {
            DB::table($table)->whereIn('status_termin', ['DALAM_PROSES', 'LUNAS'])
                ->update(['status_termin' => 'SUDAH_DITAGIH']);
        }
    }
};
