@extends('layouts.app')
@section('title', $konsesi->exists ? 'Edit Hak Kelola Konsesi' : 'Tambah Hak Kelola Konsesi')

@include('super_admin_jasa.partials.form-style')

@section('content')
<div class="jasa-form-hero mb-4 px-4 py-4">
    <div class="d-flex flex-column flex-lg-row gap-3 justify-content-between align-items-lg-center position-relative">
        <div class="d-flex gap-3 align-items-start">
            <span class="d-inline-flex align-items-center justify-content-center rounded-3 bg-white text-primary shadow-sm" style="width:44px;height:44px;">
                <i class="bi bi-percent fs-5"></i>
            </span>
            <div>
                <h4 class="mb-1 fw-black">{{ $konsesi->exists ? 'Edit Hak Kelola Konsesi' : 'Tambah Hak Kelola Konsesi' }}</h4>
                <p class="mb-0 fw-semibold small">{{ $mitra->nama_mitra }} - pengaturan layanan konsesi dan masa berlaku.</p>
            </div>
        </div>
        <a href="{{ route('jasa.mitra.show', $mitra) }}" class="btn btn-light text-primary fw-bold shadow-sm jasa-icon-btn" title="Kembali" aria-label="Kembali">
            <i class="bi bi-arrow-left"></i>
        </a>
    </div>
</div>

