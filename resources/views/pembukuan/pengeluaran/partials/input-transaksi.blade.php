<div class="card mb-3">
    <div class="card-header fw-bold"><i class="bi bi-pencil-square text-secondary"></i> Input Transaksi (Jurnal Manual SILABI)</div>
    <div class="card-body">
        <p class="text-muted small mb-3">
            Catat mutasi yang <b>bukan</b> dari pencairan SP2D — termasuk dana masuk non-tagihan
            (Terima UP/TUP, transfer dari Bendahara Penerimaan, bunga rekening, pengembalian belanja, retur),
            setor pajak, dan perpindahan tunai↔bank. Pilih <b>kode transaksi</b>; sistem mendistribusikan ke
            BKU &amp; buku pembantu otomatis. <span class="fst-italic">Kode G1/G2 = perpindahan internal (saling meniadakan di BKU).</span>
        </p>
        <form method="POST" action="{{ route('pembukuan.input-transaksi.store') }}" class="row g-2 align-items-end">
            @csrf
            <div class="col-md-2"><label class="form-label small fw-semibold">Tanggal</label><input type="date" name="tanggal" value="{{ old('tanggal') }}" class="form-control form-control-sm" required></div>
            <div class="col-md-2"><label class="form-label small fw-semibold">No. Bukti</label><input type="text" name="no_bukti" value="{{ old('no_bukti') }}" class="form-control form-control-sm" required></div>
            <div class="col-md-5">
                <label class="form-label small fw-semibold">Kode Transaksi</label>
                <select name="kode_transaksi" class="form-select form-select-sm" required>
                    <option value="">— pilih kode —</option>
                    @foreach(($kodeTransaksiOptions ?? collect()) as $k)
                        <option value="{{ $k->kode }}" @selected(old('kode_transaksi') === $k->kode)>{{ $k->kode }} — {{ $k->uraian }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3"><label class="form-label small fw-semibold">Jumlah Kotor (Rp)</label><input type="number" step="any" min="0" name="jumlah_kotor" value="{{ old('jumlah_kotor') }}" class="form-control form-control-sm" required></div>
            <div class="col-md-9"><label class="form-label small fw-semibold">Uraian</label><input type="text" name="uraian" value="{{ old('uraian') }}" class="form-control form-control-sm" placeholder="Keterangan transaksi" required></div>
            <div class="col-md-3 d-grid"><button class="btn btn-sm btn-outline-primary"><i class="bi bi-plus-lg me-1"></i>Catat Transaksi</button></div>
        </form>

        @if(($manualJournals ?? collect())->isNotEmpty())
            <hr class="my-3">
            <div class="small fw-semibold text-muted mb-2">Transaksi manual terakhir</div>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light"><tr><th>Tanggal</th><th>Kode</th><th>No. Bukti</th><th>Uraian</th><th class="text-end">Jumlah</th><th class="text-center">Aksi</th></tr></thead>
                    <tbody>
                        @foreach($manualJournals as $j)
                            <tr>
                                <td class="text-nowrap">{{ optional($j->tanggal)->format('d/m/Y') }}</td>
                                <td><span class="badge bg-light text-dark" title="{{ $j->kodeTransaksi?->uraian }}">{{ $j->kode_transaksi }}</span></td>
                                <td class="small">{{ $j->no_bukti }}</td>
                                <td class="small">{{ \Illuminate\Support\Str::limit($j->uraian, 50) }}</td>
                                <td class="text-end">Rp {{ number_format((float) $j->jumlah_kotor, 0, ',', '.') }}</td>
                                <td class="text-center">
                                    <form action="{{ route('pembukuan.input-transaksi.destroy', $j->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Batalkan transaksi ini? Baris BKU terkait akan dihapus.');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-1" title="Batalkan & hapus"><i class="bi bi-trash"></i></button>
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
