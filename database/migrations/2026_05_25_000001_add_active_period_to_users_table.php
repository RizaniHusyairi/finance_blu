<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        if (! Schema::hasColumn('users', 'is_active')) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('is_active')->default(true)->after('password')->index();
            });
        }

        if (! Schema::hasColumn('users', 'active_from')) {
            Schema::table('users', function (Blueprint $table) {
                $table->date('active_from')->nullable()->after('is_active');
            });
        }

        if (! Schema::hasColumn('users', 'active_until')) {
            Schema::table('users', function (Blueprint $table) {
                $table->date('active_until')->nullable()->after('active_from')->index();
            });
        }

        if (! Schema::hasColumn('users', 'disabled_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->timestamp('disabled_at')->nullable()->after('active_until');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        if (Schema::hasColumn('users', 'disabled_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('disabled_at');
            });
        }

        if (Schema::hasColumn('users', 'active_until')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropIndex(['active_until']);
                $table->dropColumn('active_until');
            });
        }

        if (Schema::hasColumn('users', 'active_from')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('active_from');
            });
        }

        if (Schema::hasColumn('users', 'is_active')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropIndex(['is_active']);
                $table->dropColumn('is_active');
            });
        }
    }
};
