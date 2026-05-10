@extends('layouts.app')
@section('title')
    Buat Tagihan (Kontrak & BAST)
@endsection
@push('css')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        .select2-container .select2-selection--single {
            height: 38px;
            border: 1px solid #dee2e6;
            border-radius: 6px;
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 36px;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 36px;
        }
    </style>
@endpush
@section('content')
    @php
        $isPresetTagihan = isset($selectedKontrak, $selectedTermin) && $selectedKontrak && $selectedTermin;
        $initialPotonganAngsuran = old('potongan_angsuran_uang_muka', $selectedPotonganAngsuran ?? 0);
        $kontrakTerminMap = [];

        foreach (($kontraks ?? collect()) as $kontrakItem) {
            $terms = [];

            foreach ($kontrakItem->termin->where('status_termin', 'READY_TO_BILL') as $terminItem) {
                $potongan = 0;

                if (
                    $kontrakItem->ada_uang_muka &&
                    (float) $kontrakItem->sisa_uang_muka_belum_lunas > 0 &&
                    in_array($terminItem->jenis_termin, ['PROGRESS', 'PELUNASAN'], true)
                ) {
                    $potongan = min((float) $terminItem->potongan_angsuran_uang_muka, (float) $kontrakItem->sisa_uang_muka_belum_lunas);
                }

                $terms[] = [
                    'id' => $terminItem->id,
                    'keterangan_termin' => $terminItem->keterangan_termin,
                    'persentase' => $terminItem->persentase,
                    'nilai_bruto_termin' => $terminItem->nilai_bruto_termin,
                    'jenis_termin' => $terminItem->jenis_termin,
                    'potongan_angsuran_uang_muka' => round($potongan, 2),
                ];
            }

            $kontrakTerminMap[$kontrakItem->id] = [
                'vendor' => optional($kontrakItem->vendor)->nama_perusahaan ?? 'N/A',
                'nama' => $kontrakItem->nama_pekerjaan,
                'nilai' => $kontrakItem->nilai_total_kontrak,
                'terms' => $terms,
            ];
        }
    @endphp
    <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
        <div>
            <h4 class="mb-0 fw-bold">Penagihan Termin / BAST</h4>
            <p class="text-muted mb-0 small">Formulir pengajuan pembayaran berdasarkan prestasi pekerjaan SPK</p>
        </div>
        <a href="{{ url()->previous() }}" class="btn btn-outline-secondary shadow-sm fw-bold">
            <i class="bi bi-arrow-left me-1"></i> Kembali
        </a>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger border-0 alert-dismissible fade show shadow-sm">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <form action="{{ route('tagihan.kontrak.store') }}" method="POST" enctype="multipart/form-data" id="formTagihan">
        @csrf

        <div class="row">
            {{-- Bagian 1: Pemilihan Kontrak & Validasi Termin --}}
            <div class="col-12 mb-4">
                <div class="card shadow-sm border-0 rounded-4 h-100">
                    <div class="card-header bg-primary text-white py-3 rounded-top-4">
                        <h6 class="mb-0 fw-bold"><i class="bi bi-search me-2"></i>1. Pemilihan Kontrak & Validasi Termin</h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-4">
                            <div class="col-md-6">
                                @if($isPresetTagihan)
                                    <label class="form-label fw-bold">Kontrak Terpilih</label>
                                    <input type="hidden" name="kontrak_pengadaan_id" id="kontrak_pengadaan_id" value="{{ $selectedKontrak->id }}">
                                    <div class="p-3 bg-light rounded border border-info h-100">
                                        <div class="small text-muted mb-1">Nomor SPK</div>
                                        <div class="fw-bold mb-2">{{ $selectedKontrak->nomor_spk }}</div>
                                        <div class="small text-muted mb-1">Vendor</div>
                                        <div class="fw-semibold mb-2">{{ $selectedKontrak->vendor->nama_perusahaan ?? '-' }}</div>
                                        <div class="small text-muted mb-1">Nama Pekerjaan</div>
                                        <div class="mb-2">{{ $selectedKontrak->nama_pekerjaan }}</div>
                                        <div class="small text-muted mb-1">Nilai Total Kontrak</div>
                                        <div class="fw-bold text-success fs-5">Rp {{ number_format($selectedKontrak->nilai_total_kontrak, 0, ',', '.') }}</div>
                                    </div>
                                @else
                                    <label class="form-label fw-bold">Pilih Kontrak (Nomor SPK) <span class="text-danger">*</span></label>
                                    <select class="form-select select2" name="kontrak_pengadaan_id" id="kontrak_pengadaan_id" required onchange="getDetailKontrak(this.value)">
                                        <option value="">-- Cari atau ketik Nomor SPK --</option>
                                        @foreach($kontraks ?? [] as $k)
                                            <option value="{{ $k->id }}" data-vendor="{{ $k->vendor->nama_perusahaan ?? 'N/A' }}" data-nama="{{ $k->nama_pekerjaan }}" data-nilai="{{ $k->nilai_total_kontrak }}">
                                                {{ $k->nomor_spk }} - {{ Str::limit($k->nama_pekerjaan, 40) }}
                                            </option>
                                        @endforeach
                                    </select>
                                    
                                    <div id="panel_info_kontrak" class="mt-3 p-3 bg-light rounded border border-info" style="display: none;">
                                        <small class="text-muted d-block mb-1">Nama Vendor:</small>
                                        <div class="fw-bold mb-2" id="info_vendor">-</div>
                                        <small class="text-muted d-block mb-1">Nama Pekerjaan:</small>
                                        <div class="fw-medium mb-2" id="info_pekerjaan">-</div>
                                        <small class="text-muted d-block mb-1">Nilai Total Kontrak:</small>
                                        <div class="fw-bold text-success fs-5" id="info_nilai">-</div>
                                    </div>
                                @endif
                            </div>

                            <div class="col-md-6">
                                @if($isPresetTagihan)
                                    <label class="form-label fw-bold">Termin yang Akan Ditagih</label>
                                    <input type="hidden" name="kontrak_termin_id" id="kontrak_termin_id" value="{{ $selectedTermin->id }}">
                                    <div class="p-3 bg-light rounded border border-success h-100">
                                        <div class="small text-muted mb-1">Termin</div>
                                        <div class="fw-bold mb-2">Termin {{ $selectedTermin->termin_ke }} - {{ str_replace('_', ' ', $selectedTermin->jenis_termin) }}</div>
                                        <div class="small text-muted mb-1">Keterangan</div>
                                        <div class="mb-2">{{ $selectedTermin->keterangan_termin }}</div>
                                        <div class="small text-muted mb-1">Nilai Bruto Termin</div>
                                        <div class="fw-bold text-success fs-5">Rp {{ number_format($selectedTermin->nilai_bruto_termin, 0, ',', '.') }}</div>
                                    </div>
                                @else
                                    <label class="form-label fw-bold">Pilih Termin Tagihan <span class="text-danger">*</span></label>
                                    <select class="form-select" name="kontrak_termin_id" id="kontrak_termin_id" required disabled onchange="setBrutoFromTermin()">
                                        <option value="">-- Pilih Kontrak Terlebih Dahulu --</option>
                                    </select>
                                    <div class="form-text mt-2">Hanya termin dengan status <strong>READY_TO_BILL</strong> yang akan tampil.</div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Bagian 2: Legalitas Pekerjaan (Berita Acara) --}}
            <div class="col-12 mb-4">
                <div class="card shadow-sm border-0 rounded-4 h-100">
                    <div class="card-header bg-info text-dark py-3 rounded-top-4">
                        <h6 class="mb-0 fw-bold"><i class="bi bi-file-earmark-check me-2"></i>2. Legalitas Pekerjaan (Berita Acara)</h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-4">
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Nomor BAPP <small class="text-muted">(Pemeriksaan)</small></label>
                                <div class="form-control-plaintext mb-2 text-muted fst-italic small"><i class="bi bi-magic me-1"></i>Akan digenerate: <strong class="text-primary">{{ $previewBapp }}</strong></div>
                                <input type="date" class="form-control" name="tanggal_bapp" value="{{ old('tanggal_bapp', now()->format('Y-m-d')) }}">
                            </div>
                            <div class="col-md-4" id="wrapper_bast_fields" style="display: none;">
                                <label class="form-label fw-bold">Nomor BAST <small class="text-danger">*</small> <small class="text-muted">(Serah Terima)</small></label>
                                <div class="form-control-plaintext mb-2 text-muted fst-italic small"><i class="bi bi-magic me-1"></i>Akan digenerate: <strong class="text-primary">{{ $previewBast }}</strong></div>
                                <input type="date" class="form-control" name="tanggal_bast" id="tanggal_bast" value="{{ old('tanggal_bast', now()->format('Y-m-d')) }}">
                            </div>
                            <div class="col-md-4" id="wrapper_bap_fields">
                                <label class="form-label fw-bold">Nomor BAP <small class="text-danger">*</small> <small class="text-muted">(Pembayaran)</small></label>
                                <div class="form-control-plaintext mb-2 text-muted fst-italic small"><i class="bi bi-magic me-1"></i>Akan digenerate: <strong class="text-primary">{{ $previewBap }}</strong></div>
                                <input type="date" class="form-control" name="tanggal_bap" value="{{ old('tanggal_bap', now()->format('Y-m-d')) }}" required>
                            </div>
                            
                            <div class="col-12 mt-4 pt-3 border-top">
                                <h6 class="fw-bold mb-3 text-secondary">Data Pemeriksa Hasil Pekerjaan (Untuk BAPP)</h6>
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold small">Nama Pemeriksa <span class="text-danger">*</span></label>
                                        <select class="form-select select2" name="nama_pemeriksa" id="namaPemeriksaSelect" required>
                                            <option value="">-- Pilih Pegawai --</option>
                                            @foreach($pegawaiList as $peg)
                                                <option
                                                    value="{{ $peg->nama_lengkap }}"
                                                    data-nip="{{ $peg->nip }}"
                                                    data-jabatan="{{ $peg->jabatan }}"
                                                    @selected(old('nama_pemeriksa') === $peg->nama_lengkap)
                                                >{{ $peg->nama_lengkap }}</option>
                                            @endforeach
                                        </select>
                                        <small class="text-muted">NIP & Jabatan akan terisi otomatis.</small>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold small">NIP Pemeriksa <small class="text-muted">(Otomatis)</small></label>
                                        <input type="text" class="form-control bg-light" name="nip_pemeriksa" id="nipPemeriksaInput" placeholder="Akan terisi setelah memilih nama" value="{{ old('nip_pemeriksa') }}" readonly>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold small">Jabatan Pemeriksa <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control bg-light" name="jabatan_pemeriksa" id="jabatanPemeriksaInput" placeholder="Akan terisi setelah memilih nama" value="{{ old('jabatan_pemeriksa') }}" required readonly>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Bagian 2b: Verifikator Tagihan --}}
            <div class="col-12 mb-4">
                <div class="card shadow-sm border-0 rounded-4 h-100 border-success">
                    <div class="card-header bg-success text-white py-3 rounded-top-4">
                        <h6 class="mb-0 fw-bold"><i class="bi bi-people-fill me-2"></i>2b. Verifikator Penagihan</h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="alert alert-info border-0 small mb-4">
                            <i class="bi bi-info-circle me-1"></i>
                            Pilih pejabat yang akan menjadi verifikator/penanda tangan untuk tagihan ini.
                            <strong>PPK</strong> ditentukan otomatis dari kontrak yang dipilih.
                            Nama &amp; NIP akan dipotret (snapshot) dan ditampilkan pada dokumen yang dicetak.
                        </div>

                        @php
                            $verifikatorFields = [
                                ['key' => 'ppspm',                 'label' => 'PPSPM',                                          'options' => $verifikatorOptions['ppspm'] ?? collect()],
                                ['key' => 'koordinator_keuangan',  'label' => 'Koordinator Keuangan',                            'options' => $verifikatorOptions['koordinator_keuangan'] ?? collect()],
                                ['key' => 'bendahara_pengeluaran', 'label' => 'Bendahara Pengeluaran',                           'options' => $verifikatorOptions['bendahara_pengeluaran'] ?? collect()],
                                ['key' => 'bendahara_penerimaan',  'label' => 'Bendahara Penerimaan',                            'options' => $verifikatorOptions['bendahara_penerimaan'] ?? collect()],
                                ['key' => 'kasubbag',              'label' => 'Kepala Subbagian Keuangan dan Tata Usaha',         'options' => $verifikatorOptions['kasubbag'] ?? collect()],
                            ];
                        @endphp

                        <div class="row g-3">
                            @foreach($verifikatorFields as $vf)
                                <div class="col-md-6">
                                    <label class="form-label fw-bold small">{{ $vf['label'] }} <span class="text-danger">*</span></label>
                                    <select
                                        class="form-select verifikator-select"
                                        name="{{ $vf['key'] }}_user_id"
                                        data-key="{{ $vf['key'] }}"
                                        required
                                    >
                                        <option value="">-- Pilih {{ $vf['label'] }} --</option>
                                        @foreach($vf['options'] as $opt)
                                            <option
                                                value="{{ $opt['id'] }}"
                                                data-name="{{ $opt['name'] }}"
                                                data-nip="{{ $opt['nip'] }}"
                                                data-jabatan="{{ $opt['jabatan'] }}"
                                                @selected(old($vf['key'].'_user_id') == $opt['id'])
                                            >{{ $opt['name'] }} {{ $opt['nip'] !== '-' ? '— NIP: '.$opt['nip'] : '' }}</option>
                                        @endforeach
                                    </select>
                                    <div class="small text-muted mt-1" id="info_{{ $vf['key'] }}"></div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            {{-- Bagian 3: Dokumen Vendor --}}
            <div class="col-12 mb-4">
                <div class="card shadow-sm border-0 rounded-4 h-100 border-warning">
                    <div class="card-header bg-warning text-dark py-3 rounded-top-4">
                        <h6 class="mb-0 fw-bold"><i class="bi bi-calculator me-2"></i>3. Dokumen Vendor & Ringkasan Nilai</h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-4 mb-4 pb-4 border-bottom">
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Nomor Invoice/Permohonan <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="nomor_invoice" placeholder="Contoh: INV/2026/001" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Tanggal Invoice <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" name="tanggal_invoice" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold text-success">Nilai Bruto (DPP + PPN) <span class="text-danger">*</span></label>
                                <input type="text" class="form-control fw-bold bg-light fs-5" id="total_bruto_display" value="{{ $isPresetTagihan ? 'Rp ' . number_format($selectedTermin->nilai_bruto_termin, 0, ',', '.') : 'Rp 0' }}" readonly>
                                <input type="hidden" name="total_bruto" id="total_bruto" value="{{ $isPresetTagihan ? $selectedTermin->nilai_bruto_termin : 0 }}">
                                <small class="text-muted">Terisi otomatis dari Termin.</small>
                            </div>
                        </div>

                        <div class="alert alert-info border-0 shadow-sm d-none" id="info_potongan_um">
                            <i class="bi bi-info-circle me-2"></i>
                            Kontrak ini masih memiliki sisa uang muka. Potongan angsuran uang muka akan otomatis diperhitungkan pada termin ini.
                        </div>

                        <div class="row g-3 mt-4 pt-3 border-top">
                            <div class="col-md-4">
                                <div class="small text-muted mb-1">Nilai Bruto</div>
                                <div class="fw-bold text-dark fs-5" id="summary_bruto_display">{{ $isPresetTagihan ? 'Rp ' . number_format($selectedTermin->nilai_bruto_termin, 0, ',', '.') : 'Rp 0' }}</div>
                            </div>
                            <div class="col-md-4">
                                <div class="small text-muted mb-1">Potongan Angsuran Uang Muka</div>
                                <div class="fw-bold text-warning fs-5" id="potongan_um_display">Rp {{ number_format($initialPotonganAngsuran, 0, ',', '.') }}</div>
                                <input type="hidden" name="potongan_angsuran_uang_muka" id="potongan_angsuran_uang_muka" value="{{ $initialPotonganAngsuran }}">
                            </div>
                            <div class="col-md-4 text-md-end">
                                <div class="small text-muted mb-1">Nilai Netto</div>
                                <div class="fw-bold text-success fs-3 mb-0" id="total_netto_display">Rp 0</div>
                                <input type="hidden" name="total_netto" id="total_netto" value="0">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Bagian 4: Lemari Arsip Digital (Upload File) --}}
            <div class="col-12 mb-4">
                <div class="card shadow-sm border-0 rounded-4 h-100">
                    <div class="card-header bg-secondary text-white py-3 rounded-top-4">
                        <h6 class="mb-0 fw-bold"><i class="bi bi-cloud-upload me-2"></i>4. Arsip Digital Pekerjaan (Format .PDF Maks 5MB)</h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="alert alert-info border-0 shadow-sm small mb-4">
                            <i class="bi bi-info-circle-fill me-2"></i>
                            <strong>Pemberitahuan:</strong> Dokumen final bertandatangan untuk BAPP, BAST, dan BAP dikelola nanti melalui halaman <strong>Detail Tagihan (Working Hub)</strong> setelah draft ini tersimpan.
                        </div>
                        <div class="row g-4">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">1. Surat Permohonan / Invoice <span class="text-danger">*</span></label>
                                <input type="file" class="form-control form-control-lg" name="file_invoice" accept=".pdf" required>
                                <small class="text-muted mt-1 d-block">Surat Permohonan pembayaran resmi (Format .PDF Maks 5MB).</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">2. Lampiran Laporan (Foto/Dokumentasi) <small class="text-muted">(Opsional)</small></label>
                                <input type="file" class="form-control form-control-lg" name="file_lampiran_lainnya" accept=".pdf,.zip">
                                <small class="text-muted mt-1 d-block">Laporan progres atau backup (.PDF atau .ZIP Maks 5MB).</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Action Buttons --}}
            <div class="col-12 mb-5">
                <div class="d-flex justify-content-end gap-3 p-3 bg-light rounded-4 shadow-sm">
                    <button type="reset" class="btn btn-outline-secondary px-4">Reset Ulang</button>
                    <button type="submit" class="btn btn-primary px-5 fw-bold"><i class="bi bi-save me-2"></i>Buat Draft Tagihan</button>
                </div>
            </div>

        </div>
    </form>
