<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pengajuan persetujuan KPA (Standing Instruction) dipindah dari tahap
     * verifikasi SPP ke tahap verifikasi tagihan, sehingga kolom status
     * persetujuan KPA kini hidup di tabel tagihan. Kolom lama di dokumen_spp
     * dibiarkan untuk jejak historis.
     */
    public function up(): void
    {
        Schema::table('tagihan', function (Blueprint $table) {
            $table->string('kpa_approval_status')->nullable()->after('status')->comment('PENDING_KPA, APPROVED, REJECTED');
            $table->timestamp('kpa_approved_at')->nullable()->after('kpa_approval_status');
            $table->unsignedBigInteger('kpa_approved_by')->nullable()->after('kpa_approved_at');
            $table->text('kpa_approval_notes')->nullable()->after('kpa_approved_by');
        });

        // Backfill dari SPP terakhir per tagihan agar riwayat persetujuan KPA
        // yang sudah berjalan tetap terbaca di gerbang baru (tagihan).
        $spps = DB::table('dokumen_spp')
            ->whereNotNull('kpa_approval_status')
            ->whereNotNull('tagihan_id')
            ->orderBy('id')
            ->get(['tagihan_id', 'kpa_approval_status', 'kpa_approved_at', 'kpa_approved_by', 'kpa_approval_notes']);

        foreach ($spps as $spp) {
            DB::table('tagihan')->where('id', $spp->tagihan_id)->update([
                'kpa_approval_status' => $spp->kpa_approval_status,
                'kpa_approved_at' => $spp->kpa_approved_at,
                'kpa_approved_by' => $spp->kpa_approved_by,
                'kpa_approval_notes' => $spp->kpa_approval_notes,
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('tagihan', function (Blueprint $table) {
            $table->dropColumn([
                'kpa_approval_status',
                'kpa_approved_at',
                'kpa_approved_by',
                'kpa_approval_notes',
            ]);
        });
    }
};
