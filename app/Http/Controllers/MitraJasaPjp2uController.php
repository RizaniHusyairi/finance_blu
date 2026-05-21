<?php

namespace App\Http\Controllers;

use App\Models\LayananJasa;
use App\Models\MitraJasa;
use App\Models\MitraJasaPjp2u;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MitraJasaPjp2uController extends Controller
{
    public function create(MitraJasa $mitra)
    {
        $this->abortUnlessCanManageMitraMaster();

        return view('super_admin_jasa.mitra.pjp2u-form', [
            'mitra' => $mitra,
            'hak' => new MitraJasaPjp2u([
                'tanggal_mulai' => now()->toDateString(),
                'status_aktif' => true,
            ]),
            'layanans' => $this->pjp2uLayanansForMitra($mitra),
            'kontraks' => $mitra->kontrak()->with('layananJasa')->orderByDesc('tanggal_kontrak')->get(),
        ]);
    }

    public function store(Request $request, MitraJasa $mitra)
    {
        $this->abortUnlessCanManageMitraMaster();

        $validated = $this->validateHak($request, $mitra);
        $validated['mitra_jasa_id'] = $mitra->id;
        $validated['status_aktif'] = $request->boolean('status_aktif', true);
        $validated['created_by'] = auth()->id();
        $validated['updated_by'] = auth()->id();

        MitraJasaPjp2u::create($validated);

        return redirect()
            ->route('jasa.mitra.show', $mitra)
            ->with('success', 'Hak PJP2U mitra berhasil ditambahkan.');
    }

    public function edit(MitraJasa $mitra, MitraJasaPjp2u $pjp2u)
    {
        $this->abortUnlessCanManageMitraMaster();
        $this->ensureOwnedByMitra($mitra, $pjp2u);

        return view('super_admin_jasa.mitra.pjp2u-form', [
            'mitra' => $mitra,
            'hak' => $pjp2u,
            'layanans' => $this->pjp2uLayanansForMitra($mitra),
            'kontraks' => $mitra->kontrak()->with('layananJasa')->orderByDesc('tanggal_kontrak')->get(),
        ]);
    }

    public function update(Request $request, MitraJasa $mitra, MitraJasaPjp2u $pjp2u)
    {
        $this->abortUnlessCanManageMitraMaster();
        $this->ensureOwnedByMitra($mitra, $pjp2u);

        $validated = $this->validateHak($request, $mitra);
        $validated['status_aktif'] = $request->boolean('status_aktif');
        $validated['updated_by'] = auth()->id();

        $pjp2u->update($validated);

        return redirect()
            ->route('jasa.mitra.show', $mitra)
            ->with('success', 'Hak PJP2U mitra berhasil diperbarui.');
    }

    public function deactivate(MitraJasa $mitra, MitraJasaPjp2u $pjp2u)
    {
        $this->abortUnlessCanManageMitraMaster();
        $this->ensureOwnedByMitra($mitra, $pjp2u);

        $pjp2u->update([
            'status_aktif' => false,
            'updated_by' => auth()->id(),
        ]);

        return back()->with('success', 'Hak PJP2U mitra berhasil dinonaktifkan.');
    }

    private function validateHak(Request $request, MitraJasa $mitra): array
    {
        $allowedLayananIds = $this->pjp2uLayanansForMitra($mitra)->pluck('id')->map(fn ($id) => (string) $id)->all();

        $validated = $request->validate([
            'layanan_jasa_id' => ['required', Rule::in($allowedLayananIds)],
            'kontrak_mitra_jasa_id' => ['nullable', Rule::exists('kontrak_mitra_jasa', 'id')->where('mitra_jasa_id', $mitra->id)],
            'tanggal_mulai' => ['nullable', 'date'],
            'tanggal_selesai' => ['nullable', 'date', 'after_or_equal:tanggal_mulai'],
            'status_aktif' => ['nullable', 'boolean'],
            'keterangan' => ['nullable', 'string'],
        ]);

        if (! empty($validated['kontrak_mitra_jasa_id'])) {
            $kontrak = $mitra->kontrak()->with('layananJasa')->findOrFail($validated['kontrak_mitra_jasa_id']);
            $scopedIds = $kontrak->layananJasa->pluck('id')->map(fn ($id) => (int) $id);

            if ($scopedIds->isNotEmpty() && ! $scopedIds->contains((int) $validated['layanan_jasa_id'])) {
                back()
                    ->withInput()
                    ->with('error', 'Kontrak yang dipilih tidak mencakup layanan PJP2U tersebut.')
                    ->throwResponse();
            }
        }

        return $validated;
    }

    private function pjp2uLayanansForMitra(MitraJasa $mitra)
    {
        return $mitra->layananJasaAktif()
            ->where('layanan_jasas.is_active', true)
            ->where('layanan_jasas.is_leaf', true)
            ->with('parent.parent.parent.parent.parent')
            ->get()
            ->filter(fn (LayananJasa $layanan) => $layanan->isPjp2u())
            ->values();
    }

    private function ensureOwnedByMitra(MitraJasa $mitra, MitraJasaPjp2u $pjp2u): void
    {
        abort_unless((int) $pjp2u->mitra_jasa_id === (int) $mitra->id, 404);
    }

    private function abortUnlessCanManageMitraMaster(): void
    {
        abort_unless(
            auth()->user()?->hasAnyRole(['Super Admin', 'Super Admin Jasa', 'Koordinator Jasa']) === true,
            403
        );
    }
}
