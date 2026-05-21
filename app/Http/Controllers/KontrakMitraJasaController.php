<?php

namespace App\Http\Controllers;

use App\Models\KontrakMitraJasa;
use App\Models\LayananJasa;
use App\Models\MitraJasa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class KontrakMitraJasaController extends Controller
{
    public function create(MitraJasa $mitra)
    {
        $this->abortUnlessCanManageMitraMaster();

        return view('super_admin_jasa.mitra.kontrak-form', [
            'mitra' => $mitra,
            'kontrak' => new KontrakMitraJasa(),
            'layanans' => $this->layanansForMitra($mitra),
            'selectedLayananIds' => [],
        ]);
    }

    public function store(Request $request, MitraJasa $mitra)
    {
        $this->abortUnlessCanManageMitraMaster();

        $validated = $this->validateKontrak($request, true);
        $validated['mitra_jasa_id'] = $mitra->id;
        $validated['created_by'] = auth()->id();
        $validated['updated_by'] = auth()->id();

        if ($request->hasFile('file_kontrak')) {
            $validated['file_kontrak'] = $request->file('file_kontrak')->store('mitra-jasa/kontrak', 'public');
        }

        $kontrak = KontrakMitraJasa::create($validated);
        $this->syncLayanan($kontrak, $request->input('layanan_ids', []));

        return redirect()
            ->route('jasa.mitra.kontrak.show', [$mitra, $kontrak])
            ->with('success', 'Kontrak Mitra Jasa berhasil dibuat.');
    }

    public function show(MitraJasa $mitra, KontrakMitraJasa $kontrak)
    {
        $this->ensureOwnedByMitra($mitra, $kontrak);
        $kontrak->load('layananJasa.parent.parent.parent.parent.parent');

        return view('super_admin_jasa.mitra.kontrak-show', compact('mitra', 'kontrak'));
    }

    public function edit(MitraJasa $mitra, KontrakMitraJasa $kontrak)
    {
        $this->abortUnlessCanManageMitraMaster();
        $this->ensureOwnedByMitra($mitra, $kontrak);

        return view('super_admin_jasa.mitra.kontrak-form', [
            'mitra' => $mitra,
            'kontrak' => $kontrak->load('layananJasa'),
            'layanans' => $this->layanansForMitra($mitra),
            'selectedLayananIds' => $kontrak->layananJasa->pluck('id')->all(),
        ]);
    }

    public function update(Request $request, MitraJasa $mitra, KontrakMitraJasa $kontrak)
    {
        $this->abortUnlessCanManageMitraMaster();
        $this->ensureOwnedByMitra($mitra, $kontrak);

        $validated = $this->validateKontrak($request, false);
        $validated['updated_by'] = auth()->id();

        if ($request->hasFile('file_kontrak')) {
            if ($kontrak->file_kontrak) {
                Storage::disk('public')->delete($kontrak->file_kontrak);
            }

            $validated['file_kontrak'] = $request->file('file_kontrak')->store('mitra-jasa/kontrak', 'public');
        }

        $kontrak->update($validated);
        $this->syncLayanan($kontrak, $request->input('layanan_ids', []));

        return redirect()
            ->route('jasa.mitra.kontrak.show', [$mitra, $kontrak])
            ->with('success', 'Kontrak Mitra Jasa berhasil diperbarui.');
    }

    public function download(MitraJasa $mitra, KontrakMitraJasa $kontrak)
    {
        $this->ensureOwnedByMitra($mitra, $kontrak);

        abort_unless($kontrak->file_kontrak && Storage::disk('public')->exists($kontrak->file_kontrak), 404);

        return Storage::disk('public')->download($kontrak->file_kontrak);
    }

    public function destroy(MitraJasa $mitra, KontrakMitraJasa $kontrak)
    {
        $this->abortUnlessCanManageMitraMaster();
        $this->ensureOwnedByMitra($mitra, $kontrak);

        if ($kontrak->tagihanJasa()->exists()) {
            return redirect()
                ->route('jasa.mitra.show', $mitra)
                ->with('error', 'Kontrak tidak dapat dihapus karena sudah digunakan pada tagihan jasa.');
        }

        if ($kontrak->file_kontrak) {
            Storage::disk('public')->delete($kontrak->file_kontrak);
        }

        $kontrak->delete();

        return redirect()
            ->route('jasa.mitra.show', $mitra)
            ->with('success', 'Kontrak Mitra Jasa berhasil dihapus.');
    }

    private function validateKontrak(Request $request, bool $isCreate): array
    {
        return $request->validate([
            'nomor_kontrak' => ['required', 'string', 'max:255'],
            'nama_kontrak' => ['required', 'string', 'max:255'],
            'jenis_dokumen' => ['required', 'in:KONTRAK,PERJANJIAN_KERJA_SAMA,SURAT_PERMOHONAN,BERITA_ACARA,REKAP_PEMAKAIAN,DOKUMEN_LAINNYA'],
            'tanggal_kontrak' => ['required', 'date'],
            'tanggal_mulai' => ['required', 'date'],
            'tanggal_selesai' => ['required', 'date', 'after_or_equal:tanggal_mulai'],
            'file_kontrak' => [$isCreate ? 'required' : 'nullable', 'file', 'mimes:pdf', 'max:5120'],
            'status_kontrak' => ['required', 'in:DRAFT,AKTIF,BERAKHIR,DIBATALKAN'],
            'keterangan' => ['nullable', 'string'],
            'layanan_ids' => ['nullable', 'array'],
            'layanan_ids.*' => ['integer', 'exists:layanan_jasas,id'],
        ]);
    }

    private function ensureOwnedByMitra(MitraJasa $mitra, KontrakMitraJasa $kontrak): void
    {
        abort_unless((int) $kontrak->mitra_jasa_id === (int) $mitra->id, 404);
    }

    private function layanansForMitra(MitraJasa $mitra)
    {
        return $mitra->layananJasaAktif()
            ->where('layanan_jasas.is_active', true)
            ->where('layanan_jasas.is_leaf', true)
            ->with('parent.parent.parent.parent.parent')
            ->orderBy('layanan_jasas.nama_layanan')
            ->get();
    }

    private function syncLayanan(KontrakMitraJasa $kontrak, array $layananIds): void
    {
        $allowedIds = $kontrak->mitraJasa
            ->layananJasaAktif()
            ->where('layanan_jasas.is_active', true)
            ->where('layanan_jasas.is_leaf', true)
            ->pluck('layanan_jasas.id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $syncIds = collect($layananIds)
            ->map(fn ($id) => (int) $id)
            ->intersect($allowedIds)
            ->values()
            ->mapWithKeys(fn ($id) => [$id => ['created_by' => auth()->id()]])
            ->all();

        $kontrak->layananJasa()->sync($syncIds);
    }

    private function abortUnlessCanManageMitraMaster(): void
    {
        abort_unless(
            auth()->user()?->hasAnyRole(['Super Admin', 'Super Admin Jasa', 'Koordinator Jasa']) === true,
            403
        );
    }
}
