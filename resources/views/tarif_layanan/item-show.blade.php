@extends('layouts.app')

@section('title', 'Detail Item Tarif Layanan')

@push('css')
    <script>
        window.tailwind = window.tailwind || {};
        window.tailwind.config = { corePlugins: { preflight: false } };
    </script>
    <script src="https://cdn.tailwindcss.com"></script>
@endpush

@section('content')
<div class="mx-auto max-w-5xl space-y-5">
    <div class="rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-6 py-5">
            <a href="{{ route('tarif-layanan.kategori.show', $item->kategoriLayanan) }}" class="text-sm font-semibold text-blue-600 hover:text-blue-700">Kembali ke Detail Kategori</a>
            <div class="mt-3 flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900">{{ $item->nama_item }}</h1>
                    <p class="mt-1 text-sm text-slate-500">{{ $item->jenisLayanan?->nama_jenis }} / {{ $item->kategoriLayanan?->nama_kategori }}</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-200">Item Tarif</span>
                    @if($item->is_billable)
                        <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-200">Dapat Ditagihkan</span>
                    @else
                        <span class="rounded-full bg-red-50 px-3 py-1 text-xs font-semibold text-red-700 ring-1 ring-red-200">Tidak Ditagihkan</span>
                    @endif
                </div>
            </div>
        </div>

        <div class="grid gap-4 px-6 py-5 md:grid-cols-2">
            <div class="rounded-md bg-slate-50 p-4">
                <div class="text-xs font-semibold uppercase text-slate-500">Kode Item</div>
                <div class="mt-1 font-semibold text-slate-900">{{ $item->kode_item }}</div>
            </div>
            <div class="rounded-md bg-slate-50 p-4">
                <div class="text-xs font-semibold uppercase text-slate-500">Level Excel</div>
                <div class="mt-1 font-semibold text-slate-900">kdlevel {{ $item->kdlevel }}</div>
            </div>
            <div class="rounded-md bg-slate-50 p-4">
                <div class="text-xs font-semibold uppercase text-slate-500">Satuan</div>
                <div class="mt-1 font-semibold text-slate-900">{{ $item->satuan ?: '-' }}</div>
            </div>
            <div class="rounded-md bg-slate-50 p-4">
                <div class="text-xs font-semibold uppercase text-slate-500">Tarif</div>
                <div class="mt-1 font-semibold text-slate-900">{{ $item->tarif !== null ? number_format((float) $item->tarif, 0, ',', '.') : '-' }}</div>
            </div>
            <div class="rounded-md bg-slate-50 p-4">
                <div class="text-xs font-semibold uppercase text-slate-500">Kd MAK</div>
                <div class="mt-1 font-semibold text-slate-900">{{ $item->kdmak ?: '-' }}</div>
            </div>
            <div class="rounded-md bg-slate-50 p-4">
                <div class="text-xs font-semibold uppercase text-slate-500">Kd Akun Plus</div>
                <div class="mt-1 font-semibold text-slate-900">{{ $item->kdakunplus ?: '-' }}</div>
            </div>
            <div class="rounded-md bg-slate-50 p-4">
                <div class="text-xs font-semibold uppercase text-slate-500">Mata Uang</div>
                <div class="mt-1 font-semibold text-slate-900">{{ $item->nmmu ?: ($item->kdmu ?: '-') }}</div>
            </div>
            <div class="rounded-md bg-slate-50 p-4">
                <div class="text-xs font-semibold uppercase text-slate-500">Baris Excel</div>
                <div class="mt-1 font-semibold text-slate-900">{{ $item->sumber_excel_row ?: '-' }}</div>
            </div>
        </div>

        <div class="flex flex-wrap gap-2 border-t border-slate-200 px-6 py-5">
            <a href="{{ route('tarif-layanan.index') }}" class="rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Daftar Layanan Tarif</a>
            @if($item->is_billable)
                <a href="{{ route('tagihan-jasa.create', ['item_tarif_layanan_id' => $item->id]) }}" class="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Pilih untuk Tagihan</a>
            @endif
        </div>
    </div>
</div>
@endsection
