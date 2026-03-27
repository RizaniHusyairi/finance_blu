@extends('layouts.app')
@section('title')
    Detail Kontrak
@endsection
@section('content')
    <x-page-title title="Manajemen Kontrak" subtitle="Detail Kontrak" />

    <div class="row">
        <div class="col-12 col-lg-8">
            <div class="card border-top border-4 border-info">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div class="d-flex align-items-center">
                            <div><i class="bi bi-info-circle me-2 font-22 text-info"></i></div>
                            <h5 class="mb-0 text-info">Informasi Kontrak</h5>
                        </div>
                        <div>
                            @php
                                $badgeColor = match($contract->status) {
                                    'Aktif' => 'success',
                                    'Draft' => 'secondary',
                                    'Menunggu PPK' => 'info',
                                    'Revisi', 'Ditolak PPK' => 'danger',
                                    'Selesai' => 'primary',
                                    default => 'warning',
                                };
                            @endphp
                            <span class="badge bg-{{ $badgeColor }} font-14">
                                {{ $contract->status }}
                            </span>
                        </div>
                    </div>
                    
                    <table class="table table-borderless table-sm">
                        <tbody>
                            <tr>
                                <th width="30%">Nomor Kontrak</th>
                                <td>: {{ $contract->contract_number }}</td>
                            </tr>
                            <tr>
                                <th>Tanggal Kontrak</th>
                                <td>: {{ \Carbon\Carbon::parse($contract->date)->format('d F Y') }}</td>
                            </tr>
                            <tr>
                                <th>Jenis Kontrak</th>
                                <td>: {{ $contract->type ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th>Periode Pelaksanaan</th>
                                <td>: {{ \Carbon\Carbon::parse($contract->start_date)->format('d/m/Y') }} s.d {{ \Carbon\Carbon::parse($contract->end_date)->format('d/m/Y') }}</td>
                            </tr>
                            <tr>
                                <th>Uraian Pekerjaan</th>
                                <td>: {{ $contract->description }}</td>
                            </tr>
                            <tr>
                                <th>Ketentuan Sanksi</th>
                                <td>: {{ $contract->ketentuan_sanksi ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th>Total Nilai (Rp)</th>
                                <td><strong class="text-primary">: Rp {{ number_format($contract->total_amount, 0, ',', '.') }}</strong></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Adendum Tab/Section placeholder -->
            <div class="card mt-4">
                <div class="card-header bg-transparent border-bottom">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">Daftar Adendum</h6>
                        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addendumModal"><i class="bi bi-plus"></i> Tambah Adendum</button>
                    </div>
                </div>
                <div class="card-body">
                    @if($contract->addendums->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-striped table-sm">
                                <thead>
                                    <tr>
                                        <th>No. Adendum</th>
                                        <th>Tanggal</th>
                                        <th>Keterangan</th>
                                        <th>Status</th>
                                        <th>Nilai Baru</th>
                                        <th>Waktu Baru</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($contract->addendums as $addendum)
                                    <tr>
                                        <td>{{ $addendum->addendum_number }}</td>
                                        <td>{{ \Carbon\Carbon::parse($addendum->date)->format('d/m/Y') }}</td>
                                        <td>{{ Str::limit($addendum->reason, 30) }}</td>
                                        <td>
                                            @php
                                                $badgeStatus = match($addendum->status) {
                                                    'Draft' => 'secondary',
                                                    'Menunggu PPK' => 'info',
                                                    'Disetujui' => 'success',
                                                    'Ditolak' => 'danger',
                                                    default => 'warning'
                                                };
                                            @endphp
                                            <span class="badge bg-{{ $badgeStatus }}">{{ $addendum->status }}</span>
                                        </td>
                                        <td>{{ $addendum->new_total_amount ? 'Rp ' . number_format($addendum->new_total_amount, 0, ',', '.') : '-' }}</td>
                                        <td>{{ $addendum->new_end_date ? \Carbon\Carbon::parse($addendum->new_end_date)->format('d/m/Y') : '-' }}</td>
                                        <td>
                                            <div class="d-flex align-items-center gap-1">
                                                @if(in_array($addendum->status, ['Draft', 'Ditolak']) && auth()->user()->hasAnyRole(['Super Admin', 'Operator BLU', 'Pejabat Pengadaan']))
                                                    <form action="{{ route('addendums.submit', [$contract->id, $addendum->id]) }}" method="POST" onsubmit="return confirm('Ajukan adendum ini ke PPK?');">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-outline-primary py-0 px-1" title="Ajukan ke PPK"><i class="bi bi-send"></i></button>
                                                    </form>
                                                @endif

                                                @if($addendum->status === 'Menunggu PPK' && (auth()->user()->hasRole('PPK') || auth()->user()->hasRole('Super Admin')))
                                                    <form action="{{ route('addendums.approve', [$contract->id, $addendum->id]) }}" method="POST" onsubmit="return confirm('Setujui adendum ini?');">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-outline-success py-0 px-1" title="Setujui"><i class="bi bi-check-lg"></i></button>
                                                    </form>
                                                    <form action="{{ route('addendums.reject', [$contract->id, $addendum->id]) }}" method="POST" onsubmit="var notes = prompt('Alasan penolakan:'); if(notes) { this.notes.value = notes; return true; } return false;">
                                                        @csrf
                                                        <input type="hidden" name="notes" value="">
                                                        <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-1" title="Tolak"><i class="bi bi-x-lg"></i></button>
                                                    </form>
                                                @endif

                                                @if(in_array($addendum->status, ['Draft', 'Ditolak']) && auth()->user()->hasAnyRole(['Super Admin', 'Operator BLU', 'Pejabat Pengadaan']))
                                                    <form action="{{ route('addendums.destroy', [$contract->id, $addendum->id]) }}" method="POST" onsubmit="return confirm('Hapus adendum ini?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-1" title="Hapus"><i class="bi bi-trash"></i></button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-muted mb-0">Belum ada data adendum.</p>
                    @endif
                </div>
            </div>

            <!-- Termin Pembayaran Tab/Section placeholder -->
            @php
                $terminItems = $contract->terms->filter(fn($t) => $t->type === 'Termin' || $t->type === null);
                $angsuranItems = $contract->terms->filter(fn($t) => $t->type === 'Angsuran');
            @endphp
            <div class="card mt-4">
                <div class="card-header bg-transparent border-bottom">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">Termin Pembayaran</h6>
                        <div class="d-flex gap-2 align-items-center">
                            <span class="badge bg-light text-dark border">Total: {{ $terminItems->sum('percentage') }}%</span>
                            <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#terminModal"><i class="bi bi-plus"></i> Set Termin</button>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                     @error('percentage')
                        <div class="alert alert-danger py-2">{{ $message }}</div>
                     @enderror
                     @if($terminItems->count() > 0)
                        <div class="table-responsive">
                             <table class="table table-striped table-sm">
                                <thead>
                                    <tr>
                                        <th>Termin</th>
                                        <th>Persentase</th>
                                        <th>Nilai (Rp)</th>
                                        <th>Status</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                     @foreach($terminItems as $term)
                                        <tr>
                                            <td>{{ $term->term_name }}</td>
                                            <td>{{ $term->percentage }}%</td>
                                            <td>Rp {{ number_format($term->amount, 0, ',', '.') }}</td>
                                            <td><span class="badge bg-{{ $term->status == 'Paid' ? 'success' : 'warning' }}">{{ $term->status }}</span></td>
                                            <td>
                                                <form action="{{ route('terms.destroy', [$contract->id, $term->id]) }}" method="POST" onsubmit="return confirm('Hapus termin ini?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-1" {{ $term->status == 'Paid' ? 'disabled' : '' }}><i class="bi bi-trash"></i></button>
                                                </form>
                                            </td>
                                        </tr>
                                     @endforeach
                                </tbody>
                            </table>
                        </div>
                     @else
                        <div class="alert alert-warning mb-0 border-0 bg-warning text-dark">
                            Termin pembayaran belum diatur. Nilai kontrak belum dibreakdown.
                        </div>
                     @endif
                </div>
            </div>

            {{-- Tabel Angsuran Uang Muka (tampil hanya jika kontrak ada uang muka) --}}
            @if($contract->ada_uang_muka)
            <div class="card mt-4 border-top border-4 border-warning">
                <div class="card-header bg-transparent border-bottom">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-cash-coin me-2 text-warning font-18"></i>
                            <h6 class="mb-0">Angsuran Uang Muka</h6>
                        </div>
                        <span class="badge bg-warning text-dark">UM: Rp {{ number_format($contract->nilai_uang_muka, 0, ',', '.') }} ({{ $contract->persentase_uang_muka }}%)</span>
                    </div>
                </div>
                <div class="card-body">
                    @if($angsuranItems->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-striped table-sm">
                                <thead>
                                    <tr>
                                        <th>Angsuran</th>
                                        <th>Persentase</th>
                                        <th>Nilai (Rp)</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($angsuranItems as $angsuran)
                                    <tr>
                                        <td>{{ $angsuran->term_name }}</td>
                                        <td>{{ $angsuran->percentage }}%</td>
                                        <td>Rp {{ number_format($angsuran->amount, 0, ',', '.') }}</td>
                                        <td><span class="badge bg-{{ $angsuran->status == 'Paid' ? 'success' : 'warning' }}">{{ $angsuran->status }}</span></td>
                                    </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr class="fw-bold">
                                        <td>Total</td>
                                        <td>{{ $angsuranItems->sum('percentage') }}%</td>
                                        <td>Rp {{ number_format($angsuranItems->sum('amount'), 0, ',', '.') }}</td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    @else
                        <p class="text-muted mb-0">Belum ada data angsuran uang muka.</p>
                    @endif
                </div>
            </div>
            @endif

        </div>

        <div class="col-12 col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="mb-3">Informasi Mitra</h6>
                    @if($contract->supplier)
                        <p class="mb-1"><strong>{{ $contract->supplier->name }}</strong></p>
                        <p class="mb-1 text-muted small">{{ $contract->supplier->address }}</p>
                        <hr>
                        <ul class="list-unstyled mb-0 small">
                            <li class="mb-2"><i class="bi bi-telephone me-2"></i>{{ $contract->supplier->phone ?? '-' }}</li>
                            <li class="mb-2"><i class="bi bi-card-text me-2"></i>NPWP: {{ $contract->supplier->npwp ?? '-' }}</li>
                            <li class="mb-0"><i class="bi bi-bank me-2"></i>{{ $contract->supplier->bank_name ?? 'Bank' }} - {{ $contract->supplier->bank_account ?? 'Rek' }} ({{ $contract->supplier->account_name ?? 'A.N' }})</li>
                        </ul>
                    @else
                        <span class="text-danger">Mitra tidak ditemukan.</span>
                    @endif
                </div>
            </div>

            <div class="card mt-4">
                <div class="card-body">
                    <h6 class="mb-3">Beban Anggaran (COA)</h6>
                     @if($contract->budget)
                        <p class="mb-1 fw-bold">{{ $contract->budget->year }} - {{ $contract->budget->coa }}</p>
                        <p class="mb-2 small text-muted">{{ $contract->budget->description }}</p>
                        
                        <div class="d-flex justify-content-between mb-1 small">
                            <span>Pagu Awal:</span>
                            <span>Rp {{ number_format($contract->budget->initial_budget, 0, ',', '.') }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-1 small">
                            <span>Realisasi:</span>
                            <span class="text-danger">Rp {{ number_format($contract->budget->realized_budget, 0, ',', '.') }}</span>
                        </div>
                        <div class="d-flex justify-content-between fw-bold small border-top pt-1 mt-1">
                            <span>Sisa Pagu:</span>
                            <span class="text-success">Rp {{ number_format($contract->budget->remaining_budget, 0, ',', '.') }}</span>
                        </div>
                     @else
                        <span class="text-danger">Data anggaran tidak ditemukan.</span>
                     @endif
                </div>
            </div>

            {{-- Approval Actions --}}
            @if(in_array($contract->status, ['Draft', 'Revisi', 'Ditolak PPK']))
                @if(auth()->user()->hasAnyRole(['Super Admin', 'Operator BLU', 'Pejabat Pengadaan']))
                    <div class="card mt-4 border-top border-4 border-primary">
                        <div class="card-body">
                            <h6 class="mb-3 text-primary"><i class="bi bi-send me-2"></i>Ajukan Kontrak</h6>
                            <p class="small text-muted mb-3">Kontrak ini berstatus <strong>{{ $contract->status }}</strong>. Ajukan ke PPK untuk mendapatkan persetujuan.</p>
                            <form action="{{ route('contracts.submit', $contract->id) }}" method="POST" onsubmit="return confirm('Ajukan kontrak ini ke PPK untuk persetujuan?');">
                                @csrf
                                <button type="submit" class="btn btn-primary w-100"><i class="bi bi-send me-1"></i>Ajukan ke PPK</button>
                            </form>
                        </div>
                    </div>
                @endif
            @endif

            @if($contract->status === 'Menunggu PPK')
                @if(auth()->user()->hasRole('PPK') || auth()->user()->hasRole('Super Admin'))
                    <div class="card mt-4 border-top border-4 border-success">
                        <div class="card-body">
                            <h6 class="mb-3 text-success"><i class="bi bi-clipboard-check me-2"></i>Persetujuan PPK</h6>
                            <p class="small text-muted mb-3">Kontrak ini menunggu persetujuan Anda.</p>
                            
                            {{-- Approve --}}
                            <form action="{{ route('contracts.approve', $contract->id) }}" method="POST" class="mb-2" onsubmit="return confirm('Setujui kontrak ini?');">
                                @csrf
                                <div class="mb-2">
                                    <textarea class="form-control form-control-sm" name="notes" rows="2" placeholder="Catatan persetujuan (opsional)"></textarea>
                                </div>
                                <button type="submit" class="btn btn-success w-100"><i class="bi bi-check-circle me-1"></i>Setujui Kontrak</button>
                            </form>

                            {{-- Reject --}}
                            <form action="{{ route('contracts.reject', $contract->id) }}" method="POST" onsubmit="return confirm('Tolak kontrak ini?');">
                                @csrf
                                <div class="mb-2">
                                    <textarea class="form-control form-control-sm" name="notes" rows="2" placeholder="Alasan penolakan *" required></textarea>
                                </div>
                                <button type="submit" class="btn btn-outline-danger w-100"><i class="bi bi-x-circle me-1"></i>Tolak Kontrak</button>
                            </form>
                        </div>
                    </div>
                @else
                    <div class="card mt-4 border-top border-4 border-info">
                        <div class="card-body text-center">
                            <i class="bi bi-hourglass-split text-info font-22"></i>
                            <p class="small text-muted mt-2 mb-0">Menunggu persetujuan dari PPK</p>
                        </div>
                    </div>
                @endif
            @endif

            {{-- Approval Log History --}}
            @if($contract->approvalLogs && $contract->approvalLogs->count() > 0)
                <div class="card mt-4">
                    <div class="card-body">
                        <h6 class="mb-3"><i class="bi bi-clock-history me-2"></i>Riwayat Persetujuan</h6>
                        <div class="timeline-sm">
                            @foreach($contract->approvalLogs as $log)
                                <div class="d-flex mb-3 pb-3 {{ !$loop->last ? 'border-bottom' : '' }}">
                                    <div class="flex-shrink-0 me-3">
                                        @php
                                            $logIcon = match($log->status_to) {
                                                'Aktif' => 'bi-check-circle-fill text-success',
                                                'Revisi', 'Ditolak PPK' => 'bi-x-circle-fill text-danger',
                                                'Menunggu PPK' => 'bi-send-fill text-primary',
                                                default => 'bi-circle text-secondary',
                                            };
                                        @endphp
                                        <i class="bi {{ $logIcon }} font-18"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="fw-bold small">{{ $log->status_to }}</div>
                                        <div class="text-muted small">{{ $log->user->name ?? '-' }} ({{ $log->role_name }})</div>
                                        @if($log->notes)
                                            <div class="small fst-italic mt-1">{{ $log->notes }}</div>
                                        @endif
                                        <div class="text-muted small mt-1">{{ $log->created_at->format('d M Y H:i') }}</div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
            
            <div class="d-grid mt-4">
                 <a href="{{ route('contracts.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-2"></i>Kembali ke Daftar</a>
            </div>
        </div>
    </div>

    <!-- Modal Form Tambah Adendum -->
    <div class="modal fade" id="addendumModal" tabindex="-1" aria-labelledby="addendumModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('addendums.store', $contract->id) }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="addendumModalLabel">Tambah Adendum Kontrak</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Nomor Adendum <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="addendum_number" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Tanggal Adendum <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="date" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Alasan/Uraian Adendum <span class="text-danger">*</span></label>
                            <textarea class="form-control" name="reason" rows="2" required></textarea>
                        </div>
                        <div class="alert alert-info py-2 small">Isi field di bawah jika ada perubahan nilai kontrak atau waktu:</div>
                        <div class="mb-3">
                            <label class="form-label">Nilai Kontrak Baru (Rp)</label>
                            <input type="number" step="0.01" class="form-control" name="new_total_amount" placeholder="Kosongkan jika tidak ada perubahan nilai">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Tanggal Berakhir Baru</label>
                            <input type="date" class="form-control" name="new_end_date">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan Adendum</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Form Tambah Termin -->
    <div class="modal fade" id="terminModal" tabindex="-1" aria-labelledby="terminModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('terms.store', $contract->id) }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="terminModalLabel">Tambah Termin Pembayaran</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-light border small py-2 mb-3">
                            <strong>Nilai Kontrak Saat Ini:</strong> Rp <span id="currentTotal">{{ number_format($contract->total_amount, 0, ',', '') }}</span>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nama Termin (Contoh: Uang Muka, Termin 1) <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="term_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Persentase (%) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" step="0.01" class="form-control" name="percentage" id="termPercentage" required max="100">
                                <span class="input-group-text">%</span>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nilai Termin (Rp) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" class="form-control bg-light" name="amount" id="termAmount" readonly required>
                            <small class="text-muted">Dihitung otomatis berdasarkan persentase.</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan Termin</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
@push('script')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const percentageInput = document.getElementById('termPercentage');
        const amountInput = document.getElementById('termAmount');
        const totalAmount = parseFloat(document.getElementById('currentTotal').innerText);

        percentageInput.addEventListener('input', function() {
            let percentage = parseFloat(this.value) || 0;
            if(percentage > 100) {
                percentage = 100;
                this.value = 100;
            }
            const calculatedAmount = (percentage / 100) * totalAmount;
            amountInput.value = calculatedAmount.toFixed(2);
        });
    });
</script>
@endpush
