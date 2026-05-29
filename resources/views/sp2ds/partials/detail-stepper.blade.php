{{-- Shared SP2D progress stepper.
     Expects: $progressStep (1=draft,2=verifikasi,3=selesai), $stepFail (bool) --}}
@php
    $stepFail = $stepFail ?? false;
    // 4 nodes -> 3 gaps. Track spans from node 1 center to node 4 center.
    // Fill = how many gaps are completed (NPI step is always passed).
    $completedGaps = max(0, min(3, $progressStep)); // step1 done=1 gap, step2 done=2 gaps, step3 done=3 gaps
    $fillPct = ($completedGaps / 3) * 100;

    $steps = [
        ['icon' => 'verified',       'label' => 'Integrasi NPI',     'sub' => 'Final ber-TTE',                       'state' => 'passed'],
        ['icon' => 'edit_document',  'label' => 'Pencatatan Draft',  'sub' => 'Bendahara Pengeluaran',               'state' => $progressStep > 1 ? 'passed' : ($progressStep === 1 ? 'active' : '')],
        ['icon' => $stepFail && $progressStep === 2 ? 'report_problem' : 'shield', 'label' => 'Validasi Keuangan', 'sub' => 'PPK · Kasubbag · PPSPM · Koordinator', 'state' => $progressStep > 2 ? 'passed' : ($progressStep === 2 ? ($stepFail ? 'fail' : 'active') : '')],
        ['icon' => 'task_alt',       'label' => 'SP2D Terbit',       'sub' => 'Selesai',                             'state' => $progressStep >= 3 ? 'passed' : ''],
    ];
@endphp
<div class="sp2dd-card">
    <div class="sp2dd-card__head">
        <div class="sp2dd-card__title"><i class="material-icons-outlined">timeline</i> Peta Prosedur SP2D</div>
    </div>
    <div class="sp2dd-card__body">
        <div class="sp2dd-stepper">
            <div class="sp2dd-track"><div class="sp2dd-track__fill" data-fill="{{ $fillPct }}"></div></div>
            @foreach($steps as $step)
                <div class="sp2dd-step {{ $step['state'] }}">
                    <div class="sp2dd-step__icon"><i class="material-icons-outlined">{{ $step['icon'] }}</i></div>
                    <div class="sp2dd-step__label">{{ $step['label'] }}</div>
                    <div class="sp2dd-step__sub">{{ $step['sub'] }}</div>
                </div>
            @endforeach
        </div>
    </div>
</div>

<script>
(function () {
    var fill = document.currentScript.previousElementSibling.querySelector('.sp2dd-track__fill');
    if (!fill) return;
    var pct = fill.getAttribute('data-fill') || '0';
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) { fill.style.width = pct + '%'; return; }
    requestAnimationFrame(function () { setTimeout(function () { fill.style.width = pct + '%'; }, 120); });
})();
</script>
