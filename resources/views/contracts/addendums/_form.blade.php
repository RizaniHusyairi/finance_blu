@php
    $oldNilai = (float) ($addendum->nilai_kontrak_lama ?? $contract->nilai_total_kontrak ?? 0);
    $oldTanggalSelesai = old('tanggal_selesai_baru', optional($addendum->tanggal_selesai_baru)->format('Y-m-d') ?? optional($addendum->tanggal_selesai_lama)->format('Y-m-d') ?? $contract->tanggal_selesai);
    $oldJangkaWaktu = old('jangka_waktu_baru', $addendum->jangka_waktu_baru ?? $addendum->jangka_waktu_lama ?? $contract->jangka_waktu);
    $oldNilaiBaru = old('nilai_kontrak_baru', $addendum->nilai_kontrak_baru ?? $oldNilai);
    $selectedJenis = old('jenis_addendum', $addendum->jenis_addendum);
    $documentLabels = [
        'FILE_ADDENDUM' => 'File Addendum',
        'NOTA_DINAS' => 'Nota Dinas / Justifikasi',
        'DOKUMEN_PENDUKUNG_TEKNIS' => 'Dokumen Pendukung Teknis',
        'LAMPIRAN_SPESIFIKASI' => 'Lampiran Perubahan Spesifikasi',
    ];
@endphp

