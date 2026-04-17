<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
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
