@extends('layouts.app')

@section('title', 'Master Data DIPA')

@php
    $search = request('search');
    $tahunAnggaran = request('tahun_anggaran');
    $statusAktif = request('status_aktif');
    $revisiAktif = request('revisi_aktif_ke');
@endphp

@section('content')
    <x-page-title title="Master Data DIPA" subtitle="Pengelolaan dokumen DIPA, revisi, dan item anggaran" />

    @if(session('success'))
        <div class="alert alert-success border-0 bg-success alert-dismissible fade show shadow-sm">
            <div class="text-white">{{ session('success') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('info'))
        <div class="alert alert-info border-0 bg-info alert-dismissible fade show shadow-sm">
            <div class="text-white">{{ session('info') }}</div>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
        <div>
            <h5 class="mb-1 fw-bold">Master Data DIPA</h5>
            <p class="text-muted mb-0">Daftar seluruh DIPA beserta revisi aktif dan ringkasan item anggarannya.</p>
        </div>
        <a href="{{ route('dipas.create') }}" class="btn btn-primary shadow-sm">
            <i class="bi bi-plus-lg me-1"></i> Tambah DIPA
        </a>
    </div>

    <div class="row row-cols-1 row-cols-md-2 row-cols-xl-4 g-3 mb-4">
        <div class="col">
            <div class="card rounded-4 h-100 shadow-sm border-0">
                <div class="card-body p-3 border-start border-4 border-primary rounded-4">
                    <p class="mb-1 small text-muted">Total DIPA</p>
                    <h4 class="mb-0 fw-bold">{{ number_format($summary['total_dipa']) }}</h4>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card rounded-4 h-100 shadow-sm border-0">
                <div class="card-body p-3 border-start border-4 border-success rounded-4">
                    <p class="mb-1 small text-muted">DIPA Aktif</p>
                    <h4 class="mb-0 fw-bold">{{ number_format($summary['dipa_aktif']) }}</h4>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card rounded-4 h-100 shadow-sm border-0">
                <div class="card-body p-3 border-start border-4 border-warning rounded-4">
                    <p class="mb-1 small text-muted">Tahun Anggaran Berjalan</p>
                    <h4 class="mb-0 fw-bold">{{ number_format($summary['tahun_berjalan']) }}</h4>
                    <small class="text-muted">{{ now()->year }}</small>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card rounded-4 h-100 shadow-sm border-0">
                <div class="card-body p-3 border-start border-4 border-info rounded-4">
                    <p class="mb-1 small text-muted">Total Pagu Revisi Aktif</p>
                    <h5 class="mb-0 fw-bold">Rp {{ number_format($summary['total_pagu_revisi_aktif'], 0, ',', '.') }}</h5>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0 rounded-4 mb-4">
        <div class="card-body p-4">
            <form method="GET" action="{{ route('dipas.index') }}" id="dipaFilterForm">
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold" for="dipaSearchInput">Cari Nomor DIPA</label>
                        <div class="position-relative">
                            <input type="text" name="search" id="dipaSearchInput" value="{{ $search }}" class="form-control pe-5" placeholder="Ketik nomor DIPA" autocomplete="off" inputmode="search">
                            <span class="position-absolute top-50 end-0 translate-middle-y me-3 text-muted d-none" id="dipaSearchSpinner" aria-hidden="true">
                                <span class="spinner-border spinner-border-sm" role="status"></span>
                            </span>
                        </div>
                        <small class="text-muted">Pencarian hanya berdasarkan nomor DIPA.</small>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-semibold">Tahun Anggaran</label>
                        <select name="tahun_anggaran" class="form-select" data-auto-submit="change">
                            <option value="">Semua</option>
                            @foreach($tahunOptions as $tahun)
                                <option value="{{ $tahun }}" {{ (string) $tahunAnggaran === (string) $tahun ? 'selected' : '' }}>{{ $tahun }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-semibold">Status Aktif</label>
                        <select name="status_aktif" class="form-select" data-auto-submit="change">
                            <option value="">Semua</option>
                            <option value="aktif" {{ $statusAktif === 'aktif' ? 'selected' : '' }}>Aktif</option>
                            <option value="nonaktif" {{ $statusAktif === 'nonaktif' ? 'selected' : '' }}>Nonaktif</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-semibold">Revisi Aktif</label>
                        <select name="revisi_aktif_ke" class="form-select" data-auto-submit="change">
                            <option value="">Semua</option>
                            @foreach($revisiOptions as $revisi)
                                <option value="{{ $revisi }}" {{ (string) $revisiAktif === (string) $revisi ? 'selected' : '' }}>Revisi {{ $revisi }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <div class="d-grid">
                            <a href="{{ route('dipas.index') }}" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm border-0 rounded-4">
        <div class="card-body p-4">
            <div id="dipaTableContainer">
                @include('dipas._table')
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('dipaFilterForm');
            const tableContainer = document.getElementById('dipaTableContainer');
            const searchInput = document.getElementById('dipaSearchInput');
            const spinner = document.getElementById('dipaSearchSpinner');

            if (!form || !tableContainer || !searchInput) {
                return;
            }

            const DEBOUNCE_MS = 400;

            let debounceTimer = null;
            let abortController = null;
            let lastQueryString = null;

            const buildParams = function () {
                const params = new URLSearchParams(new FormData(form));
                for (const [key, value] of Array.from(params.entries())) {
                    if (String(value).trim() === '') {
                        params.delete(key);
                    }
                }
                return params;
            };

            const setLoading = function (loading) {
                if (!spinner) return;
                spinner.classList.toggle('d-none', !loading);
            };

            const fetchTable = async function () {
                const params = buildParams();
                const userQueryString = params.toString();

                // Skip jika query sama persis dengan request terakhir (hindari request kembar).
                if (userQueryString === lastQueryString) {
                    return;
                }
                lastQueryString = userQueryString;

                // Batalkan request sebelumnya yang masih berjalan (user masih mengetik).
                if (abortController) {
                    abortController.abort();
                }
                abortController = new AbortController();

                params.set('partial', '1');
                const requestUrl = form.action + '?' + params.toString();

                setLoading(true);

                try {
                    const response = await fetch(requestUrl, {
                        method: 'GET',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'text/html',
                        },
                        signal: abortController.signal,
                        credentials: 'same-origin',
                    });

                    if (!response.ok) {
                        throw new Error('Gagal memuat data DIPA (HTTP ' + response.status + ').');
                    }

                    tableContainer.innerHTML = await response.text();

                    const newUrl = userQueryString
                        ? form.action + '?' + userQueryString
                        : form.action;
                    window.history.replaceState({}, '', newUrl);
                } catch (err) {
                    if (err.name === 'AbortError') {
                        return;
                    }
                    console.error(err);
                    lastQueryString = null;
                } finally {
                    setLoading(false);
                }
            };

            const triggerImmediate = function () {
                clearTimeout(debounceTimer);
                lastQueryString = null;
                fetchTable();
            };

            searchInput.addEventListener('input', function () {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(fetchTable, DEBOUNCE_MS);
            });

            form.addEventListener('submit', function (event) {
                event.preventDefault();
                triggerImmediate();
            });

            form.querySelectorAll('[data-auto-submit="change"]').forEach(function (field) {
                field.addEventListener('change', triggerImmediate);
            });
        });
    </script>
@endpush
