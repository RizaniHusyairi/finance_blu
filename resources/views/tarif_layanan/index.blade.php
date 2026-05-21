@extends('layouts.app')

@section('title', 'Layanan Tarif Jasa')

@push('css')
    <script>
        window.tailwind = window.tailwind || {};
        window.tailwind.config = { corePlugins: { preflight: false } };
    </script>
    <script src="https://cdn.tailwindcss.com"></script>
@endpush

@section('content')
@php
    $badge = function (string $label, string $tone = 'slate') {
        $tones = [
            'blue' => 'bg-blue-50 text-blue-700 ring-blue-200',
            'emerald' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
            'amber' => 'bg-amber-50 text-amber-700 ring-amber-200',
            'slate' => 'bg-slate-50 text-slate-700 ring-slate-200',
            'red' => 'bg-red-50 text-red-700 ring-red-200',
        ];
        return '<span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset '.($tones[$tone] ?? $tones['slate']).'">'.$label.'</span>';
    };
@endphp

<div class="tw-scope">
    <div class="mx-auto max-w-7xl space-y-5">
        <div class="rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-6 py-5">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-slate-900">Layanan Tarif Jasa</h1>
                        <p class="mt-1 text-sm text-slate-500">Struktur master tarif layanan BLU/UPBU berdasarkan hierarki jenis, kategori, dan item tarif.</p>
                    </div>
                    <a href="{{ route('master-layanan-jasa.index') }}" class="inline-flex items-center justify-center rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                        Master Lama
                    </a>
                </div>
            </div>

            <form method="GET" action="{{ route('tarif-layanan.index') }}" class="grid gap-4 border-b border-slate-200 px-6 py-5 md:grid-cols-4">
                <label class="block">
                    <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">Jenis Layanan</span>
                    <select name="jenis_layanan_id" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                        <option value="">Semua Jenis</option>
                        @foreach($jenisOptions as $jenis)
                            <option value="{{ $jenis->id }}" @selected(($filters['jenis_layanan_id'] ?? '') == $jenis->id)>{{ $jenis->nama_jenis }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="block">
                    <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">Kategori</span>
                    <select name="kategori_layanan_id" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                        <option value="">Semua Kategori</option>
                        @foreach($kategoriOptions as $kategori)
                            <option value="{{ $kategori->id }}" @selected(($filters['kategori_layanan_id'] ?? '') == $kategori->id)>{{ $kategori->nama_kategori }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="block">
                    <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">Cari Item</span>
                    <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm" placeholder="Nama kategori/item tarif">
                </label>

                <div class="flex items-end gap-2">
                    <button type="submit" class="inline-flex w-full items-center justify-center rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Terapkan</button>
                    <a href="{{ route('tarif-layanan.index') }}" class="inline-flex items-center justify-center rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Reset</a>
                </div>
            </form>

            <div class="space-y-3 px-6 py-5">
                @forelse($jenisLayanan as $jenis)
                    <details class="group rounded-lg border border-slate-200 bg-white" open>
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-4 px-4 py-4">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    {!! $badge('Jenis Layanan', 'blue') !!}
                                    <span class="truncate text-base font-semibold text-slate-900">{{ $jenis->nama_jenis }}</span>
                                </div>
                                <p class="mt-1 text-xs text-slate-500">Kode: {{ $jenis->kode_jenis }} | {{ $jenis->kategoriLayanan->count() }} kategori</p>
                            </div>
                            <span class="text-sm font-semibold text-blue-600 group-open:hidden">Buka</span>
                            <span class="hidden text-sm font-semibold text-slate-500 group-open:inline">Tutup</span>
                        </summary>

                        <div class="border-t border-slate-200 bg-slate-50/60 px-4 py-4">
                            <div class="space-y-3">
                                @foreach($jenis->kategoriLayanan as $kategori)
                                    <details class="group/kategori rounded-md border border-slate-200 bg-white">
                                        <summary class="flex cursor-pointer list-none items-center justify-between gap-4 px-4 py-3">
                                            <div class="min-w-0">
                                                <div class="flex flex-wrap items-center gap-2">
                                                    {!! $badge('Kategori', 'amber') !!}
                                                    <span class="truncate font-semibold text-slate-900">{{ $kategori->nama_kategori }}</span>
                                                    @if($kategori->itemTarifLayanan->where('is_billable', true)->count() === 0)
                                                        {!! $badge('Tidak Ditagihkan', 'red') !!}
                                                    @endif
                                                </div>
                                                <p class="mt-1 text-xs text-slate-500">Kode: {{ $kategori->kode_kategori }} | {{ $kategori->itemTarifLayanan->count() }} item tarif</p>
                                            </div>
                                            <div class="flex shrink-0 items-center gap-3">
                                                <a href="{{ route('tarif-layanan.kategori.show', $kategori) }}" class="rounded-md border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">Detail Kategori</a>
                                                <span class="text-xs font-semibold text-blue-600 group-open/kategori:hidden">Buka</span>
                                                <span class="hidden text-xs font-semibold text-slate-500 group-open/kategori:inline">Tutup</span>
                                            </div>
                                        </summary>

                                        <div class="overflow-x-auto border-t border-slate-200">
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
                                                            <td class="px-3 py-3">
                                                                <div class="font-medium text-slate-900">{{ $item->nama_item }}</div>
                                                                <div class="mt-1 flex flex-wrap gap-1">
                                                                    {!! $badge($item->kdlevel === 3 ? 'Item Tarif' : 'Kategori', $item->kdlevel === 3 ? 'emerald' : 'amber') !!}
                                                                </div>
                                                            </td>
                                                            <td class="px-3 py-3 text-slate-700">{{ $item->satuan ?: '-' }}</td>
                                                            <td class="px-3 py-3 text-right font-semibold text-slate-900">{{ $item->tarif !== null ? number_format((float) $item->tarif, 0, ',', '.') : '-' }}</td>
                                                            <td class="px-3 py-3 text-slate-700">{{ $item->kdmak ?: '-' }}</td>
                                                            <td class="px-3 py-3 text-slate-700">{{ $item->kdakunplus ?: '-' }}</td>
                                                            <td class="px-3 py-3 text-slate-700">{{ $item->nmmu ?: ($item->kdmu ?: '-') }}</td>
                                                            <td class="px-3 py-3">
                                                                {!! $item->is_billable ? $badge('Dapat Ditagihkan', 'emerald') : $badge('Tidak Ditagihkan', 'red') !!}
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
                                                            <td colspan="9" class="px-3 py-6 text-center text-slate-500">Belum ada item tarif pada kategori ini.</td>
                                                        </tr>
                                                    @endforelse
                                                </tbody>
                                            </table>
                                        </div>
                                    </details>
                                @endforeach
                            </div>
                        </div>
                    </details>
                @empty
                    <div class="rounded-lg border border-dashed border-slate-300 px-6 py-10 text-center text-slate-500">
                        Data layanan tarif jasa belum tersedia atau tidak sesuai filter.
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
