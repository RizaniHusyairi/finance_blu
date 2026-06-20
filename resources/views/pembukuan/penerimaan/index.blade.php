@extends('layouts.app')
@section('title', 'BKU Penerimaan')
@include('pembukuan.partials.styles')

@section('content')

<div class="container-fluid bku-page">

    <div class="bku-segment">
        @hasanyrole('Bendahara Pengeluaran|Super Admin')
            <a href="{{ route('pembukuan.pengeluaran.index') }}"><i class="bi bi-arrow-up-right-circle"></i> Pengeluaran</a>
        @endhasanyrole
        @hasanyrole('Bendahara Penerimaan|Super Admin')
            <a href="{{ route('pembukuan.penerimaan.index') }}" class="is-active"><i class="bi bi-arrow-down-left-circle"></i> Penerimaan</a>
        @endhasanyrole
    </div>

    @if(session('success'))<div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif
    @if(session('error'))<div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif

    <div class="bku-hero">
        <div class="bku-hero__info">
            <div class="bku-hero__eyebrow">Buku Kas Umum · Bendahara Penerimaan</div>
            <h3 class="bku-hero__title">{{ $buku['nama_buku'] }}</h3>
            @if($rekening)
                <span class="bku-hero__rek"><i class="bi bi-bank2"></i> {{ $rekening->nama_bank }} · {{ $rekening->nomor_rekening }}</span>
            @endif
        </div>
        <div class="bku-hero__actions">
            <button type="button" class="bku-btn bku-btn--light" data-bs-toggle="modal" data-bs-target="#modalMutasi"><i class="bi bi-plus-lg"></i> Catat Mutasi</button>
            <a href="{{ route('pembukuan.klasifikasi.index') }}" class="bku-btn bku-btn--ghost"><i class="bi bi-bank"></i> Impor Koran</a>
            <a href="{{ route('pembukuan.penerimaan.pdf', request()->query()) }}" target="_blank" class="bku-btn bku-btn--ghost" title="Ekspor PDF"><i class="bi bi-filetype-pdf"></i></a>
            <a href="{{ route('pembukuan.penerimaan.excel', request()->query()) }}" class="bku-btn bku-btn--ghost" title="Ekspor Excel"><i class="bi bi-filetype-xlsx"></i></a>
        </div>
    </div>

    <div class="bku-toolbar">
        <div class="bku-search">
            <i class="bi bi-search"></i>
            <input type="search" id="bku-search" value="{{ $filters['search'] ?? '' }}" placeholder="Cari uraian, kode akun, jenis, atau nominal…" autocomplete="off">
            <button type="button" class="bku-search__clear" aria-label="Hapus pencarian">&times;</button>
        </div>
        <div class="bku-field"><label>Dari</label><input type="date" id="bku-start" value="{{ $filters['start_date'] ?? '' }}"></div>
        <div class="bku-field"><label>Sampai</label><input type="date" id="bku-end" value="{{ $filters['end_date'] ?? '' }}"></div>
    </div>

    <div id="bku-content">
        @include('pembukuan.penerimaan._content')
    </div>
</div>

@include('pembukuan.penerimaan.partials.input-manual')
@endsection

@push('script')
<script>
    (function () {
        'use strict';
        var URL = '{{ route('pembukuan.penerimaan.index') }}';
        var content = document.getElementById('bku-content');
        var search = document.getElementById('bku-search');
        var start = document.getElementById('bku-start');
        var end = document.getElementById('bku-end');
        var clearBtn = document.querySelector('.bku-search__clear');
        if (!content) return;

        var sortKey = '{{ $sort ?? 'tanggal' }}';
        var sortDir = '{{ $dir ?? 'asc' }}';

        function countUp(el) {
            var target = parseInt(el.getAttribute('data-value') || '0', 10);
            if (isNaN(target)) return;
            var dur = 850, t0 = null;
            function fmt(n) { return 'Rp ' + Math.round(n).toLocaleString('id-ID'); }
            function step(ts) {
                if (!t0) t0 = ts;
                var p = Math.min((ts - t0) / dur, 1), e = 1 - Math.pow(1 - p, 3);
                el.textContent = fmt(target * e);
                if (p < 1) requestAnimationFrame(step);
            }
            requestAnimationFrame(step);
        }

        function initContent() { content.querySelectorAll('.bku-stat__value[data-value]').forEach(countUp); }

        function qs() {
            var p = new URLSearchParams();
            if (search && search.value.trim()) p.set('search', search.value.trim());
            if (start && start.value) p.set('start_date', start.value);
            if (end && end.value) p.set('end_date', end.value);
            if (sortKey) p.set('sort', sortKey);
            if (sortDir) p.set('dir', sortDir);
            p.set('partial', '1');
            return p.toString();
        }

        function reload() {
            content.classList.add('is-loading');
            fetch(URL + '?' + qs(), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (r) { if (!r.ok) throw new Error('http ' + r.status); return r.text(); })
                .then(function (html) { content.innerHTML = html; initContent(); })
                .catch(function () {})
                .then(function () { content.classList.remove('is-loading'); });
        }

        function toggleClear() { if (clearBtn) clearBtn.style.display = (search && search.value) ? 'grid' : 'none'; }

        var timer;
        if (search) search.addEventListener('input', function () { toggleClear(); clearTimeout(timer); timer = setTimeout(reload, 300); });
        if (start) start.addEventListener('change', reload);
        if (end) end.addEventListener('change', reload);
        if (clearBtn) clearBtn.addEventListener('click', function () { clearTimeout(timer); search.value = ''; toggleClear(); reload(); search.focus(); });

        // Sortir kolom: header dirender ulang tiap reload, jadi pakai event delegation.
        content.addEventListener('click', function (ev) {
            var btn = ev.target.closest('.bku-sort');
            if (!btn) return;
            sortKey = btn.getAttribute('data-sort');
            sortDir = btn.getAttribute('data-dir') || 'asc';
            reload();
        });

        toggleClear();
        initContent();

        @if($errors->any())
        window.addEventListener('load', function () {
            var m = document.getElementById('modalMutasi');
            if (m && window.bootstrap && bootstrap.Modal) bootstrap.Modal.getOrCreateInstance(m).show();
        });
        @endif
    })();
</script>
@endpush
