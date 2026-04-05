<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tagihan')) {
            return;
        }

        Schema::table('tagihan', function (Blueprint $table) {
            if (! Schema::hasColumn('tagihan', 'dipa_revision_item_id')) {
                $table->foreignId('dipa_revision_item_id')
                    ->nullable()
                    ->after('master_dipa_id')
                    ->constrained('dipa_revision_items')
                    ->restrictOnDelete();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('tagihan') || ! Schema::hasColumn('tagihan', 'dipa_revision_item_id')) {
            return;
        }

        Schema::table('tagihan', function (Blueprint $table) {
            $table->dropConstrainedForeignId('dipa_revision_item_id');
        });
    }
};
