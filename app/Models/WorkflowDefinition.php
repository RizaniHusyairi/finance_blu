<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkflowDefinition extends Model
{
    protected $table = 'workflow_definitions';
    protected $guarded = ['id'];

    protected $casts = [
        'status_aktif' => 'boolean',
    ];

    public function steps()
    {
        return $this->hasMany(WorkflowDefinitionStep::class)->orderBy('urutan_step');
    }

    public function instances()
    {
        return $this->hasMany(WorkflowInstance::class);
    }
}
