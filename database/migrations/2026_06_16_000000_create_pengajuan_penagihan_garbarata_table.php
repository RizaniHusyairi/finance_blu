<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengajuan_penagihan_garbarata', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mitra_jasa_id')->constrained('mitra_jasa')->cascadeOnDelete();
            $table->unsignedSmallInteger('periode_tahun');
            $table->unsignedTinyInteger('periode_bulan');
            $table->unsignedInteger('jumlah_pemakaian')->default(0);
            $table->unsignedInteger('total_rentang')->default(0);
            $table->string('status', 20)->default('DIAJUKAN');
            $table->text('catatan_amc')->nullable();
            $table->text('catatan_admin')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('tagihan_jasa_id')->nullable()->constrained('tagihan_jasas')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['mitra_jasa_id', 'periode_tahun', 'periode_bulan'], 'idx_pengajuan_mitra_periode');
            $table->index('status');
        });

        Schema::table('pemakaian_garbarata', function (Blueprint $table) {
            $table->foreignId('pengajuan_penagihan_garbarata_id')->nullable()
                ->after('tagihan_jasa_id')
                ->constrained('pengajuan_penagihan_garbarata', 'id', 'fk_pemakaian_pengajuan')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pemakaian_garbarata', function (Blueprint $table) {
            $table->dropForeign('fk_pemakaian_pengajuan');
            $table->dropColumn('pengajuan_penagihan_garbarata_id');
        });
        Schema::dropIfExists('pengajuan_penagihan_garbarata');
    }
};
