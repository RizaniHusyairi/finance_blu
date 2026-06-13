{{--
    Kartu Pajak & Faktur Pajak — khusus tagihan KONTRAK.
    Operator BLU memilih tipe pajak (potongan) dan mengunggah faktur pajak.
    Keduanya prasyarat sebelum draft SPP/SPM/NPI/SP2D dibuat.
--}}
@php
    $potonganPajakRows = $tagihan->potonganTagihan->where('jenis_potongan', 'PAJAK')->values();
    $sudahBilling = $potonganPajakRows->first(fn ($p) => filled($p->kode_billing) || filled($p->ntpn)) !== null;

    $fakturArsip = $tagihan->detailKontrak?->arsipDokumen
        ?->first(fn ($a) => $a->jenis_dokumen === 'FAKTUR_PAJAK' && $a->is_active)
        ?? $tagihan->detailKontrak?->arsipDokumen?->first(fn ($a) => $a->jenis_dokumen === 'FAKTUR_PAJAK');
    // url() memakai origin request aktif — Storage::url berbasis APP_URL bisa
    // salah host saat APP_URL tidak sama dengan alamat akses aplikasi.
    $fakturUrl = $fakturArsip
        ? url('storage/' . ltrim($fakturArsip->path_file, '/'))
        : null;

    $pajakDone = $state['pajakKontrakDone'];

    $isOperator = auth()->user()?->hasAnyRole(['Operator BLU', 'Super Admin']);

    // Boleh diedit selama belum ada rantai, atau rantai masih sepenuhnya draft/revisi.
    $chainEditable = ! $state['spp'] || ($state['chainStillDraft'] ?? false);

    $canEditPajak = $isOperator
        && $state['tagihanApproved']
        && $chainEditable
        && ! $sudahBilling;

    // Pajak belum lengkap tapi terkunci karena rantai sudah diajukan (tagihan lama).
    $terkunciKarenaRantai = $isOperator
        && $state['tagihanApproved']
        && ! $pajakDone
        && ! $chainEditable
        && ! $sudahBilling
        && ! $state['sp2dTerbit'];
@endphp

