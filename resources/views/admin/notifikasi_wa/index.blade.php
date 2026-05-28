@extends('layouts.app')

@section('title', 'Manajemen Notifikasi WhatsApp')

@push('css')
<style>
    .nwa-hero {
        background: linear-gradient(135deg, #25D366 0%, #128C7E 50%, #075E54 100%);
        color: #fff;
        border-radius: 1.25rem;
        padding: 1.5rem 2rem;
        margin-bottom: 1.5rem;
        position: relative;
        overflow: hidden;
        box-shadow: 0 14px 32px rgba(18, 140, 126, .25);
    }
    .nwa-hero::before, .nwa-hero::after {
        content: '';
        position: absolute;
        border-radius: 50%;
    }
    .nwa-hero::before { right: -90px; top: -90px; width: 280px; height: 280px; background: rgba(255,255,255,.10); }
    .nwa-hero::after  { right: 60px; bottom: -70px; width: 180px; height: 180px; background: rgba(255,255,255,.07); }
    .nwa-hero > * { position: relative; z-index: 1; }
    .nwa-hero .hero-tag {
        display: inline-flex; align-items: center; gap: .45rem;
        background: rgba(255,255,255,.18);
        backdrop-filter: blur(8px);
        border: 1px solid rgba(255,255,255,.30);
        padding: .35rem .85rem;
        border-radius: 999px;
        font-size: .75rem; font-weight: 700;
        margin-bottom: .55rem;
        text-transform: uppercase;
        letter-spacing: .04em;
    }
    .nwa-hero h2 {
        color: #fff !important;
        font-weight: 800; font-size: 1.55rem;
        margin: 0 0 .35rem;
        letter-spacing: -.01em;
    }
    .nwa-hero p { color: rgba(255,255,255,.92); margin: 0; }
    .nwa-illust {
        position: absolute;
        right: 1.5rem; top: 50%;
        transform: translateY(-50%) rotate(-8deg);
        font-size: 7rem; opacity: .14;
    }

    .nwa-card {
        background: #fff;
        border: 1px solid #eef0f4;
        border-radius: 1.15rem;
        margin-bottom: 1.15rem;
        overflow: hidden;
        box-shadow: 0 4px 12px rgba(15,23,42,.04);
    }
    .nwa-card .nwa-head {
        padding: 1rem 1.5rem;
        border-bottom: 1px solid #f1f3f7;
        display: flex; align-items: center; gap: .85rem;
        background: linear-gradient(180deg, #fafbff 0%, #ffffff 100%);
    }
    .nwa-card .nwa-icon {
        width: 44px; height: 44px;
        border-radius: 12px;
        display: inline-flex; align-items: center; justify-content: center;
        font-size: 1.2rem; color: #fff;
        flex-shrink: 0;
    }
    .nwa-icon.icon-warning { background: linear-gradient(135deg, #fbbf24, #f59e0b); box-shadow: 0 6px 14px rgba(245,158,11,.30); }
    .nwa-icon.icon-success { background: linear-gradient(135deg, #34d399, #10b981); box-shadow: 0 6px 14px rgba(16,185,129,.30); }
    .nwa-icon.icon-info    { background: linear-gradient(135deg, #38bdf8, #0ea5e9); box-shadow: 0 6px 14px rgba(14,165,233,.30); }

    .nwa-card .nwa-title {
        margin: 0;
        font-size: 1rem;
        font-weight: 800;
        color: #0f172a;
    }
    .nwa-card .nwa-sub {
        font-size: .76rem;
        color: #64748b;
        margin-top: .15rem;
    }
    .nwa-toggle {
        margin-left: auto;
        display: flex;
        align-items: center;
        gap: .55rem;
    }
    .nwa-toggle .form-check-input { width: 2.5em; height: 1.35em; cursor: pointer; }
    .nwa-toggle label { font-weight: 700; color: #475569; cursor: pointer; }

    .nwa-body { padding: 1.35rem 1.5rem; }

    .nwa-day-chips {
        display: flex; flex-wrap: wrap; gap: .55rem;
        margin-top: .65rem;
    }
    .nwa-day-chip {
        background: rgba(245,158,11,.10);
        color: #b45309;
        border: 1px solid rgba(245,158,11,.30);
        font-weight: 700;
        padding: .4rem .95rem;
        border-radius: 999px;
        font-size: .82rem;
        font-variant-numeric: tabular-nums;
    }

    .nwa-template {
        font-family: ui-monospace, "SF Mono", Menlo, Consolas, monospace;
        font-size: .85rem;
        line-height: 1.55;
        background: #fafbff;
        border: 1px solid #e2e8f0;
        border-radius: .75rem;
        padding: .85rem 1rem;
        resize: vertical;
        min-height: 200px;
    }
    .nwa-template:focus {
        border-color: #6366f1;
        background: #fff;
        box-shadow: 0 0 0 4px rgba(99,102,241,.12);
        outline: 0;
    }

    .nwa-placeholder-list {
        display: flex; flex-wrap: wrap; gap: .35rem;
        margin-top: .55rem;
    }
    .nwa-placeholder-list code {
        background: #f1f5f9;
        color: #0f172a;
        padding: .2rem .55rem;
        border-radius: .35rem;
        font-size: .76rem;
        cursor: pointer;
        border: 1px solid #e2e8f0;
        transition: all .15s ease;
    }
    .nwa-placeholder-list code:hover {
        background: #6366f1;
        color: #fff;
        border-color: #6366f1;
    }

    .nwa-action-bar {
        display: flex;
        gap: .65rem;
        flex-wrap: wrap;
        justify-content: flex-end;
    }
    .btn-nwa-primary {
        background: linear-gradient(135deg, #25D366, #128C7E);
        color: #fff;
        font-weight: 700;
        padding: .65rem 1.4rem;
        border-radius: .65rem;
        border: 0;
        box-shadow: 0 8px 22px rgba(37,211,102,.35);
        transition: all .25s ease;
    }
    .btn-nwa-primary:hover {
        color: #fff;
        transform: translateY(-2px);
        box-shadow: 0 14px 28px rgba(37,211,102,.45);
    }
    .btn-nwa-secondary {
        background: #f1f5f9;
        border: 1px solid #e2e8f0;
        color: #475569;
        font-weight: 600;
        padding: .65rem 1.2rem;
        border-radius: .65rem;
    }
    .btn-nwa-secondary:hover { background: #e2e8f0; color: #1e293b; }

    .nwa-log-table th {
        font-size: .65rem;
        font-weight: 700;
        letter-spacing: .06em;
        text-transform: uppercase;
        color: #64748b;
        background: #f8fafc;
    }
    .nwa-log-table td {
        font-size: .82rem;
        vertical-align: middle;
    }
    .nwa-status-pill {
        display: inline-flex;
        align-items: center;
        gap: .3rem;
        font-size: .7rem;
        font-weight: 700;
        padding: .25rem .65rem;
        border-radius: 999px;
        text-transform: uppercase;
        letter-spacing: .05em;
    }
    .nwa-status-pill.is-sent    { background: rgba(16,185,129,.12); color: #047857; }
    .nwa-status-pill.is-mock    { background: rgba(99,102,241,.10); color: #4338ca; }
    .nwa-status-pill.is-failed  { background: rgba(244,63,94,.10); color: #b91c1c; }
    .nwa-status-pill.is-pending { background: rgba(245,158,11,.12); color: #b45309; }
</style>
@endpush

@section('content')
<div class="nwa-hero">
    <i class="bi bi-whatsapp nwa-illust d-none d-md-block"></i>
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <span class="hero-tag"><i class="bi bi-bell-fill"></i> Manajemen Notifikasi</span>
            <h2><i class="bi bi-whatsapp me-2"></i>Notifikasi WhatsApp Otomatis</h2>
            <p>Atur reminder tagihan jatuh tempo dan konfirmasi lunas yang akan dikirim ke mitra secara otomatis.</p>
        </div>
        <form action="{{ route('admin.notifikasi-wa.run-now') }}" method="POST" class="d-inline">
            @csrf
            <button type="submit"
                class="btn btn-light fw-bold d-inline-flex align-items-center gap-2"
                data-sky-confirm="Jalankan reminder sekarang? Sistem akan men-scan tagihan published yang sesuai rentang hari aktif dan mengirim WA jika belum pernah dikirim hari ini."
                data-sky-confirm-title="Jalankan Reminder Manual">
                <i class="bi bi-play-circle-fill text-success"></i>
                Jalankan Reminder Sekarang
            </button>
        </form>
    </div>
</div>

<form action="{{ route('admin.notifikasi-wa.update') }}" method="POST">
    @csrf
    @method('PUT')

    {{-- A. Reminder Jatuh Tempo --}}
    <div class="nwa-card">
        <div class="nwa-head">
            <span class="nwa-icon icon-warning"><i class="bi bi-clock-history"></i></span>
            <div>
                <h6 class="nwa-title">Reminder Jatuh Tempo</h6>
                <div class="nwa-sub">Kirim WA otomatis ke mitra menjelang tanggal jatuh tempo tagihan PNBP.</div>
            </div>
            <div class="nwa-toggle">
                <div class="form-check form-switch m-0">
                    <input class="form-check-input" type="checkbox" name="reminder_enabled" id="reminderEnabled" value="1" {{ $settings['reminder_enabled'] ? 'checked' : '' }}>
                    <label class="form-check-label" for="reminderEnabled">{{ $settings['reminder_enabled'] ? 'Aktif' : 'Nonaktif' }}</label>
                </div>
            </div>
        </div>
        <div class="nwa-body">
            <div class="row g-4">
                <div class="col-lg-6">
                    <label class="form-label fw-bold">Rentang Hari Sebelum Jatuh Tempo</label>
                    <input type="text" name="reminder_days_before" id="reminderDaysBefore"
                        class="form-control" value="{{ old('reminder_days_before', $settings['reminder_days_before']) }}"
                        placeholder="Contoh: 7,3,1,0" required>
                    <div class="form-text">
                        <i class="bi bi-info-circle me-1"></i>Pisahkan dengan koma. <strong>0</strong> = pada hari jatuh tempo. Contoh "7,3,1,0" = kirim H-7, H-3, H-1, dan hari H.
                    </div>
                    <div class="nwa-day-chips" id="dayChips"></div>
                </div>
                <div class="col-lg-3">
                    <label class="form-label fw-bold">Jam Pengiriman</label>
                    <input type="time" name="reminder_send_time"
                        class="form-control" value="{{ old('reminder_send_time', $settings['reminder_send_time']) }}" required>
                    <div class="form-text">Reminder dikirim sekali pada jam ini setiap hari.</div>
                </div>
                <div class="col-lg-3">
                    <label class="form-label fw-bold">Penjadwalan</label>
                    <div class="border rounded-3 p-2 bg-light small">
                        <div><i class="bi bi-clock me-1 text-success"></i>Cron berjalan tiap jam.</div>
                        <div class="text-muted mt-1" style="font-size:.72rem;">
                            Pastikan <code>php artisan schedule:run</code> aktif di server.
                        </div>
                    </div>
                </div>
                <div class="col-12">
                    <label class="form-label fw-bold">Template Pesan</label>
                    <textarea name="reminder_template" class="form-control nwa-template" required>{{ old('reminder_template', $settings['reminder_template']) }}</textarea>
                    <div class="nwa-placeholder-list">
                        <span class="text-muted small me-1">Placeholder:</span>
                        <code data-paste="{mitra_nama}">{mitra_nama}</code>
                        <code data-paste="{nomor_tagihan}">{nomor_tagihan}</code>
                        <code data-paste="{total}">{total}</code>
                        <code data-paste="{nomor_va}">{nomor_va}</code>
                        <code data-paste="{jatuh_tempo}">{jatuh_tempo}</code>
                        <code data-paste="{sisa_hari}">{sisa_hari}</code>
                        <code data-paste="{link_invoice}">{link_invoice}</code>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- B. Notifikasi Lunas --}}
    <div class="nwa-card">
        <div class="nwa-head">
            <span class="nwa-icon icon-success"><i class="bi bi-check-circle-fill"></i></span>
            <div>
                <h6 class="nwa-title">Konfirmasi Pembayaran Lunas</h6>
                <div class="nwa-sub">Otomatis dikirim ke mitra saat callback BTN VA mengkonfirmasi pembayaran.</div>
            </div>
            <div class="nwa-toggle">
                <div class="form-check form-switch m-0">
                    <input class="form-check-input" type="checkbox" name="lunas_enabled" id="lunasEnabled" value="1" {{ $settings['lunas_enabled'] ? 'checked' : '' }}>
                    <label class="form-check-label" for="lunasEnabled">{{ $settings['lunas_enabled'] ? 'Aktif' : 'Nonaktif' }}</label>
                </div>
            </div>
        </div>
        <div class="nwa-body">
            <label class="form-label fw-bold">Template Pesan Lunas</label>
            <textarea name="lunas_template" class="form-control nwa-template" required>{{ old('lunas_template', $settings['lunas_template']) }}</textarea>
            <div class="nwa-placeholder-list">
                <span class="text-muted small me-1">Placeholder:</span>
                <code data-paste="{mitra_nama}">{mitra_nama}</code>
                <code data-paste="{nomor_tagihan}">{nomor_tagihan}</code>
                <code data-paste="{total}">{total}</code>
                <code data-paste="{referensi}">{referensi}</code>
                <code data-paste="{link_invoice}">{link_invoice}</code>
            </div>
        </div>
    </div>

    {{-- C. Notifikasi Pengajuan Tagihan Kontrak --}}
    <div class="nwa-card">
        <div class="nwa-head">
            <span class="nwa-icon icon-info"><i class="bi bi-file-earmark-text-fill"></i></span>
            <div>
                <h6 class="nwa-title">Notifikasi Pengajuan Tagihan (Verifikator)</h6>
                <div class="nwa-sub">Otomatis dikirim ke 5 pejabat verifikator saat tagihan kontrak diajukan.</div>
            </div>
            <div class="nwa-toggle">
                <div class="form-check form-switch m-0">
                    <input class="form-check-input" type="checkbox" name="pengajuan_tagihan_enabled" id="pengajuanTagihanEnabled" value="1" {{ $settings['pengajuan_tagihan_enabled'] ? 'checked' : '' }}>
                    <label class="form-check-label" for="pengajuanTagihanEnabled">{{ $settings['pengajuan_tagihan_enabled'] ? 'Aktif' : 'Nonaktif' }}</label>
                </div>
            </div>
        </div>
        <div class="nwa-body">
            <div class="alert alert-info mb-0 small">
                <i class="bi bi-info-circle me-1"></i> Jika diaktifkan, sistem akan otomatis mengirimkan notifikasi via WhatsApp ke nomor HP Pejabat Verifikator (PPK, PPSPM, Koor Keu, Bend. Keluar, Bend. Terima) yang terdaftar di Master Pegawai ketika ada tagihan kontrak yang baru diajukan (status DRAFT -> PENDING_VERIFIKASI_KONTRAK).
            </div>
        </div>
    </div>

    <div class="nwa-action-bar mb-4">
        <button type="reset" class="btn btn-nwa-secondary">
            <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
        </button>
        <button type="submit" class="btn-nwa-primary">
            <i class="bi bi-save2-fill me-1"></i> Simpan Pengaturan
        </button>
    </div>
</form>

{{-- C. Test Pengiriman --}}
<div class="nwa-card">
    <div class="nwa-head">
        <span class="nwa-icon icon-info"><i class="bi bi-send-check-fill"></i></span>
        <div>
            <h6 class="nwa-title">Test Pengiriman</h6>
            <div class="nwa-sub">Kirim pesan langsung ke nomor tertentu untuk verifikasi gateway.</div>
        </div>
    </div>
    <div class="nwa-body">
        <form action="{{ route('admin.notifikasi-wa.test') }}" method="POST" class="row g-3">
            @csrf
            <div class="col-md-3">
                <label class="form-label fw-bold">No WhatsApp Tujuan</label>
                <input type="text" name="target" class="form-control" placeholder="08xxx atau 628xxx" required>
            </div>
            <div class="col-md-7">
                <label class="form-label fw-bold">Pesan</label>
                <input type="text" name="message" class="form-control" placeholder="Tulis pesan test" maxlength="2000" required>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn-nwa-primary w-100">
                    <i class="bi bi-send me-1"></i> Kirim
                </button>
            </div>
        </form>
    </div>
</div>

{{-- D. Log Pengiriman Terbaru --}}
<div class="nwa-card">
    <div class="nwa-head">
        <span class="nwa-icon icon-info"><i class="bi bi-list-check"></i></span>
        <div>
            <h6 class="nwa-title">Log Pengiriman Terbaru</h6>
            <div class="nwa-sub">15 pesan terakhir, lengkap dengan status dan response gateway.</div>
        </div>
    </div>
    <div class="nwa-body p-0">
        <div class="table-responsive">
            <table class="table nwa-log-table mb-0">
                <thead>
                    <tr>
                        <th>Waktu</th>
                        <th>Tujuan</th>
                        <th>Provider</th>
                        <th>Pesan</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                        <tr>
                            <td class="font-monospace small">{{ $log->created_at->format('d M Y H:i') }}</td>
                            <td class="font-monospace">{{ $log->target }}</td>
                            <td><span class="badge bg-light text-dark">{{ $log->provider }}</span></td>
                            <td class="text-muted small" style="max-width:340px;">
                                <div class="text-truncate">{{ \Illuminate\Support\Str::limit($log->message, 80) }}</div>
                            </td>
                            <td>
                                @php
                                    $cls = match ($log->status) {
                                        'sent'    => 'is-sent',
                                        'mock'    => 'is-mock',
                                        'failed'  => 'is-failed',
                                        default   => 'is-pending',
                                    };
                                @endphp
                                <span class="nwa-status-pill {{ $cls }}">{{ $log->status }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">Belum ada log pengiriman.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('script')
<script>
    (function () {
        // Render preview chip rentang hari
        const input = document.getElementById('reminderDaysBefore');
        const chipsBox = document.getElementById('dayChips');

        function renderChips() {
            const offsets = (input.value || '')
                .split(',')
                .map(s => s.trim())
                .filter(s => s !== '' && /^\d+$/.test(s))
                .map(n => parseInt(n, 10))
                .filter((v, i, a) => a.indexOf(v) === i)
                .sort((a, b) => b - a);

            chipsBox.innerHTML = '';
            offsets.forEach(o => {
                const span = document.createElement('span');
                span.className = 'nwa-day-chip';
                span.textContent = o === 0 ? 'Hari H' : `H-${o}`;
                chipsBox.appendChild(span);
            });
        }
        input.addEventListener('input', renderChips);
        renderChips();

        // Klik placeholder code → masukkan ke textarea aktif
        document.querySelectorAll('[data-paste]').forEach(el => {
            el.addEventListener('click', () => {
                const txt = el.dataset.paste;
                const textarea = el.closest('.nwa-body').querySelector('textarea.nwa-template');
                if (! textarea) return;
                const start = textarea.selectionStart;
                const end = textarea.selectionEnd;
                textarea.value = textarea.value.slice(0, start) + txt + textarea.value.slice(end);
                textarea.focus();
                textarea.selectionStart = textarea.selectionEnd = start + txt.length;
            });
        });

        // Toggle label switch
        document.querySelectorAll('.nwa-toggle .form-check-input').forEach(cb => {
            const label = cb.closest('.nwa-toggle').querySelector('label');
            cb.addEventListener('change', () => {
                if (label) label.textContent = cb.checked ? 'Aktif' : 'Nonaktif';
            });
        });
    })();
</script>
@endpush
