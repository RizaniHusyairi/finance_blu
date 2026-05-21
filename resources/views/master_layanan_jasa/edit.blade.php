@extends('layouts.app')
@section('title', 'Edit Master Layanan Jasa')

@section('content')
    @include('master_layanan_jasa._form', [
        'action' => route('master-layanan-jasa.update', $layanan->id),
        'method' => 'PUT',
        'submitLabel' => 'Update Layanan',
        'layanan' => $layanan,
    ])
@endsection
