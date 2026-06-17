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
            ['label' => 'Sudah Tersanding', 'value' => number_format($summary['tercocok'] ?? 0, 0, ',', '.'), 'class' => 'text-primary'],
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
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Status Rekonsiliasi</label>
                <select name="status_rekonsiliasi" class="form-select">
                    <option value="">Semua</option>
                    <option value="BELUM" @selected($filters['status_rekonsiliasi'] === 'BELUM')>Belum</option>
                    <option value="PARTIAL" @selected($filters['status_rekonsiliasi'] === 'PARTIAL')>Partial</option>
                    <option value="MATCHED" @selected($filters['status_rekonsiliasi'] === 'MATCHED')>Matched</option>
                    <option value="SELISIH" @selected($filters['status_rekonsiliasi'] === 'SELISIH')>Selisih</option>
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
                                <th>Penyandingan</th>
                                <th>Referensi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($bku as $item)
                                @php
                                    $rek = $item->rekonsiliasiBanks->first();
                                    $masuk = $item->arus_kas === 'DEBIT_MASUK';
                                @endphp
                                <tr>
                                    <td>{{ optional($item->tanggal_transaksi)->format('d M Y') }}</td>
                                    <td><span class="fw-semibold small">{{ $item->nomor_bukti ?? '-' }}</span></td>
                                    <td class="small">{{ $item->uraian ?? '-' }}</td>
                                    <td class="text-end text-success">{{ $masuk ? 'Rp ' . number_format($item->nominal, 0, ',', '.') : '-' }}</td>
                                    <td class="text-end text-danger">{{ ! $masuk ? 'Rp ' . number_format($item->nominal, 0, ',', '.') : '-' }}</td>
                                    <td class="text-end">Rp {{ number_format($item->saldo_akhir, 0, ',', '.') }}</td>
                                    <td>@include('pembukuan.partials.status-badge', ['value' => $rek?->status ?? 'BELUM'])</td>
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

    {{-- ══════════════════════════════════════════════════════════
         REKONSILIASI: sandingkan BKU dengan rekening koran
         ══════════════════════════════════════════════════════════ --}}
    <h5 class="fw-bold mt-4 mb-2"><i class="bi bi-arrow-left-right me-1"></i>Rekonsiliasi dengan Rekening Koran</h5>

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

    {{-- 2) Penyandingan: BKU belum cocok ↔ rekening koran belum cocok --}}
    <div class="card book-card mt-3">
        <div class="card-header d-flex flex-wrap align-items-center gap-2">
            <h6 class="mb-0 fw-bold">Penyandingan (Belum Cocok)</h6>
            <span class="badge bg-info text-dark">BKU: {{ $unmatchedBku->count() }}</span>
            <span class="badge bg-warning text-dark">Koran: {{ $unmatchedMutasi->count() }}</span>
            <form method="POST" action="{{ route('pembukuan.bank.rekonsiliasi.auto', $rekening->id) }}" class="ms-auto mb-0"
                  onsubmit="return confirm('Cocokkan otomatis BKU dengan rekening koran (arah & nominal sama, tanggal ±3 hari)?')">
                @csrf
                <input type="hidden" name="start_date" value="{{ $filters['start_date'] }}">
                <input type="hidden" name="end_date" value="{{ $filters['end_date'] }}">
                <button class="btn btn-sm btn-success"><i class="bi bi-magic me-1"></i>Cocokkan Otomatis</button>
            </form>
        </div>
        <div class="card-body">
            @if($unmatchedBku->isEmpty() && $unmatchedMutasi->isEmpty())
                @include('pembukuan.partials.empty-state', ['title' => 'Semua sudah tersanding', 'message' => 'Tidak ada BKU/baris koran yang belum cocok untuk rekening ini.'])
            @else
                <form method="POST" action="{{ route('pembukuan.bank.rekonsiliasi.manual', $rekening->id) }}">
                    @csrf
                    <div class="row g-3">
                        <div class="col-lg-6">
                            <div class="fw-bold mb-2"><i class="bi bi-journal-text me-1"></i>BKU (belum cocok)</div>
                            <div class="border rounded" style="max-height:320px;overflow:auto">
                                <table class="table table-sm table-hover align-middle mb-0">
                                    <thead class="table-light"><tr><th style="width:30px"></th><th>Tanggal</th><th>Uraian</th><th class="text-end">Nominal</th><th>Arah</th></tr></thead>
                                    <tbody>
                                        @forelse($unmatchedBku as $b)
                                            <tr>
                                                <td><input class="form-check-input" type="radio" name="bku_id" value="{{ $b->id }}" required></td>
                                                <td class="text-nowrap small">{{ optional($b->tanggal_transaksi)->format('d/m/Y') }}</td>
                                                <td class="small">{{ \Illuminate\Support\Str::limit($b->uraian, 36) }}</td>
                                                <td class="text-end text-nowrap">Rp {{ number_format($b->nominal, 0, ',', '.') }}</td>
                                                <td><span class="badge {{ $b->arus_kas === 'DEBIT_MASUK' ? 'bg-success' : 'bg-danger' }}">{{ $b->arus_kas === 'DEBIT_MASUK' ? 'MASUK' : 'KELUAR' }}</span></td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="5" class="text-center text-muted py-3">Semua BKU sudah cocok.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="fw-bold mb-2"><i class="bi bi-bank me-1"></i>Rekening Koran (belum cocok)</div>
                            <div class="border rounded" style="max-height:320px;overflow:auto">
                                <table class="table table-sm table-hover align-middle mb-0">
                                    <thead class="table-light"><tr><th style="width:30px"></th><th>Tanggal</th><th>Keterangan</th><th class="text-end">Nominal</th><th>Arah</th><th></th></tr></thead>
                                    <tbody>
                                        @forelse($unmatchedMutasi as $m)
                                            @php($nom = $m->arah_mutasi === 'MASUK' ? $m->kredit : $m->debit)
                                            <tr>
                                                <td><input class="form-check-input" type="radio" name="detail_mutasi_bank_id" value="{{ $m->id }}" required></td>
                                                <td class="text-nowrap small">{{ optional($m->tanggal_transaksi)->format('d/m/Y') }}</td>
                                                <td class="small">{{ \Illuminate\Support\Str::limit($m->deskripsi, 30) }}</td>
                                                <td class="text-end text-nowrap">Rp {{ number_format($nom, 0, ',', '.') }}</td>
                                                <td><span class="badge {{ $m->arah_mutasi === 'MASUK' ? 'bg-success' : 'bg-danger' }}">{{ $m->arah_mutasi }}</span></td>
                                                <td>
                                                    <button type="submit" form="delKoran{{ $m->id }}" class="btn btn-sm btn-link text-danger p-0"
                                                            onclick="return confirm('Hapus baris koran ini?')" title="Hapus baris">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="6" class="text-center text-muted py-3">Belum ada baris rekening koran. Unggah file & tambah baris di atas.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex flex-wrap align-items-center gap-2 mt-3">
                        <input type="text" name="catatan" class="form-control form-control-sm" style="max-width:300px" placeholder="Catatan (opsional)">
                        <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-link-45deg me-1"></i>Cocokkan Manual</button>
                        <small class="text-muted">Pilih satu BKU &amp; satu baris koran, lalu Cocokkan.</small>
                    </div>
                </form>

                {{-- Form hapus baris koran (terpisah dari form match; dirujuk via atribut form=) --}}
                @foreach($unmatchedMutasi as $m)
                    <form id="delKoran{{ $m->id }}" method="POST" action="{{ route('pembukuan.bank.koran.line.destroy', [$rekening->id, $m->id]) }}" class="d-none">
                        @csrf
                        @method('DELETE')
                    </form>
                @endforeach
            @endif
        </div>
    </div>

    {{-- 3) Pasangan yang sudah tersanding --}}
    <div class="card book-card mt-3">
        <div class="card-header"><h6 class="mb-0 fw-bold">Pasangan Tersanding</h6></div>
        <div class="card-body p-0">
            @if($reconciliations->isEmpty())
                @include('pembukuan.partials.empty-state', ['title' => 'Belum ada pasangan', 'message' => 'Lakukan pencocokan otomatis atau manual di atas.'])
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 book-table">
                        <thead class="table-light">
                            <tr>
                                <th>BKU</th>
                                <th>Baris Koran</th>
                                <th class="text-end">Nominal BKU</th>
                                <th class="text-end">Nominal Koran</th>
                                <th class="text-end">Selisih</th>
                                <th>Status</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($reconciliations as $item)
                                <tr>
                                    <td>
                                        <div class="small fw-semibold">{{ $item->bku?->nomor_bukti ?? '-' }}</div>
                                        <div class="small text-muted">{{ \Illuminate\Support\Str::limit($item->bku?->uraian, 40) }}</div>
                                    </td>
                                    <td>
                                        <div class="small">{{ optional($item->detailMutasiBank?->tanggal_transaksi)->format('d/m/Y') }}</div>
                                        <div class="small text-muted">{{ \Illuminate\Support\Str::limit($item->detailMutasiBank?->deskripsi, 40) }}</div>
                                    </td>
                                    <td class="text-end text-nowrap">Rp {{ number_format($item->nominal_sistem, 0, ',', '.') }}</td>
                                    <td class="text-end text-nowrap">Rp {{ number_format($item->nominal_mutasi, 0, ',', '.') }}</td>
                                    <td class="text-end text-nowrap {{ (float) $item->selisih === 0.0 ? 'text-success' : 'text-danger' }}">Rp {{ number_format($item->selisih, 0, ',', '.') }}</td>
                                    <td>@include('pembukuan.partials.status-badge', ['value' => $item->status])</td>
                                    <td class="text-center">
                                        <form method="POST" action="{{ route('pembukuan.bank.rekonsiliasi.unmatch', $rekening->id) }}"
                                              onsubmit="return confirm('Batalkan pasangan ini?')">
                                            @csrf
                                            <input type="hidden" name="rekonsiliasi_bank_id" value="{{ $item->id }}">
                                            <button class="btn btn-sm btn-outline-danger" title="Batalkan"><i class="bi bi-x-lg"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
@endsection
