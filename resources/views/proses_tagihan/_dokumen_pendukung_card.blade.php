{{--
    Kartu daftar dokumen tagihan: semua file yang diunggah operator/vendor
    maupun yang di-generate sistem saat pembuatan tagihan.
    Data: $dokumenPendukung dari App\Support\TagihanDokumenPendukung.
    Khusus PERJALDIN: dokumen dikelompokkan per pegawai/peserta beserta
    info perjalanannya; dokumen non-peserta tetap tampil sebagai daftar umum.
    Dokumen rantai pencairan (SPP/SPM/NPI/SP2D) tidak masuk daftar ini —
    PDF-nya tersedia di kartu masing-masing pada seksi Alur Dokumen Pencairan.
--}}
@php
    $isPerjaldin = $tagihan->tipe_tagihan === 'PERJALDIN' && ($tagihan->detailPerjaldin?->isNotEmpty() ?? false);

    // Untuk perjaldin, dokumen per peserta dirender dari detailPerjaldin —
    // sisanya (arsip tagihan, pajak, dokumen sistem) tetap sebagai daftar umum.
    $dokumenUmum = $isPerjaldin
        ? $dokumenPendukung->reject(fn ($d) => ($d['source'] ?? null) === 'Perjaldin')->values()
        : $dokumenPendukung;

    $tipeMap = [
        'luar_kota' => 'Luar Kota',
        'dalam_kota_lebih_8_jam' => 'Dalam Kota > 8 Jam',
        'diklat' => 'Diklat',
    ];
