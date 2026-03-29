@extends('layouts.app')

@section('title', 'Verifikasi Honorarium')

@section('content')
    <x-page-title title="Verifikasi" subtitle="Honorarium Menunggu Persetujuan PPK" />

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="mb-0 text-uppercase">Daftar Verifikasi Honorarium</h6>
    </div>
    <hr>

    @if(session('success'))
        <div class="alert alert-success border-0 bg-success alert-dismissible fade show">
            <div class="text-white">{{ session('success') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger border-0 bg-danger alert-dismissible fade show">
            <div class="text-white">{{ session('error') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card rounded-4 shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped align-middle">
                    <thead class="table-light text-center">
                        <tr>
                            <th style="width: 60px;">No</th>
                            <th>No Honorarium</th>
                            <th>Tanggal</th>
                            <th>No BAST</th>
                            <th>No Kegiatan</th>
                            <th>Uraian</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th style="width: 260px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($honorariums as $item)
                            <tr>
                                <td class="text-center">{{ $loop->iteration }}</td>
                                <td>{{ $item->transaction_number }}</td>
                                <td>{{ optional($item->date)->format('d/m/Y') }}</td>
                                <td>{{ $item->bast_number }}</td>
                                <td>{{ $item->activity_number ?? '-' }}</td>
                                <td>{{ $item->description }}</td>
                                <td>Rp {{ number_format((float) $item->gross_amount, 0, ',', '.') }}</td>
                                <td class="text-center">
                                    <span class="badge bg-warning text-dark">{{ $item->status }}</span>
                                </td>
                                <td>
                                    <div class="d-flex flex-wrap gap-1">
                                        <a href="{{ route('honorarium.show', $item->id) }}"
                                           class="btn btn-sm btn-info text-white">
                                            Lihat
                                        </a>

                                        <form action="{{ route('honorarium.approve-ppk', $item->id) }}"
                                              method="POST"
                                              onsubmit="return confirm('Setujui honorarium ini?')">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-success">
                                                Setujui
                                            </button>
                                        </form>

                                        <form action="{{ route('honorarium.reject-ppk', $item->id) }}"
                                              method="POST"
                                              onsubmit="return confirm('Tolak honorarium ini?')">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-danger">
                                                Tolak
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted">
                                    Belum ada honorarium yang menunggu persetujuan PPK.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection