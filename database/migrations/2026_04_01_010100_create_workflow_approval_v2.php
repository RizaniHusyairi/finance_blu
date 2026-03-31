<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('workflow_definitions')) {
            Schema::create('workflow_definitions', function (Blueprint $table) {
                $table->id();
                $table->string('kode', 50)->unique();
                $table->string('nama', 150);
                $table->string('target_type', 100);
                $table->boolean('status_aktif')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('workflow_definition_steps')) {
            Schema::create('workflow_definition_steps', function (Blueprint $table) {
                $table->id();
                $table->foreignId('workflow_definition_id')->constrained('workflow_definitions')->cascadeOnDelete();
                $table->unsignedInteger('urutan_step');
                $table->string('nama_step', 100);
                $table->string('role_code', 100);
                $table->boolean('is_required')->default(true);
                $table->boolean('can_reject')->default(true);
                $table->boolean('can_request_revision')->default(true);
                $table->timestamps();

                $table->unique(['workflow_definition_id', 'urutan_step'], 'wf_def_steps_defid_urut_unq');
            });
        }

        if (!Schema::hasTable('workflow_instances')) {
            Schema::create('workflow_instances', function (Blueprint $table) {
                $table->id();
                $table->foreignId('workflow_definition_id')->constrained('workflow_definitions')->restrictOnDelete();
                $table->morphs('workflowable');
                $table->unsignedInteger('step_saat_ini')->default(1);
                $table->enum('status', ['DRAFT', 'IN_PROGRESS', 'APPROVED', 'REJECTED', 'REVISION'])->default('DRAFT');
                $table->timestamps();

                $table->index(['status', 'step_saat_ini'], 'wf_instances_status_step_idx');
            });
        }

        if (!Schema::hasTable('workflow_approvals')) {
            Schema::create('workflow_approvals', function (Blueprint $table) {
                $table->id();
                $table->foreignId('workflow_instance_id')->constrained('workflow_instances')->cascadeOnDelete();
                $table->unsignedInteger('urutan_step');
                $table->string('nama_step', 100);
                $table->string('role_code', 100);
                $table->foreignId('assigned_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('acted_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->enum('status', ['PENDING', 'APPROVED', 'REJECTED', 'REVISION'])->default('PENDING');
                $table->text('catatan')->nullable();
                $table->dateTime('acted_at')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->timestamps();

                $table->index(['workflow_instance_id', 'urutan_step'], 'wf_approvals_instance_step_idx');
                $table->index(['assigned_user_id', 'status'], 'wf_approvals_assignee_status_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_approvals');
        Schema::dropIfExists('workflow_instances');
        Schema::dropIfExists('workflow_definition_steps');
        Schema::dropIfExists('workflow_definitions');
    }
};
