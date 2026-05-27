<div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
            <tr>
                <th class="text-center" width="5%">No</th>
                <th width="24%">COA Lengkap</th>
                <th width="11%">Kode Akun</th>
                <th width="22%">Nama Akun</th>
                <th width="12%">Jenis Akun</th>
                <th width="10%" class="text-center">Dipakai di DIPA</th>
                <th width="8%" class="text-center">Status</th>
                <th width="18%" class="text-center">Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($coas as $coa)
                <tr>
                    <td class="text-center">{{ $coas->firstItem() + $loop->index }}</td>
                    <td>
                        <div class="fw-bold text-primary">{{ $coa->kode_mak_lengkap ?: '-' }}</div>
                        <div class="small text-muted">{{ $coa->kd_program ?: '-' }} / {{ $coa->kd_giat ?: '-' }} / {{ $coa->kd_output ?: '-' }}</div>
                    </td>
                    <td><span class="badge bg-light text-dark border">{{ $coa->kd_akun ?: '-' }}</span></td>
                    <td class="fw-semibold">{{ $coa->nama_akun }}</td>
                    <td>{{ $coa->jenis_akun ?: '-' }}</td>
                    <td class="text-center">
                        <span class="badge {{ $coa->dipa_revision_items_count > 0 ? 'bg-info text-dark' : 'bg-light text-dark border' }}">
                            {{ number_format($coa->dipa_revision_items_count) }}
                        </span>
                    </td>
                    <td class="text-center">
                        <span class="badge {{ $coa->status_aktif ? 'bg-success' : 'bg-secondary' }}">
                            {{ $coa->status_aktif ? 'Aktif' : 'Nonaktif' }}
                        </span>
                    </td>
                    <td class="text-center">
                        <div class="d-inline-flex align-items-center justify-content-center gap-1 flex-wrap">
                            <a href="{{ route('coas.show', $coa) }}"
                               class="btn btn-sm btn-primary jasa-icon-btn shadow-sm"
                               title="Lihat detail" aria-label="Lihat detail">
                                <i class="bi bi-eye"></i>
                            </a>
                            <a href="{{ route('coas.edit', $coa) }}"
                               class="btn btn-sm btn-outline-warning jasa-icon-btn"
                               title="Edit COA" aria-label="Edit COA">
                                <i class="bi bi-pencil-square"></i>
                            </a>
                            <form action="{{ route('coas.toggle', $coa) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit"
                                        class="btn btn-sm {{ $coa->status_aktif ? 'btn-outline-success' : 'btn-outline-secondary' }} jasa-icon-btn"
                                        title="{{ $coa->status_aktif ? 'Nonaktifkan COA' : 'Aktifkan COA' }}"
                                        aria-label="{{ $coa->status_aktif ? 'Nonaktifkan COA' : 'Aktifkan COA' }}">
                                    <i class="bi {{ $coa->status_aktif ? 'bi-toggle-on' : 'bi-toggle-off' }}"></i>
                                </button>
                            </form>
                            @if($coa->dipa_revision_items_count > 0)
                                <button type="button"
                                        class="btn btn-sm btn-outline-danger jasa-icon-btn"
                                        title="Tidak bisa dihapus (sudah dipakai di item DIPA)"
                                        aria-label="Tidak bisa dihapus"
                                        disabled>
                                    <i class="bi bi-trash"></i>
                                </button>
                            @else
                                <form action="{{ route('coas.destroy', $coa) }}" method="POST" class="d-inline"
                                      onsubmit="return confirm('Hapus COA ini secara permanen?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="btn btn-sm btn-outline-danger jasa-icon-btn"
                                            title="Hapus COA" aria-label="Hapus COA">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center py-5 text-muted">Belum ada data COA yang sesuai dengan filter.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($coas->hasPages())
    <div class="mt-4 d-flex justify-content-end">
        {{ $coas->withQueryString()->links() }}
    </div>
@endif