<form method="POST" id="konsesiForm" action="{{ $konsesi->exists ? route('jasa.mitra.konsesi.update', [$mitra, $konsesi]) : route('jasa.mitra.konsesi.store', $mitra) }}">
    @csrf
    @if($konsesi->exists)
        @method('PUT')
    @endif

    <div class="jasa-form-card">
        <div class="jasa-form-section">
            <div class="jasa-section-title">
                <span class="jasa-section-icon"><i class="bi bi-list-check"></i></span>
                <div>
                    <h6>Layanan dan Skema Konsesi</h6>
                    <p>Pilih item layanan, dokumen dasar, dan cara perhitungan konsesi.</p>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Layanan Jasa</label>
                <div class="border rounded-4 bg-light p-3" style="max-height: 400px; overflow-y: auto;">
                    @include('super_admin_jasa.mitra.partials.konsesi-layanan-tree', [
                        'layanans' => $layanans,
                        'selectedId' => old('layanan_jasa_id', $konsesi->layanan_jasa_id),
                        'parentId' => 'root',
                        'depth' => 0,
                    ])
                </div>
                @error('layanan_jasa_id')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                <div class="form-text">Pilih item layanan akhir yang akan dikelola oleh mitra.</div>
            </div>

            <div class="row g-3">
                <div class="col-lg-6">
                    <label class="form-label">Kontrak Dasar (Opsional)</label>
                    <select name="kontrak_mitra_jasa_id" class="form-select @error('kontrak_mitra_jasa_id') is-invalid @enderror">
                        <option value="">Tanpa Kontrak</option>
                        @foreach($kontraks as $kontrak)
                            @php
                                $scopeText = $kontrak->layananJasa->isEmpty() ? 'Semua layanan' : $kontrak->layananJasa->pluck('kode_layanan')->filter()->join(', ');
                            @endphp
                            <option value="{{ $kontrak->id }}" {{ old('kontrak_mitra_jasa_id', $konsesi->kontrak_mitra_jasa_id) == $kontrak->id ? 'selected' : '' }}>
                                {{ $kontrak->nomor_kontrak ?: 'Tanpa Nomor' }} ({{ $kontrak->nama_kontrak ?: 'Tanpa Nama' }}) - {{ $scopeText }}
                            </option>
                        @endforeach
                    </select>
                    @error('kontrak_mitra_jasa_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-lg-3">
                    <label class="form-label">Jenis Konsesi</label>
                    <select name="jenis_konsesi" id="jenis_konsesi" class="form-select @error('jenis_konsesi') is-invalid @enderror" required>
                        <option value="persen_omzet" {{ old('jenis_konsesi', $konsesi->jenis_konsesi ?? 'persen_omzet') == 'persen_omzet' ? 'selected' : '' }}>Persentase Omzet</option>
                        <option value="nilai_tetap" {{ old('jenis_konsesi', $konsesi->jenis_konsesi) == 'nilai_tetap' ? 'selected' : '' }}>Nilai Tetap</option>
                        <option value="minimum_guarantee" {{ old('jenis_konsesi', $konsesi->jenis_konsesi) == 'minimum_guarantee' ? 'selected' : '' }}>Minimum Guarantee</option>
                        <option value="kombinasi" {{ old('jenis_konsesi', $konsesi->jenis_konsesi) == 'kombinasi' ? 'selected' : '' }}>Kombinasi</option>
                    </select>
                    @error('jenis_konsesi')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-lg-3" id="wrap_persen">
                    <label class="form-label">Persentase (%)</label>
                    <input type="number" step="0.01" min="0" max="100" name="persentase_konsesi" id="input_persen" class="form-control bg-light @error('persentase_konsesi') is-invalid @enderror" value="{{ old('persentase_konsesi', $konsesi->persentase_konsesi) }}" readonly>
                    <div class="form-text">Diambil otomatis dari master layanan.</div>
                    @error('persentase_konsesi')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-lg-6" id="wrap_tetap" style="display:none;">
                    <label class="form-label">Nilai Tetap (Rp)</label>
                    <input type="number" name="nilai_tetap" class="form-control @error('nilai_tetap') is-invalid @enderror" value="{{ old('nilai_tetap', $konsesi->nilai_tetap) }}">
                    @error('nilai_tetap')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-lg-6" id="wrap_mag" style="display:none;">
                    <label class="form-label">Minimum Guarantee (Rp)</label>
                    <input type="number" name="nilai_minimum_guarantee" class="form-control @error('nilai_minimum_guarantee') is-invalid @enderror" value="{{ old('nilai_minimum_guarantee', $konsesi->nilai_minimum_guarantee) }}">
                    @error('nilai_minimum_guarantee')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>

        <div class="jasa-form-section">
            <div class="jasa-section-title">
                <span class="jasa-section-icon"><i class="bi bi-calendar-check"></i></span>
                <div>
                    <h6>Masa Berlaku dan Status</h6>
                    <p>Kontrol kapan hak kelola konsesi aktif.</p>
                </div>
            </div>

            <div class="row g-3">
                <input type="hidden" name="periode_pelaporan" value="bulanan">
                <div class="col-lg-4">
                    <label class="form-label">Tanggal Mulai Berlaku</label>
                    <input type="date" name="tanggal_mulai" class="form-control @error('tanggal_mulai') is-invalid @enderror" value="{{ old('tanggal_mulai', $konsesi->tanggal_mulai ? \Carbon\Carbon::parse($konsesi->tanggal_mulai)->format('Y-m-d') : '') }}" required>
                    @error('tanggal_mulai')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-lg-4">
                    <label class="form-label">Tanggal Selesai (Opsional)</label>
                    <input type="date" name="tanggal_selesai" class="form-control @error('tanggal_selesai') is-invalid @enderror" value="{{ old('tanggal_selesai', $konsesi->tanggal_selesai ? \Carbon\Carbon::parse($konsesi->tanggal_selesai)->format('Y-m-d') : '') }}">
                    @error('tanggal_selesai')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-lg-4">
                    <div class="jasa-check-card">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" id="status_aktif" name="status_aktif" value="1" {{ old('status_aktif', $konsesi->status_aktif ?? true) ? 'checked' : '' }}>
                            <label class="form-check-label fw-bold" for="status_aktif">Status Aktif</label>
                        </div>
                        <div class="form-text">Nonaktifkan jika hak kelola belum dipakai.</div>
                    </div>
                </div>
                <div class="col-12">
                    <label class="form-label">Keterangan Tambahan (Opsional)</label>
                    <textarea name="keterangan" class="form-control" rows="2" placeholder="Catatan tambahan untuk pengaturan konsesi">{{ old('keterangan', $konsesi->keterangan) }}</textarea>
                </div>
            </div>
        </div>

        <div class="jasa-action-footer">
            <div class="d-flex flex-column flex-sm-row justify-content-end gap-2">
                <a href="{{ route('jasa.mitra.show', $mitra) }}" class="btn btn-light fw-bold text-secondary border px-4">Batal</a>
                <button type="submit" class="btn btn-primary fw-bold px-4">
                    <i class="bi bi-save me-1"></i>Simpan Pengaturan
                </button>
            </div>
        </div>
    </div>
</form>
@endsection

@push('script')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const selJenis = document.getElementById('jenis_konsesi');
        const wrapPersen = document.getElementById('wrap_persen');
        const wrapTetap = document.getElementById('wrap_tetap');
        const wrapMag = document.getElementById('wrap_mag');

        function updateFields() {
            const v = selJenis.value;
            wrapPersen.style.display = (v === 'persen_omzet' || v === 'kombinasi') ? 'block' : 'none';
            wrapTetap.style.display = (v === 'nilai_tetap') ? 'block' : 'none';
            wrapMag.style.display = (v === 'minimum_guarantee' || v === 'kombinasi') ? 'block' : 'none';
        }

        selJenis.addEventListener('change', updateFields);
        updateFields();

        const root = document.querySelector('form[action*="konsesi"]');
        const inputPersen = document.getElementById('input_persen');

        if(root) {
            function openNode(node) {
                const childrenWrap = node.querySelector(':scope > .layanan-children');
                const toggle = node.querySelector(':scope > .d-flex .layanan-toggle');
                if (!childrenWrap || !toggle) return;

                childrenWrap.classList.remove('d-none');
                toggle.setAttribute('aria-expanded', 'true');
                toggle.querySelector('i')?.classList.remove('bi-caret-right-fill');
                toggle.querySelector('i')?.classList.add('bi-caret-down-fill');
            }

            function closeNode(node) {
                const childrenWrap = node.querySelector(':scope > .layanan-children');
                const toggle = node.querySelector(':scope > .d-flex .layanan-toggle');
                if (!childrenWrap || !toggle) return;

                childrenWrap.classList.add('d-none');
                toggle.setAttribute('aria-expanded', 'false');
                toggle.querySelector('i')?.classList.remove('bi-caret-down-fill');
                toggle.querySelector('i')?.classList.add('bi-caret-right-fill');
            }

            root.addEventListener('click', function (event) {
                const toggle = event.target.closest('.layanan-toggle');
                if (!toggle) return;

                const node = toggle.closest('.layanan-node');
                const childrenWrap = node?.querySelector(':scope > .layanan-children');
                if (!node || !childrenWrap) return;

                if (childrenWrap.classList.contains('d-none')) {
                    openNode(node);
                } else {
                    closeNode(node);
                }
            });

            root.addEventListener('change', function(event) {
                if(event.target.classList.contains('layanan-radio')) {
                    if(event.target.dataset.persen) {
                        inputPersen.value = parseFloat(event.target.dataset.persen);
                    }

                    root.querySelectorAll('.layanan-node > .d-flex').forEach(el => {
                        el.classList.remove('border-primary', 'bg-primary', 'bg-opacity-10');
                    });
                    const parentFlex = event.target.closest('.d-flex');
                    if(parentFlex) {
                        parentFlex.classList.add('border-primary', 'bg-primary', 'bg-opacity-10');
                    }
                }
            });

            root.querySelectorAll('.layanan-radio:checked').forEach(function (radio) {
                let node = radio.closest('.layanan-node')?.parentElement?.closest('.layanan-node');
                while (node) {
                    openNode(node);
                    node = node.parentElement?.closest('.layanan-node');
                }

                if(!inputPersen.value && radio.dataset.persen) {
                    inputPersen.value = parseFloat(radio.dataset.persen);
                }
            });

            // Submit-time validation: pastikan satu Item Tarif terpilih
            root.addEventListener('submit', function (event) {
                const checked = root.querySelector('.layanan-radio:checked');
                if (!checked) {
                    event.preventDefault();
                    alert('Pilih salah satu Item Tarif (layanan paling bawah) terlebih dahulu.');
                    root.querySelector('.layanan-radio')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    return false;
                }
            });
        }
    });
</script>
@endpush
