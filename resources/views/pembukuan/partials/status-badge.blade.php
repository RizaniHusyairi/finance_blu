@php
    $statusValue = strtoupper((string) ($value ?? '-'));

    $statusClass = match ($statusValue) {
        'MATCHED', 'DISAHKAN', 'SUDAH_SETOR', 'SUDAH_MASUK_BKU', 'DEBIT_MASUK', 'MASUK', 'ACTIVE' => 'bg-success-subtle text-success border border-success-subtle',
        'PARTIAL', 'VERIFIKASI_KPPN', 'SUDAH_BILLING', 'MANUAL_OVERRIDE' => 'bg-warning-subtle text-warning border border-warning-subtle',
        'SELISIH', 'BELUM_SETOR', 'KREDIT_KELUAR', 'KELUAR', 'REVISI' => 'bg-danger-subtle text-danger border border-danger-subtle',
        'BELUM', 'DRAFT', 'BELUM_MASUK_BKU', 'UNPAID' => 'bg-secondary-subtle text-secondary border border-secondary-subtle',
        default => 'bg-info-subtle text-info border border-info-subtle',
    };

    $statusLabel = $label ?? Str::headline(str_replace('_', ' ', strtolower((string) ($value ?? '-'))));
@endphp
<span class="badge {{ $statusClass }} rounded-pill px-3 py-2">{{ $statusLabel }}</span>
