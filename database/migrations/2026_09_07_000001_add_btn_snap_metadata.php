<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tagihan_jasas', function (Blueprint $table) {
            $table->json('btn_va_data')->nullable();
        });
        Schema::table('payment_transactions', function (Blueprint $table) {
            $table->json('provider_response')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('tagihan_jasas', fn (Blueprint $table) => $table->dropColumn('btn_va_data'));
        Schema::table('payment_transactions', fn (Blueprint $table) => $table->dropColumn('provider_response'));
    }
};
