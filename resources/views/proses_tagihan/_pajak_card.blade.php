@php
    $canEditPajak = auth()->user()?->hasAnyRole(['Bendahara Pengeluaran', 'Super Admin']);
    $billingRoute = match ($tagihan->tipe_tagihan) {
        'KONTRAK' => 'pajak-potongan.kontrak.billing',
        'HONORARIUM' => 'pajak-potongan.honor.billing',
        default => 'pajak-potongan.billing',
    };
    $ntpnRoute = match ($tagihan->tipe_tagihan) {
        'KONTRAK' => 'pajak-potongan.kontrak.ntpn',
        'HONORARIUM' => 'pajak-potongan.honor.ntpn',
        default => 'pajak-potongan.ntpn',
    };
@endphp

<div class="card process-card shadow-sm mb-3">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-start gap-2 mb-3">
            <div>
                <div class="process-section-title">Pajak</div>
                <div class="text-muted small">Kode billing dan NTPN dicatat di halaman yang sama.</div>
            </div>
            <span class="badge {{ $state['potonganPajak']->isEmpty() ? 'bg-secondary' : ($state['pajakSettled'] ? 'bg-success' : 'bg-warning text-dark') }}">
                {{ $state['potonganPajak']->isEmpty() ? 'Tidak ada' : ($state['pajakSettled'] ? 'Lengkap' : 'Menunggu') }}
            </span>
        </div>

        @if($state['potonganPajak']->isEmpty())
            <div class="text-muted small">Tagihan ini tidak memiliki potongan pajak.</div>
        @else
            <div class="table-responsive">
                <table class="table table-sm align-middle">
                    <thead>
                        <tr>
                            <th>Pajak</th>
                            <th>Nominal</th>
                            <th>Kode Billing</th>
                            <th>NTPN</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($state['potonganPajak'] as $potongan)
                            <tr>
                                <td>{{ $potongan->deskripsi }}</td>
                                <td>Rp {{ number_format((float) $potongan->nominal_potongan, 0, ',', '.') }}</td>
                                <td style="min-width: 190px;">
                                    @if($canEditPajak && Route::has($billingRoute))
                                        <form method="POST" action="{{ route($billingRoute, $potongan->id) }}" enctype="multipart/form-data">
                                            @csrf
                                            <div class="d-flex gap-1 mb-1">
                                                <input name="kode_billing" class="form-control form-control-sm" value="{{ $potongan->kode_billing }}">
                                                <button class="btn btn-sm btn-outline-primary" type="submit"><i class="bi bi-save"></i></button>
                                            </div>
                                            <input type="file" name="file_billing" class="form-control form-control-sm" accept=".pdf,.jpg,.jpeg,.png">
                                        </form>
                                    @else
                                        {{ $potongan->kode_billing ?: '-' }}
                                    @endif
                                </td>
                                <td style="min-width: 190px;">
                                    @if($canEditPajak && Route::has($ntpnRoute))
                                        <form method="POST" action="{{ route($ntpnRoute, $potongan->id) }}" enctype="multipart/form-data">
                                            @csrf
                                            <div class="d-flex gap-1 mb-1">
                                                <input name="ntpn" class="form-control form-control-sm" value="{{ $potongan->ntpn }}">
                                                <button class="btn btn-sm btn-outline-primary" type="submit"><i class="bi bi-save"></i></button>
                                            </div>
                                            <input type="file" name="file_bukti_setor" class="form-control form-control-sm" accept=".pdf,.jpg,.jpeg,.png">
                                        </form>
                                    @else
                                        {{ $potongan->ntpn ?: '-' }}
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
