@php $jenisTransaksiOptions = \App\Enums\JenisTransaksiPenerimaan::options(); @endphp
<div class="modal fade" id="modalMutasi" tabindex="-1" aria-labelledby="modalMutasiLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bku-modal">
            <form method="POST" action="{{ route('pembukuan.penerimaan-manual.store') }}">
                @csrf
                <div class="modal-header bku-modal__head">
                    <div>
                        <h5 class="modal-title" id="modalMutasiLabel"><i class="bi bi-pencil-square me-1"></i> Catat Mutasi Non-Jasa</h5>
                        <p class="bku-modal__sub">PBK / Setor Kas Negara / Pengembalian / Bunga — tanpa kode akun &amp; jenis pelayanan. Saldo dihitung ulang otomatis.</p>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    @if($errors->any())
                        <div class="alert alert-danger py-2 small mb-3"><i class="bi bi-exclamation-triangle me-1"></i> Periksa kembali isian: {{ $errors->first() }}</div>
                    @endif
                    <div class="row g-3">
                        <div class="col-6">
                            <label class="bku-flabel">Tanggal</label>
                            <input type="date" name="tanggal_transaksi" value="{{ old('tanggal_transaksi') }}" class="form-control @error('tanggal_transaksi') is-invalid @enderror" required>
                        </div>
                        <div class="col-6">
                            <label class="bku-flabel">Arah</label>
                            <select name="arus_kas" class="form-select" required>
                                <option value="KREDIT_KELUAR" @selected(old('arus_kas', 'KREDIT_KELUAR') === 'KREDIT_KELUAR')>Keluar (uang keluar)</option>
                                <option value="DEBIT_MASUK" @selected(old('arus_kas') === 'DEBIT_MASUK')>Masuk (uang masuk)</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="bku-flabel">Jenis Mutasi</label>
                            <select name="jenis_transaksi" class="form-select" required>
                                @foreach($jenisTransaksiOptions as $val => $label)
                                    <option value="{{ $val }}" @selected(old('jenis_transaksi') === $val)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="bku-flabel">No. Bukti</label>
                            <input type="text" name="nomor_bukti" value="{{ old('nomor_bukti') }}" class="form-control @error('nomor_bukti') is-invalid @enderror" placeholder="mis. PBK/241004…" required>
                        </div>
                        <div class="col-6">
                            <label class="bku-flabel">Nominal (Rp)</label>
                            <input type="number" step="any" min="0" name="nominal" value="{{ old('nominal') }}" class="form-control @error('nominal') is-invalid @enderror" placeholder="0" required>
                        </div>
                        <div class="col-12">
                            <label class="bku-flabel">Uraian</label>
                            <input type="text" name="uraian" value="{{ old('uraian') }}" class="form-control @error('uraian') is-invalid @enderror" placeholder="Keterangan mutasi" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bku-modal__foot">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn bku-btn-primary"><i class="bi bi-check-lg me-1"></i> Simpan Mutasi</button>
                </div>
            </form>
        </div>
    </div>
</div>
