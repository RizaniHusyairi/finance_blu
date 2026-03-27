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
        Schema::table('contract_addendums', function (Blueprint $table) {
            $table->enum('status', ['Draft', 'Menunggu PPK', 'Ditolak', 'Disetujui'])->default('Draft')->after('reason');
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete()->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contract_addendums', function (Blueprint $table) {
            $table->dropForeign(['submitted_by']);
            $table->dropColumn('submitted_by');
            $table->dropColumn('status');
        });
    }
};
