@extends('errors.layout')

@section('code', '503')
@section('label', 'Layanan Tidak Tersedia')
@section('accent', '#fcd34d')

@section('heading', 'Sebentar, kami sedang maintenance untuk terbang lebih tinggi ✈️')

@section('message')
    <strong>503 &mdash; Layanan Sedang Tidak Tersedia.</strong><br>
    SIKEREN BLU sedang dalam pemeliharaan untuk memberikan pengalaman yang lebih baik.
    Mohon mendarat sejenak dan coba lagi beberapa saat lagi.
@endsection

@section('actions')
    <a href="javascript:location.reload()" class="err-btn err-btn--primary">&#8635; Coba Lagi</a>
    <a href="https://aptpairport.id" class="err-btn err-btn--ghost">&#8592; Kembali ke aptpairport.id</a>
@endsection
