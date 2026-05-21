@extends('layouts.app')
@section('title', $page['title'])

@push('css')
    <script>
        window.tailwind = window.tailwind || {};
        window.tailwind.config = { corePlugins: { preflight: false } };
    </script>
    <script src="https://cdn.tailwindcss.com"></script>
@endpush

@section('content')
@php
    $rupiah = fn ($value) => 'Rp ' . number_format((float) $value, 0, ',', '.');
    $tanggal = fn ($value) => $value ? \Carbon\Carbon::parse($value)->format('d/m/Y') : '-';
    $badge = function ($tagihan) {
        return match ($tagihan->status_jatuh_tempo) {
            'LEWAT_JATUH_TEMPO' => ['Lewat Jatuh Tempo', 'bg-danger'],
            'JATUH_TEMPO_HARI_INI' => ['Jatuh Tempo Hari Ini', 'bg-dark'],
            'MENDEKATI_JATUH_TEMPO' => ['Mendekati Jatuh Tempo', 'bg-warning text-dark'],
            'NORMAL' => ['Normal', 'bg-success'],
            'LUNAS' => ['Lunas', 'bg-success'],
            default => ['Belum Diset', 'bg-secondary'],
        };
    };
@endphp

<style>
    @keyframes ajCardIn {
        from { opacity: 0; transform: translateY(14px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .aj-card {
        border: 1px solid #dbeafe;
        border-radius: 1.25rem;
        background: #fff;
        box-shadow: 0 16px 42px rgba(37, 99, 235, .08);
        animation: ajCardIn .45s cubic-bezier(.2,.8,.2,1) both;
        transition: transform .22s ease, box-shadow .22s ease, border-color .22s ease;
    }
    .aj-card:hover {
        transform: translateY(-3px);
        border-color: #93c5fd;
        box-shadow: 0 22px 56px rgba(37, 99, 235, .13);
    }
    .aj-filter :is(.form-control, .form-select) {
        border-color: #dbeafe;
        border-radius: .85rem;
        box-shadow: 0 1px 2px rgba(15, 23, 42, .03);
    }
    .aj-filter :is(.form-control:focus, .form-select:focus) {
        border-color: #3b82f6;
        box-shadow: 0 0 0 4px rgba(59, 130, 246, .12);
    }
    .aj-stat-label {
        color: #2563eb;
        font-size: .78rem;
        font-weight: 900;
        letter-spacing: .02em;
        text-transform: uppercase;
    }
    .aj-stat-value {
        color: #0f172a;
        font-size: 1.7rem;
        font-weight: 900;
        margin-top: .35rem;
    }
    .modern-table { width: 100%; border-collapse: collapse; margin-bottom: 0; }
    .modern-table th { background-color: #eff6ff; color: #1d4ed8; text-transform: uppercase; font-size: 0.74rem; font-weight: 900; letter-spacing: 0.04em; padding: 1rem 1.25rem; border-bottom: 1px solid #bfdbfe; text-align: left; white-space: nowrap; }
    .modern-table td { padding: 1rem 1.25rem; vertical-align: middle; font-size: 0.875rem; color: #334155; border-bottom: 1px solid #eaf2ff; transition: all 0.2s ease; }
    .modern-table tbody tr { background-color: #ffffff; transition: all 0.2s ease; }
    .modern-table tbody tr:hover { background-color: #f0f7ff; transform: scale(1.002); box-shadow: 0 8px 18px rgba(37,99,235,.08); z-index: 10; position: relative; }
    .modern-table tbody tr:hover td { color: #0f172a; font-weight: 500; }
</style>

<div class="tw-scope">
<div class="mb-4 overflow-hidden rounded-3xl border border-blue-100 bg-gradient-to-r from-blue-50 via-sky-50 to-blue-50 px-5 py-5 shadow-[0_18px_50px_rgba(37,99,235,.10)] sm:px-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div class="flex items-start gap-3">
            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-blue-600 text-white shadow-lg shadow-blue-600/20">
                <i class="bi {{ $page['mode'] === 'log' ? 'bi-clock-history' : 'bi-alarm' }} text-xl"></i>
            </div>
            <div>
                <h3 class="mb-1 text-xl font-black text-slate-900 lg:text-2xl">{{ $page['title'] }}</h3>
                <p class="mb-0 text-sm font-medium text-slate-500">{{ $page['subtitle'] }}</p>
            </div>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('tagihan-jasa.create') }}" class="inline-flex items-center rounded-xl bg-blue-600 px-4 py-2 text-sm font-bold text-white shadow-lg shadow-blue-600/20 transition hover:-translate-y-0.5 hover:bg-blue-700">
                <i class="bi bi-plus-lg me-2"></i>Buat Tagihan
            </a>
            <a href="{{ route('tagihan-jasa.create', ['mode' => 'konsesi']) }}" class="inline-flex items-center rounded-xl border border-blue-200 bg-white px-4 py-2 text-sm font-bold text-blue-700 shadow-sm transition hover:-translate-y-0.5 hover:bg-blue-50">
                <i class="bi bi-sliders me-2"></i>Atur Layanan Konsesi
            </a>
        </div>
    </div>
</div>

<div class="card aj-card mb-4">
    <div class="border-bottom border-primary-subtle bg-gradient-to-r from-blue-50 via-sky-50 to-white px-3 py-2">
        <div class="d-flex align-items-center gap-2">
            <span class="d-inline-flex align-items-center justify-content-center rounded-3 bg-primary text-white shadow-sm" style="width:34px;height:34px;"><i class="bi bi-funnel"></i></span>
            <div>
                <div class="fw-bold" style="color:#1e3a8a;">Filter Tagihan</div>
                <div class="small fw-semibold text-muted">Saring data berdasarkan periode, mitra, layanan, dan status.</div>
            </div>
        </div>
    </div>
    <div class="card-body">
        <form method="GET" class="row g-3 aj-filter">
            <div class="col-md-2">
                <label class="form-label small fw-bold">Bulan</label>
                <select name="month" class="form-select form-select-sm">
                    @for($m = 1; $m <= 12; $m++)
                        <option value="{{ $m }}" {{ (int)($filters['month'] ?? now()->month) === $m ? 'selected' : '' }}>{{ \Carbon\Carbon::create()->month($m)->translatedFormat('F') }}</option>
                    @endfor
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-bold">Tahun</label>
                <input type="number" name="year" value="{{ $filters['year'] ?? now()->year }}" class="form-control form-control-sm">
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-bold">Dari Tanggal</label>
                <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="form-control form-control-sm">
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-bold">Sampai Tanggal</label>
                <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="form-control form-control-sm">
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-bold">Mitra</label>
                <select name="mitra_jasa_id" class="form-select form-select-sm">
                    <option value="">Semua</option>
                    @foreach($filterOptions['mitras'] as $mitra)
                        <option value="{{ $mitra->id }}" {{ (string)($filters['mitra_jasa_id'] ?? '') === (string)$mitra->id ? 'selected' : '' }}>{{ $mitra->nama_mitra }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-bold">Layanan</label>
                <select name="layanan_jasa_id" class="form-select form-select-sm">
                    <option value="">Semua</option>
                    @foreach($filterOptions['layanans'] as $layanan)
                        <option value="{{ $layanan->id }}" {{ (string)($filters['layanan_jasa_id'] ?? '') === (string)$layanan->id ? 'selected' : '' }}>{{ $layanan->nama_layanan }}</option>
                    @endforeach
                </select>
            </div>
            @if($page['mode'] === 'log')
                <div class="col-md-2">
                    <label class="form-label small fw-bold">Status</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">Semua</option>
                        @foreach(['DRAFT','VERIFIKASI_KOORDINATOR','VERIFIKASI_KABANDARA','DITOLAK','PUBLISHED','LUNAS','BATAL'] as $status)
                            <option value="{{ $status }}" {{ ($filters['status'] ?? '') === $status ? 'selected' : '' }}>{{ str_replace('_', ' ', $status) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold">Pembayaran</label>
                    <select name="status_pembayaran" class="form-select form-select-sm">
                        <option value="">Semua</option>
                        <option value="belum_dibayar" {{ ($filters['status_pembayaran'] ?? '') === 'belum_dibayar' ? 'selected' : '' }}>Belum Dibayar</option>
                        <option value="sebagian" {{ ($filters['status_pembayaran'] ?? '') === 'sebagian' ? 'selected' : '' }}>Sebagian</option>
                        <option value="lunas" {{ ($filters['status_pembayaran'] ?? '') === 'lunas' ? 'selected' : '' }}>Lunas</option>
                    </select>
                </div>
            @endif
            <div class="col-12 d-flex gap-2">
                <button class="btn btn-primary btn-sm fw-bold px-3"><i class="bi bi-check2-circle me-1"></i>Terapkan Filter</button>
                <a href="{{ url()->current() }}" class="btn btn-light border btn-sm fw-bold px-3"><i class="bi bi-arrow-counterclockwise me-1"></i>Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="card aj-card h-100 overflow-hidden">
            <div class="h-2 bg-blue-600"></div>
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="aj-stat-label">Total Tagihan</div>
                        <div class="aj-stat-value">{{ number_format($summary['count'], 0, ',', '.') }}</div>
                    </div>
                    <div class="d-flex align-items-center justify-content-center rounded-4 bg-blue-50 text-primary fs-1" style="width:62px;height:62px;"><i class="bi bi-file-earmark-text"></i></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card aj-card h-100 overflow-hidden">
            <div class="h-2 bg-sky-500"></div>
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="aj-stat-label">Nominal Tagihan</div>
                        <div class="aj-stat-value">{{ $rupiah($summary['nominal']) }}</div>
                    </div>
                    <div class="d-flex align-items-center justify-content-center rounded-4 bg-blue-50 text-primary fs-1" style="width:62px;height:62px;"><i class="bi bi-cash-coin"></i></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card aj-card overflow-hidden">
    <div class="border-bottom border-primary-subtle bg-gradient-to-r from-blue-50 via-sky-50 to-white px-3 py-2">
        <div class="d-flex align-items-center gap-2">
            <span class="d-inline-flex align-items-center justify-content-center rounded-3 bg-primary text-white shadow-sm" style="width:34px;height:34px;"><i class="bi bi-table"></i></span>
            <div>
                <div class="fw-bold" style="color:#1e3a8a;">Daftar Tagihan</div>
                <div class="small fw-semibold text-muted">Riwayat tagihan sesuai filter yang dipilih.</div>
            </div>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table modern-table">
            <thead>
                <tr>
                    <th>No</th>
                    <th>No Tagihan</th>
                    <th>Mitra Jasa</th>
                    <th>Tanggal</th>
                    <th>Jatuh Tempo</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Pembayaran</th>
                    <th>Badge</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($tagihans as $tagihan)
                    @php([$label, $class] = $badge($tagihan))
                    <tr>
                        <td>{{ $tagihans->firstItem() + $loop->index }}</td>
                        <td class="fw-semibold">{{ $tagihan->nomor_tagihan }}</td>
                        <td>{{ $tagihan->mitra->nama_mitra ?? '-' }}</td>
                        <td>{{ $tanggal($tagihan->tanggal_tagihan) }}</td>
                        <td>{{ $tanggal($tagihan->tanggal_jatuh_tempo) }}</td>
                        <td>{{ $rupiah($tagihan->total_tagihan) }}</td>
                        <td><span class="badge bg-secondary">{{ str_replace('_', ' ', $tagihan->status) }}</span></td>
                        <td><span class="badge bg-light text-dark border">{{ str_replace('_', ' ', $tagihan->status_pembayaran ?? 'belum_dibayar') }}</span></td>
                        <td><span class="badge {{ $class }}">{{ $label }}</span></td>
                        <td><a href="{{ route('tagihan-jasa.show', $tagihan->id) }}" class="btn btn-sm btn-light border fw-semibold text-primary"><i class="bi bi-eye me-1"></i>Detail</a></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="text-center text-muted py-4">{{ $page['empty'] }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($tagihans->hasPages())
        <div class="card-footer bg-white">
            {{ $tagihans->links() }}
        </div>
    @endif
</div>
</div>
@endsection
