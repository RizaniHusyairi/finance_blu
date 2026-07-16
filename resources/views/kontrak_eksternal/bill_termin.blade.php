@extends('layouts.app')
@section('title', 'Buat Tagihan Termin')

@push('css')
<style>
    @keyframes kbIn { from { opacity: 0; transform: translateY(14px); } to { opacity: 1; transform: translateY(0); } }
    .kb-reveal { opacity: 0; animation: kbIn .5s cubic-bezier(.22, 1, .36, 1) forwards; animation-delay: var(--d, 0s); }

    .kb-head {
        position: relative; overflow: hidden; border-radius: 1.25rem; padding: 1.5rem 1.75rem; color: #fff;
        background: linear-gradient(120deg, #065f46 0%, #059669 55%, #34d399 100%);
        box-shadow: 0 16px 36px -16px rgba(5, 150, 105, .55);
    }
    .kb-head::before { content: ''; position: absolute; width: 240px; height: 240px; top: -120px; right: -50px; border-radius: 50%; background: rgba(255,255,255,.08); pointer-events: none; }
    .kb-chip { display: inline-flex; align-items: center; gap: .35rem; padding: .28rem .7rem; border-radius: 999px; background: rgba(255,255,255,.14); border: 1px solid rgba(255,255,255,.22); font-size: .7rem; font-weight: 700; }

    .kb-card { border: 1px solid #eef0f4; border-radius: 1.15rem; background: #fff; box-shadow: 0 2px 10px rgba(15,23,42,.04); padding: 1.25rem 1.4rem; }
    .kb-row { display: flex; justify-content: space-between; gap: 1rem; padding: .5rem 0; border-bottom: 1px dashed #eef0f4; font-size: .87rem; }
    .kb-row:last-child { border-bottom: 0; }
    .kb-row .k { color: #94a3b8; font-weight: 600; }
    .kb-row .v { font-weight: 700; color: #0f172a; text-align: right; font-variant-numeric: tabular-nums; }
    .kb-netto { border-radius: .9rem; background: linear-gradient(135deg, #ecfdf5, #f0fdfa); border: 1px dashed #6ee7b7; padding: .8rem 1rem; }
    .kb-drop {
        position: relative; border: 2px dashed #dbe3f0; border-radius: 1rem; background: #fbfcff;
        padding: 1rem; display: flex; align-items: center; gap: .85rem; min-height: 82px;
        transition: border-color .2s ease, background .2s ease;
    }
    .kb-drop:hover { border-color: #059669; background: #f0fdf9; }
    .kb-drop.picked { border-style: solid; border-color: #10b981; background: #f0fdf9; }
    .kb-drop input[type="file"] { position: absolute; inset: 0; width: 100%; height: 100%; opacity: 0; cursor: pointer; }
    .kb-drop .t { font-weight: 700; font-size: .84rem; }
    .kb-drop .s { font-size: .72rem; color: #94a3b8; overflow-wrap: anywhere; }
</style>
@endpush

@section('content')
<div class="page-content">
    @php $netto = round((float) $termin->nilai_bruto_termin - (float) $termin->potongan_angsuran_uang_muka, 2); @endphp

    <div class="kb-head mb-4 kb-reveal" style="--d:.02s;">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 position-relative" style="z-index:1;">
            <div>
                <h5 class="fw-bold mb-1" style="color:#fff;">Buat Tagihan — Termin {{ $termin->termin_ke }} / {{ $kontrak->termin()->count() }}</h5>
                <div style="color:rgba(255,255,255,.85); font-size:.85rem;">{{ $kontrak->nama_pekerjaan }}</div>
                <div class="d-flex flex-wrap gap-2 mt-2">
                    <span class="kb-chip"><i class="bi bi-hash"></i> {{ $kontrak->nomor_surat_pesanan }}</span>
                    <span class="kb-chip"><i class="bi bi-flag"></i> {{ $termin->keterangan_termin }} · {{ $termin->jenis_termin }}</span>
                </div>
            </div>
            <a href="{{ route('kontrak-eksternal.show', $kontrak->id) }}" class="btn btn-light rounded-3 fw-bold"><i class="bi bi-arrow-left"></i> Kembali</a>
        </div>
    </div>

    @if($errors->any())
        <div class="alert alert-danger rounded-4 kb-reveal" style="--d:.04s;">
            @foreach($errors->all() as $err)<div><i class="bi bi-exclamation-triangle-fill me-1"></i> {{ $err }}</div>@endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('kontrak-eksternal.termin.store-tagihan', [$kontrak->id, $termin->id]) }}" enctype="multipart/form-data">
        @csrf
        <div class="row g-4">
            <div class="col-lg-5">
                <div class="kb-card kb-reveal" style="--d:.08s;">
                    <h6 class="fw-bold mb-2"><i class="bi bi-calculator text-success"></i> Ringkasan Termin (dari kontrak)</h6>
                    <div class="kb-row"><span class="k">Bruto Termin ({{ rtrim(rtrim(number_format((float) $termin->persentase, 4, '.', ''), '0'), '.') }}%)</span><span class="v text-success">Rp {{ number_format((float) $termin->nilai_bruto_termin, 0, ',', '.') }}</span></div>
                    @if((float) $termin->potongan_angsuran_uang_muka > 0)
                        <div class="kb-row"><span class="k">Angsuran Uang Muka</span><span class="v" style="color:#b45309;">− Rp {{ number_format((float) $termin->potongan_angsuran_uang_muka, 0, ',', '.') }}</span></div>
                    @endif
                    <div class="kb-netto mt-2 d-flex justify-content-between align-items-center">
                        <span class="fw-bold" style="font-size:.85rem;">Netto (sebelum pajak)</span>
                        <span class="fw-bold text-success" style="font-size:1.05rem;">Rp {{ number_format($netto, 0, ',', '.') }}</span>
                    </div>
                    <hr class="my-3" style="border-color:#eef0f4;">
                    <div class="kb-row"><span class="k">Penyedia</span><span class="v">{{ $kontrak->vendor?->nama_pihak }}</span></div>
                    <div class="kb-row"><span class="k">Surat Pesanan</span><span class="v"><span class="text-success"><i class="bi bi-file-earmark-check"></i> Dari arsip kontrak</span></span></div>
                    <div class="kb-row"><span class="k">Verifikator</span><span class="v">Mengikuti kontrak (6 pejabat)</span></div>
                </div>
            </div>

            <div class="col-lg-7">
                <div class="kb-card kb-reveal" style="--d:.12s;">
                    <h6 class="fw-bold mb-3"><i class="bi bi-pencil-square text-success"></i> Data Tagihan</h6>
                    <div class="mb-3">
                        <label class="form-label fw-bold" style="font-size:.8rem;">Deskripsi (opsional)</label>
                        <textarea name="deskripsi" rows="2" class="form-control rounded-3" placeholder="Pembayaran {{ $kontrak->nama_pekerjaan }} — {{ $termin->keterangan_termin }}">{{ old('deskripsi') }}</textarea>
                    </div>

                    <label class="form-label fw-bold" style="font-size:.8rem;">Dokumen Pendukung Termin <span class="text-secondary fw-normal">(opsional)</span></label>
                    <div class="row g-3">
                        @foreach([
                            'file_invoice' => ['Invoice', 'bi-receipt'],
                            'file_kwitansi' => ['Kwitansi', 'bi-cash-coin'],
                            'file_bast' => ['BAST / Serah Terima', 'bi-box-seam'],
                        ] as $field => [$label, $icon])
                            <div class="col-md-4">
                                <div class="kb-drop" data-kb-drop>
                                    <input type="file" name="{{ $field }}" accept="application/pdf">
                                    <div>
                                        <div class="t"><i class="bi {{ $icon }} text-success"></i> {{ $label }}</div>
                                        <div class="s" data-kb-filename>PDF, klik/tarik ke sini.</div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <a href="{{ route('kontrak-eksternal.show', $kontrak->id) }}" class="btn btn-light border rounded-3">Batal</a>
                        <button type="submit" class="btn btn-success rounded-3 fw-bold px-4">
                            <i class="bi bi-receipt me-1"></i> Buat Draft Tagihan
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
    document.querySelectorAll('[data-kb-drop]').forEach(function (zone) {
        const input = zone.querySelector('input[type="file"]');
        const nameEl = zone.querySelector('[data-kb-filename]');
        if (!input) return;
        input.addEventListener('change', function () {
            const file = this.files && this.files[0];
            zone.classList.toggle('picked', !!file);
            if (nameEl) nameEl.innerHTML = file ? '<i class="bi bi-check-circle"></i> ' + file.name : 'PDF, klik/tarik ke sini.';
        });
    });
</script>
@endpush
