{{-- verification-action-panel.blade.php --}}
{{-- Variables: $tagihan, $userRole, $approveRoute, $revisiRoute, $allRoleApprovals (optional) --}}
@php
    $status = $tagihan->status;
    $currentApproval = $currentApproval ?? null;
    $allRoleApprovals = $allRoleApprovals ?? [];
    $hasRoleSpecificActions = count($allRoleApprovals) > 0;
    $isDualRole = count($allRoleApprovals) > 1;
    $useRoleSpecificActions = $hasRoleSpecificActions && (!$currentApproval || $isDualRole);
    $canAct = $useRoleSpecificActions ? true : (bool) $currentApproval;

    $panelClass = $canAct ? 'border-primary' : 'border-secondary';
    $headerClass = $canAct ? 'bg-primary text-white' : 'bg-light text-secondary';

    $lastRevisiLog = $tagihan->logs
        ->filter(fn($l) => str_starts_with((string) $l->status_baru, 'REVISI_'))
        ->sortByDesc('created_at')
        ->first();

    $roleColorMap = [
        'PPSPM' => '#6610f2',
        'Koordinator Keuangan' => '#198754',
        'PPK' => '#0d6efd',
        'Bendahara Pengeluaran' => '#d63384',
        'Bendahara Penerimaan' => '#fd7e14',
        'Kasubbag' => '#0dcaf0',
    ];
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
        @if($useRoleSpecificActions)
            {{-- === ROLE-SPECIFIC MODE: Tampilkan tombol sesuai approval pending user === --}}
            <div class="alert alert-primary border-0 py-2 mb-3 rounded-3 small">
                <i class="bi {{ $isDualRole ? 'bi-people-fill' : 'bi-person-check-fill' }} me-1"></i>
                @if($isDualRole)
                    Anda memiliki <strong>{{ count($allRoleApprovals) }} peran verifikasi</strong> pada dokumen ini.
                    Silakan verifikasi masing-masing peran secara terpisah.
                @else
                    Dokumen ini masih menunggu verifikasi <strong>{{ $allRoleApprovals[0]['label'] }}</strong>.
                @endif
            </div>

            {{-- Previous revision note if any --}}
            @if($lastRevisiLog)
                <div class="alert alert-warning border-start border-3 border-warning py-2 mb-3 small">
                    <i class="bi bi-exclamation-triangle me-1"></i>
                    <strong>Pernah direvisi:</strong> {{ $lastRevisiLog->catatan }}
                    <span class="text-muted ms-2">— {{ $lastRevisiLog->created_at->format('d M Y') }}</span>
                </div>
            @endif

            @foreach($allRoleApprovals as $idx => $roleApproval)
                @php
                    $roleColor = $roleColorMap[$roleApproval['label']] ?? '#6c757d';
                @endphp
                <div class="border rounded-4 p-3 mb-3" style="border-color: {{ $roleColor }}30 !important; background: {{ $roleColor }}08;">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <span class="badge rounded-pill px-3 py-2" style="background: {{ $roleColor }};">
                            {{ $roleApproval['label'] }}
                        </span>
                        <span class="badge bg-warning text-dark small">Perlu Tindakan</span>
                    </div>

                    {{-- Form Approve --}}
                    <form action="{{ $roleApproval['approveRoute'] }}" method="POST" id="formApprove{{ $idx }}"
                          onsubmit="return confirm('Apakah Anda yakin ingin menyetujui dokumen ini sebagai {{ $roleApproval['label'] }}?')">
                        @csrf
                        <input type="hidden" name="approval_id" value="{{ $roleApproval['approval']->id }}">
                        <div class="mb-2">
                            <textarea name="catatan" class="form-control form-control-sm" rows="1"
                                      placeholder="Catatan persetujuan {{ $roleApproval['label'] }} (opsional)..."></textarea>
                        </div>
                        <button type="submit" class="btn btn-success w-100 mb-2 fw-semibold btn-sm">
                            <i class="bi bi-check-circle-fill me-2"></i>Verifikasi ({{ $roleApproval['label'] }})
                        </button>
                    </form>

                    {{-- Form Revisi --}}
                    <form action="{{ $roleApproval['revisiRoute'] }}" method="POST" id="formRevisi{{ $idx }}">
                        @csrf
                        <input type="hidden" name="approval_id" value="{{ $roleApproval['approval']->id }}">
                        <div class="mb-2">
                            <textarea name="catatan_revisi" class="form-control form-control-sm" rows="1"
                                      placeholder="Catatan revisi {{ $roleApproval['label'] }}..."
                                      id="catatanRevisiInput{{ $tagihan->id }}_{{ $idx }}"></textarea>
                        </div>
                        <button type="button" class="btn btn-warning w-100 fw-semibold btn-sm"
                                onclick="submitRevisiDual({{ $tagihan->id }}, {{ $idx }})">
                            <i class="bi bi-arrow-counterclockwise me-2"></i>Minta Revisi ({{ $roleApproval['label'] }})
                        </button>
                    </form>
                </div>
            @endforeach

        @elseif($canAct)
            {{-- === SINGLE ROLE MODE (original behavior) === --}}
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

        @elseif($status === 'DISETUJUI_PERJALDIN')
            <div class="text-center py-3">
                <i class="bi bi-check-all text-success fs-2 mb-2 d-block"></i>
                <p class="fw-semibold text-success mb-1">Verifikasi selesai.</p>
                <p class="text-muted small">Dokumen telah disetujui seluruh verifikator dan Kasubbag.</p>
            </div>
        @else
            <div class="text-center py-3 text-muted">
                <i class="bi bi-info-circle fs-2 mb-2 d-block"></i>
                <p class="small">Dokumen ini tidak berada pada tahap verifikasi Anda saat ini.</p>
                <p class="small mb-0">Status saat ini: <strong>@include('verifikasi_perjaldin.partials.status-badge', ['status' => $status])</strong></p>
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
function submitRevisiDual(id, idx) {
    const catatan = document.getElementById('catatanRevisiInput' + id + '_' + idx).value.trim();
    if (!catatan) {
        alert('Catatan revisi wajib diisi.');
        return;
    }
    if (confirm('Dokumen akan dikembalikan ke Operator Perjaldin untuk direvisi. Lanjutkan?')) {
        document.getElementById('formRevisi' + idx).submit();
    }
}
</script>
@endpush
