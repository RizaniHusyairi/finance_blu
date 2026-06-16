@extends('layouts.app')
@section('title', 'Perubahan Tarif')

@push('css')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/css/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
<style>
    .td-card { border:1px solid rgba(15,23,42,.08); border-radius:18px; box-shadow:0 16px 40px rgba(15,23,42,.06); }
    .td-head { display:flex; align-items:center; justify-content:space-between; gap:12px; padding:14px 18px; border-bottom:1px solid #eef2f7; background:linear-gradient(90deg,#fff7ed,#fff); border-radius:18px 18px 0 0; }
    .td-badge { display:inline-flex; align-items:center; gap:.35rem; padding:5px 10px; border-radius:999px; font-size:11px; font-weight:800; }
    .td-aktif { color:#047857; background:#d1fae5; }
    .td-akan { color:#1d4ed8; background:#dbeafe; }
    .td-berakhir { color:#475569; background:#e2e8f0; }
    .td-nonaktif { color:#92400e; background:#fef3c7; }
    .td-form .form-label { font-weight:700; font-size:.82rem; color:#475569; }
    .td-form .select2-container--bootstrap-5 .select2-selection { min-height:42px; border-radius:.6rem; }
</style>
@endpush

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
    <div>
        <h4 class="mb-0 fw-bold"><i class="bi bi-tags me-2 text-warning"></i>Perubahan Tarif</h4>
        <p class="mb-0 small text-muted">Atur tarif diskon untuk periode tertentu. Setelah periode berakhir, tarif otomatis kembali normal.</p>
    </div>
    <a href="{{ url()->previous() }}" class="btn btn-secondary fw-bold">Kembali</a>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
@endif

<div class="row g-4">
    {{-- ===== Form tambah/ubah ===== --}}
    <div class="col-xl-5 col-lg-6">
        <div class="card td-card border-0">
            <div class="td-head">
                <h6 class="mb-0 fw-bold" id="tdFormTitle">Tambah Periode Tarif/Diskon</h6>
                <button type="button" id="tdCancelEdit" class="btn btn-sm btn-light border d-none">Batal Ubah</button>
            </div>
            <div class="card-body p-4">
                <form id="tdForm" method="POST" action="{{ route('tarif-diskon.store') }}" class="td-form">
                    @csrf
                    <input type="hidden" name="_method" id="tdMethod" value="POST">

                    <div class="mb-3">
                        <label class="form-label">Layanan (Item Tarif) <span class="text-danger">*</span></label>
                        <select name="layanan_jasa_id" id="tdLayanan" class="form-select" required>
                            <option value="">Pilih item tarif...</option>
                            @foreach($leafLayanans as $l)
                                <option value="{{ $l['id'] }}" data-tarif="{{ $l['tarif_dasar'] }}" data-satuan="{{ $l['satuan'] }}">
                                    {{ $l['nama'] }}{{ $l['kode'] ? ' ('.$l['kode'].')' : '' }}
                                </option>
                            @endforeach
                        </select>
                        <div class="form-text" id="tdTarifNormalHint">Tarif normal akan tampil setelah layanan dipilih.</div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label">Jenis</label>
                            <select name="jenis" id="tdJenis" class="form-select">
                                <option value="DISKON">Diskon</option>
                                <option value="TARIF_KHUSUS">Tarif Khusus</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Persen (opsional)</label>
                            <div class="input-group">
                                <input type="number" name="persen_diskon" id="tdPersen" class="form-control" min="0" max="100" step="0.01" placeholder="mis. 20">
                                <span class="input-group-text">%</span>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Tarif Selama Periode (Rp) <span class="text-danger">*</span></label>
                        <input type="number" name="tarif" id="tdTarif" class="form-control" min="0" step="1" required placeholder="Tarif yang berlaku selama periode">
                        <div class="form-text">Nominal tarif yang dipakai selama periode (mis. harga diskon).</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Berlaku untuk Mitra</label>
                        <select name="mitra_jasa_id" id="tdMitra" class="form-select">
                            <option value="">Semua mitra</option>
                            @foreach($mitras as $m)
                                <option value="{{ $m->id }}">{{ $m->nama_mitra }}</option>
                            @endforeach
                        </select>
                        <div class="form-text">Kosongkan untuk diskon yang berlaku ke semua mitra.</div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label">Berlaku Mulai <span class="text-danger">*</span></label>
                            <input type="date" name="berlaku_mulai" id="tdMulai" class="form-control" value="{{ now()->toDateString() }}" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Berlaku Sampai</label>
                            <input type="date" name="berlaku_sampai" id="tdSampai" class="form-control">
                            <div class="form-text">Kosong = tanpa batas akhir.</div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Keterangan</label>
                        <input type="text" name="keterangan" id="tdKeterangan" class="form-control" maxlength="255" placeholder="mis. Promo low season">
                    </div>

                    <div class="form-check form-switch mb-3">
                        <input type="checkbox" name="is_active" id="tdAktif" class="form-check-input" value="1" checked>
                        <label class="form-check-label" for="tdAktif">Aktif</label>
                    </div>

                    <button type="submit" class="btn btn-warning fw-bold w-100"><i class="bi bi-save me-1"></i><span id="tdSubmitLabel">Simpan Periode</span></button>
                </form>
            </div>
        </div>
    </div>

    {{-- ===== Daftar periode ===== --}}
    <div class="col-xl-7 col-lg-6">
        <div class="card td-card border-0">
            <div class="td-head">
                <h6 class="mb-0 fw-bold">Daftar Periode Tarif/Diskon</h6>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Layanan</th>
                            <th>Mitra</th>
                            <th>Tarif</th>
                            <th>Periode</th>
                            <th>Status</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($periodes as $p)
                            @php
                                $statusBadge = $p->status_periode === 'AKTIF' ? ['td-aktif','Aktif','bi-broadcast']
                                    : ($p->status_periode === 'AKAN_DATANG' ? ['td-akan','Akan datang','bi-clock'] : ['td-berakhir','Berakhir','bi-check2']);
                            @endphp
                            <tr>
                                <td>
                                    <div class="fw-semibold text-dark">{{ $p->layananJasa->nama_layanan ?? '-' }}</div>
                                    <div class="small text-muted">{{ $p->layananJasa->kode_layanan ?? '' }}
                                        @if($p->layananJasa) · Normal Rp {{ number_format((float) $p->layananJasa->tarif_dasar, 0, ',', '.') }}@endif
                                    </div>
                                </td>
                                <td>{{ $p->mitra->nama_mitra ?? 'Semua mitra' }}</td>
                                <td>
                                    <span class="fw-bold text-success">Rp {{ number_format((float) $p->tarif, 0, ',', '.') }}</span>
                                    @if($p->persen_diskon !== null)<div class="small text-muted">{{ rtrim(rtrim(number_format((float)$p->persen_diskon,2,',','.'),'0'),',') }}%</div>@endif
                                </td>
                                <td class="small">
                                    {{ \Carbon\Carbon::parse($p->berlaku_mulai)->format('d/m/Y') }}
                                    &rarr; {{ $p->berlaku_sampai ? \Carbon\Carbon::parse($p->berlaku_sampai)->format('d/m/Y') : '∞' }}
                                </td>
                                <td>
                                    <span class="td-badge {{ $statusBadge[0] }}"><i class="bi {{ $statusBadge[2] }}"></i>{{ $statusBadge[1] }}</span>
                                    @unless($p->is_active)<div class="td-badge td-nonaktif mt-1"><i class="bi bi-pause-circle"></i>Nonaktif</div>@endunless
                                </td>
                                <td class="text-end">
                                    <div class="d-flex gap-1 justify-content-end">
                                        <button type="button" class="btn btn-sm btn-light border text-primary td-edit"
                                            data-id="{{ $p->id }}"
                                            data-layanan="{{ $p->layanan_jasa_id }}"
                                            data-mitra="{{ $p->mitra_jasa_id }}"
                                            data-jenis="{{ $p->jenis }}"
                                            data-tarif="{{ $p->tarif }}"
                                            data-persen="{{ $p->persen_diskon }}"
                                            data-mulai="{{ \Carbon\Carbon::parse($p->berlaku_mulai)->toDateString() }}"
                                            data-sampai="{{ $p->berlaku_sampai ? \Carbon\Carbon::parse($p->berlaku_sampai)->toDateString() : '' }}"
                                            data-keterangan="{{ $p->keterangan }}"
                                            data-aktif="{{ $p->is_active ? 1 : 0 }}"
                                            title="Ubah"><i class="bi bi-pencil"></i></button>
                                        <form action="{{ route('tarif-diskon.destroy', $p->id) }}" method="POST" onsubmit="return confirm('Hapus periode tarif ini?');">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger" title="Hapus"><i class="bi bi-trash"></i></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-4">Belum ada periode tarif/diskon.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($periodes->hasPages())
                <div class="card-footer bg-white border-0 pt-3">{{ $periodes->links() }}</div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('script')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    $(function () {
        $('#tdLayanan, #tdMitra').select2({ theme: 'bootstrap-5', width: '100%' });

        const form = document.getElementById('tdForm');
        const storeAction = @json(route('tarif-diskon.store'));
        const updateBase = @json(url('jasa/tarif-diskon'));

        function setTarifNormalHint() {
            const opt = document.querySelector('#tdLayanan option:checked');
            const tarif = opt ? parseFloat(opt.dataset.tarif || 0) : 0;
            const hint = document.getElementById('tdTarifNormalHint');
            if (opt && opt.value) {
                hint.textContent = 'Tarif normal: Rp ' + (tarif || 0).toLocaleString('id-ID') + (opt.dataset.satuan ? ' / ' + opt.dataset.satuan : '');
            } else {
                hint.textContent = 'Tarif normal akan tampil setelah layanan dipilih.';
            }
        }

        $('#tdLayanan').on('change', function () {
            setTarifNormalHint();
            // Saat menambah (bukan edit), prefill tarif dengan tarif normal sebagai titik awal.
            if (document.getElementById('tdMethod').value === 'POST') {
                const opt = document.querySelector('#tdLayanan option:checked');
                if (opt && opt.value && !document.getElementById('tdTarif').value) {
                    document.getElementById('tdTarif').value = parseFloat(opt.dataset.tarif || 0) || '';
                }
            }
        });

        function resetToCreate() {
            form.action = storeAction;
            document.getElementById('tdMethod').value = 'POST';
            document.getElementById('tdFormTitle').textContent = 'Tambah Periode Tarif/Diskon';
            document.getElementById('tdSubmitLabel').textContent = 'Simpan Periode';
            document.getElementById('tdCancelEdit').classList.add('d-none');
            form.reset();
            $('#tdLayanan').val('').trigger('change');
            $('#tdMitra').val('').trigger('change');
            document.getElementById('tdAktif').checked = true;
        }

        document.getElementById('tdCancelEdit').addEventListener('click', resetToCreate);

        document.querySelectorAll('.td-edit').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const d = this.dataset;
                form.action = updateBase + '/' + d.id;
                document.getElementById('tdMethod').value = 'PUT';
                document.getElementById('tdFormTitle').textContent = 'Ubah Periode Tarif/Diskon';
                document.getElementById('tdSubmitLabel').textContent = 'Perbarui Periode';
                document.getElementById('tdCancelEdit').classList.remove('d-none');

                $('#tdLayanan').val(d.layanan).trigger('change');
                $('#tdMitra').val(d.mitra || '').trigger('change');
                document.getElementById('tdJenis').value = d.jenis || 'DISKON';
                document.getElementById('tdTarif').value = d.tarif || '';
                document.getElementById('tdPersen').value = d.persen || '';
                document.getElementById('tdMulai').value = d.mulai || '';
                document.getElementById('tdSampai').value = d.sampai || '';
                document.getElementById('tdKeterangan').value = d.keterangan || '';
                document.getElementById('tdAktif').checked = d.aktif === '1';
                setTarifNormalHint();

                document.getElementById('tdForm').scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
        });
    });
</script>
@endpush
