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
        Schema::table('transactions', function (Blueprint $table) {
            $table->foreignId('ppk_id')->nullable()->after('supplier_id')->constrained('users')->nullOnDelete();
            $table->string('jenis_pengajuan')->nullable()->after('type'); // Kontrak / Non Kontrak
            $table->string('jenis_dokumen_dasar')->nullable()->after('description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('ppk_id');
            $table->dropColumn(['jenis_pengajuan', 'jenis_dokumen_dasar']);
        });
    }
};
