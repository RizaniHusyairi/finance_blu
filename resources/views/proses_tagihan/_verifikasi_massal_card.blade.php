{{-- Banner Verifikasi Massal: satu tombol menyetujui semua dokumen SPP/SPM/NPI
     yang menunggu persetujuan user saat ini. Tampil hanya bila ≥2 dokumen
     pending (satu dokumen sudah terlayani tombol per-kartu). SP2D dikecualikan
     — penerbitan pembayaran tetap aksi tersendiri. --}}
@php
    $vmDocs = collect([
        'spp' => ['label' => 'SPP', 'ikon' => 'bi-file-earmark-arrow-up-fill', 'nomor' => $state['spp']?->nomor_spp],
        'spm' => ['label' => 'SPM', 'ikon' => 'bi-file-earmark-check-fill', 'nomor' => $state['spm']?->nomor_spm],
        'npi' => ['label' => 'NPI', 'ikon' => 'bi-file-earmark-ruled-fill', 'nomor' => $state['npi']?->nomor_npi],
    ])->filter(fn ($d, $jenis) => $state['myApprovals'][$jenis]->isNotEmpty());

    $vmRole = $vmDocs->keys()
        ->map(fn ($jenis) => $state['myApprovals'][$jenis]->first()?->role_code)
        ->filter()->unique()->implode(' / ');
@endphp

@if(session('bulk_approved'))
    <div class="pt-confetti" id="vmConfetti"></div>
@endif

