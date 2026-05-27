{{-- partial: verifikator-info.blade.php --}}
@php
    $workflow = collect($tagihan->workflowInstances ?? [])->sortByDesc('created_at')->first();
    $approvals = collect($workflow?->approvals ?? []);

    $getVerifierInfo = function($roleCodes, $namaSnapshot) use ($approvals) {
        $roleCodes = (array) $roleCodes;
        $approval = $approvals->first(fn($app) => in_array($app->role_code, $roleCodes));

        $statusText = 'Belum Verifikasi';
        $statusClass = 'vs-empty';
        $statusIcon = 'bi-circle';

        if ($approval) {
            switch ($approval->status) {
                case 'APPROVED': $statusText = 'Disetujui';  $statusClass = 'vs-approved'; $statusIcon = 'bi-check-circle-fill'; break;
                case 'REVISION': $statusText = 'Revisi';     $statusClass = 'vs-revision'; $statusIcon = 'bi-arrow-counterclockwise'; break;
                case 'REJECTED': $statusText = 'Ditolak';    $statusClass = 'vs-rejected'; $statusIcon = 'bi-x-circle-fill'; break;
                case 'PENDING':  $statusText = 'Menunggu';   $statusClass = 'vs-pending';  $statusIcon = 'bi-hourglass-split'; break;
            }
        }

        if (empty($namaSnapshot)) {
            if ($approval && $approval->actedByUser) {
                $namaSnapshot = $approval->actedByUser->name;
            } else {
                $spatieRole = match($roleCodes[0]) {
                    'PPSPM' => 'PPSPM',
                    'BENDAHARA_PENERIMAAN' => 'Bendahara Penerimaan',
                    'BENDAHARA_PENGELUARAN' => 'Bendahara Pengeluaran',
                    'PPK' => 'PPK',
                    'KASUBBAG' => 'Kepala Subbagian Keuangan dan Tata Usaha',
                    'KOORDINATOR_KEUANGAN' => 'Koordinator Keuangan',
                    default => $roleCodes[0]
                };
                $fallbackUser = \App\Models\User::role($spatieRole)->orderBy('name')->first();
                $namaSnapshot = $fallbackUser ? $fallbackUser->name : '-';
            }
        }

        return [
            'nama' => $namaSnapshot ?: '-',
            'status' => $statusText,
            'status_class' => $statusClass,
            'status_icon' => $statusIcon,
            'acted_at' => $approval?->acted_at,
        ];
    };

    $initials = function ($name) {
        $name = trim((string) $name);
        if ($name === '' || $name === '-') return '?';
        $parts = preg_split('/\s+/', $name);
        $first = mb_substr($parts[0] ?? '', 0, 1);
        $last  = count($parts) > 1 ? mb_substr(end($parts), 0, 1) : '';
        return mb_strtoupper($first . $last);
    };

    $verifikators = [
        ['short' => 'PPK', 'label' => 'PPK', 'color' => 'vk-primary', 'data' => $getVerifierInfo('PPK', $tagihan->ppk_nama_snapshot)],
        ['short' => 'PPSPM', 'label' => 'PPSPM', 'color' => 'vk-success', 'data' => $getVerifierInfo('PPSPM', $tagihan->ppspm_nama_snapshot)],
        ['short' => 'Koor. Keu', 'label' => 'Koordinator Keuangan', 'color' => 'vk-warning', 'data' => $getVerifierInfo(['KOORDINATOR_KEUANGAN', 'Koordinator Keuangan'], $tagihan->koordinator_keuangan_nama_snapshot)],
        ['short' => 'Bend. Penerimaan', 'label' => 'Bendahara Penerimaan', 'color' => 'vk-info', 'data' => $getVerifierInfo('BENDAHARA_PENERIMAAN', $tagihan->bendahara_penerimaan_nama_snapshot)],
        ['short' => 'Bend. Pengeluaran', 'label' => 'Bendahara Pengeluaran', 'color' => 'vk-danger', 'data' => $getVerifierInfo('BENDAHARA_PENGELUARAN', $tagihan->bendahara_pengeluaran_nama_snapshot)],
        ['short' => 'Kasubbag', 'label' => 'Kepala Subbagian Keu & TU', 'color' => 'vk-violet', 'data' => $getVerifierInfo(['KASUBBAG', 'Kepala Subbagian Keuangan dan Tata Usaha'], $tagihan->kasubbag_nama_snapshot)],
    ];

    $totalApproved = collect($verifikators)->where('data.status', 'Disetujui')->count();
@endphp

<div class="modern-card">
    <div class="mc-head mc-icon-primary">
        <div class="mc-head-left">
            <div class="mc-icon"><i class="bi bi-people-fill"></i></div>
            <div>
                <h6 class="mc-title">Informasi Verifikator</h6>
                <p class="mc-sub">Pejabat yang akan memverifikasi & menandatangani dokumen</p>
            </div>
        </div>
        <span class="mc-pill mc-pill-success">
            <i class="bi bi-check-circle-fill"></i> {{ $totalApproved }}/{{ count($verifikators) }} Disetujui
        </span>
    </div>
    <div class="mc-body">
        <div class="vk-grid">
            @foreach($verifikators as $v)
                <div class="vk-card {{ $v['color'] }}">
                    <div class="vk-head">
                        <div class="vk-avatar">{{ $initials($v['data']['nama']) }}</div>
                        <div class="flex-grow-1 min-w-0">
                            <span class="vk-role">{{ $v['short'] }}</span>
                            <p class="vk-name" title="{{ $v['data']['nama'] }}">{{ $v['data']['nama'] }}</p>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="vk-status {{ $v['data']['status_class'] }}">
                            <i class="bi {{ $v['data']['status_icon'] }}"></i> {{ $v['data']['status'] }}
                        </span>
                        @if($v['data']['acted_at'])
                            <small class="text-muted" style="font-size: .68rem;">
                                <i class="bi bi-clock"></i> {{ \Carbon\Carbon::parse($v['data']['acted_at'])->isoFormat('D MMM HH:mm') }}
                            </small>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
