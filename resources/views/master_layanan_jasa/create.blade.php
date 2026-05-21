@extends('layouts.app')
@section('title', 'Tambah Master Layanan Jasa')

@section('content')
    @include('master_layanan_jasa._form', [
        'action' => route('master-layanan-jasa.store'),
        'submitLabel' => 'Simpan Layanan',
    ])
@endsection
