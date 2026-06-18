@extends('layouts.app')
@section('title', 'Klasifikasi Penerimaan')
@include('pembukuan.partials.styles')

@section('content')
<div class="container-fluid">
    <x-page-title title="Pembukuan" subtitle="Klasifikasi Penerimaan (Rekening Koran)" />

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Rekening Penerimaan</label>
                    <select name="rekening_bank_id" class="form-select form-select-sm">
                        <option value="">Semua Rekening</option>
                        @foreach($rekeningOptions as $r)
                            <option value="{{ $r->id }}" @selected((string)($filters['rekening_bank_id'] ?? '') === (string)$r->id)>{{ $r->nama_bank }} - {{ $r->nomor_rekening }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2"><label class="form-label small fw-semibold">Dari</label><input type="date" name="start_date" value="{{ $filters['start_date'] ?? '' }}" class="form-control form-control-sm"></div>
                <div class="col-md-2"><label class="form-label small fw-semibold">Sampai</label><input type="date" name="end_date" value="{{ $filters['end_date'] ?? '' }}" class="form-control form-control-sm"></div>
                <div class="col-md-4 d-flex gap-2">
                    <button class="btn btn-sm btn-primary"><i class="bi bi-funnel"></i> Filter</button>
                    @if($filters['rekening_bank_id'] ?? false)
                    <form method="POST" action="{{ route('pembukuan.klasifikasi.post-batch') }}" onsubmit="return confirm('Klasifikasi otomatis & posting semua baris ke BKU Penerimaan?');">
                        @csrf
                        <input type="hidden" name="rekening_bank_id" value="{{ $filters['rekening_bank_id'] }}">
                        <input type="hidden" name="start_date" value="{{ $filters['start_date'] ?? '' }}">
                        <input type="hidden" name="end_date" value="{{ $filters['end_date'] ?? '' }}">
                        <button class="btn btn-sm btn-success"><i class="bi bi-magic"></i> Klasifikasi &amp; Posting Massal</button>
                    </form>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header fw-bold"><i class="bi bi-tags text-primary"></i> Baris Rekening Koran</div>
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr><th>Tanggal</th><th>Deskripsi</th><th class="text-end">Masuk</th><th class="text-end">Keluar</th><th style="width:280px;">Akun Pendapatan</th><th class="text-center">BKU</th></tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                        <tr>
                            <td class="text-nowrap">{{ optional($row->tanggal_transaksi)->format('d/m/Y') }}</td>
                            <td><span title="{{ $row->deskripsi }}">{{ \Illuminate\Support\Str::limit($row->deskripsi, 55) }}</span></td>
                            <td class="text-end text-success">{{ (float)$row->kredit ? number_format($row->kredit, 0, ',', '.') : '' }}</td>
                            <td class="text-end text-danger">{{ (float)$row->debit ? number_format($row->debit, 0, ',', '.') : '' }}</td>
                            <td>
                                @if($row->arah_mutasi === 'MASUK')
                                    <form method="POST" action="{{ route('pembukuan.klasifikasi.akun', $row->id) }}">
                                        @csrf
                                        <select name="akun_pendapatan_id" class="form-select form-select-sm" onchange="this.form.submit()">
                                            <option value="">— belum —</option>
                                            @foreach($akunOptions as $a)
                                                <option value="{{ $a->id }}" @selected($row->akun_pendapatan_id == $a->id)>{{ $a->kode_gabungan }} {{ $a->uraian_jenis }}</option>
                                            @endforeach
                                        </select>
                                    </form>
                                @else
                                    <span class="text-muted small">— (arus keluar)</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($row->bukuKasUmum)<span class="badge bg-success">Terposting</span>@else<span class="badge bg-secondary">Belum</span>@endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">Tidak ada baris koran. Impor rekening koran dahulu lewat Buku Pembantu Bank.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $rows->links() }}</div>
    </div>
</div>
@endsection
