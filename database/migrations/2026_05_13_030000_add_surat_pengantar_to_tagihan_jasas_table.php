<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tagihan_jasas', function (Blueprint $table) {
            if (! Schema::hasColumn('tagihan_jasas', 'nomor_surat_pengantar')) {
                $table->string('nomor_surat_pengantar')->nullable()->after('nomor_tagihan');
            }

            if (! Schema::hasColumn('tagihan_jasas', 'tanggal_surat_pengantar')) {
                $table->date('tanggal_surat_pengantar')->nullable()->after('nomor_surat_pengantar');
            }

            if (! Schema::hasColumn('tagihan_jasas', 'perihal_surat_pengantar')) {
                $table->string('perihal_surat_pengantar')->nullable()->after('tanggal_surat_pengantar');
            }

            if (! Schema::hasColumn('tagihan_jasas', 'pejabat_penandatangan_nama')) {
                $table->string('pejabat_penandatangan_nama')->nullable()->after('perihal_surat_pengantar');
            }

            if (! Schema::hasColumn('tagihan_jasas', 'pejabat_penandatangan_nip')) {
                $table->string('pejabat_penandatangan_nip')->nullable()->after('pejabat_penandatangan_nama');
            }

            if (! Schema::hasColumn('tagihan_jasas', 'pejabat_penandatangan_jabatan')) {
                $table->string('pejabat_penandatangan_jabatan')->nullable()->after('pejabat_penandatangan_nip');
            }

            if (! Schema::hasColumn('tagihan_jasas', 'file_surat_pengantar_final')) {
                $table->string('file_surat_pengantar_final')->nullable()->after('pejabat_penandatangan_jabatan');
            }

            if (! Schema::hasColumn('tagihan_jasas', 'uploaded_surat_pengantar_by')) {
                $table->foreignId('uploaded_surat_pengantar_by')->nullable()->after('file_surat_pengantar_final')->constrained('users')->nullOnDelete();
            }

            if (! Schema::hasColumn('tagihan_jasas', 'uploaded_surat_pengantar_at')) {
                $table->timestamp('uploaded_surat_pengantar_at')->nullable()->after('uploaded_surat_pengantar_by');
            }

            if (! Schema::hasColumn('tagihan_jasas', 'status_dokumen_pengantar')) {
                $table->string('status_dokumen_pengantar', 50)->default('DRAFT')->after('uploaded_surat_pengantar_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tagihan_jasas', function (Blueprint $table) {
            if (Schema::hasColumn('tagihan_jasas', 'uploaded_surat_pengantar_by')) {
                $table->dropConstrainedForeignId('uploaded_surat_pengantar_by');
            }

            foreach ([
                'status_dokumen_pengantar',
                'uploaded_surat_pengantar_at',
                'file_surat_pengantar_final',
                'pejabat_penandatangan_jabatan',
                'pejabat_penandatangan_nip',
                'pejabat_penandatangan_nama',
                'perihal_surat_pengantar',
                'tanggal_surat_pengantar',
                'nomor_surat_pengantar',
            ] as $column) {
                if (Schema::hasColumn('tagihan_jasas', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
