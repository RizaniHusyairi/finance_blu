@extends('errors.layout')

@section('code', '404')
@section('label', 'Halaman Tidak Ditemukan')
@section('accent', '#fcd34d')

@section('heading', 'Halaman ini hilang dari radar ✈️')

@section('message')
    <strong>404 &mdash; Halaman Tidak Ditemukan.</strong><br>
    Sepertinya halaman yang Anda tuju sudah berpindah jalur penerbangan atau memang tidak pernah ada.
    Mari kembali ke jalur yang benar.
@endsection
