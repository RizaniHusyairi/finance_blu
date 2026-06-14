<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tagihan_jasas', function (Blueprint $table) {
            if (!Schema::hasColumn('tagihan_jasas', 'final_verifier_user_id')) {
                // Pejabat PLT/PLH terpilih sebagai verifikator final & penandatangan.
                $table->foreignId('final_verifier_user_id')->nullable()->after('final_verifier_role')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('tagihan_jasas', 'final_verifier_jenis')) {
                // 'PLT' atau 'PLH' — hanya memengaruhi label jabatan pada surat pengantar.
                $table->string('final_verifier_jenis', 10)->nullable()->after('final_verifier_user_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tagihan_jasas', function (Blueprint $table) {
            if (Schema::hasColumn('tagihan_jasas', 'final_verifier_user_id')) {
                $table->dropConstrainedForeignId('final_verifier_user_id');
            }
            if (Schema::hasColumn('tagihan_jasas', 'final_verifier_jenis')) {
                $table->dropColumn('final_verifier_jenis');
            }
        });
    }
};
