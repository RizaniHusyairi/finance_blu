<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Perluas buku_kas_umum agar mendukung model SILABI multi-buku per peran.
 *
 * - peran           : PENERIMAAN | PENGELUARAN (pisah dua BKU resmi).
 * - kode_buku        : 1=BKU, 2=Tunai, 3=Bank, … (lihat App\Enums\KodeBuku).
 * - kode_transaksi   : kode SILABI sumber (untuk baris sisi pengeluaran).
 * - akun_pendapatan_id: klasifikasi akun (untuk baris sisi penerimaan).
 * - transaksi_pembukuan_id: tautan ke baris jurnal yang menghasilkan baris ini.
 *
 * Backfill: baris lama berperan PENERIMAAN bila punya referensi_penerimaan_id,
 * selain itu PENGELUARAN; semuanya kode_buku=1 (BKU) — perilaku saldo lama setara.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('buku_kas_umum', function (Blueprint $table) {
            if (! Schema::hasColumn('buku_kas_umum', 'peran')) {
                $table->string('peran', 20)->default('PENGELUARAN')->after('arus_kas');
            }
            if (! Schema::hasColumn('buku_kas_umum', 'kode_buku')) {
                $table->unsignedSmallInteger('kode_buku')->default(1)->after('peran');
            }
            if (! Schema::hasColumn('buku_kas_umum', 'kode_transaksi')) {
                $table->string('kode_transaksi', 8)->nullable()->after('kode_buku');
            }
            if (! Schema::hasColumn('buku_kas_umum', 'akun_pendapatan_id')) {
                $table->foreignId('akun_pendapatan_id')->nullable()->after('kode_transaksi')
                    ->constrained('akun_pendapatan')->nullOnDelete();
            }
            if (! Schema::hasColumn('buku_kas_umum', 'transaksi_pembukuan_id')) {
                $table->foreignId('transaksi_pembukuan_id')->nullable()->after('akun_pendapatan_id')
                    ->constrained('transaksi_pembukuan')->nullOnDelete();
            }
            if (! Schema::hasColumn('buku_kas_umum', 'detail_mutasi_bank_id')) {
                // Sumber penerimaan dari baris rekening koran (Fase 3).
                $table->foreignId('detail_mutasi_bank_id')->nullable()->after('transaksi_pembukuan_id')
                    ->constrained('detail_mutasi_bank')->nullOnDelete();
            }
        });

        // Backfill peran untuk baris existing.
        DB::table('buku_kas_umum')
            ->whereNotNull('referensi_penerimaan_id')
            ->update(['peran' => 'PENERIMAAN']);
        DB::table('buku_kas_umum')
            ->whereNull('referensi_penerimaan_id')
            ->update(['peran' => 'PENGELUARAN']);

        Schema::table('buku_kas_umum', function (Blueprint $table) {
            $table->index(['peran', 'kode_buku', 'sumber_rekening_id'], 'bku_peran_buku_rek_idx');
        });
    }

    public function down(): void
    {
        Schema::table('buku_kas_umum', function (Blueprint $table) {
            $table->dropIndex('bku_peran_buku_rek_idx');
            foreach (['detail_mutasi_bank_id', 'transaksi_pembukuan_id', 'akun_pendapatan_id'] as $col) {
                if (Schema::hasColumn('buku_kas_umum', $col)) {
                    $table->dropConstrainedForeignId($col);
                }
            }
            foreach (['kode_transaksi', 'kode_buku', 'peran'] as $col) {
                if (Schema::hasColumn('buku_kas_umum', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
