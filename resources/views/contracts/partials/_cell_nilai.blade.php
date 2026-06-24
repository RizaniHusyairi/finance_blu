@php
    $endDate = \Carbon\Carbon::parse($kontrak->tanggal_selesai);
    $isLate = $endDate->isPast() && $kontrak->status_kontrak === 'AKTIF';
@endphp
<span class="money-pos">Rp {{ number_format($kontrak->nilai_total_kontrak, 0, ',', '.') }}</span>
<div class="timeline-info">
    <i class="bi bi-calendar-event {{ $isLate ? 'text-danger' : '' }}"></i>
    {{ $isLate ? 'Terlambat' : 'Selesai' }}: {{ $endDate->isoFormat('D MMM YYYY') }}
</div>
