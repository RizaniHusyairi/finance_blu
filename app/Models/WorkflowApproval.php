<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkflowApproval extends Model
{
    protected $table = 'workflow_approvals';
    protected $guarded = ['id'];

    protected $casts = [
        'acted_at' => 'datetime',
        'revisi_target' => 'array',
    ];

    /**
     * Label tampilan untuk setiap kunci bagian revisi.
     */
    public const REVISI_TARGET_LABELS = [
        'rincian' => 'Rincian / Nominal Tagihan',
        'mitra_dokumen' => 'Mitra / Dokumen Dasar',
        'surat_pengantar' => 'Surat Pengantar',
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
