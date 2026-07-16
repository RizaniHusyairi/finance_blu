@extends('layouts.app')
@section('title', 'Edit Tagihan Kontrak')

@push('css')
<style>
    @keyframes kfIn { from { opacity: 0; transform: translateY(14px); } to { opacity: 1; transform: translateY(0); } }
    .kf-reveal { opacity: 0; animation: kfIn .5s cubic-bezier(.22, 1, .36, 1) forwards; animation-delay: var(--d, 0s); }

    .kf-page-head {
        position: relative; overflow: hidden; border-radius: 1.25rem; padding: 1.5rem 1.75rem; color: #fff;
        background: linear-gradient(120deg, #78350f 0%, #d97706 55%, #fbbf24 100%);
        box-shadow: 0 16px 36px -16px rgba(217, 119, 6, .55);
    }
    .kf-page-head::before { content: ''; position: absolute; width: 240px; height: 240px; top: -120px; right: -50px; border-radius: 50%; background: rgba(255,255,255,.08); pointer-events: none; }
    .kf-head-chip { display: inline-flex; align-items: center; gap: .35rem; padding: .28rem .7rem; border-radius: 999px; background: rgba(255,255,255,.14); border: 1px solid rgba(255,255,255,.22); font-size: .7rem; font-weight: 700; }

    .kf-card { border: 1px solid #eef0f4; border-radius: 1.15rem; background: #fff; box-shadow: 0 2px 10px rgba(15,23,42,.04); padding: 1.25rem 1.4rem; }
    .kf-row { display: flex; justify-content: space-between; gap: 1rem; padding: .5rem 0; border-bottom: 1px dashed #eef0f4; font-size: .87rem; }
    .kf-row:last-child { border-bottom: 0; }
    .kf-row .k { color: #94a3b8; font-weight: 600; }
    .kf-row .v { font-weight: 700; color: #0f172a; text-align: right; font-variant-numeric: tabular-nums; }
    .kf-drop {
        position: relative; border: 2px dashed #dbe3f0; border-radius: 1rem; background: #fbfcff;
        padding: 1rem; display: flex; align-items: center; gap: .85rem; min-height: 82px;
        transition: border-color .2s ease, background .2s ease;
    }
    .kf-drop:hover { border-color: #d97706; background: #fffbeb; }
    .kf-drop.picked { border-style: solid; border-color: #10b981; background: #f0fdf9; }
    .kf-drop input[type="file"] { position: absolute; inset: 0; width: 100%; height: 100%; opacity: 0; cursor: pointer; }
    .kf-drop .t { font-weight: 700; font-size: .84rem; }
    .kf-drop .s { font-size: .72rem; color: #94a3b8; overflow-wrap: anywhere; }
</style>
@endpush

@section('content')
<div class="page-content">
    @php
        $terminM = $detail?->kontrakEksternalTermin;
        $kontrakM = $terminM?->kontrak;
    @endphp

    <div class="kf-page-head mb-4 kf-reveal" style="--d:.01s;">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 position-relative" style="z-index:1;">
            <div>
                <h5 class="fw-bold mb-1" style="letter-spacing:-.3px; color:#fff;">Edit Tagihan Kontrak</h5>
                <div style="color:rgba(255,255,255,.85); font-size:.85rem;">
                    {{ $tagihan->nomor_tagihan }} — hanya deskripsi &amp; dokumen pendukung yang dapat diubah; data kontrak dikelola di master.
                </div>
                <div class="d-flex flex-wrap gap-2 mt-2">
                    <span class="kf-head-chip"><i class="bi bi-hash"></i> {{ $detail?->nomor_surat_pesanan }}</span>
                    @if($detail?->termin_ke)<span class="kf-head-chip"><i class="bi bi-layers-half"></i> Termin {{ $detail->termin_ke }}/{{ $detail->total_termin }}</span>@endif
                </div>
            </div>
            <a href="{{ route('tagihan-kontrak-eksternal.show', $tagihan->id) }}" class="btn btn-light rounded-3 fw-bold">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
        </div>
    </div>

    @if($errors->any())
        <div class="alert alert-danger rounded-4 kf-reveal" style="--d:.03s;" data-sky-ignore>
            @foreach($errors->all() as $err)<div><i class="bi bi-exclamation-triangle-fill me-1"></i> {{ $err }}</div>@endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('tagihan-kontrak-eksternal.update', $tagihan->id) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        <div class="row g-4">
            <div class="col-lg-5">
                <div class="kf-card kf-reveal" style="--d:.06s;">
                    <h6 class="fw-bold mb-2"><i class="bi bi-journal-bookmark text-warning"></i> Data dari Master Kontrak</h6>
                    <div class="kf-row"><span class="k">Nama Pekerjaan</span><span class="v">{{ $detail?->nama_pekerjaan }}</span></div>
                    <div class="kf-row"><span class="k">Termin</span><span class="v">{{ $detail?->termin_ke }}/{{ $detail?->total_termin }} · {{ $terminM?->jenis_termin ?? 'PELUNASAN' }}</span></div>
                    <div class="kf-row"><span class="k">Bruto Termin</span><span class="v text-success">Rp {{ number_format((float) $tagihan->total_bruto, 0, ',', '.') }}</span></div>
                    @if((float) ($terminM?->potongan_angsuran_uang_muka ?? 0) > 0)
                        <div class="kf-row"><span class="k">Angsuran Uang Muka</span><span class="v" style="color:#b45309;">− Rp {{ number_format((float) $terminM->potongan_angsuran_uang_muka, 0, ',', '.') }}</span></div>
                    @endif
                    <div class="kf-row"><span class="k">Penyedia</span><span class="v">{{ $tagihan->pihak?->nama_pihak }}</span></div>
                    @if($kontrakM)
                        <div class="mt-3">
                            <a href="{{ route('kontrak-eksternal.show', $kontrakM->id) }}" class="btn btn-sm btn-light border rounded-3 fw-bold w-100">
                                <i class="bi bi-box-arrow-up-right"></i> Ubah data kontrak di halaman master
                            </a>
                        </div>
                    @endif
                </div>
            </div>

            <div class="col-lg-7">
                <div class="kf-card kf-reveal" style="--d:.1s;">
                    <h6 class="fw-bold mb-3"><i class="bi bi-pencil-square text-warning"></i> Data Tagihan</h6>
                    <div class="mb-3">
                        <label class="form-label fw-bold" style="font-size:.8rem;">Deskripsi</label>
                        <textarea name="deskripsi" rows="2" class="form-control rounded-3">{{ old('deskripsi', $tagihan->deskripsi) }}</textarea>
                    </div>

                    <label class="form-label fw-bold" style="font-size:.8rem;">Dokumen Pendukung Termin <span class="text-secondary fw-normal">(unggah untuk mengganti)</span></label>
                    <div class="row g-3">
                        @foreach([
                            'file_invoice' => ['Invoice', 'bi-receipt', $detail?->file_invoice],
                            'file_kwitansi' => ['Kwitansi', 'bi-cash-coin', $detail?->file_kwitansi],
                            'file_bast' => ['BAST / Serah Terima', 'bi-box-seam', $detail?->file_bast],
                        ] as $field => [$label, $icon, $sudahAda])
                            <div class="col-md-4">
                                <div class="kf-drop {{ $sudahAda ? 'picked' : '' }}" data-kf-drop>
                                    <input type="file" name="{{ $field }}" accept="application/pdf">
                                    <div>
                                        <div class="t"><i class="bi {{ $icon }} text-warning"></i> {{ $label }}</div>
                                        <div class="s" data-kf-filename>
                                            @if($sudahAda)<i class="bi bi-check-circle"></i> Sudah ada — pilih file untuk mengganti.@else PDF, klik/tarik ke sini.@endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <a href="{{ route('tagihan-kontrak-eksternal.show', $tagihan->id) }}" class="btn btn-light border rounded-3">Batal</a>
                        <button type="submit" class="btn btn-warning rounded-3 fw-bold px-4 text-white">
                            <i class="bi bi-save me-1"></i> Simpan Perubahan
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@push('script')
<script>
    document.querySelectorAll('[data-kf-drop]').forEach(function (zone) {
        const input = zone.querySelector('input[type="file"]');
        const nameEl = zone.querySelector('[data-kf-filename]');
        if (!input) return;
        input.addEventListener('change', function () {
            const file = this.files && this.files[0];
            if (file) {
                zone.classList.add('picked');
                if (nameEl) nameEl.innerHTML = '<i class="bi bi-check-circle"></i> ' + file.name;
            }
        });
    });
</script>
@endpush
