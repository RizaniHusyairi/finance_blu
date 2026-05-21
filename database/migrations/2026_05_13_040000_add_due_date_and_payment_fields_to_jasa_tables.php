<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('layanan_jasas', function (Blueprint $table) {
            if (! Schema::hasColumn('layanan_jasas', 'jumlah_hari_jatuh_tempo')) {
                $table->unsignedSmallInteger('jumlah_hari_jatuh_tempo')->default(30)->after('tarif_dasar');
            }

            if (! Schema::hasColumn('layanan_jasas', 'masa_toleransi_hari')) {
                $table->unsignedSmallInteger('masa_toleransi_hari')->default(0)->after('jumlah_hari_jatuh_tempo');
            }

            if (! Schema::hasColumn('layanan_jasas', 'wajib_tagihan_terpisah')) {
                $table->boolean('wajib_tagihan_terpisah')->default(false)->after('masa_toleransi_hari');
            }

            if (! Schema::hasColumn('layanan_jasas', 'catatan_jatuh_tempo')) {
                $table->text('catatan_jatuh_tempo')->nullable()->after('wajib_tagihan_terpisah');
            }
        });

        Schema::table('tagihan_jasas', function (Blueprint $table) {
            if (! Schema::hasColumn('tagihan_jasas', 'tanggal_publish')) {
                $table->date('tanggal_publish')->nullable()->after('tanggal_tagihan');
            }

            if (! Schema::hasColumn('tagihan_jasas', 'jumlah_hari_jatuh_tempo')) {
                $table->unsignedSmallInteger('jumlah_hari_jatuh_tempo')->default(30)->after('tanggal_publish');
            }

            if (! Schema::hasColumn('tagihan_jasas', 'masa_toleransi_hari')) {
                $table->unsignedSmallInteger('masa_toleransi_hari')->default(0)->after('jumlah_hari_jatuh_tempo');
            }

            if (! Schema::hasColumn('tagihan_jasas', 'tanggal_jatuh_tempo')) {
                $table->date('tanggal_jatuh_tempo')->nullable()->after('masa_toleransi_hari');
            }

            if (! Schema::hasColumn('tagihan_jasas', 'tanggal_akhir_toleransi')) {
                $table->date('tanggal_akhir_toleransi')->nullable()->after('tanggal_jatuh_tempo');
            }

            if (! Schema::hasColumn('tagihan_jasas', 'catatan_jatuh_tempo')) {
                $table->text('catatan_jatuh_tempo')->nullable()->after('tanggal_akhir_toleransi');
            }

            if (! Schema::hasColumn('tagihan_jasas', 'status_pembayaran')) {
                $table->string('status_pembayaran', 30)->default('belum_dibayar')->after('status');
            }

            if (! Schema::hasColumn('tagihan_jasas', 'tanggal_lunas')) {
                $table->date('tanggal_lunas')->nullable()->after('status_pembayaran');
            }

            if (! Schema::hasColumn('tagihan_jasas', 'jumlah_dibayar')) {
                $table->decimal('jumlah_dibayar', 20, 2)->default(0)->after('tanggal_lunas');
            }

            if (! Schema::hasColumn('tagihan_jasas', 'sisa_tagihan')) {
                $table->decimal('sisa_tagihan', 20, 2)->default(0)->after('jumlah_dibayar');
            }
        });

        DB::table('tagihan_jasas')
            ->where('status', 'LUNAS')
            ->update([
                'status_pembayaran' => 'lunas',
                'tanggal_lunas' => DB::raw('COALESCE(tanggal_lunas, DATE(updated_at))'),
                'jumlah_dibayar' => DB::raw('total_tagihan'),
                'sisa_tagihan' => 0,
            ]);

        DB::table('tagihan_jasas')
            ->where('status', 'PUBLISHED')
            ->where('status_pembayaran', 'belum_dibayar')
            ->update([
                'sisa_tagihan' => DB::raw('total_tagihan'),
            ]);
    }

    public function down(): void
    {
        Schema::table('tagihan_jasas', function (Blueprint $table) {
            foreach ([
                'sisa_tagihan',
                'jumlah_dibayar',
                'tanggal_lunas',
                'status_pembayaran',
                'catatan_jatuh_tempo',
                'tanggal_akhir_toleransi',
                'tanggal_jatuh_tempo',
                'masa_toleransi_hari',
                'jumlah_hari_jatuh_tempo',
                'tanggal_publish',
            ] as $column) {
                if (Schema::hasColumn('tagihan_jasas', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('layanan_jasas', function (Blueprint $table) {
            foreach ([
                'catatan_jatuh_tempo',
                'wajib_tagihan_terpisah',
                'masa_toleransi_hari',
                'jumlah_hari_jatuh_tempo',
            ] as $column) {
                if (Schema::hasColumn('layanan_jasas', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
