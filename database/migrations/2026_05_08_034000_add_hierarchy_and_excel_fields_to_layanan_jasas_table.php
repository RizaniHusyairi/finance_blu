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
        Schema::table('layanan_jasas', function (Blueprint $table) {
            $table->unsignedBigInteger('parent_id')->nullable()->after('id');
            $table->integer('level')->default(1)->after('parent_id');
            $table->string('kode_mak')->nullable()->after('nama_layanan');
            $table->string('kode_akun')->nullable()->after('kode_mak');
            $table->boolean('is_leaf')->default(true)->after('is_active');
            
            $table->foreign('parent_id')->references('id')->on('layanan_jasas')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('layanan_jasas', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropColumn(['parent_id', 'level', 'kode_mak', 'kode_akun', 'is_leaf']);
        });
    }
};
