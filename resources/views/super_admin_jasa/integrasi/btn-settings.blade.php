@php
    $btnGroups = [
        [
            'title' => '1. Koneksi ke BTN',
            'help' => 'Isi sekali saat penyiapan menggunakan data yang diberikan BTN untuk lingkungan yang dipilih.',
            'fields' => ['base_url', 'client_id', 'partner_id', 'client_secret', 'partner_service_id', 'channel_id', 'origin', 'private_key'],
        ],
        [
            'title' => '2. Callback dari BTN',
            'help' => 'Untuk menerima pengecekan tagihan dan pemberitahuan pembayaran dari BTN.',
            'fields' => ['inbound_partner_id', 'inbound_auth', 'bank_public_key'],
        ],
        [
            'title' => 'Pengaturan tambahan',
            'help' => 'Ubah hanya jika diperlukan sesuai kesepakatan dengan BTN.',
            'fields' => ['current_account_no', 'trx_type'],
        ],
        [
            'title' => 'Simulasi dan arsip',
            'help' => 'Prefix dan secret berikut hanya untuk simulasi. Merchant lama disimpan sebagai arsip.',
            'fields' => ['va_prefix', 'callback_secret', 'merchant_id'],
        ],
    ];
    $btnFields = array_replace_recursive(\App\Services\Btn\BtnSnapSettings::FIELDS, [
        'base_url' => ['label' => 'Alamat API BTN', 'type' => 'url', 'placeholder' => 'https://devapi.btn.co.id'],
        'client_id' => ['label' => 'Client ID dari BTN', 'help' => 'OAuth ID / X-CLIENT-KEY.'],
        'partner_id' => ['label' => 'Partner ID dari BTN', 'help' => 'API Key ID / X-PARTNER-ID untuk mengakses API BTN.'],
        'client_secret' => ['label' => 'API Key Secret dari BTN', 'type' => 'password'],
        'origin' => ['help' => 'Domain aplikasi yang didaftarkan ke BTN.'],
        'private_key' => ['help' => 'Private key milik aplikasi dari pasangan RSA yang Anda buat.'],
        'inbound_partner_id' => ['label' => 'Partner ID callback SIKEREN', 'help' => 'ID yang Anda buat dan kirim ke BTN, berbeda dari Partner ID pemberian BTN.'],
        'inbound_auth' => ['label' => 'Keamanan callback'],
        'bank_public_key' => ['help' => 'Public key yang diberikan BTN untuk memverifikasi callback.'],
        'va_prefix' => ['label' => 'Prefix VA simulasi'],
        'callback_secret' => ['label' => 'Callback Secret simulasi', 'type' => 'password'],
        'merchant_id' => ['label' => 'Merchant lama (arsip)'],
    ]);
@endphp

<div class="card-body p-4">
    <p class="small text-muted mb-3">Aktifkan untuk membuat VA otomatis saat tagihan dipublikasikan. Jika nonaktif, Admin Jasa mengisi nomor VA secara manual.</p>
    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <label class="form-label" for="btn_mode">Lingkungan pembayaran</label>
            <select name="btn_mode" id="btn_mode" class="form-select">
                @foreach(['mock' => 'Simulasi lokal', 'sandbox' => 'Pengujian bersama BTN (Sandbox)', 'production' => 'Transaksi nyata (Production)'] as $value => $label)
                    <option value="{{ $value }}" @selected(old('btn_mode', $settings['btn_mode']) === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <div class="form-text">Simulasi tidak terhubung ke bank. Sandbox dan Production memerlukan konfigurasi BTN.</div>
        </div>
        <div class="col-md-6">
            <label class="form-label" for="btn_va_expiry_days">Masa berlaku VA (hari)</label>
            <input type="number" min="1" max="365" name="btn_va_expiry_days" id="btn_va_expiry_days" class="form-control" value="{{ old('btn_va_expiry_days', $settings['btn_va_expiry_days']) }}">
        </div>
    </div>
    <p class="small text-muted">Buka bagian berikut saat penyiapan awal atau jika ada perubahan dari BTN. Kunci dan secret yang sudah tersimpan tidak perlu diisi ulang.</p>

    @foreach($btnGroups as $group)
        @php
            $groupHasErrors = collect($group['fields'])->contains(fn ($key) => $errors->has('btn_'.$key));
        @endphp
        <details class="btn-settings-group mb-3" @if($groupHasErrors) open @endif>
            <summary class="fw-bold text-primary">{{ $group['title'] }} @if($groupHasErrors)<span class="text-danger small">— Periksa isian</span>@endif</summary>
            <div class="p-3 border-top">
                <p class="small text-muted">{{ $group['help'] }}</p>
                <div class="row g-3">
                    @foreach($group['fields'] as $key)
                        @php
                            $field = $btnFields[$key];
                            $name = 'btn_'.$key;
                        @endphp
                        <div class="col-md-6">
                            <label class="form-label" for="{{ $name }}">{{ $field['label'] }}</label>
                            @if($key === 'inbound_auth')
                                <select class="form-select" name="{{ $name }}" id="{{ $name }}">
                                    <option value="disabled" @selected(old($name, $settings[$name]) === 'disabled')>Nonaktif</option>
                                    <option value="rsa" @selected(old($name, $settings[$name]) === 'rsa')>Signature RSA tanpa token</option>
                                </select>
                                <div class="form-text">Gunakan skema yang telah disepakati dengan BTN.</div>
                            @elseif($field['secret'] ?? false)
                                <textarea class="form-control font-monospace" name="{{ $name }}" id="{{ $name }}" rows="4" autocomplete="off" spellcheck="false" placeholder="{{ $settings[$name] ? 'Key tersimpan. Kosongkan untuk mempertahankan.' : 'Tempel key PEM di sini' }}"></textarea>
                                <div class="form-text">Disimpan terenkripsi. Kosongkan jika tidak ingin mengganti.</div>
                            @elseif(($field['type'] ?? 'text') === 'password')
                                <input type="password" name="{{ $name }}" id="{{ $name }}" class="form-control" autocomplete="new-password" placeholder="{{ $settings[$name.'_masked'] ? 'Secret tersimpan' : 'Belum diisi' }}">
                                <div class="form-text">Kosongkan jika tidak ingin mengganti.</div>
                            @else
                                <input type="{{ $field['type'] ?? 'text' }}" class="form-control" name="{{ $name }}" id="{{ $name }}" value="{{ old($name, $settings[$name]) }}" placeholder="{{ $field['placeholder'] ?? '' }}">
                            @endif
                            @if(isset($field['help']))<div class="form-text">{{ $field['help'] }}</div>@endif
                            @error($name)<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </div>
                    @endforeach
                </div>
                @if(in_array('inbound_partner_id', $group['fields'], true))
                    <div class="integration-soft mt-3 small">
                        <strong>URL untuk didaftarkan ke BTN</strong>
                        <div class="mt-2">Inquiry: <code class="text-break">{{ url('/snap/v1/transfer-va/inquiry') }}</code></div>
                        <div>Payment: <code class="text-break">{{ url('/snap/v1/transfer-va/payment') }}</code></div>
                        <div class="mt-2">Salin dari aplikasi pada domain server yang akan digunakan untuk menerima callback.</div>
                    </div>
                @endif
            </div>
        </details>
    @endforeach
    <p class="small text-muted mb-0">SNAP BTN v2.04 · Selesaikan pengujian bersama BTN sebelum menggunakan mode Production.</p>
</div>
