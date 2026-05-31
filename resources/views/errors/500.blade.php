@extends('errors.layout')

@section('code', '500')
@section('label', 'Kesalahan Server')
@section('accent', '#f87171')

@section('heading', 'Kami mengalami sedikit turbulensi 🌩️')

@section('message')
    <strong>500 &mdash; Terjadi Kesalahan Internal.</strong><br>
    Ada masalah tak terduga di sisi server kami. Tim teknis sudah diberi tahu dan sedang menstabilkan penerbangan.
    Silakan coba lagi beberapa saat lagi.
@endsection

@section('actions')
    <a href="javascript:location.reload()" class="err-btn err-btn--primary">&#8635; Coba Lagi</a>
    <a href="{{ url('/') }}" class="err-btn err-btn--ghost">&#8962; Kembali ke Beranda</a>
@endsection
