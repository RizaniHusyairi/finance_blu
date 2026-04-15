{{-- status-badge.blade.php --}}
{{-- Usage: @include('verifikasi_perjaldin.partials.status-badge', ['status' => $tagihan->status]) --}}
@php
    $map = [
        'DRAFT'               => ['class' => 'bg-secondary',          'icon' => 'bi-circle',                  'label' => 'Draft'],
        'PENDING_PPK'         => ['class' => 'bg-primary',            'icon' => 'bi-hourglass-split',         'label' => 'Menunggu PPK'],
        'REVISI_PPK'          => ['class' => 'bg-warning text-dark',  'icon' => 'bi-arrow-counterclockwise',  'label' => 'Revisi PPK'],
        'DITOLAK_PPK'         => ['class' => 'bg-danger',             'icon' => 'bi-x-octagon',               'label' => 'Ditolak PPK'],
        'PENDING_BENDAHARA'   => ['class' => 'bg-info text-dark',     'icon' => 'bi-hourglass-split',         'label' => 'Menunggu Bendahara'],
        'REVISI_BENDAHARA'    => ['class' => 'bg-warning text-dark',  'icon' => 'bi-arrow-counterclockwise',  'label' => 'Revisi Bendahara'],
        'DITOLAK_BENDAHARA'   => ['class' => 'bg-danger',             'icon' => 'bi-x-octagon',               'label' => 'Ditolak Bendahara'],
        'DISETUJUI_PERJALDIN' => ['class' => 'bg-success',            'icon' => 'bi-check-circle-fill',       'label' => 'Disetujui'],
    ];
    $s = $map[$status] ?? ['class' => 'bg-secondary', 'icon' => 'bi-question-circle', 'label' => $status];
    $size = $size ?? '';
@endphp
<span class="badge {{ $s['class'] }} {{ $size }} rounded-pill px-3 py-2">
    <i class="bi {{ $s['icon'] }} me-1"></i>{{ $s['label'] }}
</span>
