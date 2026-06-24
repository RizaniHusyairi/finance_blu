@php
    $statusCls = match($kontrak->status_kontrak) {
        'AKTIF' => 'status-aktif',
        'SELESAI' => 'status-selesai',
        'DRAFT' => 'status-draft',
        'DIBATALKAN' => 'status-dibatalkan',
        'PENDING_REVIEW' => 'status-pending',
        'REVISI' => 'status-pending',
        default => 'status-draft',
    };
@endphp
<span class="status-pill {{ $statusCls }}">{{ str_replace('_', ' ', $kontrak->status_kontrak) }}</span>
@if($kontrak->status_kontrak === 'REVISI' && $kontrak->ppk_catatan)
    <div class="timeline-info text-danger mt-1" title="{{ $kontrak->ppk_catatan }}">
        <i class="bi bi-chat-left-text"></i> PPK: {{ \Illuminate\Support\Str::limit($kontrak->ppk_catatan, 45) }}
    </div>
@endif
