@extends('layouts.app')

@section('title', 'Detail Kategori Tarif Layanan')

@push('css')
    <script>
        window.tailwind = window.tailwind || {};
        window.tailwind.config = { corePlugins: { preflight: false } };
    </script>
    <script src="https://cdn.tailwindcss.com"></script>
@endpush

@section('content')
@php
    $billableCount = $kategori->itemTarifLayanan->where('is_billable', true)->count();
@endphp

<div class="mx-auto max-w-7xl space-y-5">
    <div class="rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-6 py-5">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <a href="{{ route('tarif-layanan.index') }}" class="text-sm font-semibold text-blue-600 hover:text-blue-700">Kembali ke Layanan Tarif Jasa</a>
                    <h1 class="mt-2 text-2xl font-bold text-slate-900">{{ $kategori->nama_kategori }}</h1>
                    <p class="mt-1 text-sm text-slate-500">{{ $kategori->jenisLayanan?->nama_jenis }}</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700 ring-1 ring-blue-200">Jenis Layanan</span>
                    <span class="rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700 ring-1 ring-amber-200">Kategori</span>
                </div>
            </div>
        </div>

        <div class="grid gap-4 border-b border-slate-200 px-6 py-5 md:grid-cols-4">
            <div class="rounded-md bg-slate-50 p-4">
                <div class="text-xs font-semibold uppercase text-slate-500">Kode Kategori</div>
                <div class="mt-1 font-bold text-slate-900">{{ $kategori->kode_kategori }}</div>
            </div>
            <div class="rounded-md bg-slate-50 p-4">
                <div class="text-xs font-semibold uppercase text-slate-500">Jumlah Item</div>
                <div class="mt-1 font-bold text-slate-900">{{ $kategori->itemTarifLayanan->count() }}</div>
            </div>
            <div class="rounded-md bg-slate-50 p-4">
                <div class="text-xs font-semibold uppercase text-slate-500">Dapat Ditagihkan</div>
                <div class="mt-1 font-bold text-emerald-700">{{ $billableCount }}</div>
            </div>
            <div class="rounded-md bg-slate-50 p-4">
                <div class="text-xs font-semibold uppercase text-slate-500">Tidak Ditagihkan</div>
                <div class="mt-1 font-bold text-red-700">{{ $kategori->itemTarifLayanan->count() - $billableCount }}</div>
            </div>
        </div>

        <div class="overflow-x-auto px-6 py-5">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-100 text-xs uppercase tracking-wide text-slate-600">
                    <tr>
                        <th class="px-3 py-3 text-left">No</th>
                        <th class="px-3 py-3 text-left">Nama Item Tarif</th>
                        <th class="px-3 py-3 text-left">Satuan</th>
                        <th class="px-3 py-3 text-right">Tarif</th>
                        <th class="px-3 py-3 text-left">Kd MAK</th>
                        <th class="px-3 py-3 text-left">Kd Akun Plus</th>
                        <th class="px-3 py-3 text-left">Mata Uang</th>
                        <th class="px-3 py-3 text-left">Status</th>
                        <th class="px-3 py-3 text-left">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse($kategori->itemTarifLayanan as $item)
                        <tr>
                            <td class="px-3 py-3 text-slate-500">{{ $loop->iteration }}</td>
                            <td class="px-3 py-3 font-medium text-slate-900">{{ $item->nama_item }}</td>
                            <td class="px-3 py-3">{{ $item->satuan ?: '-' }}</td>
                            <td class="px-3 py-3 text-right font-semibold">{{ $item->tarif !== null ? number_format((float) $item->tarif, 0, ',', '.') : '-' }}</td>
                            <td class="px-3 py-3">{{ $item->kdmak ?: '-' }}</td>
                            <td class="px-3 py-3">{{ $item->kdakunplus ?: '-' }}</td>
                            <td class="px-3 py-3">{{ $item->nmmu ?: ($item->kdmu ?: '-') }}</td>
                            <td class="px-3 py-3">
                                @if($item->is_billable)
                                    <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-200">Dapat Ditagihkan</span>
                                @else
                                    <span class="rounded-full bg-red-50 px-2.5 py-1 text-xs font-semibold text-red-700 ring-1 ring-red-200">Tidak Ditagihkan</span>
                                @endif
                            </td>
                            <td class="px-3 py-3">
                                <div class="flex flex-wrap gap-2">
                                    <a href="{{ route('tarif-layanan.item.show', $item) }}" class="rounded-md border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">Detail</a>
                                    @if($item->is_billable)
                                        <a href="{{ route('tagihan-jasa.create', ['item_tarif_layanan_id' => $item->id]) }}" class="rounded-md bg-blue-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-blue-700">Pilih untuk Tagihan</a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-3 py-8 text-center text-slate-500">Belum ada item tarif pada kategori ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
