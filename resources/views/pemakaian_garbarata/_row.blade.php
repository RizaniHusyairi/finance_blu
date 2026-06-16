@php
    $value = fn ($key, $default = '') => $row[$key] ?? $default;
    $arrVal = $value('flight_arr');
    $depVal = $value('flight_dep');
    $avioVal = (int) ($row['nomor_avio'] ?? 1);
@endphp
<tr>
    <td class="text-center"><span class="amc-no">{{ is_numeric($index) ? ((int) $index + 1) : '' }}</span></td>
    <td><input type="date" name="rows[{{ $index }}][tanggal]" class="form-control amc-tanggal" value="{{ $value('tanggal', now()->toDateString()) }}" required></td>
    <td><input type="text" name="rows[{{ $index }}][registrasi_pesawat]" class="form-control amc-reg" value="{{ $value('registrasi_pesawat') }}" placeholder="PK-SAT" maxlength="50"></td>
    <td style="min-width:140px;">
        <select name="rows[{{ $index }}][flight_arr]" class="form-select amc-small amc-flight-arr">
            <option value=""></option>
            @if($arrVal)<option value="{{ $arrVal }}" selected>{{ $arrVal }}</option>@endif
        </select>
    </td>
    <td style="min-width:140px;">
        <select name="rows[{{ $index }}][flight_dep]" class="form-select amc-small amc-flight-dep">
            <option value=""></option>
            @if($depVal)<option value="{{ $depVal }}" selected>{{ $depVal }}</option>@endif
        </select>
    </td>
    <td><input type="text" name="rows[{{ $index }}][route]" class="form-control amc-route" value="{{ $value('route') }}" placeholder="YIA-AAP-SUB" maxlength="150"></td>
    <td><input type="time" name="rows[{{ $index }}][docking]" class="form-control amc-docking" value="{{ $value('docking') }}" required></td>
    <td><input type="time" name="rows[{{ $index }}][undocking]" class="form-control amc-undocking" value="{{ $value('undocking') }}" required></td>
    <td><input type="text" name="rows[{{ $index }}][type_pesawat]" class="form-control amc-small amc-type" value="{{ $value('type_pesawat', 'A320') }}" placeholder="A320" maxlength="50"></td>
    <td>
        <input type="number" name="rows[{{ $index }}][bobot_ton]" class="form-control amc-small text-end" value="{{ $value('bobot_ton', 77) }}" step="0.01" min="0">
        <input type="hidden" name="rows[{{ $index }}][tarif_garbarata]" class="amc-tarif" value="{{ $value('tarif_garbarata', 280000) }}">
    </td>
    <td><span class="amc-total-pill amc-waktu">00:00</span></td>
    <td>
        <select name="rows[{{ $index }}][nomor_avio]" class="form-select amc-small amc-avio">
            <option value="1" @selected($avioVal === 1)>1</option>
            <option value="2" @selected($avioVal === 2)>2</option>
        </select>
    </td>
    <td class="text-center"><span class="amc-total-pill amc-rentang">0</span></td>
    <td style="min-width:170px;">
        <input type="file" name="rows[{{ $index }}][file_pendukung]" class="form-control form-control-sm" accept=".pdf,.jpg,.jpeg,.png" style="font-size:.72rem;">
        @if(!empty($row['existing_file_url']))
            <a href="{{ $row['existing_file_url'] }}" target="_blank" class="small d-block mt-1 text-decoration-none"><i class="bi bi-paperclip"></i> File terlampir</a>
        @endif
    </td>
    @if($editableMany)
        <td class="text-center">
            <button type="button" class="btn btn-sm btn-outline-danger btn-remove-row" title="Hapus baris"><i class="bi bi-x-lg"></i></button>
        </td>
    @endif
</tr>
