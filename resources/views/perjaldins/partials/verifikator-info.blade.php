{{-- partial: verifikator-info.blade.php --}}
@php
    $workflow = collect($tagihan->workflowInstances ?? [])->sortByDesc('created_at')->first();
    $approvals = collect($workflow?->approvals ?? []);
    
    $getVerifierInfo = function($roleCodes, $namaSnapshot) use ($approvals) {
        $roleCodes = (array) $roleCodes;
        $approval = $approvals->first(function($app) use ($roleCodes) {
            return in_array($app->role_code, $roleCodes);
        });
        
        $statusText = 'Belum Verifikasi';
        $badgeClass = 'bg-secondary';
        
        if ($approval) {
            switch ($approval->status) {
                case 'APPROVED':
                    $statusText = 'Sudah Verifikasi';
                    $badgeClass = 'bg-success';
                    break;
                case 'REVISION':
                    $statusText = 'Revisi';
                    $badgeClass = 'bg-warning text-dark';
                    break;
                case 'REJECTED':
                    $statusText = 'Ditolak';
                    $badgeClass = 'bg-danger';
                    break;
                case 'PENDING':
                    $statusText = 'Menunggu';
                    $badgeClass = 'bg-primary';
                    break;
            }
        }

        if (empty($namaSnapshot)) {
            // Coba dari aksi workflow yang sudah ada
            if ($approval && $approval->actedByUser) {
                $namaSnapshot = $approval->actedByUser->name;
            } else {
                // Fallback cari user dengan role tersebut (terutama untuk data lama)
                $spatieRole = match($roleCodes[0]) {
                    'PPSPM' => 'PPSPM',
                    'BENDAHARA_PENERIMAAN' => 'Bendahara Penerimaan',
                    'BENDAHARA_PENGELUARAN' => 'Bendahara Pengeluaran',
                    'PPK' => 'PPK',
                    'KASUBBAG' => 'Kepala Subbagian Keuangan dan Tata Usaha',
                    default => $roleCodes[0]
                };
                $fallbackUser = \App\Models\User::role($spatieRole)->orderBy('name')->first();
                $namaSnapshot = $fallbackUser ? $fallbackUser->name : '-';
            }
        }
        
        return [
            'nama' => $namaSnapshot ?: '-',
            'status' => $statusText,
            'badge_class' => $badgeClass,
        ];
    };

    $verifikators = [
        'PPK' => $getVerifierInfo('PPK', $tagihan->ppk_nama_snapshot),
        'PPSPM' => $getVerifierInfo('PPSPM', $tagihan->ppspm_nama_snapshot),
        'Bendahara Penerimaan' => $getVerifierInfo('BENDAHARA_PENERIMAAN', $tagihan->bendahara_penerimaan_nama_snapshot),
        'Bendahara Pengeluaran' => $getVerifierInfo('BENDAHARA_PENGELUARAN', $tagihan->bendahara_pengeluaran_nama_snapshot),
        'Kepala Subbagian Keuangan dan Tata Usaha' => $getVerifierInfo(['KASUBBAG', 'Kepala Subbagian Keuangan dan Tata Usaha'], $tagihan->kasubbag_nama_snapshot),
    ];
@endphp

<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-bottom py-3">
        <h6 class="mb-0 fw-bold text-dark">
            <i class="bi bi-people text-info me-2"></i>Informasi Verifikator
        </h6>
    </div>
    <div class="card-body">
        <div class="row g-3">
            @foreach($verifikators as $role => $info)
                <div class="col-md-4 col-sm-6">
                    <div class="border rounded p-3 h-100">
                        <div class="small text-muted mb-1">{{ $role }}</div>
                        <div class="fw-bold mb-2 text-truncate" title="{{ $info['nama'] }}">{{ $info['nama'] }}</div>
                        <span class="badge {{ $info['badge_class'] }}">{{ $info['status'] }}</span>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
