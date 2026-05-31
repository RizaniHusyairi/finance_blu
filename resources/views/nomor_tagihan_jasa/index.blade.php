@extends('layouts.app')
@section('title', 'Nomor Tagihan Jasa')

@php
    use Illuminate\Support\Str;

    // Ambil 4 digit terakhir (nomor urut) dari nomor tagihan.
    $ambilUrut = function (?string $nomor): string {
        $nomor = (string) $nomor;
        return preg_match('/(\d{4})$/', $nomor, $m) ? $m[1] : '-';
    };

    // Warna badge status tagihan (konsisten dengan daftar tagihan jasa).
    $statusBadge = function (?string $status): string {
        return match ($status) {
            'PUBLISHED', 'LUNAS', 'DISETUJUI' => 'bg-success',
            'DRAFT' => 'bg-secondary',
            'DITOLAK' => 'bg-danger',
            default => 'bg-warning text-dark',
        };
    };
@endphp

@push('css')
<style>
    .nt-hero {
        position: relative; overflow: hidden; border-radius: 1.4rem; color: #fff;
        padding: 1.7rem 1.9rem; margin-bottom: 1.3rem;
        background: linear-gradient(125deg,#4338ca 0%,#6366f1 50%,#8b5cf6 100%);
        box-shadow: 0 20px 45px -22px rgba(99,102,241,.8);
    }
    .nt-hero::after { content:""; position:absolute; right:-70px; top:-90px; width:260px; height:260px; border-radius:50%; background:radial-gradient(circle,rgba(255,255,255,.18),transparent 70%); }
    .nt-hero h4 { color:#fff; font-weight:800; }
    .nt-hero p { color: rgba(255,255,255,.88); margin:0; max-width:64ch; }
    .nt-hero .nt-ic { width:54px;height:54px;border-radius:1rem;display:grid;place-items:center;font-size:1.6rem;background:rgba(255,255,255,.18);backdrop-filter:blur(6px); }

    .nt-card { border:none; border-radius:1rem; overflow:hidden; box-shadow:0 2px 12px rgba(0,0,0,.06); }
    .nt-card-header { background:linear-gradient(135deg,#6366f1,#4338ca); padding:1.1rem 1.4rem; display:flex; align-items:center; gap:.7rem; }
    .nt-card-header .ic { width:38px;height:38px;border-radius:50%;background:rgba(255,255,255,.18);display:grid;place-items:center;color:#fff;font-size:1.1rem; }
    .nt-card-header h6 { margin:0;color:#fff;font-weight:700;font-size:.95rem; }
    .nt-card-header span { color:rgba(255,255,255,.78); font-size:.78rem; }

    .nt-urut-badge { font-family:ui-monospace,SFMono-Regular,Menlo,monospace; font-weight:800; background:#eef2ff; color:#4338ca; padding:.25rem .6rem; border-radius:.5rem; }
    .nt-creator { display:flex; align-items:center; gap:.55rem; }
    .nt-avatar { width:34px;height:34px;border-radius:50%;display:grid;place-items:center;font-weight:800;font-size:.8rem;color:#fff;background:linear-gradient(135deg,#6366f1,#a855f7);flex-shrink:0; }
</style>
@endpush

@section('content')
    <div class="nt-hero">
        <div class="d-flex align-items-center gap-3 position-relative" style="z-index:2;">
            <div class="nt-ic"><i class="bi bi-hash"></i></div>
            <div>
                <h4 class="mb-1">Nomor Tagihan Jasa</h4>
                <p>Tentukan nomor urut awal penomoran tagihan jasa, serta pantau seluruh nomor tagihan beserta pembuat dan file nota-nya.</p>
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

    {{-- Ringkasan + Setting nomor awal --}}
    <div class="row g-3 mb-4">
        <div class="col-lg-8">
            <div class="card nt-card h-100">
                <div class="nt-card-header">
                    <div class="ic"><i class="bi bi-123"></i></div>
                    <div>
                        <h6>Nomor Urut Awal</h6>
                        <span>Tagihan baru akan mulai dari nomor ini (atau lebih tinggi bila sudah ada nomor lebih besar pada periode yang sama).</span>
                    </div>
                </div>
                <div class="card-body p-4">
                    <form method="POST" action="{{ route('nomor-tagihan-jasa.nomor-awal') }}" class="row g-3 align-items-end">
                        @csrf
                        <div class="col-md-5">
                            <label class="form-label fw-semibold small text-uppercase">Mulai Nomor Urut Dari</label>
                            <input type="number" name="nomor_urut_awal" class="form-control form-control-lg fw-bold font-monospace text-primary"
                                   value="{{ old('nomor_urut_awal', $nomorUrutAwal) }}" min="1" max="9999" required>
                            <div class="form-text">Saat ini: <strong>{{ str_pad((string) $nomorUrutAwal, 4, '0', STR_PAD_LEFT) }}</strong>. Format nomor: satkermakbulantahun<strong>urut</strong>.</div>
                        </div>
                        <div class="col-md-4">
                            <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i> Simpan Nomor Awal</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card rounded-4 h-100 shadow-sm border-0">
                <div class="card-body p-4 d-flex flex-column justify-content-center">
                    <div class="d-flex justify-content-between mb-3">
                        <span class="text-muted">Total Tagihan</span>
                        <span class="fw-bold fs-5">{{ number_format($summary['total']) }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-3">
                        <span class="text-muted">Dibuat Bulan Ini</span>
                        <span class="fw-bold fs-5 text-primary">{{ number_format($summary['bulan_ini']) }}</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-muted">Nomor Urut Awal</span>
                        <span class="fw-bold fs-5 text-success font-monospace">{{ str_pad((string) $summary['nomor_awal'], 4, '0', STR_PAD_LEFT) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filter --}}
    <div class="card shadow-sm border-0 rounded-4 mb-4">
        <div class="card-body p-4">
            <form method="GET" action="{{ route('nomor-tagihan-jasa.index') }}" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Cari Nomor Tagihan</label>
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Nomor tagihan / surat pengantar">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Tipe PNBP</label>
                    <select name="tipe_pnbp" class="form-select">
                        <option value="">Semua</option>
                        <option value="FUNGSI" @selected(request('tipe_pnbp')==='FUNGSI')>Fungsi</option>
                        <option value="NON_FUNGSI" @selected(request('tipe_pnbp')==='NON_FUNGSI')>Non Fungsi</option>
                        <option value="KONSESI" @selected(request('tipe_pnbp')==='KONSESI')>Konsesi</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-funnel me-1"></i> Filter</button>
                        <a href="{{ route('nomor-tagihan-jasa.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-counterclockwise me-1"></i> Reset</a>
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
                            <th width="22%">Nomor Tagihan</th>
                            <th width="8%" class="text-center">Urut</th>
                            <th width="16%">Mitra</th>
                            <th width="12%">Status</th>
                            <th width="16%">Dibuat Oleh</th>
                            <th width="11%">Tanggal</th>
                            <th width="10%" class="text-center">Nota / File</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($tagihans as $tagihan)
                            @php
                                $creator = $tagihan->creator;
                                $creatorName = $creator?->name ?? '—';
                                $inisial = collect(explode(' ', trim($creatorName)))->filter()->take(2)
                                    ->map(fn($w) => mb_strtoupper(mb_substr($w,0,1)))->implode('') ?: '?';
                                $mitraName = $tagihan->mitra?->nama_mitra ?? $tagihan->mitraLegacy?->nama_pihak ?? '—';
                            @endphp
                            <tr>
                                <td class="text-center">{{ $tagihans->firstItem() + $loop->index }}</td>
                                <td>
                                    <div class="fw-bold font-monospace text-primary">{{ $tagihan->nomor_tagihan }}</div>
                                    @if($tagihan->nomor_surat_pengantar)
                                        <div class="small text-muted">SP: {{ $tagihan->nomor_surat_pengantar }}</div>
                                    @endif
                                </td>
                                <td class="text-center"><span class="nt-urut-badge">{{ $ambilUrut($tagihan->nomor_tagihan) }}</span></td>
                                <td>{{ $mitraName }}</td>
                                <td>
                                    <span class="badge {{ $statusBadge($tagihan->status) }}">{{ str_replace('_', ' ', (string) ($tagihan->status ?? '—')) }}</span>
                                    @if($tagihan->status_pembayaran)
                                        <div class="small text-muted mt-1">Bayar: {{ ucfirst(strtolower((string) $tagihan->status_pembayaran)) }}</div>
                                    @endif
                                </td>
                                <td>
                                    <div class="nt-creator">
                                        <span class="nt-avatar">{{ $inisial }}</span>
                                        <div>
                                            <div class="fw-semibold">{{ $creatorName }}</div>
                                            <div class="small text-muted">{{ $creator?->email ?? '' }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="small">{{ optional($tagihan->tanggal_tagihan)->translatedFormat('d M Y') ?? '-' }}</div>
                                    <div class="small text-muted">Dibuat {{ optional($tagihan->created_at)->translatedFormat('d M Y H:i') }}</div>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group">
                                        <a href="{{ route('tagihan-jasa.pdf', $tagihan->id) }}" target="_blank" class="btn btn-sm btn-outline-primary" title="Lihat Nota / Invoice">
                                            <i class="bi bi-file-earmark-text"></i>
                                        </a>
                                        @if($tagihan->file_surat_pengantar_final)
                                            <a href="{{ route('tagihan-jasa.surat-pengantar-final.view', $tagihan->id) }}" target="_blank" class="btn btn-sm btn-outline-success" title="Surat Pengantar Final (TTD)">
                                                <i class="bi bi-patch-check"></i>
                                            </a>
                                        @endif
                                        <a href="{{ route('tagihan-jasa.show', $tagihan->id) }}" class="btn btn-sm btn-outline-secondary" title="Detail Tagihan">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center py-5 text-muted">Belum ada tagihan jasa pada filter ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($tagihans->hasPages())
                <div class="mt-4 d-flex justify-content-end">{{ $tagihans->withQueryString()->links() }}</div>
            @endif
        </div>
    </div>
@endsection
