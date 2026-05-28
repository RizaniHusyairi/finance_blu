<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('detail_perjaldin', function (Blueprint $table) {
            if (! Schema::hasColumn('detail_perjaldin', 'spt_file_path')) {
                $table->string('spt_file_path', 255)->nullable()->after('no_spt');
            }
            if (! Schema::hasColumn('detail_perjaldin', 'spt_file_name')) {
                $table->string('spt_file_name', 255)->nullable()->after('spt_file_path');
            }
        });
    }

    public function down(): void
    {
        Schema::table('detail_perjaldin', function (Blueprint $table) {
            foreach (['spt_file_path', 'spt_file_name'] as $col) {
                if (Schema::hasColumn('detail_perjaldin', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
