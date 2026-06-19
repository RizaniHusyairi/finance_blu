@php
    $bln = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

    $curSort = $sort ?? 'tanggal';
    $curDir = ($dir ?? 'asc') === 'desc' ? 'desc' : 'asc';
    // Tombol header sortir: klik menukar arah pada kolom aktif, atau buka kolom baru asc.
    $sortBtn = function (string $key, string $label) use ($curSort, $curDir) {
        $active = $curSort === $key;
        $next = $active && $curDir === 'asc' ? 'desc' : 'asc';
        $ico = ! $active ? 'bi-arrow-down-up' : ($curDir === 'asc' ? 'bi-caret-up-fill' : 'bi-caret-down-fill');

        return '<button type="button" class="bku-sort' . ($active ? ' is-active' : '')
            . '" data-sort="' . $key . '" data-dir="' . $next . '" aria-label="Urutkan ' . e($label) . '">'
            . e($label) . ' <i class="bi ' . $ico . '"></i></button>';
    };
@endphp

{{-- ====== Ringkasan ====== --}}
<div class="bku-stats">
    <div class="bku-stat bku-stat--slate" style="--i:0">
        <div class="bku-stat__icon"><i class="bi bi-wallet2"></i></div>
        <div class="bku-stat__body">
            <span class="bku-stat__label">Saldo Awal</span>
            <span class="bku-stat__value" data-value="{{ (int) $buku['saldo_awal'] }}">Rp {{ number_format($buku['saldo_awal'], 0, ',', '.') }}</span>
        </div>
    </div>
    <div class="bku-stat bku-stat--green" style="--i:1">
        <div class="bku-stat__icon"><i class="bi bi-arrow-down-left"></i></div>
        <div class="bku-stat__body">
            <span class="bku-stat__label">Total Penerimaan</span>
            <span class="bku-stat__value" data-value="{{ (int) $buku['total_terima'] }}">Rp {{ number_format($buku['total_terima'], 0, ',', '.') }}</span>
        </div>
    </div>
    <div class="bku-stat bku-stat--red" style="--i:2">
        <div class="bku-stat__icon"><i class="bi bi-arrow-up-right"></i></div>
        <div class="bku-stat__body">
            <span class="bku-stat__label">Total Pengeluaran</span>
            <span class="bku-stat__value" data-value="{{ (int) $buku['total_keluar'] }}">Rp {{ number_format($buku['total_keluar'], 0, ',', '.') }}</span>
        </div>
    </div>
    <div class="bku-stat bku-stat--indigo" style="--i:3">
        <div class="bku-stat__icon"><i class="bi bi-cash-stack"></i></div>
        <div class="bku-stat__body">
            <span class="bku-stat__label">Saldo Akhir</span>
            <span class="bku-stat__value" data-value="{{ (int) $buku['saldo_akhir'] }}">Rp {{ number_format($buku['saldo_akhir'], 0, ',', '.') }}</span>
        </div>
    </div>
</div>

