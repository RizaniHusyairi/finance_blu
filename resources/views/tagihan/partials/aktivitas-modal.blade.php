{{--
    Modal Lihat Aktivitas Tagihan Kontrak.
    Wrapper modal yang membungkus partial aktivitas-content.

    Variabel yang dibutuhkan:
      $tagihan (App\Models\Tagihan)
--}}

<div class="modal fade" id="modalAktivitasTagihan" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header modal-grad-primary">
                <h5 class="modal-title fw-bold">
                    <i class="bi bi-activity me-2"></i>Aktivitas Tagihan: {{ $tagihan->nomor_tagihan }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 bg-light">
                @include('tagihan.partials.aktivitas-content', ['tagihan' => $tagihan])
            </div>
            <div class="modal-footer bg-white">
                <small class="text-muted me-auto"><i class="bi bi-info-circle me-1"></i>Data ini diperbarui secara otomatis seiring proses berjalan.</small>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
