<div class="table-responsive">
    <table class="table align-middle mb-0">
        <thead>
            <tr>
                <th class="text-center px-3" width="4%">No</th>
                <th width="12%">Kode Pajak</th>
                <th width="16%">Jenis Pajak</th>
                <th width="9%" class="text-center">Persentase</th>
                <th width="11%" class="text-center">KAP / KJS</th>
                <th width="17%">Rumus</th>
                <th width="15%">Periode Berlaku</th>
                <th width="8%" class="text-center">Status</th>
                <th width="8%" class="text-center px-3">Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($pajaks as $pajak)
                @php
                    $mulai = $pajak->berlaku_mulai ? \Carbon\Carbon::parse($pajak->berlaku_mulai) : null;
                    $sampai = $pajak->berlaku_sampai ? \Carbon\Carbon::parse($pajak->berlaku_sampai) : null;

                    $periodLabel = 'Tanpa batas waktu';
                    if ($mulai && $sampai) {
                        $periodLabel = $mulai->format('d-m-Y') . ' s/d ' . $sampai->format('d-m-Y');
                    } elseif ($mulai && !$sampai) {
                        $periodLabel = 'Mulai ' . $mulai->format('d-m-Y');
                    }

                    // Badge masa berlaku: berlaku (hijau), segera berakhir ≤30 hari (kuning
                    // berdenyut), belum berlaku, atau expired.
                    $validity = null;
                    if ($pajak->status_aktif) {
                        if ($mulai && $mulai->gt($today)) {
                            $validity = ['label' => 'Belum berlaku', 'class' => 'tp-badge-belum', 'icon' => 'bi-hourglass-top'];
                        } elseif ($sampai && $sampai->lt($today)) {
                            $validity = ['label' => 'Expired', 'class' => 'tp-badge-expired', 'icon' => 'bi-x-octagon'];
                        } elseif ($sampai && $today->diffInDays($sampai) <= 30) {
                            $sisaHari = (int) ceil($today->diffInDays($sampai));
                            $validity = ['label' => $sisaHari <= 0 ? 'Berakhir hari ini' : 'Berakhir ' . $sisaHari . ' hari lagi', 'class' => 'tp-badge-segera', 'icon' => 'bi-alarm'];
                        } else {
                            $validity = ['label' => 'Berlaku', 'class' => 'tp-badge-berlaku', 'dot' => true];
                        }
                    }

                    $persenLabel = rtrim(rtrim(number_format($pajak->persentase, 4, ',', '.'), '0'), ',');
                @endphp
                <tr style="--i: {{ $loop->index }};">
                    <td class="text-center px-3 text-muted fw-semibold">{{ $pajaks->firstItem() + $loop->index }}</td>
                    <td>
                        @if($pajak->kode_pajak)
                            <span class="tp-kode" data-copy="{{ $pajak->kode_pajak }}" title="Klik untuk menyalin kode">
                                <span class="tp-kode-text">{{ $pajak->kode_pajak }}</span>
                                <i class="bi bi-copy"></i>
                            </span>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td><span class="tp-badge tp-badge-jenis"><i class="bi bi-tag"></i>{{ $pajak->jenis_pajak }}</span></td>
                    <td class="text-center">
                        <span class="tp-persen">{{ $persenLabel }}<small>%</small></span>
                    </td>
                    <td class="text-center">
                        @if($pajak->kode_akun_pajak || $pajak->kode_jenis_setoran)
                            <span class="tp-badge tp-badge-kap" title="Kode Akun Pajak">{{ $pajak->kode_akun_pajak ?? '—' }}</span>
                            <span class="tp-badge tp-badge-kap" title="Kode Jenis Setoran">{{ $pajak->kode_jenis_setoran ?? '—' }}</span>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td>
                        @if($pajak->rumus)
                            <span class="tp-rumus" title="{{ $pajak->rumus }}">{{ $pajak->rumus }}</span>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td>
                        <div class="small fw-semibold" style="color:#334155;">{{ $periodLabel }}</div>
                        @if($validity)
                            <span class="tp-badge {{ $validity['class'] }} mt-1">
                                @if(!empty($validity['dot']))<span class="dot"></span>@else<i class="bi {{ $validity['icon'] }}"></i>@endif
                                {{ $validity['label'] }}
                            </span>
                        @endif
                    </td>
                    <td class="text-center">
                        @if($pajak->status_aktif)
                            <span class="tp-badge tp-badge-aktif"><span class="dot"></span>Aktif</span>
                        @else
                            <span class="tp-badge tp-badge-nonaktif"><i class="bi bi-pause-circle"></i>Nonaktif</span>
                        @endif
                    </td>
                    <td class="text-center px-3">
                        <div class="d-inline-flex align-items-center justify-content-center gap-1 flex-wrap">
                            <a href="{{ route('master-pajak.show', $pajak) }}"
                               class="tp-act tp-act-view"
                               title="Lihat detail" aria-label="Lihat detail">
                                <i class="bi bi-eye"></i>
                            </a>
                            <a href="{{ route('master-pajak.edit', $pajak) }}"
                               class="tp-act tp-act-edit"
                               title="Edit tarif pajak" aria-label="Edit tarif pajak">
                                <i class="bi bi-pencil-square"></i>
                            </a>
                            <form action="{{ route('master-pajak.toggle', $pajak) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit"
                                        class="tp-act {{ $pajak->status_aktif ? 'tp-act-on' : 'tp-act-off' }}"
                                        title="{{ $pajak->status_aktif ? 'Nonaktifkan tarif' : 'Aktifkan tarif' }}"
                                        aria-label="{{ $pajak->status_aktif ? 'Nonaktifkan tarif' : 'Aktifkan tarif' }}">
                                    <i class="bi {{ $pajak->status_aktif ? 'bi-toggle-on' : 'bi-toggle-off' }}"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9">
                        <div class="tp-empty">
                            <div class="glyph"><i class="bi bi-receipt"></i></div>
                            <div class="fw-bold mb-1" style="color:#334155;">Tidak ada tarif pajak ditemukan</div>
                            <div class="small">Belum ada data yang sesuai dengan filter. Coba ubah kata kunci atau reset filter.</div>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($pajaks->hasPages())
    <div class="px-4 py-3 d-flex justify-content-end border-top" style="border-color:#e6efec !important;">
        {{ $pajaks->withQueryString()->links() }}
    </div>
@endif
