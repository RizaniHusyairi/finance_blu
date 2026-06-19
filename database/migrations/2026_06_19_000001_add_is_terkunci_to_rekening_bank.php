<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rekening_bank', function (Blueprint $table) {
            // Penanda rekening bawaan sistem (default Penerimaan/Pengeluaran).
            // Rekening terkunci tetap boleh diedit/dinonaktifkan, tetapi TIDAK
            // boleh dihapus agar resolusi sumber BKU selalu punya rekening rujukan.
            $table->boolean('is_terkunci')->default(false)->after('is_default');
        });
    }

    public function down(): void
    {
        Schema::table('rekening_bank', function (Blueprint $table) {
            $table->dropColumn('is_terkunci');
        });
    }
};
