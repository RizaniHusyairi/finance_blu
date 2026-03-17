@extends('layouts.app')
@section('title')
    Detail Tagihan (Pengajuan Pembayaran BLU)
@endsection
@section('content')
    <x-page-title title="Tagihan & Pembayaran" subtitle="Detail Pengajuan" />

    @if(session('success'))
        <div class="alert alert-success border-0 bg-success alert-dismissible fade show">
            <div class="text-white">{{ session('success') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger border-0 bg-danger alert-dismissible fade show">
            <div class="text-white">{{ session('error') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row">
        <!-- Main Info Card -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Informasi Tagihan</h5>
                    @php
                        $statusClass = 'secondary';
                        if($transaction->status == 'Draft') $statusClass = 'warning text-dark';
                        elseif($transaction->status == 'Verified') $statusClass = 'info text-dark';
                        elseif($transaction->status == 'Approved SPP' || $transaction->status == 'Approved SPM') $statusClass = 'primary';
                        elseif($transaction->status == 'Paid SP2D') $statusClass = 'success';
                        elseif($transaction->status == 'Rejected') $statusClass = 'danger';
                    @endphp
                    <span class="badge bg-{{ $statusClass }} fs-6">{{ $transaction->status }}</span>
                </div>
                <div class="card-body">
                    <table class="table table-borderless table-sm">
                        <tbody>
                            <tr>
                                <th width="30%">Nomor Transaksi</th>
                                <td>: {{ $transaction->transaction_number }}</td>
                            </tr>
                            <tr>
                                <th>Tanggal</th>
                                <td>: {{ \Carbon\Carbon::parse($transaction->date)->isoFormat('D MMMM Y') }}</td>
                            </tr>
                            <tr>
                                <th>Jenis Pembayaran</th>
                                <td>: {{ $transaction->type }}</td>
                            </tr>
                            <tr>
                                <th>Uraian</th>
                                <td>: {{ $transaction->description }}</td>
                            </tr>
                            <tr>
                                <th>Beban Anggaran (COA)</th>
                                <td>: {{ $transaction->budget ? $transaction->budget->coa . ' - ' . $transaction->budget->description : '-' }}</td>
                            </tr>
                            <tr>
                                <th>Kontrak Terkait</th>
                                <td>: 
                                    @if($transaction->contract)
                                        <a href="{{ route('contracts.show', $transaction->contract_id) }}">
                                            {{ $transaction->contract->contract_number }}
                                        </a>
                                        @if($transaction->term)
                                            <span class="badge bg-info text-dark ms-2">Termin: {{ $transaction->term->term_name }}</span>
                                        @endif
                                    @else
                                        - Non Kontrak -
                                    @endif
                                </td>
                            </tr>
                        </tbody>
                    </table>

                     <hr>
                     <div class="d-flex justify-content-between align-items-center mt-3">
                         <h5 class="mb-0">Rincian Nilai</h5>
                     </div>
                     <table class="table table-bordered mt-2">
                        <tbody>
                            <tr>
                                <th>Nilai Bruto (Tagihan)</th>
                                <td class="text-end">Rp {{ number_format($transaction->amount, 2, ',', '.') }}</td>
                            </tr>
                            
                            @php
                                $totalTaxes = 0; 
                            @endphp
                            
                            @foreach($transaction->taxes as $tax)
                                @php $totalTaxes += $tax->amount; @endphp
                                <tr>
                                    <td>
                                        <div class="d-flex justify-content-between">
                                            <span>Pajak: {{ $tax->tax_name }} {{ $tax->tax_account ? '('.$tax->tax_account.')' : '' }}</span>
                                            @if($transaction->status == 'Draft' || $transaction->status == 'Rejected')
                                                <form action="{{ route('blu-payment-submissions.taxes.destroy', [$transaction->id, $tax->id]) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm text-danger p-0" onclick="return confirm('Hapus pajak ini?');"><i class="bi bi-x-circle"></i></button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="text-end text-danger">- Rp {{ number_format($tax->amount, 2, ',', '.') }}</td>
                                </tr>
                            @endforeach
                            
                            @php
                                $netAmount = $transaction->amount - $totalTaxes;
                            @endphp
                            <tr class="table-light">
                                <th>Total Potongan Pajak</th>
                                <td class="text-end text-danger">- Rp {{ number_format($totalTaxes, 2, ',', '.') }}</td>
                            </tr>
                            <tr class="table-primary">
                                <th>Nilai Bersih (Netto)</th>
                                <td class="text-end fw-bold">Rp {{ number_format($netAmount, 2, ',', '.') }}</td>
                            </tr>
                        </tbody>
                     </table>
                     
                     @if($transaction->status == 'Draft' || $transaction->status == 'Rejected')
                         <div class="mt-3">
                             <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#addTaxModal">
                                 <i class="bi bi-plus-circle"></i> Tambah Rincian Pajak
                             </button>
                         </div>
                     @endif
                </div>
                <div class="card-footer bg-light">
                    <!-- Action Buttons based on Role & Status -->
                    <div class="d-flex gap-2 flex-wrap">
                        <a href="{{ route('blu-payment-submissions.index') }}" class="btn btn-secondary">Kembali</a>
                        @if($transaction->status == 'Draft' || $transaction->status == 'Rejected')
                            <a href="{{ route('blu-payment-submissions.edit', $transaction->id) }}" class="btn btn-warning">Edit Draft</a>
                            <form action="{{ route('blu-payment-submissions.submit', $transaction->id) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-primary" onclick="return confirm('Ajukan berkas untuk Verifikasi?')">Ajukan Verifikasi</button>
                            </form>
                        @endif
                        @if(in_array($transaction->status, ['Verified', 'Approved SPP', 'Approved SPM']))
                            <form action="{{ route('blu-payment-submissions.approve', $transaction->id) }}" method="POST">
                                @csrf
                                @php
                                    $approveLabel = 'Approve';
                                    if($transaction->status == 'Verified') $approveLabel = 'Approve SPP (PPK)';
                                    elseif($transaction->status == 'Approved SPP') $approveLabel = 'Approve SPM (PPSPM)';
                                    elseif($transaction->status == 'Approved SPM') $approveLabel = 'Konfirmasi SP2D (Bendahara)';
                                @endphp
                                <button type="submit" class="btn btn-success" onclick="return confirm('Setujui pengajuan ini?')"><i class="bi bi-check2-circle"></i> {{ $approveLabel }}</button>
                            </form>
                            <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#rejectModal"><i class="bi bi-x-circle"></i> Tolak / Kembalikan</button>
                        @endif
                        @if($transaction->status == 'Paid SP2D')
                            <span class="btn btn-success disabled"><i class="bi bi-check-all"></i> Lunas (SP2D Terbit)</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar Timeline/Logs Card -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Riwayat Proses</h5>
                </div>
                <div class="card-body">
                    <div class="timeline">
                        <div class="d-flex mb-3">
                            <div class="flex-shrink-0">
                                <span class="badge bg-success rounded-circle p-2"><i class="bi bi-check2"></i></span>
                            </div>
                            <div class="flex-grow-1 ms-3 border-start ps-3 border-2 border-success">
                                <h6 class="mb-1">Draft Dibuat</h6>
                                <p class="mb-0 text-muted small">{{ \Carbon\Carbon::parse($transaction->created_at)->diffForHumans() }}</p>
                            </div>
                        </div>
                        
                        @foreach($transaction->approvalLogs as $log)
                            @php
                                $logColor = 'secondary';
                                $logIcon = 'arrow-right';
                                if($log->action == 'approve') { $logColor = 'success'; $logIcon = 'check-circle'; }
                                elseif($log->action == 'reject') { $logColor = 'danger'; $logIcon = 'x-circle'; }
                                elseif($log->action == 'submit') { $logColor = 'info'; $logIcon = 'send'; }
                            @endphp
                            <div class="d-flex mb-3">
                                <div class="flex-shrink-0">
                                    <span class="badge bg-{{ $logColor }} rounded-circle p-2"><i class="bi bi-{{ $logIcon }}"></i></span>
                                </div>
                                <div class="flex-grow-1 ms-3 border-start ps-3 border-2 border-{{ $logColor }}">
                                    <h6 class="mb-1">{{ $log->from_status }} → {{ $log->to_status }}</h6>
                                    <p class="mb-1 small">{{ $log->notes }}</p>
                                    <p class="mb-0 text-muted small">{{ $log->user->name ?? 'System' }} · {{ \Carbon\Carbon::parse($log->created_at)->diffForHumans() }}</p>
                                </div>
                            </div>
                        @endforeach

                        @if($transaction->approvalLogs->isEmpty() && $transaction->status == 'Draft')
                            <p class="text-muted small">Belum ada riwayat proses.</p>
                        @endif
                    </div>
                </div>
            </div>
            
            <!-- Dokumen Cetak -->
            <div class="card mt-3">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Dokumen Cetak</h5>
                </div>
                <div class="card-body">
                     @if($transaction->status == 'Draft')
                         <p class="text-muted small">Dokumen akan tersedia setelah berkas diajukan dan diverifikasi.</p>
                         <button class="btn btn-outline-secondary w-100 mb-2" disabled><i class="bi bi-printer"></i> Cetak SPP</button>
                         <button class="btn btn-outline-secondary w-100" disabled><i class="bi bi-printer"></i> Cetak SPM</button>
                     @else
                         <a href="{{ route('blu-payment-submissions.print.spp', $transaction->id) }}" target="_blank" class="btn btn-outline-primary w-100 mb-2"><i class="bi bi-printer"></i> Cetak SPP (PDF)</a>
                         @if(!in_array($transaction->status, ['Draft', 'Rejected', 'Verified']))
                             <a href="{{ route('blu-payment-submissions.print.spm', $transaction->id) }}" target="_blank" class="btn btn-outline-success w-100"><i class="bi bi-printer"></i> Cetak SPM (PDF)</a>
                         @else
                             <button class="btn btn-outline-secondary w-100" disabled><i class="bi bi-printer"></i> Cetak SPM (Belum tersedia)</button>
                         @endif
                     @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Reject -->
    @if(in_array($transaction->status, ['Verified', 'Approved SPP', 'Approved SPM']))
    <div class="modal fade" id="rejectModal" tabindex="-1" aria-labelledby="rejectModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('blu-payment-submissions.reject', $transaction->id) }}" method="POST">
                    @csrf
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title" id="rejectModalLabel">Tolak / Kembalikan Berkas</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="reject_notes" class="form-label">Alasan Penolakan <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="reject_notes" name="notes" rows="3" required placeholder="Jelaskan alasan penolakan..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-danger">Tolak Berkas</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    <!-- Modal Tambah Pajak -->
    @if($transaction->status == 'Draft' || $transaction->status == 'Rejected')
    <div class="modal fade" id="addTaxModal" tabindex="-1" aria-labelledby="addTaxModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('blu-payment-submissions.taxes.store', $transaction->id) }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="addTaxModalLabel">Tambah Rincian Pajak</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="raw_amount" value="{{ $transaction->amount }}">

                        <div class="mb-3">
                            <label for="tax_type" class="form-label">Jenis Pajak</label>
                            <select class="form-select @error('tax_type') is-invalid @enderror" id="tax_type" name="tax_type" required>
                                <option value="">-- Pilih Jenis Pajak --</option>
                                <option value="PPN">PPN</option>
                                <option value="PPh 21">PPh Pasal 21</option>
                                <option value="PPh 22">PPh Pasal 22</option>
                                <option value="PPh 23">PPh Pasal 23</option>
                                <option value="PPh 4(2)">PPh Pasal 4(2)</option>
                                <option value="Lainnya">Lainnya</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Hitung DPP Otomatis dari Bruto?</label>
                            <select class="form-select" id="dpp_calc_mode" onchange="calculateTax()">
                                <option value="manual">Manual (Isi DPP Sendiri)</option>
                                <option value="ppn_inc">Termasuk PPN 11% (100/111 x Bruto)</option>
                                <option value="full">Bruto Sebagai DPP (100% x Bruto)</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="dpp_amount" class="form-label">Dasar Pengenaan Pajak (DPP) Rp</label>
                            <input type="number" step="0.01" class="form-control" id="dpp_amount" name="dpp_amount" required oninput="calculateTax()" value="0">
                        </div>

                        <div class="mb-3">
                            <label for="percentage" class="form-label">Tarif Pajak (%)</label>
                            <input type="number" step="0.01" class="form-control" id="percentage" name="percentage" required oninput="calculateTax()">
                        </div>

                        <div class="mb-3">
                            <label for="tax_amount" class="form-label">Nominal Pajak (Rp) - <i>(Otomatis DPP x Tarif)</i></label>
                            <input type="number" step="0.01" class="form-control bg-light" id="tax_amount" name="tax_amount" required readonly>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Keterangan / Kode Billing (Opsional)</label>
                            <input type="text" class="form-control" id="description" name="description">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-danger">Simpan Potongan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
@endsection
@push('script')
<script>
    function calculateTax() {
        const mode = document.getElementById('dpp_calc_mode').value;
        const bruto = parseFloat(document.getElementById('raw_amount').value);
        const dppInput = document.getElementById('dpp_amount');
        const percentage = parseFloat(document.getElementById('percentage').value) || 0;
        const taxAmountInput = document.getElementById('tax_amount');

        let dpp = parseFloat(dppInput.value) || 0;

        if (mode === 'ppn_inc') {
            dpp = bruto * (100 / 111);
            dppInput.value = dpp.toFixed(2);
             dppInput.setAttribute('readonly', 'readonly');
        } else if (mode === 'full') {
            dpp = bruto;
            dppInput.value = dpp.toFixed(2);
            dppInput.setAttribute('readonly', 'readonly');
        } else {
            dppInput.removeAttribute('readonly');
        }

        const taxAmount = dpp * (percentage / 100);
        taxAmountInput.value = taxAmount.toFixed(2);
    }
    
    document.getElementById('tax_type')?.addEventListener('change', function() {
        const type = this.value;
        const pc = document.getElementById('percentage');
        if(type === 'PPN') pc.value = 11;
        else if(type === 'PPh 23') pc.value = 2;
        else if(type === 'PPh 4(2)') pc.value = 10;
        
        calculateTax();
    });
</script>
@endpush
