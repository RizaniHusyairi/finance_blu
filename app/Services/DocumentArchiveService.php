<?php

namespace App\Services;

use App\Models\ArsipDokumen;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class DocumentArchiveService
{
    public function list(Model $documentable, ?string $jenisDokumen = null)
    {
        return $documentable->arsipDokumen()
            ->when($jenisDokumen, fn ($query) => $query->where('jenis_dokumen', $jenisDokumen))
            ->latest()
            ->get();
    }

    public function upload(Model $documentable, string $jenisDokumen, UploadedFile $file, array $attributes = []): ArsipDokumen
    {
        // INF-01: pemanggil dokumen sensitif internal mengirim `'disk' => 'local'`
        // secara eksplisit (lihat call site INVOICE/FAKTUR_PAJAK/BAPP_GAMBAR_RAB/
        // LAMPIRAN_LAINNYA). Default dibiarkan `public` agar pemanggil lain yang
        // me-render via Storage::url() (mis. Honorarium) tidak ikut rusak —
        // migrasi default ke `local` menyusul setelah seluruh rendering dialihkan
        // ke route terproteksi arsip.view.
        $disk = $attributes['disk'] ?? 'public';
        $path = $file->store($attributes['directory'] ?? 'arsip-dokumen', $disk);

        return $documentable->arsipDokumen()->create([
            'jenis_dokumen' => $jenisDokumen,
            'nama_file_asli' => $file->getClientOriginalName(),
            'path_file' => $path,
            'disk' => $disk,
            'mime_type' => $file->getMimeType(),
            'ukuran_file' => $file->getSize(),
            'checksum' => hash_file('sha256', $file->getRealPath()),
            'uploaded_by' => $attributes['uploaded_by'] ?? auth()->id(),
            'uploaded_at' => now(),
            'keterangan' => $attributes['keterangan'] ?? null,
            'is_active' => $attributes['is_active'] ?? true,
        ]);
    }

    public function replace(Model $documentable, string $jenisDokumen, UploadedFile $file, array $attributes = []): ArsipDokumen
    {
        $documentable->arsipDokumen()
            ->where('jenis_dokumen', $jenisDokumen)
            ->where('is_active', true)
            ->get()
            ->each(function (ArsipDokumen $arsip) {
                $arsip->update(['is_active' => false]);
            });

        return $this->upload($documentable, $jenisDokumen, $file, $attributes);
    }

    public function download(ArsipDokumen $arsip)
    {
        return Storage::disk($arsip->disk)->download($arsip->path_file, $arsip->nama_file_asli);
    }

    /**
     * Tampilkan arsip secara inline (mis. tombol "Lihat" di halaman tagihan)
     * dari disk-nya — privat maupun publik — melalui route terproteksi.
     */
    public function view(ArsipDokumen $arsip)
    {
        return Storage::disk($arsip->disk)->response(
            $arsip->path_file,
            $arsip->nama_file_asli,
            ['Content-Type' => $arsip->mime_type ?: 'application/octet-stream']
        );
    }

    public function delete(ArsipDokumen $arsip): void
    {
        if (Storage::disk($arsip->disk)->exists($arsip->path_file)) {
            Storage::disk($arsip->disk)->delete($arsip->path_file);
        }

        $arsip->delete();
    }
}
