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

@once
    @push('css')
    <style>
        .honor-card .honor-stat {
            position: relative;
            border-radius: 14px;
            padding: 1rem 1.1rem;
            height: 100%;
            background: var(--stat-soft, rgba(100,116,139,.08));
            border: 1px solid var(--stat-border, rgba(100,116,139,.18));
            overflow: hidden;
        }
        .honor-card .honor-stat::before {
            content: ''; position: absolute; inset: 0 auto 0 0; width: 4px;
            background: var(--stat-tone, #64748b);
        }
        .honor-card .honor-stat-label {
            display: flex; align-items: center; gap: .4rem;
            font-size: .72rem; font-weight: 700; letter-spacing: .04em;
            text-transform: uppercase; color: var(--pt-secondary, #64748b);
            margin-bottom: .35rem;
        }
        .honor-card .honor-stat-value { font-weight: 800; font-size: 1.25rem; line-height: 1.2; }

        /* Tabel: cegah kolom tergencet ketika sidebar mempersempit area.
           min-width memicu scroll horizontal alih-alih membungkus per huruf. */
        .honor-card .honor-table { min-width: 720px; margin-bottom: 0; }
        .honor-card .honor-table thead th {
            font-size: .68rem; letter-spacing: .05em; text-transform: uppercase;
            font-weight: 700; color: var(--pt-secondary, #64748b);
            border-bottom: 2px solid var(--pt-border, #e2e8f0);
            white-space: nowrap; padding-top: .65rem; padding-bottom: .65rem;
        }
        .honor-card .honor-table tbody td { padding-top: .8rem; padding-bottom: .8rem; vertical-align: middle; }
        .honor-card .honor-table tbody tr + tr td { border-top: 1px solid var(--pt-border, #eef2f7); }
        .honor-card .honor-table .col-num { white-space: nowrap; text-align: right; font-variant-numeric: tabular-nums; overflow-wrap: normal; }
        /* layout global memaksa overflow-wrap:anywhere pada td → nama pecah per huruf.
           Paksa balik ke pemenggalan per kata. */
        .honor-card .honor-name { font-weight: 700; color: var(--pt-ink, #0f172a); line-height: 1.25; overflow-wrap: normal; word-break: keep-all; }
        .honor-card .honor-nrp { white-space: nowrap; overflow-wrap: normal; }
        .honor-card .honor-avatar {
            width: 40px; height: 40px; flex-shrink: 0; border-radius: 11px;
            display: flex; align-items: center; justify-content: center;
            font-size: .78rem; font-weight: 800; letter-spacing: .02em;
            background: linear-gradient(135deg, var(--tone-amber, #f59e0b), #fbbf24);
            color: #fff; box-shadow: 0 4px 10px rgba(245,158,11,.28);
        }
        .honor-card .honor-table tfoot td {
            border-top: 2px solid var(--pt-border, #e2e8f0);
            background: rgba(248,250,252,.7);
            font-weight: 800; padding-top: .85rem; padding-bottom: .85rem;
        }
    </style>
    @endpush
@endonce

<div class="process-card honor-card mb-4">
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
                    <div class="honor-stat" style="--stat-tone: var(--tone-amber, #f59e0b); --stat-soft: var(--tone-amber-soft, rgba(245,158,11,.1)); --stat-border: rgba(245,158,11,.22);">
                        <div class="honor-stat-label"><i class="bi bi-cash-stack"></i>Total Honor (Bruto)</div>
                        <div class="honor-stat-value text-dark">Rp {{ number_format($totalHonor, 0, ',', '.') }}</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="honor-stat" style="--stat-tone: #ef4444; --stat-soft: rgba(239,68,68,.08); --stat-border: rgba(239,68,68,.2);">
                        <div class="honor-stat-label"><i class="bi bi-dash-circle"></i>Total Potongan PPh 21</div>
                        <div class="honor-stat-value text-danger">- Rp {{ number_format($totalPph, 0, ',', '.') }}</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="honor-stat" style="--stat-tone: #16a34a; --stat-soft: rgba(22,163,74,.08); --stat-border: rgba(22,163,74,.2);">
                        <div class="honor-stat-label"><i class="bi bi-wallet2"></i>Total Netto Diterima</div>
                        <div class="honor-stat-value text-success">Rp {{ number_format($totalNetto, 0, ',', '.') }}</div>
                    </div>
                </div>
            </div>

            {{-- Tabel penerima --}}
            <div class="table-responsive">
                <table class="table table-hover align-middle honor-table">
                    <thead>
                        <tr>
                            <th style="width: 36px;">#</th>
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
                                        <div class="honor-avatar">{{ strtoupper($inisial) ?: '?' }}</div>
                                        <div style="min-width: 0;">
                                            <div class="honor-name fs-7">{{ $p->nama_personel ?? '-' }}</div>
                                            @if($p->nrp_nip)
                                                <div class="text-secondary fs-8 font-monospace honor-nrp"><i class="bi bi-person-badge me-1"></i>{{ $p->nrp_nip }}</div>
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
                                        <div class="text-dark fs-7 font-monospace fw-semibold honor-nrp">{{ $p->rekening }}</div>
                                        <div class="text-secondary fs-8">{{ trim(($p->jenis_bank ?? '') . ' · ' . ($p->nama_rekening ?? ''), ' ·') ?: '-' }}</div>
                                    @else
                                        <span class="text-secondary fs-8">-</span>
                                    @endif
                                </td>
                                <td class="col-num fw-semibold text-dark font-monospace">Rp {{ number_format((float) $p->nilai_honor, 0, ',', '.') }}</td>
                                <td class="col-num text-danger font-monospace">- Rp {{ number_format((float) $p->pph, 0, ',', '.') }}</td>
                                <td class="col-num fw-bold text-success font-monospace">Rp {{ number_format($netto, 0, ',', '.') }}</td>
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
                    <tfoot>
                        <tr>
                            <td colspan="4" class="text-end">TOTAL</td>
                            <td class="col-num font-monospace">Rp {{ number_format($totalHonor, 0, ',', '.') }}</td>
                            <td class="col-num text-danger font-monospace">- Rp {{ number_format($totalPph, 0, ',', '.') }}</td>
                            <td class="col-num text-success font-monospace">Rp {{ number_format($totalNetto, 0, ',', '.') }}</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @endif
    </div>
</div>
