@extends('layouts.app')
@section('title', 'Edit Tagihan Kontrak Eksternal')

@push('css')
<style>
    .kf-page-head {
        position: relative; overflow: hidden;
        border-radius: 1.25rem;
        padding: 1.5rem 1.75rem;
        color: #fff;
        background: linear-gradient(120deg, #78350f 0%, #d97706 50%, #fbbf24 100%);
        box-shadow: 0 16px 36px -16px rgba(217, 119, 6, .55);
    }
    .kf-page-head::before {
        content: '';
        position: absolute; width: 240px; height: 240px; top: -120px; right: -50px;
        border-radius: 50%; background: rgba(255, 255, 255, .1); pointer-events: none;
    }
    .kf-page-icon {
        width: 54px; height: 54px;
        display: inline-flex; align-items: center; justify-content: center;
        border-radius: 1rem; font-size: 1.5rem;
        background: rgba(255, 255, 255, .16); border: 1px solid rgba(255, 255, 255, .25);
    }
    .kf-head-chip {
        display: inline-flex; align-items: center; gap: .35rem;
        padding: .28rem .7rem; border-radius: 999px;
        background: rgba(255, 255, 255, .14); border: 1px solid rgba(255, 255, 255, .22);
        font-size: .7rem; font-weight: 700;
    }
</style>
@endpush

@section('content')
<div class="page-content">
    <div class="kf-page-head mb-4 kf-reveal" style="--d:.01s;">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 position-relative" style="z-index:1;">
            <div class="d-flex align-items-center gap-3">
                <span class="kf-page-icon"><i class="bi bi-pencil-square"></i></span>
                <div>
                    <h5 class="fw-bold mb-1" style="letter-spacing:-.3px;">Edit Tagihan Kontrak Eksternal</h5>
                    <div style="color:rgba(255,255,255,.85); font-size:.85rem;">
                        {{ $tagihan->nomor_tagihan }} — dapat diubah selama belum diajukan.
                    </div>
                    <div class="d-flex flex-wrap gap-2 mt-2">
                        <span class="kf-head-chip"><i class="bi bi-hash"></i> {{ $detail?->nomor_surat_pesanan }}</span>
                        <span class="kf-head-chip"><i class="bi bi-pencil"></i> Status: {{ str_replace('_', ' ', $tagihan->status) }}</span>
                    </div>
                </div>
            </div>
            <a href="{{ route('tagihan-kontrak-eksternal.show', $tagihan->id) }}" class="btn btn-light rounded-3 fw-bold">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
        </div>
    </div>

    @if($errors->any())
        <div class="alert alert-danger rounded-4 kf-reveal" style="--d:.03s;" data-sky-ignore>
            <div class="fw-bold mb-1"><i class="bi bi-exclamation-triangle"></i> Periksa kembali isian Anda:</div>
            <ul class="mb-0">
                @foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('tagihan-kontrak-eksternal.update', $tagihan->id) }}" enctype="multipart/form-data" data-kf-form>
        @csrf
        @method('PUT')
        @include('tagihan_kontrak_eksternal._form_fields')

        <div class="kf-submitbar mb-4 kf-reveal" style="--d:.3s;">
            <div class="kf-progress" id="kfProgress">
                <div class="lbl"><span><i class="bi bi-clipboard-check"></i> Kelengkapan form</span><span id="kfProgressPct">0%</span></div>
                <div class="track"><div class="fill" id="kfProgressFill"></div></div>
            </div>
            <div class="d-flex gap-2 ms-auto">
                <a href="{{ route('tagihan-kontrak-eksternal.show', $tagihan->id) }}" class="btn btn-light border rounded-3">Batal</a>
                <button type="submit" class="btn kf-btn-submit"><i class="bi bi-save me-1"></i> Simpan Perubahan</button>
            </div>
        </div>
    </form>
</div>
@endsection
