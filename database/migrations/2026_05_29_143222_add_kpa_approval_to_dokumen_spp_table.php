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
        Schema::table('dokumen_spp', function (Blueprint $table) {
            $table->string('kpa_approval_status')->nullable()->after('status')->comment('PENDING_KPA, APPROVED, REJECTED');
            $table->timestamp('kpa_approved_at')->nullable()->after('kpa_approval_status');
            $table->unsignedBigInteger('kpa_approved_by')->nullable()->after('kpa_approved_at');
            $table->text('kpa_approval_notes')->nullable()->after('kpa_approved_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dokumen_spp', function (Blueprint $table) {
            $table->dropColumn([
                'kpa_approval_status',
                'kpa_approved_at',
                'kpa_approved_by',
                'kpa_approval_notes'
            ]);
        });
    }
};
