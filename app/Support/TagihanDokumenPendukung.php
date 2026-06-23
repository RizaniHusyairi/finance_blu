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

        $addArsip = function ($arsip, ?string $source = null) use ($items, $addFile) {
            // Versi lama yang sudah digantikan (is_active=false) tidak ditampilkan.
            if ($arsip !== null && isset($arsip->is_active) && ! $arsip->is_active) {
                return;
            }

            $path = $arsip?->path_file ?? $arsip?->file_path ?? null;
            $title = $arsip?->nama_file_asli
                ?: ($arsip?->jenis_dokumen ? ucwords(strtolower(str_replace('_', ' ', $arsip->jenis_dokumen))) : null);

            // INF-01: record ArsipDokumen disajikan via route terproteksi (auth +
            // role internal), bukan URL publik /storage. Berlaku untuk arsip di
            // disk privat (local) maupun yang masih publik — endpoint menyamakan
            // akses lewat kontrol aplikasi.
            if ($arsip?->id && trim((string) $path) !== '') {
                $items->push([
                    'title' => $title ?: basename((string) $path),
                    'path' => $path,
                    'url' => route('arsip.view', $arsip),
                    'source' => $source,
                    'is_generated' => false,
                ]);

                return;
            }

            // Fallback untuk objek tanpa id (mis. path mentah) — perilaku lama.
            $addFile($title, $path, $source, $arsip?->disk ?? null);
        };

        foreach ($tagihan->arsipDokumen ?? collect() as $arsip) {
            $addArsip($arsip, 'Tagihan');
        }

        if ($tagihan->detailKontrak) {
            $detail = $tagihan->detailKontrak;
            // INF-01: file kolom dokumen kontrak disajikan via route terproteksi
            // (auth + staf internal) `secure-file`, bukan URL publik /storage.
            $kontrakFiles = [
                'file_bapp' => 'Berita Acara Pemeriksaan Pekerjaan (BAPP)',
                'file_bast' => 'Berita Acara Serah Terima (BAST)',
                'file_bap' => 'Berita Acara Pembayaran (BAP)',
                'file_invoice' => 'Invoice Tagihan',
                'file_kwitansi' => 'Kwitansi Pembayaran',
                'file_faktur_pajak' => 'Faktur Pajak',
                'file_lampiran_lainnya' => 'Lampiran Lainnya',
            ];

            foreach ($kontrakFiles as $field => $title) {
                $path = $detail->$field;
                if (filled($path)) {
                    $items->push([
                        'title' => $title,
                        'path' => $path,
                        'url' => route('secure-file', ['tagihan-kontrak', $detail->id, $field]),
                        'source' => 'Detail Kontrak',
                        'is_generated' => false,
                    ]);
                }
            }

            foreach ($detail->arsipDokumen ?? collect() as $arsip) {
                $addArsip($arsip, 'Detail Kontrak');
            }
        }

        foreach ($tagihan->detailPerjaldin ?? collect() as $detail) {
            $nama = $detail->nama_pegawai ?? $detail->pegawai?->nama_lengkap ?? 'Peserta';
            // INF-01: bukti perjaldin disajikan via route terproteksi `secure-file`.
            $perjaldinFiles = [
                'spt_file_path' => 'Surat Tugas / SPT - ' . $nama,
                'tiket_file_path' => 'Tiket Perjalanan - ' . $nama,
                'transport_file_path' => 'Bukti Transport - ' . $nama,
                'penginapan_file_path' => 'Bukti Penginapan - ' . $nama,
                'uang_harian_file_path' => 'Bukti Uang Harian - ' . $nama,
            ];
            foreach ($perjaldinFiles as $field => $title) {
                $path = $detail->$field ?? null;
                if (filled($path)) {
                    $items->push([
                        'title' => $title,
                        'path' => $path,
                        'url' => route('secure-file', ['tagihan-perjaldin', $detail->id, $field]),
                        'source' => 'Perjaldin',
                        'is_generated' => false,
                    ]);
                }
            }
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
