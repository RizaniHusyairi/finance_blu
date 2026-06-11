<div class="card process-card shadow-sm mb-3">
    <div class="card-body">
        <div class="process-section-title mb-3">Pipeline</div>
        @php
            $items = [
                ['label' => 'Tagihan diverifikasi', 'done' => $state['tagihanApproved']],
                ['label' => 'COA lengkap', 'done' => $state['coaDone']],
                ['label' => 'KPA setuju', 'done' => $state['kpaDone']],
                ['label' => 'Draft SPP/SPM/NPI/SP2D', 'done' => (bool) $state['spp']],
                ['label' => 'SPP approved', 'done' => $state['sppApproved']],
                ['label' => 'SPM approved', 'done' => $state['spmApproved']],
                ['label' => 'NPI approved', 'done' => $state['npiApproved']],
                ['label' => 'Bukti transfer', 'done' => (bool) $state['buktiTransfer']],
                ['label' => 'SP2D terbit', 'done' => $state['sp2dTerbit']],
                ['label' => 'BKU posted', 'done' => $state['bkuPosted']],
            ];
        @endphp
        @foreach($items as $item)
            <div class="process-timeline-item">
                <span class="process-timeline-dot" style="background: {{ $item['done'] ? '#16a34a' : '#cbd5e1' }}"></span>
                <div class="fw-semibold">{{ $item['label'] }}</div>
                <div class="small text-muted">{{ $item['done'] ? 'Selesai' : 'Belum selesai' }}</div>
            </div>
        @endforeach
    </div>
</div>

<div class="card process-card shadow-sm">
    <div class="card-body">
        <div class="process-section-title mb-3">Log Terakhir</div>
        @forelse($tagihan->logs->sortByDesc('created_at')->take(8) as $log)
            <div class="mb-3">
                <div class="fw-semibold">{{ $log->aksi }}</div>
                <div class="small text-muted">{{ optional($log->created_at)->format('d/m/Y H:i') }} - {{ $log->user?->name ?? 'System' }}</div>
                @if($log->catatan)
                    <div class="small">{{ $log->catatan }}</div>
                @endif
            </div>
        @empty
            <div class="text-muted small">Belum ada log.</div>
        @endforelse
    </div>
</div>
