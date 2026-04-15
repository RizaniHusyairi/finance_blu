{{-- Partial: Komponen Biaya Card --}}
{{-- Variables: $komponen (TagihanPerjaldinKomponen), $budgetGroups (Collection), $tagihan --}}
@php
    $spp = $komponen->dokumenSpp;
    $hasSpp = !is_null($spp);
    $hasCoa = !is_null($komponen->dipa_revision_item_id);
    $canCreateSpp = $hasCoa && !$hasSpp && (float) $komponen->total_nominal > 0;
    $coaLabel = $komponen->dipaRevisionItem?->coa?->kode_mak_lengkap ?? null;
    $coaNama = $komponen->dipaRevisionItem?->coa?->nama_akun ?? null;
@endphp

<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white d-flex justify-content-between align-items-center py-2">
        <div>
            <h6 class="mb-0 fw-bold">
                <i class="bi bi-box me-1 text-primary"></i>{{ $komponen->nama_komponen }}
            </h6>
            <small class="text-muted">Kode: {{ $komponen->kode_komponen }}</small>
        </div>
        <span class="badge {{ $komponen->status_badge_class }} px-3">
            {{ $komponen->status_label }}
        </span>
    </div>

    <div class="card-body py-3">
        <div class="row g-3">
            {{-- Ringkasan Nominal --}}
            <div class="col-md-12">
                <div class="bg-light rounded p-3 text-center h-100">
                    <small class="text-muted d-block">Total Nominal</small>
                    <span class="fs-5 fw-bold text-primary">Rp {{ number_format($komponen->total_nominal, 0, ',', '.') }}</span>
                    <small class="text-muted d-block mt-1">{{ $komponen->jumlah_peserta }} peserta</small>
                </div>
            </div>

            {{-- Aksi SPP dan fitur COA dipindahkan secara konseptual. Operator Perjaldin hanya melihat rincian biaya. --}}
        </div>
    </div>
</div>
