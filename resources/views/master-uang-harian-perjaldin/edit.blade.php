@extends('layouts.app')
@section('title')
    Edit Data Uang Harian
@endsection
@section('content')
    <x-page-title title="Master Data" subtitle="Edit Uang Harian" />

    <div class="card">
        <div class="card-body">
            <h5 class="mb-4">Form Edit Uang Harian Perjaldin</h5>
            <form action="{{ route('master-uang-harian-perjaldin.update', $data->id) }}" method="POST">
                @method('PUT')
                @include('master-uang-harian-perjaldin._form')
            </form>
        </div>
    </div>
@endsection
