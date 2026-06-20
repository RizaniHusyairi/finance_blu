{{-- Kartu ringkasan bergaya BKU (bku-stat). Tiap $card: label, value,
     opsional class (warna Bootstrap → varian), icon, hint. --}}
@php
    // Pemetaan kelas warna Bootstrap lama → varian + ikon kartu BKU.
    $bkuVariantMap = [
        'text-success'   => ['green',  'bi-arrow-down-left-circle'],
        'text-danger'    => ['red',    'bi-arrow-up-right-circle'],
        'text-primary'   => ['indigo', 'bi-cash-stack'],
        'text-warning'   => ['amber',  'bi-hourglass-split'],
        'text-info'      => ['cyan',   'bi-graph-up'],
        'text-secondary' => ['slate',  'bi-wallet2'],
        'text-dark'      => ['slate',  'bi-collection'],
    ];
@endphp
<div class="bku-stats">
    @foreach($cards as $i => $card)
        @php [$variant, $icon] = $bkuVariantMap[$card['class'] ?? 'text-dark'] ?? ['slate', 'bi-dot']; @endphp
        <div class="bku-stat bku-stat--{{ $variant }}" style="--i:{{ $i }}">
            <div class="bku-stat__icon"><i class="bi {{ $card['icon'] ?? $icon }}"></i></div>
            <div class="bku-stat__body">
                <span class="bku-stat__label">{{ $card['label'] }}</span>
                <span class="bku-stat__value">{{ $card['value'] }}</span>
                @if(!empty($card['hint']))
                    <span class="bku-stat__hint">{{ $card['hint'] }}</span>
                @endif
            </div>
        </div>
    @endforeach
</div>
