{{--
    Reusable calendar card.
    Required: $calendar = ['monthLabel', 'firstWeekday', 'daysInMonth', 'today' => int|null, 'events' => [day => [['type','label','nomor'?,'mitra'?,'status'?], ...]]]
    Optional props: $title (default "Kalender Tagihan"), $subtitle (default $calendar['monthLabel']),
                    $iconBg (default amber gradient), $icon (default 'bi-calendar3')
--}}
@php
    $title = $title ?? 'Kalender Tagihan';
    $subtitle = $subtitle ?? ($calendar['monthLabel'] ?? '');
    $iconBg = $iconBg ?? 'linear-gradient(135deg, #f59e0b, #fbbf24)';
    $iconShadow = $iconShadow ?? 'rgba(245, 158, 11, .25)';
    $icon = $icon ?? 'bi-calendar3';
    $weekdays = ['M','S','S','R','K','J','S'];
@endphp
<div class="sa-chart-card h-100">
    <div class="sa-chart-body">
        <div class="sa-chart-head">
            <div class="sa-chart-title">
                <span class="sa-chart-icon" style="background: {{ $iconBg }}; box-shadow: 0 12px 24px {{ $iconShadow }};">
                    <i class="bi {{ $icon }}"></i>
                </span>
                <div>
                    <h6>{{ $title }}</h6>
                    <small>{{ $subtitle }}</small>
                </div>
            </div>
        </div>

        <div class="sa-cal-grid">
            @foreach($weekdays as $w)
                <div class="sa-cal-h">{{ $w }}</div>
            @endforeach

            @for($i = 0; $i < ($calendar['firstWeekday'] ?? 0); $i++)
                <div class="sa-cal-cell sa-cal-empty"></div>
            @endfor

            @for($d = 1; $d <= ($calendar['daysInMonth'] ?? 0); $d++)
                @php
                    $events = $calendar['events'][$d] ?? [];
                    $isToday = (($calendar['today'] ?? null) === $d);
                    $hasEvent = !empty($events);
                    $types = collect($events)->pluck('type')->unique()->values();
                    $delay = ($d * 0.018);
                @endphp
                <div class="sa-cal-cell {{ $isToday ? 'sa-cal-today' : '' }} {{ $hasEvent ? 'sa-has-event' : '' }}"
                     style="animation-delay: {{ number_format($delay, 3) }}s;">
                    <span>{{ $d }}</span>

                    @if($hasEvent)
                        <span class="sa-cal-dots">
                            @foreach($types as $type)
                                @php
                                    $cls = match($type) {
                                        'terbit' => 'sa-dot-terbit',
                                        'jatuh_tempo' => 'sa-dot-jt',
                                        'lunas' => 'sa-dot-lunas',
                                        default => '',
                                    };
                                @endphp
                                <span class="sa-cal-dot {{ $cls }}"></span>
                            @endforeach
                        </span>
                        <div class="sa-cal-tooltip">
                            <div class="mb-1"><strong>Tanggal {{ $d }}</strong></div>
                            @foreach(array_slice($events, 0, 3) as $ev)
                                <div class="d-flex justify-content-between gap-2">
                                    <span>{{ $ev['label'] ?? '-' }}</span>
                                    <span class="text-white-50">{{ $ev['nomor'] ?? '' }}</span>
                                </div>
                            @endforeach
                            @if(count($events) > 3)
                                <div class="text-white-50">+{{ count($events) - 3 }} lainnya</div>
                            @endif
                        </div>
                    @endif
                </div>
            @endfor
        </div>

        <div class="sa-cal-legend">
            <span><i class="terbit"></i> Tagihan terbit</span>
            <span><i class="jt"></i> Jatuh tempo</span>
            <span><i class="lunas"></i> Lunas</span>
        </div>
    </div>
</div>
