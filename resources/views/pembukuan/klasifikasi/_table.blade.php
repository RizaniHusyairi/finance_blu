@php $bln = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des']; @endphp
<div class="klas-card">
    <div class="klas-card__head">
        <div class="klas-card__title"><i class="bi bi-tags"></i> Baris Rekening Koran</div>
        <span class="klas-chip-count">{{ number_format($rows->total(), 0, ',', '.') }} baris</span>
    </div>
    <div class="klas-table-wrap">
        <table class="klas-table">
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Deskripsi</th>
                    <th class="ta-end">Masuk</th>
                    <th class="ta-end">Keluar</th>
                    <th style="min-width:300px;">Akun Pendapatan</th>
                    <th class="ta-center">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $row)
                    <tr class="klas-row" style="--r:{{ $loop->index }}">
                        <td>
                            <span class="klas-date">
                                <span class="klas-date__d">{{ optional($row->tanggal_transaksi)->format('d') }}</span>
                                <span class="klas-date__m">{{ $bln[(int) optional($row->tanggal_transaksi)->format('n')] ?? '' }} {{ optional($row->tanggal_transaksi)->format('Y') }}</span>
                            </span>
                        </td>
                        <td><span class="klas-desc" title="{{ $row->deskripsi }}">{{ \Illuminate\Support\Str::limit($row->deskripsi, 58) }}</span></td>
                        <td class="ta-end klas-num klas-num--in">{{ (float) $row->kredit ? number_format($row->kredit, 0, ',', '.') : '' }}</td>
                        <td class="ta-end klas-num klas-num--out">{{ (float) $row->debit ? number_format($row->debit, 0, ',', '.') : '' }}</td>
                        <td>
                            @if($row->arah_mutasi === 'MASUK')
                                <div class="klas-akun">
                                    <select class="klas-select js-akun" data-id="{{ $row->id }}" aria-label="Akun pendapatan">
                                        <option value="">— belum dipilih —</option>
                                        @foreach($akunOptions as $a)
                                            <option value="{{ $a->id }}" @selected($row->akun_pendapatan_id == $a->id)>{{ $a->kode_gabungan }} · {{ $a->layanan_label }}</option>
                                        @endforeach
                                    </select>
                                    <i class="bi bi-check-lg klas-akun__ok"></i>
                                </div>
                            @else
                                <span class="klas-muted"><i class="bi bi-arrow-up-right"></i> arus keluar</span>
                            @endif
                        </td>
                        <td class="ta-center">
                            @if($row->bukuKasUmum && $row->bukuKasUmum->referensi_penerimaan_id)
                                <span class="klas-status klas-status--ok" title="Cocok: terposting di BKU &amp; tertaut tagihan — terverifikasi"><i class="bi bi-patch-check-fill"></i> Cocok</span>
                            @elseif($row->bukuKasUmum)
                                <span class="klas-status klas-status--posted" title="Sudah diposting ke BKU, belum tertaut tagihan"><i class="bi bi-check-circle"></i> Terposting</span>
                            @else
                                <span class="klas-status klas-status--pending" title="Belum diposting ke BKU"><i class="bi bi-clock-history"></i> Belum</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">
                            <div class="klas-empty">
                                <i class="bi bi-{{ ($filters['search'] ?? '') || ($filters['status'] ?? '') || ($filters['import_id'] ?? '') ? 'search' : 'inbox' }}"></i>
                                <p>Tidak ada baris koran sesuai filter.</p>
                                <span>Unggah berkas rekening koran lewat tombol <b>Impor Rekening Koran</b> di atas.</span>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($rows->hasPages())
        <div class="klas-pagination">{{ $rows->withQueryString()->links() }}</div>
    @endif
</div>
