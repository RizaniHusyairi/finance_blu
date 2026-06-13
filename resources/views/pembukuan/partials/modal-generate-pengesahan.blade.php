{{--
    Modal "Buat Laporan Periode" — generator record laporan_pengesahan_blu.
    Dipakai bersama oleh index Buku Pengesahan Belanja dan Buku Pengesahan
    Pendapatan (satu record per periode untuk kedua buku).
    Membutuhkan: $months (array bulan dari PembukuanService::monthOptions()).
--}}
<div class="modal fade" id="modalGeneratePengesahan" tabindex="-1" aria-labelledby="modalGeneratePengesahanLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ route('pembukuan.pengesahan.generate') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="modalGeneratePengesahanLabel">
                        <i class="bi bi-journal-plus me-2"></i>Buat Laporan Periode
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info d-flex gap-2 align-items-start" data-sky-ignore>
                        <i class="bi bi-info-circle-fill mt-1"></i>
                        <div class="small mb-0">
                            Laporan dihitung dari transaksi <strong>Buku Kas Umum</strong> pada periode terpilih
                            (total penerimaan, total pengeluaran, dan saldo akhir BLU), lalu disimpan berstatus
                            <strong>DRAFT</strong>. Satu periode hanya bisa punya satu laporan.
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Bulan <span class="text-danger">*</span></label>
                        <select name="bulan" class="form-select @error('bulan') is-invalid @enderror" required>
                            <option value="">— Pilih Bulan —</option>
                            @foreach($months as $number => $label)
                                <option value="{{ $number }}"
                                    @selected((string) old('bulan', now()->subMonthNoOverflow()->month) === (string) $number)>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                        @error('bulan')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-1">
                        <label class="form-label fw-semibold small">Tahun <span class="text-danger">*</span></label>
                        <input type="number" name="tahun" min="2000" max="2100"
                               class="form-control @error('tahun') is-invalid @enderror"
                               value="{{ old('tahun', now()->subMonthNoOverflow()->year) }}" required>
                        @error('tahun')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-check2-circle me-1"></i>Buat Laporan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('script')
<script>
    // Buka kembali modal generator otomatis bila ada error validasi dari submit sebelumnya.
    @if($errors->has('bulan') || $errors->has('tahun'))
    document.addEventListener('DOMContentLoaded', function () {
        var el = document.getElementById('modalGeneratePengesahan');
        if (el && window.bootstrap) {
            bootstrap.Modal.getOrCreateInstance(el).show();
        }
    });
    @endif
</script>
@endpush
