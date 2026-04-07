<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkflowInstance extends Model
{
    protected $table = 'workflow_instances';
    protected $guarded = ['id'];

    public function definition()
    {
        return $this->belongsTo(WorkflowDefinition::class, 'workflow_definition_id');
    }

    public function workflowable()
    {
        return $this->morphTo();
    }

    public function approvals()
    {
        return $this->hasMany(WorkflowApproval::class)->orderBy('urutan_step');
    }

    public function currentApproval()
    {
        return $this->hasOne(WorkflowApproval::class)
            ->where('urutan_step', $this->step_saat_ini)
            ->latestOfMany('id');
    }
}
