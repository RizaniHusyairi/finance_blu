@extends('layouts.app')
@section('title', 'Cetak Penyetoran Pajak Honorarium')

@push('css')
<style>
    @media print {
        .no-print { display: none !important; }
        .main-wrapper, .main-content { margin: 0 !important; padding: 0 !important; }
        body { background: #fff !important; }
    }
    .print-sheet { max-width: 760px; margin: 0 auto; background: #fff; border: 1px solid #e2e8f0; border-radius: .5rem; padding: 2.5rem; }
    .print-title { text-align: center; font-weight: 800; letter-spacing: .03em; }
    .pt-table th, .pt-table td { padding: .5rem .75rem; font-size: .85rem; border-bottom: 1px solid #eef2f7; }
    .pt-table th { text-align: left; color: #64748b; width: 38%; font-weight: 600; }
</style>
@endpush

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3 no-print">
        <x-page-title title="Cetak Penyetoran Pajak Honorarium" subtitle="Ringkasan setoran PPh 21" />
        <div class="d-flex gap-2">
            <a href="{{ route('pajak-potongan.honor.detail', $potongan->id) }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i> Kembali</a>
            <button onclick="window.print()" class="btn btn-primary btn-sm"><i class="bi bi-printer me-1"></i> Cetak</button>
        </div>
    </div>

    <div class="print-sheet">
        <div class="mb-4">
            <div class="print-title fs-5">BUKTI PENYETORAN PAJAK HONORARIUM (PPh 21)</div>
            <div class="text-center text-muted small">Kantor UPBU Aji Pangeran Tumenggung Pranoto</div>
        </div>

        <table class="pt-table w-100 mb-4">
            <tr><th>Nomor Tagihan</th><td>{{ $tagihan?->nomor_tagihan ?? '-' }}</td></tr>
            <tr><th>Uraian</th><td>{{ $tagihan?->deskripsi ?? '-' }}</td></tr>
            <tr><th>Nomor SP2D</th><td>{{ $sp2d?->nomor_sp2d ?? '-' }}</td></tr>
            <tr><th>Tanggal SP2D</th><td>{{ $sp2d?->tanggal_sp2d ? \Carbon\Carbon::parse($sp2d->tanggal_sp2d)->format('d M Y') : '-' }}</td></tr>
            <tr><th>Jenis Pajak</th><td>{{ $potongan->nama_pajak_snapshot ?? 'PPh 21' }}</td></tr>
            <tr><th>DPP</th><td>Rp {{ number_format($potongan->dpp, 0, ',', '.') }}</td></tr>
            <tr><th>Tarif</th><td>{{ $potongan->persentase_tarif_snapshot ? number_format($potongan->persentase_tarif_snapshot, 2) . '%' : '-' }}</td></tr>
            <tr><th>Nominal Disetor</th><td class="fw-bold">Rp {{ number_format($potongan->nominal_potongan, 0, ',', '.') }}</td></tr>
            <tr><th>Kode Billing</th><td class="font-monospace">{{ $potongan->kode_billing ?? '-' }}</td></tr>
            <tr><th>NTPN</th><td class="font-monospace">{{ $potongan->ntpn ?? '-' }}</td></tr>
        </table>

        <table class="pt-table w-100">
            <thead><tr><th style="width:auto;">Penerima</th><th class="text-end">Nilai Honor</th><th class="text-end">PPh 21</th></tr></thead>
            <tbody>
                @foreach($tagihan?->detailHonorarium ?? [] as $d)
                    <tr>
                        <td>{{ $d->nama_personel }} <span class="text-muted">({{ $d->nrp_nip ?? '-' }})</span></td>
                        <td class="text-end">Rp {{ number_format($d->nilai_honor, 0, ',', '.') }}</td>
                        <td class="text-end">Rp {{ number_format($d->pph, 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
