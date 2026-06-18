@extends('layouts.app')
@section('title', 'Detail Buku Pembantu Bank')

@include('pembukuan.partials.styles')

@section('content')
    <x-page-title title="Pembukuan" subtitle="Detail Buku Pembantu Bank" />

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="mb-1 fw-bold text-dark">{{ $rekening->nama_bank }} · {{ $rekening->nomor_rekening }}</h4>
            <div class="text-muted">{{ $rekening->nama_rekening ?? '-' }}</div>
        </div>
        <a href="{{ route('pembukuan.bank.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Kembali</a>
    </div>

    @php
        $cards = [
            ['label' => 'Jumlah Transaksi', 'value' => number_format($summary['jumlah_transaksi'] ?? 0, 0, ',', '.'), 'class' => 'text-dark'],
            ['label' => 'Total Masuk', 'value' => 'Rp ' . number_format($summary['total_masuk'] ?? 0, 0, ',', '.'), 'class' => 'text-success'],
            ['label' => 'Total Keluar', 'value' => 'Rp ' . number_format($summary['total_keluar'] ?? 0, 0, ',', '.'), 'class' => 'text-danger'],
        ];
    @endphp
    @include('pembukuan.partials.summary-cards', ['cards' => $cards])

    <div class="book-filter">
        <form method="GET" action="{{ route('pembukuan.bank.show', $rekening->id) }}" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Tanggal Awal</label>
                <input type="date" name="start_date" class="form-control" value="{{ $filters['start_date'] }}">
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Tanggal Akhir</label>
                <input type="date" name="end_date" class="form-control" value="{{ $filters['end_date'] }}">
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Arah Mutasi</label>
                <select name="arah_mutasi" class="form-select">
                    <option value="">Semua</option>
                    <option value="MASUK" @selected($filters['arah_mutasi'] === 'MASUK')>Masuk</option>
                    <option value="KELUAR" @selected($filters['arah_mutasi'] === 'KELUAR')>Keluar</option>
                </select>
            </div>
            <div class="col-12 d-flex gap-2">
                <button class="btn btn-primary"><i class="bi bi-funnel me-1"></i>Filter</button>
                <a href="{{ route('pembukuan.bank.show', $rekening->id) }}" class="btn btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>

    <div class="card book-card">
        <div class="card-header"><h6 class="mb-0 fw-bold">Transaksi BKU Rekening Ini</h6></div>
        <div class="card-body p-0">
            @if($bku->isEmpty())
                @include('pembukuan.partials.empty-state', ['title' => 'Belum ada transaksi', 'message' => 'Tidak ada transaksi BKU yang sesuai filter untuk rekening ini.'])
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 book-table">
                        <thead class="table-light">
                            <tr>
                                <th>Tanggal</th>
                                <th>Nomor Bukti</th>
                                <th>Uraian</th>
                                <th class="text-end">Masuk</th>
                                <th class="text-end">Keluar</th>
                                <th class="text-end">Saldo</th>
                                <th>Referensi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($bku as $item)
                                @php
                                    $masuk = $item->arus_kas === 'DEBIT_MASUK';
                                @endphp
                                <tr>
                                    <td>{{ optional($item->tanggal_transaksi)->format('d M Y') }}</td>
                                    <td><span class="fw-semibold small">{{ $item->nomor_bukti ?? '-' }}</span></td>
                                    <td class="small">{{ $item->uraian ?? '-' }}</td>
                                    <td class="text-end text-success">{{ $masuk ? 'Rp ' . number_format($item->nominal, 0, ',', '.') : '-' }}</td>
                                    <td class="text-end text-danger">{{ ! $masuk ? 'Rp ' . number_format($item->nominal, 0, ',', '.') : '-' }}</td>
                                    <td class="text-end">Rp {{ number_format($item->saldo_akhir, 0, ',', '.') }}</td>
                                    <td class="small text-muted">
                                        @if($item->referensiPengeluaran)
                                            Tagihan: {{ $item->referensiPengeluaran->nomor_tagihan ?? '-' }}
                                        @elseif($item->referensiPenerimaan)
                                            Invoice: {{ $item->referensiPenerimaan->nomor_invoice ?? '-' }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    {{-- Impor rekening koran — sumber data untuk Klasifikasi Penerimaan. --}}
    <h5 class="fw-bold mt-4 mb-2"><i class="bi bi-bank me-1"></i>Impor Rekening Koran</h5>

    {{-- 1) Rekening koran: upload file + input baris manual --}}
    <div class="row g-3">
        <div class="col-lg-5">
            <div class="card book-card h-100">
                <div class="card-header"><h6 class="mb-0 fw-bold">Rekening Koran Rekening Ini</h6></div>
                <div class="card-body">
                    <form method="POST" action="{{ route('pembukuan.bank.koran.upload', $rekening->id) }}" enctype="multipart/form-data" class="mb-3">
                        @csrf
                        <label class="form-label small fw-semibold">Unggah file rekening koran (bukti)</label>
                        <input type="file" name="file_koran" class="form-control form-control-sm mb-2" required
                               accept=".pdf,.xls,.xlsx,.csv,.jpg,.jpeg,.png">
                        <div class="row g-2 mb-2">
                            <div class="col"><input type="date" name="periode_awal" class="form-control form-control-sm" placeholder="Periode awal"></div>
                            <div class="col"><input type="date" name="periode_akhir" class="form-control form-control-sm"></div>
                        </div>
                        <button class="btn btn-sm btn-primary w-100"><i class="bi bi-upload me-1"></i>Unggah</button>
                    </form>

                    @if($imports->isNotEmpty())
                        <div class="small fw-semibold text-muted mb-1">File terunggah</div>
                        <ul class="list-group list-group-flush small mb-0">
                            @foreach($imports as $imp)
                                <li class="list-group-item px-0 d-flex justify-content-between align-items-center">
                                    <span>
                                        <i class="bi bi-file-earmark-text me-1"></i>
                                        {{ $imp->nama_file_asli }}
                                        <span class="text-muted">· {{ $imp->detail_mutasi_banks_count }} baris</span>
                                    </span>
                                    @if($imp->path_file)
                                        <a href="{{ asset('storage/'.$imp->path_file) }}" target="_blank" class="btn btn-sm btn-link p-0">Lihat</a>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="card book-card h-100">
                <div class="card-header"><h6 class="mb-0 fw-bold">Tambah Baris Rekening Koran (manual)</h6></div>
                <div class="card-body">
                    <form method="POST" action="{{ route('pembukuan.bank.koran.line.store', $rekening->id) }}" class="row g-2 align-items-end">
                        @csrf
                        <div class="col-md-3"><label class="form-label small fw-semibold">Tanggal</label><input type="date" name="tanggal_transaksi" class="form-control form-control-sm" required></div>
                        <div class="col-md-4"><label class="form-label small fw-semibold">Keterangan</label><input type="text" name="deskripsi" class="form-control form-control-sm" placeholder="Uraian mutasi"></div>
                        <div class="col-md-2">
                            <label class="form-label small fw-semibold">Arah</label>
                            <select name="arah_mutasi" class="form-select form-select-sm" required>
                                <option value="MASUK">Masuk</option>
                                <option value="KELUAR">Keluar</option>
                            </select>
                        </div>
                        <div class="col-md-3"><label class="form-label small fw-semibold">Nominal</label><input type="number" step="any" min="0" name="nominal" class="form-control form-control-sm" required></div>
                        <div class="col-12"><button class="btn btn-sm btn-outline-primary"><i class="bi bi-plus-lg me-1"></i>Tambah Baris</button></div>
                    </form>
                </div>
            </div>
        </div>
    </div>

@endsection
