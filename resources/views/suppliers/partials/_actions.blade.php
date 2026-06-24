@php $rek = $supplier->rekening->first(); @endphp
<div class="btn-group shadow-sm">
    <button type="button" class="btn btn-sm btn-light text-primary" data-bs-toggle="modal" data-bs-target="#detailModal{{ $supplier->id }}" title="Detail">
        <i class="bi bi-eye"></i>
    </button>
    <a href="{{ route('suppliers.edit', $supplier->id) }}" class="btn btn-sm btn-light text-warning" title="Edit">
        <i class="bi bi-pencil"></i>
    </a>
    <form action="{{ route('suppliers.destroy', $supplier->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Peringatan: Menghapus data vendor juga akan menghapus/merusak relasi SPP yang sedang berjalan. Apakah Anda yakin ingin menghapus data ini?');">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn-sm btn-light text-danger" title="Hapus">
            <i class="bi bi-trash"></i>
        </button>
    </form>
</div>

{{-- Modal Detail --}}
<div class="modal fade" id="detailModal{{ $supplier->id }}" tabindex="-1" aria-labelledby="detailModalLabel{{ $supplier->id }}" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered text-start">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold" id="detailModalLabel{{ $supplier->id }}">
                    <i class="bi bi-building me-2 text-primary"></i>Detail Mitra/Vendor
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <h6 class="text-primary fw-bold mb-3 border-bottom pb-2">Identitas Utama</h6>
                <div class="row mb-3">
                    <div class="col-md-4 ">Nama Perusahaan</div>
                    <div class="col-md-8 fw-bold">{{ $supplier->nama_perusahaan }}</div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-4 ">Direktur / PIC</div>
                    <div class="col-md-8">{{ $supplier->nama_direktur ?: '-' }}</div>
                </div>

                <div class="row mb-4">
                    <div class="col-md-4 ">NPWP</div>
                    <div class="col-md-8 font-monospace">{{ $supplier->npwp ?: 'Belum Ada' }}</div>
                </div>

                <h6 class="text-info fw-bold mb-3 border-bottom pb-2">Kontak & Alamat</h6>
                <div class="row mb-3">
                    <div class="col-md-4 ">Email</div>
                    <div class="col-md-8">{{ $supplier->email ?: '-' }}</div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-4 ">No. Telepon</div>
                    <div class="col-md-8">{{ $supplier->no_telepon ?: '-' }}</div>
                </div>
                <div class="row mb-4">
                    <div class="col-md-4 ">Alamat Lengkap</div>
                    <div class="col-md-8">{{ $supplier->alamat ?: '-' }}</div>
                </div>

                <h6 class="text-success fw-bold mb-3 border-bottom pb-2">Rekening Bank Terdaftar</h6>
                @if($rek)
                    <div class="row mb-2">
                        <div class="col-md-4 ">Nama Bank</div>
                        <div class="col-md-8">{{ $rek->nama_bank }}</div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-md-4 ">Nomor Rekening</div>
                        <div class="col-md-8 font-monospace">{{ $rek->nomor_rekening }}</div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-md-4 ">Nama Pemilik (A.N.)</div>
                        <div class="col-md-8">{{ $rek->nama_rekening }}</div>
                    </div>
                @else
                    <p class=""><i class="bi bi-exclamation-triangle me-1"></i> Data rekening belum disetel untuk mitra ini.</p>
                @endif
            </div>
            <div class="modal-footer border-top-0 pt-0">
                <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