{{-- ====== Ledger ====== --}}
<div class="bku-card">
    <div class="bku-card__head">
        <div class="bku-card__title"><i class="bi bi-journal-text"></i> Rincian Transaksi</div>
        <span class="bku-chip-count">{{ $entries->count() }} transaksi @if($search)<span class="text-muted">dari {{ $buku['jumlah_transaksi'] }}</span>@endif</span>
    </div>
    <div class="bku-table-wrap">
        <table class="bku-table">
            <thead>
                <tr>
                    <th>{!! $sortBtn('tanggal', 'Tanggal') !!}</th>
                    <th>{!! $sortBtn('kode', 'Kode Akun & Jenis') !!}</th>
                    <th>{!! $sortBtn('uraian', 'Uraian') !!}</th>
                    <th class="ta-end">{!! $sortBtn('penerimaan', 'Penerimaan') !!}</th>
                    <th class="ta-end">{!! $sortBtn('pengeluaran', 'Pengeluaran') !!}</th>
                    <th class="ta-end">{!! $sortBtn('saldo', 'Saldo') !!}</th>
                    <th class="ta-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @unless($search)
                    <tr class="bku-row-saldoawal">
                        <td colspan="5"><i class="bi bi-flag-fill"></i> Saldo Awal Bulan Berjalan
                            <a href="{{ route('pembukuan.setup.edit') }}" class="bku-link-mini" title="Atur saldo awal di Setup Pembukuan">Atur</a>
                        </td>
                        <td class="ta-end bku-num bku-num--saldo">{{ number_format($buku['saldo_awal'], 0, ',', '.') }}</td>
                        <td></td>
                    </tr>
                @endunless

                @forelse($entries as $e)
                    @php $masuk = $e->arus_kas === 'DEBIT_MASUK'; @endphp
                    <tr class="bku-row" style="--r:{{ $loop->index }}">
                        <td>
                            <span class="bku-date">
                                <span class="bku-date__d">{{ optional($e->tanggal_transaksi)->format('d') }}</span>
                                <span class="bku-date__m">{{ $bln[(int) optional($e->tanggal_transaksi)->format('n')] ?? '' }} {{ optional($e->tanggal_transaksi)->format('Y') }}</span>
                            </span>
                        </td>
                        <td>
                            @if($e->akunPendapatan)
                                <span class="bku-pill bku-pill--akun"><span class="bku-pill__code">{{ $e->akunPendapatan->kode_gabungan }}</span>{{ $e->akunPendapatan->uraian_jenis }}</span>
                            @elseif($e->jenis_transaksi)
                                <span class="bku-pill bku-pill--jenis">{{ $e->jenis_transaksi->label() }}</span>
                            @else
                                <span class="bku-muted">—</span>
                            @endif
                        </td>
                        <td>
                            <span class="bku-uraian">{{ \Illuminate\Support\Str::limit($e->uraian, 64) }}</span>
                            @if($e->detail_mutasi_bank_id && $e->referensi_penerimaan_id)
                                <span class="bku-tag bku-tag--ok" title="Cocok dengan rekening koran &amp; tagihan — terverifikasi"><i class="bi bi-patch-check-fill"></i> Terverifikasi</span>
                            @elseif($e->referensi_penerimaan_id)
                                <span class="bku-tag bku-tag--warn" title="Dari tagihan; belum ada padanan di rekening koran">Belum cocok bank</span>
                            @elseif($e->detail_mutasi_bank_id)
                                <span class="bku-tag bku-tag--info" title="Dari rekening koran">Bank koran</span>
                            @endif
                        </td>
                        <td class="ta-end bku-num bku-num--in">{{ $masuk ? number_format($e->nominal, 0, ',', '.') : '' }}</td>
                        <td class="ta-end bku-num bku-num--out">{{ $masuk ? '' : number_format($e->nominal, 0, ',', '.') }}</td>
                        <td class="ta-end bku-num bku-num--saldo">{{ number_format($e->saldo_berjalan ?? 0, 0, ',', '.') }}</td>
                        <td class="ta-center">
                            <div class="bku-actions">
                                @unless(\Illuminate\Support\Str::startsWith($e->nomor_bukti, 'SALDO-AWAL/'))
                                    <a href="{{ route('pembukuan.bku.show', $e->id) }}" class="bku-ico" title="Detail"><i class="bi bi-eye"></i></a>
                                @endunless
                                @if($e->jenis_transaksi && ! $e->referensi_penerimaan_id && ! $e->detail_mutasi_bank_id && ! $e->transaksi_pembukuan_id)
                                    <form action="{{ route('pembukuan.penerimaan-manual.destroy', $e->id) }}" method="POST" onsubmit="return confirm('Hapus baris mutasi non-jasa ini?');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="bku-ico bku-ico--danger" title="Hapus baris manual"><i class="bi bi-trash3"></i></button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">
                            <div class="bku-empty">
                                <i class="bi bi-{{ $search ? 'search' : 'inbox' }}"></i>
                                <p>{{ $search ? 'Tidak ada transaksi yang cocok dengan pencarian.' : 'Belum ada transaksi penerimaan.' }}</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
