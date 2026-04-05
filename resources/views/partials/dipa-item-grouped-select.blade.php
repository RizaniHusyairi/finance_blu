@php
    $fieldName = $fieldName ?? 'dipa_revision_item_id';
    $fieldId = $fieldId ?? 'dipa_revision_item_id';
    $fieldLabel = $fieldLabel ?? 'Pilih Item Anggaran (COA)';
    $fieldRequired = $fieldRequired ?? true;
    $fieldClass = $fieldClass ?? 'form-select budget-item-select';
    $placeholder = $placeholder ?? '-- Pilih Item Anggaran dari DIPA Aktif --';
    $helpText = $helpText ?? 'Pilih item anggaran aktif. Sistem akan otomatis memetakan DIPA induk, revisi aktif, dan COA.';
    $selectedValue = old($fieldName, $selectedValue ?? null);
@endphp

<label class="form-label fw-bold">{{ $fieldLabel }} @if($fieldRequired)<span class="text-danger">*</span>@endif</label>
<select
    name="{{ $fieldName }}"
    id="{{ $fieldId }}"
    class="{{ $fieldClass }}"
    @if($fieldRequired) required @endif
>
    <option value="">{{ $placeholder }}</option>
    @foreach ($budgetGroups as $group)
        <optgroup label="{{ $group['label'] }}">
            @foreach ($group['items'] as $item)
                <option
                    
                    value="{{ $item['id'] }}"
                    data-master-dipa-id="{{ $item['master_dipa_id'] }}"
                    data-dipa-label="{{ $item['dipa_label'] }}"
                    data-revisi-label="{{ $item['revisi_label'] }}"
                    data-coa-label="{{ $item['coa_label'] }}"
                    data-nama-akun="{{ $item['nama_akun'] }}"
                    data-jenis-akun="{{ $item['jenis_akun'] }}"
                    data-nilai-pagu="{{ $item['nilai_pagu'] }}"
                    {{ (string) $selectedValue === (string) $item['id'] ? 'selected' : '' }}
                >
                    &nbsp;&nbsp;&nbsp;{{ $item['option_label'] }}
                </option>
            @endforeach
        </optgroup>
    @endforeach
</select>
@if($helpText)
    <small class="text-muted d-block mt-2">{{ $helpText }}</small>
@endif
