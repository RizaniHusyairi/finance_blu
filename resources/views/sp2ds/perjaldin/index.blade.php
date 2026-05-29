@extends('layouts.app')
@section('title', 'Pencatatan SP2D Perjalanan Dinas')

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
            $st === DokumenSp2d::STATUS_DISETUJUI_FINAL      => 'green',
            $st === DokumenSp2d::STATUS_EXECUTED             => 'primary',
            default                                          => 'slate',
        };
        $label = match ($st) {
            'SIAP_DIBUAT'                              => 'Belum Dibuat',
            DokumenSp2d::STATUS_DRAFT                  => 'Draft SP2D',
            DokumenSp2d::STATUS_REVISI                 => 'Revisi',
            DokumenSp2d::STATUS_MENUNGGU_VERIFIKASI    => 'Menunggu Verifikasi',
            DokumenSp2d::STATUS_DISETUJUI_FINAL        => 'Disetujui Final',
            DokumenSp2d::STATUS_EXECUTED               => 'Telah Dibayar (Lunas)',
            default                                    => str_replace('_', ' ', $st),
        };
        $state = $st === 'SIAP_DIBUAT' ? 'create'
            : (in_array($st, [DokumenSp2d::STATUS_DRAFT, DokumenSp2d::STATUS_REVISI]) ? 'manage' : 'view');
        $showVerif = !in_array($st, ['SIAP_DIBUAT', DokumenSp2d::STATUS_DRAFT]);

        $approvals = collect(optional($sp2d?->workflowInstances)->first()?->approvals ?? []);
        $jumlahPeserta = collect($npi->tagihanModel?->detailPerjaldin)->count();

        return [
            'npi_id'         => $npi->id,
            'nomor_sp2d'     => $sp2d?->nomor_sp2d,
            'tanggal_sp2d'   => $sp2d?->tanggal_sp2d,
            'nomor_npi'      => $npi->nomor_npi,
            'nomor_spm'      => $npi->spmModel?->nomor_spm,
            'third_value'    => $npi->sppModel?->nomor_spp,
            'subject_title'  => $npi->tagihanModel?->deskripsi,
            'subject_sub'    => $jumlahPeserta . ' Peserta Perjalanan',
            'nominal'        => $npi->nilai_npi,
            'status_label'   => $label,
            'status_variant' => $variant,
            'state'          => $state,
            'show_verif'     => $showVerif && $approvals->isNotEmpty(),
            'verif'          => [
                'PPK'   => $approvals->firstWhere('role_code', 'PPK')?->status,
                'KSB'   => $approvals->firstWhere('role_code', 'Kepala Subbagian Keuangan dan Tata Usaha')?->status,
                'PPSPM' => $approvals->firstWhere('role_code', 'PPSPM')?->status,
            ],
        ];
    });

    $stats = [
        ['label' => 'Siap Dibuat',         'value' => $summary['siap_dibuat'] ?? 0, 'icon' => 'note_add',      'color' => '#d97706', 'filter' => 'siap_dibuat'],
        ['label' => 'Draft / Revisi',      'value' => ($summary['draft'] ?? 0) + ($summary['revisi'] ?? 0), 'icon' => 'draw', 'color' => '#64748b', 'filter' => 'draft'],
        ['label' => 'Menunggu Verifikasi', 'value' => $summary['menunggu'] ?? 0,    'icon' => 'hourglass_top', 'color' => '#0891b2', 'filter' => 'menunggu'],
        ['label' => 'Selesai / Final',     'value' => $summary['selesai'] ?? 0,     'icon' => 'verified',      'color' => '#059669', 'filter' => 'selesai'],
    ];

    $statusOptions = [
        'semua'       => 'Semua Status',
        'siap_dibuat' => 'Siap Dibuat (Belum Ada Draft)',
        'draft'       => 'Draft SP2D',
        'revisi'      => 'Revisi',
        'menunggu'    => 'Menunggu Verifikasi',
        'selesai'     => 'Selesai / Final',
    ];
@endphp

@include('sp2ds.partials.pencatatan-index', [
    'pageTitle'         => 'Perjalanan Dinas',
    'pageSubtitle'      => 'Kelola pencairan SP2D untuk tagihan perjalanan dinas yang NPI-nya telah final ber-TTE.',
    'heroIcon'          => 'flight_takeoff',
    'accent'            => '#4f46e5',
    'indexRoute'        => 'sp2ds.perjaldin.index',
    'detailRoute'       => 'sp2ds.perjaldin.detail',
    'searchPlaceholder' => 'Cari nomor NPI, SP2D, SPM, SPP, uraian...',
    'statusOptions'     => $statusOptions,
    'secondColLabel'    => 'Uraian / Peserta',
    'thirdLabel'        => 'SPP',
    'stats'             => $stats,
    'rows'              => $rows,
])
@endsection