@if($vmDocs->count() >= 2)
    <style>
        .vm-card {
            position: relative;
            border-radius: var(--pt-radius, 18px);
            padding: 1.5px; /* bingkai gradien */
            background: linear-gradient(120deg, var(--pt-primary), var(--pt-primary-2), var(--pt-primary));
            background-size: 200% 200%;
            animation: vmBorder 6s ease infinite;
            box-shadow: 0 18px 40px -22px rgba(79, 70, 229, .55);
        }
        .vm-inner {
            border-radius: calc(var(--pt-radius, 18px) - 1.5px);
            background:
                radial-gradient(600px 140px at 90% -30%, rgba(124, 58, 237, .10), transparent 70%),
                linear-gradient(180deg, #ffffff, #f8f8ff);
            padding: 1.35rem 1.5rem;
        }
        @keyframes vmBorder { 0%, 100% { background-position: 0% 50%; } 50% { background-position: 100% 50%; } }
        .vm-ic {
            width: 52px; height: 52px; border-radius: 15px; flex-shrink: 0;
            display: flex; align-items: center; justify-content: center; font-size: 1.4rem; color: #fff;
            background: linear-gradient(135deg, var(--pt-primary), var(--pt-primary-2));
            box-shadow: 0 10px 22px -10px rgba(79, 70, 229, .8);
            animation: vmPulse 2.4s ease-in-out infinite;
        }
        @keyframes vmPulse {
            0%, 100% { transform: scale(1); box-shadow: 0 10px 22px -10px rgba(79,70,229,.8); }
            50% { transform: scale(1.07); box-shadow: 0 14px 30px -10px rgba(124,58,237,.9); }
        }
        .vm-doc {
            display: inline-flex; align-items: center; gap: .45rem;
            font-size: .78rem; font-weight: 700; color: #3730a3;
            background: #fff; border: 1px solid #ddd9fb; border-radius: 999px;
            padding: .38rem .85rem;
            opacity: 0; transform: translateY(8px);
            animation: vmChip .5s cubic-bezier(.22,1,.36,1) forwards;
            transition: transform .18s, box-shadow .18s;
        }
        .vm-doc:nth-child(2) { animation-delay: .1s; }
        .vm-doc:nth-child(3) { animation-delay: .2s; }
        .vm-doc:hover { transform: translateY(-2px); box-shadow: 0 8px 18px -12px rgba(79,70,229,.6); }
        .vm-doc i { color: var(--pt-primary); }
        .vm-doc .vm-nomor { font-weight: 500; color: #64748b; font-size: .72rem; }
        @keyframes vmChip { to { opacity: 1; transform: translateY(0); } }
        .vm-btn {
            position: relative; overflow: hidden;
            border: 0; border-radius: .8rem; padding: .75rem 1.6rem;
            font-weight: 800; font-size: .95rem; color: #fff;
            background: linear-gradient(120deg, #059669, #10b981);
            box-shadow: 0 12px 26px -12px rgba(16, 185, 129, .8);
            transition: transform .2s, box-shadow .2s;
        }
        .vm-btn:hover { color: #fff; transform: translateY(-2px); box-shadow: 0 18px 34px -14px rgba(16,185,129,.9); }
        .vm-btn:active { transform: translateY(0); }
        .vm-btn::after {
            content: ''; position: absolute; top: 0; bottom: 0; width: 34%; left: -45%;
            background: linear-gradient(100deg, transparent, rgba(255,255,255,.5), transparent);
            transform: skewX(-18deg);
            animation: vmShine 2.8s ease-in-out infinite;
        }
        @keyframes vmShine { 0%, 55% { left: -45%; } 85%, 100% { left: 115%; } }
        .vm-modal-doc {
            display: flex; align-items: center; gap: .8rem;
            border: 1px solid #e7eaf3; border-radius: .8rem; padding: .7rem .9rem;
        }
        .vm-modal-doc .vm-modal-ic {
            width: 36px; height: 36px; border-radius: 10px; flex-shrink: 0;
            display: flex; align-items: center; justify-content: center;
            background: var(--tone-indigo-soft, rgba(79,70,229,.1)); color: var(--pt-primary);
        }
        @media (prefers-reduced-motion: reduce) {
            .vm-card, .vm-ic, .vm-doc, .vm-btn::after { animation: none !important; }
            .vm-doc { opacity: 1; transform: none; }
            .vm-btn, .vm-btn:hover, .vm-doc:hover { transition: none; transform: none; }
        }
    </style>

    <div class="vm-card mb-4 reveal">
        <div class="vm-inner">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="vm-ic"><i class="bi bi-shield-fill-check"></i></div>
                    <div>
                        <h6 class="fw-bolder text-dark mb-1">Giliran Anda — Verifikasi Massal</h6>
                        <div class="small text-secondary">
                            <strong>{{ $vmDocs->count() }} dokumen</strong> menunggu persetujuan Anda sebagai <strong>{{ $vmRole }}</strong> — setujui semuanya sekali klik, atau tinjau satu per satu pada kartu di bawah.
                        </div>
                        <div class="d-flex flex-wrap gap-2 mt-2">
                            @foreach($vmDocs as $doc)
                                <span class="vm-doc">
                                    <i class="bi {{ $doc['ikon'] }}"></i> {{ $doc['label'] }}
                                    @if($doc['nomor'])<span class="vm-nomor">{{ $doc['nomor'] }}</span>@endif
                                </span>
                            @endforeach
                        </div>
                    </div>
                </div>
                <button type="button" class="vm-btn" data-bs-toggle="modal" data-bs-target="#modalVerifikasiMassal">
                    <i class="bi bi-check2-all me-1"></i> Setujui Semua ({{ $vmDocs->count() }} Dokumen)
                </button>
            </div>
        </div>
    </div>

    {{-- Modal konfirmasi verifikasi massal --}}
    <div class="modal fade" id="modalVerifikasiMassal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" action="{{ route('proses-tagihan.dokumen.setujui-semua', $tagihan->id) }}" class="modal-content" style="border: 0; border-radius: 1rem; overflow: hidden;">
                @csrf
                <div class="modal-header text-white" style="background: linear-gradient(120deg, var(--pt-primary), var(--pt-primary-2)); border: 0;">
                    <h6 class="modal-title fw-bold mb-0"><i class="bi bi-check2-all me-2"></i>Konfirmasi Verifikasi Massal</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body d-flex flex-column gap-2 p-4">
                    <p class="small text-secondary mb-1">
                        Anda akan menyetujui <strong>{{ $vmDocs->count() }} dokumen</strong> berikut sekaligus sebagai <strong>{{ $vmRole }}</strong>:
                    </p>
                    @foreach($vmDocs as $doc)
                        <div class="vm-modal-doc">
                            <span class="vm-modal-ic"><i class="bi {{ $doc['ikon'] }}"></i></span>
                            <div>
                                <div class="fw-bold small">{{ $doc['label'] }}</div>
                                <div class="text-secondary" style="font-size: .74rem;">{{ $doc['nomor'] ?? '—' }}</div>
                            </div>
                            <i class="bi bi-check-circle text-success ms-auto"></i>
                        </div>
                    @endforeach

                    <div class="form-floating mt-2">
                        <textarea name="catatan" class="form-control" id="catatanVerifMassal" style="height: 72px" maxlength="1000" placeholder="Catatan..."></textarea>
                        <label for="catatanVerifMassal">Catatan persetujuan (opsional, berlaku untuk semua)</label>
                    </div>
                    <div class="small text-muted d-flex gap-2 mt-1">
                        <i class="bi bi-info-circle flex-shrink-0"></i>
                        <span>Persetujuan tercatat per dokumen atas nama Anda. Untuk meminta perbaikan, gunakan tombol <strong>Revisi</strong> pada kartu dokumen terkait.</span>
                    </div>
                </div>
                <div class="modal-footer" style="background: #fafbfd;">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="vm-btn" style="padding: .55rem 1.3rem; font-size: .88rem;">
                        <i class="bi bi-check2-all me-1"></i> Ya, Setujui Semua
                    </button>
                </div>
            </form>
        </div>
    </div>
@endif

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        // Modal keluar dari kartu beranimasi agar backdrop tidak menutupinya
        // (stacking context pada elemen ber-transform).
        var modal = document.getElementById('modalVerifikasiMassal');
        if (modal && modal.parentElement !== document.body) {
            document.body.appendChild(modal);
        }

        // Cegah klik ganda saat submit.
        var form = modal ? modal.querySelector('form') : null;
        if (form) {
            form.addEventListener('submit', function () {
                var btn = form.querySelector('button[type=submit]');
                if (btn) {
                    btn.disabled = true;
                    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Memproses…';
                }
            });
        }

        // Confetti perayaan setelah verifikasi massal berhasil.
        var conf = document.getElementById('vmConfetti');
        if (conf && !reduced) {
            var colors = ['#4f46e5', '#10b981', '#f59e0b', '#06b6d4', '#8b5cf6', '#f43f5e'];
            for (var i = 0; i < 70; i++) {
                var s = document.createElement('span');
                s.style.left = Math.random() * 100 + 'vw';
                s.style.background = colors[i % colors.length];
                s.style.animationDuration = (2.2 + Math.random() * 2) + 's';
                s.style.animationDelay = (Math.random() * 1) + 's';
                s.style.transform = 'rotate(' + Math.random() * 360 + 'deg)';
                conf.appendChild(s);
            }
            setTimeout(function () { conf.remove(); }, 6000);
        } else if (conf) {
            conf.remove();
        }
    });
</script>
