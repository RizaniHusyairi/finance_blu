@extends('layouts.app')

@section('title', 'Master Pajak')

@php
    $search = request('search');
    $statusFilter = request('status_aktif');
    $berlakuFilter = request('berlaku');
    $today = \Carbon\Carbon::today();
@endphp

@section('content')
    <x-page-title title="Master Pajak" subtitle="Daftar tarif pajak yang digunakan dalam perhitungan dokumen" />

    @if(session('success'))
        <div class="alert alert-success border-0 bg-success alert-dismissible fade show shadow-sm">
            <div class="text-white">{{ session('success') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger border-0 bg-danger alert-dismissible fade show shadow-sm">
            <div class="text-white">{{ session('error') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
        <div>
            <h5 class="mb-1 fw-bold">Master Pajak</h5>
            <p class="text-muted mb-0">Kelola tarif pajak untuk referensi perhitungan SPP, SPM, dan dokumen lainnya.</p>
        </div>
        <a href="{{ route('master-pajak.create') }}" class="btn btn-primary shadow-sm">
            <i class="bi bi-plus-lg me-1"></i> Tambah Pajak
        </a>
    </div>

    {{-- Summary Cards --}}
    <div class="row row-cols-1 row-cols-md-2 row-cols-xl-4 g-3 mb-4">
        <div class="col">
            <div class="card rounded-4 h-100 shadow-sm border-0">
                <div class="card-body p-3 border-start border-4 border-primary rounded-4">
                    <p class="mb-1 small text-muted">Total Pajak</p>
                    <h4 class="mb-0 fw-bold">{{ number_format($summary['total']) }}</h4>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card rounded-4 h-100 shadow-sm border-0">
                <div class="card-body p-3 border-start border-4 border-success rounded-4">
                    <p class="mb-1 small text-muted">Pajak Aktif</p>
                    <h4 class="mb-0 fw-bold">{{ number_format($summary['aktif']) }}</h4>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card rounded-4 h-100 shadow-sm border-0">
                <div class="card-body p-3 border-start border-4 border-secondary rounded-4">
                    <p class="mb-1 small text-muted">Pajak Nonaktif</p>
                    <h4 class="mb-0 fw-bold">{{ number_format($summary['nonaktif']) }}</h4>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card rounded-4 h-100 shadow-sm border-0">
                <div class="card-body p-3 border-start border-4 border-info rounded-4">
                    <p class="mb-1 small text-muted">Berlaku Saat Ini</p>
                    <h4 class="mb-0 fw-bold">{{ number_format($summary['berlaku_sekarang']) }}</h4>
                </div>
            </div>
        </div>
    </div>

    {{-- Filter Bar --}}
    <div class="card shadow-sm border-0 rounded-4 mb-4">
        <div class="card-body p-4">
            <form method="GET" action="{{ route('master-pajak.index') }}" id="pajakFilterForm">
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold" for="pajakSearchInput">Cari Kode Pajak</label>
                        <div class="position-relative">
                            <input type="text" name="search" id="pajakSearchInput" value="{{ $search }}" class="form-control pe-5" placeholder="Ketik kode pajak" autocomplete="off" inputmode="search">
                            <span class="position-absolute top-50 end-0 translate-middle-y me-3 text-muted d-none" id="pajakSearchSpinner" aria-hidden="true">
                                <span class="spinner-border spinner-border-sm" role="status"></span>
                            </span>
                        </div>
                        <small class="text-muted">Pencarian hanya berdasarkan kode pajak.</small>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-semibold">Status</label>
                        <select name="status_aktif" class="form-select" data-auto-submit="change">
                            <option value="">Semua</option>
                            <option value="aktif" {{ $statusFilter === 'aktif' ? 'selected' : '' }}>Aktif</option>
                            <option value="nonaktif" {{ $statusFilter === 'nonaktif' ? 'selected' : '' }}>Nonaktif</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Masa Berlaku</label>
                        <select name="berlaku" class="form-select" data-auto-submit="change">
                            <option value="">Semua</option>
                            <option value="berlaku" {{ $berlakuFilter === 'berlaku' ? 'selected' : '' }}>Berlaku Saat Ini</option>
                            <option value="belum" {{ $berlakuFilter === 'belum' ? 'selected' : '' }}>Belum Berlaku</option>
                            <option value="expired" {{ $berlakuFilter === 'expired' ? 'selected' : '' }}>Sudah Berakhir</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <div class="d-grid">
                            <a href="{{ route('master-pajak.index') }}" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Tabel Daftar Pajak --}}
    <div class="card shadow-sm border-0 rounded-4">
        <div class="card-body p-4">
            <div id="pajakTableContainer">
                @include('master-pajak._table')
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('pajakFilterForm');
            const tableContainer = document.getElementById('pajakTableContainer');
            const searchInput = document.getElementById('pajakSearchInput');
            const spinner = document.getElementById('pajakSearchSpinner');

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
                // Reset paginator setiap kali query berubah.
                params.delete('page');
                return params;
            };

            const setLoading = function (loading) {
                if (!spinner) return;
                spinner.classList.toggle('d-none', !loading);
            };

            const fetchTable = async function () {
                const params = buildParams();
                const userQueryString = params.toString();

                if (userQueryString === lastQueryString) {
                    return;
                }
                lastQueryString = userQueryString;

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
                        throw new Error('Gagal memuat data pajak (HTTP ' + response.status + ').');
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

            // Intercept klik paginasi agar tetap AJAX.
            tableContainer.addEventListener('click', async function (event) {
                const link = event.target.closest('.pagination a');
                if (!link) return;
                event.preventDefault();

                const targetUrl = new URL(link.href, window.location.origin);
                const pageNumber = targetUrl.searchParams.get('page');
                if (!pageNumber) return;

                if (abortController) {
                    abortController.abort();
                }
                abortController = new AbortController();

                const params = buildParams();
                params.set('page', pageNumber);
                const userQueryString = params.toString();
                lastQueryString = userQueryString;
                params.set('partial', '1');

                setLoading(true);
                try {
                    const response = await fetch(form.action + '?' + params.toString(), {
                        method: 'GET',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'text/html',
                        },
                        signal: abortController.signal,
                        credentials: 'same-origin',
                    });
                    if (!response.ok) {
                        throw new Error('Gagal memuat halaman (HTTP ' + response.status + ').');
                    }
                    tableContainer.innerHTML = await response.text();
                    window.history.replaceState({}, '', form.action + '?' + userQueryString);
                    tableContainer.scrollIntoView({ behavior: 'smooth', block: 'start' });
                } catch (err) {
                    if (err.name !== 'AbortError') {
                        console.error(err);
                        lastQueryString = null;
                    }
                } finally {
                    setLoading(false);
                }
            });
        });
    </script>
@endpush
