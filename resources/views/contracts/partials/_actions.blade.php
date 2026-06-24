@php $readyTerms = $kontrak->termin->where('status_termin', 'READY_TO_BILL')->values(); @endphp
<div class="action-bar-cell">
    <a href="{{ route('contracts.show', $kontrak->id) }}" class="btn-act btn-act-detail" title="Detail">
        <i class="bi bi-search"></i> Detail
    </a>
    <a href="{{ route('addendums.index', $kontrak->id) }}" class="btn-act btn-act-addm" title="Kelola Addendum">
        <i class="bi bi-journal-text"></i> Addm. <span>{{ $kontrak->addendums->count() }}</span>
    </a>
    @if(Auth::user()->hasAnyRole(['Super Admin', 'Pejabat Pengadaan']) && in_array($kontrak->status_kontrak, ['DRAFT', 'REVISI'], true))
        <a href="{{ route('contracts.edit', $kontrak->id) }}" class="btn-act btn-act-edit" title="{{ $kontrak->status_kontrak === 'REVISI' ? 'Perbaiki kontrak sesuai catatan PPK' : 'Edit Kontrak' }}">
            <i class="bi bi-pencil-square"></i> {{ $kontrak->status_kontrak === 'REVISI' ? 'Perbaiki' : 'Edit' }}
        </a>
    @endif
    @if(Auth::user()->hasRole('Pejabat Pengadaan') && $kontrak->status_kontrak === 'DRAFT')
        <form action="{{ route('contracts.destroy', $kontrak->id) }}" method="POST" class="d-inline m-0" onsubmit="return confirm('Yakin hapus draf kontrak ini? Arsip terkait akan ikut terhapus permanen.')">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn-act btn-act-delete" title="Hapus Draf">
                <i class="bi bi-trash3"></i>
            </button>
        </form>
    @endif
    @if($kontrak->status_kontrak == 'AKTIF')
        <button type="button" class="btn-act btn-act-tagih" title="{{ !$kontrak->hasVendorUploadedFinalDocs() ? 'SPK, SPMK, dan Ringkasan Kontrak harus disetujui vendor terlebih dahulu' : 'Buat Tagihan' }}"
                data-bs-toggle="modal" data-bs-target="#modalTagihKontrak{{ $kontrak->id }}"
                {{ $readyTerms->isEmpty() || !$kontrak->hasVendorUploadedFinalDocs() ? 'disabled' : '' }}>
            <i class="bi bi-cash-stack"></i> Tagih
        </button>
    @endif
</div>
