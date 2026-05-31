@extends('errors.layout')

@section('code', '429')
@section('label', 'Terlalu Banyak Permintaan')
@section('accent', '#fb923c')

@section('heading', 'Lalu lintas udara sedang padat 🛬')

@section('message')
    <strong>429 &mdash; Terlalu Banyak Permintaan.</strong><br>
    Menara pengawas meminta Anda menunggu sejenak. Terlalu banyak permintaan dalam waktu singkat &mdash;
    mohon coba lagi beberapa saat lagi.
@endsection

@section('actions')
    <a href="javascript:location.reload()" class="err-btn err-btn--primary">&#8635; Coba Lagi</a>
    <a href="{{ url('/') }}" class="err-btn err-btn--ghost">&#8962; Kembali ke Beranda</a>
@endsection
