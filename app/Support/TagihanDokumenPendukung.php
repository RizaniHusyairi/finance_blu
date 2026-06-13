<?php

namespace App\Support;

use App\Models\Tagihan;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

/**
 * Pengumpul daftar dokumen pendukung sebuah tagihan — baik yang diunggah
 * operator/vendor saat pembuatan tagihan maupun yang di-generate sistem
 * (BAPP/BAST/BAP ber-TTE, faktur pajak, daftar nominatif, dsb).
 *
 * Dipakai halaman persetujuan KPA dan halaman detail Proses Tagihan.
 * Setiap item: ['title', 'path', 'url', 'source', 'is_generated'].
 */
class TagihanDokumenPendukung
{
    public static function collect(Tagihan $tagihan): Collection
    {
        $items = collect();

        $addFile = function (?string $title, ?string $path, ?string $source = null, ?string $disk = null) use ($items) {
            $path = trim((string) $path);
            if ($path === '') {
                return;
            }

            // Bangun URL dari origin request yang aktif (bukan APP_URL via
            // Storage::url) supaya link tetap benar saat APP_URL tidak sama
            // dengan host yang dipakai mengakses aplikasi (mis. artisan serve).
            $disk = $disk ?: 'public';
            if ($disk === 'public') {
                $url = url('storage/' . ltrim($path, '/'));
            } else {
                try {
                    $url = Storage::disk($disk)->url($path);
                } catch (\Throwable) {
                    $url = url('storage/' . ltrim($path, '/'));
                }
            }

            $items->push([
                'title' => $title ?: basename($path),
                'path' => $path,
                'url' => $url,
                'source' => $source,
                'is_generated' => false,
            ]);
        };

        $addUrl = function (string $title, string $url, ?string $source = null) use ($items) {
            $items->push([
                'title' => $title,
                'path' => null,
                'url' => $url,
                'source' => $source,
                'is_generated' => true,
            ]);
        };

        $addArsip = function ($arsip, ?string $source = null) use ($addFile) {
            // Versi lama yang sudah digantikan (is_active=false) tidak ditampilkan.
            if ($arsip !== null && isset($arsip->is_active) && ! $arsip->is_active) {
                return;
            }

            $path = $arsip?->path_file ?? $arsip?->file_path ?? null;
            $title = $arsip?->nama_file_asli
                ?: ($arsip?->jenis_dokumen ? ucwords(strtolower(str_replace('_', ' ', $arsip->jenis_dokumen))) : null);

            $addFile($title, $path, $source, $arsip?->disk ?? null);
        };

        foreach ($tagihan->arsipDokumen ?? collect() as $arsip) {
            $addArsip($arsip, 'Tagihan');
        }

        if ($tagihan->detailKontrak) {
            $detail = $tagihan->detailKontrak;
            $kontrakFiles = [
                'Berita Acara Pemeriksaan Pekerjaan (BAPP)' => $detail->file_bapp,
                'Berita Acara Serah Terima (BAST)' => $detail->file_bast,
                'Berita Acara Pembayaran (BAP)' => $detail->file_bap,
                'Invoice Tagihan' => $detail->file_invoice,
                'Kwitansi Pembayaran' => $detail->file_kwitansi,
                'Faktur Pajak' => $detail->file_faktur_pajak,
                'Lampiran Lainnya' => $detail->file_lampiran_lainnya,
            ];

            foreach ($kontrakFiles as $title => $path) {
                $addFile($title, $path, 'Detail Kontrak');
            }

            foreach ($detail->arsipDokumen ?? collect() as $arsip) {
                $addArsip($arsip, 'Detail Kontrak');
            }
        }

        foreach ($tagihan->detailPerjaldin ?? collect() as $detail) {
            $nama = $detail->nama_pegawai ?? $detail->pegawai?->nama_lengkap ?? 'Peserta';
            $addFile('Surat Tugas / SPT - ' . $nama, $detail->spt_file_path ?? null, 'Perjaldin');
            $addFile('Tiket Perjalanan - ' . $nama, $detail->tiket_file_path ?? null, 'Perjaldin');
            $addFile('Bukti Transport - ' . $nama, $detail->transport_file_path ?? null, 'Perjaldin');
            $addFile('Bukti Penginapan - ' . $nama, $detail->penginapan_file_path ?? null, 'Perjaldin');
            $addFile('Bukti Uang Harian - ' . $nama, $detail->uang_harian_file_path ?? null, 'Perjaldin');
        }

        foreach ($tagihan->potonganTagihan ?? collect() as $potongan) {
            foreach ($potongan->arsipDokumen ?? collect() as $arsip) {
                $addArsip($arsip, 'Pajak/Potongan');
            }
        }

        if ($tagihan->tipe_tagihan === 'HONORARIUM') {
            $addUrl('Daftar Nominatif Honorarium', route('honorarium.pdf-nominatif', $tagihan->id), 'Dokumen Sistem');
            $addUrl('Dokumen Honorarium', route('honorarium.pdf', $tagihan->id), 'Dokumen Sistem');
        }

        if ($tagihan->tipe_tagihan === 'PERJALDIN') {
            $addUrl('Daftar Nominatif Perjaldin', route('perjaldins.pdf-nominatif', $tagihan->id), 'Dokumen Sistem');
            $addUrl('Daftar Nominatif Pembayaran Perjaldin', route('perjaldins.pdf-lampiran', $tagihan->id), 'Dokumen Sistem');
        }

        return $items
            ->unique(fn ($item) => $item['url'] . '|' . $item['title'])
            ->values();
    }
}
