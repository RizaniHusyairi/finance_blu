@extends('layouts.app')
@section('title')
    Tambah Data Uang Harian
@endsection
@section('content')
    <x-page-title title="Master Data" subtitle="Tambah Uang Harian" />

    <div class="card">
        <div class="card-body">
            <h5 class="mb-4">Form Tambah Uang Harian Perjaldin</h5>
            <form action="{{ route('master-uang-harian-perjaldin.store') }}" method="POST">
                @include('master-uang-harian-perjaldin._form')
            </form>
        </div>
    </div>
@endsection
