{{--
    Kartu Daftar Penerima Honorarium — hanya untuk tagihan tipe HONORARIUM.
    Data: $tagihan->detailHonorarium (model App\Models\DetailHonorarium).
    Menampilkan rincian per personel (honor bruto, PPh 21, netto, rekening,
    status bukti potong) beserta ringkasan total.
--}}
@php
    $penerima = $tagihan->detailHonorarium ?? collect();
    $totalHonor = (float) $penerima->sum('nilai_honor');
    $totalPph = (float) $penerima->sum('pph');
    $totalNetto = $totalHonor - $totalPph;
@endphp
<div class="process-card mb-4">
    <div class="process-card-body">
        <div class="d-flex align-items-center justify-content-between gap-2 mb-3 pb-3 border-bottom border-light-subtle">
            <div class="d-flex align-items-center gap-3">
                <div class="doc-icon-tile" style="--tone: var(--tone-amber); --tone-soft: var(--tone-amber-soft); width:44px;height:44px;font-size:1.2rem;">
                    <i class="bi bi-people-fill"></i>
                </div>
                <div>
                    <h6 class="mb-0 fw-bold text-dark">Daftar Penerima Honorarium</h6>
                    <div class="text-secondary small">Rincian honor per personel beserta potongan PPh 21 dan rekening tujuan.</div>
                </div>
            </div>
            <span class="badge bg-light text-secondary border rounded-pill px-3 py-2 fw-bold flex-shrink-0">
                <i class="bi bi-person-lines-fill me-1"></i>{{ $penerima->count() }} penerima
            </span>
        </div>

        @if($penerima->isEmpty())
            <div class="pt-locked">
                <i class="bi bi-person-x fs-4"></i>
                <div class="small fw-semibold">Belum ada data penerima honorarium pada tagihan ini.</div>
            </div>
        @else
            {{-- Ringkasan total --}}
            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <div class="bg-light rounded-3 border border-light-subtle p-3 h-100">
                        <div class="process-muted mb-1">Total Honor (Bruto)</div>
                        <div class="process-value">Rp {{ number_format($totalHonor, 0, ',', '.') }}</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="bg-light rounded-3 border border-light-subtle p-3 h-100">
                        <div class="process-muted mb-1">Total Potongan PPh 21</div>
                        <div class="process-value text-danger">- Rp {{ number_format($totalPph, 0, ',', '.') }}</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="bg-light rounded-3 border border-light-subtle p-3 h-100">
                        <div class="process-muted mb-1">Total Netto Diterima</div>
                        <div class="process-value text-success">Rp {{ number_format($totalNetto, 0, ',', '.') }}</div>
                    </div>
                </div>
            </div>

            {{-- Tabel penerima --}}
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 40px;">#</th>
                            <th>Personel</th>
                            <th>Jabatan / Pangkat</th>
                            <th>Rekening Tujuan</th>
                            <th class="text-end">Honor (Bruto)</th>
                            <th class="text-end">PPh 21</th>
                            <th class="text-end">Netto</th>
                            <th class="text-center">Bukti Potong</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($penerima as $i => $p)
                            @php
                                $netto = (float) $p->nilai_honor - (float) $p->pph;
                                $inisial = collect(explode(' ', trim($p->nama_personel ?? '')))
                                    ->map(fn ($k) => mb_substr($k, 0, 1))->take(2)->implode('');
                                $bupotTone = match (strtoupper($p->bupot_status ?? 'DRAFT')) {
                                    'TERBIT', 'PUBLISHED', 'FINAL' => ['bg-success-subtle text-success border-success-subtle', 'bi-patch-check-fill'],
                                    default => ['bg-secondary-subtle text-secondary border-secondary-subtle', 'bi-hourglass-split'],
                                };
                            @endphp
                            <tr>
                                <td class="text-secondary fw-semibold">{{ $i + 1 }}</td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="rounded-circle bg-warning bg-opacity-10 text-warning fw-bolder d-flex align-items-center justify-content-center flex-shrink-0"
                                             style="width: 38px; height: 38px; font-size: .82rem;">{{ strtoupper($inisial) ?: '?' }}</div>
                                        <div style="min-width: 0;">
                                            <div class="fw-bold text-dark fs-7">{{ $p->nama_personel ?? '-' }}</div>
                                            @if($p->nrp_nip)
                                                <div class="text-secondary fs-8 font-monospace"><i class="bi bi-person-badge me-1"></i>{{ $p->nrp_nip }}</div>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="text-dark fs-7">{{ $p->jabatan ?: '-' }}</div>
                                    @if($p->pangkat_korp)
                                        <div class="text-secondary fs-8">{{ $p->pangkat_korp }}</div>
                                    @endif
                                </td>
                                <td>
                                    @if($p->rekening)
                                        <div class="text-dark fs-7 font-monospace fw-semibold">{{ $p->rekening }}</div>
                                        <div class="text-secondary fs-8">{{ trim(($p->jenis_bank ?? '') . ' · ' . ($p->nama_rekening ?? ''), ' ·') ?: '-' }}</div>
                                    @else
                                        <span class="text-secondary fs-8">-</span>
                                    @endif
                                </td>
                                <td class="text-end fw-semibold text-dark font-monospace">Rp {{ number_format((float) $p->nilai_honor, 0, ',', '.') }}</td>
                                <td class="text-end text-danger font-monospace">- Rp {{ number_format((float) $p->pph, 0, ',', '.') }}</td>
                                <td class="text-end fw-bold text-success font-monospace">Rp {{ number_format($netto, 0, ',', '.') }}</td>
                                <td class="text-center">
                                    @if($p->nomor_bupot)
                                        <span class="badge {{ $bupotTone[0] }} border rounded-pill fs-8" title="{{ $p->nomor_bupot }}">
                                            <i class="bi {{ $bupotTone[1] }} me-1"></i>{{ $p->nomor_bupot }}
                                        </span>
                                    @else
                                        <span class="badge {{ $bupotTone[0] }} border rounded-pill fs-8">
                                            <i class="bi {{ $bupotTone[1] }} me-1"></i>{{ \Illuminate\Support\Str::title(strtolower($p->bupot_status ?? 'Draft')) }}
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="table-light">
                        <tr class="fw-bold">
                            <td colspan="4" class="text-end">TOTAL</td>
                            <td class="text-end font-monospace">Rp {{ number_format($totalHonor, 0, ',', '.') }}</td>
                            <td class="text-end text-danger font-monospace">- Rp {{ number_format($totalPph, 0, ',', '.') }}</td>
                            <td class="text-end text-success font-monospace">Rp {{ number_format($totalNetto, 0, ',', '.') }}</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @endif
    </div>
</div>
