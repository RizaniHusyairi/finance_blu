@php
    $sipandaiService = app(\App\Services\SipandaiReconciliationService::class);
    $sipandai = $sipandaiService->latest($penjualan);
    $sipandaiAirline = $sipandaiService->airline($penjualan);
    $sipandaiStale = $sipandai && ($sipandai->report_hash !== $sipandaiService->fingerprint($penjualan) || $sipandai->airline_code !== $sipandaiAirline);
    $canReconcile = auth()->user()?->hasAnyRole(['Super Admin', 'Super Admin Jasa', 'Koordinator Jasa', 'Admin Jasa'])
        && $penjualan->status === 'diajukan' && ! $penjualan->tagihan_jasa_id;
@endphp
<section class="card border-0 shadow-sm rounded-4 mb-4" id="rekonsiliasi-sipandai">
    <div class="card-header bg-white p-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h5 class="mb-0">Rekonsiliasi Penumpang</h5>
        @if($canReconcile)
            <form method="POST" action="{{ route('jasa.mitra.penjualan.sipandai.fetch', [$mitra, $penjualan]) }}">
                @csrf
                <button class="btn btn-primary" @disabled(!config('sipandai.enabled') || !$sipandaiAirline)>
                    <i class="bi bi-cloud-download me-1"></i>Ambil Data SIPANDAI
                </button>
            </form>
        @endif
    </div>
    <div class="card-body">
        @error('sipandai') <div class="alert alert-danger" role="alert">{{ $message }}</div> @enderror
        @error('note') <div class="alert alert-danger" role="alert">{{ $message }}</div> @enderror
        @error('snapshot_id') <div class="alert alert-danger" role="alert">{{ $message }}</div> @enderror
        @error('airline_code') <div class="alert alert-danger" role="alert">{{ $message }}</div> @enderror
        @error('confirmed') <div class="alert alert-danger" role="alert">{{ $message }}</div> @enderror
        @if(auth()->user()?->hasAnyRole(['Super Admin', 'Super Admin Jasa', 'Koordinator Jasa']))
            <details class="mb-3" @if(!$sipandaiAirline) open @endif>
                <summary class="text-primary">Pemetaan maskapai{{ $sipandaiAirline ? ': '.$sipandaiAirline : ' — perlu diisi' }}</summary>
                <form method="POST" action="{{ route('jasa.mitra.penjualan.sipandai.mapping', [$mitra, $penjualan]) }}" class="mt-2">
                    @csrf
                    <label for="sipandai-airline" class="form-label">Kode maskapai SIPANDAI untuk {{ $mitra->nama_mitra }}</label>
                    <div class="d-flex gap-2">
                        <input id="sipandai-airline" name="airline_code" value="{{ $sipandaiAirline }}" class="form-control text-uppercase" style="max-width:120px" minlength="2" maxlength="3" pattern="[A-Za-z0-9]{2,3}" placeholder="Contoh: GA" required>
                        <button class="btn btn-outline-primary">Simpan Pemetaan</button>
                    </div>
                    <div class="form-text">Berlaku untuk seluruh laporan mitra ini. Pastikan kode sesuai master penerbangan SIPANDAI.</div>
                </form>
            </details>
        @endif
        <p class="text-muted mb-2">Bandingkan jumlah hasil scan dengan laporan maskapai selama periode laporan (WITA). Jumlah laporan mencakup dewasa, anak, bayi, dan transit; jumlah yang ditagihkan tetap mengikuti laporan yang diverifikasi.</p>
        <div class="alert alert-warning py-2">Hasil scan merupakan data pembanding, belum menjadi bukti keberangkatan. Periksa penumpang batal berangkat, kategori penumpang, kemungkinan PNR bersama, dan kelengkapan sinkronisasi sebelum menyetujui.</div>
        @if(!config('sipandai.enabled') || !$sipandaiAirline)
            <p class="text-muted mb-0">Koneksi SIPANDAI atau pemetaan maskapai belum aktif. Hubungi pengelola aplikasi untuk mengatur koneksi.</p>
        @endif
        @if($sipandai)
            <div class="d-flex flex-wrap gap-3 mb-3 small">
                <span>Maskapai: <strong>{{ $sipandai->airline_code }}</strong></span>
                <span>Diambil {{ $sipandai->fetched_at->timezone('Asia/Makassar')->format('d/m/Y H:i') }} WITA oleh {{ $sipandai->fetchedBy?->name ?? 'Petugas' }}</span>
                <span class="badge {{ $sipandai->accepted_at && !$sipandaiStale ? 'bg-success' : 'bg-warning text-dark' }}">{{ $sipandaiStale ? 'Laporan berubah — ambil ulang' : ($sipandai->accepted_at ? 'Sudah direkonsiliasi' : 'Perlu pemeriksaan') }}</span>
            </div>
            <p class="small text-muted">Nomor penerbangan yang berulang dijumlahkan sepanjang periode. Tanda — berarti tidak ada data pada sumber tersebut, bukan nol penumpang.</p>
            <div class="table-responsive">
                <table class="table table-bordered align-middle">
                    <thead><tr><th>Penerbangan</th><th>Laporan maskapai</th><th>Scan SIPANDAI</th><th>Selisih scan − laporan</th><th>Status SIPANDAI</th></tr></thead>
                    <tbody>
                    @foreach($sipandai->comparison as $row)
                        <tr class="{{ $row['difference'] !== 0 || array_diff($row['statuses'], ['CLOSED']) ? 'table-warning' : '' }}">
                            <td>{{ $row['number'] }}</td>
                            <td>{{ $row['reported'] === null ? '—' : number_format($row['reported']) }}</td>
                            <td>{{ $row['scanned'] === null ? '—' : number_format($row['scanned']) }}</td>
                            <td>{{ $row['difference'] === null ? 'Data belum lengkap' : number_format($row['difference']) }}</td>
                            <td>{{ implode(', ', $row['statuses']) ?: 'Tidak ditemukan' }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            @if($sipandai->accepted_at)
                <p class="mb-1"><strong>Catatan rekonsiliasi:</strong> {{ $sipandai->note }}</p>
                <p class="small text-muted mb-0">Disimpan oleh {{ $sipandai->acceptedBy?->name ?? 'Petugas' }} pada {{ $sipandai->accepted_at->timezone('Asia/Makassar')->format('d/m/Y H:i') }} WITA.</p>
            @elseif($canReconcile && !$sipandaiStale)
                <form method="POST" action="{{ route('jasa.mitra.penjualan.sipandai.accept', [$mitra, $penjualan]) }}">
                    @csrf
                    <input type="hidden" name="snapshot_id" value="{{ $sipandai->id }}">
                    <label for="sipandai-note" class="form-label fw-semibold">Catatan pemeriksaan dan penjelasan selisih</label>
                    <textarea id="sipandai-note" name="note" class="form-control mb-2" required minlength="10" maxlength="2000" rows="3" placeholder="Jelaskan hasil pemeriksaan dan alasan jumlah pada laporan dapat digunakan.">{{ old('note') }}</textarea>
                    <div class="form-check mb-3">
                        <input type="checkbox" name="confirmed" value="1" id="sipandai-confirmed" class="form-check-input" required>
                        <label for="sipandai-confirmed" class="form-check-label">Saya telah memeriksa selisih dan kategori penumpang. Jumlah pada laporan maskapai dapat dilanjutkan ke verifikasi.</label>
                    </div>
                    <button class="btn btn-success">Simpan Rekonsiliasi</button>
                </form>
            @endif
        @elseif(config('sipandai.enabled') && $sipandaiAirline)
            <p class="mb-0">Belum ada data SIPANDAI untuk laporan ini. Ambil data untuk mulai membandingkan.</p>
        @endif
    </div>
</section>
