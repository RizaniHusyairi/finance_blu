<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('detail_perjaldin', function (Blueprint $table) {
            if (DB::getDriverName() !== 'sqlite') {
                $table->dropForeign(['pegawai_id']);
            }
            $table->unsignedBigInteger('pegawai_id')->nullable()->change();
            
            $table->string('nama_pegawai')->nullable()->after('pegawai_id');
            $table->string('nip')->nullable()->after('nama_pegawai');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('detail_perjaldin', function (Blueprint $table) {
            $table->dropColumn(['nama_pegawai', 'nip']);
            
            $table->unsignedBigInteger('pegawai_id')->nullable(false)->change();
            if (DB::getDriverName() !== 'sqlite') {
                $table->foreign('pegawai_id')->references('id')->on('master_pegawai');
            }
        });
    }
};
