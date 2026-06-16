<?php

namespace App\Http\Controllers;

use App\Models\LayananJasa;
use App\Models\LayananJasaTarif;
use App\Models\MitraJasa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LayananTarifDiskonController extends Controller
{
    public function index()
    {
        // Peta nama lengkap layanan dibangun sekali tanpa N+1.
        $all = LayananJasa::query()->get(['id', 'parent_id', 'nama_layanan', 'kode_layanan', 'tarif_dasar', 'satuan', 'is_leaf']);
        $byId = $all->keyBy('id');

        $fullName = function ($layanan) use ($byId) {
            $names = [$layanan->nama_layanan];
            $parentId = $layanan->parent_id;
            $guard = 0;
            while ($parentId && $guard < 10) {
                $parent = $byId->get($parentId);
                if (! $parent) {
                    break;
                }
                array_unshift($names, $parent->nama_layanan);
                $parentId = $parent->parent_id;
                $guard++;
            }
            return implode(' > ', $names);
        };

        $leafLayanans = $all
            ->filter(fn ($l) => (bool) $l->is_leaf)
            ->map(fn ($l) => [
                'id' => $l->id,
                'nama' => $fullName($l),
                'kode' => $l->kode_layanan,
                'tarif_dasar' => (float) $l->tarif_dasar,
                'satuan' => $l->satuan,
            ])
            ->sortBy('nama')
            ->values();

        $mitras = MitraJasa::query()->orderBy('nama_mitra')->get(['id', 'nama_mitra']);

        $periodes = LayananJasaTarif::query()
            ->with(['layananJasa:id,nama_layanan,kode_layanan,tarif_dasar,satuan', 'mitra:id,nama_mitra'])
            ->orderByDesc('berlaku_mulai')
            ->orderByDesc('id')
            ->paginate(20);

        return view('jasa.tarif_diskon.index', compact('leafLayanans', 'mitras', 'periodes'));
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        $data['created_by'] = Auth::id();

        LayananJasaTarif::create($data);

        return back()->with('success', 'Periode tarif/diskon berhasil ditambahkan.');
    }

    public function update(Request $request, LayananJasaTarif $tarif_diskon)
    {
        $tarif_diskon->update($this->validateData($request));

        return back()->with('success', 'Periode tarif/diskon berhasil diperbarui.');
    }

    public function destroy(LayananJasaTarif $tarif_diskon)
    {
        $tarif_diskon->delete();

        return back()->with('success', 'Periode tarif/diskon berhasil dihapus.');
    }

    private function validateData(Request $request): array
    {
        $validated = $request->validate([
            'layanan_jasa_id' => ['required', 'exists:layanan_jasas,id'],
            'mitra_jasa_id' => ['nullable', 'exists:mitra_jasa,id'],
            'jenis' => ['required', 'in:DISKON,TARIF_KHUSUS'],
            'tarif' => ['required', 'numeric', 'min:0'],
            'persen_diskon' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'berlaku_mulai' => ['required', 'date'],
            'berlaku_sampai' => ['nullable', 'date', 'after_or_equal:berlaku_mulai'],
            'keterangan' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'berlaku_sampai.after_or_equal' => 'Tanggal berakhir tidak boleh sebelum tanggal mulai.',
        ]);

        // Pastikan layanan yang dipilih adalah item tarif (leaf).
        $layanan = LayananJasa::find($validated['layanan_jasa_id']);
        abort_unless($layanan && $layanan->is_leaf, 422, 'Tarif hanya dapat dipasang pada item tarif (layanan paling akhir).');

        $validated['is_active'] = (bool) ($validated['is_active'] ?? true);

        return $validated;
    }
}
