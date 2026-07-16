<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Aksi publik via magic link (TTE vendor/tim pemeriksa) tidak punya user
 * login — log timeline mencatatnya dengan user_id null dan aktor disimpan
 * pada role_saat_itu ('Vendor' / 'Tim Pemeriksa').
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('log_status_dokumen', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('log_status_dokumen', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable(false)->change();
        });
    }
};
