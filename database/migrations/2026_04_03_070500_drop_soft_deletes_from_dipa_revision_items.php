<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('dipa_revision_items') && Schema::hasColumn('dipa_revision_items', 'deleted_at')) {
            Schema::table('dipa_revision_items', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('dipa_revision_items') && ! Schema::hasColumn('dipa_revision_items', 'deleted_at')) {
            Schema::table('dipa_revision_items', function (Blueprint $table) {
                $table->softDeletes();
            });
        }
    }
};
