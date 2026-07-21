@extends('layouts.app')

@section('title', 'Edit DIPA: ' . $dipa->nomor_dipa)

@push('css')
@include('dipas._form_styles')
@endpush

@section('content')
<div class="dipa-form">

    @if ($errors->any())
        <div class="alert alert-danger border-0 bg-danger alert-dismissible fade show shadow-sm">
            <ul class="text-white mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- ════════ HERO ════════ --}}
    <div class="df-hero">
        <div class="mesh"></div>
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 position-relative" style="z-index:1;">
            <div>
                <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                    <span class="df-chip"><i class="bi bi-journal-bookmark-fill"></i> MASTER DATA</span>
                    <span class="df-chip"><i class="bi bi-hash"></i> {{ $dipa->nomor_dipa }}</span>
                </div>
                <h4>Edit Header DIPA ✏️</h4>
                <div class="sub">Perbarui informasi dokumen induk — pagu &amp; COA dikelola lewat revisi DIPA.</div>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('dipas.show', $dipa) }}" class="df-btn-back"><i class="bi bi-eye"></i> Detail</a>
                <a href="{{ route('dipas.index') }}" class="df-btn-back"><i class="bi bi-arrow-left"></i> Batal</a>
            </div>
        </div>
    </div>

    <form action="{{ route('dipas.update', $dipa) }}" method="POST" id="dipaEditForm">
        @csrf
        @method('PUT')

        <div class="row g-4">
            {{-- ════════ KOLOM FORM ════════ --}}
            <div class="col-lg-8">

                {{-- Step 1: Header --}}
                <div class="df-card mb-4" style="--d:.05s; --t:#4f46e5; --t2:#818cf8;">
                    <div class="df-card-head">
                        <span class="df-step">01</span>
                        <div>
                            <h6 class="df-card-title">Data Header DIPA</h6>
                            <div class="df-card-sub">Informasi utama dokumen induk DIPA.</div>
                        </div>
                    </div>
                    <div class="px-4 pb-4 pt-1">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Nomor DIPA</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-hash"></i></span>
                                    <input type="text" name="nomor_dipa" id="dfNomor" class="form-control"
                                           value="{{ old('nomor_dipa', $dipa->nomor_dipa) }}" required>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Tahun Anggaran</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-calendar3"></i></span>
                                    <input type="number" name="tahun_anggaran" id="dfTahun" class="form-control"
                                           value="{{ old('tahun_anggaran', $dipa->tahun_anggaran) }}" min="2000" max="2100" required>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Tanggal Disahkan</label>
                                <input type="date" name="tanggal_disahkan" id="dfTanggal" class="form-control"
                                       value="{{ old('tanggal_disahkan', optional($dipa->tanggal_disahkan)->format('Y-m-d')) }}" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Status Aktif</label>
                                <select name="status_aktif" id="dfStatus" class="form-select" required>
                                    <option value="1" {{ (string) old('status_aktif', $dipa->status_aktif ? '1' : '0') === '1' ? 'selected' : '' }}>Aktif</option>
                                    <option value="0" {{ (string) old('status_aktif', $dipa->status_aktif ? '1' : '0') === '0' ? 'selected' : '' }}>Nonaktif</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Info revisi (read-only) --}}
                <div class="df-card mb-4" style="--d:.12s; --t:#7c3aed; --t2:#a855f7;">
                    <div class="df-card-head">
                        <span class="df-step"><i class="bi bi-layers"></i></span>
                        <div>
                            <h6 class="df-card-title">Revisi &amp; Pagu (Info)</h6>
                            <div class="df-card-sub">Nilai pagu tidak diubah di sini — buat revisi DIPA baru bila pagu berubah.</div>
                        </div>
                        <a href="{{ route('dipas.revisions.create', $dipa) }}" class="df-btn df-btn-ghost text-decoration-none ms-auto" style="padding:.4rem 1rem; font-size:.78rem;">
                            <i class="bi bi-file-earmark-plus"></i> Tambah Revisi
                        </a>
                    </div>
                    <div class="px-4 pb-4 pt-1">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Revisi Aktif</label>
                                <input type="text" class="form-control" value="Revisi {{ $dipa->revisi_aktif_ke ?? 0 }}" readonly>
                            </div>
                            <div class="col-md-8">
                                <label class="form-label">Total Pagu Revisi Aktif</label>
                                <input type="text" class="form-control" value="Rp {{ number_format((float) optional($dipa->activeRevision)->total_pagu, 0, ',', '.') }}" readonly>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Aksi --}}
                <div class="df-card" style="--d:.18s;">
                    <div class="p-4 df-actions">
                        <a href="{{ route('dipas.index') }}" class="df-btn df-btn-ghost text-decoration-none">Batal</a>
                        <button type="submit" class="df-btn df-btn-primary js-df-submit">
                            <i class="bi bi-save"></i> Simpan Perubahan
                        </button>
                    </div>
                </div>
            </div>

            {{-- ════════ PRATINJAU LIVE ════════ --}}
            <div class="col-lg-4">
                <div class="df-preview">
                    <div class="df-doc">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi bi-journal-bookmark-fill fs-4" style="color:#a5b4fc;"></i>
                                <span class="fw-bold" style="letter-spacing:.08em; font-size:.78rem;">PRATINJAU DIPA</span>
                            </div>
                            <span class="df-doc-badge" id="pvStatus"><i class="bi bi-check-circle-fill"></i> AKTIF</span>
                        </div>

                        <div class="df-doc-lbl">Nomor DIPA</div>
                        <div class="df-doc-val mono mb-2" id="pvNomor">{{ $dipa->nomor_dipa }}</div>

                        <div class="row g-2">
                            <div class="col-6">
                                <div class="df-doc-lbl">Tahun Anggaran</div>
                                <div class="df-doc-val" id="pvTahun">{{ $dipa->tahun_anggaran }}</div>
                            </div>
                            <div class="col-6">
                                <div class="df-doc-lbl">Disahkan</div>
                                <div class="df-doc-val" id="pvTanggal">—</div>
                            </div>
                        </div>

                        <div class="df-doc-sep"></div>

                        <div class="df-doc-lbl">Total Pagu Revisi {{ $dipa->revisi_aktif_ke ?? 0 }}</div>
                        <div class="df-pagu">Rp {{ number_format((float) optional($dipa->activeRevision)->total_pagu, 0, ',', '.') }}</div>
                    </div>
                    <div class="df-hint mt-3 text-center">
                        <i class="bi bi-magic me-1"></i>Pratinjau terisi otomatis mengikuti isian formulir.
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@push('script')
<script>
document.addEventListener('DOMContentLoaded', function () {
    'use strict';
    var form = document.getElementById('dipaEditForm');
    if (!form) return;

    var bulan = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];

    function escapeHtml(s) {
        return s.replace(/[&<>"']/g, function (c) {
            return { '&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#39;' }[c];
        });
    }

    function syncPreview() {
        var nomor = document.getElementById('dfNomor').value.trim();
        document.getElementById('pvNomor').innerHTML = nomor
            ? escapeHtml(nomor)
            : '<span class="text-white-50">Belum diisi</span><span class="df-cursor"></span>';

        document.getElementById('pvTahun').textContent = document.getElementById('dfTahun').value || '—';

        var tgl = document.getElementById('dfTanggal').value;
        if (tgl) {
            var d = new Date(tgl + 'T00:00:00');
            document.getElementById('pvTanggal').textContent = d.getDate() + ' ' + bulan[d.getMonth()] + ' ' + d.getFullYear();
        } else {
            document.getElementById('pvTanggal').textContent = '—';
        }

        var aktif = document.getElementById('dfStatus').value === '1';
        var badge = document.getElementById('pvStatus');
        badge.classList.toggle('off', !aktif);
        badge.innerHTML = aktif
            ? '<i class="bi bi-check-circle-fill"></i> AKTIF'
            : '<i class="bi bi-pause-circle"></i> NONAKTIF';
    }

    ['dfNomor', 'dfTahun', 'dfTanggal', 'dfStatus'].forEach(function (id) {
        document.getElementById(id).addEventListener('input', syncPreview);
        document.getElementById(id).addEventListener('change', syncPreview);
    });
    syncPreview();

    form.addEventListener('submit', function (e) {
        var btn = e.submitter && e.submitter.classList.contains('js-df-submit') ? e.submitter : null;
        form.querySelectorAll('.js-df-submit').forEach(function (b) { b.disabled = true; });
        if (btn) {
            btn.style.minWidth = Math.ceil(btn.getBoundingClientRect().width) + 'px';
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Menyimpan…';
        }
    });
});
</script>
@endpush
