@csrf
<div class="row g-4">
    <!-- Provinsi Input -->
    <div class="col-12 col-lg-6">
        <div class="form-group-premium animate-fade-in-up delay-1">
            <label for="provinsi" class="label-premium">
                <i class="bi bi-geo-alt-fill text-primary"></i> Provinsi <span class="text-danger">*</span>
            </label>
            <input type="text" class="form-control input-premium @error('provinsi') is-invalid @enderror" 
                   id="provinsi" name="provinsi" placeholder="Contoh: DKI JAKARTA" 
                   value="{{ old('provinsi', $data->provinsi ?? '') }}" required>
            <span class="form-desc-text">Ketik nama provinsi dengan huruf kapital lengkap</span>
            @error('provinsi') 
                <div class="invalid-feedback mt-2 fw-semibold">{{ $message }}</div> 
            @enderror
        </div>
    </div>

    <!-- Luar Kota Input -->
    <div class="col-12 col-lg-6">
        <div class="form-group-premium animate-fade-in-up delay-1">
            <label for="luar_kota_input" class="label-premium">
                <i class="bi bi-airplane-engines-fill text-teal"></i> Uang Harian Luar Kota <span class="text-danger">*</span>
            </label>
            <div class="input-group-premium input-group-teal">
                <span class="input-group-text-premium">Rp</span>
                <input type="text" class="form-control input-premium currency-input @error('luar_kota') is-invalid @enderror" 
                       id="luar_kota_input" placeholder="0" 
                       value="{{ old('luar_kota', isset($data) ? $data->luar_kota : '') }}" required>
            </div>
            <input type="hidden" name="luar_kota" id="luar_kota" value="{{ old('luar_kota', isset($data) ? $data->luar_kota : '') }}">
            <span class="form-desc-text">Besaran uang harian perjalanan dinas ke luar wilayah provinsi asal</span>
            @error('luar_kota') 
                <div class="invalid-feedback mt-2 fw-semibold">{{ $message }}</div> 
            @enderror
        </div>
    </div>

    <!-- Dalam Kota Lebih 8 Jam Input -->
    <div class="col-12 col-lg-6">
        <div class="form-group-premium animate-fade-in-up delay-2">
            <label for="dalam_kota_lebih_8_jam_input" class="label-premium">
                <i class="bi bi-car-front-fill text-primary"></i> Dalam Kota > 8 Jam <span class="text-danger">*</span>
            </label>
            <div class="input-group-premium input-group-blue">
                <span class="input-group-text-premium">Rp</span>
                <input type="text" class="form-control input-premium currency-input @error('dalam_kota_lebih_8_jam') is-invalid @enderror" 
                       id="dalam_kota_lebih_8_jam_input" placeholder="0" 
                       value="{{ old('dalam_kota_lebih_8_jam', isset($data) ? $data->dalam_kota_lebih_8_jam : '') }}" required>
            </div>
            <input type="hidden" name="dalam_kota_lebih_8_jam" id="dalam_kota_lebih_8_jam" value="{{ old('dalam_kota_lebih_8_jam', isset($data) ? $data->dalam_kota_lebih_8_jam : '') }}">
            <span class="form-desc-text">Besaran uang harian dinas dalam kota dengan durasi lebih dari 8 jam</span>
            @error('dalam_kota_lebih_8_jam') 
                <div class="invalid-feedback mt-2 fw-semibold">{{ $message }}</div> 
            @enderror
        </div>
    </div>

    <!-- Diklat Input -->
    <div class="col-12 col-lg-6">
        <div class="form-group-premium animate-fade-in-up delay-2">
            <label for="diklat_input" class="label-premium">
                <i class="bi bi-mortarboard-fill text-warning"></i> Kegiatan Diklat
            </label>
            <div class="input-group-premium input-group-amber">
                <span class="input-group-text-premium">Rp</span>
                <input type="text" class="form-control input-premium currency-input @error('diklat') is-invalid @enderror" 
                       id="diklat_input" placeholder="0" 
                       value="{{ old('diklat', isset($data) ? $data->diklat : '') }}">
            </div>
            <input type="hidden" name="diklat" id="diklat" value="{{ old('diklat', isset($data) ? $data->diklat : '') }}">
            <span class="form-desc-text">Besaran uang harian khusus keikutsertaan kegiatan pendidikan/pelatihan (opsional)</span>
            @error('diklat') 
                <div class="invalid-feedback mt-2 fw-semibold">{{ $message }}</div> 
            @enderror
        </div>
    </div>
</div>

<!-- Form Actions -->
<div class="d-flex align-items-center gap-3 mt-5 pt-4 border-top border-light animate-fade-in-up delay-2">
    <button type="submit" class="btn-save-premium">
        <i class="bi bi-check-circle-fill"></i> Simpan Data
    </button>
    <a href="{{ route('master-uang-harian-perjaldin.index') }}" class="btn-cancel-premium">
        <i class="bi bi-x-circle-fill"></i> Batal
    </a>
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
