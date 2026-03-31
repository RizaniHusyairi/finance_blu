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
            // Drop foreign key and column
            $table->dropForeign(['personel_id']);
            $table->dropColumn('personel_id');

            // Add new manual fields
            $table->string('nama_personel', 255)->after('tagihan_id');
            $table->string('nrp_nip', 100)->nullable()->after('nama_personel');
            $table->string('pangkat_korp', 100)->nullable()->after('nrp_nip');
            $table->string('jabatan', 100)->nullable()->after('pangkat_korp');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('detail_honorarium', function (Blueprint $table) {
            $table->dropColumn(['nama_personel', 'nrp_nip', 'pangkat_korp', 'jabatan']);
            $table->foreignId('personel_id')->constrained('master_personel_eksternal')->restrictOnDelete();
        });
    }
};
