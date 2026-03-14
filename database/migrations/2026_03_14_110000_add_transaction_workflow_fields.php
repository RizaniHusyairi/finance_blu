<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            if (! Schema::hasColumn('transactions', 'transaction_number')) {
                $table->string('transaction_number')->nullable()->unique()->after('id');
            }

            if (! Schema::hasColumn('transactions', 'date')) {
                $table->date('date')->nullable()->after('transaction_number');
            }

            if (! Schema::hasColumn('transactions', 'term_id')) {
                $table->foreignId('term_id')->nullable()->after('contract_id')->constrained('contract_terms')->nullOnDelete();
            }
        });

        DB::table('transactions')
            ->whereNull('transaction_number')
            ->orderBy('id')
            ->get()
            ->each(function ($transaction) {
                DB::table('transactions')
                    ->where('id', $transaction->id)
                    ->update([
                        'transaction_number' => 'TRX-LEGACY-' . str_pad((string) $transaction->id, 6, '0', STR_PAD_LEFT),
                        'date' => $transaction->date ?? now()->toDateString(),
                    ]);
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            if (Schema::hasColumn('transactions', 'term_id')) {
                $table->dropConstrainedForeignId('term_id');
            }

            if (Schema::hasColumn('transactions', 'date')) {
                $table->dropColumn('date');
            }

            if (Schema::hasColumn('transactions', 'transaction_number')) {
                $table->dropUnique(['transaction_number']);
                $table->dropColumn('transaction_number');
            }
        });
    }
};
