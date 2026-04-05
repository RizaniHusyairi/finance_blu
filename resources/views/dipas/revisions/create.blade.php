@extends('layouts.app')

@section('title', 'Form Revisi DIPA')

@php
    $paguSebelumnya = old('total_pagu', $summary['total_pagu_revisi_aktif']);
@endphp

@section('content')
    <x-page-title title="Form Revisi DIPA" subtitle="Buat revisi baru tanpa langsung mengubah revisi aktif yang sedang berjalan" />

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

    <form action="{{ route('dipas.revisions.store', $dipa) }}" method="POST" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="nomor_revisi" value="{{ $nextRevisionNumber }}">

        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
            <div>
                <h5 class="mb-1 fw-bold">Revisi Baru untuk {{ $dipa->nomor_dipa }}</h5>
                <p class="text-muted mb-0">Revisi baru akan dibuat sebagai draft nonaktif sampai diaktifkan manual dari halaman detail DIPA.</p>
            </div>
            <a href="{{ route('dipas.show', $dipa) }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Batal
            </a>
        </div>

        <div class="card shadow-sm border-0 rounded-4 mb-4">
            <div class="card-header bg-white border-bottom-0 pt-4 px-4">
                <h5 class="mb-1 fw-bold">Ringkasan DIPA Induk</h5>
                <p class="text-muted small mb-0">Informasi dasar DIPA yang sedang direvisi.</p>
            </div>
            <div class="card-body px-4 pb-4">
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="border rounded-4 p-3 h-100">
                            <div class="small text-muted mb-1">Nomor DIPA</div>
                            <div class="fw-bold fs-6">{{ $dipa->nomor_dipa }}</div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="border rounded-4 p-3 h-100">
                            <div class="small text-muted mb-1">Tahun Anggaran</div>
                            <div class="fw-bold">{{ $dipa->tahun_anggaran }}</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="border rounded-4 p-3 h-100">
                            <div class="small text-muted mb-1">Tanggal Disahkan</div>
                            <div class="fw-bold">{{ optional($dipa->tanggal_disahkan)->format('d M Y') ?? '-' }}</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="border rounded-4 p-3 h-100">
                            <div class="small text-muted mb-1">Status Aktif</div>
                            <span class="badge {{ $dipa->status_aktif ? 'bg-success' : 'bg-secondary' }}">{{ $dipa->status_aktif ? 'Aktif' : 'Nonaktif' }}</span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="border rounded-4 p-3 h-100 bg-light-primary">
                            <div class="small text-muted mb-1">Revisi Aktif Saat Ini</div>
                            <div class="fw-bold fs-5">Revisi {{ $summary['revisi_aktif_saat_ini'] }}</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="border rounded-4 p-3 h-100 bg-light-info">
                            <div class="small text-muted mb-1">Total Pagu Revisi Aktif</div>
                            <div class="fw-bold fs-5">Rp {{ number_format($summary['total_pagu_revisi_aktif'], 0, ',', '.') }}</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="border rounded-4 p-3 h-100 bg-light-success">
                            <div class="small text-muted mb-1">Jumlah Item Anggaran Revisi Aktif</div>
                            <div class="fw-bold fs-5">{{ number_format($summary['jumlah_item_anggaran_revisi_aktif']) }} Item</div>
                            <div class="small text-muted mt-1">{{ number_format($summary['jumlah_item_anggaran_aktif']) }} item berstatus aktif</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0 rounded-4 mb-4">
            <div class="card-header bg-white border-bottom-0 pt-4 px-4">
                <h5 class="mb-1 fw-bold">Form Revisi Baru</h5>
                <p class="text-muted small mb-0">Isi detail revisi baru. Revisi lama tetap aktif sampai Anda mengaktifkan revisi baru secara manual.</p>
            </div>
            <div class="card-body px-4 pb-4">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Nomor Revisi Baru</label>
                        <input type="text" class="form-control" value="{{ $nextRevisionNumber }}" readonly>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Status Revisi</label>
                        <input type="text" class="form-control" value="Draft Nonaktif" readonly>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Tanggal Revisi</label>
                        <input type="date" name="tanggal_revisi" class="form-control" value="{{ old('tanggal_revisi', now()->format('Y-m-d')) }}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Total Pagu Revisi</label>
                        <input type="number" step="0.01" min="0" name="total_pagu" id="total_pagu_revisi_baru" class="form-control" value="{{ old('total_pagu', $summary['total_pagu_revisi_aktif']) }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Dokumen Revisi DIPA (PDF)</label>
                        <input type="file" name="file_dokumen_dipa" class="form-control" accept=".pdf,application/pdf">
                        <div class="form-text">Opsional, format PDF maksimal 5MB.</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold d-block">Salin Item Anggaran</label>
                        <div class="form-check form-switch border rounded-4 px-3 py-3 mt-1">
                            <input class="form-check-input" type="checkbox" role="switch" id="salin_item_anggaran" name="salin_item_anggaran" value="1" {{ old('salin_item_anggaran', '1') ? 'checked' : '' }}>
                            <label class="form-check-label ms-2" for="salin_item_anggaran">
                                Salin item anggaran dari revisi aktif sebelumnya
                            </label>
                            <div class="small text-muted mt-1">Jika dicentang, semua item anggaran dari revisi aktif saat ini akan dikloning ke revisi baru.</div>
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Keterangan Revisi</label>
                        <textarea name="keterangan" class="form-control" rows="4" placeholder="Tambahkan alasan atau ringkasan perubahan revisi ini">{{ old('keterangan') }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0 rounded-4 mb-4">
            <div class="card-body p-4">
                <div class="row g-3 align-items-stretch">
                    <div class="col-md-4">
                        <div class="border rounded-4 p-3 h-100">
                            <div class="small text-muted mb-1">Total Pagu Revisi Sebelumnya</div>
                            <div class="fw-bold fs-5">Rp {{ number_format($summary['total_pagu_revisi_aktif'], 0, ',', '.') }}</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="border rounded-4 p-3 h-100">
                            <div class="small text-muted mb-1">Total Pagu Revisi Baru</div>
                            <div class="fw-bold fs-5 text-primary" id="preview_total_pagu_baru">Rp {{ number_format($paguSebelumnya, 0, ',', '.') }}</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="border rounded-4 p-3 h-100">
                            <div class="small text-muted mb-1">Selisih Pagu</div>
                            <div class="fw-bold fs-5" id="preview_selisih_pagu">Rp 0</div>
                            <div class="small text-muted mt-1">Selisih dihitung dari pagu revisi baru terhadap revisi aktif saat ini.</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-body p-4 d-flex justify-content-end flex-wrap gap-2">
                <a href="{{ route('dipas.show', $dipa) }}" class="btn btn-outline-secondary px-4">Batal</a>
                <button type="submit" name="redirect_action" value="save" class="btn btn-primary px-4">
                    <i class="bi bi-save me-1"></i> Simpan Revisi
                </button>
                <button type="submit" name="redirect_action" value="save_and_manage" class="btn btn-success px-4">
                    <i class="bi bi-arrow-right-circle me-1"></i> Simpan &amp; Kelola Item Anggaran
                </button>
            </div>
        </div>
    </form>
@endsection

@push('script')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const totalPaguInput = document.getElementById('total_pagu_revisi_baru');
        const totalBaruTarget = document.getElementById('preview_total_pagu_baru');
        const selisihTarget = document.getElementById('preview_selisih_pagu');
        const paguSebelumnya = {{ (float) $summary['total_pagu_revisi_aktif'] }};

        if (!totalPaguInput || !totalBaruTarget || !selisihTarget) {
            return;
        }

        const formatRupiah = (value) => {
            return 'Rp ' + new Intl.NumberFormat('id-ID', {
                minimumFractionDigits: 0,
                maximumFractionDigits: 0,
            }).format(value || 0);
        };

        const renderComparison = () => {
            const paguBaru = parseFloat(totalPaguInput.value) || 0;
            const selisih = paguBaru - paguSebelumnya;

            totalBaruTarget.textContent = formatRupiah(paguBaru);
            selisihTarget.textContent = formatRupiah(selisih);
            selisihTarget.classList.remove('text-success', 'text-danger', 'text-dark');

            if (selisih > 0) {
                selisihTarget.classList.add('text-success');
            } else if (selisih < 0) {
                selisihTarget.classList.add('text-danger');
            } else {
                selisihTarget.classList.add('text-dark');
            }
        };

        totalPaguInput.addEventListener('input', renderComparison);
        renderComparison();
    });
</script>
@endpush
