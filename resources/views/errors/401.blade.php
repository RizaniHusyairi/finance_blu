@extends('errors.layout')

@section('code', '401')
@section('label', 'Belum Masuk')
@section('accent', '#a78bfa')

@section('heading', 'Boarding pass Anda belum dipindai 🎫')

@section('message')
    <strong>401 &mdash; Tidak Terautentikasi.</strong><br>
    Anda perlu masuk terlebih dahulu sebelum melanjutkan penerbangan ke halaman ini.
@endsection

@section('actions')
    <a href="{{ route('login') }}" class="err-btn err-btn--primary">&#8594; Masuk Sekarang</a>
    <a href="{{ url('/') }}" class="err-btn err-btn--ghost">&#8962; Kembali ke Beranda</a>
@endsection
