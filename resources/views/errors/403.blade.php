@extends('errors.layout')

@section('code', '403')
@section('label', 'Akses Ditolak')
@section('accent', '#f87171')

@section('heading', 'Maaf, area ini khusus kru berwenang 🛂')

@section('message')
    <strong>403 &mdash; Akses Ditolak.</strong><br>
    Boarding pass Anda tidak mencakup area ini. Anda tidak memiliki izin untuk membuka halaman tersebut.
    Jika menurut Anda ini keliru, hubungi administrator.
@endsection
