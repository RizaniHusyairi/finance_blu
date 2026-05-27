<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('detail_perjaldin', function (Blueprint $table) {
            foreach ([
                ['transport_file_path', 'biaya_transport'],
                ['transport_file_name', 'transport_file_path'],
                ['penginapan_file_path', 'biaya_penginapan'],
                ['penginapan_file_name', 'penginapan_file_path'],
                ['uang_harian_file_path', 'uang_harian'],
                ['uang_harian_file_name', 'uang_harian_file_path'],
            ] as [$col, $after]) {
                if (! Schema::hasColumn('detail_perjaldin', $col)) {
                    $table->string($col, 255)->nullable()->after($after);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('detail_perjaldin', function (Blueprint $table) {
            foreach ([
                'transport_file_path', 'transport_file_name',
                'penginapan_file_path', 'penginapan_file_name',
                'uang_harian_file_path', 'uang_harian_file_name',
            ] as $col) {
                if (Schema::hasColumn('detail_perjaldin', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
