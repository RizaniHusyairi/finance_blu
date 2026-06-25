@php
    $statusClass = [
        'AVAILABLE' => 'bg-success',
        'RESERVED' => 'bg-warning text-dark',
        'USED' => 'bg-primary',
        'CANCELLED' => 'bg-secondary',
    ];
@endphp

<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <div class="small text-muted">
        Menampilkan <strong>{{ $numbers->firstItem() ?? 0 }}–{{ $numbers->lastItem() ?? 0 }}</strong>
        dari <strong>{{ number_format($numbers->total()) }}</strong> nomor
        @if(request()->filled('search'))
            untuk pencarian “<strong>{{ request('search') }}</strong>”
        @endif
    </div>
    @if(request()->hasAny(['search', 'document_key', 'status', 'tahun']))
        <a href="{{ route('document-numbers.index') }}" data-dn-reset class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-x-circle me-1"></i>Hapus filter
        </a>
    @endif
</div>

<div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
            <tr>
                <th width="5%" class="text-center">No</th>
                <th width="14%">Jenis</th>
                <th width="25%">Nomor Dokumen</th>
                <th width="10%" class="text-center">Status</th>
                <th width="16%">Pemakaian</th>
                <th width="18%">Catatan</th>
                <th width="12%" class="text-center">Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($numbers as $number)
                <tr>
                    <td class="text-center">{{ $numbers->firstItem() + $loop->index }}</td>
                    <td>
                        <div class="fw-bold">{{ str_replace('_', ' ', $number->document_key) }}</div>
                        <div class="small text-muted">{{ $number->series_prefix }}</div>
                    </td>
                    <td>
                        <div class="fw-bold font-monospace text-primary">{{ $number->full_number }}</div>
                        <div class="small text-muted">Nomor urut: {{ str_pad((string) $number->running_number, $number->number_padding, '0', STR_PAD_LEFT) }}</div>
                    </td>
                    <td class="text-center">
                        <span class="badge {{ $statusClass[$number->status] ?? 'bg-secondary' }}">{{ $number->status }}</span>
                    </td>
                    <td>
                        @if($number->status === 'RESERVED')
                            <div class="fw-semibold">{{ $number->reservedBy->name ?? '-' }}</div>
                            <div class="small text-muted">{{ optional($number->reserved_at)->translatedFormat('d M Y H:i') }}</div>
                        @elseif($number->status === 'USED')
                            <div class="fw-semibold">{{ $number->usedBy->name ?? '-' }}</div>
                            <div class="small text-muted">{{ $number->usage_source === 'EXTERNAL' ? 'Eksternal' : 'Sistem' }}</div>
                            <div class="small text-muted">{{ optional($number->used_at)->translatedFormat('d M Y H:i') }}</div>
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </td>
                    <td class="small text-muted">{{ $number->notes ?: '-' }}</td>
                    <td class="text-center">
                        <div class="btn-group">
                            @if($number->status === 'RESERVED')
                                <form method="POST" action="{{ route('document-numbers.release', $number) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-secondary">Lepas</button>
                                </form>
                            @endif
                            @if(in_array($number->status, ['AVAILABLE', 'RESERVED'], true))
                                <form method="POST" action="{{ route('document-numbers.mark-used', $number) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-primary">Eksternal</button>
                                </form>
                                <form method="POST" action="{{ route('document-numbers.cancel', $number) }}" onsubmit="return confirm('Batalkan nomor ini?');">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Batal</button>
                                </form>
                            @elseif($number->status === 'USED' && $number->usage_source === 'EXTERNAL')
                                <form method="POST" action="{{ route('document-numbers.cancel', $number) }}" onsubmit="return confirm('Batalkan nomor eksternal ini?');">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Batal</button>
                                </form>
                            @else
                                <span class="text-muted small">Terkunci</span>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center py-5 text-muted">Belum ada nomor dokumen pada filter ini.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($numbers->hasPages())
    <div class="mt-4 d-flex justify-content-end">
        {{ $numbers->withQueryString()->links() }}
    </div>
@endif
