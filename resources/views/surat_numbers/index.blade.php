@extends('layouts.app')
@section('title', 'Manajemen Nomor Surat KU')

@php
    $statusClass = [
        'AVAILABLE' => 'bg-success',
        'RESERVED' => 'bg-warning text-dark',
        'USED' => 'bg-primary',
        'CANCELLED' => 'bg-secondary',
    ];
@endphp

@push('css')
<style>
    .sn-hero {
        position: relative; overflow: hidden; border-radius: 1.4rem; color: #fff;
        padding: 1.7rem 1.9rem; margin-bottom: 1.3rem;
        background: linear-gradient(125deg,#0369a1 0%,#0ea5e9 50%,#06b6d4 100%);
        box-shadow: 0 20px 45px -22px rgba(14,165,233,.8);
    }
    .sn-hero::after { content:""; position:absolute; right:-70px; top:-90px; width:260px; height:260px; border-radius:50%; background:radial-gradient(circle,rgba(255,255,255,.18),transparent 70%); }
    .sn-hero h4 { color:#fff; font-weight:800; }
    .sn-hero p { color: rgba(255,255,255,.88); margin:0; max-width:64ch; }
    .sn-hero .sn-ic { width:54px;height:54px;border-radius:1rem;display:grid;place-items:center;font-size:1.6rem;background:rgba(255,255,255,.18);backdrop-filter:blur(6px); }

    .sn-next { display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:1rem; margin-bottom:1.3rem; }
    .sn-next-card { background:#fff; border:1px solid rgba(15,23,42,.07); border-radius:1.1rem; padding:1.1rem 1.2rem; box-shadow:0 12px 30px -26px rgba(15,23,42,.6); }
    .sn-next-card .lbl { font-size:.72rem; text-transform:uppercase; letter-spacing:.05em; font-weight:700; color:#64748b; }
    .sn-next-card .val { font-family:ui-monospace,SFMono-Regular,Menlo,monospace; font-weight:800; color:#0ea5e9; font-size:1.05rem; margin-top:.25rem; word-break:break-all; }

    .sn-card { border:none; border-radius:1rem; overflow:hidden; box-shadow:0 2px 12px rgba(0,0,0,.06); }
    .sn-card-header { background:linear-gradient(135deg,#0ea5e9,#0369a1); padding:1.1rem 1.4rem; display:flex; align-items:center; gap:.7rem; }
    .sn-card-header .ic { width:38px;height:38px;border-radius:50%;background:rgba(255,255,255,.18);display:grid;place-items:center;color:#fff;font-size:1.1rem; }
    .sn-card-header h6 { margin:0;color:#fff;font-weight:700;font-size:.95rem; }
    .sn-card-header span { color:rgba(255,255,255,.75); font-size:.78rem; }
</style>
@endpush

@section('content')
    <div class="sn-hero">
        <div class="d-flex align-items-center gap-3 position-relative" style="z-index:2;">
            <div class="sn-ic"><i class="bi bi-hash"></i></div>
            <div>
                <h4 class="mb-1">Manajemen Nomor Surat KU</h4>
                <p>Register terpusat nomor surat berawalan KU untuk Honorarium (KU.201), Perjalanan Dinas (KU.201), dan Surat Pengantar Penagihan Jasa (KU.102). Nomor urut 4 digit dijamin tidak sama lintas ketiga jenis surat dan ditentukan otomatis oleh sistem.</p>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success border-0 bg-success alert-dismissible fade show shadow-sm">
            <div class="text-white">{{ session('success') }}</div>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger border-0 bg-danger alert-dismissible fade show shadow-sm">
            <div class="text-white">{{ $errors->first() }}</div>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Preview nomor berikutnya (pool bersama) --}}
    <div class="sn-next">
        <div class="sn-next-card">
            <div class="lbl"><i class="bi bi-people me-1"></i>Berikutnya · Honorarium</div>
            <div class="val">{{ $nextPreview['KU_HONOR'] }}</div>
        </div>
        <div class="sn-next-card">
            <div class="lbl"><i class="bi bi-airplane me-1"></i>Berikutnya · Perjalanan Dinas</div>
            <div class="val">{{ $nextPreview['KU_PERJALDIN'] }}</div>
        </div>
        <div class="sn-next-card">
            <div class="lbl"><i class="bi bi-envelope-paper me-1"></i>Berikutnya · Surat Pengantar Jasa</div>
            <div class="val">{{ $nextPreview['KU_SURAT_PENGANTAR_JASA'] }}</div>
        </div>
    </div>

    {{-- Ringkasan --}}
    <div class="row row-cols-1 row-cols-md-2 row-cols-xl-4 g-3 mb-4">
        <div class="col"><div class="card rounded-4 h-100 shadow-sm border-0"><div class="card-body p-3 border-start border-4 border-primary rounded-4"><p class="mb-1 small text-muted">Total Nomor KU</p><h4 class="mb-0 fw-bold">{{ number_format($summary['total']) }}</h4></div></div></div>
        <div class="col"><div class="card rounded-4 h-100 shadow-sm border-0"><div class="card-body p-3 border-start border-4 border-info rounded-4"><p class="mb-1 small text-muted">Honorarium</p><h4 class="mb-0 fw-bold">{{ number_format($summary['honor']) }}</h4></div></div></div>
        <div class="col"><div class="card rounded-4 h-100 shadow-sm border-0"><div class="card-body p-3 border-start border-4 border-warning rounded-4"><p class="mb-1 small text-muted">Perjalanan Dinas</p><h4 class="mb-0 fw-bold">{{ number_format($summary['perjaldin']) }}</h4></div></div></div>
        <div class="col"><div class="card rounded-4 h-100 shadow-sm border-0"><div class="card-body p-3 border-start border-4 border-success rounded-4"><p class="mb-1 small text-muted">Surat Pengantar Jasa</p><h4 class="mb-0 fw-bold">{{ number_format($summary['jasa']) }}</h4></div></div></div>
    </div>

    {{-- Catat nomor eksternal --}}
    <div class="card sn-card mb-4">
        <div class="sn-card-header">
            <div class="ic"><i class="bi bi-bookmark-plus"></i></div>
            <div>
                <h6>Catat Nomor Surat Eksternal</h6>
                <span>Catat nomor KU yang sudah dipakai di luar sistem agar dilewati pada penomoran otomatis</span>
            </div>
        </div>
        <div class="card-body p-4">
            <form method="POST" action="{{ route('surat-numbers.store') }}" class="row g-3 align-items-end">
                @csrf
                <div class="col-lg-3 col-md-6">
                    <label class="form-label fw-semibold small text-uppercase">Jenis Surat</label>
                    <select name="document_key" class="form-select" required>
                        @foreach($documentKeys as $key)
                            <option value="{{ $key }}" @selected(old('document_key') === $key)>{{ $labels[$key] ?? $key }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-2 col-md-3">
                    <label class="form-label fw-semibold small text-uppercase">Tahun</label>
                    <input type="number" name="tahun" value="{{ request('tahun', now()->year) }}" class="form-control" min="2000" max="2100" required>
                </div>
                <div class="col-lg-2 col-md-3">
                    <label class="form-label fw-semibold small text-uppercase">Mulai (4 Digit)</label>
                    <input type="text" name="start_number" class="form-control" inputmode="numeric" maxlength="4" placeholder="0205" pattern="[0-9]{1,4}" required>
                </div>
                <div class="col-lg-1 col-md-3">
                    <label class="form-label fw-semibold small text-uppercase">Jumlah</label>
                    <input type="number" name="count" value="1" class="form-control" min="1" max="100" required>
                </div>
                <div class="col-lg-3 col-md-6">
                    <label class="form-label fw-semibold small text-uppercase">Catatan</label>
                    <input type="text" name="notes" class="form-control" placeholder="Mis: surat manual">
                </div>
                <div class="col-lg-1 col-md-3">
                    <button type="submit" class="btn btn-info text-white w-100"><i class="bi bi-lock"></i></button>
                </div>
            </form>
        </div>
    </div>

    {{-- Filter --}}
    <div class="card shadow-sm border-0 rounded-4 mb-4">
        <div class="card-body p-4">
            <form method="GET" action="{{ route('surat-numbers.index') }}" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Cari Nomor</label>
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="KU.201 atau 0205">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Jenis Surat</label>
                    <select name="document_key" class="form-select">
                        <option value="">Semua</option>
                        @foreach($documentKeys as $key)
                            <option value="{{ $key }}" @selected(request('document_key') === $key)>{{ $labels[$key] ?? $key }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold">Status</label>
                    <select name="status" class="form-select">
                        <option value="">Semua</option>
                        @foreach($statusOptions as $status)
                            <option value="{{ $status }}" @selected(request('status') === $status)>{{ $status }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold">Tahun</label>
                    <input type="number" name="tahun" value="{{ request('tahun') }}" class="form-control" min="2000" max="2100">
                </div>
                <div class="col-md-2">
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-funnel me-1"></i> Filter</button>
                        <a href="{{ route('surat-numbers.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-counterclockwise me-1"></i> Reset</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Tabel --}}
    <div class="card shadow-sm border-0 rounded-4">
        <div class="card-body p-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th width="5%" class="text-center">No</th>
                            <th width="18%">Jenis Surat</th>
                            <th width="27%">Nomor Surat</th>
                            <th width="10%" class="text-center">Status</th>
                            <th width="18%">Pemakaian</th>
                            <th width="14%">Catatan</th>
                            <th width="8%" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($numbers as $number)
                            <tr>
                                <td class="text-center">{{ $numbers->firstItem() + $loop->index }}</td>
                                <td>
                                    <div class="fw-bold">{{ $labels[$number->document_key] ?? str_replace('_',' ',$number->document_key) }}</div>
                                    <div class="small text-muted">{{ $number->series_prefix }}</div>
                                </td>
                                <td>
                                    <div class="fw-bold font-monospace text-primary">{{ $number->full_number }}</div>
                                    <div class="small text-muted">Urut: {{ str_pad((string) $number->running_number, $number->number_padding, '0', STR_PAD_LEFT) }} · {{ $number->tahun }}</div>
                                </td>
                                <td class="text-center"><span class="badge {{ $statusClass[$number->status] ?? 'bg-secondary' }}">{{ $number->status }}</span></td>
                                <td>
                                    @if($number->status === 'USED')
                                        <div class="fw-semibold">{{ $number->usedBy->name ?? '-' }}</div>
                                        <div class="small text-muted">{{ $number->usage_source === 'EXTERNAL' ? 'Eksternal' : 'Sistem' }} · {{ optional($number->used_at)->translatedFormat('d M Y H:i') }}</div>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="small text-muted">{{ $number->notes ?: '-' }}</td>
                                <td class="text-center">
                                    @if($number->status !== 'CANCELLED' && ! ($number->status === 'USED' && $number->usage_source === 'INTERNAL'))
                                        <form method="POST" action="{{ route('surat-numbers.cancel', $number) }}" onsubmit="return confirm('Batalkan nomor ini?');">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Batal</button>
                                        </form>
                                    @else
                                        <span class="text-muted small">Terkunci</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center py-5 text-muted">Belum ada nomor surat KU pada filter ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($numbers->hasPages())
                <div class="mt-4 d-flex justify-content-end">{{ $numbers->withQueryString()->links() }}</div>
            @endif
        </div>
    </div>
@endsection
