@php
    $canEditCoa = auth()->user()?->hasAnyRole(['Operator BLU', 'Super Admin'])
        && $state['tagihanApproved']
        && ! $state['spp'];
@endphp

<div class="card process-card shadow-sm mb-3">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-start gap-2 mb-3">
            <div>
                <div class="process-section-title">COA</div>
                <div class="text-muted small">Operator BLU memilih item DIPA sebelum draft dibuat.</div>
            </div>
            <span class="badge {{ $state['coaDone'] ? 'bg-success' : 'bg-warning text-dark' }}">
                {{ $state['coaDone'] ? 'Lengkap' : 'Menunggu' }}
            </span>
        </div>

        @if($tagihan->tipe_tagihan === 'PERJALDIN')
            <form method="POST" action="{{ route('proses-tagihan.coa', $tagihan->id) }}">
                @csrf
                <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead>
                            <tr>
                                <th>Komponen</th>
                                <th>Nominal</th>
                                <th>COA</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($tagihan->komponenPerjaldin->where('total_nominal', '>', 0) as $komponen)
                                <tr>
                                    <td>{{ $komponen->nama_komponen }}</td>
                                    <td>Rp {{ number_format((float) $komponen->total_nominal, 0, ',', '.') }}</td>
                                    <td style="min-width: 260px;">
                                        @if($canEditCoa)
                                            <select name="coa[{{ $komponen->id }}]" class="form-select form-select-sm" required>
                                                <option value="">Pilih COA</option>
                                                @foreach($coaOptions as $item)
                                                    <option value="{{ $item->id }}" @selected((int) $komponen->dipa_revision_item_id === (int) $item->id)>
                                                        {{ $item->coa?->kode_mak_lengkap }} - Sisa Rp {{ number_format((float) $item->sisa_pagu, 0, ',', '.') }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        @else
                                            <div class="fw-semibold">{{ $komponen->dipaRevisionItem?->coa?->kode_mak_lengkap ?? '-' }}</div>
                                            <div class="text-muted small">{{ $komponen->dipaRevisionItem?->coa?->uraian ?? '' }}</div>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if($canEditCoa)
                    <button class="btn btn-primary" type="submit"><i class="bi bi-save me-1"></i>Simpan COA</button>
                @endif
            </form>
        @else
            <form method="POST" action="{{ route('proses-tagihan.coa', $tagihan->id) }}">
                @csrf
                @if($canEditCoa)
                    <select name="dipa_revision_item_id" class="form-select mb-3" required>
                        <option value="">Pilih COA</option>
                        @foreach($coaOptions as $item)
                            <option value="{{ $item->id }}" @selected((int) $tagihan->dipa_revision_item_id === (int) $item->id)>
                                {{ $item->coa?->kode_mak_lengkap }} - {{ \Illuminate\Support\Str::limit($item->coa?->uraian, 90) }} - Sisa Rp {{ number_format((float) $item->sisa_pagu, 0, ',', '.') }}
                            </option>
                        @endforeach
                    </select>
                    <button class="btn btn-primary" type="submit"><i class="bi bi-save me-1"></i>Simpan COA</button>
                @else
                    <div class="fw-semibold">{{ $tagihan->dipaRevisionItem?->coa?->kode_mak_lengkap ?? '-' }}</div>
                    <div class="text-muted small">{{ $tagihan->dipaRevisionItem?->coa?->uraian ?? 'COA belum dipilih.' }}</div>
                @endif
            </form>
        @endif
    </div>
</div>
