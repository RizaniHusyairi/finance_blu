@extends('layouts.app')
@section('title', 'e-Bupot 21 - ' . ($detail->nama_personel ?? ''))

@php
    $periode = $sp2d?->tanggal_sp2d ? \Carbon\Carbon::parse($sp2d->tanggal_sp2d) : now();
    $dpp = (float) $detail->nilai_honor;
    $pph = (float) $detail->pph;
    $tarif = $pajak?->persentase;
    $ntpnGabungan = $ntpnList && $ntpnList->isNotEmpty() ? $ntpnList->implode(', ') : '-';
    $pemotongNama = $pemotong?->name ?? 'Bendahara Pengeluaran';
    $pemotongNip = $pemotong?->pegawai?->nip ?? '-';
@endphp

@push('css')
<style>
    @media print {
        .no-print { display: none !important; }
        .main-wrapper, .main-content { margin: 0 !important; padding: 0 !important; }
        body { background: #fff !important; }
    }
    .bupot-sheet { max-width: 800px; margin: 0 auto; background: #fff; border: 1px solid #cbd5e1; padding: 2.5rem; position: relative; }
    .bupot-watermark { position: absolute; top: 42%; left: 50%; transform: translate(-50%,-50%) rotate(-24deg); font-size: 5rem; font-weight: 800; opacity: .07; letter-spacing: .1em; pointer-events: none; }
    .bupot-head { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #0f172a; padding-bottom: 1rem; margin-bottom: 1.25rem; }
    .bupot-title { font-weight: 800; font-size: 1.05rem; line-height: 1.3; }
    .bupot-no { font-family: ui-monospace, monospace; font-weight: 700; }
    .bupot-section { font-weight: 700; text-transform: uppercase; font-size: .72rem; letter-spacing: .08em; color: #475569; margin: 1.25rem 0 .5rem; }
    .bp-row { display: flex; padding: .35rem 0; font-size: .88rem; border-bottom: 1px dashed #eef2f7; }
    .bp-row .k { width: 42%; color: #64748b; }
    .bp-row .v { width: 58%; font-weight: 600; color: #1e293b; }
    .calc-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: .5rem; padding: 1rem 1.25rem; margin-top: 1rem; }
    .status-chip { display: inline-block; padding: .25rem .75rem; border-radius: 999px; font-size: .72rem; font-weight: 800; letter-spacing: .05em; }
    .chip-final { background: #d1fae5; color: #047857; }
    .chip-draft { background: #fef3c7; color: #a16207; }
</style>
@endpush

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3 no-print">
        <x-page-title title="e-Bupot 21" subtitle="Bukti Pemotongan PPh Pasal 21" />
        <div class="d-flex gap-2">
            <a href="{{ url()->previous() }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i> Kembali</a>
            <button onclick="window.print()" class="btn btn-primary btn-sm"><i class="bi bi-printer me-1"></i> Cetak</button>
        </div>
    </div>

    <div class="bupot-sheet shadow-sm">
        <div class="bupot-watermark">{{ $isFinal ? 'FINAL' : 'DRAFT' }}</div>

        <div class="bupot-head">
            <div>
                <div class="bupot-title">BUKTI PEMOTONGAN<br>PAJAK PENGHASILAN PASAL 21</div>
                <div class="text-muted small mt-1">Kantor UPBU Aji Pangeran Tumenggung Pranoto</div>
            </div>
            <div class="text-end">
                <div class="text-muted small">Nomor</div>
                <div class="bupot-no">{{ $detail->nomor_bupot ?? '(Belum final)' }}</div>
                <div class="mt-2">
                    <span class="status-chip {{ $isFinal ? 'chip-final' : 'chip-draft' }}">{{ $isFinal ? 'FINAL' : 'DRAFT' }}</span>
                </div>
            </div>
        </div>

        @unless($isFinal)
            <div class="alert alert-warning border-0 small no-print"><i class="bi bi-info-circle me-1"></i> Bukti potong ini masih DRAFT. Nomor resmi terbit otomatis setelah seluruh PPh 21 tagihan ini tersetor (NTPN lengkap).</div>
        @endunless

        <div class="bupot-section">A. Identitas Penerima Penghasilan</div>
        <div class="bp-row"><div class="k">Nama</div><div class="v">{{ $detail->nama_personel }}</div></div>
        <div class="bp-row"><div class="k">NIP / NRP</div><div class="v">{{ $detail->nrp_nip ?? '-' }}</div></div>
        <div class="bp-row"><div class="k">Pangkat / Golongan</div><div class="v">{{ $detail->pangkat_korp ?? '-' }}</div></div>
        <div class="bp-row"><div class="k">Jabatan</div><div class="v">{{ $detail->jabatan ?? '-' }}</div></div>

        <div class="bupot-section">B. Pajak Penghasilan yang Dipotong</div>
        <div class="bp-row"><div class="k">Jenis Pajak</div><div class="v">{{ $pajak?->jenis_pajak ?? 'PPh Pasal 21' }}</div></div>
        <div class="bp-row"><div class="k">Masa Pajak</div><div class="v">{{ $periode->locale('id')->isoFormat('MMMM Y') }}</div></div>
        <div class="calc-box">
            <div class="bp-row" style="border:none;"><div class="k">Dasar Pengenaan Pajak (DPP)</div><div class="v">Rp {{ number_format($dpp, 0, ',', '.') }}</div></div>
            <div class="bp-row" style="border:none;"><div class="k">Tarif</div><div class="v">{{ $tarif ? number_format($tarif, 2) . '%' : '-' }}</div></div>
            <div class="bp-row" style="border:none;"><div class="k fw-bold">PPh Pasal 21 Dipotong</div><div class="v fw-bold text-danger">Rp {{ number_format($pph, 0, ',', '.') }}</div></div>
        </div>

        <div class="bupot-section">C. Referensi Setoran</div>
        <div class="bp-row"><div class="k">Nomor SP2D</div><div class="v">{{ $sp2d?->nomor_sp2d ?? '-' }}</div></div>
        <div class="bp-row"><div class="k">NTPN</div><div class="v font-monospace">{{ $ntpnGabungan }}</div></div>
        <div class="bp-row"><div class="k">Nomor Tagihan</div><div class="v">{{ $tagihan?->nomor_tagihan ?? '-' }}</div></div>

        <div class="bupot-section">D. Identitas Pemotong</div>
        <div class="bp-row"><div class="k">Nama</div><div class="v">{{ $pemotongNama }}</div></div>
        <div class="bp-row"><div class="k">NIP</div><div class="v">{{ $pemotongNip }}</div></div>
        <div class="bp-row"><div class="k">Jabatan</div><div class="v">Bendahara Pengeluaran</div></div>

        <div class="text-end mt-4 pt-3" style="font-size:.85rem;">
            <div>Samarinda, {{ $periode->locale('id')->isoFormat('D MMMM Y') }}</div>
            <div class="mb-5">Bendahara Pengeluaran</div>
            <div class="fw-bold text-decoration-underline">{{ strtoupper($pemotongNama) }}</div>
            <div>NIP {{ $pemotongNip }}</div>
        </div>
    </div>
@endsection
