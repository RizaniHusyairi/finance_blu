<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('integration_settings', function (Blueprint $table) {
            $table->id();
            $table->string('group', 50)->index();
            $table->string('key')->unique();
            $table->string('label')->nullable();
            $table->string('type', 30)->default('text');
            $table->text('value')->nullable();
            $table->boolean('is_encrypted')->default(false);
            $table->boolean('is_active')->default(true);
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('integration_logs', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 50)->index();
            $table->string('action', 80)->index();
            $table->string('direction', 20)->default('outbound');
            $table->string('status', 30)->default('pending')->index();
            $table->string('endpoint')->nullable();
            $table->unsignedSmallInteger('status_code')->nullable();
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->json('request_payload')->nullable();
            $table->json('response_payload')->nullable();
            $table->text('message')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tagihan_jasa_id')->nullable()->constrained('tagihan_jasas')->nullOnDelete();
            $table->string('provider', 50)->default('btn');
            $table->string('external_reference')->nullable()->index();
            $table->string('virtual_account')->nullable()->index();
            $table->decimal('amount', 20, 2)->default(0);
            $table->string('status', 30)->default('pending')->index();
            $table->string('payment_channel')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->unique(['provider', 'external_reference']);
        });

        Schema::create('whatsapp_notification_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tagihan_jasa_id')->nullable()->constrained('tagihan_jasas')->nullOnDelete();
            $table->string('provider', 50)->default('fonnte');
            $table->string('target', 40);
            $table->text('message');
            $table->string('status', 30)->default('pending')->index();
            $table->json('response_payload')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });

        Schema::table('tagihan_jasas', function (Blueprint $table) {
            if (! Schema::hasColumn('tagihan_jasas', 'va_provider')) {
                $table->string('va_provider', 50)->nullable()->after('nomor_va');
            }
            if (! Schema::hasColumn('tagihan_jasas', 'va_reference')) {
                $table->string('va_reference')->nullable()->after('va_provider');
            }
            if (! Schema::hasColumn('tagihan_jasas', 'va_expired_at')) {
                $table->timestamp('va_expired_at')->nullable()->after('va_reference');
            }
            if (! Schema::hasColumn('tagihan_jasas', 'payment_reference')) {
                $table->string('payment_reference')->nullable()->after('sisa_tagihan');
            }
            if (! Schema::hasColumn('tagihan_jasas', 'payment_channel')) {
                $table->string('payment_channel')->nullable()->after('payment_reference');
            }
            if (! Schema::hasColumn('tagihan_jasas', 'paid_at')) {
                $table->timestamp('paid_at')->nullable()->after('payment_channel');
            }
            if (! Schema::hasColumn('tagihan_jasas', 'last_payment_sync_at')) {
                $table->timestamp('last_payment_sync_at')->nullable()->after('paid_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tagihan_jasas', function (Blueprint $table) {
            foreach ([
                'last_payment_sync_at',
                'paid_at',
                'payment_channel',
                'payment_reference',
                'va_expired_at',
                'va_reference',
                'va_provider',
            ] as $column) {
                if (Schema::hasColumn('tagihan_jasas', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::dropIfExists('whatsapp_notification_logs');
        Schema::dropIfExists('payment_transactions');
        Schema::dropIfExists('integration_logs');
        Schema::dropIfExists('integration_settings');
    }
};
