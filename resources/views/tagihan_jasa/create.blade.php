@extends('layouts.app')
@section('title', 'Buat Tagihan Jasa (PNBP)')

@push('css')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
@endpush

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
    <div>
        <h4 class="mb-0 fw-bold">Buat Tagihan Jasa (PNBP)</h4>
        <p class="mb-0 small">Masukkan informasi mitra dan layanan jasa yang ditagihkan</p>
    </div>
    <a href="{{ route('tagihan-jasa.index') }}" class="btn btn-secondary shadow-sm fw-bold">
        <i class="bi bi-arrow-left me-1"></i> Kembali
    </a>
</div>

@if($errors->any() || session('error'))
    <div class="alert alert-danger border-0 bg-danger text-white alert-dismissible fade show shadow-sm">
        <div class="fw-bold mb-1"><i class="bi bi-exclamation-triangle me-2"></i>Terdapat kesalahan:</div>
        <ul class="mb-0">
            @if(session('error'))
                <li>{{ session('error') }}</li>
            @endif
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<form action="{{ route('tagihan-jasa.store') }}" method="POST" enctype="multipart/form-data">
    @csrf
    
    <div class="row">
        <!-- Section 1: Informasi Mitra & Kontrak -->
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white p-3 border-bottom rounded-top-4">
                    <h6 class="mb-0 fw-bold"><i class="bi bi-building me-2 text-primary"></i>Informasi Mitra & Kontrak</h6>
                </div>
                <div class="card-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Pilih Mitra <span class="text-danger">*</span></label>
                        <select name="mitra_id" class="form-select select2" required>
                            <option value="">-- Pilih Mitra --</option>
                            @foreach($mitras as $mitra)
                                <option value="{{ $mitra->id }}" {{ old('mitra_id') == $mitra->id ? 'selected' : '' }}>
                                    {{ $mitra->nama_pihak }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Tanggal Tagihan <span class="text-danger">*</span></label>
                        <input type="date" name="tanggal_tagihan" class="form-control" value="{{ old('tanggal_tagihan', date('Y-m-d')) }}" required>
                    </div>

                    <hr class="my-4">
                    <h6 class="fw-bold mb-3 small text-muted">Detail Kontrak (Opsional jika Non-Kontrak)</h6>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Nomor Kontrak</label>
                        <input type="text" name="nomor_kontrak" class="form-control" value="{{ old('nomor_kontrak') }}" placeholder="Masukkan Nomor Kontrak">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">File Kontrak (PDF)</label>
                        <input type="file" name="file_kontrak" class="form-control" accept=".pdf">
                        <div class="form-text">Maksimal 5MB. Format PDF.</div>
                    </div>

                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label fw-bold small">Tgl Mulai</label>
                            <input type="date" name="tanggal_mulai_kontrak" class="form-control" value="{{ old('tanggal_mulai_kontrak') }}">
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label fw-bold small">Tgl Selesai</label>
                            <input type="date" name="tanggal_selesai_kontrak" class="form-control" value="{{ old('tanggal_selesai_kontrak') }}">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 2: Input Daftar Tagihan -->
        <div class="col-md-8">
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white p-3 border-bottom rounded-top-4 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold"><i class="bi bi-list-check me-2 text-primary"></i>Daftar Layanan Jasa (PNBP)</h6>
                    <button type="button" class="btn btn-sm btn-outline-primary fw-bold" id="btnAddService">
                        <i class="bi bi-plus-lg me-1"></i> Tambah Layanan
                    </button>
                </div>
                <div class="card-body p-4">
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle" id="tableServices">
                            <thead class="table-light">
                                <tr>
                                    <th width="45%">Pilih Layanan</th>
                                    <th width="15%">Qty/Volume</th>
                                    <th width="20%">Harga Satuan (Rp)</th>
                                    <th width="15%">Subtotal (Rp)</th>
                                    <th width="5%" class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="serviceList">
                                <!-- Dynamic rows go here -->
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <td colspan="3" class="text-end fw-bold">TOTAL TAGIHAN :</td>
                                    <td colspan="2" class="fw-bold text-success fs-5">
                                        Rp <span id="grandTotal">0</span>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                        <button type="button" class="btn btn-light border fw-bold me-md-2" onclick="window.history.back();">Batal</button>
                        <button type="submit" class="btn btn-primary fw-bold px-4" id="btnSubmit">
                            <i class="bi bi-save me-1"></i> Simpan Draf Tagihan
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<!-- Template for new row -->
<template id="rowTemplate">
    <tr class="service-row">
        <td>
            <select name="layanan[__INDEX__][id]" class="form-select service-select" required>
                <option value="">-- Pilih Layanan Jasa --</option>
                @foreach($layanans as $layanan)
                    <option value="{{ $layanan->id }}" data-tarif="{{ $layanan->tarif_dasar }}">{{ $layanan->nama_layanan }}</option>
                @endforeach
            </select>
        </td>
        <td>
            <input type="number" name="layanan[__INDEX__][qty]" class="form-control qty-input text-end" value="1" step="0.01" min="0.01" required>
        </td>
        <td>
            <input type="number" name="layanan[__INDEX__][harga_satuan]" class="form-control price-input text-end" value="0" step="1" min="0" required>
        </td>
        <td>
            <input type="text" class="form-control subtotal-display text-end fw-bold bg-light" readonly value="0">
        </td>
        <td class="text-center">
            <button type="button" class="btn btn-sm btn-outline-danger btn-remove-service">
                <i class="bi bi-trash"></i>
            </button>
        </td>
    </tr>
</template>

@endsection

@push('script')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    $(document).ready(function() {
        $('.select2').select2({
            theme: 'bootstrap-5'
        });

        let rowIndex = 0;
        
        function formatRupiah(number) {
            return new Intl.NumberFormat('id-ID').format(number);
        }

        function calculateTotals() {
            let grandTotal = 0;
            $('.service-row').each(function() {
                let qty = parseFloat($(this).find('.qty-input').val()) || 0;
                let price = parseFloat($(this).find('.price-input').val()) || 0;
                let subtotal = qty * price;
                
                $(this).find('.subtotal-display').val(formatRupiah(subtotal));
                grandTotal += subtotal;
            });
            $('#grandTotal').text(formatRupiah(grandTotal));
        }

        function addServiceRow() {
            let template = $('#rowTemplate').html();
            template = template.replace(/__INDEX__/g, rowIndex);
            $('#serviceList').append(template);
            
            // Initialize select2 on the new row
            $('#serviceList .service-select').last().select2({
                theme: 'bootstrap-5'
            });
            
            rowIndex++;
            calculateTotals();
        }

        // Add first row by default
        addServiceRow();

        // Event listeners
        $('#btnAddService').click(function() {
            addServiceRow();
        });

        $(document).on('click', '.btn-remove-service', function() {
            if ($('.service-row').length > 1) {
                $(this).closest('tr').remove();
                calculateTotals();
            } else {
                alert('Minimal harus ada 1 layanan.');
            }
        });

        $(document).on('change', '.service-select', function() {
            let tarif = $(this).find(':selected').data('tarif') || 0;
            $(this).closest('tr').find('.price-input').val(tarif);
            calculateTotals();
        });

        $(document).on('input', '.qty-input, .price-input', function() {
            calculateTotals();
        });
        
        $('form').submit(function() {
            if ($('.service-row').length === 0) {
                alert('Silakan tambahkan minimal 1 layanan jasa.');
                return false;
            }
            $('#btnSubmit').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Menyimpan...');
            return true;
        });
    });
</script>
@endpush
