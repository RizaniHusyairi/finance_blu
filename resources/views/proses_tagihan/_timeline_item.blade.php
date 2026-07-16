{{-- Satu entri pada timeline "Aktivitas Terakhir" (dipakai daftar utama & collapsed). --}}
@php
    $meta = \App\Support\TimelineAksi::meta($log->aksi);
    $chip = \App\Support\TimelineAksi::dokumenContext($log->dokumen_type);
    $aktor = $log->user?->name
        ?? ($log->role_saat_itu && $log->role_saat_itu !== 'SYSTEM' ? $log->role_saat_itu : 'Sistem');
@endphp
<div class="pt-log-item">
    <div class="d-flex justify-content-between align-items-start gap-2">
        <span class="d-inline-flex align-items-center gap-2" style="min-width:0;">
            <i class="bi {{ $meta['icon'] }} text-{{ $meta['color'] }} flex-shrink-0"></i>
            <span class="fw-bold fs-7 text-dark">{{ $meta['label'] }}</span>
            @if($chip)
                <span class="badge bg-light text-secondary border rounded-pill fs-8 flex-shrink-0">{{ $chip }}</span>
            @endif
        </span>
        <span class="text-muted fs-8 text-nowrap" title="{{ optional($log->created_at)->translatedFormat('d M Y H:i') }}">
            {{ optional($log->created_at)->diffForHumans(short: true) }}
        </span>
    </div>
    <div class="text-secondary fs-8 d-flex align-items-center flex-wrap gap-1 mt-1">
        <i class="bi bi-person-circle"></i> {{ $aktor }}
        @if($log->user && $log->role_saat_itu && $log->role_saat_itu !== 'SYSTEM')
            <span class="text-muted">· {{ $log->role_saat_itu }}</span>
        @endif
    </div>
    @if($log->catatan)
        <div class="bg-light rounded-3 p-2 fs-8 text-dark fst-italic mt-1 border-start border-2 border-{{ $meta['color'] }}-subtle">
            "{{ \Illuminate\Support\Str::limit($log->catatan, 160) }}"
        </div>
    @endif
</div>
