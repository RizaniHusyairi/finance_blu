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
        if (DB::getDriverName() === 'sqlite') {
            // SQLite menyimpan enum sebagai kolom ber-CHECK constraint yang tidak
            // bisa di-ALTER; rebuild kolom menjadi string agar nilai baru 'DRAFT'
            // diterima (paritas dengan enum MySQL di bawah — nilai tetap dijaga
            // oleh kode aplikasi).
            Schema::table('kontrak_termin', function (Blueprint $table) {
                $table->string('status_termin')->default('LOCKED')->change();
            });

            return;
        }

        DB::statement("ALTER TABLE kontrak_termin MODIFY COLUMN status_termin ENUM('LOCKED', 'READY_TO_BILL', 'DRAFT', 'SUDAH_DITAGIH') DEFAULT 'LOCKED'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("ALTER TABLE kontrak_termin MODIFY COLUMN status_termin ENUM('LOCKED', 'READY_TO_BILL', 'SUDAH_DITAGIH') DEFAULT 'LOCKED'");
    }
};
