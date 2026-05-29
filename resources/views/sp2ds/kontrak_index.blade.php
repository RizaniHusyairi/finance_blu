@extends('layouts.app')
@section('title', 'Pencatatan SP2D Kontrak')

@section('content')
@php
    use App\Models\DokumenSp2d;

    $rows = collect($listSp2d)->map(function ($item) {
        $raw = $item->raw_status;
        $variant = match (true) {
            $item->status_badge === 'Belum Dibuat'          => 'amber',
            $raw === DokumenSp2d::STATUS_DRAFT               => 'slate',
            $raw === DokumenSp2d::STATUS_REVISI              => 'rose',
            $raw === DokumenSp2d::STATUS_MENUNGGU_VERIFIKASI => 'cyan',
            in_array($raw, [DokumenSp2d::STATUS_DISETUJUI_FINAL, DokumenSp2d::STATUS_SP2D_TERBIT]) => 'green',
            $raw === DokumenSp2d::STATUS_EXECUTED            => 'primary',
            default                                          => 'slate',
        };
        $state = $item->status_badge === 'Belum Dibuat' ? 'create' : ($item->is_draft ? 'manage' : 'view');
        $showVerif = !in_array($item->status_badge, ['Belum Dibuat', 'Draft']);

        return [
            'npi_id'         => $item->npi_id,
            'nomor_sp2d'     => $item->nomor_sp2d,
            'tanggal_sp2d'   => $item->tanggal_sp2d,
            'nomor_npi'      => $item->nomor_npi,
            'nomor_spm'      => $item->nomor_spm,
            'third_value'    => $item->nomor_spk,
            'subject_title'  => $item->nama_vendor,
            'subject_sub'    => $item->nama_pekerjaan,
            'nominal'        => $item->nominal,
            'status_label'   => $item->status_badge,
            'status_variant' => $variant,
            'state'          => $state,
            'show_verif'     => $showVerif,
            'verif'          => [
                'PPK'   => $item->ppk_status,
                'KSB'   => $item->kasubbag_status,
                'PPSPM' => $item->ppspm_status,
            ],
        ];
    });

    $stats = [
        ['label' => 'Belum Ada SP2D',      'value' => $summary['belum_dibuat'], 'icon' => 'note_add',       'color' => '#d97706', 'filter' => 'belum_dibuat'],
        ['label' => 'Draft / Revisi',      'value' => $summary['draft_revisi'], 'icon' => 'draw',           'color' => '#64748b', 'filter' => 'draft'],
        ['label' => 'Menunggu Verifikasi', 'value' => $summary['menunggu'],     'icon' => 'hourglass_top',  'color' => '#0891b2', 'filter' => 'menunggu'],
        ['label' => 'Selesai',             'value' => $summary['selesai'],      'icon' => 'verified',       'color' => '#059669', 'filter' => 'selesai'],
    ];

    $statusOptions = [
        'semua'        => 'Semua Status',
        'belum_dibuat' => 'Belum Dibuat',
        'draft'        => 'Draft',
        'revisi'       => 'Revisi',
        'menunggu'     => 'Menunggu Verifikasi',
        'selesai'      => 'Disetujui Final / Selesai',
    ];
@endphp

@include('sp2ds.partials.pencatatan-index', [
    'pageTitle'         => 'Kontrak',
    'pageSubtitle'      => 'Kelola pencairan SP2D untuk tagihan kontrak yang NPI-nya telah final ber-TTE.',
    'heroIcon'          => 'description',
    'accent'            => '#4f46e5',
    'indexRoute'        => 'sp2ds.kontrak.index',
    'detailRoute'       => 'sp2ds.kontrak.detail',
    'searchPlaceholder' => 'Cari nomor NPI, SP2D, SPM, SPK, vendor...',
    'statusOptions'     => $statusOptions,
    'secondColLabel'    => 'Vendor / Pekerjaan',
    'thirdLabel'        => 'SPK',
    'stats'             => $stats,
    'rows'              => $rows,
])
@endsection
