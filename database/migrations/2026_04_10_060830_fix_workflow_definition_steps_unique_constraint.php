<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('workflow_definition_steps', function (Blueprint $table) {
            // Create index for foreign key first so MySQL doesn't complain
            $table->index('workflow_definition_id', 'wf_def_steps_def_id_idx');
            $table->dropUnique('wf_def_steps_defid_urut_unq');
            $table->unique(['workflow_definition_id', 'urutan_step', 'role_code'], 'wf_def_steps_defid_urut_role_unq');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('workflow_definition_steps', function (Blueprint $table) {
            $table->dropUnique('wf_def_steps_defid_urut_role_unq');
            $table->unique(['workflow_definition_id', 'urutan_step'], 'wf_def_steps_defid_urut_unq');
            $table->dropIndex('wf_def_steps_def_id_idx');
        });
    }
};

