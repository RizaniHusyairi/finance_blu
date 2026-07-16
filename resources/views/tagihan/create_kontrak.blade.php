@extends('layouts.app')
@section('title')
    Buat Tagihan SPK (Termin & BAST)
@endsection

@push('css')
@include('tagihan.partials.form_kontrak_css')
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

    {{-- HERO --}}
    <div class="form-hero">
        <i class="bi bi-receipt-cutoff receipt-illust d-none d-md-block"></i>
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <span class="hero-tag"><i class="bi bi-stars"></i> Penagihan SPK</span>
                <h2><i class="bi bi-cash-stack me-2"></i>Penagihan Termin / BAST</h2>
                <p>Formulir pengajuan pembayaran berdasarkan prestasi pekerjaan SPK. Lengkapi data kontrak, BAST/BAP, verifikator, dan ringkasan nilai.</p>
            </div>
            <a href="{{ url()->previous() }}" class="btn-back-hero">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
        </div>
    </div>

    @if ($errors->any())
        <div class="alert-modern-error">
            <div class="alert-title">
                <i class="bi bi-exclamation-octagon-fill"></i>
                Terdapat kesalahan pada formulir
            </div>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('tagihan.kontrak.store') }}" method="POST" enctype="multipart/form-data" id="formTagihan">
        @csrf

        {{-- ============ A. Pemilihan Kontrak & Termin ============ --}}
        <div class="sec-card">
            <div class="sec-head">
                <span class="sec-icon si-primary"><i class="bi bi-file-earmark-text-fill"></i></span>
                <div>
                    <h6>Pemilihan SPK &amp; Termin</h6>
                    <small>Tentukan SPK dan termin yang akan ditagih.</small>
                </div>
                <span class="sec-letter">A</span>
            </div>
            <div class="sec-body">
                <div class="row g-4">
                    <div class="col-md-6">
                        @if($isPresetTagihan)
                            <label class="form-label modern"><i class="bi bi-bookmark-check-fill text-primary"></i> SPK Terpilih</label>
                            <input type="hidden" name="kontrak_pengadaan_id" id="kontrak_pengadaan_id" value="{{ $selectedKontrak->id }}">
                            <div class="preset-card">
                                <div class="pc-head">
                                    <span class="pc-icon"><i class="bi bi-file-earmark-text-fill"></i></span>
                                    <div>
                                        <div class="pc-sub">SPK</div>
                                        <div class="pc-title">{{ $selectedKontrak->nomor_spk }}</div>
                                    </div>
                                </div>
                                <div class="pc-body">
                                    <div class="pc-row">
                                        <div class="pc-label">Vendor</div>
                                        <div class="pc-value">{{ $selectedKontrak->vendor->nama_perusahaan ?? '-' }}</div>
                                    </div>
                                    <div class="pc-row">
                                        <div class="pc-label">Pekerjaan</div>
                                        <div class="pc-value" style="font-weight:500;">{{ $selectedKontrak->nama_pekerjaan }}</div>
                                    </div>
                                </div>
                                <div class="pc-foot">
                                    <span class="pc-foot-label"><i class="bi bi-cash-stack me-1"></i>Nilai Total Kontrak</span>
                                    <span class="pc-money">Rp {{ number_format($selectedKontrak->nilai_total_kontrak, 0, ',', '.') }}</span>
                                </div>
                            </div>
                        @else
                            <label class="form-label modern" for="kontrak_pengadaan_id">
                                <i class="bi bi-search text-primary"></i> Pilih SPK (Nomor SPK)
                                <span class="text-danger ms-1">*</span>
                            </label>
                            <select class="form-select select2" name="kontrak_pengadaan_id" id="kontrak_pengadaan_id" required onchange="getDetailKontrak(this.value)">
                                <option value="">-- Cari atau ketik Nomor SPK --</option>
                                @foreach($kontraks ?? [] as $k)
                                    <option value="{{ $k->id }}" data-vendor="{{ $k->vendor->nama_perusahaan ?? 'N/A' }}" data-nama="{{ $k->nama_pekerjaan }}" data-nilai="{{ $k->nilai_total_kontrak }}">
                                        {{ $k->nomor_spk }} - {{ Str::limit($k->nama_pekerjaan, 40) }}
                                    </option>
                                @endforeach
                            </select>

                            <div id="panel_info_kontrak" class="preset-card mt-3" style="display: none;">
                                <div class="pc-head">
                                    <span class="pc-icon"><i class="bi bi-file-earmark-text-fill"></i></span>
                                    <div>
                                        <div class="pc-sub">Detail SPK</div>
                                        <div class="pc-title">Ringkasan Vendor &amp; Pekerjaan</div>
                                    </div>
                                </div>
                                <div class="pc-body">
                                    <div class="pc-row">
                                        <div class="pc-label">Vendor</div>
                                        <div class="pc-value" id="info_vendor">-</div>
                                    </div>
                                    <div class="pc-row">
                                        <div class="pc-label">Pekerjaan</div>
                                        <div class="pc-value" style="font-weight:500;" id="info_pekerjaan">-</div>
                                    </div>
                                </div>
                                <div class="pc-foot">
                                    <span class="pc-foot-label"><i class="bi bi-cash-stack me-1"></i>Nilai Total Kontrak</span>
                                    <span class="pc-money" id="info_nilai">-</span>
                                </div>
                            </div>
                        @endif
                    </div>

                    <div class="col-md-6">
                        @if($isPresetTagihan)
                            <label class="form-label modern"><i class="bi bi-collection-fill text-success"></i> Termin yang Akan Ditagih</label>
                            <input type="hidden" name="kontrak_termin_id" id="kontrak_termin_id" value="{{ $selectedTermin->id }}">
                            <div class="preset-card is-success">
                                <div class="pc-head">
                                    <span class="pc-icon"><i class="bi bi-collection-fill"></i></span>
                                    <div>
                                        <div class="pc-sub">Termin Aktif</div>
                                        <div class="pc-title">Termin {{ $selectedTermin->termin_ke }} &middot; {{ str_replace('_', ' ', $selectedTermin->jenis_termin) }}</div>
                                    </div>
                                </div>
                                <div class="pc-body">
                                    <div class="pc-row">
                                        <div class="pc-label">Keterangan</div>
                                        <div class="pc-value" style="font-weight:500;">{{ $selectedTermin->keterangan_termin }}</div>
                                    </div>
                                    @if(!is_null($selectedTermin->persentase ?? null))
                                        <div class="pc-row">
                                            <div class="pc-label">Persentase</div>
                                            <div class="pc-value pc-mono">{{ rtrim(rtrim(number_format($selectedTermin->persentase, 2, ',', '.'), '0'), ',') }}%</div>
                                        </div>
                                    @endif
                                </div>
                                <div class="pc-foot">
                                    <span class="pc-foot-label"><i class="bi bi-cash-stack me-1"></i>Nilai Bruto Termin</span>
                                    <span class="pc-money">Rp {{ number_format($selectedTermin->nilai_bruto_termin, 0, ',', '.') }}</span>
                                </div>
                            </div>
                        @else
                            <label class="form-label modern" for="kontrak_termin_id">
                                <i class="bi bi-collection-fill text-success"></i> Pilih Termin Tagihan
                                <span class="text-danger ms-1">*</span>
                            </label>
                            <select class="form-select select2" name="kontrak_termin_id" id="kontrak_termin_id" required disabled onchange="setBrutoFromTermin()">
                                <option value="">-- Pilih SPK Terlebih Dahulu --</option>
                            </select>
                            <div class="info-banner banner-info mt-3">
                                <i class="bi bi-info-circle-fill"></i>
                                <span>Hanya termin dengan status <strong>READY_TO_BILL</strong> yang akan tampil dalam daftar.</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- ============ B. Legalitas Pekerjaan (Berita Acara) ============ --}}
        <div class="sec-card">
            <div class="sec-head">
                <span class="sec-icon si-info"><i class="bi bi-file-earmark-check-fill"></i></span>
                <div>
                    <h6>Legalitas Pekerjaan (Berita Acara)</h6>
                    <small>Tanggal BAPP, BAST, BAP dan data pemeriksa.</small>
                </div>
                <span class="sec-letter">B</span>
            </div>
            <div class="sec-body">
                <div class="row g-4">
                    <div class="col-md-4">
                        <label class="form-label modern"><i class="bi bi-clipboard2-check text-info"></i> Nomor BAPP <span class="text-muted fw-normal">(Pemeriksaan)</span></label>
                        <div class="auto-gen mb-2">
                            <i class="bi bi-magic"></i>
                            <span>Akan digenerate:</span>
                            <strong>{{ $previewBapp }}</strong>
                        </div>
                        <label class="form-label modern" style="font-size:.7rem;color:#94a3b8;">Tanggal BAPP</label>
                        <input type="date" class="form-control modern" name="tanggal_bapp" value="{{ old('tanggal_bapp', now()->format('Y-m-d')) }}">
                        <label class="form-label modern mt-3" for="gambar_rab_bapp">
                            <i class="bi bi-file-earmark-image text-success"></i> Gambar RAB
                            <span class="text-danger ms-1">*</span>
                        </label>
                        <label class="file-drop" data-accept=".jpg,.jpeg,.png" data-max-mb="5" data-target="gambar_rab_bapp">
                            <input type="file" id="gambar_rab_bapp" name="gambar_rab_bapp" accept=".jpg,.jpeg,.png" required>
                            <div class="fd-default">
                                <div class="fd-icon"><i class="bi bi-cloud-arrow-up-fill"></i></div>
                                <div class="fd-title">Tarik &amp; lepaskan, atau <strong>klik untuk memilih</strong></div>
                                <div class="fd-sub">Gambar RAB yang akan ditampilkan pada draft PDF BAPP.</div>
                                <div class="fd-meta"><i class="bi bi-file-earmark-image"></i> JPG / PNG &middot; Maks 5MB</div>
                            </div>
                            <div class="fd-preview">
                                <div class="fp-icon is-img"><i class="bi bi-file-earmark-image-fill"></i></div>
                                <div class="fp-info">
                                    <div class="fp-name">-</div>
                                    <div class="fp-detail">
                                        <span class="fp-size">0 KB</span>
                                        <span class="fp-type text-muted">Gambar</span>
                                    </div>
                                    <div class="fp-bar"><span style="width:0%"></span></div>
                                </div>
                                <button type="button" class="fp-remove" title="Hapus berkas"><i class="bi bi-x-lg"></i></button>
                            </div>
                        </label>
                    </div>
                    <div class="col-md-4" id="wrapper_bast_fields" style="display: none;">
                        <label class="form-label modern"><i class="bi bi-truck text-warning"></i> Nomor BAST <span class="text-danger ms-1">*</span> <span class="text-muted fw-normal ms-1">(Serah Terima)</span></label>
                        <div class="auto-gen mb-2">
                            <i class="bi bi-magic"></i>
                            <span>Akan digenerate:</span>
                            <strong>{{ $previewBast }}</strong>
                        </div>
                        <label class="form-label modern" style="font-size:.7rem;color:#94a3b8;">Tanggal BAST</label>
                        <input type="date" class="form-control modern" name="tanggal_bast" id="tanggal_bast" value="{{ old('tanggal_bast', now()->format('Y-m-d')) }}">
                    </div>
                    <div class="col-md-4" id="wrapper_bap_fields">
                        <label class="form-label modern"><i class="bi bi-cash-coin text-success"></i> Nomor BAP <span class="text-danger ms-1">*</span> <span class="text-muted fw-normal ms-1">(Pembayaran)</span></label>
                        <div class="auto-gen mb-2">
                            <i class="bi bi-magic"></i>
                            <span>Akan digenerate:</span>
                            <strong>{{ $previewBap }}</strong>
                        </div>
                        <label class="form-label modern" style="font-size:.7rem;color:#94a3b8;">Tanggal BAP</label>
                        <input type="date" class="form-control modern" name="tanggal_bap" value="{{ old('tanggal_bap', now()->format('Y-m-d')) }}" required>
                    </div>

                    <div class="col-12">
                        <div class="d-flex align-items-center gap-2 mb-3 mt-2 pt-3" style="border-top:1px dashed #e2e8f0;">
                            <span class="badge" style="background:rgba(14,165,233,.10);color:#0369a1;font-weight:700;letter-spacing:.04em;padding:.4rem .75rem;border-radius:999px;">
                                <i class="bi bi-person-vcard me-1"></i> Pemeriksa Hasil Pekerjaan (BAPP)
                            </span>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label modern" for="namaPemeriksaSelect">
                                    <i class="bi bi-person-badge text-primary"></i> Nama Pemeriksa
                                    <span class="text-danger ms-1">*</span>
                                </label>
                                <select class="form-select select2" name="nama_pemeriksa" id="namaPemeriksaSelect" required>
                                    <option value="">-- Pilih Pegawai --</option>
                                    @foreach($pegawaiList as $peg)
                                        <option
                                            value="{{ $peg->nama_lengkap }}"
                                            data-nip="{{ $peg->nip }}"
                                            data-jabatan="{{ $peg->jabatan }}"
                                            data-wa="{{ $peg->nomor_hp }}"
                                            @selected(old('nama_pemeriksa') === $peg->nama_lengkap)
                                        >{{ $peg->nama_lengkap }}</option>
                                    @endforeach
                                </select>
                                <small class="text-muted d-block mt-1" style="font-size:.74rem;"><i class="bi bi-magic me-1"></i>NIP &amp; Jabatan otomatis.</small>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label modern"><i class="bi bi-hash text-secondary"></i> NIP Pemeriksa</label>
                                <input type="text" class="form-control modern" name="nip_pemeriksa" id="nipPemeriksaInput" placeholder="Akan terisi setelah memilih nama" value="{{ old('nip_pemeriksa') }}" readonly>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label modern"><i class="bi bi-briefcase text-secondary"></i> Jabatan Pemeriksa <span class="text-danger ms-1">*</span></label>
                                <input type="text" class="form-control modern" name="jabatan_pemeriksa" id="jabatanPemeriksaInput" placeholder="Akan terisi setelah memilih nama" value="{{ old('jabatan_pemeriksa') }}" required readonly>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label modern"><i class="bi bi-whatsapp text-success"></i> No. WA Pemeriksa <span class="text-danger ms-1">*</span></label>
                                <input type="text" class="form-control modern" name="wa_pemeriksa" id="waPemeriksaInput" placeholder="Contoh: 0812..." value="{{ old('wa_pemeriksa') }}" required>
                                <small class="text-muted d-block mt-1" style="font-size:.74rem;">Digunakan untuk link TTE BAPP.</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ============ C. Verifikator Penagihan ============ --}}
        <div class="sec-card">
            <div class="sec-head">
                <span class="sec-icon si-success"><i class="bi bi-people-fill"></i></span>
                <div>
                    <h6>Verifikator Penagihan</h6>
                    <small>Penanda tangan dokumen tagihan ini.</small>
                </div>
                <span class="sec-letter">C</span>
            </div>
            <div class="sec-body">
                <div class="info-banner banner-info mb-4">
                    <i class="bi bi-info-circle-fill"></i>
                    <span>
                        Pilih pejabat yang akan menjadi verifikator/penanda tangan untuk tagihan ini.
                        <strong>PPK</strong> ditentukan otomatis dari kontrak yang dipilih.
                        Nama &amp; NIP akan dipotret (snapshot) dan ditampilkan pada dokumen yang dicetak.
                    </span>
                </div>

                @php
                    $verifikatorFields = [
                        ['key' => 'ppspm',                 'label' => 'PPSPM',                                          'icon' => 'bi-shield-check',     'options' => $verifikatorOptions['ppspm'] ?? collect()],
                        ['key' => 'koordinator_keuangan',  'label' => 'Koordinator Keuangan',                            'icon' => 'bi-diagram-3-fill',   'options' => $verifikatorOptions['koordinator_keuangan'] ?? collect()],
                        ['key' => 'bendahara_pengeluaran', 'label' => 'Bendahara Pengeluaran',                           'icon' => 'bi-wallet2',          'options' => $verifikatorOptions['bendahara_pengeluaran'] ?? collect()],
                        ['key' => 'bendahara_penerimaan',  'label' => 'Bendahara Penerimaan',                            'icon' => 'bi-piggy-bank-fill',  'options' => $verifikatorOptions['bendahara_penerimaan'] ?? collect()],
                        ['key' => 'kasubbag',              'label' => 'Kepala Subbagian Keuangan dan Tata Usaha',         'icon' => 'bi-person-workspace','options' => $verifikatorOptions['kasubbag'] ?? collect()],
                    ];
                @endphp

                <div class="row g-3">
                    @foreach($verifikatorFields as $vf)
                        <div class="col-md-6">
                            <label class="form-label modern" for="verif_{{ $vf['key'] }}">
                                <i class="bi {{ $vf['icon'] }} text-success"></i> {{ $vf['label'] }}
                                <span class="text-danger ms-1">*</span>
                            </label>
                            <select
                                id="verif_{{ $vf['key'] }}"
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
                            <div class="small text-muted mt-1" style="font-size:.76rem;" id="info_{{ $vf['key'] }}"></div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- ============ D. Dokumen Vendor & Ringkasan Nilai ============ --}}
        <div class="sec-card">
            <div class="sec-head">
                <span class="sec-icon si-warning"><i class="bi bi-calculator-fill"></i></span>
                <div>
                    <h6>Dokumen Vendor &amp; Ringkasan Nilai</h6>
                    <small>Detail invoice dan perhitungan netto.</small>
                </div>
                <span class="sec-letter">D</span>
            </div>
            <div class="sec-body">
                <div class="row g-4">
                    <div class="col-md-4">
                        <label class="form-label modern" for="nomor_invoice">
                            <i class="bi bi-receipt text-warning"></i> Nomor Invoice / Permohonan
                            <span class="text-danger ms-1">*</span>
                        </label>
                        <input type="text" id="nomor_invoice" class="form-control modern" name="nomor_invoice" placeholder="Contoh: INV/2026/001" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label modern" for="tanggal_invoice">
                            <i class="bi bi-calendar-event text-warning"></i> Tanggal Invoice
                            <span class="text-danger ms-1">*</span>
                        </label>
                        <input type="date" id="tanggal_invoice" class="form-control modern" name="tanggal_invoice" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label modern"><i class="bi bi-cash-stack text-success"></i> Nilai Bruto (DPP + PPN)</label>
                        <input type="text" class="form-control modern fw-bold fs-5" id="total_bruto_display" value="{{ $isPresetTagihan ? 'Rp ' . number_format($selectedTermin->nilai_bruto_termin, 0, ',', '.') : 'Rp 0' }}" readonly>
                        <input type="hidden" name="total_bruto" id="total_bruto" value="{{ $isPresetTagihan ? $selectedTermin->nilai_bruto_termin : 0 }}">
                        <small class="text-muted d-block mt-1" style="font-size:.74rem;"><i class="bi bi-magic me-1"></i>Terisi otomatis dari Termin.</small>
                    </div>
                </div>

                <div class="info-banner banner-warning mt-4 d-none" id="info_potongan_um">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <span>Kontrak ini masih memiliki <strong>sisa uang muka</strong>. Potongan angsuran uang muka akan otomatis diperhitungkan pada termin ini.</span>
                </div>

                <div class="row g-3 mt-4 pt-4" style="border-top:1px dashed #e2e8f0;">
                    <div class="col-md-4">
                        <div class="nominal-card" style="border-color:rgba(99,102,241,.20);">
                            <div class="nc-label" style="color:#4338ca;"><i class="bi bi-cash me-1"></i>Nilai Bruto</div>
                            <div class="fw-bold fs-5 mb-0" id="summary_bruto_display" style="color:#0f172a;font-variant-numeric:tabular-nums;">{{ $isPresetTagihan ? 'Rp ' . number_format($selectedTermin->nilai_bruto_termin, 0, ',', '.') : 'Rp 0' }}</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="nominal-card" style="border-color:rgba(245,158,11,.30);">
                            <div class="nc-label" style="color:#b45309;"><i class="bi bi-dash-circle me-1"></i>Potongan Angsuran UM</div>
                            <div class="fw-bold fs-5 mb-0" id="potongan_um_display" style="color:#b45309;font-variant-numeric:tabular-nums;">Rp {{ number_format($initialPotonganAngsuran, 0, ',', '.') }}</div>
                            <input type="hidden" name="potongan_angsuran_uang_muka" id="potongan_angsuran_uang_muka" value="{{ $initialPotonganAngsuran }}">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="nominal-card" style="background:linear-gradient(135deg,rgba(16,185,129,.08),rgba(16,185,129,.02));">
                            <div class="nc-label"><i class="bi bi-check-circle-fill me-1"></i>Nilai Netto</div>
                            <div class="fw-bold fs-3 mb-0" id="total_netto_display" style="color:#047857;font-variant-numeric:tabular-nums;letter-spacing:-.01em;">Rp 0</div>
                            <input type="hidden" name="total_netto" id="total_netto" value="0">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ============ E. Arsip Digital Pekerjaan ============ --}}
        <div class="sec-card">
            <div class="sec-head">
                <span class="sec-icon si-danger"><i class="bi bi-cloud-arrow-up-fill"></i></span>
                <div>
                    <h6>Arsip Digital Pekerjaan</h6>
                    <small>Format .PDF / .ZIP, maksimal 5MB per berkas.</small>
                </div>
                <span class="sec-letter">E</span>
            </div>
            <div class="sec-body">
                <div class="info-banner banner-info mb-4">
                    <i class="bi bi-info-circle-fill"></i>
                    <span><strong>Pemberitahuan:</strong> Dokumen final bertandatangan untuk BAPP, BAST, dan BAP dikelola nanti melalui halaman <strong>Detail Tagihan (Working Hub)</strong> setelah draft ini tersimpan.</span>
                </div>
                <div class="row g-4">
                    <div class="col-md-6">
                        <label class="form-label modern" for="file_invoice">
                            <i class="bi bi-file-earmark-pdf-fill text-danger"></i> Surat Permohonan / Invoice
                            <span class="text-danger ms-1">*</span>
                        </label>
                        <label class="file-drop" data-accept=".pdf" data-max-mb="5" data-target="file_invoice">
                            <input type="file" id="file_invoice" name="file_invoice" accept=".pdf" required>
                            <div class="fd-default">
                                <div class="fd-icon"><i class="bi bi-cloud-arrow-up-fill"></i></div>
                                <div class="fd-title">Tarik &amp; lepaskan, atau <strong>klik untuk memilih</strong></div>
                                <div class="fd-sub">Surat Permohonan pembayaran resmi dari vendor.</div>
                                <div class="fd-meta"><i class="bi bi-file-earmark-pdf"></i> PDF &middot; Maks 5MB</div>
                            </div>
                            <div class="fd-preview">
                                <div class="fp-icon"><i class="bi bi-file-earmark-pdf-fill"></i></div>
                                <div class="fp-info">
                                    <div class="fp-name">-</div>
                                    <div class="fp-detail">
                                        <span class="fp-size">0 KB</span>
                                        <span class="fp-type text-muted">PDF</span>
                                    </div>
                                    <div class="fp-bar"><span style="width:0%"></span></div>
                                </div>
                                <button type="button" class="fp-remove" title="Hapus berkas"><i class="bi bi-x-lg"></i></button>
                            </div>
                        </label>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label modern" for="file_lampiran_lainnya">
                            <i class="bi bi-images text-secondary"></i> Lampiran Laporan (Foto/Dokumentasi)
                            <span class="text-muted fw-normal ms-1">(Opsional)</span>
                        </label>
                        <label class="file-drop" data-accept=".pdf,.zip" data-max-mb="5" data-target="file_lampiran_lainnya">
                            <input type="file" id="file_lampiran_lainnya" name="file_lampiran_lainnya" accept=".pdf,.zip">
                            <div class="fd-default">
                                <div class="fd-icon"><i class="bi bi-cloud-arrow-up-fill"></i></div>
                                <div class="fd-title">Tarik &amp; lepaskan, atau <strong>klik untuk memilih</strong></div>
                                <div class="fd-sub">Laporan progres, dokumentasi pekerjaan, atau backup.</div>
                                <div class="fd-meta"><i class="bi bi-file-earmark-zip"></i> PDF / ZIP &middot; Maks 5MB</div>
                            </div>
                            <div class="fd-preview">
                                <div class="fp-icon"><i class="bi bi-file-earmark-zip-fill"></i></div>
                                <div class="fp-info">
                                    <div class="fp-name">-</div>
                                    <div class="fp-detail">
                                        <span class="fp-size">0 KB</span>
                                        <span class="fp-type text-muted">-</span>
                                    </div>
                                    <div class="fp-bar"><span style="width:0%"></span></div>
                                </div>
                                <button type="button" class="fp-remove" title="Hapus berkas"><i class="bi bi-x-lg"></i></button>
                            </div>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        {{-- ============ Submit Bar ============ --}}
        <div class="submit-bar">
            <div class="me-auto d-none d-md-flex align-items-center gap-2 text-muted" style="font-size:.82rem;">
                <i class="bi bi-shield-lock"></i>
                <span>Pastikan seluruh data telah diisi dengan benar sebelum menyimpan draft.</span>
            </div>
            <button type="reset" class="btn-cancel-submit">
                <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
            </button>
            <button type="submit" class="btn-submit-primary">
                <i class="bi bi-save2-fill"></i> Buat Draft Tagihan
            </button>
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
        $('.select2').select2({
            theme: 'default',
            width: '100%',
            dropdownAutoWidth: true,
        });
        $('.verifikator-select').select2({
            theme: 'default',
            width: '100%',
            dropdownAutoWidth: true,
            placeholder: function () {
                return $(this).find('option:first').text();
            },
        });

        if (isPresetTagihan) {
            toggleBastFields(selectedTerminMeta?.jenis_termin ?? null);
            updatePotonganAngsuranDisplay(selectedTerminMeta?.potongan_angsuran_uang_muka ?? 0);
            hitungTotalNetto();
        }
    });

    function getDetailKontrak(idKontrak) {
        if(!idKontrak) {
            $('#panel_info_kontrak').hide();
            let $termin0 = $('#kontrak_termin_id');
            if ($termin0.hasClass('select2-hidden-accessible')) { $termin0.select2('destroy'); }
            $termin0.html('<option value="">-- Pilih SPK Terlebih Dahulu --</option>').prop('disabled', true);
            $termin0.select2({ theme: 'default', width: '100%' });
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
        let $termin = $('#kontrak_termin_id');
        if ($termin.hasClass('select2-hidden-accessible')) {
            $termin.select2('destroy');
        }
        $termin.html(html).prop('disabled', false);
        $termin.select2({ theme: 'default', width: '100%' });
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
        // ============ File Drop Zones ============
        document.querySelectorAll('.file-drop').forEach(function (zone) {
            const input = zone.querySelector('input[type="file"]');
            if (!input) return;

            const preview = zone.querySelector('.fd-preview');
            const fpName = preview.querySelector('.fp-name');
            const fpSize = preview.querySelector('.fp-size');
            const fpType = preview.querySelector('.fp-type');
            const fpIcon = preview.querySelector('.fp-icon');
            const fpBar = preview.querySelector('.fp-bar > span');
            const fpRemove = preview.querySelector('.fp-remove');
            const maxMb = parseFloat(zone.dataset.maxMb || '5');
            const maxBytes = maxMb * 1024 * 1024;

            const fmtSize = function (bytes) {
                if (bytes < 1024) return bytes + ' B';
                if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
                return (bytes / (1024 * 1024)).toFixed(2) + ' MB';
            };

            const setIconForFile = function (file) {
                fpIcon.classList.remove('is-zip', 'is-img');
                const name = (file.name || '').toLowerCase();
                const isZip = /\.zip$/.test(name) || file.type === 'application/zip' || file.type === 'application/x-zip-compressed';
                const isImg = (file.type || '').startsWith('image/');
                let html = '<i class="bi bi-file-earmark-pdf-fill"></i>';
                if (isZip) {
                    fpIcon.classList.add('is-zip');
                    html = '<i class="bi bi-file-earmark-zip-fill"></i>';
                } else if (isImg) {
                    fpIcon.classList.add('is-img');
                    html = '<i class="bi bi-file-earmark-image-fill"></i>';
                }
                fpIcon.innerHTML = html;
            };

            const renderFile = function (file) {
                if (!file) {
                    zone.classList.remove('is-filled', 'is-error');
                    return;
                }

                const size = file.size || 0;
                const ratio = Math.min(size / maxBytes, 1);
                const ext = (file.name.split('.').pop() || '').toUpperCase();

                fpName.textContent = file.name;
                fpSize.textContent = fmtSize(size);
                fpType.textContent = ext;
                setIconForFile(file);

                fpBar.classList.remove('is-warn', 'is-error');
                fpSize.classList.remove('is-warn', 'is-error');
                if (ratio >= 1) {
                    fpBar.classList.add('is-error');
                    fpSize.classList.add('is-error');
                } else if (ratio >= 0.8) {
                    fpBar.classList.add('is-warn');
                    fpSize.classList.add('is-warn');
                }
                fpBar.style.width = (ratio * 100).toFixed(0) + '%';

                zone.classList.remove('is-error');
                if (size > maxBytes) {
                    zone.classList.add('is-error');
                    zone.classList.remove('is-filled');
                    fpName.textContent = file.name + ' — melebihi ' + maxMb + 'MB';
                } else {
                    zone.classList.add('is-filled');
                }
            };

            input.addEventListener('change', function () {
                const file = input.files && input.files[0];
                renderFile(file || null);
            });

            fpRemove.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                input.value = '';
                zone.classList.remove('is-filled', 'is-error');
                fpName.textContent = '-';
                fpSize.textContent = '0 KB';
                fpType.textContent = '-';
                fpBar.style.width = '0%';
            });

            // Drag & drop
            ['dragenter', 'dragover'].forEach(function (evt) {
                zone.addEventListener(evt, function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    zone.classList.add('is-drag');
                });
            });
            ['dragleave', 'dragend', 'drop'].forEach(function (evt) {
                zone.addEventListener(evt, function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    zone.classList.remove('is-drag');
                });
            });
            zone.addEventListener('drop', function (e) {
                const dt = e.dataTransfer;
                if (!dt || !dt.files || !dt.files.length) return;
                try {
                    input.files = dt.files;
                } catch (err) {
                    // Fallback for browsers that don't allow direct assignment
                    const dataTransfer = new DataTransfer();
                    dataTransfer.items.add(dt.files[0]);
                    input.files = dataTransfer.files;
                }
                input.dispatchEvent(new Event('change', { bubbles: true }));
            });
        });

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

    // Auto-fill NIP, Jabatan & WA saat memilih Nama Pemeriksa dari dropdown pegawai
    $(document).ready(function () {
        const $namaSelect = $('#namaPemeriksaSelect');
        const $nipInput = $('#nipPemeriksaInput');
        const $jabatanInput = $('#jabatanPemeriksaInput');
        const $waInput = $('#waPemeriksaInput');

        if (!$namaSelect.length || !$nipInput.length || !$jabatanInput.length || !$waInput.length) return;

        function syncPemeriksa() {
            const $opt = $namaSelect.find(':selected');
            if (!$opt.val()) {
                $nipInput.val('');
                $jabatanInput.val('');
                $waInput.val('');
                return;
            }
            $nipInput.val($opt.data('nip') || '');
            $jabatanInput.val($opt.data('jabatan') || '');
            $waInput.val($opt.data('wa') || '');
        }

        $namaSelect.on('change', syncPemeriksa);

        // Inisialisasi (mis. setelah validasi gagal & old() mengembalikan pilihan)
        if ($namaSelect.val()) syncPemeriksa();
    });
</script>
@endpush
