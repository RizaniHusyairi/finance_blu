<div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
            <tr>
                <th class="text-center" width="5%">No</th>
                <th width="22%">Nomor DIPA</th>
                <th width="10%">Tahun</th>
                <th width="12%">Tanggal Disahkan</th>
                <th width="10%">Revisi Aktif</th>
                <th width="16%" class="text-end">Total Pagu Revisi Aktif</th>
                <th width="10%" class="text-center">Status</th>
                <th width="10%" class="text-center">Jumlah Item</th>
                <th width="15%" class="text-center">Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($dipas as $dipa)
                @php
                    $activeRevision = $dipa->activeRevision;
                    $activeItems = collect(optional($activeRevision)->items)->where('status_aktif', true);
                @endphp
                <tr>
                    <td class="text-center">{{ $loop->iteration }}</td>
                    <td>
                        <div class="fw-bold text-primary">{{ $dipa->nomor_dipa }}</div>
                        <div class="small text-muted">Dokumen induk DIPA</div>
                    </td>
                    <td>
                        <span class="badge bg-light text-dark border">{{ $dipa->tahun_anggaran }}</span>
                    </td>
                    <td>{{ optional($dipa->tanggal_disahkan)->format('d M Y') ?? '-' }}</td>
                    <td>
                        <span class="badge bg-info text-dark">Revisi {{ $dipa->revisi_aktif_ke ?? 0 }}</span>
                    </td>
                    <td class="text-end fw-bold">
                        Rp {{ number_format(optional($activeRevision)->total_pagu ?? 0, 0, ',', '.') }}
                    </td>
                    <td class="text-center">
                        @if($dipa->status_aktif)
                            <span class="badge bg-success">Aktif</span>
                        @else
                            <span class="badge bg-secondary">Nonaktif</span>
                        @endif
                    </td>
                    <td class="text-center">
                        <span class="badge bg-light text-dark border">{{ $activeItems->count() }} Item</span>
                    </td>
                    <td class="text-center">
                        <div class="d-inline-flex align-items-center justify-content-center gap-1 flex-wrap">
                            <a href="{{ route('dipas.show', $dipa) }}"
                               class="btn btn-sm btn-primary jasa-icon-btn shadow-sm"
                               title="Lihat detail" aria-label="Lihat detail">
                                <i class="bi bi-eye"></i>
                            </a>
                            <a href="{{ route('dipas.edit', $dipa) }}"
                               class="btn btn-sm btn-outline-warning jasa-icon-btn"
                               title="Edit header DIPA" aria-label="Edit header DIPA">
                                <i class="bi bi-pencil-square"></i>
                            </a>
                            <a href="{{ route('dipas.revisions.create', $dipa) }}"
                               class="btn btn-sm btn-outline-info jasa-icon-btn"
                               title="Tambah revisi DIPA" aria-label="Tambah revisi DIPA">
                                <i class="bi bi-file-earmark-plus"></i>
                            </a>
                            <form action="{{ route('dipas.toggle', $dipa) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit"
                                        class="btn btn-sm {{ $dipa->status_aktif ? 'btn-outline-success' : 'btn-outline-secondary' }} jasa-icon-btn"
                                        title="{{ $dipa->status_aktif ? 'Nonaktifkan DIPA' : 'Aktifkan DIPA' }}"
                                        aria-label="{{ $dipa->status_aktif ? 'Nonaktifkan DIPA' : 'Aktifkan DIPA' }}">
                                    <i class="bi {{ $dipa->status_aktif ? 'bi-toggle-on' : 'bi-toggle-off' }}"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center py-5 text-muted">
                        Belum ada data DIPA yang sesuai dengan filter.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
