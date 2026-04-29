<div class="modal fade" id="{{ $id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('tagihan.kontrak.upload-arsip', $tagihan->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="jenis_dokumen" value="{{ $jenis }}">
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold"><i class="bi bi-upload me-2"></i>{{ $title }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                @php
                    $isImage = str_contains($mimes, 'png') || str_contains($mimes, 'jpg');
                @endphp
                <div class="modal-body p-4">
                    <div class="alert alert-info border-0 small">
                        @if($isImage)
                            Unggah gambar RAB (Rincian Anggaran Biaya) yang akan ditampilkan pada PDF draft BAPP.
                        @else
                            Pastikan dokumen yang diunggah telah ditandatangani lengkap oleh para pihak terkait.
                        @endif
                    </div>
                    <label class="form-label fw-bold">Pilih File ({{ $isImage ? 'JPG / PNG' : 'PDF' }})</label>
                    <input type="file" name="file" class="form-control form-control-lg" accept="{{ $mimes }}" required>
                    <div class="form-text mt-2">Maksimal ukuran file: {{ $isImage ? '5MB' : '10MB' }}. Jika Anda mengunggah ulang, file sebelumnya akan diganti.</div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-cloud-upload me-1"></i>Unggah & Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
