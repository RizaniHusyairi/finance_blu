@csrf
<div class="row mb-3">
    <label for="provinsi" class="col-sm-3 col-form-label">Provinsi <span class="text-danger">*</span></label>
    <div class="col-sm-9">
        <input type="text" class="form-control @error('provinsi') is-invalid @enderror" id="provinsi" name="provinsi" value="{{ old('provinsi', $data->provinsi ?? '') }}" required>
        @error('provinsi') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>
<div class="row mb-3">
    <label for="luar_kota_input" class="col-sm-3 col-form-label">Luar Kota <span class="text-danger">*</span></label>
    <div class="col-sm-9">
        <div class="input-group">
            <span class="input-group-text">Rp</span>
            <input type="text" class="form-control currency-input @error('luar_kota') is-invalid @enderror" id="luar_kota_input" value="{{ old('luar_kota', isset($data) ? $data->luar_kota : '') }}" required>
            <input type="hidden" name="luar_kota" id="luar_kota" value="{{ old('luar_kota', isset($data) ? $data->luar_kota : '') }}">
            @error('luar_kota') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
    </div>
</div>
<div class="row mb-3">
    <label for="dalam_kota_lebih_8_jam_input" class="col-sm-3 col-form-label">Dalam Kota > 8 Jam <span class="text-danger">*</span></label>
    <div class="col-sm-9">
        <div class="input-group">
            <span class="input-group-text">Rp</span>
            <input type="text" class="form-control currency-input @error('dalam_kota_lebih_8_jam') is-invalid @enderror" id="dalam_kota_lebih_8_jam_input" value="{{ old('dalam_kota_lebih_8_jam', isset($data) ? $data->dalam_kota_lebih_8_jam : '') }}" required>
            <input type="hidden" name="dalam_kota_lebih_8_jam" id="dalam_kota_lebih_8_jam" value="{{ old('dalam_kota_lebih_8_jam', isset($data) ? $data->dalam_kota_lebih_8_jam : '') }}">
            @error('dalam_kota_lebih_8_jam') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
    </div>
</div>
<div class="row mb-3">
    <label for="diklat_input" class="col-sm-3 col-form-label">Diklat</label>
    <div class="col-sm-9">
        <div class="input-group">
            <span class="input-group-text">Rp</span>
            <input type="text" class="form-control currency-input @error('diklat') is-invalid @enderror" id="diklat_input" value="{{ old('diklat', isset($data) ? $data->diklat : '') }}">
            <input type="hidden" name="diklat" id="diklat" value="{{ old('diklat', isset($data) ? $data->diklat : '') }}">
            @error('diklat') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
    </div>
</div>
<div class="row">
    <label class="col-sm-3 col-form-label"></label>
    <div class="col-sm-9">
        <button type="submit" class="btn btn-primary px-4">Simpan</button>
        <a href="{{ route('master-uang-harian-perjaldin.index') }}" class="btn btn-secondary px-4">Batal</a>
    </div>
</div>

@push('script')
<script>
    function formatRupiah(number) {
        if (!number) return '';
        number = number.toString().replace(/[^,\d]/g, '');
        let split = number.split(',');
        let sisa = split[0].length % 3;
        let rupiah = split[0].substr(0, sisa);
        let ribuan = split[0].substr(sisa).match(/\d{3}/gi);

        if (ribuan) {
            let separator = sisa ? '.' : '';
            rupiah += separator + ribuan.join('.');
        }

        rupiah = split[1] !== undefined ? rupiah + ',' + split[1] : rupiah;
        return rupiah;
    }

    function removeRupiah(formattedRupiah) {
        if (!formattedRupiah) return '';
        return formattedRupiah.toString().replace(/\./g, '');
    }

    // Initialize formatting
    $('.currency-input').each(function() {
        let val = $(this).val();
        if(val) {
            $(this).val(formatRupiah(val));
        }
    });

    // On input format
    $('.currency-input').on('keyup', function() {
        $(this).val(formatRupiah($(this).val()));
    });

    // On form submit, update hidden fields
    $('form').on('submit', function() {
        $('#luar_kota').val(removeRupiah($('#luar_kota_input').val()));
        $('#dalam_kota_lebih_8_jam').val(removeRupiah($('#dalam_kota_lebih_8_jam_input').val()));
        $('#diklat').val(removeRupiah($('#diklat_input').val()));
    });
</script>
@endpush