@endphp
<div class="process-card mb-4">
    <div class="process-card-body">
        <div class="d-flex align-items-center justify-content-between gap-2 mb-3 pb-3 border-bottom border-light-subtle">
            <div class="d-flex align-items-center gap-3">
                <div class="doc-icon-tile" style="--tone: var(--tone-info); --tone-soft: var(--tone-info-soft); width:44px;height:44px;font-size:1.2rem;">
                    <i class="bi bi-folder2-open"></i>
                </div>
                <div>
                    <h6 class="mb-0 fw-bold text-dark">Dokumen Tagihan</h6>
                    <div class="text-secondary small">
                        @if($isPerjaldin)
                            Dokumen perjalanan dikelompokkan per pegawai peserta perjaldin.
                        @else
                            Seluruh dokumen yang diunggah atau di-generate saat pembuatan tagihan ini.
                        @endif
                    </div>
                </div>
            </div>
            <span class="badge bg-light text-secondary border rounded-pill px-3 py-2 fw-bold">
                {{ $dokumenPendukung->count() }} dokumen
            </span>
        </div>

        @if($dokumenPendukung->isEmpty() && ! $isPerjaldin)
            <div class="pt-locked">
                <i class="bi bi-folder-x fs-4"></i>
                <div class="small fw-semibold">Belum ada dokumen yang diunggah atau di-generate untuk tagihan ini.</div>
            </div>
        @else
            <div class="d-flex flex-column gap-3">

                {{-- ===== Grup per pegawai (khusus PERJALDIN) ===== --}}
                @if($isPerjaldin)
                    @foreach($tagihan->detailPerjaldin as $detail)
                        @php
                            $nama = $detail->nama_pegawai ?? $detail->pegawai?->nama_lengkap ?? 'Peserta';
                            $nip = $detail->nip ?? $detail->pegawai?->nip;
                            $inisial = collect(explode(' ', trim($nama)))->map(fn ($k) => mb_substr($k, 0, 1))->take(2)->implode('');
                            $tipeLabel = $tipeMap[$detail->tipe_perjalanan ?? ''] ?? null;

                            $uangHarianTotal = (float) ($detail->uang_harian ?? 0)
                                + (float) ($detail->uang_representasi ?? 0)
                                + (float) ($detail->uang_rapat ?? 0);

                            // [label, icon, path, nama file, nominal terkait (null = wajib tanpa nominal)]
                            $dokumenPegawai = collect([
                                ['Surat Tugas / SPT', 'bi-file-earmark-text-fill', $detail->spt_file_path, $detail->spt_file_name, null, 'spt_file_path'],
                                ['Tiket Perjalanan', 'bi-airplane-fill', $detail->tiket_file_path, $detail->tiket_file_name, (float) ($detail->biaya_tiket ?? 0), 'tiket_file_path'],
                                ['Bukti Transport', 'bi-bus-front-fill', $detail->transport_file_path, $detail->transport_file_name, (float) ($detail->biaya_transport ?? 0), 'transport_file_path'],
                                ['Bukti Penginapan', 'bi-building-fill', $detail->penginapan_file_path, $detail->penginapan_file_name, (float) ($detail->biaya_penginapan ?? 0), 'penginapan_file_path'],
                                ['Bukti Uang Harian', 'bi-wallet-fill', $detail->uang_harian_file_path, $detail->uang_harian_file_name, $uangHarianTotal, 'uang_harian_file_path'],
                            ])
                                // Tampilkan bila ada file-nya, atau biayanya terisi (jadi terlihat bukti yang kurang).
                                ->filter(fn ($d) => filled($d[2]) || $d[4] === null || $d[4] > 0)
                                ->values();

                            $jumlahFile = $dokumenPegawai->filter(fn ($d) => filled($d[2]))->count();
                        @endphp

                        <div class="border border-light-subtle rounded-4 overflow-hidden">
                            {{-- Header pegawai --}}
                            <div class="d-flex flex-wrap align-items-center gap-3 p-3" style="background: linear-gradient(135deg, #f8fafc, #eef2ff66);">
                                <div class="rounded-circle bg-primary bg-opacity-10 text-primary fw-bolder d-flex align-items-center justify-content-center flex-shrink-0"
                                     style="width: 42px; height: 42px; font-size: 0.9rem;">{{ strtoupper($inisial) ?: '?' }}</div>
                                <div class="flex-grow-1" style="min-width: 0;">
                                    <div class="fw-bold text-dark fs-7 text-truncate">{{ $nama }}</div>
                                    <div class="d-flex flex-wrap align-items-center gap-2 mt-1 text-secondary fs-8">
                                        @if($nip)
                                            <span class="font-monospace"><i class="bi bi-person-badge me-1"></i>{{ $nip }}</span>
                                        @endif
                                        @if($detail->provinsi?->provinsi || $detail->tujuan)
                                            <span><i class="bi bi-geo-alt me-1"></i>{{ $detail->tujuan ?: $detail->provinsi?->provinsi }}</span>
                                        @endif
                                        @if($detail->tgl_berangkat)
                                            <span><i class="bi bi-calendar-event me-1"></i>{{ \Carbon\Carbon::parse($detail->tgl_berangkat)->translatedFormat('d M Y') }}{{ $detail->lama_hari ? ' · ' . $detail->lama_hari . ' hari' : '' }}</span>
                                        @endif
                                        @if($tipeLabel)
                                            <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle rounded-pill fs-8">{{ $tipeLabel }}</span>
                                        @endif
                                    </div>
                                </div>
                                <span class="badge {{ $jumlahFile === $dokumenPegawai->count() ? 'bg-success-subtle text-success border-success-subtle' : 'bg-warning-subtle text-warning border-warning-subtle' }} border rounded-pill px-3 py-2 fw-bold flex-shrink-0">
                                    <i class="bi bi-paperclip me-1"></i>{{ $jumlahFile }}/{{ $dokumenPegawai->count() }} dokumen
                                </span>
                            </div>

                            {{-- Daftar dokumen pegawai --}}
                            <div class="p-2 d-flex flex-column gap-1">
                                {{-- Perincian Biaya Perjalanan Dinas (generate sistem, TTD basah) --}}
                                <div class="d-flex align-items-center gap-3 rounded-3 p-2 px-3" style="background: #f0f9ff;">
                                    <i class="bi bi-file-earmark-code-fill text-info fs-5 flex-shrink-0"></i>
                                    <div class="flex-grow-1" style="min-width: 0;">
                                        <div class="fw-semibold text-dark fs-7">Perincian Biaya Perjalanan Dinas</div>
                                        <div class="text-secondary fs-8">Lampiran SPD — dicetak untuk ditandatangani Bendahara, penerima, dan PPK.</div>
                                    </div>
                                    <span class="badge bg-info-subtle text-info border border-info-subtle rounded-pill fs-8 flex-shrink-0 d-none d-sm-inline"><i class="bi bi-cpu me-1"></i>Generate Sistem</span>
                                    <a href="{{ route('perjaldins.pdf-perincian', [$tagihan->id, $detail->id]) }}" target="_blank" class="btn btn-sm btn-light border rounded-pill fw-bold btn-pt-action flex-shrink-0">
                                        <i class="bi bi-eye"></i> Lihat
                                    </a>
                                </div>
                                @foreach($dokumenPegawai as [$dLabel, $dIcon, $dPath, $dName, $dAmount, $dField])
                                    <div class="d-flex align-items-center gap-3 rounded-3 p-2 px-3" style="background: #fcfcfe;">
                                        <i class="bi {{ $dIcon }} {{ filled($dPath) ? 'text-primary' : 'text-secondary opacity-50' }} fs-5 flex-shrink-0"></i>
                                        <div class="flex-grow-1" style="min-width: 0;">
                                            <div class="fw-semibold text-dark fs-7">{{ $dLabel }}</div>
                                            <div class="text-secondary fs-8 text-truncate">
                                                @if(filled($dPath))
                                                    {{ $dName ?: basename($dPath) }}
                                                @else
                                                    <span class="text-warning"><i class="bi bi-exclamation-circle me-1"></i>Belum dilampirkan</span>
                                                @endif
                                            </div>
                                        </div>
                                        @if($dAmount !== null && $dAmount > 0)
                                            <span class="fw-semibold text-dark font-monospace fs-8 flex-shrink-0 d-none d-sm-inline">Rp {{ number_format($dAmount, 0, ',', '.') }}</span>
                                        @endif
                                        @if(filled($dPath))
                                            <a href="{{ route('secure-file', ['tagihan-perjaldin', $detail->id, $dField]) }}" target="_blank" class="btn btn-sm btn-light border rounded-pill fw-bold btn-pt-action flex-shrink-0">
                                                <i class="bi bi-eye"></i> Lihat
                                            </a>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                @endif

                {{-- ===== Dokumen umum (arsip tagihan, pajak, dokumen sistem) ===== --}}
                @if($dokumenUmum->isNotEmpty())
                    @if($isPerjaldin)
                        <div class="text-secondary fs-8 fw-bold text-uppercase letter-spacing-1 mt-1">Dokumen Lainnya</div>
                    @endif
                    <style>
                        .dt-tile {
                            display: flex; align-items: center; gap: .7rem;
                            height: 100%; padding: .7rem .85rem;
                            border: 1px solid #eceef5; border-radius: 12px;
                            background: #fcfcfe; text-decoration: none;
                            transition: transform .18s, box-shadow .18s, border-color .18s;
                        }
                        a.dt-tile:hover {
                            transform: translateY(-2px);
                            border-color: var(--pt-primary, #4f46e5);
                            box-shadow: 0 12px 24px -16px rgba(79, 70, 229, .5);
                        }
                        a.dt-tile:hover .dt-open { opacity: 1; transform: translateX(0); }
                        .dt-ic {
                            width: 38px; height: 38px; border-radius: 10px; flex-shrink: 0;
                            display: flex; align-items: center; justify-content: center; font-size: 1.05rem;
                            background: var(--tone-indigo-soft, rgba(79,70,229,.1)); color: var(--pt-primary, #4f46e5);
                        }
                        .dt-ic.is-gen { background: var(--tone-info-soft, rgba(6,182,212,.1)); color: var(--tone-info, #06b6d4); }
                        .dt-name {
                            display: block; font-size: .8rem; font-weight: 600; color: #1e293b; line-height: 1.3;
                            display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
                            overflow-wrap: anywhere;
                        }
                        .dt-meta { display: block; font-size: .68rem; color: #94a3b8; margin-top: .15rem; }
                        .dt-meta .dot { display: inline-block; width: 6px; height: 6px; border-radius: 50%; vertical-align: middle; margin: 0 .25rem 1px .05rem; }
                        .dt-open {
                            margin-left: auto; flex-shrink: 0; color: var(--pt-primary, #4f46e5); font-size: .85rem;
                            opacity: 0; transform: translateX(-4px); transition: opacity .18s, transform .18s;
                        }
                        @media (prefers-reduced-motion: reduce) {
                            .dt-tile, a.dt-tile:hover, .dt-open { transition: none; transform: none; }
                            .dt-open { opacity: .6; }
                        }
                    </style>
                    <div class="row g-2">
                        @foreach($dokumenUmum as $dok)
                            <div class="col-sm-6 col-xl-4 d-flex">
                                @if($dok['url'])
                                    <a href="{{ $dok['url'] }}" target="_blank" class="dt-tile w-100" title="Lihat: {{ $dok['title'] }}">
                                @else
                                    <div class="dt-tile w-100" title="{{ $dok['title'] }}">
                                @endif
                                    <span class="dt-ic {{ $dok['is_generated'] ? 'is-gen' : '' }}">
                                        <i class="bi {{ $dok['is_generated'] ? 'bi-file-earmark-code-fill' : 'bi-file-earmark-arrow-up-fill' }}"></i>
                                    </span>
                                    <span style="min-width: 0;">
                                        <span class="dt-name">{{ $dok['title'] }}</span>
                                        <span class="dt-meta">
                                            {{ $dok['source'] ?: '—' }}
                                            <span class="dot" style="background: {{ $dok['is_generated'] ? '#06b6d4' : '#10b981' }};"></span>{{ $dok['is_generated'] ? 'Generate Sistem' : 'Diunggah' }}
                                        </span>
                                    </span>
                                    @if($dok['url'])
                                        <i class="bi bi-box-arrow-up-right dt-open"></i>
                                    @endif
                                @if($dok['url'])
                                    </a>
                                @else
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
            <div class="text-muted fs-8 mt-2">
                <i class="bi bi-info-circle me-1"></i>Dokumen SPP/SPM/NPI/SP2D tersedia pada kartu masing-masing di seksi Alur Dokumen Pencairan.
            </div>
        @endif
    </div>
</div>
