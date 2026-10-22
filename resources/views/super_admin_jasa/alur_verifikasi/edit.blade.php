@extends('layouts.app')
@section('title', 'Alur Verifikasi Tagihan Jasa')

@push('css')
<style>
    .av-hero { border-radius: 20px; padding: 24px 26px; color: #fff; background: linear-gradient(120deg, #0b1f4d 0%, #1d4ed8 60%, #2563eb 100%); box-shadow: 0 18px 44px rgba(29,78,216,.25); }
    .av-hero p { color: rgba(255,255,255,.8); }
    .av-opsi { position: relative; display: block; height: 100%; border: 2px solid #e2e8f0; border-radius: 18px; background: #fff; padding: 20px 22px; cursor: pointer; transition: border-color .15s ease, box-shadow .15s ease; }
    .av-opsi:hover { border-color: #93c5fd; }
    .av-opsi:has(input:checked) { border-color: #1d4ed8; box-shadow: 0 14px 34px rgba(29,78,216,.14); }
    .av-opsi input { position: absolute; top: 20px; right: 20px; width: 20px; height: 20px; }
    .av-opsi h6 { font-weight: 800; color: #0f172a; margin: 0 30px 4px 0; }
    .av-opsi .av-sub { font-size: .82rem; color: #64748b; margin-bottom: 14px; }
    .av-langkah { list-style: none; margin: 0; padding: 0; display: grid; gap: 8px; }
    .av-langkah li { display: flex; align-items: center; gap: 10px; font-size: .88rem; font-weight: 600; color: #1e293b; }
    .av-langkah .n { width: 26px; height: 26px; flex-shrink: 0; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: .75rem; font-weight: 800; color: #1d4ed8; background: #dbeafe; }
    .av-langkah li.akhir .n { color: #fff; background: #16a34a; }
    .av-langkah li.akhir { color: #166534; }
    .av-catatan { border-radius: 14px; background: #f8fafc; border: 1px solid #e2e8f0; padding: 14px 16px; font-size: .86rem; color: #334155; }
    .av-badge { font-size: .7rem; font-weight: 800; border-radius: 999px; padding: 3px 9px; background: #dcfce7; color: #166534; }
</style>
@endpush

@section('content')
<div class="av-hero mb-4">
    <div class="small fw-bold text-uppercase mb-1" style="letter-spacing:.06em;color:#bfdbfe">Pengaturan Super Admin</div>
    <h4 class="fw-bold text-white mb-1">Alur Verifikasi Tagihan Jasa</h4>
    <p class="mb-0">Pilih alur verifikasi untuk tagihan <strong class="text-white">layanan aero</strong> (Tarif Jasa Kebandarudaraan domestik &amp; internasional). Tagihan non-aero atau campuran selalu memakai Alur Kabandara.</p>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<form method="POST" action="{{ route('jasa.alur-verifikasi.update') }}">
    @csrf
    @method('PUT')
    <div class="row g-4 mb-4">
        <div class="col-lg-6">
            <label class="av-opsi">
                <input type="radio" name="alur_aero" value="KABANDARA" @checked($alurAktif === 'KABANDARA')>
                <h6>Alur 1 — Kabandara</h6>
                <div class="av-sub">Persetujuan akhir dan TTE oleh Kepala Bandar Udara (KPA) atau PLT/PLH. Tagihan terbit otomatis.</div>
                <ol class="av-langkah">
                    <li><span class="n">1</span>Koordinator Jasa</li>
                    <li><span class="n">2</span>Kepala Seksi Pelayanan dan Kerjasama</li>
                    <li><span class="n">3</span>Kepala Subbagian Keuangan dan TU</li>
                    <li><span class="n">4</span>KPA / PLT-PLH — setujui &amp; TTE</li>
                    <li class="akhir"><span class="n"><i class="bi bi-send"></i></span>Terbit otomatis ke mitra</li>
                </ol>
                @if($berjalan['KABANDARA'] ?? 0)<div class="mt-3"><span class="av-badge">{{ $berjalan['KABANDARA'] }} tagihan sedang berjalan</span></div>@endif
            </label>
        </div>
        <div class="col-lg-6">
            <label class="av-opsi">
                <input type="radio" name="alur_aero" value="KASI_JASA" @checked($alurAktif === 'KASI_JASA')>
                <h6>Alur 2 — Kasi Jasa</h6>
                <div class="av-sub">Setelah Kasubag TU menyetujui, surat pengantar otomatis ber-TTE Kasi Jasa dan tagihan kembali ke Kasi Jasa untuk diterbitkan.</div>
                <ol class="av-langkah">
                    <li><span class="n">1</span>Koordinator Jasa</li>
                    <li><span class="n">2</span>Kepala Seksi Pelayanan dan Kerjasama</li>
                    <li><span class="n">3</span>Kepala Subbagian Keuangan dan TU</li>
                    <li><span class="n"><i class="bi bi-pen"></i></span>Surat otomatis ber-TTE Kasi Jasa</li>
                    <li class="akhir"><span class="n"><i class="bi bi-send"></i></span>Kasi Jasa menerbitkan ke mitra</li>
                </ol>
                @if($berjalan['KASI_JASA'] ?? 0)<div class="mt-3"><span class="av-badge">{{ $berjalan['KASI_JASA'] }} tagihan sedang berjalan</span></div>@endif
            </label>
        </div>
    </div>

    <div class="av-catatan mb-4">
        <i class="bi bi-info-circle text-primary me-1"></i>
        Perubahan berlaku untuk tagihan yang <strong>diajukan setelah disimpan</strong> (termasuk pengajuan ulang setelah revisi). Tagihan yang sedang diverifikasi tetap memakai alurnya masing-masing.
    </div>

    <button type="submit" class="btn btn-primary fw-bold px-4"><i class="bi bi-save me-1"></i>Simpan Pengaturan</button>
</form>
@endsection
