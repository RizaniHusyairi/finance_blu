@extends('layouts.app')
@section('title', 'Pencatatan SP2D Honorarium')

@section('content')
@php
    use App\Models\DokumenSp2d;

    $rows = collect($viewNpis)->map(function ($npi) {
        $sp2d = $npi->sp2d;
        $st   = $npi->status_sp2d;

        $variant = match (true) {
            $st === 'SIAP_DIBUAT'                            => 'amber',
            $st === DokumenSp2d::STATUS_DRAFT                => 'slate',
            $st === DokumenSp2d::STATUS_REVISI               => 'rose',
            $st === DokumenSp2d::STATUS_MENUNGGU_VERIFIKASI  => 'cyan',
            in_array($st, [DokumenSp2d::STATUS_DISETUJUI_FINAL]) => 'green',
            $st === DokumenSp2d::STATUS_EXECUTED             => 'primary',
            default                                          => 'slate',
        };
        $label = match ($st) {
            'SIAP_DIBUAT'                              => 'Belum Dibuat',
            DokumenSp2d::STATUS_DRAFT                  => 'Draft',
            DokumenSp2d::STATUS_REVISI                 => 'Revisi',
            DokumenSp2d::STATUS_MENUNGGU_VERIFIKASI    => 'Menunggu Verifikasi',
            DokumenSp2d::STATUS_DISETUJUI_FINAL        => 'Disetujui Final',
            DokumenSp2d::STATUS_EXECUTED               => 'Selesai',
            default                                    => str_replace('_', ' ', $st),
        };
        $state = $st === 'SIAP_DIBUAT' ? 'create'
            : (in_array($st, [DokumenSp2d::STATUS_DRAFT, DokumenSp2d::STATUS_REVISI]) ? 'manage' : 'view');
        $showVerif = !in_array($st, ['SIAP_DIBUAT', DokumenSp2d::STATUS_DRAFT]);

        $approvals = collect($sp2d?->workflowInstances?->first()?->approvals ?? []);
        $jumlahPenerima = collect($npi->tagihanModel?->detailHonorarium)->count();

        return [
            'npi_id'         => $npi->id,
            'nomor_sp2d'     => $sp2d?->nomor_sp2d,
            'tanggal_sp2d'   => $sp2d?->tanggal_sp2d,
            'nomor_npi'      => $npi->nomor_npi,
            'nomor_spm'      => $npi->spmModel?->nomor_spm,
            'third_value'    => $npi->sppModel?->nomor_spp,
            'subject_title'  => $npi->tagihanModel?->deskripsi,
            'subject_sub'    => $jumlahPenerima . ' Penerima Honor',
            'nominal'        => $npi->nilai_npi,
            'status_label'   => $label,
            'status_variant' => $variant,
            'state'          => $state,
            'show_verif'     => $showVerif,
            'verif'          => [
                'PPK'   => $approvals->firstWhere('role_code', 'PPK')?->status,
                'KSB'   => $approvals->firstWhere('role_code', 'Kepala Subbagian Keuangan dan Tata Usaha')?->status,
                'PPSPM' => $approvals->firstWhere('role_code', 'PPSPM')?->status,
            ],
        ];
    });

    $stats = [
        ['label' => 'Belum Ada SP2D',      'value' => $summary['siap_dibuat'] ?? 0,                                      'icon' => 'note_add',      'color' => '#d97706', 'filter' => 'siap_dibuat'],
        ['label' => 'Draft / Revisi',      'value' => ($summary['draft'] ?? 0) + ($summary['revisi'] ?? 0),              'icon' => 'draw',          'color' => '#64748b', 'filter' => 'draft'],
        ['label' => 'Menunggu Verifikasi', 'value' => $summary['menunggu'] ?? 0,                                         'icon' => 'hourglass_top', 'color' => '#0891b2', 'filter' => 'menunggu'],
        ['label' => 'Selesai',             'value' => $summary['selesai'] ?? 0,                                          'icon' => 'verified',      'color' => '#059669', 'filter' => 'selesai'],
    ];

    $statusOptions = [
        'semua'       => 'Semua Status',
        'siap_dibuat' => 'Belum Dibuat',
        'draft'       => 'Draft',
        'revisi'      => 'Revisi',
        'menunggu'    => 'Menunggu Verifikasi',
        'selesai'     => 'Disetujui Final / Selesai',
    ];
@endphp

@include('sp2ds.partials.pencatatan-index', [
    'pageTitle'         => 'Honorarium',
    'pageSubtitle'      => 'Kelola pencairan SP2D untuk tagihan honorarium yang NPI-nya telah final ber-TTE.',
    'heroIcon'          => 'payments',
    'accent'            => '#4f46e5',
    'indexRoute'        => 'sp2ds.honor.index',
    'detailRoute'       => 'sp2ds.honor.detail',
    'searchPlaceholder' => 'Cari nomor NPI, SP2D, SPM, SPP, deskripsi...',
    'statusOptions'     => $statusOptions,
    'secondColLabel'    => 'Deskripsi / Penerima',
    'thirdLabel'        => 'SPP',
    'stats'             => $stats,
    'rows'              => $rows,
])
@endsection
