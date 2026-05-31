@extends('layouts.app')
@section('title', 'Pengaturan Integrasi Jasa')

@push('css')
<style>
    @keyframes integrationHeroSweep {
        0% { transform: translateX(-120%) skewX(-18deg); opacity: 0; }
        20% { opacity: .28; }
        55%, 100% { transform: translateX(220%) skewX(-18deg); opacity: 0; }
    }
    .integration-hero {
        position: relative;
        overflow: hidden;
        isolation: isolate;
        border: 1px solid rgba(147, 197, 253, .28);
        border-top: 1px solid rgba(251, 191, 36, .42);
        border-radius: 0 24px 24px 0;
        background: radial-gradient(circle at 18% 30%, rgba(96, 165, 250, .28), transparent 28%),
            linear-gradient(110deg, #071421 0%, #0d2744 42%, #174f86 100%);
        color: #fff;
        padding: 28px 30px;
        box-shadow: 0 18px 50px rgba(18, 53, 92, .22);
    }
    .integration-hero::before,
    .integration-hero::after {
        content: "";
        position: absolute;
        pointer-events: none;
        z-index: -1;
    }
    .integration-hero::before {
        width: 430px;
        height: 220px;
        right: -90px;
        top: -80px;
        border-radius: 0 0 0 999px;
        border-left: 2px solid rgba(251, 191, 36, .44);
        border-bottom: 2px solid rgba(147, 197, 253, .24);
        background: radial-gradient(circle at 50% 30%, rgba(96, 165, 250, .18), transparent 62%);
    }
    .integration-hero::after {
        inset: 0;
        width: 46%;
        background: linear-gradient(90deg, transparent, rgba(125,211,252,.12), rgba(255,255,255,.20), rgba(96,165,250,.10), transparent);
        animation: integrationHeroSweep 4.2s ease-in-out infinite;
    }
    .integration-card {
        overflow: hidden;
        border: 1px solid rgba(37, 99, 235, .12);
        border-radius: 18px;
        background: #fff;
        box-shadow: 0 16px 42px rgba(37, 99, 235, .08);
    }
    .integration-card-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        padding: 12px 16px;
        border-bottom: 1px solid #bfdbfe;
        background: linear-gradient(90deg, #eff6ff 0%, #f8fbff 58%, #ffffff 100%);
    }
    .integration-title {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .integration-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 36px;
        height: 36px;
        border-radius: 10px;
        color: #fff;
        background: #1d4ed8;
        box-shadow: 0 10px 20px rgba(37, 99, 235, .18);
    }
    .integration-card .form-label {
        color: #334155;
        font-size: 13px;
        font-weight: 800;
    }
    .integration-card .form-control,
    .integration-card .form-select {
        border-color: #dbe3ef;
        border-radius: 13px;
        min-height: 42px;
    }
    .integration-soft {
        border: 1px solid #bfdbfe;
        border-radius: 16px;
        background: linear-gradient(135deg, #eff6ff 0%, #f8fbff 100%);
        padding: 14px;
        color: #475569;
    }
</style>
@endpush

@section('content')
<div class="integration-hero mb-4">
    <div class="d-flex flex-column flex-lg-row align-items-start align-items-lg-center justify-content-between gap-3">
        <div>
            <div class="small text-white-50 fw-bold text-uppercase mb-1">Integrasi Pembayaran & Notifikasi</div>
            <h4 class="mb-1 fw-bold text-white">Pengaturan API Bank BTN, WhatsApp & Email</h4>
            <p class="mb-0 small text-white-50">Kelola Virtual Account BTN, template invoice, struk pembayaran, email, dan log integrasi.</p>
        </div>
        <span class="badge bg-light text-primary px-3 py-2 rounded-pill">Mode {{ strtoupper($settings['btn_mode']) }}</span>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

<form method="POST" action="{{ route('jasa.integrasi.update') }}" class="integration-card mb-4">
    @csrf
    @method('PUT')
    <div class="integration-card-header">
        <div class="integration-title">
            <span class="integration-icon"><i class="bi bi-bank"></i></span>
            <div>
                <h6 class="mb-0 fw-black text-primary">Bank BTN Virtual Account</h6>
                <small class="text-muted fw-semibold">Konfigurasi VA untuk tagihan PNBP Jasa.</small>
            </div>
        </div>
        <div class="form-check form-switch mb-0">
            <input class="form-check-input" type="checkbox" name="btn_enabled" value="1" id="btnEnabled" {{ $settings['btn_enabled'] ? 'checked' : '' }}>
            <label class="form-check-label fw-bold" for="btnEnabled">Aktif</label>
        </div>
    </div>
    <div class="card-body p-4">
        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Mode</label>
                <select name="btn_mode" class="form-select">
                    @foreach(['mock' => 'Mock/Simulasi', 'sandbox' => 'Sandbox', 'production' => 'Production'] as $value => $label)
                        <option value="{{ $value }}" @selected($settings['btn_mode'] === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-5">
                <label class="form-label">Base URL API BTN</label>
                <input type="url" name="btn_base_url" class="form-control" value="{{ old('btn_base_url', $settings['btn_base_url']) }}" placeholder="https://sandbox-api.btn.co.id">
            </div>
            <div class="col-md-2">
                <label class="form-label">Prefix VA</label>
                <input type="text" name="btn_va_prefix" class="form-control" value="{{ old('btn_va_prefix', $settings['btn_va_prefix']) }}">
            </div>
            <div class="col-md-2">
                <label class="form-label">Aktif (Hari)</label>
                <input type="number" min="1" max="365" name="btn_va_expiry_days" class="form-control" value="{{ old('btn_va_expiry_days', $settings['btn_va_expiry_days']) }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">Client ID / Partner ID</label>
                <input type="text" name="btn_client_id" class="form-control" value="{{ old('btn_client_id', $settings['btn_client_id']) }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">Client Secret</label>
                <input type="password" name="btn_client_secret" class="form-control" placeholder="{{ $settings['btn_client_secret_masked'] ?: 'Isi untuk menyimpan secret' }}">
                <div class="form-text">Kosongkan jika tidak ingin mengubah secret.</div>
            </div>
            <div class="col-md-4">
                <label class="form-label">Merchant / Company Code</label>
                <input type="text" name="btn_merchant_id" class="form-control" value="{{ old('btn_merchant_id', $settings['btn_merchant_id']) }}">
            </div>
        </div>

        <div class="integration-soft mt-4">
            <strong>Catatan:</strong> mode real BTN belum menembak endpoint sebelum dokumen API BTN final dimasukkan. Saat ini sistem akan membuat VA mock dengan format prefix + ID tagihan, agar alur publish, invoice, dan pembayaran bisa diuji dulu.
        </div>
    </div>

    <div class="integration-card-header">
        <div class="integration-title">
            <span class="integration-icon"><i class="bi bi-whatsapp"></i></span>
            <div>
                <h6 class="mb-0 fw-black text-primary">Notifikasi WhatsApp Gateway</h6>
                <small class="text-muted fw-semibold">Pengaturan provider dan fitur notifikasi otomatis (TTE Kontrak, Tagihan, Invoice, Struk).</small>
            </div>
        </div>
        <div class="form-check form-switch mb-0">
            <input class="form-check-input" type="checkbox" name="whatsapp_enabled" value="1" id="waEnabled" {{ $settings['whatsapp_enabled'] ? 'checked' : '' }}>
            <label class="form-check-label fw-bold" for="waEnabled">Aktif</label>
        </div>
    </div>
    <div class="card-body p-4">
        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Provider</label>
                <select name="whatsapp_provider" class="form-select" id="waProvider">
                    <option value="fonnte" @selected($settings['whatsapp_provider'] === 'fonnte')>Fonnte</option>
                    <option value="wa_gateway" @selected($settings['whatsapp_provider'] === 'wa_gateway')>WA Gateway (Bearer)</option>
                </select>
            </div>
            <div class="col-md-5 wa-fonnte-only">
                <label class="form-label">Endpoint Fonnte</label>
                <input type="url" name="whatsapp_fonnte_endpoint" class="form-control" value="{{ old('whatsapp_fonnte_endpoint', $settings['whatsapp_fonnte_endpoint']) }}">
            </div>
            <div class="col-md-2">
                <label class="form-label">Kode Negara</label>
                <input type="text" name="whatsapp_default_country_code" class="form-control" value="{{ old('whatsapp_default_country_code', $settings['whatsapp_default_country_code']) }}">
            </div>
            <div class="col-md-2 wa-fonnte-only">
                <label class="form-label">Token Fonnte</label>
                <input type="password" name="whatsapp_fonnte_token" class="form-control" placeholder="{{ $settings['whatsapp_fonnte_token_masked'] ?: 'Token API' }}">
            </div>

            <div class="col-md-5 wa-gateway-only d-none">
                <label class="form-label">Gateway URL</label>
                <input type="url" name="whatsapp_gateway_url" class="form-control" value="{{ old('whatsapp_gateway_url', $settings['whatsapp_gateway_url']) }}" placeholder="https://wa.example.com">
                <small class="text-muted">Endpoint dasar gateway. Sistem akan POST ke <code>/send/text</code>.</small>
            </div>
            <div class="col-md-3 wa-gateway-only d-none">
                <label class="form-label">API Key (Bearer)</label>
                <input type="password" name="whatsapp_gateway_api_key" class="form-control" placeholder="{{ $settings['whatsapp_gateway_api_key_masked'] ?: 'Bearer API Key' }}">
            </div>
            <div class="col-md-2 wa-gateway-only d-none">
                <label class="form-label">Session</label>
                <input type="text" name="whatsapp_gateway_session" class="form-control" value="{{ old('whatsapp_gateway_session', $settings['whatsapp_gateway_session']) }}" placeholder="opsional">
                <small class="text-muted">Multi-akun. Kosongkan untuk default.</small>
            </div>

            <div class="col-md-6">
                <label class="form-label">Template Invoice</label>
                <textarea name="whatsapp_invoice_template" rows="5" class="form-control" placeholder="Opsional, template custom invoice">{{ old('whatsapp_invoice_template', $settings['whatsapp_invoice_template']) }}</textarea>
            </div>
            <div class="col-md-6">
                <label class="form-label">Template Struk Pembayaran</label>
                <textarea name="whatsapp_receipt_template" rows="5" class="form-control" placeholder="Opsional, template custom struk lunas">{{ old('whatsapp_receipt_template', $settings['whatsapp_receipt_template']) }}</textarea>
            </div>
        </div>
    </div>

    <div class="integration-card-header">
        <div class="integration-title">
            <span class="integration-icon"><i class="bi bi-envelope-paper"></i></span>
            <div>
                <h6 class="mb-0 fw-black text-primary">Notifikasi Email Tagihan</h6>
                <small class="text-muted fw-semibold">Email dikirim otomatis ke mitra saat tagihan dipublish.</small>
            </div>
        </div>
        <div class="form-check form-switch mb-0">
            <input class="form-check-input" type="checkbox" name="email_enabled" value="1" id="emailEnabled" {{ $settings['email_enabled'] ? 'checked' : '' }}>
            <label class="form-check-label fw-bold" for="emailEnabled">Aktif</label>
        </div>
    </div>
    <div class="card-body p-4">
        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Mailer</label>
                <select name="email_mailer" class="form-select" id="emailMailer">
                    @foreach(['log' => 'Log/Simulasi', 'smtp' => 'SMTP', 'sendmail' => 'Sendmail'] as $value => $label)
                        <option value="{{ $value }}" @selected($settings['email_mailer'] === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <small class="text-muted">Gunakan Log untuk testing tanpa kirim email real.</small>
            </div>
            <div class="col-md-5">
                <label class="form-label">Email Pengirim</label>
                <input type="email" name="email_from_address" class="form-control" value="{{ old('email_from_address', $settings['email_from_address']) }}" placeholder="billing@sikeren.id">
            </div>
            <div class="col-md-4">
                <label class="form-label">Nama Pengirim</label>
                <input type="text" name="email_from_name" class="form-control" value="{{ old('email_from_name', $settings['email_from_name']) }}" placeholder="SIKEREN-BLU">
            </div>

            <div class="col-md-4 email-smtp-only d-none">
                <label class="form-label">SMTP Host</label>
                <input type="text" name="email_smtp_host" class="form-control" value="{{ old('email_smtp_host', $settings['email_smtp_host']) }}" placeholder="smtp.example.com">
            </div>
            <div class="col-md-2 email-smtp-only d-none">
                <label class="form-label">Port</label>
                <input type="number" name="email_smtp_port" class="form-control" value="{{ old('email_smtp_port', $settings['email_smtp_port']) }}" min="1" max="65535">
            </div>
            <div class="col-md-2 email-smtp-only d-none">
                <label class="form-label">Encryption</label>
                <select name="email_smtp_encryption" class="form-select">
                    @foreach(['tls' => 'TLS', 'ssl' => 'SSL', 'none' => 'None'] as $value => $label)
                        <option value="{{ $value }}" @selected(($settings['email_smtp_encryption'] ?: 'none') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 email-smtp-only d-none">
                <label class="form-label">Username</label>
                <input type="text" name="email_smtp_username" class="form-control" value="{{ old('email_smtp_username', $settings['email_smtp_username']) }}">
            </div>
            <div class="col-md-2 email-smtp-only d-none">
                <label class="form-label">Password</label>
                <input type="password" name="email_smtp_password" class="form-control" placeholder="{{ $settings['email_smtp_password_masked'] ?: 'Password SMTP' }}">
            </div>

            <div class="col-md-12">
                <label class="form-label">Subjek Email Tagihan</label>
                <input type="text" name="email_invoice_subject" class="form-control" value="{{ old('email_invoice_subject', $settings['email_invoice_subject']) }}">
            </div>
            <div class="col-md-12">
                <label class="form-label">Template Email Tagihan</label>
                <textarea name="email_invoice_template" rows="7" class="form-control" placeholder="Opsional, kosongkan untuk template default">{{ old('email_invoice_template', $settings['email_invoice_template']) }}</textarea>
                <small class="text-muted">
                    Placeholder: <code>{mitra_nama}</code>, <code>{nomor_tagihan}</code>, <code>{nomor_va}</code>, <code>{total}</code>, <code>{tanggal_publish}</code>, <code>{jatuh_tempo}</code>, <code>{link_invoice}</code>, <code>{email_login}</code>, <code>{password_login}</code>.
                </small>
            </div>
        </div>
    </div>
    <div class="card-footer bg-light d-flex justify-content-end gap-2 p-3">
        <button class="btn btn-primary fw-bold px-4"><i class="bi bi-save me-1"></i>Simpan Pengaturan</button>
    </div>
</form>

<div class="row g-4">
    <div class="col-lg-5">
        <div class="integration-card h-100">
            <div class="integration-card-header">
                <div class="integration-title">
                    <span class="integration-icon"><i class="bi bi-send-check"></i></span>
                    <div>
                        <h6 class="mb-0 fw-black text-primary">Test WhatsApp</h6>
                        <small class="text-muted fw-semibold">Kirim pesan percobaan.</small>
                    </div>
                </div>
            </div>
            <form method="POST" action="{{ route('jasa.integrasi.whatsapp.test') }}" class="card-body p-4">
                @csrf
                <label class="form-label">Nomor Tujuan</label>
                <input type="text" name="target" class="form-control mb-3" placeholder="62812xxxx">
                <label class="form-label">Pesan</label>
                <textarea name="message" rows="4" class="form-control mb-3">Test notifikasi WhatsApp SIKEREN-BLU.</textarea>
                <button class="btn btn-outline-primary fw-bold"><i class="bi bi-send me-1"></i>Kirim Test</button>
            </form>
        </div>
        <div class="integration-card mt-4">
            <div class="integration-card-header">
                <div class="integration-title">
                    <span class="integration-icon"><i class="bi bi-envelope-check"></i></span>
                    <div>
                        <h6 class="mb-0 fw-black text-primary">Test Email</h6>
                        <small class="text-muted fw-semibold">Kirim email percobaan.</small>
                    </div>
                </div>
            </div>
            <form method="POST" action="{{ route('jasa.integrasi.email.test') }}" class="card-body p-4">
                @csrf
                <label class="form-label">Email Tujuan</label>
                <input type="email" name="target" class="form-control mb-3" placeholder="mitra@example.com">
                <label class="form-label">Pesan</label>
                <textarea name="message" rows="4" class="form-control mb-3">Test notifikasi email SIKEREN-BLU.</textarea>
                <button class="btn btn-outline-primary fw-bold"><i class="bi bi-envelope-paper me-1"></i>Kirim Test</button>
            </form>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="integration-card h-100">
            <div class="integration-card-header">
                <div class="integration-title">
                    <span class="integration-icon"><i class="bi bi-clock-history"></i></span>
                    <div>
                        <h6 class="mb-0 fw-black text-primary">Log Integrasi Terbaru</h6>
                        <small class="text-muted fw-semibold">Aktivitas VA BTN, WhatsApp, dan email.</small>
                    </div>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Waktu</th>
                            <th>Provider</th>
                            <th>Aksi</th>
                            <th>Status</th>
                            <th>Pesan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($logs as $log)
                            <tr>
                                <td class="small text-muted">{{ $log->created_at->format('d/m/Y H:i') }}</td>
                                <td class="fw-bold text-uppercase">{{ $log->provider }}</td>
                                <td>{{ str_replace('_', ' ', $log->action) }}</td>
                                <td><span class="badge bg-{{ $log->status === 'success' ? 'success' : ($log->status === 'mock' ? 'info' : 'secondary') }}">{{ $log->status }}</span></td>
                                <td class="small text-muted">{{ \Illuminate\Support\Str::limit($log->message ?: '-', 60) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-4">Belum ada log integrasi.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('script')
<script>
    (function () {
        const sel = document.getElementById('waProvider');
        if (! sel) return;

        const fonnteFields = document.querySelectorAll('.wa-fonnte-only');
        const gatewayFields = document.querySelectorAll('.wa-gateway-only');

        const toggle = () => {
            const v = sel.value;
            fonnteFields.forEach(el => el.classList.toggle('d-none', v !== 'fonnte'));
            gatewayFields.forEach(el => el.classList.toggle('d-none', v !== 'wa_gateway'));
        };

        sel.addEventListener('change', toggle);
        toggle();
    })();
    (function () {
        const sel = document.getElementById('emailMailer');
        if (! sel) return;

        const smtpFields = document.querySelectorAll('.email-smtp-only');

        const toggle = () => {
            smtpFields.forEach(el => el.classList.toggle('d-none', sel.value !== 'smtp'));
        };

        sel.addEventListener('change', toggle);
        toggle();
    })();
</script>
@endpush
