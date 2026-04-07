<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkflowDefinitionStep extends Model
{
    protected $table = 'workflow_definition_steps';
    protected $guarded = ['id'];

    protected $casts = [
        'is_required' => 'boolean',
        'can_reject' => 'boolean',
        'can_request_revision' => 'boolean',
    ];

    public function definition()
    {
        return $this->belongsTo(WorkflowDefinition::class, 'workflow_definition_id');
    }
}
