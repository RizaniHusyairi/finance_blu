@php
    use App\Enums\JenisRekening;
    $jenisBadge = [
        'PENERIMAAN' => 'bg-success',
        'PENGELUARAN' => 'bg-primary',
        'LAINNYA' => 'bg-secondary',
    ];
@endphp
<div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
            <tr>
                <th class="text-center" width="5%">No</th>
                <th width="22%">Bank / Rekening</th>
                <th width="20%">Atas Nama</th>
                <th width="13%">Jenis</th>
                <th width="14%" class="text-end">Saldo Awal</th>
                <th width="8%" class="text-center">Default</th>
                <th width="8%" class="text-center">Status</th>
                <th width="10%" class="text-center">Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rekenings as $rekening)
                @php $jenis = $rekening->jenis_rekening?->value ?? (string) $rekening->jenis_rekening; @endphp
                <tr>
                    <td class="text-center">{{ $rekenings->firstItem() + $loop->index }}</td>
                    <td>
                        <div class="fw-bold">{{ $rekening->nama_bank }}</div>
                        <div class="small text-muted">{{ $rekening->nomor_rekening }}</div>
                    </td>
                    <td>
                        <div class="fw-semibold">{{ $rekening->nama_rekening }}</div>
                        <div class="small text-muted">{{ $rekening->pemilik?->name ?? '-' }}</div>
                    </td>
                    <td>
                        <span class="badge {{ $jenisBadge[$jenis] ?? 'bg-secondary' }}">
                            {{ JenisRekening::tryFrom($jenis)?->label() ?? $jenis }}
                        </span>
                    </td>
                    <td class="text-end">Rp {{ number_format((float) $rekening->saldo_awal, 2, ',', '.') }}</td>
                    <td class="text-center">
                        @if($rekening->is_default)
                            <span class="badge bg-warning text-dark">Default</span>
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </td>
                    <td class="text-center">
                        <span class="badge {{ $rekening->status_aktif ? 'bg-success' : 'bg-secondary' }}">
                            {{ $rekening->status_aktif ? 'Aktif' : 'Nonaktif' }}
                        </span>
                    </td>
                    <td class="text-center">
                        <div class="d-inline-flex align-items-center justify-content-center gap-1 flex-wrap">
                            <a href="{{ route('rekening-bank.show', $rekening) }}" class="btn btn-sm btn-primary jasa-icon-btn shadow-sm" title="Lihat detail">
                                <i class="bi bi-eye"></i>
                            </a>
                            <a href="{{ route('rekening-bank.edit', $rekening) }}" class="btn btn-sm btn-outline-warning jasa-icon-btn" title="Edit rekening">
                                <i class="bi bi-pencil-square"></i>
                            </a>
                            <form action="{{ route('rekening-bank.toggle', $rekening) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-sm {{ $rekening->status_aktif ? 'btn-outline-success' : 'btn-outline-secondary' }} jasa-icon-btn" title="{{ $rekening->status_aktif ? 'Nonaktifkan' : 'Aktifkan' }}">
                                    <i class="bi {{ $rekening->status_aktif ? 'bi-toggle-on' : 'bi-toggle-off' }}"></i>
                                </button>
                            </form>
                            <form action="{{ route('rekening-bank.destroy', $rekening) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus rekening ini?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger jasa-icon-btn" title="Hapus rekening">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center py-5 text-muted">Belum ada rekening yang sesuai dengan filter.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($rekenings->hasPages())
    <div class="mt-4 d-flex justify-content-end">
        {{ $rekenings->withQueryString()->links() }}
    </div>
@endif
