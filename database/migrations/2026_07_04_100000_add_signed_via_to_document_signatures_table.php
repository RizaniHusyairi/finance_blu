<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Penanda asal tanda tangan vendor pada dokumen (BAP/BAPP/BAST):
 * - ONLINE : vendor menyetujui sendiri via magic link TTE.
 * - MANUAL : staf mengunggah scan TTD basah atas nama vendor
 *            (signed_by_user_id = staf pengunggah, untuk audit).
 * Baris lama (sebelum fitur unggah manual) bernilai NULL — diperlakukan ONLINE.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_signatures', function (Blueprint $table) {
            $table->string('signed_via', 10)->nullable()->after('status');
            $table->foreignId('signed_by_user_id')->nullable()->after('signed_via')
                ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('document_signatures', function (Blueprint $table) {
            $table->dropConstrainedForeignId('signed_by_user_id');
            $table->dropColumn('signed_via');
        });
    }
};
