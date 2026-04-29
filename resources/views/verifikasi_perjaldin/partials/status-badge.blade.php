{{-- status-badge.blade.php --}}
{{-- Usage: @include('verifikasi_perjaldin.partials.status-badge', ['status' => $tagihan->status]) --}}
@php
    $map = [
        'DRAFT'               => ['class' => 'bg-secondary',          'icon' => 'bi-circle',                  'label' => 'Draft'],
        'PENDING_VERIFIKASI_PERJALDIN' => ['class' => 'bg-primary',    'icon' => 'bi-hourglass-split',         'label' => 'Menunggu Verifikator'],
        'PENDING_PPK'         => ['class' => 'bg-primary',            'icon' => 'bi-hourglass-split',         'label' => 'Menunggu PPK'],
        'PENDING_PPSPM'       => ['class' => 'bg-primary',            'icon' => 'bi-hourglass-split',         'label' => 'Menunggu PPSPM'],
        'REVISI_PPK'          => ['class' => 'bg-warning text-dark',  'icon' => 'bi-arrow-counterclockwise',  'label' => 'Revisi PPK'],
        'REVISI_PPSPM'        => ['class' => 'bg-warning text-dark',  'icon' => 'bi-arrow-counterclockwise',  'label' => 'Revisi PPSPM'],
        'DITOLAK_PPK'         => ['class' => 'bg-danger',             'icon' => 'bi-x-octagon',               'label' => 'Ditolak PPK'],
        'DITOLAK_PPSPM'       => ['class' => 'bg-danger',             'icon' => 'bi-x-octagon',               'label' => 'Ditolak PPSPM'],
        'PENDING_BENDAHARA'   => ['class' => 'bg-info text-dark',     'icon' => 'bi-hourglass-split',         'label' => 'Menunggu Bendahara'],
        'PENDING_BENDAHARA_PENERIMAAN' => ['class' => 'bg-info text-dark', 'icon' => 'bi-hourglass-split',     'label' => 'Menunggu Ben. Penerimaan'],
        'PENDING_BENDAHARA_PENGELUARAN' => ['class' => 'bg-info text-dark', 'icon' => 'bi-hourglass-split',    'label' => 'Menunggu Ben. Pengeluaran'],
        'REVISI_BENDAHARA'    => ['class' => 'bg-warning text-dark',  'icon' => 'bi-arrow-counterclockwise',  'label' => 'Revisi Bendahara'],
        'REVISI_BENDAHARA_PENERIMAAN' => ['class' => 'bg-warning text-dark', 'icon' => 'bi-arrow-counterclockwise', 'label' => 'Revisi Ben. Penerimaan'],
        'REVISI_BENDAHARA_PENGELUARAN' => ['class' => 'bg-warning text-dark', 'icon' => 'bi-arrow-counterclockwise', 'label' => 'Revisi Ben. Pengeluaran'],
        'DITOLAK_BENDAHARA'   => ['class' => 'bg-danger',             'icon' => 'bi-x-octagon',               'label' => 'Ditolak Bendahara'],
        'DITOLAK_BENDAHARA_PENERIMAAN' => ['class' => 'bg-danger',     'icon' => 'bi-x-octagon',               'label' => 'Ditolak Ben. Penerimaan'],
        'DITOLAK_BENDAHARA_PENGELUARAN' => ['class' => 'bg-danger',    'icon' => 'bi-x-octagon',               'label' => 'Ditolak Ben. Pengeluaran'],
        'PENDING_KASUBBAG'    => ['class' => 'bg-info text-dark',      'icon' => 'bi-hourglass-split',         'label' => 'Menunggu Kasubbag'],
        'REVISI_KASUBBAG'     => ['class' => 'bg-warning text-dark',   'icon' => 'bi-arrow-counterclockwise',  'label' => 'Revisi Kasubbag'],
        'DITOLAK_KASUBBAG'    => ['class' => 'bg-danger',             'icon' => 'bi-x-octagon',               'label' => 'Ditolak Kasubbag'],
        'DISETUJUI_PERJALDIN' => ['class' => 'bg-success',            'icon' => 'bi-check-circle-fill',       'label' => 'Disetujui'],
        // === Tagihan Kontrak ===
        'PENDING_VERIFIKASI_KONTRAK'      => ['class' => 'bg-warning text-dark', 'icon' => 'bi-people-fill',           'label' => 'Verifikasi Paralel'],
        'PENDING_KOORDINATOR_KEUANGAN'    => ['class' => 'bg-primary',           'icon' => 'bi-hourglass-split',       'label' => 'Menunggu Koor.Keuangan'],
        'REVISI_KOORDINATOR_KEUANGAN'     => ['class' => 'bg-warning text-dark', 'icon' => 'bi-arrow-counterclockwise','label' => 'Revisi Koor.Keuangan'],
        'DITOLAK_KOORDINATOR_KEUANGAN'    => ['class' => 'bg-danger',            'icon' => 'bi-x-octagon',             'label' => 'Ditolak Koor.Keuangan'],
        'READY_FOR_SPP'                   => ['class' => 'bg-success',           'icon' => 'bi-check-circle-fill',     'label' => 'Disetujui · Siap SPP'],
        'DISETUJUI_KONTRAK'               => ['class' => 'bg-success',           'icon' => 'bi-check-circle-fill',     'label' => 'Disetujui'],
    ];
    $s = $map[$status] ?? ['class' => 'bg-secondary', 'icon' => 'bi-question-circle', 'label' => $status];
    $size = $size ?? '';
@endphp
<span class="badge {{ $s['class'] }} {{ $size }} rounded-pill px-3 py-2">
    <i class="bi {{ $s['icon'] }} me-1"></i>{{ $s['label'] }}
</span>