@endsection

@push('script')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    const isPresetTagihan = @json($isPresetTagihan);
    const kontrakTerminMap = @json($kontrakTerminMap);
    const selectedTerminMeta = @json($selectedTermin ? [
        'jenis_termin' => $selectedTermin->jenis_termin,
        'potongan_angsuran_uang_muka' => (float) $initialPotonganAngsuran,
    ] : null);
    
    $(document).ready(function() {
        $('.select2').select2({ theme: 'classic' });

        if (isPresetTagihan) {
            toggleBastFields(selectedTerminMeta?.jenis_termin ?? null);
            updatePotonganAngsuranDisplay(selectedTerminMeta?.potongan_angsuran_uang_muka ?? 0);
            hitungTotalNetto();
        }
    });

    function getDetailKontrak(idKontrak) {
        if(!idKontrak) {
            $('#panel_info_kontrak').hide();
            $('#kontrak_termin_id').html('<option value="">-- Pilih Kontrak Terlebih Dahulu --</option>').prop('disabled', true);
            toggleBastFields(null);
            updatePotonganAngsuranDisplay(0);
            $('#total_bruto').val(0);
            $('#total_bruto_display').val('Rp 0');
            $('#summary_bruto_display').text('Rp 0');
            hitungTotalNetto();
            return;
        }

        let kontrakData = kontrakTerminMap[idKontrak];
        if (!kontrakData) {
            return;
        }

        $('#info_vendor').text(kontrakData.vendor);
        $('#info_pekerjaan').text(kontrakData.nama);
        $('#info_nilai').text(formatRupiah(kontrakData.nilai.toString()));
        $('#panel_info_kontrak').fadeIn();

        let html = '<option value="">-- Pilih Termin / Tagihan --</option>';
        kontrakData.terms.forEach(t => {
            html += `<option value="${t.id}" data-bruto="${t.nilai_bruto_termin}" data-jenis="${t.jenis_termin}" data-potongan-um="${t.potongan_angsuran_uang_muka}">${t.keterangan_termin} - ${t.persentase}% (Rp ${formatRupiahCustom(t.nilai_bruto_termin)})</option>`;
        });
        if (kontrakData.terms.length === 0) {
            html = '<option value="">Tidak ada Termin berstatus READY_TO_BILL</option>';
        }
        $('#kontrak_termin_id').html(html).prop('disabled', false);
        toggleBastFields(null);
        updatePotonganAngsuranDisplay(0);
    }

    function setBrutoFromTermin() {
        let opt = $('#kontrak_termin_id').find(':selected');
        let brutoVal = opt.data('bruto') || 0;
        let jenisTermin = opt.data('jenis') || null;
        let potonganUm = parseFloat(opt.data('potongan-um')) || 0;
        
        $('#total_bruto').val(brutoVal);
        $('#total_bruto_display').val('Rp ' + formatRupiahCustom(brutoVal));
        $('#summary_bruto_display').text('Rp ' + formatRupiahCustom(brutoVal));
        toggleBastFields(jenisTermin);
        updatePotonganAngsuranDisplay(potonganUm);
        hitungTotalNetto();
    }

    function toggleBastFields(jenisTermin) {
        const isPelunasan = jenisTermin === 'PELUNASAN';
        const bastWrapper = document.getElementById('wrapper_bast_fields');
        const bastFileWrapper = document.getElementById('wrapper_file_bast');
        const tanggalBast = document.getElementById('tanggal_bast');
        const fileBast = document.getElementById('file_bast');

        if (bastWrapper) bastWrapper.style.display = isPelunasan ? 'block' : 'none';
        if (bastFileWrapper) bastFileWrapper.style.display = isPelunasan ? 'block' : 'none';
        if (tanggalBast) tanggalBast.required = isPelunasan;
        if (fileBast) fileBast.required = isPelunasan;

        if (!isPelunasan) {
            if (tanggalBast) tanggalBast.value = '';
            if (fileBast) fileBast.value = '';
        }
    }

    function updatePotonganAngsuranDisplay(nominal) {
        const normalized = parseFloat(nominal) || 0;
        document.getElementById('potongan_angsuran_uang_muka').value = normalized;
        document.getElementById('potongan_um_display').textContent = 'Rp ' + formatRupiahCustom(Math.round(normalized));
        document.getElementById('info_potongan_um').classList.toggle('d-none', normalized <= 0);
    }

    function formatRupiah(numberStr) {
        let nStr = numberStr.toString();
        let split = nStr.split('.');
        let sisa = split[0].length % 3;
        let rupiah = split[0].substr(0, sisa);
        let ribuan = split[0].substr(sisa).match(/\d{3}/gi);
        if (ribuan) {
            let separator = sisa ? '.' : '';
            rupiah += separator + ribuan.join('.');
        }
        return 'Rp ' + rupiah;
    }

    function formatRupiahCustom(angka) {
        let number_string = angka.toString().replace(/[^,\d]/g, ''),
        split   		= number_string.split(','),
        sisa     		= split[0].length % 3,
        rupiah     		= split[0].substr(0, sisa),
        ribuan     		= split[0].substr(sisa).match(/\d{3}/gi);

        if(ribuan){
            let separator = sisa ? '.' : '';
            rupiah += separator + ribuan.join('.');
        }

        rupiah = split[1] != undefined ? rupiah + ',' + split[1] : rupiah;
        return rupiah;
    }

    function hitungTotalNetto() {
        let bruto = parseFloat($('#total_bruto').val()) || 0;
        let potonganAngsuranUangMuka = parseFloat($('#potongan_angsuran_uang_muka').val()) || 0;

        let netto = bruto - potonganAngsuranUangMuka;
        
        $('#total_netto').val(netto);
        $('#total_netto_display').text('Rp ' + formatRupiahCustom(Math.round(netto)));
    }

    // Verifikator info preview (NIP & Jabatan)
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.verifikator-select').forEach(function (sel) {
            const key = sel.dataset.key;
            const info = document.getElementById('info_' + key);
            const update = function () {
                const opt = sel.options[sel.selectedIndex];
                if (!opt || !opt.value) {
                    info.innerHTML = '';
                    return;
                }
                const nip = opt.dataset.nip || '-';
                const jab = opt.dataset.jabatan || '';
                info.innerHTML = '<i class="bi bi-person-badge me-1"></i>NIP: <span class="font-monospace">' + nip + '</span>' + (jab ? ' &middot; ' + jab : '');
            };
            sel.addEventListener('change', update);
            if (sel.value) update();
        });
    });

    // Auto-fill NIP & Jabatan saat memilih Nama Pemeriksa dari dropdown pegawai
    $(document).ready(function () {
        const $namaSelect = $('#namaPemeriksaSelect');
        const $nipInput = $('#nipPemeriksaInput');
        const $jabatanInput = $('#jabatanPemeriksaInput');

        if (!$namaSelect.length || !$nipInput.length || !$jabatanInput.length) return;

        function syncPemeriksa() {
            const $opt = $namaSelect.find(':selected');
            if (!$opt.val()) {
                $nipInput.val('');
                $jabatanInput.val('');
                return;
            }
            $nipInput.val($opt.data('nip') || '');
            $jabatanInput.val($opt.data('jabatan') || '');
        }

        $namaSelect.on('change', syncPemeriksa);

        // Inisialisasi (mis. setelah validasi gagal & old() mengembalikan pilihan)
        if ($namaSelect.val()) syncPemeriksa();
    });
</script>
@endpush
