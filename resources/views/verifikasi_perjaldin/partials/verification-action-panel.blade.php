{{-- verification-action-panel.blade.php --}}
{{-- Variables: $tagihan, $userRole, $approveRoute, $revisiRoute --}}
@php
    $status = $tagihan->status;
    $isPpk = $userRole === 'PPK';
    $isBendahara = $userRole === 'Bendahara Pengeluaran';

    $canActPpk        = $isPpk && $status === 'PENDING_PPK';
    $canActBendahara  = $isBendahara && $status === 'PENDING_BENDAHARA';
    $canAct = $canActPpk || $canActBendahara;

    $panelClass = $canAct ? 'border-primary' : 'border-secondary';
    $headerClass = $canAct ? 'bg-primary text-white' : 'bg-light text-secondary';

    $revisiStatuses = ['REVISI_PPK','REVISI_BENDAHARA'];
    $lastRevisiLog = $tagihan->logs
        ->filter(fn($l) => in_array($l->status_baru, $revisiStatuses))
        ->sortByDesc('created_at')
        ->first();
@endphp

<div class="card border-2 {{ $panelClass }} shadow mb-4">
    <div class="card-header py-3 {{ $headerClass }}">
        <h6 class="mb-0 fw-bold">
            <i class="bi bi-shield-check me-2"></i>
            Panel Aksi Verifikasi
            @if($canAct)
                <span class="badge bg-white text-primary ms-2 small">Perlu Tindakan</span>
            @endif
        </h6>
    </div>
    <div class="card-body py-3">
        @if($canAct)
            {{-- Status Info --}}
            <div class="alert alert-primary border-0 py-2 mb-3 rounded-3 small">
                <i class="bi bi-info-circle me-1"></i>
                Dokumen ini menunggu verifikasi <strong>{{ $userRole }}</strong>.
                Anda adalah approver aktif untuk tahap ini.
            </div>

            {{-- Previous revision note if any --}}
            @if($lastRevisiLog)
                <div class="alert alert-warning border-start border-3 border-warning py-2 mb-3 small">
                    <i class="bi bi-exclamation-triangle me-1"></i>
                    <strong>Pernah direvisi:</strong> {{ $lastRevisiLog->catatan }}
                    <span class="text-muted ms-2">— {{ $lastRevisiLog->created_at->format('d M Y') }}</span>
                </div>
            @endif

            {{-- Form Approve --}}
            <form action="{{ $approveRoute }}" method="POST" id="formApprove"
                  onsubmit="return confirm('Apakah Anda yakin ingin menyetujui dokumen ini?')">
                @csrf
                <div class="mb-3">
                    <label class="form-label small fw-semibold text-muted">Catatan Persetujuan <span class="text-muted fw-normal">(opsional)</span></label>
                    <textarea name="catatan" class="form-control form-control-sm" rows="2" placeholder="Tambahkan catatan jika diperlukan..."></textarea>
                </div>
                <button type="submit" class="btn btn-success w-100 mb-2 fw-semibold">
                    <i class="bi bi-check-circle-fill me-2"></i>Setujui Dokumen
                </button>
            </form>

            <hr class="my-3">

            {{-- Form Revisi --}}
            <form action="{{ $revisiRoute }}" method="POST" id="formRevisi">
                @csrf
                <div class="mb-2">
                    <label class="form-label small fw-semibold text-muted">Catatan Revisi <span class="text-danger">*</span></label>
                    <textarea name="catatan_revisi" class="form-control form-control-sm" rows="3"
                              placeholder="Tuliskan apa yang perlu diperbaiki oleh Operator Perjaldin..."
                              id="catatanRevisiInput{{$tagihan->id}}"></textarea>
                </div>
                <button type="button" class="btn btn-warning w-100 mb-2 fw-semibold"
                        onclick="submitRevisi({{ $tagihan->id }})">
                    <i class="bi bi-arrow-counterclockwise me-2"></i>Minta Revisi
                </button>
            </form>

        @elseif($isPpk && $status === 'PENDING_BENDAHARA')
            <div class="text-center py-3">
                <i class="bi bi-check-circle-fill text-success fs-2 mb-2 d-block"></i>
                <p class="fw-semibold text-success mb-1">Anda sudah menyetujui dokumen ini.</p>
                <p class="text-muted small">Saat ini dokumen menunggu verifikasi <strong>Bendahara Pengeluaran</strong>.</p>
            </div>
        @elseif($isPpk && $status === 'DISETUJUI_PERJALDIN')
            <div class="text-center py-3">
                <i class="bi bi-check-all text-success fs-2 mb-2 d-block"></i>
                <p class="fw-semibold text-success mb-1">Dokumen telah disetujui penuh.</p>
                <p class="text-muted small">Verifikasi selesai oleh PPK dan Bendahara Pengeluaran.</p>
            </div>
        @elseif($isBendahara && !in_array($status, ['PENDING_BENDAHARA','DISETUJUI_PERJALDIN']))
            <div class="text-center py-3 text-muted">
                <i class="bi bi-lock fs-2 mb-2 d-block"></i>
                <p class="small">Dokumen belum berada pada tahap verifikasi Bendahara Pengeluaran.</p>
                <p class="small mb-0">Status saat ini: <strong>@include('verifikasi_perjaldin.partials.status-badge', ['status' => $status])</strong></p>
            </div>
        @elseif($status === 'DISETUJUI_PERJALDIN')
            <div class="text-center py-3">
                <i class="bi bi-check-all text-success fs-2 mb-2 d-block"></i>
                <p class="fw-semibold text-success mb-1">Verifikasi selesai.</p>
                <p class="text-muted small">Dokumen telah disetujui oleh semua pihak.</p>
            </div>
        @else
            <div class="text-center py-3 text-muted">
                <i class="bi bi-info-circle fs-2 mb-2 d-block"></i>
                <p class="small">Dokumen ini tidak berada pada tahap verifikasi Anda saat ini.</p>
            </div>
        @endif
    </div>
</div>

@push('script')
<script>
function submitRevisi(id) {
    const catatan = document.getElementById('catatanRevisiInput' + id).value.trim();
    if (!catatan) {
        alert('Catatan revisi wajib diisi.');
        return;
    }
    if (confirm('Dokumen akan dikembalikan ke Operator Perjaldin untuk direvisi. Lanjutkan?')) {
        document.getElementById('formRevisi').submit();
    }
}
</script>
@endpush
