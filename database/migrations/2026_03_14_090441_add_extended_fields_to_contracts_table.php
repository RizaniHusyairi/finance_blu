<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->string('id_transaksi')->nullable()->after('id');
            $table->string('nomor_spk_sp')->nullable()->after('contract_number');
            $table->string('mata_uang')->default('IDR')->after('description');
            $table->string('cara_bayar')->nullable()->after('mata_uang'); // Sekali Bayar, Termin
            $table->foreignId('ppk_id')->nullable()->constrained('users')->nullOnDelete()->after('budget_id');
            $table->foreignId('pejabat_pengadaan_id')->nullable()->constrained('users')->nullOnDelete()->after('ppk_id');
            $table->integer('tahun_anggaran')->nullable()->after('pejabat_pengadaan_id');
            
            // Waktu Pekerjaan
            $table->integer('jangka_waktu_pekerjaan')->nullable()->after('end_date');
            $table->string('satuan_waktu_pekerjaan')->nullable()->after('jangka_waktu_pekerjaan'); // Hari, Minggu, Bulan
            
            // Waktu Pemeliharaan
            $table->boolean('ada_masa_pemeliharaan')->default(false)->after('satuan_waktu_pekerjaan');
            $table->integer('jangka_waktu_pemeliharaan')->nullable()->after('ada_masa_pemeliharaan');
            $table->date('tanggal_mulai_pemeliharaan')->nullable()->after('jangka_waktu_pemeliharaan');
            $table->date('tanggal_selesai_pemeliharaan')->nullable()->after('tanggal_mulai_pemeliharaan');
            
            // Termin
            $table->integer('jumlah_termin')->default(1)->after('cara_bayar');

            // Uang Muka
            $table->boolean('ada_uang_muka')->default(false)->after('jumlah_termin');
            $table->decimal('nilai_uang_muka', 20, 2)->nullable()->after('ada_uang_muka');
            $table->decimal('persentase_uang_muka', 5, 2)->nullable()->after('nilai_uang_muka');
            $table->integer('jumlah_angsuran_um')->nullable()->after('persentase_uang_muka');

            // Jaminan UM
            $table->string('penjamin_um')->nullable()->after('jumlah_angsuran_um');
            $table->string('nomor_jaminan_um')->nullable()->after('penjamin_um');
            $table->date('tanggal_jaminan_um')->nullable()->after('nomor_jaminan_um');
            $table->integer('masa_berlaku_jaminan')->nullable()->after('tanggal_jaminan_um');
            $table->date('tanggal_mulai_jaminan')->nullable()->after('masa_berlaku_jaminan');
            $table->date('tanggal_selesai_jaminan')->nullable()->after('tanggal_mulai_jaminan');
        });

        Schema::table('contract_terms', function(Blueprint $table) {
            $table->string('type')->default('Termin')->after('term_name'); // Termin, Uang Muka
            $table->date('target_date')->nullable()->after('amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contract_terms', function (Blueprint $table) {
            $table->dropColumn(['type', 'target_date']);
        });

        Schema::table('contracts', function (Blueprint $table) {
            $table->dropForeign(['ppk_id']);
            $table->dropForeign(['pejabat_pengadaan_id']);
            $table->dropColumn([
                'id_transaksi', 'nomor_spk_sp', 'mata_uang', 'cara_bayar', 'ppk_id', 'pejabat_pengadaan_id',
                'tahun_anggaran', 'jangka_waktu_pekerjaan', 'satuan_waktu_pekerjaan', 'ada_masa_pemeliharaan',
                'jangka_waktu_pemeliharaan', 'tanggal_mulai_pemeliharaan', 'tanggal_selesai_pemeliharaan',
                'jumlah_termin', 'ada_uang_muka', 'nilai_uang_muka', 'persentase_uang_muka', 'jumlah_angsuran_um',
                'penjamin_um', 'nomor_jaminan_um', 'tanggal_jaminan_um', 'masa_berlaku_jaminan',
                'tanggal_mulai_jaminan', 'tanggal_selesai_jaminan'
            ]);
        });
    }
};
