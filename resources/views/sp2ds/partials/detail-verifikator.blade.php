{{-- Shared verifikator status grid.
     Expects: $verifikators => ['Label' => $approval, ...] --}}
@php
    // Resolve a display name per role_code (cached) for steps still pending
    // where no specific user has been assigned/acted yet.
    if (!function_exists('sp2dd_role_user_name')) {
        function sp2dd_role_user_name(?string $roleCode): ?string {
            static $cache = [];
            if (!$roleCode) return null;
            if (array_key_exists($roleCode, $cache)) return $cache[$roleCode];
            $name = null;
            try {
                $name = \App\Models\User::role($roleCode)->orderBy('id')->first()?->name;
            } catch (\Throwable $e) {
                $name = null;
            }
            return $cache[$roleCode] = $name;
        }
    }
@endphp
<div class="sp2dd-card">
    <div class="sp2dd-card__head">
        <div class="sp2dd-card__title"><i class="material-icons-outlined">how_to_reg</i> Status Verifikator SP2D</div>
    </div>
    <div class="sp2dd-card__body">
        <div class="sp2dd-verif">
            @foreach($verifikators as $label => $approval)
                @php
                    $st = $approval?->status;
                    $tone = match($st) {
                        'APPROVED' => 'ok',
                        'PENDING'  => 'wait',
                        'REVISION','REJECTED' => 'bad',
                        default => 'idle',
                    };
                    $ic = match($st) {
                        'APPROVED' => 'check_circle',
                        'PENDING'  => 'hourglass_top',
                        'REVISION','REJECTED' => 'cancel',
                        default => 'schedule',
                    };
                    $txt = $st ?? 'MENUNGGU';
                    $verifName = $approval?->actedByUser?->name
                        ?? $approval?->assignedUser?->name
                        ?? sp2dd_role_user_name($approval?->role_code)
                        ?? 'Belum ditetapkan';
                @endphp
                <div class="sp2dd-vcard {{ $tone }}" style="animation-delay: {{ $loop->index * 0.06 }}s;">
                    <div class="sp2dd-vcard__role">{{ $label }}</div>
                    <div class="sp2dd-vcard__name">{{ $verifName }}</div>
                    <span class="sp2dd-vpill {{ $tone }}"><i class="material-icons-outlined">{{ $ic }}</i> {{ $txt }}</span>
                </div>
            @endforeach
        </div>
    </div>
</div>
