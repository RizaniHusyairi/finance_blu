<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkflowApproval extends Model
{
    protected $table = 'workflow_approvals';
    protected $guarded = ['id'];

    protected $casts = [
        'acted_at' => 'datetime',
    ];

    public function instance()
    {
        return $this->belongsTo(WorkflowInstance::class, 'workflow_instance_id');
    }

    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function actedByUser()
    {
        return $this->belongsTo(User::class, 'acted_by_user_id');
    }
}
