@extends('errors.layout')

@section('code', '419')
@section('label', 'Sesi Kedaluwarsa')
@section('accent', '#fbbf24')

@section('heading', 'Sesi penerbangan Anda kedaluwarsa ⏱️')

@section('message')
    <strong>419 &mdash; Halaman Kedaluwarsa.</strong><br>
    Sesi Anda telah berakhir demi keamanan. Silakan muat ulang halaman dan coba kirim kembali.
@endsection

@section('actions')
    <a href="javascript:location.reload()" class="err-btn err-btn--primary">&#8635; Muat Ulang</a>
    <a href="{{ route('login') }}" class="err-btn err-btn--ghost">&#8594; Masuk Lagi</a>
@endsection
