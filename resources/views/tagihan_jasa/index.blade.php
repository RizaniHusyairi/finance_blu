@extends('layouts.app')
@section('title')
    Penagihan Jasa
@endsection
@push('css')
    <link href="{{ URL::asset('build/plugins/datatable/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet" />
    <style>
        .soft-table-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: 10px 14px;
            border-bottom: 1px solid #bfdbfe;
            background: linear-gradient(90deg, #eff6ff 0%, #f8fbff 58%, #ffffff 100%);
        }
        .soft-table-title {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .soft-table-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 34px;
            height: 34px;
            border-radius: 9px;
            color: #fff;
            background: #1d4ed8;
            box-shadow: 0 10px 20px rgba(37, 99, 235, .18);
        }
        .soft-table-title h6 {
            margin: 0;
            color: #1e3a8a;
            font-weight: 800;
        }
        .soft-table-title small {
            color: #64748b;
            font-weight: 700;
        }
    </style>
@endpush
@section('content')
    @php
        $canCreateTagihanJasa = auth()->user()?->hasAnyRole(['Super Admin', 'Super Admin Jasa', 'Admin Jasa', 'Admin Konsesi']) === true;
    @endphp
    <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
        <div>
            <h4 class="mb-0 fw-bold">Penagihan Jasa</h4>
            <p class="mb-0 small">Kelola seluruh tagihan jasa mitra dalam satu daftar.</p>
        </div>
        @if($canCreateTagihanJasa)
        <a href="{{ route('tagihan-jasa.create', ['mode' => 'konsesi']) }}" class="btn btn-outline-primary fw-bold">
            <i class="bi bi-percent me-1"></i> Atur Layanan Konsesi
        </a>
        @endif
    </div>

    @if(session('success'))
        <div class="alert alert-success border-0 bg-success text-white alert-dismissible fade show shadow-sm">
            <i class="bi bi-check-circle me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger border-0 bg-danger text-white alert-dismissible fade show shadow-sm">
            <i class="bi bi-x-circle me-2"></i> {{ session('error') }}
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="soft-table-header">
            <div class="soft-table-title">
                <span class="soft-table-icon"><i class="bi bi-receipt-cutoff"></i></span>
                <div>
                    <h6>Daftar Tagihan Jasa</h6>
                    <small>Tagihan jasa yang sudah dibuat untuk mitra.</small>
                </div>
            </div>
            @if($canCreateTagihanJasa)
            <div>
                <a href="{{ route('tagihan-jasa.create') }}" class="btn btn-primary shadow-sm fw-bold">
                    <i class="bi bi-plus-lg me-1"></i> Buat Tagihan
                </a>
            </div>
            @endif
        </div>
        <div class="card-body p-4">
            <div class="table-responsive">
                <table id="tableTagihanJasa" class="table table-hover align-middle w-100">
                    <thead>
                        <tr>
                            <th width="5%" class="text-center">No</th>
                            <th width="22%">No. Tagihan</th>
                            <th width="20%">Mitra</th>
                            <th width="20%">Total & Tgl Tagihan</th>
                            <th width="13%">Status</th>
                            <th width="20%" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($tagihans as $tagihan)
                            @include('tagihan_jasa._row_tagihan', ['tagihan' => $tagihan])
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script src="{{ URL::asset('build/plugins/datatable/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ URL::asset('build/plugins/datatable/js/dataTables.bootstrap5.min.js') }}"></script>
    <script>
        $(document).ready(function() {
            $('#tableTagihanJasa').DataTable({
                language: {
                    url: "https://cdn.datatables.net/plug-ins/1.13.6/i18n/id.json"
                }
            });
        });
    </script>
@endpush
