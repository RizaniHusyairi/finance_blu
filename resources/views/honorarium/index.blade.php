@extends('layouts.app')

@section('title')
    Data Honorarium
@endsection

@section('content')
    <x-page-title title="Manajemen Honor" subtitle="Daftar Data Honorarium" />

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="mb-0 text-uppercase">Data Honorarium</h6>
        <a href="{{ route('honorarium.create') }}" class="btn btn-primary">
            <i class="bi bi-plus"></i> Input Honorarium
        </a>
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

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>No Honorarium</th>
                            <th>Tanggal</th>
                            <th>No BAST</th>
                            <th>Uraian</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th width="220">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($honorariums as $item)
                            @php
                                $locked = in_array($item->status, ['Approved', 'Approved SPP', 'Approved SPM', 'Paid SP2D']);
                            @endphp
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $item->transaction_number }}</td>
                                <td>{{ optional($item->date)->format('d/m/Y') }}</td>
                                <td>{{ $item->bast_number }}</td>
                                <td>{{ $item->description }}</td>
                                <td>Rp {{ number_format($item->gross_amount, 0, ',', '.') }}</td>
                                <td>
                                    @if($locked)
                                        <span class="badge bg-success">{{ $item->status }}</span>
                                    @else
                                        <span class="badge bg-secondary">{{ $item->status }}</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex flex-wrap gap-1">
                                        <a href="{{ route('honorarium.show', $item->id) }}" class="btn btn-sm btn-info text-white">
                                            Lihat
                                        </a>

                                        @if(!$locked)
                                            <a href="{{ route('honorarium.edit', $item->id) }}" class="btn btn-sm btn-warning text-dark">
                                                Edit
                                            </a>

                                            <form action="{{ route('honorarium.destroy', $item->id) }}" method="POST"
                                                onsubmit="return confirm('Yakin mau hapus data honorarium ini?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger">
                                                    Hapus
                                                </button>
                                            </form>
                                        @else
                                            <button type="button" class="btn btn-sm btn-warning" disabled>Edit</button>
                                            <button type="button" class="btn btn-sm btn-danger" disabled>Hapus</button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted">Belum ada data honorarium.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection