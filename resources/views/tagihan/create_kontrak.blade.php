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
                                <label class="form-label fw-bold">Pilih Kontrak (Nomor SPK) <span class="text-danger">*</span></label>
                                <select class="form-select select2" name="kontrak_pengadaan_id" id="kontrak_pengadaan_id" required onchange="getDetailKontrak(this.value)">
                                    <option value="">-- Cari atau ketik Nomor SPK --</option>
                                    @foreach($kontraks ?? [] as $k)
                                        <option value="{{ $k->id }}" data-vendor="{{ $k->vendor->nama_perusahaan ?? 'N/A' }}" data-nama="{{ $k->nama_pekerjaan }}" data-nilai="{{ $k->nilai_total_kontrak }}">
                                            {{ $k->nomor_spk }} - {{ Str::limit($k->nama_pekerjaan, 40) }}
                                        </option>
                                    @endforeach
                                </select>
                                
                                {{-- Panel Informasi Read-Only (Akan muncul saat SPK dipilih) --}}
                                <div id="panel_info_kontrak" class="mt-3 p-3 bg-light rounded border border-info" style="display: none;">
                                    <small class="text-muted d-block mb-1">Nama Vendor:</small>
                                    <div class="fw-bold mb-2" id="info_vendor">-</div>
                                    <small class="text-muted d-block mb-1">Nama Pekerjaan:</small>
                                    <div class="fw-medium mb-2" id="info_pekerjaan">-</div>
                                    <small class="text-muted d-block mb-1">Nilai Total Kontrak:</small>
                                    <div class="fw-bold text-success fs-5" id="info_nilai">-</div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold">Pilih Termin Tagihan <span class="text-danger">*</span></label>
                                <select class="form-select" name="kontrak_termin_id" id="kontrak_termin_id" required disabled onchange="setBrutoFromTermin()">
                                    <option value="">-- Pilih Kontrak Terlebih Dahulu --</option>
                                    {{-- Opsi Termin akan di-load via AJAX atau JS --}}
                                </select>
                                <div class="form-text mt-2">Hanya termin dengan status <strong>LOCKED</strong> (belum ditagihkan) yang akan tampil.</div>
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
                                <input type="text" class="form-control mb-2" name="nomor_bapp" placeholder="No. BAPP">
                                <input type="date" class="form-control" name="tanggal_bapp">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Nomor BAST <small class="text-danger">*</small> <small class="text-muted">(Serah Terima)</small></label>
                                <input type="text" class="form-control mb-2" name="nomor_bast" placeholder="No. BAST" required>
                                <input type="date" class="form-control" name="tanggal_bast" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Nomor BAP <small class="text-danger">*</small> <small class="text-muted">(Pembayaran)</small></label>
                                <input type="text" class="form-control mb-2" name="nomor_bap" placeholder="No. BAP" required>
                                <input type="date" class="form-control" name="tanggal_bap" required>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Bagian 3: Dokumen Vendor & Kalkulasi Pajak --}}
            <div class="col-12 mb-4">
                <div class="card shadow-sm border-0 rounded-4 h-100 border-warning">
                    <div class="card-header bg-warning text-dark py-3 rounded-top-4">
                        <h6 class="mb-0 fw-bold"><i class="bi bi-calculator me-2"></i>3. Dokumen Vendor & Kalkulasi Pajak</h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-4 mb-4 pb-4 border-bottom">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Nomor Invoice/Permohonan <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="nomor_invoice" placeholder="Contoh: INV/2026/001" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-success">Nilai Bruto Tagihan (DPP + PPN) <span class="text-danger">*</span></label>
                                <input type="text" class="form-control fw-bold bg-light fs-5" id="total_bruto_display" value="Rp 0" readonly>
                                <input type="hidden" name="total_bruto" id="total_bruto" value="0">
                                <small class="text-muted">Akan terisi otomatis berdasarkan Termin yang dipilih.</small>
                            </div>
                        </div>

                        {{-- Dynamic Form Pajak --}}
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="fw-bold mb-0">Rincian Potongan Pajak <small class="text-muted fw-normal">(Opsional)</small></h6>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="btnTambahPajak">
                                <i class="bi bi-plus"></i> Tambah Potongan
                            </button>
                        </div>

                        <div id="containerPajak">
                            {{-- Row pajak diisi js secara dinamis --}}
                            <div class="text-center text-muted p-3 bg-light rounded" id="pajakKosong">
                                Tidak ada potongan pajak. Nilai Bruto = Nilai Netto.
                            </div>
                        </div>

                        <div class="mt-4 pt-3 border-top text-end">
                            <h5 class="text-muted mb-1">Nilai Netto (Akan Diterima Vendor):</h5>
                            <h2 class="fw-bold text-success mb-0" id="total_netto_display">Rp 0</h2>
                            <input type="hidden" name="total_netto" id="total_netto" value="0">
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
                        <div class="row g-4">
                            <div class="col-md-4">
                                <label class="form-label fw-bold">1. Surat Permohonan / Invoice <span class="text-danger">*</span></label>
                                <input type="file" class="form-control" name="file_invoice" accept=".pdf" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">2. BAST / BAP / BAPP Ber-TTD <span class="text-danger">*</span></label>
                                <input type="file" class="form-control" name="file_bast" accept=".pdf" required>
                                <small class="text-muted d-block mt-1">Jika terpisah, gabungkan dalam 1 file PDF.</small>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">3. Kwitansi Bermaterai <span class="text-danger">*</span></label>
                                <input type="file" class="form-control" name="file_kwitansi" accept=".pdf" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">4. Faktur Pajak & E-Billing <small class="text-muted">(Jika ada pemotongan)</small></label>
                                <input type="file" class="form-control" name="file_pajak" accept=".pdf">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">5. Lampiran Laporan (Foto/Dokumentasi) <small class="text-muted">(Opsional)</small></label>
                                <input type="file" class="form-control" name="file_lampiran_lainnya" accept=".pdf,.zip">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Action Buttons --}}
            <div class="col-12 mb-5">
                <div class="d-flex justify-content-end gap-3 p-3 bg-light rounded-4 shadow-sm">
                    <button type="reset" class="btn btn-outline-secondary px-4">Reset Ulang</button>
                    <button type="submit" class="btn btn-primary px-5 fw-bold"><i class="bi bi-send-check me-2"></i>Ajukan Tagihan ke PPK</button>
                </div>
            </div>

        </div>
    </form>
@endsection

@push('script')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    // Data dummy simulasi (jengkelan Backend belum siap, kita simulasikan untuk UI demo)
    // Di produksi asli, ini diganti endpoint AJAX route('termins.get_by_kontrak')
    const terminDataSimulator = {
        // Akan diisi saat AJAX.
    };
    
    // Master Tarif Pajak (disiapkan saat page load dari controller)
    const pajakOptions = [
        @foreach($pajaks ?? [] as $pj)
            { id: "{{$pj->id}}", text: "{{$pj->nama_pajak}} ({{$pj->persentase}}%)", tarif: {{$pj->persentase}} },
        @endforeach
    ];

    let pajakCounter = 0;

    $(document).ready(function() {
        $('.select2').select2({ theme: 'classic' });

        $('#btnTambahPajak').click(function() {
            tambahRowPajak();
        });
    });

    function getDetailKontrak(idKontrak) {
        if(!idKontrak) {
            $('#panel_info_kontrak').hide();
            $('#kontrak_termin_id').html('<option value="">-- Pilih Kontrak Terlebih Dahulu --</option>').prop('disabled', true);
            return;
        }

        // Tampilkan info ReadOnly
        let optionSel = $('#kontrak_pengadaan_id').find(':selected');
        $('#info_vendor').text(optionSel.data('vendor'));
        $('#info_pekerjaan').text(optionSel.data('nama'));
        $('#info_nilai').text(formatRupiah(optionSel.data('nilai').toString()));
        $('#panel_info_kontrak').fadeIn();

        // Panggil Termin (Simulasi AJAX untuk demo cepat)
        $('#kontrak_termin_id').html('<option value="">Sedang memuat termin...</option>').prop('disabled', false);
        
        // Asumsikan kita punya fetch dari API, untuk dummy saat ini:
        $.ajax({
            url: "{{ url('/api/kontrak') }}/" + idKontrak + "/termins",
            type: "GET",
            success: function(res) {
                let html = '<option value="">-- Pilih Termin / Tagihan --</option>';
                res.data.forEach(t => {
                    html += `<option value="${t.id}" data-bruto="${t.nilai_bruto_termin}">${t.keterangan_termin} - ${t.persentase}% (Rp ${formatRupiahCustom(t.nilai_bruto_termin)})</option>`;
                });
                if(res.data.length === 0) {
                    html = '<option value="">Tidak ada Termin berstatus LOCKED</option>';
                }
                $('#kontrak_termin_id').html(html);
            },
            error: function() {
                // Dummy fallback saat API belum connect
                let dummyHtml = `<option value="">-- Pilih Termin / Tagihan --</option>
                                <option value="99" data-bruto="50000000">Termin 1 - 50% (Rp 50.000.000)</option>`;
                $('#kontrak_termin_id').html(dummyHtml);
            }
        });
    }

    function setBrutoFromTermin() {
        let opt = $('#kontrak_termin_id').find(':selected');
        let brutoVal = opt.data('bruto') || 0;
        
        $('#total_bruto').val(brutoVal);
        $('#total_bruto_display').val('Rp ' + formatRupiahCustom(brutoVal));
        
        hitungTotalNetto();
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

    function tambahRowPajak() {
        $('#pajakKosong').hide();
        pajakCounter++;
        let pId = pajakCounter;

        let optionsHtml = '<option value="">-- Pilih Jenis Pajak --</option>';
        pajakOptions.forEach(p => {
            optionsHtml += `<option value="${p.id}" data-tarif="${p.tarif}">${p.text}</option>`;
        });
        
        let rowHtml = `
            <div class="row g-3 mb-3 border p-3 rounded bg-white shadow-sm align-items-end pajak-row" id="pajak_row_${pId}">
                <div class="col-md-3">
                    <label class="form-label small fw-bold">Jenis Pajak</label>
                    <select class="form-select pajak-select" name="pajak[${pId}][id]" id="pajak_sel_${pId}" required onchange="hitungPajakRow(${pId})">
                        ${optionsHtml}
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-bold">Nilai DPP (Dasar Pengenaan) Rp</label>
                    <input type="text" class="form-control dpp-input" id="dpp_display_${pId}" value="0" onkeyup="formatInputDpp(this, ${pId})">
                    <input type="hidden" name="pajak[${pId}][dpp]" id="dpp_val_${pId}" value="0">
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-bold text-danger">Potongan Terhitung Rp</label>
                    <input type="text" class="form-control bg-light fw-bold text-danger potongan-hasil" id="potongan_display_${pId}" value="0" readonly>
                    <input type="hidden" name="pajak[${pId}][nominal]" id="potongan_val_${pId}" value="0" class="potongan-val-class">
                </div>
                <div class="col-md-1 text-end">
                    <button type="button" class="btn btn-outline-danger w-100" onclick="hapusPajakRow(${pId})"><i class="bi bi-trash"></i></button>
                </div>
            </div>
        `;

        $('#containerPajak').append(rowHtml);
    }

    function hapusPajakRow(id) {
        $(`#pajak_row_${id}`).remove();
        if($('.pajak-row').length === 0) {
            $('#pajakKosong').show();
        }
        hitungTotalNetto();
    }

    function formatInputDpp(inputEl, id) {
        let clean = inputEl.value.replace(/[^,\d]/g, '');
        $(`#dpp_val_${id}`).val(clean);
        inputEl.value = formatRupiahCustom(clean);
        hitungPajakRow(id);
    }

    function hitungPajakRow(id) {
        let tarif = $(`#pajak_sel_${id}`).find(':selected').data('tarif') || 0;
        let dpp = parseFloat($(`#dpp_val_${id}`).val()) || 0;
        
        let hasil = (dpp * (parseFloat(tarif) / 100));
        
        $(`#potongan_val_${id}`).val(hasil);
        $(`#potongan_display_${id}`).val('- Rp ' + formatRupiahCustom(Math.round(hasil)));
        
        hitungTotalNetto();
    }

    function hitungTotalNetto() {
        let bruto = parseFloat($('#total_bruto').val()) || 0;
        let totalPotongan = 0;
        
        $('.potongan-val-class').each(function() {
            totalPotongan += parseFloat($(this).val()) || 0;
        });

        let netto = bruto - totalPotongan;
        
        $('#total_netto').val(netto);
        $('#total_netto_display').text('Rp ' + formatRupiahCustom(Math.round(netto)));
    }
</script>
@endpush
