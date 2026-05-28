<div class="modal fade upload-modal" id="{{ $id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form action="{{ route('tagihan.kontrak.upload-arsip', $tagihan->id) }}" method="POST" enctype="multipart/form-data" class="modal-content">
            @csrf
            <input type="hidden" name="jenis_dokumen" value="{{ $jenis }}">
            @php
                $isImage = str_contains($mimes, 'png') || str_contains($mimes, 'jpg');
                $icon = $isImage ? 'bi-image-fill' : 'bi-file-earmark-pdf-fill';
                $illustGrad = $isImage ? 'linear-gradient(135deg, #0ea5e9 0%, #6366f1 50%, #8b5cf6 100%)' : 'linear-gradient(135deg, #6366f1 0%, #8b5cf6 50%, #ec4899 100%)';
            @endphp
            <div class="modal-hero" style="background: {{ $illustGrad }};">
                <i class="bi {{ $icon }} um-illust"></i>
                <button type="button" class="btn-close-um" data-bs-dismiss="modal" aria-label="Tutup"><i class="bi bi-x-lg"></i></button>
                <span class="um-tag"><i class="bi bi-stars"></i> Upload Dokumen</span>
                <h5 class="um-title">
                    <i class="bi {{ $icon }} me-1"></i>{{ $title }}
                </h5>
                <p class="um-sub">
                    <i class="bi bi-bookmark-check"></i>
                    Tagihan: <strong>{{ $tagihan->nomor_tagihan }}</strong>
                </p>
            </div>
            <div class="modal-body">
                <div class="um-banner">
                    <i class="bi bi-info-circle-fill"></i>
                    <div>
                        @if($isImage)
                            Unggah gambar RAB yang akan ditampilkan pada PDF draft BAPP.
                        @else
                            Pastikan dokumen yang diunggah telah ditandatangani lengkap oleh para pihak terkait.
                        @endif
                    </div>
                </div>

                <label class="um-label" for="file_{{ $id }}">
                    <i class="bi {{ $icon }} text-info"></i>
                    File Dokumen
                    <span class="text-danger ms-1">*</span>
                </label>
                <label class="um-drop" data-max-mb="{{ $isImage ? 5 : 10 }}" data-kind="{{ $isImage ? 'image' : 'pdf' }}">
                    <input type="file" id="file_{{ $id }}" name="file" accept="{{ $mimes }}" required>
                    <div class="ud-default">
                        <div class="ud-icon" style="background: linear-gradient(135deg, #38bdf8, #0ea5e9); box-shadow: 0 8px 20px rgba(14,165,233,.30);"><i class="bi bi-cloud-arrow-up-fill"></i></div>
                        <div class="ud-title">Tarik &amp; lepaskan, atau <strong>klik untuk memilih</strong></div>
                        <div class="ud-sub">Pilih file dari perangkat Anda.</div>
                        <div class="ud-meta" style="background: rgba(14,165,233,.10); color:#0369a1;"><i class="bi {{ $icon }}"></i> {{ $isImage ? 'JPG / PNG' : 'PDF' }} &middot; Maks {{ $isImage ? '5MB' : '10MB' }}</div>
                    </div>
                    <div class="ud-preview">
                        <div class="ud-fp-icon" style="background: linear-gradient(135deg, #38bdf8, #0ea5e9); box-shadow: 0 6px 14px rgba(14,165,233,.30);"><i class="bi bi-file-earmark-check-fill"></i></div>
                        <div class="ud-fp-info">
                            <div class="ud-fp-name">-</div>
                            <div class="ud-fp-detail">
                                <span class="ud-fp-size">0 KB</span>
                            </div>
                            <div class="ud-bar"><span style="width:0%"></span></div>
                        </div>
                        <button type="button" class="ud-fp-remove" title="Hapus berkas"><i class="bi bi-x-lg"></i></button>
                    </div>
                </label>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-um-cancel" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle me-1"></i> Batal
                </button>
                <button type="submit" class="btn-um-submit">
                    <i class="bi bi-cloud-arrow-up-fill"></i> Unggah & Simpan
                </button>
            </div>
        </form>
    </div>
</div>
