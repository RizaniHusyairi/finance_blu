<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('detail_perjaldin', function (Blueprint $table) {
            if (! Schema::hasColumn('detail_perjaldin', 'tiket_file_path')) {
                $table->string('tiket_file_path', 255)->nullable()->after('biaya_tiket');
            }
            if (! Schema::hasColumn('detail_perjaldin', 'tiket_file_name')) {
                $table->string('tiket_file_name', 255)->nullable()->after('tiket_file_path');
            }
        });
    }

    public function down(): void
    {
        Schema::table('detail_perjaldin', function (Blueprint $table) {
            if (Schema::hasColumn('detail_perjaldin', 'tiket_file_name')) {
                $table->dropColumn('tiket_file_name');
            }
            if (Schema::hasColumn('detail_perjaldin', 'tiket_file_path')) {
                $table->dropColumn('tiket_file_path');
            }
        });
    }
};
