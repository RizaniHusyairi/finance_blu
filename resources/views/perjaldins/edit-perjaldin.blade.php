@extends('layouts.app')
@section('title')
    Edit Data Perjaldin
@endsection
@section('content')
    <x-page-title title="Manajemen Perjaldin" subtitle="Edit Data" />

    <div class="card">
        <div class="card-body">
            <h6 class="mb-4">Form Edit Perjaldin: {{ $tagihan->nomor_tagihan }}</h6>
            @if ($errors->any())
                <div class="alert alert-danger border-0 bg-danger alert-dismissible fade show">
                    <ul class="text-white mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <form action="{{ route('perjaldins.update-perjaldin', $tagihan->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <!-- SECTION A: HEADER DOKUMEN -->
                <h5 class="mb-3 border-bottom pb-2 text-primary"><i class="bi bi-file-earmark-text"></i> Bagian A: Informasi Dokumen & Anggaran</h5>
                <div class="row mb-4">
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Uraian / Judul Perjalanan <span class="text-danger">*</span></label>
                        <input type="text" name="deskripsi" class="form-control" placeholder="Acara..." required value="{{ old('deskripsi', $tagihan->deskripsi) }}">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Nomor Perjalanan Dinas <span class="text-danger">*</span></label>
                        <input type="text" name="nomor_perjaldin" class="form-control" placeholder="Contoh: KU.201/1245/APTP/2026" required value="{{ old('nomor_perjaldin', $tagihan->nomor_tagihan) }}">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Periode Bulan <span class="text-danger">*</span></label>
                        <select name="periode_bulan" class="form-select" required>
                            <option value="">-- Pilih Bulan --</option>
                            @for($i=1; $i<=12; $i++)
                                <option value="{{ $i }}" {{ old('periode_bulan', $tagihan->periode_bulan) == $i ? 'selected' : '' }}>{{ date('F', mktime(0, 0, 0, $i, 10)) }}</option>
                            @endfor
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Periode Tahun <span class="text-danger">*</span></label>
                        <input type="number" name="periode_tahun" class="form-control" required value="{{ old('periode_tahun', $tagihan->periode_tahun) }}" min="2000" max="2100">
                    </div>

                    <div class="col-md-3 mb-3">
                        <label class="form-label">Kota TTD <span class="text-danger">*</span></label>
                        <input type="text" name="kota_ttd" class="form-control" placeholder="Samarinda" required value="{{ old('kota_ttd', $tagihan->kota_ttd) }}">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Tanggal TTD <span class="text-danger">*</span></label>
                        <input type="date" name="tanggal_ttd" class="form-control" required value="{{ old('tanggal_ttd', $tagihan->tanggal_ttd) }}">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Mekanisme Pembayaran <span class="text-danger">*</span></label>
                        @php
                            $mekValue = old(
                                'mekanisme_pembayaran',
                                optional($tagihan->mekanisme_pembayaran)->value
                                    ?? \App\Enums\MekanismePembayaran::defaultFor('PERJALDIN')->value
                            );
                        @endphp
                        <select name="mekanisme_pembayaran" class="form-select" required>
                            @foreach(\App\Enums\MekanismePembayaran::optionsFor('PERJALDIN') as $val => $lbl)
                                <option value="{{ $val }}" {{ $mekValue === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                            @endforeach
                        </select>
                        <div class="form-text">LS - Pihak Ketiga: ditransfer langsung ke rekening masing-masing peserta. LS - Via Bendahara: diteruskan melalui Bendahara Pengeluaran.</div>
                    </div>
                </div>

                <!-- SECTION B: VERIFIKATOR -->
                <h5 class="mb-3 border-bottom pb-2 text-primary mt-4"><i class="bi bi-pen"></i> Bagian B: Verifikator Dokumen</h5>
                @php
                    $kasubbagId = old('kasubbag_user_id', $tagihan->kasubbag_user_id ?: optional($kasubbagUser)->id);
                    $kasubbagNama = old('kasubbag_nama_snapshot', $tagihan->kasubbag_nama_snapshot ?: optional($kasubbagUser)->name);
                    $kasubbagNip = old('kasubbag_nip_snapshot', $tagihan->kasubbag_nip_snapshot ?: optional(optional($kasubbagUser)->pegawai)->nip);
                @endphp
                <div class="row mb-4">
                    <!-- PPK -->
                    <div class="col-md-6 col-xl mb-3">
                        <h6 class="text-muted mb-3">Pejabat Pembuat Komitmen</h6>
                        <div class="mb-2">
                            <label class="form-label">Pilih User PPK (Opsional)</label>
                            <select name="ppk_user_id" class="form-select select2" id="ppkUserId">
                                <option value="">-- Pilih User PPK --</option>
                                @foreach($ppkUsers as $user)
                                    <option value="{{ $user->id }}" data-nip="{{ optional($user->pegawai)->nip }}" data-nama="{{ $user->name }}" {{ old('ppk_user_id', $tagihan->ppk_user_id) == $user->id ? 'selected' : '' }}>
                                        {{ $user->name }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-muted d-block mt-1">Pilih untuk auto-fill Nama & NIP di bawah ini</small>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Nama PPK (Cetak PDF) <span class="text-danger">*</span></label>
                            <input type="text" name="ppk_nama_snapshot" id="ppkNamaSnapshot" class="form-control" required value="{{ old('ppk_nama_snapshot', $tagihan->ppk_nama_snapshot) }}">
                        </div>
                        <div class="mb-2">
                            <label class="form-label">NIP PPK (Cetak PDF) <span class="text-danger">*</span></label>
                            <input type="text" name="ppk_nip_snapshot" id="ppkNipSnapshot" class="form-control" required value="{{ old('ppk_nip_snapshot', $tagihan->ppk_nip_snapshot) }}">
                        </div>
                    </div>

                    <!-- PPSPM -->
                    <div class="col-md-6 col-xl mb-3">
                        <h6 class="text-muted mb-3">PPSPM</h6>
                        <div class="mb-2">
                            <label class="form-label">Pilih User PPSPM <span class="text-danger">*</span></label>
                            <select name="ppspm_user_id" class="form-select select2" id="ppspmUserId" required>
                                <option value="">-- Pilih User PPSPM --</option>
                                @foreach($ppspmUsers as $user)
                                    <option value="{{ $user->id }}" data-nip="{{ optional($user->pegawai)->nip }}" data-nama="{{ $user->name }}" {{ old('ppspm_user_id', $tagihan->ppspm_user_id) == $user->id ? 'selected' : '' }}>
                                        {{ $user->name }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-muted d-block mt-1">Pilih untuk auto-fill Nama & NIP di bawah ini</small>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Nama PPSPM <span class="text-danger">*</span></label>
                            <input type="text" name="ppspm_nama_snapshot" id="ppspmNamaSnapshot" class="form-control" required value="{{ old('ppspm_nama_snapshot', $tagihan->ppspm_nama_snapshot) }}">
                        </div>
                        <div class="mb-2">
                            <label class="form-label">NIP PPSPM</label>
                            <input type="text" name="ppspm_nip_snapshot" id="ppspmNipSnapshot" class="form-control" value="{{ old('ppspm_nip_snapshot', $tagihan->ppspm_nip_snapshot) }}">
                        </div>
                    </div>

                    <!-- Bendahara Penerimaan -->
                    <div class="col-md-6 col-xl mb-3">
                        <h6 class="text-muted mb-3">Bendahara Penerimaan</h6>
                        <div class="mb-2">
                            <label class="form-label">Pilih User Bendahara Penerimaan <span class="text-danger">*</span></label>
                            <select name="bendahara_penerimaan_user_id" class="form-select select2" id="bendaharaPenerimaanUserId" required>
                                <option value="">-- Pilih Bendahara Penerimaan --</option>
                                @foreach($bendaharaPenerimaanUsers as $user)
                                    <option value="{{ $user->id }}" data-nip="{{ optional($user->pegawai)->nip }}" data-nama="{{ $user->name }}" {{ old('bendahara_penerimaan_user_id', $tagihan->bendahara_penerimaan_user_id) == $user->id ? 'selected' : '' }}>
                                        {{ $user->name }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-muted d-block mt-1">Pilih untuk auto-fill Nama & NIP di bawah ini</small>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Nama Bendahara Penerimaan <span class="text-danger">*</span></label>
                            <input type="text" name="bendahara_penerimaan_nama_snapshot" id="bendaharaPenerimaanNamaSnapshot" class="form-control" required value="{{ old('bendahara_penerimaan_nama_snapshot', $tagihan->bendahara_penerimaan_nama_snapshot) }}">
                        </div>
                        <div class="mb-2">
                            <label class="form-label">NIP Bendahara Penerimaan</label>
                            <input type="text" name="bendahara_penerimaan_nip_snapshot" id="bendaharaPenerimaanNipSnapshot" class="form-control" value="{{ old('bendahara_penerimaan_nip_snapshot', $tagihan->bendahara_penerimaan_nip_snapshot) }}">
                        </div>
                    </div>

                    <!-- Bendahara Pengeluaran -->
                    <div class="col-md-6 col-xl mb-3">
                        <h6 class="text-muted mb-3">Bendahara Pengeluaran</h6>
                        <div class="mb-2">
                            <label class="form-label">Pilih User Bendahara <span class="text-danger">*</span></label>
                            <select name="bendahara_pengeluaran_user_id" class="form-select select2" id="bendaharaUserId" required>
                                <option value="">-- Pilih User Bendahara --</option>
                                @foreach($bendaharaUsers as $user)
                                    <option value="{{ $user->id }}" data-nip="{{ optional($user->pegawai)->nip }}" data-nama="{{ $user->name }}" {{ old('bendahara_pengeluaran_user_id', $tagihan->bendahara_pengeluaran_user_id) == $user->id ? 'selected' : '' }}>
                                        {{ $user->name }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-muted d-block mt-1">Pilih untuk auto-fill Nama & NIP di bawah ini</small>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Nama Bendahara (Cetak PDF) <span class="text-danger">*</span></label>
                            <input type="text" name="bendahara_pengeluaran_nama_snapshot" id="bendaharaNamaSnapshot" class="form-control" required value="{{ old('bendahara_pengeluaran_nama_snapshot', $tagihan->bendahara_pengeluaran_nama_snapshot) }}">
                        </div>
                        <div class="mb-2">
                            <label class="form-label">NIP Bendahara (Cetak PDF) <span class="text-danger">*</span></label>
                            <input type="text" name="bendahara_pengeluaran_nip_snapshot" id="bendaharaNipSnapshot" class="form-control" required value="{{ old('bendahara_pengeluaran_nip_snapshot', $tagihan->bendahara_pengeluaran_nip_snapshot) }}">
                        </div>
                    </div>

                    <!-- Kasubbag -->
                    <div class="col-md-6 col-xl mb-3">
                        <h6 class="text-muted mb-3">Kasubbag</h6>
                        <input type="hidden" name="kasubbag_user_id" value="{{ $kasubbagId }}">
                        <div class="mb-2">
                            <label class="form-label">User Kasubbag</label>
                            <input type="text" class="form-control" readonly value="{{ $kasubbagNama ?: 'Belum ada user Kasubbag' }}">
                            <input type="hidden" name="kasubbag_nama_snapshot" value="{{ $kasubbagNama }}">
                            <small class="text-muted d-block mt-1">Ditentukan otomatis dari role Kasubbag.</small>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">NIP Kasubbag</label>
                            <input type="text" class="form-control" readonly value="{{ $kasubbagNip ?: '-' }}">
                            <input type="hidden" name="kasubbag_nip_snapshot" value="{{ $kasubbagNip }}">
                        </div>
                    </div>

                    <!-- Koordinator Keuangan -->
                    <div class="col-md-6 col-xl mb-3">
                        <h6 class="text-muted mb-3">Koordinator Keuangan</h6>
                        <div class="mb-2">
                            <label class="form-label">Pilih User Koordinator Keuangan <span class="text-danger">*</span></label>
                            <select name="koordinator_keuangan_user_id" class="form-select select2" id="koorKeuanganUserId" required>
                                <option value="">-- Pilih User Koordinator --</option>
                                @foreach($koorKeuanganUsers as $user)
                                    <option value="{{ $user->id }}" data-nip="{{ optional($user->pegawai)->nip }}" data-nama="{{ $user->name }}" {{ old('koordinator_keuangan_user_id', $tagihan->koordinator_keuangan_user_id) == $user->id ? 'selected' : '' }}>
                                        {{ $user->name }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-muted d-block mt-1">Pilih untuk auto-fill Nama & NIP di bawah ini</small>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Nama Koordinator Keuangan <span class="text-danger">*</span></label>
                            <input type="text" name="koordinator_keuangan_nama_snapshot" id="koorKeuanganNamaSnapshot" class="form-control" required value="{{ old('koordinator_keuangan_nama_snapshot', $tagihan->koordinator_keuangan_nama_snapshot) }}">
                        </div>
                        <div class="mb-2">
                            <label class="form-label">NIP Koordinator Keuangan</label>
                            <input type="text" name="koordinator_keuangan_nip_snapshot" id="koorKeuanganNipSnapshot" class="form-control" value="{{ old('koordinator_keuangan_nip_snapshot', $tagihan->koordinator_keuangan_nip_snapshot) }}">
                        </div>
                    </div>
                </div>

                                <!-- SECTION C: DAFTAR NOMINATIF PEGAWAI -->
                <div class="d-flex justify-content-between align-items-end mb-3 mt-4 border-bottom pb-2">
                    <h5 class="text-primary mb-0"><i class="bi bi-people"></i> Bagian C: Daftar Nominatif Pegawai</h5>
                    <button type="button" class="btn btn-dark btn-sm btn-add-row-trigger"><i class="bi bi-plus-circle"></i> Tambah Pegawai</button>
                </div>

                <!-- Bagian Summary (Global Tab) -->
                <div class="alert alert-info d-flex align-items-center mb-3 shadow-sm border-0">
                    <div class="me-auto">
                        <strong>Ringkasan:</strong> <span id="summaryCount">{{ count($tagihan->detailPerjaldin) }}</span> Peserta Terdaftar
                    </div>
                    <div class="fs-5 fw-bold text-dark">
                        Grand Total: Rp <span id="summaryGrandTotal" class="text-primary">{{ number_format($tagihan->total_bruto, 0, '.', ',') }}</span>
                    </div>
                    <input type="hidden" id="grandTotal" name="total_bruto" value="{{ $tagihan->total_bruto }}">
                </div>

                <!-- Wrapper Card List -->
                <div id="pesertaRepeater">
                    @php
                        $oldPeserta = old('peserta', $tagihan->detailPerjaldin->toArray());
                        $isCreate = false;
                    @endphp

                    @foreach($oldPeserta as $index => $row)
                        @include('perjaldins.partials.peserta-card', ['index' => $index, 'row' => $row, 'masterProvinsi' => $masterProvinsi, 'masterPegawai' => $masterPegawai, 'isCreate' => false])
                    @endforeach
                </div>

                <div class="mt-3">
                    <button type="button" class="btn btn-dark btn-sm btn-add-row-trigger"><i class="bi bi-plus-circle"></i> Tambah Baris Peserta</button>
                </div>

                <!-- SECTION D: PEMILIHAN COA PER KOMPONEN BIAYA -->
                @php
                    $komponenList = [
                        ['kode' => 'TIKET', 'label' => 'Biaya Tiket', 'icon' => 'bi-ticket-detailed'],
                        ['kode' => 'TRANSPORT', 'label' => 'Biaya Transport', 'icon' => 'bi-car-front'],
                        ['kode' => 'PENGINAPAN', 'label' => 'Biaya Penginapan', 'icon' => 'bi-building'],
                        ['kode' => 'UANG_HARIAN', 'label' => 'Uang Harian', 'icon' => 'bi-wallet2'],
                    ];
                    $existingKomponen = $tagihan->komponenPerjaldin->keyBy('kode_komponen');
                @endphp
                <h5 class="mb-3 border-bottom pb-2 text-primary mt-5"><i class="bi bi-bank"></i> Bagian D: Pemilihan COA per Komponen Biaya</h5>
                <div id="komponenCoaSection">
                    <div class="alert alert-warning border-0 shadow-sm py-2 px-3 mb-3 small komponen-coa-empty">
                        <i class="bi bi-info-circle me-1"></i> Belum ada komponen biaya bernilai. Isi rincian biaya peserta dulu, baris pemilihan COA akan muncul otomatis.
                    </div>
                    @foreach($komponenList as $k)
                        @php
                            $existing = $existingKomponen->get($k['kode']);
                            $lockedBySpp = $existing && $existing->hasDokumenTurunan();
                            $selectedCoa = old('komponen_coa.' . $k['kode'], $existing?->dipa_revision_item_id);
                        @endphp
                        <div class="card border shadow-sm mb-2 komponen-coa-row d-none" data-kode="{{ $k['kode'] }}">
                            <div class="card-body py-2 px-3">
                                <div class="row g-2 align-items-center">
                                    <div class="col-md-3">
                                        <div class="d-flex align-items-center">
                                            <div class="bg-primary bg-opacity-10 text-primary p-2 rounded-circle me-2">
                                                <i class="bi {{ $k['icon'] }}"></i>
                                            </div>
                                            <div>
                                                <div class="fw-bold small">{{ $k['label'] }}</div>
                                                <small class="text-muted" style="font-size: 0.7rem;">Kode: {{ $k['kode'] }}</small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <small class="text-muted d-block" style="font-size: 0.7rem;">Total Komponen</small>
                                        <span class="fw-bold text-dark">Rp <span class="komponen-coa-total">0</span></span>
                                        @if($lockedBySpp)
                                            <span class="badge bg-warning text-dark mt-1" style="font-size: 0.6rem;"><i class="bi bi-lock-fill"></i> Terkunci SPP</span>
                                        @endif
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label mb-1 small fw-semibold">Pilih COA <span class="text-danger">*</span></label>
                                        <select name="komponen_coa[{{ $k['kode'] }}]" class="form-select form-select-sm komponen-coa-select @error('komponen_coa.' . $k['kode']) is-invalid @enderror" {{ $lockedBySpp ? 'disabled' : '' }}>
                                            <option value="">-- Pilih COA --</option>
                                            @foreach($budgetGroups as $group)
                                                <optgroup label="{{ $group['label'] }}">
                                                    @foreach($group['items'] as $item)
                                                        <option value="{{ $item['id'] }}" data-sisa-pagu="{{ $item['sisa_pagu'] }}" {{ (string) $selectedCoa === (string) $item['id'] ? 'selected' : '' }}>
                                                            {{ $item['option_label'] }}
                                                        </option>
                                                    @endforeach
                                                </optgroup>
                                            @endforeach
                                        </select>
                                        @if($lockedBySpp)
                                            <input type="hidden" name="komponen_coa[{{ $k['kode'] }}]" value="{{ $selectedCoa }}">
                                            <small class="text-muted" style="font-size: 0.7rem;"><i class="bi bi-info-circle me-1"></i>COA tidak dapat diubah karena komponen sudah memiliki SPP.</small>
                                        @endif
                                        <small class="d-none text-danger mt-1 komponen-coa-warning" style="font-size: 0.7rem;">
                                            <i class="bi bi-exclamation-triangle-fill me-1"></i><span class="komponen-coa-warning-text"></span>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-5 border-top pt-3 text-end">
                    <a href="{{ route('perjaldins.index') }}" class="btn btn-light"><i class="bi bi-x-circle"></i> Batal</a>
                    <button type="submit" class="btn btn-primary px-4"><i class="bi bi-save"></i> Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('script')
    <script>
        let rowIdx = {{ count($oldPeserta) > 0 ? count($oldPeserta) : 1 }};

        // Formatting numbers to string with commas
        function formatNumber(n) {
            return n.toString().replace(/\D/g, "").replace(/\B(?=(\d{3})+(?!\d))/g, ",");
        }

        // Toggle visibility input file bukti berdasarkan nilai komponen biaya.
        // key: 'tiket' | 'transport' | 'penginapan' | 'uang-harian'
        window.toggleBuktiFile = function (element, key) {
            let card = $(element).closest('.item-row');
            let val = parseFloat(String($(element).val()).replace(/,/g, '')) || 0;
            let wrapper = card.find('.' + key + '-file-wrapper');
            if (val > 0) {
                wrapper.removeClass('d-none');
            } else {
                wrapper.addClass('d-none');
                wrapper.find('.' + key + '-file-input').val('');
                wrapper.find('.' + key + '-existing-notice').remove();
            }
        };
        // Backward-compat alias
        window.toggleTiketFile = function (element) { return toggleBuktiFile(element, 'tiket'); };

        // Toggle file uang harian berdasarkan total grup (Harian + Representasi + Rapat)
        function toggleUangHarianFile(card) {
            let total = 0;
            card.find('.uang-harian-component').each(function () {
                let n = parseFloat(String($(this).val()).replace(/,/g, ''));
                if (!isNaN(n)) total += n;
            });
            let wrapper = card.find('.uang-harian-file-wrapper');
            if (total > 0) {
                wrapper.removeClass('d-none');
            } else {
                wrapper.addClass('d-none');
                wrapper.find('.uang-harian-file-input').val('');
                wrapper.find('.uang-harian-existing-notice').remove();
            }
        }

        // Hitung total tampilan grup Uang Harian (Harian + Representasi + Rapat)
        function calculateUangHarianGroup(card) {
            let total = 0;
            card.find('.uang-harian-component').each(function () {
                let num = parseFloat(String($(this).val()).replace(/,/g, ''));
                if (!isNaN(num)) total += num;
            });
            card.find('.uang-harian-total').text(formatNumber(total));
            toggleUangHarianFile(card);
        }

        // Cek sisa pagu COA terpilih vs total komponen, tampilkan warning bila kurang.
        function evaluateCoaPagu(row, total) {
            let select = row.find('.komponen-coa-select');
            let warning = row.find('.komponen-coa-warning');
            let warningText = row.find('.komponen-coa-warning-text');
            let selectedOpt = select.find('option:selected');
            let sisa = parseFloat(selectedOpt.data('sisa-pagu'));
            if (!selectedOpt.val() || isNaN(sisa)) {
                warning.addClass('d-none');
                select.removeClass('is-invalid');
                return;
            }
            if (sisa < total) {
                warningText.text('Sisa pagu COA (Rp ' + formatNumber(Math.round(sisa)) + ') tidak mencukupi total komponen (Rp ' + formatNumber(total) + ').');
                warning.removeClass('d-none');
                select.addClass('is-invalid');
            } else {
                warning.addClass('d-none');
                select.removeClass('is-invalid');
            }
        }

        // Hitung total per kode komponen biaya (lintas semua peserta) dan show/hide baris COA
        function recalcKomponenCoaSection() {
            let totals = { TIKET: 0, TRANSPORT: 0, PENGINAPAN: 0, UANG_HARIAN: 0 };
            $('.komponen-input').each(function () {
                let kode = $(this).data('kode');
                if (!(kode in totals)) return;
                let num = parseFloat(String($(this).val()).replace(/,/g, ''));
                if (!isNaN(num)) totals[kode] += num;
            });
            let anyVisible = false;
            $('.komponen-coa-row').each(function () {
                let row = $(this);
                let kode = row.data('kode');
                let total = totals[kode] || 0;
                row.find('.komponen-coa-total').text(formatNumber(total));
                let select = row.find('.komponen-coa-select');
                if (total > 0) {
                    row.removeClass('d-none');
                    if (!select.prop('disabled')) {
                        select.prop('required', true);
                    }
                    anyVisible = true;
                } else {
                    row.addClass('d-none');
                    if (!select.prop('disabled')) {
                        select.prop('required', false).val('');
                    }
                }
                evaluateCoaPagu(row, total);
            });
            $('.komponen-coa-empty').toggleClass('d-none', anyVisible);
        }

        // Live re-cek pagu saat user ganti COA
        $(document).on('change', '.komponen-coa-select', function () {
            let row = $(this).closest('.komponen-coa-row');
            let total = parseFloat(String(row.find('.komponen-coa-total').text()).replace(/,/g, '')) || 0;
            evaluateCoaPagu(row, total);
        });

        // Auto calculate per row and grand total
        window.calculateJumlah = function (element) {
            let val = $(element).val();
            if(val !== '') {
                $(element).val(formatNumber(val));
            }

            let card = $(element).closest('.item-row');
            let total = 0;
            card.find('.biaya-input').each(function () {
                let num = parseFloat($(this).val().replace(/,/g, ''));
                if (!isNaN(num)) total += num;
            });
            card.find('.row-jumlah').val(formatNumber(total));
            card.find('.summary-total').text(formatNumber(total)); // update header card summary
            calculateUangHarianGroup(card);
            calculateGrandTotal();
            recalcKomponenCoaSection();
        }

        function calculateGrandTotal() {
            let grandTotal = 0;
            $('.row-jumlah').each(function () {
                let num = parseFloat($(this).val().replace(/,/g, ''));
                if (!isNaN(num)) grandTotal += num;
            });
            $('#grandTotal').val(formatNumber(grandTotal));
            $('#summaryGrandTotal').text(formatNumber(grandTotal));
            $('#summaryCount').text($('.item-row').length);
        }

        // Auto calculate Uang Harian
        function calculateUangHarian(card) {
            let provSelect = card.find('.provinsi-select option:selected');
            let tipe = card.find('.tipe-select').val();
            let lamaHari = parseInt(card.find('.lama-hari-input').val()) || 0;

            if (provSelect.val() !== '' && typeof provSelect.val() !== 'undefined' && tipe !== '') {
                let rate = 0;
                if (tipe === 'luar_kota') rate = parseFloat(provSelect.data('luar')) || 0;
                else if (tipe === 'dalam_kota_lebih_8_jam') rate = parseFloat(provSelect.data('dalam')) || 0;
                else if (tipe === 'diklat') rate = parseFloat(provSelect.data('diklat')) || 0;

                let totalUangHarian = rate * lamaHari;
                card.find('.uang-harian-input').val(formatNumber(totalUangHarian));
                calculateJumlah(card.find('.uang-harian-input')[0]);
            }
        }

        $(document).ready(function () {
            $('.select2').select2({
                theme: 'bootstrap-5',
                width: '100%'
            });

            // Initialize formatting for old inputs
            $('.biaya-input').each(function() {
               calculateJumlah(this);
            });

            // Initialize toggle file bukti berdasar nilai awal masing-masing komponen
            $('.tiket-amount-input').each(function() { toggleBuktiFile(this, 'tiket'); });
            $('.transport-amount-input').each(function() { toggleBuktiFile(this, 'transport'); });
            $('.penginapan-amount-input').each(function() { toggleBuktiFile(this, 'penginapan'); });
            $('.item-row').each(function() { toggleUangHarianFile($(this)); });

            // Auto-fill PPK
            $('#ppkUserId').change(function() {
                let selected = $(this).find(':selected');
                if(selected.val() !== '') {
                    $('#ppkNamaSnapshot').val(selected.data('nama'));
                    $('#ppkNipSnapshot').val(selected.data('nip'));
                }
            });

            // Auto-fill PPSPM
            $('#ppspmUserId').change(function() {
                let selected = $(this).find(':selected');
                if(selected.val() !== '') {
                    $('#ppspmNamaSnapshot').val(selected.data('nama'));
                    $('#ppspmNipSnapshot').val(selected.data('nip'));
                }
            });

            // Auto-fill Bendahara Penerimaan
            $('#bendaharaPenerimaanUserId').change(function() {
                let selected = $(this).find(':selected');
                if(selected.val() !== '') {
                    $('#bendaharaPenerimaanNamaSnapshot').val(selected.data('nama'));
                    $('#bendaharaPenerimaanNipSnapshot').val(selected.data('nip'));
                }
            });

            // Auto-fill Bendahara
            $('#bendaharaUserId').change(function() {
                let selected = $(this).find(':selected');
                if(selected.val() !== '') {
                    $('#bendaharaNamaSnapshot').val(selected.data('nama'));
                    $('#bendaharaNipSnapshot').val(selected.data('nip'));
                }
            });

            // Auto-fill Koordinator Keuangan
            $('#koorKeuanganUserId').change(function() {
                let selected = $(this).find(':selected');
                if(selected.val() !== '') {
                    $('#koorKeuanganNamaSnapshot').val(selected.data('nama'));
                    $('#koorKeuanganNipSnapshot').val(selected.data('nip'));
                }
            });

            // Add Row Check
            $(document).on('click', '.btn-add-row-trigger', function (e) {
                e.preventDefault();
                
                // Destroy Select2 on the first row before cloning
                let firstRow = $('.item-row:first');
                if (firstRow.find('.select2').hasClass('select2-hidden-accessible')) {
                    firstRow.find('.select2').select2('destroy');
                }

                let newRow = firstRow.clone();

                // Reinitialize Select2 on the first row
                firstRow.find('.select2').select2({
                    theme: 'bootstrap-5',
                    width: '100%'
                });

                // Clear values and validation classes
                newRow.find('input[type="text"], input[type="number"], input[type="date"], input[type="hidden"], input[type="file"], textarea').val('').removeClass('is-invalid');
                newRow.find('select').prop('selectedIndex', 0).removeClass('is-invalid');
                newRow.find('.row-jumlah').val('0');
                newRow.find('.summary-nama, .summary-tujuan').text('-');
                newRow.find('.summary-total').text('0');
                newRow.find('.uang-harian-total').text('0');
                newRow.find('.file-existing-notice').remove();
                newRow.find('.tiket-existing-notice, .transport-existing-notice, .penginapan-existing-notice, .uang-harian-existing-notice').remove();
                newRow.find('.tiket-file-wrapper, .transport-file-wrapper, .penginapan-file-wrapper, .uang-harian-file-wrapper').addClass('d-none');
                newRow.find('.file-status-badge').removeClass('bg-success').addClass('bg-secondary').html('<i class="bi bi-paperclip"></i> SPT Kosong');
                newRow.find('.nip-input').val('').prop('readonly', true);
                newRow.find('.rekening-input').val('').prop('readonly', false);
                newRow.find('.rek-hint').addClass('d-none');
                
                // Remove any leftover select2 elements if they were cloned
                newRow.find('.select2-container').remove();
                newRow.find('.select2-hidden-accessible').removeClass('select2-hidden-accessible').removeAttr('data-select2-id aria-hidden tabindex');

                // Adjust Collapse id
                let collapseId = 'collapsePeserta' + rowIdx;
                newRow.find('.collapse-trigger').attr('data-bs-target', '#' + collapseId);
                newRow.find('.peserta-collapse').attr('id', collapseId).addClass('show');

                newRow.find('input, select, textarea').each(function () {
                    var name = $(this).attr('name');
                    if (name) {
                        name = name.replace(/\[\d+\]/g, '[' + rowIdx + ']');
                        $(this).attr('name', name);
                    }
                    $(this).removeAttr('id'); // Remove id to avoid duplicates
                });

                newRow.find('.btn-delete-row').prop('disabled', false);
                $('#pesertaRepeater').append(newRow);
                
                // Initialize Select2 on the new row
                newRow.find('.select2').select2({
                    theme: 'bootstrap-5',
                    width: '100%'
                });

                updateRowNumbers();
                $('.btn-delete-row').prop('disabled', false);
                rowIdx++;
            });

            // Delete Row
            $(document).on('click', '.btn-delete-row', function (e) {
                e.preventDefault();
                e.stopPropagation();
                if ($('.item-row').length > 1) {
                    $(this).closest('.item-row').remove();
                    updateRowNumbers();
                    calculateGrandTotal();
                    recalcKomponenCoaSection();
                    if ($('.item-row').length === 1) {
                        $('.btn-delete-row').prop('disabled', true);
                    }
                }
            });

            // Listeners for Uang Harian auto-calc
            $(document).on('change', '.provinsi-select, .tipe-select', function() {
                calculateUangHarian($(this).closest('.item-row'));
            });
            
            $(document).on('input', '.lama-hari-input', function() {
                calculateUangHarian($(this).closest('.item-row'));
            });

            // Auto-fill pegawai (NIP, Rekening, Summary)
            $(document).on('change', '.pegawai-select', function() {
                let card = $(this).closest('.item-row');
                let selected = $(this).find(':selected');
                let nama = selected.data('nama') || '';
                let nip = selected.data('nip') || '';
                let rek = selected.data('rek') || '';

                card.find('.input-nama-hidden').val(nama);
                card.find('.nip-input').val(nip);

                if (rek) {
                    card.find('.rekening-input').val(rek).prop('readonly', false);
                    card.find('.rek-hint').addClass('d-none');
                } else {
                    card.find('.rekening-input').val('').prop('readonly', false);
                    if (selected.val() !== '') {
                        card.find('.rek-hint').removeClass('d-none');
                    } else {
                        card.find('.rek-hint').addClass('d-none');
                    }
                }

                card.find('.summary-nama').text(nama || '-');
            });

            // Update Summary Tujuan Live
            $(document).on('input', '.input-tujuan', function() {
                $(this).closest('.item-row').find('.summary-tujuan').text($(this).val() || '-');
            });

            // File select listener to update badge
            $(document).on('change', '.spt-file-input', function() {
                let card = $(this).closest('.item-row');
                let badge = card.find('.file-status-badge');
                if (this.files && this.files.length > 0) {
                    badge.removeClass('bg-secondary text-secondary border-secondary').addClass('bg-success text-white').html('<i class="bi bi-paperclip"></i> SPT: ' + this.files[0].name.substring(0, 15) + '...');
                } else {
                    badge.removeClass('bg-success text-white').addClass('bg-secondary text-white').html('<i class="bi bi-paperclip"></i> SPT Kosong');
                }
            });

            // Collapse icon toggle
            $(document).on('click', '.collapse-trigger', function() {
                let icon = $(this).find('.toggle-icon');
                if ($(this).attr('aria-expanded') === 'true') {
                    // It will collapse
                    icon.removeClass('bi-chevron-down').addClass('bi-chevron-right');
                } else {
                    // It will expand
                    icon.removeClass('bi-chevron-right').addClass('bi-chevron-down');
                }
            });

            function updateRowNumbers() {
                $('.item-row').each(function (index) {
                    $(this).find('.row-number').text(index + 1);
                });
                calculateGrandTotal();
            }

            // Custom UI error toggling validation helper
            @if($errors->any())
                // Ensure all items with validation errors are visible (expanded)
                $('.is-invalid').closest('.peserta-collapse').addClass('show');
                $('.is-invalid').closest('.item-row').addClass('border border-danger');
            @endif
            
            updateRowNumbers();
        });
    </script>
@endpush
