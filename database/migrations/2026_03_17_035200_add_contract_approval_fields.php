<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add contract_id to approval_logs so it can track contract approvals
        Schema::table('approval_logs', function (Blueprint $table) {
            $table->foreignId('contract_id')->nullable()->after('transaction_id')->constrained()->cascadeOnDelete();
            // Make transaction_id nullable (was required before, now either transaction_id or contract_id is set)
            $table->foreignId('transaction_id')->nullable()->change();
        });

        // Add submitted_by to contracts
        Schema::table('contracts', function (Blueprint $table) {
            $table->foreignId('submitted_by')->nullable()->after('status')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('approval_logs', function (Blueprint $table) {
            $table->dropForeign(['contract_id']);
            $table->dropColumn('contract_id');
        });

        Schema::table('contracts', function (Blueprint $table) {
            $table->dropForeign(['submitted_by']);
            $table->dropColumn('submitted_by');
        });
    }
};
