<div class="row g-3 mb-4">
    @foreach($cards as $card)
        <div class="col-md-6 col-xl-3">
            <div class="book-summary">
                <div class="label">{{ $card['label'] }}</div>
                <div class="value {{ $card['class'] ?? '' }}">{{ $card['value'] }}</div>
                @if(!empty($card['hint']))
                    <div class="small text-muted mt-2">{{ $card['hint'] }}</div>
                @endif
            </div>
        </div>
    @endforeach
</div>
