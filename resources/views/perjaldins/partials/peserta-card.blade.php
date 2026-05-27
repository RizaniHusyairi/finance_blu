<div class="card mb-3 shadow-sm border-0 peserta-card item-row">
    <div class="card-header bg-light d-flex justify-content-between align-items-center py-2 collapse-trigger" style="cursor: pointer;" data-bs-toggle="collapse" data-bs-target="#collapsePeserta{{ $index }}" aria-expanded="true">
        <div>
            <h6 class="mb-0 text-primary fw-bold">
                <i class="bi bi-person-badge me-2"></i>Peserta #<span class="row-number">{{ $index + 1 }}</span>
            </h6>
            <small class="text-muted summary-info">
                Nama: <span class="summary-nama">{{ $row['nama_pegawai'] ?? '-' }}</span> | 
                Tujuan: <span class="summary-tujuan">{{ $row['tujuan'] ?? '-' }}</span> | 
                Total: Rp <span class="summary-total">0</span>
            </small>
        </div>
        <div class="d-flex align-items-center gap-3">
            <span class="badge bg-{{ isset($row['spt_file_path']) ? 'success' : 'secondary' }} file-status-badge">
                <i class="bi bi-paperclip"></i> SPT {{ isset($row['spt_file_path']) ? 'Terlampir' : 'Kosong' }}
            </span>
            <i class="bi bi-chevron-down toggle-icon text-secondary"></i>
            <button type="button" class="btn btn-sm btn-outline-danger btn-delete-row" title="Hapus Peserta">
                <i class="bi bi-trash"></i>
            </button>
        </div>
    </div>

    <div id="collapsePeserta{{ $index }}" class="collapse show peserta-collapse">
        <div class="card-body p-3">
            <input type="hidden" name="peserta[{{ $index }}][detail_id]" class="detail-id-input" value="{{ $row['id'] ?? '' }}">
            
            <div class="row g-3">
                <!-- 1. Informasi Pegawai -->
                <div class="col-md-4 border-end">
                    <h6 class="text-secondary border-bottom pb-1"><i class="bi bi-person me-1"></i> 1. Informasi Pegawai</h6>
                    <div class="mb-2">
                        <label class="form-label mb-1 small">Nama Pegawai <span class="text-danger">*</span></label>
                        <select class="form-select form-select-sm select2 pegawai-select @error("peserta.{$index}.nama_pegawai") is-invalid @enderror" name="peserta[{{ $index }}][pegawai_id]" required>
                            <option value="">-- Pilih Pegawai --</option>
                            @foreach($masterPegawai as $peg)
                                <option value="{{ $peg->id }}"
                                    data-nama="{{ $peg->nama_lengkap }}"
                                    data-nip="{{ $peg->nip }}"
                                    data-rek="{{ $peg->nomor_rekening }}"
                                    data-namarek="{{ $peg->nama_rekening }}"
                                    {{ (isset($row['nama_pegawai']) && $peg->nama_lengkap == ($row['nama_pegawai'] ?? '')) ? 'selected' : '' }}
                                >
                                    {{ $peg->nama_lengkap }} {{ $peg->nip ? '(' . $peg->nip . ')' : '' }}
                                </option>
                            @endforeach
                        </select>
                        <input type="hidden" class="input-nama-hidden" name="peserta[{{ $index }}][nama_pegawai]" value="{{ $row['nama_pegawai'] ?? '' }}">
                    </div>
                    <div class="mb-2">
                        <label class="form-label mb-1 small">NIP</label>
                        <input type="text" class="form-control form-control-sm nip-input" name="peserta[{{ $index }}][nip]" placeholder="Otomatis terisi" value="{{ $row['nip'] ?? '' }}" readonly>
                    </div>
                    <div class="mb-2">
                        <label class="form-label mb-1 small">Rekening</label>
                        <input type="text" class="form-control form-control-sm rekening-input" name="peserta[{{ $index }}][rekening]" placeholder="Rekening (Opsional)" value="{{ $row['rekening'] ?? '' }}">
                        <small class="text-muted font-10 rek-hint d-none"><i class="bi bi-info-circle"></i> Tidak ada data rekening, silakan isi manual.</small>
                    </div>
                </div>

                <!-- 2. Dokumen Perjalanan -->
                <div class="col-md-4 border-end">
                    <h6 class="text-secondary border-bottom pb-1"><i class="bi bi-folder2-open me-1"></i> 2. Dokumen Perjalanan</h6>
                    <div class="mb-2">
                        <label class="form-label mb-1 small">No. SPT <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-sm @error("peserta.{$index}.no_spt") is-invalid @enderror" name="peserta[{{ $index }}][no_spt]" placeholder="Nomor SPT" required value="{{ $row['no_spt'] ?? '' }}">
                    </div>
                    <div class="mb-2">
                        <label class="form-label mb-1 small">No. SPPD <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-sm @error("peserta.{$index}.no_sppd") is-invalid @enderror" name="peserta[{{ $index }}][no_sppd]" placeholder="Nomor SPPD" required value="{{ $row['no_sppd'] ?? '' }}">
                    </div>
                    <div class="mb-2">
                        <label class="form-label mb-1 small">Upload / Lampiran SPT</label>
                        <input type="file" class="form-control form-control-sm spt-file-input @error("peserta.{$index}.spt_file") is-invalid @enderror" name="peserta[{{ $index }}][spt_file]" accept=".pdf,.jpg,.jpeg,.png">
                        @if(isset($row['spt_file_path']))
                            <small class="text-success d-block mt-1 file-existing-notice">
                                <i class="bi bi-check-circle"></i> File tersimpan: <a href="{{ Storage::url($row['spt_file_path']) }}" target="_blank">{{ $row['spt_file_name'] ?? 'Lihat Dokumen' }}</a>
                            </small>
                            <small class="text-muted file-existing-notice">Abaikan jika tak diubah.</small>
                        @endif
                        <small class="text-muted d-block mt-1">Maks. 5MB (PDF/JPG/PNG)</small>
                    </div>
                </div>

                <!-- 3. Detail Perjalanan -->
                <div class="col-md-4">
                    <h6 class="text-secondary border-bottom pb-1"><i class="bi bi-geo-alt me-1"></i> 3. Detail Perjalanan</h6>
                    <div class="mb-2">
                        <label class="form-label mb-1 small">Kota / Provinsi <span class="text-danger">*</span></label>
                        <select class="form-select form-select-sm provinsi-select @error("peserta.{$index}.provinsi_id") is-invalid @enderror" name="peserta[{{ $index }}][provinsi_id]" required>
                            <option value="">-- Pilih Kota/Provinsi --</option>
                            @foreach($masterProvinsi as $prov)
                                <option value="{{ $prov->id }}" data-luar="{{ $prov->luar_kota }}" data-dalam="{{ $prov->dalam_kota_lebih_8_jam }}" data-diklat="{{ $prov->diklat }}" {{ (isset($row['provinsi_id']) && $row['provinsi_id'] == $prov->id) ? 'selected' : '' }}>
                                    {{ $prov->provinsi }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label mb-1 small">Tipe Perjalanan <span class="text-danger">*</span></label>
                        <select class="form-select form-select-sm tipe-select @error("peserta.{$index}.tipe_perjalanan") is-invalid @enderror" name="peserta[{{ $index }}][tipe_perjalanan]" required>
                            <option value="">-- Pilih Tipe --</option>
                            <option value="luar_kota" {{ (isset($row['tipe_perjalanan']) && $row['tipe_perjalanan'] == 'luar_kota') ? 'selected' : '' }}>Luar Kota</option>
                            <option value="dalam_kota_lebih_8_jam" {{ (isset($row['tipe_perjalanan']) && $row['tipe_perjalanan'] == 'dalam_kota_lebih_8_jam') ? 'selected' : '' }}>Dalam Kota > 8 Jam</option>
                            <option value="diklat" {{ (isset($row['tipe_perjalanan']) && $row['tipe_perjalanan'] == 'diklat') ? 'selected' : '' }}>Diklat</option>
                        </select>
                    </div>
                    <div class="row g-2 mb-2">
                        <div class="col-8">
                            <label class="form-label mb-1 small">Berangkat <span class="text-danger">*</span></label>
                            <input type="date" class="form-control form-control-sm @error("peserta.{$index}.tgl_berangkat") is-invalid @enderror" name="peserta[{{ $index }}][tgl_berangkat]" required value="{{ isset($row['tgl_berangkat']) ? \Carbon\Carbon::parse($row['tgl_berangkat'])->format('Y-m-d') : '' }}">
                        </div>
                        <div class="col-4">
                            <label class="form-label mb-1 small">Hari <span class="text-danger">*</span></label>
                            <input type="number" class="form-control form-control-sm text-center lama-hari-input @error("peserta.{$index}.lama_hari") is-invalid @enderror" name="peserta[{{ $index }}][lama_hari]" placeholder="1" required min="1" value="{{ $row['lama_hari'] ?? '' }}">
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label mb-1 small">Tujuan Spesifik / Tempat (Opsional)</label>
                        <textarea class="form-control form-control-sm input-tujuan" name="peserta[{{ $index }}][tujuan]" rows="2" placeholder="Nama Tempat/Instansi...">{{ $row['tujuan'] ?? '' }}</textarea>
                    </div>
                </div>
            </div>

            <!-- 4. Rincian Biaya -->
            <div class="mt-3 bg-light p-2 rounded border">
                <h6 class="text-secondary border-bottom pb-1 mb-2"><i class="bi bi-cash-coin me-1"></i> 4. Rincian Biaya (Rp)</h6>
                <div class="row g-2 align-items-stretch">
                    {{-- Biaya Transportasi & Penginapan --}}
                    <div class="col-md-2">
                        <label class="form-label mb-1 small" style="font-size: 0.75rem;">Tiket</label>
                        <input type="text" data-kode="TIKET" class="form-control form-control-sm text-end biaya-input tiket-amount-input komponen-input @error("peserta.{$index}.biaya_tiket") is-invalid @enderror" name="peserta[{{ $index }}][biaya_tiket]" placeholder="0" onkeyup="calculateJumlah(this); toggleBuktiFile(this, 'tiket');" value="{{ isset($row['biaya_tiket']) ? (int) $row['biaya_tiket'] : '' }}">
                        <div class="tiket-file-wrapper mt-1 {{ ((int)($row['biaya_tiket'] ?? 0) > 0) ? '' : 'd-none' }}">
                            <input type="file" class="form-control form-control-sm tiket-file-input @error("peserta.{$index}.tiket_file") is-invalid @enderror" name="peserta[{{ $index }}][tiket_file]" accept=".pdf,.jpg,.jpeg,.png" style="font-size: 0.7rem;">
                            @if(isset($row['tiket_file_path']))
                                <small class="text-success d-block mt-1 tiket-existing-notice" style="font-size: 0.65rem;">
                                    <i class="bi bi-check-circle"></i> <a href="{{ Storage::url($row['tiket_file_path']) }}" target="_blank">{{ $row['tiket_file_name'] ?? 'Lihat Tiket' }}</a>
                                </small>
                            @endif
                            <small class="text-muted d-block" style="font-size: 0.65rem;">Bukti tiket (PDF/JPG/PNG, maks 5MB)</small>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label mb-1 small" style="font-size: 0.75rem;">Transport</label>
                        <input type="text" data-kode="TRANSPORT" class="form-control form-control-sm text-end biaya-input transport-amount-input komponen-input @error("peserta.{$index}.biaya_transport") is-invalid @enderror" name="peserta[{{ $index }}][biaya_transport]" placeholder="0" onkeyup="calculateJumlah(this); toggleBuktiFile(this, 'transport');" value="{{ isset($row['biaya_transport']) ? (int) $row['biaya_transport'] : '' }}">
                        <div class="transport-file-wrapper mt-1 {{ ((int)($row['biaya_transport'] ?? 0) > 0) ? '' : 'd-none' }}">
                            <input type="file" class="form-control form-control-sm transport-file-input @error("peserta.{$index}.transport_file") is-invalid @enderror" name="peserta[{{ $index }}][transport_file]" accept=".pdf,.jpg,.jpeg,.png" style="font-size: 0.7rem;">
                            @if(isset($row['transport_file_path']))
                                <small class="text-success d-block mt-1 transport-existing-notice" style="font-size: 0.65rem;">
                                    <i class="bi bi-check-circle"></i> <a href="{{ Storage::url($row['transport_file_path']) }}" target="_blank">{{ $row['transport_file_name'] ?? 'Lihat Bukti' }}</a>
                                </small>
                            @endif
                            <small class="text-muted d-block" style="font-size: 0.65rem;">Bukti transport (PDF/JPG/PNG, maks 5MB)</small>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label mb-1 small" style="font-size: 0.75rem;">Penginapan</label>
                        <input type="text" data-kode="PENGINAPAN" class="form-control form-control-sm text-end biaya-input penginapan-amount-input komponen-input @error("peserta.{$index}.biaya_penginapan") is-invalid @enderror" name="peserta[{{ $index }}][biaya_penginapan]" placeholder="0" onkeyup="calculateJumlah(this); toggleBuktiFile(this, 'penginapan');" value="{{ isset($row['biaya_penginapan']) ? (int) $row['biaya_penginapan'] : '' }}">
                        <div class="penginapan-file-wrapper mt-1 {{ ((int)($row['biaya_penginapan'] ?? 0) > 0) ? '' : 'd-none' }}">
                            <input type="file" class="form-control form-control-sm penginapan-file-input @error("peserta.{$index}.penginapan_file") is-invalid @enderror" name="peserta[{{ $index }}][penginapan_file]" accept=".pdf,.jpg,.jpeg,.png" style="font-size: 0.7rem;">
                            @if(isset($row['penginapan_file_path']))
                                <small class="text-success d-block mt-1 penginapan-existing-notice" style="font-size: 0.65rem;">
                                    <i class="bi bi-check-circle"></i> <a href="{{ Storage::url($row['penginapan_file_path']) }}" target="_blank">{{ $row['penginapan_file_name'] ?? 'Lihat Bukti' }}</a>
                                </small>
                            @endif
                            <small class="text-muted d-block" style="font-size: 0.65rem;">Bukti penginapan (PDF/JPG/PNG, maks 5MB)</small>
                        </div>
                    </div>

                    {{-- Grup Uang Harian (Uang Harian + Representasi + Rapat) --}}
                    <div class="col-md-4">
                        <div class="border rounded p-2 bg-white h-100 uang-harian-group">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label fw-semibold text-primary mb-0" style="font-size: 0.75rem;">
                                    <i class="bi bi-wallet2 me-1"></i> Uang Harian
                                </label>
                                <small class="text-muted" style="font-size: 0.65rem;">
                                    Total: Rp <span class="uang-harian-total fw-bold text-primary">0</span>
                                </small>
                            </div>
                            <div class="row g-1">
                                <div class="col-4">
                                    <label class="form-label mb-1 text-muted" style="font-size: 0.65rem;">Harian (Auto)</label>
                                    <input type="text" data-kode="UANG_HARIAN" class="form-control form-control-sm text-end biaya-input uang-harian-input uang-harian-component komponen-input @error("peserta.{$index}.uang_harian") is-invalid @enderror" name="peserta[{{ $index }}][uang_harian]" placeholder="0" onkeyup="calculateJumlah(this)" value="{{ isset($row['uang_harian']) ? (int) $row['uang_harian'] : '' }}">
                                </div>
                                <div class="col-4">
                                    <label class="form-label mb-1 text-muted" style="font-size: 0.65rem;">+ Representasi</label>
                                    <input type="text" data-kode="UANG_HARIAN" class="form-control form-control-sm text-end biaya-input uang-harian-component komponen-input @error("peserta.{$index}.uang_representasi") is-invalid @enderror" name="peserta[{{ $index }}][uang_representasi]" placeholder="0" onkeyup="calculateJumlah(this)" value="{{ isset($row['uang_representasi']) ? (int) $row['uang_representasi'] : '' }}">
                                </div>
                                <div class="col-4">
                                    <label class="form-label mb-1 text-muted" style="font-size: 0.65rem;">+ Rapat </label>
                                    <input type="text" data-kode="UANG_HARIAN" class="form-control form-control-sm text-end biaya-input uang-harian-component komponen-input @error("peserta.{$index}.uang_rapat") is-invalid @enderror" name="peserta[{{ $index }}][uang_rapat]" placeholder="0" onkeyup="calculateJumlah(this)" value="{{ isset($row['uang_rapat']) && (int) $row['uang_rapat'] > 0 ? (int) $row['uang_rapat'] : '' }}">
                                </div>
                            </div>
                            @php
                                $uhGroupTotal = (int)($row['uang_harian'] ?? 0) + (int)($row['uang_representasi'] ?? 0) + (int)($row['uang_rapat'] ?? 0);
                            @endphp
                            <div class="uang-harian-file-wrapper mt-2 {{ $uhGroupTotal > 0 ? '' : 'd-none' }}">
                                <input type="file" class="form-control form-control-sm uang-harian-file-input @error("peserta.{$index}.uang_harian_file") is-invalid @enderror" name="peserta[{{ $index }}][uang_harian_file]" accept=".pdf,.jpg,.jpeg,.png" style="font-size: 0.7rem;">
                                @if(isset($row['uang_harian_file_path']))
                                    <small class="text-success d-block mt-1 uang-harian-existing-notice" style="font-size: 0.65rem;">
                                        <i class="bi bi-check-circle"></i> <a href="{{ Storage::url($row['uang_harian_file_path']) }}" target="_blank">{{ $row['uang_harian_file_name'] ?? 'Lihat Bukti' }}</a>
                                    </small>
                                @endif
                                <small class="text-muted d-block" style="font-size: 0.65rem;">Bukti uang harian (PDF/JPG/PNG, maks 5MB)</small>
                            </div>
                        </div>
                    </div>

                    {{-- Sub-Total keseluruhan --}}
                    <div class="col-md-2 border-start">
                        <label class="form-label mb-1 small fw-bold text-primary" style="font-size: 0.75rem;">Sub-Total (Auto)</label>
                        <input type="text" class="form-control form-control-sm text-end fw-bold row-jumlah border-primary" style="background-color:#ebf5ff; color: #084298;" readonly value="0">
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
