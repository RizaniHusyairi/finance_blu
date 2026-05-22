<tr>
    @php($mitraTagihan = $tagihan->mitra ?? $tagihan->mitraLegacy)
    <td class="text-center">{{ $loop->iteration }}</td>
    <td>
        <span class="fw-bold">{{ $tagihan->nomor_tagihan }}</span><br>
        @if($tagihan->nomor_kontrak)
        <small class="text-muted"><i class="bi bi-file-earmark-text me-1"></i>{{ $tagihan->nomor_kontrak }}</small>
        @endif
    </td>
    <td>
        <span class="fw-medium">{{ $mitraTagihan->nama_pihak ?? 'N/A' }}</span>
    </td>
    <td>
        <span class="fw-bold text-success">Rp {{ number_format($tagihan->total_tagihan, 0, ',', '.') }}</span><br>
        <small><i class="bi bi-calendar-event me-1"></i>{{ \Carbon\Carbon::parse($tagihan->tanggal_tagihan)->format('d M Y') }}</small>
    </td>
    <td>
        <span class="badge {{ match($tagihan->status) {
            'PUBLISHED', 'LUNAS', 'DISETUJUI' => 'bg-success',
            'DRAFT' => 'bg-secondary',
            'DITOLAK' => 'bg-danger',
            default => 'bg-warning text-dark',
        } }}">{{ str_replace('_', ' ', $tagihan->status) }}</span>
    </td>
    <td class="text-center">
        <div class="d-flex justify-content-center gap-1">
            <a href="{{ route('tagihan-jasa.show', $tagihan->id) }}" class="btn btn-sm btn-light text-info border shadow-sm" title="Detail">
                <i class="bi bi-search"></i> Detail
            </a>
            @if(in_array($tagihan->status, ['PUBLISHED', 'LUNAS', 'DISETUJUI', 'VERIFIKASI_KABANDARA']))
            <a href="{{ route('tagihan-jasa.pdf', $tagihan->id) }}" target="_blank" class="btn btn-sm btn-light text-danger border shadow-sm" title="Cetak PDF">
                <i class="bi bi-file-pdf"></i> PDF
            </a>
            @endif
        </div>
    </td>
</tr>
