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
        Schema::table('detail_honorarium', function (Blueprint $table) {
            $table->string('no_hp', 50)->nullable()->after('nama_rekening');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('detail_honorarium', function (Blueprint $table) {
            $table->dropColumn('no_hp');
        });
    }
};
