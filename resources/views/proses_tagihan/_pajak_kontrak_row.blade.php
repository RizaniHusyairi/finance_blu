{{--
    Satu baris pilihan pajak (dropdown + DPP + nominal) pada kartu Pajak Kontrak.
    Dipakai dua kali: prefill baris tersimpan, dan <template> baris kosong
    untuk tombol "Tambah Pajak". Tanpa $selectedTarifId, input dinonaktifkan
    dan tidak diberi name — JS yang mengaktifkan begitu tipe pajak dipilih.
--}}
@php
    $selectedTarifId = $selectedTarifId ?? null;
    $dppVal = $dppVal ?? null;
    $nominalVal = $nominalVal ?? null;
@endphp
<div class="border rounded-4 p-3 pjk-row border-light-subtle" style="background: #fcfcfe;">
    <div class="row g-2">
        <div class="col-lg-5">
            <label class="form-label fs-8 fw-bold text-secondary text-uppercase mb-1">Tipe Pajak</label>
            <select name="pajak[]" class="form-select form-select-sm pjk-select" required>
                <option value="">— Pilih tipe pajak —</option>
                @foreach($pajakOptions as $tarif)
                    <option value="{{ $tarif->id }}"
                            data-persen="{{ $tarif->persentase }}"
                            data-kode="{{ $tarif->kode_pajak }}"
                            data-kap="{{ $tarif->kode_akun_pajak ? 'KAP ' . $tarif->kode_akun_pajak . ($tarif->kode_jenis_setoran ? ' / KJS ' . $tarif->kode_jenis_setoran : '') : '' }}"
                            data-rumus="{{ $tarif->rumus }}"
                            @selected((int) $selectedTarifId === (int) $tarif->id)>
                        {{ $tarif->jenis_pajak }} ({{ $tarif->kode_pajak }} — {{ rtrim(rtrim(number_format($tarif->persentase, 2, ',', '.'), '0'), ',') }}%)
                    </option>
                @endforeach
            </select>
            <div class="pjk-info text-muted fs-8 mt-1 d-none"></div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <label class="form-label fs-8 fw-bold text-secondary text-uppercase mb-1">DPP (Dasar Pengenaan)</label>
            <div class="input-group input-group-sm">
                <span class="input-group-text">Rp</span>
                <input type="number" step="any" min="0" class="form-control pjk-dpp"
                       @if($selectedTarifId) name="dpp[{{ $selectedTarifId }}]" value="{{ $dppVal }}" data-manual="1" @else disabled @endif>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <label class="form-label fs-8 fw-bold text-secondary text-uppercase mb-1">Nominal Potongan</label>
            <div class="input-group input-group-sm">
                <span class="input-group-text">Rp</span>
                <input type="number" step="any" min="0" class="form-control pjk-nominal fw-bold"
                       @if($selectedTarifId) name="nominal[{{ $selectedTarifId }}]" value="{{ $nominalVal }}" data-manual="1" @else disabled @endif>
            </div>
        </div>
        <div class="col-lg-1 d-flex align-items-end justify-content-lg-end">
            <button type="button" class="btn btn-sm btn-light border text-danger pjk-remove" title="Hapus baris pajak">
                <i class="bi bi-trash"></i>
            </button>
        </div>
    </div>
</div>
