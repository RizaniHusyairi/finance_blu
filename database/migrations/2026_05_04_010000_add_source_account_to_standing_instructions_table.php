<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('standing_instructions', function (Blueprint $table) {
            if (! Schema::hasColumn('standing_instructions', 'rekening_sumber_nomor')) {
                $table->string('rekening_sumber_nomor')->nullable()->after('jabatan_kpa_snapshot');
            }

            if (! Schema::hasColumn('standing_instructions', 'rekening_sumber_nama')) {
                $table->string('rekening_sumber_nama')->nullable()->after('rekening_sumber_nomor');
            }

            if (! Schema::hasColumn('standing_instructions', 'rekening_sumber_bank')) {
                $table->string('rekening_sumber_bank')->nullable()->after('rekening_sumber_nama');
            }
        });
    }

    public function down(): void
    {
        Schema::table('standing_instructions', function (Blueprint $table) {
            if (Schema::hasColumn('standing_instructions', 'rekening_sumber_bank')) {
                $table->dropColumn('rekening_sumber_bank');
            }

            if (Schema::hasColumn('standing_instructions', 'rekening_sumber_nama')) {
                $table->dropColumn('rekening_sumber_nama');
            }

            if (Schema::hasColumn('standing_instructions', 'rekening_sumber_nomor')) {
                $table->dropColumn('rekening_sumber_nomor');
            }
        });
    }
};