@if ($errors->any())
    <div class="alert alert-danger border-0 shadow-sm rounded-4">
        <div class="fw-bold mb-2"><i class="bi bi-exclamation-triangle-fill me-2"></i>Terdapat data yang perlu diperbaiki</div>
        <ul class="mb-0 ps-3">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form action="{{ $formAction }}" method="POST" enctype="multipart/form-data">
    @csrf
    @if ($formMethod !== 'POST')
        @method($formMethod)
    @endif

    <div class="row g-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4">
                    <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 align-items-lg-center">
                        <div>
                            <h4 class="fw-bold mb-1">{{ $pageTitle }}</h4>
                            <div class="text-muted">{{ $pageSubtitle }}</div>
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                            <a href="{{ route('addendums.index', $contract) }}" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left me-1"></i> Kembali
                            </a>
                            <a href="{{ route('contracts.show', $contract) }}" class="btn btn-light border">
                                <i class="bi bi-file-earmark-text me-1"></i> Detail Kontrak
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white border-0 p-4 pb-0">
                    <h6 class="fw-bold mb-1">Informasi Kontrak Sumber</h6>
                    <div class="text-muted small">Nilai lama addendum selalu mengikuti kontrak aktif yang menjadi dasar perubahan.</div>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="border rounded-4 p-3 h-100 bg-light">
                                <div class="small text-muted mb-1">Nomor SPK</div>
                                <div class="fw-bold">{{ $contract->nomor_spk ?? '-' }}</div>
                                <div class="small text-muted mt-2">Status: {{ $contract->status_kontrak ?? '-' }}</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="border rounded-4 p-3 h-100 bg-light">
                                <div class="small text-muted mb-1">Nama Pekerjaan</div>
                                <div class="fw-bold">{{ $contract->nama_pekerjaan ?? '-' }}</div>
                                <div class="small text-muted mt-2">{{ optional($contract->vendor)->nama_pihak ?? optional($contract->vendor)->nama_perusahaan ?? 'Vendor belum terhubung' }}</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="border rounded-4 p-3 h-100 bg-light">
                                <div class="small text-muted mb-1">Nilai / Waktu Aktif</div>
                                <div class="fw-bold text-success">Rp {{ number_format((float) ($contract->nilai_total_kontrak ?? 0), 0, ',', '.') }}</div>
                                <div class="small text-muted mt-2">
                                    Selesai: {{ $contract->tanggal_selesai ? \Carbon\Carbon::parse($contract->tanggal_selesai)->translatedFormat('d M Y') : '-' }}
                                </div>
                                <div class="small text-muted">Jangka: {{ number_format((int) ($contract->jangka_waktu ?? 0), 0, ',', '.') }} {{ strtolower($contract->satuan_waktu ?? 'hari') }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white border-0 p-4 pb-0">
                    <h6 class="fw-bold mb-1">Informasi Addendum</h6>
                    <div class="text-muted small">Isi alasan perubahan dan sesuaikan nilai sesudah addendum dengan jenis addendum yang dipilih.</div>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Nomor Addendum <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('nomor_addendum') is-invalid @enderror" name="nomor_addendum" value="{{ old('nomor_addendum', $addendum->nomor_addendum) }}" placeholder="Contoh: ADD-01/SPK/2026" required>
                            @error('nomor_addendum')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Tanggal Addendum <span class="text-danger">*</span></label>
                            <input type="date" class="form-control @error('tanggal_addendum') is-invalid @enderror" name="tanggal_addendum" value="{{ old('tanggal_addendum', optional($addendum->tanggal_addendum)->format('Y-m-d') ?? now()->toDateString()) }}" required>
                            @error('tanggal_addendum')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Jenis Addendum <span class="text-danger">*</span></label>
                            <select class="form-select @error('jenis_addendum') is-invalid @enderror" name="jenis_addendum" id="jenisAddendum" required>
                                @foreach ($jenisOptions as $jenis)
                                    <option value="{{ $jenis }}" @selected($selectedJenis === $jenis)>{{ str_replace('_', ' ', $jenis) }}</option>
                                @endforeach
                            </select>
                            @error('jenis_addendum')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Catatan Perubahan Spesifikasi</label>
                            <input type="text" class="form-control @error('catatan_perubahan_spesifikasi') is-invalid @enderror" name="catatan_perubahan_spesifikasi" id="catatanSpesifikasi" value="{{ old('catatan_perubahan_spesifikasi', $addendum->catatan_perubahan_spesifikasi) }}" placeholder="Contoh: penyesuaian ruang lingkup atau spesifikasi teknis">
                            @error('catatan_perubahan_spesifikasi')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Alasan / Keterangan Addendum <span class="text-danger">*</span></label>
                            <textarea class="form-control @error('keterangan_alasan') is-invalid @enderror" name="keterangan_alasan" rows="4" required placeholder="Jelaskan latar belakang perubahan addendum ini.">{{ old('keterangan_alasan', $addendum->keterangan_alasan) }}</textarea>
                            @error('keterangan_alasan')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white border-0 p-4 pb-0">
                    <h6 class="fw-bold mb-1">Nilai Sebelum dan Sesudah Addendum</h6>
                    <div class="text-muted small">Kolom sebelum addendum hanya baca. Kolom sesudah addendum akan diringkas otomatis di panel sebelah kanan.</div>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Nilai Kontrak Lama</label>
                            <input type="text" class="form-control" value="Rp {{ number_format($oldNilai, 0, ',', '.') }}" readonly>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Tanggal Selesai Lama</label>
                            <input type="text" class="form-control" value="{{ $contract->tanggal_selesai ? \Carbon\Carbon::parse($contract->tanggal_selesai)->translatedFormat('d M Y') : '-' }}" readonly>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Jangka Waktu Lama</label>
                            <input type="text" class="form-control" value="{{ number_format((int) ($contract->jangka_waktu ?? 0), 0, ',', '.') }} {{ strtolower($contract->satuan_waktu ?? 'hari') }}" readonly>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Nilai Kontrak Baru</label>
                            <input type="number" step="0.01" min="0" class="form-control @error('nilai_kontrak_baru') is-invalid @enderror" name="nilai_kontrak_baru" id="nilaiKontrakBaru" value="{{ $oldNilaiBaru }}" placeholder="Isi jika nilai berubah">
                            @error('nilai_kontrak_baru')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Tanggal Selesai Baru</label>
                            <input type="date" class="form-control @error('tanggal_selesai_baru') is-invalid @enderror" name="tanggal_selesai_baru" id="tanggalSelesaiBaru" value="{{ $oldTanggalSelesai }}">
                            @error('tanggal_selesai_baru')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Jangka Waktu Baru</label>
                            <input type="number" min="1" class="form-control @error('jangka_waktu_baru') is-invalid @enderror" name="jangka_waktu_baru" id="jangkaWaktuBaru" value="{{ $oldJangkaWaktu }}" placeholder="Isi jika jangka waktu berubah">
                            @error('jangka_waktu_baru')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white border-0 p-4 pb-0">
                    <h6 class="fw-bold mb-1">Dokumen Pendukung</h6>
                    <div class="text-muted small">Unggah file yang relevan bila tersedia. File baru akan menggantikan file aktif sebelumnya pada jenis dokumen yang sama.</div>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">File Addendum</label>
                            <input type="file" class="form-control @error('file_addendum') is-invalid @enderror" name="file_addendum" accept=".pdf,.doc,.docx">
                            @error('file_addendum')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Nota Dinas / Justifikasi</label>
                            <input type="file" class="form-control @error('nota_dinas_file') is-invalid @enderror" name="nota_dinas_file" accept=".pdf,.doc,.docx">
                            @error('nota_dinas_file')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Dokumen Pendukung Teknis</label>
                            <input type="file" class="form-control @error('dokumen_pendukung_teknis_file') is-invalid @enderror" name="dokumen_pendukung_teknis_file" accept=".pdf,.doc,.docx">
                            @error('dokumen_pendukung_teknis_file')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Lampiran Perubahan Spesifikasi</label>
                            <input type="file" class="form-control @error('lampiran_spesifikasi_file') is-invalid @enderror" name="lampiran_spesifikasi_file" accept=".pdf,.doc,.docx">
                            @error('lampiran_spesifikasi_file')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    @if($addendum->exists && $addendum->arsipDokumen->isNotEmpty())
                        <div class="border-top mt-4 pt-4">
                            <div class="small text-muted text-uppercase fw-bold mb-3">Dokumen Aktif Saat Ini</div>
                            <div class="row g-3">
                                @foreach ($addendum->arsipDokumen->where('is_active', true) as $arsip)
                                    <div class="col-md-6">
                                        <div class="border rounded-4 p-3 h-100 bg-light">
                                            <div class="small text-muted mb-1">{{ $documentLabels[$arsip->jenis_dokumen] ?? $arsip->jenis_dokumen }}</div>
                                            <div class="fw-semibold text-truncate">{{ $arsip->nama_file_asli }}</div>
                                            <a href="{{ Storage::url($arsip->path_file) }}" target="_blank" class="btn btn-sm btn-outline-primary mt-2">
                                                <i class="bi bi-eye me-1"></i> Lihat Dokumen
                                            </a>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 mb-4 position-sticky" style="top: 20px;">
                <div class="card-header bg-white border-0 p-4 pb-0">
                    <h6 class="fw-bold mb-1">Ringkasan Perubahan</h6>
                    <div class="text-muted small">Panel ini membantu melihat dampak addendum sebelum disimpan atau diajukan.</div>
                </div>
                <div class="card-body p-4">
                    <div class="border rounded-4 p-3 bg-light mb-3">
                        <div class="small text-muted mb-1">Selisih Nilai Kontrak</div>
                        <div class="fw-bold" id="deltaNilaiLabel">Rp 0</div>
                    </div>
                    <div class="border rounded-4 p-3 bg-light mb-3">
                        <div class="small text-muted mb-1">Perubahan Jangka Waktu</div>
                        <div class="fw-bold" id="deltaWaktuLabel">0</div>
                    </div>
                    <div class="border rounded-4 p-3 bg-light">
                        <div class="small text-muted mb-2">Impact Summary</div>
                        <div class="d-flex flex-column gap-2 small">
                            <span class="badge bg-light text-dark border text-start" id="impactNilai">Nilai kontrak tetap</span>
                            <span class="badge bg-light text-dark border text-start" id="impactWaktu">Waktu kontrak tetap</span>
                            <span class="badge bg-light text-dark border text-start" id="impactSpesifikasi">Spesifikasi tidak berubah</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4 d-grid gap-2">
                    <button type="submit" name="action" value="draft" class="btn btn-primary fw-bold">
                        <i class="bi bi-save me-1"></i> Simpan Draft
                    </button>
                    <button type="submit" name="action" value="submit" class="btn btn-success fw-bold">
                        <i class="bi bi-send-check me-1"></i> Simpan & Ajukan
                    </button>
                    <a href="{{ route('addendums.index', $contract) }}" class="btn btn-outline-secondary">
                        Batal
                    </a>
                </div>
            </div>
        </div>
    </div>
</form>

@push('script')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const nilaiLama = {{ json_encode((float) ($contract->nilai_total_kontrak ?? 0)) }};
            const jangkaLama = {{ json_encode((int) ($contract->jangka_waktu ?? 0)) }};
            const jenisInput = document.getElementById('jenisAddendum');
            const nilaiBaruInput = document.getElementById('nilaiKontrakBaru');
            const jangkaBaruInput = document.getElementById('jangkaWaktuBaru');
            const catatanSpesifikasi = document.getElementById('catatanSpesifikasi');
            const deltaNilaiLabel = document.getElementById('deltaNilaiLabel');
            const deltaWaktuLabel = document.getElementById('deltaWaktuLabel');
            const impactNilai = document.getElementById('impactNilai');
            const impactWaktu = document.getElementById('impactWaktu');
            const impactSpesifikasi = document.getElementById('impactSpesifikasi');

            const rupiah = new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR',
                maximumFractionDigits: 0
            });

            function updateSummary() {
                const nilaiBaru = parseFloat(nilaiBaruInput.value || nilaiLama);
                const jangkaBaru = parseInt(jangkaBaruInput.value || jangkaLama, 10);
                const deltaNilai = nilaiBaru - nilaiLama;
                const deltaWaktu = jangkaBaru - jangkaLama;
                const jenis = jenisInput.value;
                const hasSpec = (catatanSpesifikasi.value || '').trim().length > 0 || ['GANTI_SPESIFIKASI', 'KOMBINASI'].includes(jenis);

                deltaNilaiLabel.textContent = rupiah.format(deltaNilai);
                deltaNilaiLabel.className = 'fw-bold ' + (deltaNilai > 0 ? 'text-success' : (deltaNilai < 0 ? 'text-danger' : 'text-dark'));

                deltaWaktuLabel.textContent = deltaWaktu + ' {{ strtolower($contract->satuan_waktu ?? 'hari') }}';
                deltaWaktuLabel.className = 'fw-bold ' + (deltaWaktu > 0 ? 'text-warning' : (deltaWaktu < 0 ? 'text-danger' : 'text-dark'));

                impactNilai.textContent = deltaNilai > 0 ? 'Nilai kontrak bertambah' : (deltaNilai < 0 ? 'Nilai kontrak berkurang' : 'Nilai kontrak tetap');
                impactWaktu.textContent = deltaWaktu > 0 ? 'Waktu kontrak bertambah' : (deltaWaktu < 0 ? 'Waktu kontrak berkurang' : 'Waktu kontrak tetap');
                impactSpesifikasi.textContent = hasSpec ? 'Spesifikasi / ruang lingkup berubah' : 'Spesifikasi tidak berubah';
            }

            [jenisInput, nilaiBaruInput, jangkaBaruInput, catatanSpesifikasi].forEach(function (element) {
                element.addEventListener('input', updateSummary);
                element.addEventListener('change', updateSummary);
            });

            updateSummary();
        });
    </script>
@endpush
