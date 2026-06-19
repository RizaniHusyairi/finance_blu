@php $st = $filters['status'] ?? ''; @endphp
<div class="klas-stats">
    <button type="button" class="klas-stat klas-stat--total {{ $st === '' ? 'is-active' : '' }}" data-status="" aria-pressed="{{ $st === '' ? 'true' : 'false' }}" title="Tampilkan semua baris" style="--i:0">
        <span class="klas-stat__icon"><i class="bi bi-collection-fill"></i></span>
        <span class="klas-stat__body">
            <span class="klas-stat__num" data-value="{{ $counts['total'] }}">{{ number_format($counts['total'], 0, ',', '.') }}</span>
            <span class="klas-stat__label">Semua Baris</span>
        </span>
    </button>
    <button type="button" class="klas-stat klas-stat--pending {{ $st === 'belum' ? 'is-active' : '' }}" data-status="belum" aria-pressed="{{ $st === 'belum' ? 'true' : 'false' }}" title="Saring: belum diposting" style="--i:1">
        <span class="klas-stat__icon"><i class="bi bi-clock-history"></i></span>
        <span class="klas-stat__body">
            <span class="klas-stat__num" data-value="{{ $counts['belum'] }}">{{ number_format($counts['belum'], 0, ',', '.') }}</span>
            <span class="klas-stat__label">Belum Diposting</span>
        </span>
    </button>
    <button type="button" class="klas-stat klas-stat--posted {{ $st === 'terposting' ? 'is-active' : '' }}" data-status="terposting" aria-pressed="{{ $st === 'terposting' ? 'true' : 'false' }}" title="Saring: sudah terposting" style="--i:2">
        <span class="klas-stat__icon"><i class="bi bi-check-circle-fill"></i></span>
        <span class="klas-stat__body">
            <span class="klas-stat__num" data-value="{{ $counts['terposting'] }}">{{ number_format($counts['terposting'], 0, ',', '.') }}</span>
            <span class="klas-stat__label">Terposting</span>
        </span>
    </button>
    <button type="button" class="klas-stat klas-stat--ok {{ $st === 'cocok' ? 'is-active' : '' }}" data-status="cocok" aria-pressed="{{ $st === 'cocok' ? 'true' : 'false' }}" title="Saring: cocok (terverifikasi)" style="--i:3">
        <span class="klas-stat__icon"><i class="bi bi-patch-check-fill"></i></span>
        <span class="klas-stat__body">
            <span class="klas-stat__num" data-value="{{ $counts['cocok'] }}">{{ number_format($counts['cocok'], 0, ',', '.') }}</span>
            <span class="klas-stat__label">Cocok (Terverifikasi)</span>
        </span>
    </button>
</div>

@include('pembukuan.klasifikasi._table')
