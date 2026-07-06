@extends('layouts.app')
@section('title')
    Edit Tagihan Termin — {{ $tagihan->nomor_tagihan }}
@endsection

@push('css')
@include('tagihan.partials.form_kontrak_css')
@endpush
@section('content')
    @php
        $wajibBast = $termin->jenis_termin === 'PELUNASAN';
        $arsipAktif = $detailKontrak->arsipDokumen->where('is_active', true);
        $arsipInvoice = $arsipAktif->firstWhere('jenis_dokumen', 'INVOICE');
        $arsipRab = $arsipAktif->firstWhere('jenis_dokumen', 'BAPP_GAMBAR_RAB');
        $arsipLampiran = $arsipAktif->firstWhere('jenis_dokumen', 'LAMPIRAN_LAINNYA');

        $potonganAngsuran = (float) $tagihan->potonganTagihan
            ->where('jenis_potongan', 'ANGSURAN_UANG_MUKA')
            ->sum('nominal_potongan');

        $verifikatorTerpilih = [
            'ppspm'                 => $tagihan->ppspm_user_id,
            'koordinator_keuangan'  => $tagihan->koordinator_keuangan_user_id,
            'bendahara_pengeluaran' => $tagihan->bendahara_pengeluaran_user_id,
            'bendahara_penerimaan'  => $tagihan->bendahara_penerimaan_user_id,
            'kasubbag'              => $tagihan->kasubbag_user_id,
        ];

        // Nama pemeriksa tersimpan mungkin tidak ada lagi di master pegawai —
        // tetap tampilkan sebagai pilihan agar data tidak hilang saat disimpan.
        $namaPemeriksaTersimpan = old('nama_pemeriksa', $detailKontrak->nama_pemeriksa);
        $pemeriksaAdaDiMaster = $pegawaiList->contains(fn ($p) => $p->nama_lengkap === $namaPemeriksaTersimpan);
    @endphp

    {{-- HERO --}}
    <div class="form-hero">
        <i class="bi bi-receipt-cutoff receipt-illust d-none d-md-block"></i>
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <span class="hero-tag"><i class="bi bi-pencil-square"></i> Edit Penagihan Kontrak</span>
                <h2><i class="bi bi-cash-stack me-2"></i>Edit Tagihan Termin</h2>
                <p>{{ $tagihan->nomor_tagihan }} &middot; Perbarui data BA, pemeriksa, penanda tangan, dan arsip selama tagihan belum diajukan.</p>
            </div>
            <a href="{{ route('tagihan.kontrak.show', $tagihan->id) }}" class="btn-back-hero">
                <i class="bi bi-arrow-left"></i> Kembali ke Detail
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

    <form action="{{ route('tagihan.kontrak.update', $tagihan->id) }}" method="POST" enctype="multipart/form-data" id="formTagihan">
        @csrf
        @method('PUT')

        {{-- ============ A. Kontrak & Termin (terkunci) ============ --}}
        <div class="sec-card">
            <div class="sec-head">
                <span class="sec-icon si-primary"><i class="bi bi-file-earmark-text-fill"></i></span>
                <div>
                    <h6>Kontrak &amp; Termin</h6>
                    <small>Terkunci — mengikuti tagihan yang sudah dibuat.</small>
                </div>
                <span class="sec-letter">A</span>
            </div>
            <div class="sec-body">
                <div class="row g-4">
                    <div class="col-md-6">
                        <label class="form-label modern"><i class="bi bi-bookmark-check-fill text-primary"></i> Kontrak Terpilih</label>
                        <div class="preset-card">
                            <div class="pc-head">
                                <span class="pc-icon"><i class="bi bi-file-earmark-text-fill"></i></span>
                                <div>
                                    <div class="pc-sub">Kontrak SPK</div>
                                    <div class="pc-title">{{ $kontrak->nomor_spk }}</div>
                                </div>
                            </div>
                            <div class="pc-body">
                                <div class="pc-row">
                                    <div class="pc-label">Vendor</div>
                                    <div class="pc-value">{{ $kontrak->vendor->nama_perusahaan ?? '-' }}</div>
                                </div>
                                <div class="pc-row">
                                    <div class="pc-label">Pekerjaan</div>
                                    <div class="pc-value" style="font-weight:500;">{{ $kontrak->nama_pekerjaan }}</div>
                                </div>
                            </div>
                            <div class="pc-foot">
                                <span class="pc-foot-label"><i class="bi bi-cash-stack me-1"></i>Nilai Total Kontrak</span>
                                <span class="pc-money">Rp {{ number_format($kontrak->nilai_total_kontrak, 0, ',', '.') }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label modern"><i class="bi bi-collection-fill text-success"></i> Termin yang Ditagih</label>
                        <div class="preset-card is-success">
                            <div class="pc-head">
                                <span class="pc-icon"><i class="bi bi-collection-fill"></i></span>
                                <div>
                                    <div class="pc-sub">Termin Aktif</div>
                                    <div class="pc-title">Termin {{ $termin->termin_ke }} &middot; {{ str_replace('_', ' ', $termin->jenis_termin) }}</div>
                                </div>
                            </div>
                            <div class="pc-body">
                                <div class="pc-row">
                                    <div class="pc-label">Keterangan</div>
                                    <div class="pc-value" style="font-weight:500;">{{ $termin->keterangan_termin }}</div>
                                </div>
                                @if(!is_null($termin->persentase ?? null))
                                    <div class="pc-row">
                                        <div class="pc-label">Persentase</div>
                                        <div class="pc-value pc-mono">{{ rtrim(rtrim(number_format($termin->persentase, 2, ',', '.'), '0'), ',') }}%</div>
                                    </div>
                                @endif
                            </div>
                            <div class="pc-foot">
                                <span class="pc-foot-label"><i class="bi bi-cash-stack me-1"></i>Nilai Bruto Termin</span>
                                <span class="pc-money">Rp {{ number_format($termin->nilai_bruto_termin, 0, ',', '.') }}</span>
                            </div>
                        </div>
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
                            <i class="bi bi-lock-fill"></i>
                            <span>Nomor tetap:</span>
                            <strong>{{ $detailKontrak->nomor_bapp ?? '-' }}</strong>
                        </div>
                        <label class="form-label modern" style="font-size:.7rem;color:#94a3b8;">Tanggal BAPP</label>
                        <input type="date" class="form-control modern" name="tanggal_bapp" value="{{ old('tanggal_bapp', optional($detailKontrak->tanggal_bapp)->format('Y-m-d')) }}">
                        <label class="form-label modern mt-3" for="gambar_rab_bapp">
                            <i class="bi bi-file-earmark-image text-success"></i> Gambar RAB
                            <span class="text-muted fw-normal ms-1">(kosongkan bila tidak diganti)</span>
                        </label>
                        <label class="file-drop {{ $arsipRab ? 'is-filled' : '' }}" data-accept=".jpg,.jpeg,.png" data-max-mb="5" data-target="gambar_rab_bapp">
                            <input type="file" id="gambar_rab_bapp" name="gambar_rab_bapp" accept=".jpg,.jpeg,.png">
                            <div class="fd-default">
                                <div class="fd-icon"><i class="bi bi-cloud-arrow-up-fill"></i></div>
                                <div class="fd-title">Tarik &amp; lepaskan, atau <strong>klik untuk memilih</strong></div>
                                <div class="fd-sub">Gambar RAB yang akan ditampilkan pada draft PDF BAPP.</div>
                                <div class="fd-meta"><i class="bi bi-file-earmark-image"></i> JPG / PNG &middot; Maks 5MB</div>
                            </div>
                            <div class="fd-preview">
                                <div class="fp-icon is-img"><i class="bi bi-file-earmark-image-fill"></i></div>
                                <div class="fp-info">
                                    <div class="fp-name">{{ $arsipRab->nama_file_asli ?? '-' }}</div>
                                    <div class="fp-detail">
                                        <span class="fp-size">{{ $arsipRab ? 'Tersimpan' : '0 KB' }}</span>
                                        <span class="fp-type text-muted">Gambar</span>
                                    </div>
                                    <div class="fp-bar"><span style="width:0%"></span></div>
                                </div>
                                <button type="button" class="fp-remove" title="Hapus berkas"><i class="bi bi-x-lg"></i></button>
                            </div>
                        </label>
                    </div>
                    @if($wajibBast)
                        <div class="col-md-4" id="wrapper_bast_fields">
                            <label class="form-label modern"><i class="bi bi-truck text-warning"></i> Nomor BAST <span class="text-danger ms-1">*</span> <span class="text-muted fw-normal ms-1">(Serah Terima)</span></label>
                            <div class="auto-gen mb-2">
                                <i class="bi bi-lock-fill"></i>
                                <span>Nomor tetap:</span>
                                <strong>{{ $detailKontrak->nomor_bast ?? '-' }}</strong>
                            </div>
                            <label class="form-label modern" style="font-size:.7rem;color:#94a3b8;">Tanggal BAST</label>
                            <input type="date" class="form-control modern" name="tanggal_bast" id="tanggal_bast" required value="{{ old('tanggal_bast', optional($detailKontrak->tanggal_bast)->format('Y-m-d')) }}">
                        </div>
                    @endif
                    <div class="col-md-4" id="wrapper_bap_fields">
                        <label class="form-label modern"><i class="bi bi-cash-coin text-success"></i> Nomor BAP <span class="text-danger ms-1">*</span> <span class="text-muted fw-normal ms-1">(Pembayaran)</span></label>
                        <div class="auto-gen mb-2">
                            <i class="bi bi-lock-fill"></i>
                            <span>Nomor tetap:</span>
                            <strong>{{ $detailKontrak->nomor_bap ?? '-' }}</strong>
                        </div>
                        <label class="form-label modern" style="font-size:.7rem;color:#94a3b8;">Tanggal BAP</label>
                        <input type="date" class="form-control modern" name="tanggal_bap" required value="{{ old('tanggal_bap', optional($detailKontrak->tanggal_bap)->format('Y-m-d')) }}">
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
                                    @if($namaPemeriksaTersimpan && ! $pemeriksaAdaDiMaster)
                                        <option value="{{ $namaPemeriksaTersimpan }}" selected
                                            data-nip="{{ $detailKontrak->nip_pemeriksa }}"
                                            data-jabatan="{{ $detailKontrak->jabatan_pemeriksa }}"
                                            data-wa="{{ $detailKontrak->wa_pemeriksa }}"
                                        >{{ $namaPemeriksaTersimpan }}</option>
                                    @endif
                                    @foreach($pegawaiList as $peg)
                                        <option
                                            value="{{ $peg->nama_lengkap }}"
                                            data-nip="{{ $peg->nip }}"
                                            data-jabatan="{{ $peg->jabatan }}"
                                            data-wa="{{ $peg->nomor_hp }}"
                                            @selected($namaPemeriksaTersimpan === $peg->nama_lengkap)
                                        >{{ $peg->nama_lengkap }}</option>
                                    @endforeach
                                </select>
                                <small class="text-muted d-block mt-1" style="font-size:.74rem;"><i class="bi bi-magic me-1"></i>NIP &amp; Jabatan otomatis saat memilih nama baru.</small>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label modern"><i class="bi bi-hash text-secondary"></i> NIP Pemeriksa</label>
                                <input type="text" class="form-control modern" name="nip_pemeriksa" id="nipPemeriksaInput" value="{{ old('nip_pemeriksa', $detailKontrak->nip_pemeriksa) }}" readonly>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label modern"><i class="bi bi-briefcase text-secondary"></i> Jabatan Pemeriksa <span class="text-danger ms-1">*</span></label>
                                <input type="text" class="form-control modern" name="jabatan_pemeriksa" id="jabatanPemeriksaInput" value="{{ old('jabatan_pemeriksa', $detailKontrak->jabatan_pemeriksa) }}" required readonly>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label modern"><i class="bi bi-whatsapp text-success"></i> No. WA Pemeriksa <span class="text-danger ms-1">*</span></label>
                                <input type="text" class="form-control modern" name="wa_pemeriksa" id="waPemeriksaInput" value="{{ old('wa_pemeriksa', $detailKontrak->wa_pemeriksa) }}" required>
                                <small class="text-muted d-block mt-1" style="font-size:.74rem;">Digunakan untuk link TTE BAPP.</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ============ C. Verifikator / Penanda Tangan ============ --}}
        <div class="sec-card">
            <div class="sec-head">
                <span class="sec-icon si-success"><i class="bi bi-people-fill"></i></span>
                <div>
                    <h6>Pejabat Penanda Tangan</h6>
                    <small>Penanda tangan dokumen pencairan tagihan ini.</small>
                </div>
                <span class="sec-letter">C</span>
            </div>
            <div class="sec-body">
                <div class="info-banner banner-info mb-4">
                    <i class="bi bi-info-circle-fill"></i>
                    <span>
                        <strong>PPK</strong> ditentukan otomatis dari kontrak: <strong>{{ $tagihan->ppk_nama_snapshot ?? '-' }}</strong>.
                        Nama &amp; NIP pejabat akan dipotret ulang (snapshot) saat perubahan disimpan.
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
                                        @selected((int) old($vf['key'].'_user_id', $verifikatorTerpilih[$vf['key']]) === (int) $opt['id'])
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
                    <small>Detail invoice — nilai bruto/netto terkunci mengikuti termin.</small>
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
                        <input type="text" id="nomor_invoice" class="form-control modern" name="nomor_invoice" required value="{{ old('nomor_invoice', $detailKontrak->nomor_invoice) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label modern" for="tanggal_invoice">
                            <i class="bi bi-calendar-event text-warning"></i> Tanggal Invoice
                            <span class="text-danger ms-1">*</span>
                        </label>
                        <input type="date" id="tanggal_invoice" class="form-control modern" name="tanggal_invoice" required value="{{ old('tanggal_invoice', optional($detailKontrak->tanggal_invoice)->format('Y-m-d')) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label modern"><i class="bi bi-cash-stack text-success"></i> Nilai Bruto (DPP + PPN)</label>
                        <input type="text" class="form-control modern fw-bold fs-5" value="Rp {{ number_format((float) $tagihan->total_bruto, 0, ',', '.') }}" readonly>
                        <small class="text-muted d-block mt-1" style="font-size:.74rem;"><i class="bi bi-lock-fill me-1"></i>Terkunci — mengikuti Termin.</small>
                    </div>
                </div>

                <div class="row g-3 mt-4 pt-4" style="border-top:1px dashed #e2e8f0;">
                    <div class="col-md-4">
                        <div class="nominal-card" style="border-color:rgba(99,102,241,.20);">
                            <div class="nc-label" style="color:#4338ca;"><i class="bi bi-cash me-1"></i>Nilai Bruto</div>
                            <div class="fw-bold fs-5 mb-0" style="color:#0f172a;font-variant-numeric:tabular-nums;">Rp {{ number_format((float) $tagihan->total_bruto, 0, ',', '.') }}</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="nominal-card" style="border-color:rgba(245,158,11,.30);">
                            <div class="nc-label" style="color:#b45309;"><i class="bi bi-dash-circle me-1"></i>Potongan Angsuran UM</div>
                            <div class="fw-bold fs-5 mb-0" style="color:#b45309;font-variant-numeric:tabular-nums;">Rp {{ number_format($potonganAngsuran, 0, ',', '.') }}</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="nominal-card" style="background:linear-gradient(135deg,rgba(16,185,129,.08),rgba(16,185,129,.02));">
                            <div class="nc-label"><i class="bi bi-check-circle-fill me-1"></i>Nilai Netto</div>
                            <div class="fw-bold fs-3 mb-0" style="color:#047857;font-variant-numeric:tabular-nums;letter-spacing:-.01em;">Rp {{ number_format((float) $tagihan->total_netto, 0, ',', '.') }}</div>
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
                    <small>Kosongkan bila tidak mengganti berkas yang sudah ada.</small>
                </div>
                <span class="sec-letter">E</span>
            </div>
            <div class="sec-body">
                <div class="info-banner banner-info mb-4">
                    <i class="bi bi-info-circle-fill"></i>
                    <span><strong>Pemberitahuan:</strong> Mengunggah berkas baru akan menggantikan berkas sebelumnya; versi lama tetap tercatat di arsip.</span>
                </div>
                <div class="row g-4">
                    <div class="col-md-6">
                        <label class="form-label modern" for="file_invoice">
                            <i class="bi bi-file-earmark-pdf-fill text-danger"></i> Surat Permohonan / Invoice
                            <span class="text-muted fw-normal ms-1">(kosongkan bila tidak diganti)</span>
                        </label>
                        <label class="file-drop {{ $arsipInvoice ? 'is-filled' : '' }}" data-accept=".pdf" data-max-mb="5" data-target="file_invoice">
                            <input type="file" id="file_invoice" name="file_invoice" accept=".pdf">
                            <div class="fd-default">
                                <div class="fd-icon"><i class="bi bi-cloud-arrow-up-fill"></i></div>
                                <div class="fd-title">Tarik &amp; lepaskan, atau <strong>klik untuk memilih</strong></div>
                                <div class="fd-sub">Surat Permohonan pembayaran resmi dari vendor.</div>
                                <div class="fd-meta"><i class="bi bi-file-earmark-pdf"></i> PDF &middot; Maks 5MB</div>
                            </div>
                            <div class="fd-preview">
                                <div class="fp-icon"><i class="bi bi-file-earmark-pdf-fill"></i></div>
                                <div class="fp-info">
                                    <div class="fp-name">{{ $arsipInvoice->nama_file_asli ?? '-' }}</div>
                                    <div class="fp-detail">
                                        <span class="fp-size">{{ $arsipInvoice ? 'Tersimpan' : '0 KB' }}</span>
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
                        <label class="file-drop {{ $arsipLampiran ? 'is-filled' : '' }}" data-accept=".pdf,.zip" data-max-mb="5" data-target="file_lampiran_lainnya">
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
                                    <div class="fp-name">{{ $arsipLampiran->nama_file_asli ?? '-' }}</div>
                                    <div class="fp-detail">
                                        <span class="fp-size">{{ $arsipLampiran ? 'Tersimpan' : '0 KB' }}</span>
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
                <span>Perubahan hanya dapat disimpan selama tagihan belum diajukan.</span>
            </div>
            <a href="{{ route('tagihan.kontrak.show', $tagihan->id) }}" class="btn-cancel-submit text-decoration-none">
                <i class="bi bi-x-lg me-1"></i> Batal
            </a>
            <button type="submit" class="btn-submit-primary">
                <i class="bi bi-save2-fill"></i> Simpan Perubahan
            </button>
        </div>
    </form>
@endsection

@push('script')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
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
    });

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
                    const dataTransfer = new DataTransfer();
                    dataTransfer.items.add(dt.files[0]);
                    input.files = dataTransfer.files;
                }
                input.dispatchEvent(new Event('change', { bubbles: true }));
            });
        });

        // Verifikator info preview (NIP & Jabatan)
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

    // Auto-fill NIP, Jabatan & WA saat MENGGANTI Nama Pemeriksa. Nilai tersimpan
    // dipertahankan saat halaman dibuka (tidak ditimpa data master).
    $(document).ready(function () {
        const $namaSelect = $('#namaPemeriksaSelect');
        const $nipInput = $('#nipPemeriksaInput');
        const $jabatanInput = $('#jabatanPemeriksaInput');
        const $waInput = $('#waPemeriksaInput');

        if (!$namaSelect.length) return;

        $namaSelect.on('change', function () {
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
        });
    });
</script>
@endpush
