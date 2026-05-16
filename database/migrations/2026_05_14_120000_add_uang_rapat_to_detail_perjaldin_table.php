<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('detail_perjaldin', function (Blueprint $table) {
            if (! Schema::hasColumn('detail_perjaldin', 'uang_rapat')) {
                $table->decimal('uang_rapat', 18, 2)->default(0)->after('uang_representasi');
            }
        });
    }

    public function down(): void
    {
        Schema::table('detail_perjaldin', function (Blueprint $table) {
            if (Schema::hasColumn('detail_perjaldin', 'uang_rapat')) {
                $table->dropColumn('uang_rapat');
            }
        });
    }
};
