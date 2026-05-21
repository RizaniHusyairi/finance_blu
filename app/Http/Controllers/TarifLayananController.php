<?php

namespace App\Http\Controllers;

use App\Models\ItemTarifLayanan;
use App\Models\JenisLayanan;
use App\Models\KategoriLayanan;
use Illuminate\Http\Request;

class TarifLayananController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'jenis_layanan_id' => ['nullable', 'integer', 'exists:master_jenis_layanan,id'],
            'kategori_layanan_id' => ['nullable', 'integer', 'exists:master_kategori_layanan,id'],
            'q' => ['nullable', 'string', 'max:150'],
        ]);

        $q = trim((string) ($filters['q'] ?? ''));

        $jenisOptions = JenisLayanan::query()
            ->where('status_aktif', true)
            ->orderBy('urutan')
            ->get();

        $kategoriOptions = KategoriLayanan::query()
            ->with('jenisLayanan')
            ->where('status_aktif', true)
            ->when($filters['jenis_layanan_id'] ?? null, fn ($query, $jenisId) => $query->where('jenis_layanan_id', $jenisId))
            ->orderBy('jenis_layanan_id')
            ->orderBy('urutan')
            ->get();

        $jenisLayanan = JenisLayanan::query()
            ->where('status_aktif', true)
            ->when($filters['jenis_layanan_id'] ?? null, fn ($query, $jenisId) => $query->whereKey($jenisId))
            ->whereHas('kategoriLayanan', function ($query) use ($filters, $q) {
                $query->where('status_aktif', true)
                    ->when($filters['kategori_layanan_id'] ?? null, fn ($subQuery, $kategoriId) => $subQuery->whereKey($kategoriId))
                    ->when($q !== '', function ($subQuery) use ($q) {
                        $subQuery->where(function ($nested) use ($q) {
                            $nested->where('nama_kategori', 'like', "%{$q}%")
                                ->orWhereHas('itemTarifLayanan', fn ($itemQuery) => $itemQuery->where('nama_item', 'like', "%{$q}%"));
                        });
                    });
            })
            ->with(['kategoriLayanan' => function ($query) use ($filters, $q) {
                $query->where('status_aktif', true)
                    ->when($filters['kategori_layanan_id'] ?? null, fn ($subQuery, $kategoriId) => $subQuery->whereKey($kategoriId))
                    ->when($q !== '', function ($subQuery) use ($q) {
                        $subQuery->where(function ($nested) use ($q) {
                            $nested->where('nama_kategori', 'like', "%{$q}%")
                                ->orWhereHas('itemTarifLayanan', fn ($itemQuery) => $itemQuery->where('nama_item', 'like', "%{$q}%"));
                        });
                    })
                    ->with(['itemTarifLayanan' => function ($itemQuery) use ($q) {
                        $itemQuery->where('status_aktif', true)
                            ->when($q !== '', fn ($query) => $query->where('nama_item', 'like', "%{$q}%"))
                            ->orderBy('urutan');
                    }])
                    ->orderBy('urutan');
            }])
            ->orderBy('urutan')
            ->get();

        return view('tarif_layanan.index', compact('jenisLayanan', 'jenisOptions', 'kategoriOptions', 'filters'));
    }

    public function showKategori(KategoriLayanan $kategori)
    {
        $kategori->load([
            'jenisLayanan',
            'itemTarifLayanan' => fn ($query) => $query->where('status_aktif', true)->orderBy('urutan'),
        ]);

        return view('tarif_layanan.kategori-show', compact('kategori'));
    }

    public function showItem(ItemTarifLayanan $item)
    {
        $item->load(['jenisLayanan', 'kategoriLayanan']);

        return view('tarif_layanan.item-show', compact('item'));
    }
}
