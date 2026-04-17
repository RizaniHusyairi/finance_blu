<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE workflow_approvals MODIFY COLUMN status ENUM('WAITING','PENDING','APPROVED','REJECTED','REVISION') NOT NULL DEFAULT 'PENDING'");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("UPDATE workflow_approvals SET status = 'PENDING' WHERE status = 'WAITING'");
        DB::statement("ALTER TABLE workflow_approvals MODIFY COLUMN status ENUM('PENDING','APPROVED','REJECTED','REVISION') NOT NULL DEFAULT 'PENDING'");
    }
};
