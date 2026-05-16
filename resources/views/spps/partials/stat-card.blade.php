{{--
    Stat Card komponen — terinspirasi gaya app cards minimalis.

    Variabel:
      $icon         (string) class Bootstrap Icon, contoh 'bi-tag'
      $color        (string) warna tema: warning|primary|info|success|dark|danger
      $category     (string) label kategori kecil, contoh 'COA'
      $value        (string|int) angka/nilai utama
      $description  (string) deskripsi kecil di bawah angka
      $badge        (?string) label pill kanan-atas (opsional)
      $badgeColor   (?string) warna pill, default 'success'
--}}
@php
    $color = $color ?? 'primary';
    $badgeColor = $badgeColor ?? 'success';
@endphp

<div class="card stat-card h-100 border-0 shadow-sm rounded-4 position-relative overflow-hidden">
    <div class="card-body p-4">
        <div class="d-flex justify-content-between align-items-start mb-3">
            <div class="stat-icon bg-{{ $color }} bg-opacity-10 text-{{ $color }}">
                <i class="bi {{ $icon }}"></i>
            </div>
            @isset($badge)
                <span class="badge rounded-pill bg-{{ $badgeColor }} bg-opacity-10 text-{{ $badgeColor }} px-3 py-2 fw-semibold border border-{{ $badgeColor }} border-opacity-25">
                    {{ $badge }}
                </span>
            @endisset
        </div>

        <div class="text-muted small text-uppercase fw-semibold mb-1" style="font-size:.68rem; letter-spacing:.6px;">
            {{ $category }}
        </div>

        <div class="stat-value fw-bold text-dark mb-1">{{ $value }}</div>

        <small class="text-muted">{{ $description }}</small>

        {{-- Dekorasi: animasi ombak berlapis di bagian bawah --}}
        <div class="stat-deco text-{{ $color }}">
            <svg class="stat-wave stat-wave-1" viewBox="0 0 2400 80" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none">
                <path d="M0 45 Q 100 15, 200 45 T 400 45 T 600 45 T 800 45 T 1000 45 T 1200 45 T 1400 45 T 1600 45 T 1800 45 T 2000 45 T 2200 45 T 2400 45 V 80 H 0 Z" fill="currentColor"/>
            </svg>
            <svg class="stat-wave stat-wave-2" viewBox="0 0 2400 80" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none">
                <path d="M0 55 Q 150 25, 300 55 T 600 55 T 900 55 T 1200 55 T 1500 55 T 1800 55 T 2100 55 T 2400 55 V 80 H 0 Z" fill="currentColor"/>
            </svg>
            <svg class="stat-wave stat-wave-3" viewBox="0 0 2400 80" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none">
                <path d="M0 62 Q 200 40, 400 62 T 800 62 T 1200 62 T 1600 62 T 2000 62 T 2400 62 V 80 H 0 Z" fill="currentColor"/>
            </svg>
        </div>
    </div>
</div>
