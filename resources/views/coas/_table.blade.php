<div class="table-responsive">
    <table class="table align-middle mb-0">
        <thead>
            <tr>
                <th class="text-center px-3" width="4%">No</th>
                <th width="26%">COA Lengkap</th>
                <th width="10%">Kode Akun</th>
                <th width="22%">Nama Akun</th>
                <th width="11%">Jenis Akun</th>
                <th width="10%" class="text-center">Dipakai di DIPA</th>
                <th width="8%" class="text-center">Status</th>
                <th width="15%" class="text-center px-3">Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($coas as $coa)
                <tr style="--i: {{ $loop->index }};">
                    <td class="text-center px-3 text-muted fw-semibold">{{ $coas->firstItem() + $loop->index }}</td>
                    <td>
                        @if($coa->kode_mak_lengkap)
                            <span class="cl-kode" data-copy="{{ $coa->kode_mak_lengkap }}" title="Klik untuk menyalin kode">
                                <span class="cl-kode-text">{{ $coa->kode_mak_lengkap }}</span>
                                <i class="bi bi-copy"></i>
                            </span>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                        <div class="small text-muted mt-1">
                            <i class="bi bi-diagram-2 me-1"></i>{{ $coa->kd_program ?: '-' }} / {{ $coa->kd_giat ?: '-' }} / {{ $coa->kd_output ?: '-' }}
                        </div>
                    </td>
                    <td><span class="cl-badge cl-badge-akun">{{ $coa->kd_akun ?: '—' }}</span></td>
                    <td class="fw-semibold">{{ $coa->nama_akun }}</td>
                    <td>
                        @if($coa->jenis_akun)
                            <span class="cl-badge cl-badge-jenis">{{ $coa->jenis_akun }}</span>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td class="text-center">
                        @if($coa->dipa_revision_items_count > 0)
                            <span class="cl-badge cl-badge-dipa"><i class="bi bi-folder2-open"></i>{{ number_format($coa->dipa_revision_items_count) }} item</span>
                        @else
                            <span class="cl-badge cl-badge-zero">0 item</span>
                        @endif
                    </td>
                    <td class="text-center">
                        @if($coa->status_aktif)
                            <span class="cl-badge cl-badge-aktif"><span class="dot"></span>Aktif</span>
                        @else
                            <span class="cl-badge cl-badge-nonaktif"><i class="bi bi-pause-circle"></i>Nonaktif</span>
                        @endif
                    </td>
                    <td class="text-center px-3">
                        <div class="d-inline-flex align-items-center justify-content-center gap-1 flex-wrap">
                            <a href="{{ route('coas.show', $coa) }}"
                               class="cl-act cl-act-view"
                               title="Lihat detail" aria-label="Lihat detail">
                                <i class="bi bi-eye"></i>
                            </a>
                            <a href="{{ route('coas.edit', $coa) }}"
                               class="cl-act cl-act-edit"
                               title="Edit COA" aria-label="Edit COA">
                                <i class="bi bi-pencil-square"></i>
                            </a>
                            <form action="{{ route('coas.toggle', $coa) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit"
                                        class="cl-act {{ $coa->status_aktif ? 'cl-act-on' : 'cl-act-off' }}"
                                        title="{{ $coa->status_aktif ? 'Nonaktifkan COA' : 'Aktifkan COA' }}"
                                        aria-label="{{ $coa->status_aktif ? 'Nonaktifkan COA' : 'Aktifkan COA' }}">
                                    <i class="bi {{ $coa->status_aktif ? 'bi-toggle-on' : 'bi-toggle-off' }}"></i>
                                </button>
                            </form>
                            @if($coa->dipa_revision_items_count > 0)
                                <button type="button"
                                        class="cl-act cl-act-del"
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
                                            class="cl-act cl-act-del"
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
                    <td colspan="8">
                        <div class="cl-empty">
                            <div class="glyph"><i class="bi bi-journal-x"></i></div>
                            <div class="fw-bold mb-1" style="color:#334155;">Tidak ada COA ditemukan</div>
                            <div class="small">Belum ada data COA yang sesuai dengan filter. Coba ubah kata kunci atau reset filter.</div>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($coas->hasPages())
    <div class="px-4 py-3 d-flex justify-content-end border-top" style="border-color:#e8ecf5 !important;">
        {{ $coas->withQueryString()->links() }}
    </div>
@endif
