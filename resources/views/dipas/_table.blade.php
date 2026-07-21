<div class="table-responsive">
    <table class="table align-middle mb-0">
        <thead>
            <tr>
                <th class="text-center" width="4%">No</th>
                <th width="24%">Nomor DIPA</th>
                <th width="8%">Tahun</th>
                <th width="12%">Tanggal Disahkan</th>
                <th width="10%">Revisi Aktif</th>
                <th width="16%" class="text-end">Total Pagu Revisi Aktif</th>
                <th width="9%" class="text-center">Status</th>
                <th width="8%" class="text-center">COA</th>
                <th width="14%" class="text-center">Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($dipas as $dipa)
                @php
                    $activeRevision = $dipa->activeRevision;
                    $activeItems = collect(optional($activeRevision)->items)->where('status_aktif', true);
                @endphp
                <tr>
                    <td class="text-center text-muted fw-semibold">{{ $loop->iteration }}</td>
                    <td>
                        <div class="d-flex align-items-center gap-3">
                            <div class="dp-doc-tile"><i class="bi bi-journal-bookmark-fill"></i></div>
                            <div>
                                <a href="{{ route('dipas.show', $dipa) }}" class="dp-nomor text-decoration-none">{{ $dipa->nomor_dipa }}</a>
                                <div class="small text-muted">Dokumen induk DIPA</div>
                            </div>
                        </div>
                    </td>
                    <td><span class="dp-badge dp-badge-year"><i class="bi bi-calendar3"></i>{{ $dipa->tahun_anggaran }}</span></td>
                    <td>
                        <div class="fw-semibold">{{ optional($dipa->tanggal_disahkan)->format('d M Y') ?? '-' }}</div>
                        @if($dipa->tanggal_disahkan)
                            <div class="small text-muted">{{ $dipa->tanggal_disahkan->diffForHumans() }}</div>
                        @endif
                    </td>
                    <td><span class="dp-badge dp-badge-rev"><i class="bi bi-arrow-repeat"></i>Revisi {{ $dipa->revisi_aktif_ke ?? 0 }}</span></td>
                    <td class="text-end">
                        <div class="dp-pagu">Rp {{ number_format(optional($activeRevision)->total_pagu ?? 0, 0, ',', '.') }}</div>
                    </td>
                    <td class="text-center">
                        @if($dipa->status_aktif)
                            <span class="dp-badge dp-badge-aktif"><span class="dot"></span>Aktif</span>
                        @else
                            <span class="dp-badge dp-badge-nonaktif"><i class="bi bi-pause-circle"></i>Nonaktif</span>
                        @endif
                    </td>
                    <td class="text-center">
                        <span class="dp-badge dp-badge-item"><i class="bi bi-list-ol"></i>{{ $activeItems->count() }}</span>
                    </td>
                    <td class="text-center">
                        <div class="d-inline-flex align-items-center justify-content-center gap-1 flex-wrap">
                            <a href="{{ route('dipas.show', $dipa) }}"
                               class="dp-act dp-act-view"
                               title="Lihat detail" aria-label="Lihat detail">
                                <i class="bi bi-eye"></i>
                            </a>
                            <a href="{{ route('dipas.edit', $dipa) }}"
                               class="dp-act dp-act-edit"
                               title="Edit header DIPA" aria-label="Edit header DIPA">
                                <i class="bi bi-pencil-square"></i>
                            </a>
                            <a href="{{ route('dipas.revisions.create', $dipa) }}"
                               class="dp-act dp-act-rev"
                               title="Tambah revisi DIPA" aria-label="Tambah revisi DIPA">
                                <i class="bi bi-file-earmark-plus"></i>
                            </a>
                            <form action="{{ route('dipas.toggle', $dipa) }}" method="POST" class="d-inline js-dipa-toggle">
                                @csrf
                                <button type="submit"
                                        class="dp-act {{ $dipa->status_aktif ? 'dp-act-on' : 'dp-act-off' }}"
                                        title="{{ $dipa->status_aktif ? 'Nonaktifkan DIPA' : 'Aktifkan DIPA' }}"
                                        aria-label="{{ $dipa->status_aktif ? 'Nonaktifkan DIPA' : 'Aktifkan DIPA' }}">
                                    <i class="bi {{ $dipa->status_aktif ? 'bi-toggle-on' : 'bi-toggle-off' }}"></i>
                                </button>
                            </form>
                            @if($activeItems->isEmpty())
                                <form action="{{ route('dipas.destroy', $dipa) }}" method="POST" class="d-inline"
                                      onsubmit="return confirm('Hapus DIPA ini beserta seluruh revisinya secara permanen?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="dp-act dp-act-del"
                                            data-deltip="ok" aria-label="Hapus DIPA">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            @else
                                {{-- Elemen disabled tidak memicu event mouse — tooltip
                                     dipasang pada wrapper span. --}}
                                <span class="d-inline-block" data-deltip="locked">
                                    <button type="button" class="dp-act dp-act-del" disabled
                                            aria-label="Tidak bisa dihapus" style="pointer-events:none;">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </span>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9">
                        <div class="dp-empty">
                            <div class="glyph"><i class="bi bi-journal-x"></i></div>
                            <div class="fw-bold mb-1" style="color:#334155;">Tidak ada DIPA ditemukan</div>
                            <div class="small">Belum ada data DIPA yang sesuai dengan filter. Coba ubah kata kunci atau reset filter.</div>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
