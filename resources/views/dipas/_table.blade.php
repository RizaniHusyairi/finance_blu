{{-- Tabel di kontainer lebar; di kontainer sempit tiap baris menjadi kartu
     (lihat @container di dipas/index). data-label dipakai sebagai label kartu. --}}
<div class="dp-table-wrap">
    <table class="table align-middle mb-0 dp-table">
        <thead>
            <tr>
                <th class="dp-c-no text-center">No</th>
                <th class="dp-c-dipa">Nomor DIPA</th>
                <th class="dp-c-tahun">Tahun &amp; Pengesahan</th>
                <th class="dp-c-rev">Revisi Aktif</th>
                <th class="dp-c-pagu text-end">Total Pagu Revisi Aktif</th>
                <th class="dp-c-status text-center">Status</th>
                <th class="dp-c-coa text-center">COA</th>
                <th class="dp-c-aksi text-center">Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($dipas as $dipa)
                @php
                    $activeRevision = $dipa->activeRevision;
                    $activeItems = collect(optional($activeRevision)->items)->where('status_aktif', true);
                @endphp
                <tr>
                    <td class="dp-c-no text-center text-muted fw-semibold">{{ $loop->iteration }}</td>
                    <td class="dp-c-dipa">
                        <div class="d-flex align-items-center gap-3">
                            <div class="dp-doc-tile"><i class="bi bi-journal-bookmark-fill"></i></div>
                            <div class="min-w-0">
                                <a href="{{ route('dipas.show', $dipa) }}" class="dp-nomor text-decoration-none">{!! str_replace(['/', '.'], ['/<wbr>', '.<wbr>'], e($dipa->nomor_dipa)) !!}</a>
                                <div class="small text-muted">Dokumen induk DIPA</div>
                            </div>
                        </div>
                    </td>
                    <td class="dp-c-tahun" data-label="Tahun &amp; Pengesahan">
                        <span class="dp-badge dp-badge-year"><i class="bi bi-calendar3"></i>TA {{ $dipa->tahun_anggaran }}</span>
                        <div class="dp-tgl">
                            <span class="fw-semibold">{{ optional($dipa->tanggal_disahkan)->format('d M Y') ?? '-' }}</span>
                            @if($dipa->tanggal_disahkan)
                                <span class="dp-tgl-rel text-muted">{{ $dipa->tanggal_disahkan->diffForHumans() }}</span>
                            @endif
                        </div>
                    </td>
                    <td class="dp-c-rev" data-label="Revisi Aktif">
                        <span class="dp-badge dp-badge-rev"><i class="bi bi-arrow-repeat"></i>Revisi {{ $dipa->revisi_aktif_ke ?? 0 }}</span>
                    </td>
                    <td class="dp-c-pagu text-end" data-label="Total Pagu Revisi Aktif">
                        <div class="dp-pagu">Rp {{ number_format(optional($activeRevision)->total_pagu ?? 0, 0, ',', '.') }}</div>
                    </td>
                    <td class="dp-c-status text-center">
                        @if($dipa->status_aktif)
                            <span class="dp-badge dp-badge-aktif"><span class="dot"></span>Aktif</span>
                        @else
                            <span class="dp-badge dp-badge-nonaktif"><i class="bi bi-pause-circle"></i>Nonaktif</span>
                        @endif
                    </td>
                    <td class="dp-c-coa text-center" data-label="COA Aktif">
                        <span class="dp-badge dp-badge-item" title="{{ $activeItems->count() }} COA aktif pada revisi aktif">
                            <i class="bi bi-list-ol"></i>{{ number_format($activeItems->count(), 0, ',', '.') }}
                        </span>
                    </td>
                    <td class="dp-c-aksi text-center">
                        <div class="dp-actions">
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
                <tr class="dp-row-empty">
                    <td colspan="8">
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
