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

<div class="card action-panel-premium {{ $canAct ? 'action-panel-active' : 'action-panel-inactive' }} shadow mb-4">
    {{-- Header --}}
    <div class="card-header py-3 {{ $canAct ? 'bg-primary text-white' : 'bg-light text-secondary' }}" style="border-radius: 16px 16px 0 0 !important;">
        <div class="d-flex align-items-center justify-content-between">
            <h6 class="mb-0 fw-bold">
                <i class="bi bi-shield-check me-2"></i>
                Panel Aksi Verifikasi
            </h6>
            @if($canAct)
                <span class="badge bg-white text-primary rounded-pill px-3 py-1 small fw-semibold" style="animation: pulse-glow 2s infinite alternate;">
                    <i class="bi bi-bell-fill me-1" style="font-size: 0.65rem;"></i>Perlu Tindakan
                </span>
            @endif
        </div>
    </div>
    <div class="card-body py-3 px-3">
        @if($useRoleSpecificActions)
            {{-- === ROLE-SPECIFIC MODE: Tampilkan tombol sesuai approval pending user === --}}
            <div class="bg-primary-subtle bg-opacity-25 border border-primary-subtle border-opacity-30 rounded-3 p-3 mb-3">
                <div class="d-flex align-items-start gap-2">
                    <i class="bi {{ $isDualRole ? 'bi-people-fill' : 'bi-person-check-fill' }} text-primary mt-1"></i>
                    <div class="small">
                        @if($isDualRole)
                            Anda memiliki <strong>{{ count($allRoleApprovals) }} peran verifikasi</strong> pada dokumen ini.
                            Silakan verifikasi masing-masing peran secara terpisah.
                        @else
                            Dokumen ini masih menunggu verifikasi <strong>{{ $allRoleApprovals[0]['label'] }}</strong>.
                        @endif
                    </div>
                </div>
            </div>

            {{-- Previous revision note if any --}}
            @if($lastRevisiLog)
                <div class="border border-warning border-opacity-50 bg-warning bg-opacity-10 rounded-3 p-3 mb-3">
                    <div class="d-flex align-items-start gap-2">
                        <i class="bi bi-exclamation-triangle text-warning mt-1"></i>
                        <div class="small">
                            <strong>Pernah direvisi:</strong> {{ $lastRevisiLog->catatan }}
                            <span class="text-muted ms-1 font-mono-premium" style="font-size: 0.68rem;">— {{ $lastRevisiLog->created_at->format('d M Y') }}</span>
                        </div>
                    </div>
                </div>
            @endif

            @foreach($allRoleApprovals as $idx => $roleApproval)
                @php
                    $roleColor = $roleColorMap[$roleApproval['label']] ?? '#6c757d';
                @endphp
                <div class="border rounded-4 p-3 mb-3" style="border-color: {{ $roleColor }}40 !important; background: {{ $roleColor }}06;">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <span class="badge rounded-pill px-3 py-2 text-white fw-semibold" style="background: {{ $roleColor }};">
                            {{ $roleApproval['label'] }}
                        </span>
                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle small rounded-pill px-2 py-1">
                            <i class="bi bi-bell-fill me-1" style="font-size: 0.55rem;"></i>Perlu Tindakan
                        </span>
                    </div>

                    {{-- Form Approve --}}
                    <form action="{{ $roleApproval['approveRoute'] }}" method="POST" id="formApprove{{ $idx }}"
                          onsubmit="return confirm('Apakah Anda yakin ingin menyetujui dokumen ini sebagai {{ $roleApproval['label'] }}?')">
                        @csrf
                        <input type="hidden" name="approval_id" value="{{ $roleApproval['approval']->id }}">
                        <div class="mb-2">
                            <textarea name="catatan" class="form-control form-control-sm action-textarea" rows="1"
                                      placeholder="Catatan persetujuan {{ $roleApproval['label'] }} (opsional)..."></textarea>
                        </div>
                        <button type="submit" class="btn btn-approve-modern text-white w-100 mb-2 fw-semibold btn-sm rounded-3 py-2">
                            <i class="bi bi-check-circle-fill me-2"></i>Verifikasi ({{ $roleApproval['label'] }})
                        </button>
                    </form>

                    {{-- Form Revisi --}}
                    <form action="{{ $roleApproval['revisiRoute'] }}" method="POST" id="formRevisi{{ $idx }}">
                        @csrf
                        <input type="hidden" name="approval_id" value="{{ $roleApproval['approval']->id }}">
                        <div class="mb-2">
                            <textarea name="catatan_revisi" class="form-control form-control-sm action-textarea" rows="1"
                                      placeholder="Catatan revisi {{ $roleApproval['label'] }}..."
                                      id="catatanRevisiInput{{ $tagihan->id }}_{{ $idx }}"></textarea>
                        </div>
                        <button type="button" class="btn btn-revision-modern w-100 fw-semibold btn-sm rounded-3 py-2"
                                onclick="submitRevisiDual({{ $tagihan->id }}, {{ $idx }})">
                            <i class="bi bi-arrow-counterclockwise me-2"></i>Minta Revisi ({{ $roleApproval['label'] }})
                        </button>
                    </form>
                </div>
            @endforeach

        @elseif($canAct)
            {{-- === SINGLE ROLE MODE (original behavior) === --}}
            <div class="bg-primary-subtle bg-opacity-25 border border-primary-subtle border-opacity-30 rounded-3 p-3 mb-3">
                <div class="d-flex align-items-start gap-2">
                    <i class="bi bi-info-circle text-primary mt-1"></i>
                    <div class="small">
                        Dokumen ini menunggu verifikasi <strong>{{ $userRole }}</strong>.
                        Anda adalah approver aktif untuk tahap ini.
                    </div>
                </div>
            </div>

            {{-- Previous revision note if any --}}
            @if($lastRevisiLog)
                <div class="border border-warning border-opacity-50 bg-warning bg-opacity-10 rounded-3 p-3 mb-3">
                    <div class="d-flex align-items-start gap-2">
                        <i class="bi bi-exclamation-triangle text-warning mt-1"></i>
                        <div class="small">
                            <strong>Pernah direvisi:</strong> {{ $lastRevisiLog->catatan }}
                            <span class="text-muted ms-1 font-mono-premium" style="font-size: 0.68rem;">— {{ $lastRevisiLog->created_at->format('d M Y') }}</span>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Form Approve --}}
            <form action="{{ $approveRoute }}" method="POST" id="formApprove"
                  onsubmit="return confirm('Apakah Anda yakin ingin menyetujui dokumen ini?')">
                @csrf
                <div class="mb-3">
                    <label class="form-label small fw-bold text-muted">Catatan Persetujuan <span class="text-muted fw-normal">(opsional)</span></label>
                    <textarea name="catatan" class="form-control form-control-sm action-textarea" rows="2" placeholder="Tambahkan catatan jika diperlukan..."></textarea>
                </div>
                <button type="submit" class="btn btn-approve-modern text-white w-100 mb-2 fw-semibold rounded-3 py-2">
                    <i class="bi bi-check-circle-fill me-2"></i>Setujui Dokumen
                </button>
            </form>

            <div class="border-top my-3"></div>

            {{-- Form Revisi --}}
            <form action="{{ $revisiRoute }}" method="POST" id="formRevisi">
                @csrf
                <div class="mb-2">
                    <label class="form-label small fw-bold text-muted">Catatan Revisi <span class="text-danger">*</span></label>
                    <textarea name="catatan_revisi" class="form-control form-control-sm action-textarea" rows="3"
                              placeholder="Tuliskan apa yang perlu diperbaiki oleh Operator Perjaldin..."
                              id="catatanRevisiInput{{$tagihan->id}}"></textarea>
                </div>
                <button type="button" class="btn btn-revision-modern w-100 mb-2 fw-semibold rounded-3 py-2"
                        onclick="submitRevisi({{ $tagihan->id }})">
                    <i class="bi bi-arrow-counterclockwise me-2"></i>Minta Revisi
                </button>
            </form>

        @elseif($status === 'DISETUJUI_PERJALDIN')
            <div class="text-center py-4">
                <div class="rounded-circle bg-success bg-opacity-10 d-inline-flex align-items-center justify-content-center mb-3" style="width: 56px; height: 56px;">
                    <i class="bi bi-check-all text-success fs-3"></i>
                </div>
                <p class="fw-bold text-success mb-1">Verifikasi selesai.</p>
                <p class="text-muted small mb-0">Dokumen telah disetujui seluruh verifikator dan Kasubbag.</p>
            </div>
        @else
            <div class="text-center py-4 text-muted">
                <div class="rounded-circle bg-light d-inline-flex align-items-center justify-content-center mb-3" style="width: 56px; height: 56px;">
                    <i class="bi bi-info-circle text-secondary fs-3"></i>
                </div>
                <p class="small mb-1">Dokumen ini tidak berada pada tahap verifikasi Anda saat ini.</p>
                <p class="small mb-0">Status saat ini: @include('verifikasi_perjaldin.partials.status-badge', ['status' => $status])</p>
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