<div class="process-card mb-4 position-relative overflow-hidden">
    @if($pajakDone)
        <div class="position-absolute top-0 end-0 p-3 opacity-25" style="transform: scale(2) translate(10%, -10%); pointer-events: none;">
            <i class="bi bi-check-circle-fill text-success" style="font-size: 5rem;"></i>
        </div>
    @endif

    <div class="process-card-body position-relative z-index-1">
        @if($tagihan->chain_correction_target === 'PAJAK')
            {{-- data-sky-ignore: panel kontekstual, jangan diubah jadi toast oleh sky-alerts. --}}
            <div class="alert alert-warning border-warning-subtle d-flex gap-3 align-items-start mb-4" data-sky-ignore>
                <i class="bi bi-arrow-counterclockwise fs-4 flex-shrink-0"></i>
                <div class="small">
                    <div class="fw-bold">
                        Perbaikan pajak diminta oleh {{ $tagihan->chainCorrectionRequester?->name ?? 'verifikator' }}
                        @if($tagihan->chain_correction_requested_at)
                            <span class="fw-normal text-secondary">({{ \Carbon\Carbon::parse($tagihan->chain_correction_requested_at)->translatedFormat('d M Y H:i') }})</span>
                        @endif
                    </div>
                    <div class="mt-1" style="white-space: pre-line;">{{ $tagihan->chain_correction_note ?: 'Tanpa catatan.' }}</div>
                    <div class="text-secondary mt-2">
                        Rantai dokumen telah dibatalkan. Perbaiki tipe pajak / faktur pajak lalu klik
                        <strong>Simpan Pajak &amp; Faktur</strong> — setelah itu PPK mengajukan ulang persetujuan KPA
                        dan draft dokumen dibuat ulang otomatis.
                    </div>
                </div>
            </div>
        @endif

        <div class="d-flex justify-content-between align-items-start gap-3 mb-4 pb-3 border-bottom border-light-subtle">
            <div class="d-flex align-items-center gap-3">
                <div class="{{ $pajakDone ? 'bg-success' : 'bg-warning' }} bg-opacity-10 rounded p-2 d-flex align-items-center justify-content-center">
                    <i class="bi bi-receipt-cutoff fs-5 {{ $pajakDone ? 'text-success' : 'text-warning' }}"></i>
                </div>
                <div>
                    <h6 class="mb-1 fw-bold text-dark">Pajak &amp; Faktur Pajak Kontrak</h6>
                    <div class="text-secondary small">Operator BLU memilih tipe pajak dan mengunggah faktur pajak sebelum draft SPP/SPM/NPI dibuat.</div>
                </div>
            </div>
            <span class="badge {{ $pajakDone ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-warning-subtle text-warning border border-warning-subtle' }} rounded-pill px-3 py-2 fw-bold">
                @if($pajakDone)
                    <i class="bi bi-check-circle-fill me-1"></i> Lengkap
                @else
                    <i class="bi bi-hourglass-split me-1"></i> Menunggu Operator BLU
                @endif
            </span>
        </div>

        @if($canEditPajak)
            <form method="POST" action="{{ route('proses-tagihan.pajak-kontrak', $tagihan->id) }}" enctype="multipart/form-data" id="formPajakKontrak">
                @csrf

                {{-- Pilihan tipe pajak --}}
                <div class="fw-bold text-dark mb-1">Pilih Tipe Pajak <span class="text-danger">*</span></div>
                <div class="text-muted fs-8 mb-1">
                    <i class="bi bi-calculator me-1"></i>DPP otomatis = nilai bruto × 100/(100+PPN) — PPN dikeluarkan dari nilai tagihan.
                    Nominal pajak dibulatkan ke atas ke ratusan terdekat, mengikuti kalkulator pajak.
                </div>
                <div class="text-muted fs-8 mb-2">
                    <i class="bi bi-journal-bookmark me-1"></i>Sesuai PMK 59/2022: <strong>PPN &amp; PPh 22</strong> hanya dipungut bila pembayaran
                    <strong>&gt; Rp 2 juta</strong> (dan tidak dipecah-pecah); <strong>PPh 23 &amp; PPh 4(2)</strong> tanpa batas minimum.
                    PPh 22 disetor a.n. NPWP rekanan, lainnya a.n. NPWP instansi.
                </div>
                <div class="d-flex flex-column gap-2 mb-2" id="pjkRows">
                    @foreach($potonganPajakRows as $row)
                        @include('proses_tagihan._pajak_kontrak_row', [
                            'pajakOptions' => $pajakOptions,
                            'selectedTarifId' => $row->pajak_id,
                            'dppVal' => $row->dpp,
                            'nominalVal' => $row->nominal_potongan,
                        ])
                    @endforeach
                </div>
                <div class="mb-4">
                    <button type="button" id="pjkAddRow" class="btn btn-sm btn-light border rounded-pill fw-bold btn-pt-action">
                        <i class="bi bi-plus-circle text-primary"></i> Tambah Pajak
                    </button>
                    <span class="text-muted fs-8 ms-2">Nominal dihitung otomatis (DPP × tarif, dibulatkan ke atas ke ratusan) dan dapat disesuaikan.</span>
                </div>

                {{-- Template baris kosong untuk tombol Tambah Pajak --}}
                <template id="pjkRowTemplate">
                    @include('proses_tagihan._pajak_kontrak_row', ['pajakOptions' => $pajakOptions])
                </template>

                {{-- Ringkasan potongan --}}
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 bg-light border border-light-subtle rounded-4 p-3 mb-4">
                    <div>
                        <div class="fs-8 fw-bold text-secondary text-uppercase">Estimasi Total Potongan Pajak</div>
                        <div class="fw-bolder text-danger fs-5">- Rp <span id="pjkTotal">0</span></div>
                    </div>
                    <div class="text-sm-end">
                        <div class="fs-8 fw-bold text-secondary text-uppercase">Estimasi Netto Dibayarkan</div>
                        <div class="fw-bolder text-success fs-5">Rp <span id="pjkNetto">{{ number_format((float) $tagihan->total_bruto, 0, ',', '.') }}</span></div>
                    </div>
                </div>

                {{-- Upload faktur pajak --}}
                <div class="pt-upload mb-4">
                    <div class="d-flex flex-wrap align-items-center gap-3">
                        <div class="bg-white rounded-circle d-flex align-items-center justify-content-center shadow-sm flex-shrink-0" style="width: 48px; height: 48px;">
                            <i class="bi bi-file-earmark-arrow-up fs-4 text-primary"></i>
                        </div>
                        <div class="flex-grow-1">
                            <label class="fw-bold text-dark mb-1 d-block" for="faktur_pajak">
                                Faktur Pajak @unless($fakturUrl)<span class="text-danger">*</span>@endunless
                            </label>
                            <input type="file" name="faktur_pajak" id="faktur_pajak" class="form-control form-control-sm" accept=".pdf,.jpg,.jpeg,.png" {{ $fakturUrl ? '' : 'required' }}>
                            <div class="text-muted fs-8 mt-1">PDF/JPG/PNG, maks. 5 MB. {{ $fakturUrl ? 'Mengunggah file baru akan menggantikan faktur sebelumnya.' : 'Wajib diunggah sebelum draft dokumen dibuat.' }}</div>
                        </div>
                        @if($fakturUrl)
                            <a href="{{ $fakturUrl }}" target="_blank" class="btn btn-sm btn-light border rounded-pill fw-bold btn-pt-action">
                                <i class="bi bi-eye"></i> Lihat Faktur Saat Ini
                            </a>
                        @endif
                    </div>
                </div>

                <div class="text-end">
                    <button class="btn btn-primary rounded-pill px-5 py-2 fw-bold shadow btn-pt-action" type="submit">
                        <i class="bi bi-save-fill"></i> Simpan Pajak &amp; Faktur
                    </button>
                </div>
            </form>
        @else
            {{-- Read only --}}
            <div class="d-flex flex-column gap-3">
                @if($terkunciKarenaRantai)
                    {{-- data-sky-ignore: panel kontekstual, jangan diubah jadi toast oleh sky-alerts. --}}
                    <div class="alert alert-warning pt-alert d-flex align-items-start gap-3 mb-0" data-sky-ignore>
                        <i class="bi bi-lock-fill fs-4 text-warning"></i>
                        <div class="flex-grow-1">
                            <div class="fw-bold text-dark mb-1">Pajak Terkunci — Dokumen Sudah Diajukan</div>
                            <div class="small text-dark opacity-75 mb-2">
                                Rantai dokumen pencairan tagihan ini dibuat/diajukan sebelum pajak diisi.
                                Untuk memilih tipe pajak dan mengunggah faktur:
                            </div>
                            <ol class="small text-dark opacity-75 mb-2 ps-3">
                                <li>Batalkan rantai dokumen dengan tombol di bawah.</li>
                                <li>Isi tipe pajak dan unggah faktur pajak.</li>
                                <li>Draft dokumen dibuat ulang otomatis dengan nominal netto setelah pajak.</li>
                            </ol>
                            <form method="POST" action="{{ route('proses-tagihan.batalkan-rantai', $tagihan->id) }}"
                                  onsubmit="return confirm('Batalkan seluruh rantai dokumen (SPP/SPM/NPI/SP2D) tagihan ini? Draft baru akan dibuat ulang setelah pajak diisi.');">
                                @csrf
                                <input type="hidden" name="alasan" value="Rantai dibatalkan untuk melengkapi tipe pajak & faktur pajak kontrak.">
                                <button type="submit" class="btn btn-sm btn-warning rounded-pill fw-bold btn-pt-action">
                                    <i class="bi bi-arrow-counterclockwise"></i> Batalkan Rantai &amp; Isi Pajak
                                </button>
                            </form>
                        </div>
                    </div>
                @endif

                @if($potonganPajakRows->isEmpty())
                    @unless($terkunciKarenaRantai)
                        <div class="pt-locked">
                            <i class="bi bi-hourglass fs-4"></i>
                            <div class="small fw-semibold">
                                @if(! $state['tagihanApproved'])
                                    Pajak dapat diatur Operator BLU setelah tagihan disetujui seluruh verifikator.
                                @else
                                    Operator BLU belum memilih tipe pajak untuk tagihan kontrak ini.
                                @endif
                            </div>
                        </div>
                    @endunless
                @else
                    <div class="table-responsive rounded-3 border border-light-subtle">
                        <table class="table align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-secondary fw-semibold fs-7 text-uppercase py-2 ps-3">Tipe Pajak</th>
                                    <th class="text-secondary fw-semibold fs-7 text-uppercase py-2">Tarif</th>
                                    <th class="text-secondary fw-semibold fs-7 text-uppercase py-2">KAP / KJS</th>
                                    <th class="text-secondary fw-semibold fs-7 text-uppercase py-2">DPP</th>
                                    <th class="text-secondary fw-semibold fs-7 text-uppercase py-2 pe-3 text-end">Nominal</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($potonganPajakRows as $row)
                                    <tr>
                                        <td class="ps-3 fw-semibold text-dark">{{ $row->nama_pajak_snapshot ?? $row->deskripsi }}</td>
                                        <td>{{ $row->persentase_tarif_snapshot !== null ? rtrim(rtrim(number_format((float) $row->persentase_tarif_snapshot, 2, ',', '.'), '0'), ',') . '%' : '-' }}</td>
                                        <td>
                                            @if($row->pajak?->kode_akun_pajak)
                                                <span class="font-monospace fs-8 fw-bold">{{ $row->pajak->kode_akun_pajak }}{{ $row->pajak->kode_jenis_setoran ? ' / ' . $row->pajak->kode_jenis_setoran : '' }}</span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>Rp {{ number_format((float) $row->dpp, 0, ',', '.') }}</td>
                                        <td class="pe-3 text-end fw-bold text-danger">- Rp {{ number_format((float) $row->nominal_potongan, 0, ',', '.') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                <div class="d-flex align-items-center gap-3 bg-light border border-light-subtle rounded-4 p-3">
                    <div class="bg-white rounded-circle d-flex align-items-center justify-content-center shadow-sm flex-shrink-0" style="width: 44px; height: 44px;">
                        <i class="bi bi-file-earmark-text fs-5 {{ $fakturUrl ? 'text-success' : 'text-secondary' }}"></i>
                    </div>
                    <div class="flex-grow-1">
                        <div class="fw-bold text-dark fs-7">Faktur Pajak</div>
                        @if($fakturUrl)
                            <div class="text-success fs-8 fw-bold"><i class="bi bi-check-circle-fill me-1"></i>Sudah diunggah</div>
                        @else
                            <div class="text-muted fs-8">Belum diunggah.</div>
                        @endif
                    </div>
                    @if($fakturUrl)
                        <a href="{{ $fakturUrl }}" target="_blank" class="btn btn-sm btn-light border rounded-pill fw-bold btn-pt-action">
                            <i class="bi bi-eye"></i> Lihat
                        </a>
                    @endif
                </div>
            </div>
        @endif
    </div>
</div>

@if($canEditPajak)
@push('script')
<script>
(function () {
    'use strict';
    var form = document.getElementById('formPajakKontrak');
    if (!form) return;

    var rowsBox = document.getElementById('pjkRows');
    var tpl = document.getElementById('pjkRowTemplate');
    var bruto = {{ (float) $tagihan->total_bruto }};
    var nonPajak = {{ (float) $tagihan->potonganTagihan->where('jenis_potongan', '!=', 'PAJAK')->sum('nominal_potongan') }};
    var fmt = new Intl.NumberFormat('id-ID');

    function selectedOpt(sel) {
        return sel.value ? sel.options[sel.selectedIndex] : null;
    }

    // Sesuai kalkulator pajak: DPP = bruto × 100/(100+PPN); nominal = ROUNDUP(DPP × tarif, -2).
    function ppnRate() {
        var rate = 11;
        rowsBox.querySelectorAll('.pjk-select').forEach(function (sel) {
            var o = selectedOpt(sel);
            if (o && (o.dataset.kode || '').toUpperCase().indexOf('PPN') === 0) {
                rate = parseFloat(o.dataset.persen) || rate;
            }
        });
        return rate;
    }
    function dppDefault() { return bruto * 100 / (100 + ppnRate()); }
    function roundUp100(x) { return Math.ceil(x / 100) * 100; }

    // Aktifkan/namai input sesuai tarif terpilih + tampilkan info KAP/rumus.
    function syncRow(row, recompute) {
        var sel = row.querySelector('.pjk-select');
        var dpp = row.querySelector('.pjk-dpp');
        var nom = row.querySelector('.pjk-nominal');
        var info = row.querySelector('.pjk-info');
        var o = selectedOpt(sel);

        if (!o) {
            dpp.disabled = nom.disabled = true;
            dpp.removeAttribute('name');
            nom.removeAttribute('name');
            info.classList.add('d-none');
            info.textContent = '';
            return;
        }

        dpp.disabled = nom.disabled = false;
        dpp.name = 'dpp[' + sel.value + ']';
        nom.name = 'nominal[' + sel.value + ']';

        var infoParts = [];
        if (o.dataset.kap) infoParts.push(o.dataset.kap);
        if (o.dataset.rumus) infoParts.push(o.dataset.rumus);
        info.textContent = infoParts.join(' — ');
        info.classList.toggle('d-none', infoParts.length === 0);

        if (recompute) {
            if (dpp.dataset.manual !== '1') {
                dpp.value = Math.round(dppDefault() * 100) / 100;
            }
            if (nom.dataset.manual !== '1') {
                nom.value = roundUp100((parseFloat(dpp.value) || 0) * (parseFloat(o.dataset.persen) || 0) / 100);
            }
        }
    }

    // Tarif yang sudah dipakai baris lain dinonaktifkan agar tidak dipilih ganda.
    function syncOptionDisabling() {
        var chosen = [];
        rowsBox.querySelectorAll('.pjk-select').forEach(function (s) { if (s.value) chosen.push(s.value); });
        rowsBox.querySelectorAll('.pjk-select').forEach(function (s) {
            Array.prototype.forEach.call(s.options, function (o) {
                if (!o.value) return;
                o.disabled = chosen.indexOf(o.value) !== -1 && o.value !== s.value;
            });
        });
    }

    function refreshAll(recompute) {
        rowsBox.querySelectorAll('.pjk-row').forEach(function (row) { syncRow(row, recompute); });
        syncOptionDisabling();

        var total = 0;
        rowsBox.querySelectorAll('.pjk-row').forEach(function (row) {
            if (!row.querySelector('.pjk-select').value) return;
            total += parseFloat(row.querySelector('.pjk-nominal').value) || 0;
        });
        document.getElementById('pjkTotal').textContent = fmt.format(Math.round(total + nonPajak));
        document.getElementById('pjkNetto').textContent = fmt.format(Math.round(Math.max(0, bruto - total - nonPajak)));
    }

    function addRow() {
        var node = tpl.content.firstElementChild.cloneNode(true);
        rowsBox.appendChild(node);
        refreshAll(false);
        return node;
    }

    document.getElementById('pjkAddRow').addEventListener('click', function () { addRow(); });

    rowsBox.addEventListener('change', function (e) {
        if (!e.target.classList.contains('pjk-select')) return;
        var row = e.target.closest('.pjk-row');
        // Ganti tipe pajak = hitung ulang default baris ini.
        row.querySelector('.pjk-dpp').dataset.manual = '';
        row.querySelector('.pjk-nominal').dataset.manual = '';
        // Pilihan PPN mengubah faktor ekstraksi DPP semua baris non-manual.
        refreshAll(true);
    });

    rowsBox.addEventListener('input', function (e) {
        var row = e.target.closest('.pjk-row');
        if (!row) return;
        if (e.target.classList.contains('pjk-dpp')) {
            e.target.dataset.manual = '1';
            var nom = row.querySelector('.pjk-nominal');
            var o = selectedOpt(row.querySelector('.pjk-select'));
            if (o && nom.dataset.manual !== '1') {
                nom.value = roundUp100((parseFloat(e.target.value) || 0) * (parseFloat(o.dataset.persen) || 0) / 100);
            }
            refreshAll(false);
        } else if (e.target.classList.contains('pjk-nominal')) {
            e.target.dataset.manual = '1';
            refreshAll(false);
        }
    });

    rowsBox.addEventListener('click', function (e) {
        var btn = e.target.closest('.pjk-remove');
        if (!btn) return;
        btn.closest('.pjk-row').remove();
        if (!rowsBox.querySelector('.pjk-row')) addRow(); // minimal satu baris
        refreshAll(true); // menghapus baris PPN mengubah default DPP baris lain
    });

    if (!rowsBox.querySelector('.pjk-row')) addRow();
    refreshAll(true);
})();
</script>
@endpush
@endif
