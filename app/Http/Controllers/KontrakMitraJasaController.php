<?php

namespace App\Http\Controllers;

use App\Models\KontrakMitraJasa;
use App\Models\LayananJasa;
use App\Models\MitraJasa;
use App\Services\MitraLayananService;
use App\Support\PdfCompressor;
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
            $validated['file_kontrak'] = PdfCompressor::storeCompressed(
                $request->file('file_kontrak'),
                'mitra-jasa/kontrak',
                'local'
            );
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
        $kontrak->load('layananJasa.parent.parent.parent.parent.parent')
            ->loadCount(['tagihanJasa', 'konsesi', 'penjualan', 'pjp2u']);

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
            $this->deleteFileKontrak($kontrak->file_kontrak);

            $validated['file_kontrak'] = PdfCompressor::storeCompressed(
                $request->file('file_kontrak'),
                'mitra-jasa/kontrak',
                'local'
            );
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

        // INF-01: file kontrak kini di disk privat `local` (fallback `public` untuk
        // file lama yang belum dimigrasi). Disajikan via streaming terotentikasi.
        $disk = $kontrak->file_kontrak && Storage::disk('local')->exists($kontrak->file_kontrak) ? 'local' : 'public';
        abort_unless($kontrak->file_kontrak && Storage::disk($disk)->exists($kontrak->file_kontrak), 404);

        return Storage::disk($disk)->download($kontrak->file_kontrak);
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

        $this->deleteFileKontrak($kontrak->file_kontrak);

        $kontrak->delete();

        // Pool layanan mitra disesuaikan ulang dari kontrak yang tersisa.
        app(MitraLayananService::class)->syncFromKontrak($mitra, auth()->id());

        return redirect()
            ->route('jasa.mitra.show', $mitra)
            ->with('success', 'Kontrak Mitra Jasa berhasil dihapus.');
    }

    private function deleteFileKontrak(?string $path): void
    {
        if (! $path) {
            return;
        }

        // INF-01: file kini tersimpan di disk privat `local` (fallback `public`
        // untuk file lama yang belum dimigrasi). Resolusi disk selaras dengan download().
        $disk = Storage::disk('local')->exists($path) ? 'local' : 'public';
        Storage::disk($disk)->delete($path);
    }

    private function validateKontrak(Request $request, bool $isCreate): array
    {
        return $request->validate([
            'nomor_kontrak' => ['required', 'string', 'max:255'],
            'nama_kontrak' => ['required', 'string', 'max:255'],
            'jenis_dokumen' => ['required', 'in:' . implode(',', array_keys(KontrakMitraJasa::JENIS_DOKUMEN))],
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
        // Sumber pilihan = master layanan aktif (leaf), bukan pool mitra — memecah
        // sirkularitas: pool justru DITURUNKAN dari kontrak (lihat syncLayanan).
        return LayananJasa::query()
            ->where('is_active', true)
            ->where('is_leaf', true)
            ->with('parent.parent.parent.parent.parent')
            ->orderBy('nama_layanan')
            ->get();
    }

    private function syncLayanan(KontrakMitraJasa $kontrak, array $layananIds): void
    {
        $allowedIds = LayananJasa::query()
            ->where('is_active', true)
            ->where('is_leaf', true)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $syncIds = collect($layananIds)
            ->map(fn ($id) => (int) $id)
            ->intersect($allowedIds)
            ->values()
            ->mapWithKeys(fn ($id) => [$id => ['created_by' => auth()->id()]])
            ->all();

        $kontrak->layananJasa()->sync($syncIds);

        // Pool layanan mitra = turunan dari kontrak AKTIF.
        app(MitraLayananService::class)->syncFromKontrak($kontrak->mitraJasa, auth()->id());
    }

    private function abortUnlessCanManageMitraMaster(): void
    {
        abort_unless(
            auth()->user()?->hasAnyRole(['Super Admin', 'Super Admin Jasa', 'Koordinator Jasa']) === true,
            403
        );
    }
}
