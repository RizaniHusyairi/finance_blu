<div class="modal fade" id="modalInputTransaksi" tabindex="-1" aria-labelledby="modalInputTransaksiLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content bku-modal">
            <div class="modal-header bku-modal__head">
                <div>
                    <h5 class="modal-title" id="modalInputTransaksiLabel"><i class="bi bi-pencil-square me-1"></i> Input Transaksi (Jurnal Manual SILABI)</h5>
                    <p class="bku-modal__sub">Mutasi yang <b>bukan</b> dari pencairan SP2D — Terima UP/TUP, transfer dari Bendahara Penerimaan, bunga, setor pajak, perpindahan tunai↔bank. Kode G1/G2 = perpindahan internal.</p>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                @if($errors->any())
                    <div class="alert alert-danger py-2 small mb-3"><i class="bi bi-exclamation-triangle me-1"></i> Periksa kembali isian: {{ $errors->first() }}</div>
                @endif
                <form method="POST" action="{{ route('pembukuan.input-transaksi.store') }}" class="row g-3 align-items-end">
                    @csrf
                    <div class="col-md-4"><label class="bku-flabel">Tanggal</label><input type="date" name="tanggal" value="{{ old('tanggal') }}" class="form-control" required></div>
                    <div class="col-md-4"><label class="bku-flabel">No. Bukti</label><input type="text" name="no_bukti" value="{{ old('no_bukti') }}" class="form-control" required></div>
                    <div class="col-md-4"><label class="bku-flabel">Jumlah Kotor (Rp)</label><input type="number" step="any" min="0" name="jumlah_kotor" value="{{ old('jumlah_kotor') }}" class="form-control" required></div>
                    <div class="col-12">
                        <label class="bku-flabel">Kode Transaksi</label>
                        <select name="kode_transaksi" class="form-select" required>
                            <option value="">— pilih kode —</option>
                            @foreach(($kodeTransaksiOptions ?? collect()) as $k)
                                <option value="{{ $k->kode }}" @selected(old('kode_transaksi') === $k->kode)>{{ $k->kode }} — {{ $k->uraian }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12"><label class="bku-flabel">Uraian</label><input type="text" name="uraian" value="{{ old('uraian') }}" class="form-control" placeholder="Keterangan transaksi" required></div>
                    <div class="col-12 d-grid"><button class="btn bku-btn-primary"><i class="bi bi-plus-lg me-1"></i> Catat Transaksi</button></div>
                </form>

                @if(($manualJournals ?? collect())->isNotEmpty())
                    <hr class="my-3">
                    <div class="bku-flabel mb-2">Transaksi manual terakhir</div>
                    <div class="bku-table-wrap">
                        <table class="bku-table">
                            <thead><tr><th>Tanggal</th><th>Kode</th><th>No. Bukti</th><th>Uraian</th><th class="ta-end">Jumlah</th><th class="ta-center">Aksi</th></tr></thead>
                            <tbody>
                                @foreach($manualJournals as $j)
                                    <tr>
                                        <td class="text-nowrap">{{ optional($j->tanggal)->format('d/m/Y') }}</td>
                                        <td><span class="bku-pill__code">{{ $j->kode_transaksi }}</span></td>
                                        <td class="small">{{ $j->no_bukti }}</td>
                                        <td class="small">{{ \Illuminate\Support\Str::limit($j->uraian, 50) }}</td>
                                        <td class="ta-end bku-num bku-num--saldo">{{ number_format((float) $j->jumlah_kotor, 0, ',', '.') }}</td>
                                        <td class="ta-center">
                                            <form action="{{ route('pembukuan.input-transaksi.destroy', $j->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Batalkan transaksi ini? Baris BKU terkait akan dihapus.');">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="bku-ico bku-ico--danger" title="Batalkan &amp; hapus"><i class="bi bi-trash3"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
