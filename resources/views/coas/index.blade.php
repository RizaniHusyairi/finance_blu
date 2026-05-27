@extends('layouts.app')

@section('title', 'Master COA')

@php
    $search = request('search');
    $jenisAkun = request('jenis_akun');
    $statusAktif = request('status_aktif');
@endphp

@section('content')
    <x-page-title title="Master COA" subtitle="Pengelolaan chart of account untuk kebutuhan DIPA dan transaksi BLU" />

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

    @if(session('error'))
        <div class="alert alert-danger border-0 bg-danger alert-dismissible fade show shadow-sm">
            <div class="text-white">{{ session('error') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
        <div>
            <h5 class="mb-1 fw-bold">Master COA</h5>
            <p class="text-muted mb-0">Daftar COA lengkap beserta pemakaian pada item anggaran DIPA.</p>
        </div>
        <a href="{{ route('coas.create') }}" class="btn btn-primary shadow-sm">
            <i class="bi bi-plus-lg me-1"></i> Tambah COA
        </a>
    </div>

    <div class="row row-cols-1 row-cols-md-2 row-cols-xl-4 g-3 mb-4">
        <div class="col">
            <div class="card rounded-4 h-100 shadow-sm border-0">
                <div class="card-body p-3 border-start border-4 border-primary rounded-4">
                    <p class="mb-1 small text-muted">Total COA</p>
                    <h4 class="mb-0 fw-bold">{{ number_format($summary['total_coa']) }}</h4>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card rounded-4 h-100 shadow-sm border-0">
                <div class="card-body p-3 border-start border-4 border-success rounded-4">
                    <p class="mb-1 small text-muted">COA Aktif</p>
                    <h4 class="mb-0 fw-bold">{{ number_format($summary['coa_aktif']) }}</h4>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card rounded-4 h-100 shadow-sm border-0">
                <div class="card-body p-3 border-start border-4 border-warning rounded-4">
                    <p class="mb-1 small text-muted">Kode Akun Unik</p>
                    <h4 class="mb-0 fw-bold">{{ number_format($summary['kode_akun_unik']) }}</h4>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card rounded-4 h-100 shadow-sm border-0">
                <div class="card-body p-3 border-start border-4 border-info rounded-4">
                    <p class="mb-1 small text-muted">Dipakai di Item DIPA</p>
                    <h4 class="mb-0 fw-bold">{{ number_format($summary['coa_dipakai_di_dipa']) }}</h4>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0 rounded-4 mb-4">
        <div class="card-body p-4">
            <form method="GET" action="{{ route('coas.index') }}" id="coaFilterForm">
                <div class="row g-3 align-items-end">
                    <div class="col-md-5">
                        <label class="form-label fw-semibold" for="coaSearchInput">Cari Kode COA</label>
                        <div class="position-relative">
                            <input type="text" name="search" id="coaSearchInput" value="{{ $search }}" class="form-control pe-5" placeholder="Ketik kode MAK lengkap atau kode akun" autocomplete="off" inputmode="search">
                            <span class="position-absolute top-50 end-0 translate-middle-y me-3 text-muted d-none" id="coaSearchSpinner" aria-hidden="true">
                                <span class="spinner-border spinner-border-sm" role="status"></span>
                            </span>
                        </div>
                        <small class="text-muted">Pencarian hanya berdasarkan kode COA (kode MAK lengkap / kode akun).</small>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Jenis Akun</label>
                        <select name="jenis_akun" class="form-select" data-auto-submit="change">
                            <option value="">Semua</option>
                            @foreach($jenisAkunOptions as $option)
                                <option value="{{ $option }}" {{ (string) $jenisAkun === (string) $option ? 'selected' : '' }}>{{ $option }}</option>
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
                        <div class="d-grid">
                            <a href="{{ route('coas.index') }}" class="btn btn-outline-secondary">
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
            <div id="coaTableContainer" data-coa-table>
                @include('coas._table')
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('coaFilterForm');
            const tableContainer = document.getElementById('coaTableContainer');
            const searchInput = document.getElementById('coaSearchInput');
            const spinner = document.getElementById('coaSearchSpinner');

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
                        throw new Error('Gagal memuat data COA (HTTP ' + response.status + ').');
                    }

                    const html = await response.text();
                    tableContainer.innerHTML = html;

                    // Sinkronkan URL browser agar hasil pencarian tetap dapat disalin/dibookmark.
                    const newUrl = userQueryString
                        ? form.action + '?' + userQueryString
                        : form.action;
                    window.history.replaceState({}, '', newUrl);
                } catch (err) {
                    if (err.name === 'AbortError') {
                        return;
                    }
                    console.error(err);
                    // Reset cache supaya user bisa mencoba lagi dengan input yang sama.
                    lastQueryString = null;
                } finally {
                    setLoading(false);
                }
            };

            const triggerImmediate = function () {
                clearTimeout(debounceTimer);
                // Paksa fetch meski query string sama (mis. user klik tombol Filter ulang).
                lastQueryString = null;
                fetchTable();
            };

            searchInput.addEventListener('input', function () {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(fetchTable, DEBOUNCE_MS);
            });

            // Submit form (klik tombol Filter / Enter) — langsung jalankan tanpa menunggu debounce.
            form.addEventListener('submit', function (event) {
                event.preventDefault();
                triggerImmediate();
            });

            // Dropdown filter — jalankan langsung saat berubah.
            form.querySelectorAll('[data-auto-submit="change"]').forEach(function (field) {
                field.addEventListener('change', triggerImmediate);
            });

            // Intercept klik paginasi agar tetap AJAX (tidak full reload).
            tableContainer.addEventListener('click', async function (event) {
                const link = event.target.closest('.pagination a');
                if (!link) {
                    return;
                }
                event.preventDefault();

                const targetUrl = new URL(link.href, window.location.origin);
                const pageNumber = targetUrl.searchParams.get('page');
                if (!pageNumber) {
                    return;
                }

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
