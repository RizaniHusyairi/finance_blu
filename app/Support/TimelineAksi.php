<?php

namespace App\Support;

use App\Models\DokumenNpi;
use App\Models\DokumenSp2d;
use App\Models\DokumenSpm;
use App\Models\DokumenSpp;
use App\Models\PotonganTagihan;
use App\Models\Spp;
use Illuminate\Support\Str;

/**
 * Katalog label/ikon/warna berbahasa Indonesia untuk aksi pada timeline
 * "Aktivitas Terakhir" halaman Proses Tagihan. Aksi disimpan mentah di
 * log_status_dokumen (jangan di-rename demi kompatibilitas); kamus ini
 * hanya lapisan presentasi.
 */
final class TimelineAksi
{
    public const CATALOG = [
        // ── Siklus tagihan ─────────────────────────────────────────────
        'DIBUAT' => ['label' => 'Tagihan dibuat',                    'icon' => 'bi-file-earmark-plus',       'color' => 'primary'],
        'CREATE' => ['label' => 'Tagihan dibuat',                    'icon' => 'bi-file-earmark-plus',       'color' => 'primary'],
        'SIMPAN_DRAFT' => ['label' => 'Draft tagihan disimpan',           'icon' => 'bi-file-earmark-plus',       'color' => 'secondary'],
        'UPDATE_DRAFT' => ['label' => 'Draft tagihan diperbarui',         'icon' => 'bi-pencil-square',           'color' => 'secondary'],
        'UPDATE' => ['label' => 'Data tagihan diperbarui',           'icon' => 'bi-pencil-square',           'color' => 'secondary'],
        'DIPERBARUI' => ['label' => 'Data tagihan diperbarui',           'icon' => 'bi-pencil-square',           'color' => 'secondary'],
        'DIAJUKAN' => ['label' => 'Tagihan diajukan untuk verifikasi', 'icon' => 'bi-send',                    'color' => 'primary'],
        'SUBMIT' => ['label' => 'Tagihan diajukan ke alur verifikasi', 'icon' => 'bi-send',                  'color' => 'primary'],
        'APPROVE' => ['label' => 'Tagihan disetujui verifikator',     'icon' => 'bi-check-circle',            'color' => 'success'],
        'REVISION' => ['label' => 'Tagihan diminta revisi',            'icon' => 'bi-arrow-counterclockwise',  'color' => 'warning'],
        'REJECT' => ['label' => 'Tagihan ditolak',                   'icon' => 'bi-x-circle',                'color' => 'danger'],
        'APPROVE_PPK' => ['label' => 'Disetujui PPK',                     'icon' => 'bi-check-circle',            'color' => 'success'],
        'REJECT_PPK' => ['label' => 'Ditolak PPK',                       'icon' => 'bi-x-circle',                'color' => 'danger'],

        // ── Prasyarat rantai dokumen ───────────────────────────────────
        'SET_PAJAK_KONTRAK' => ['label' => 'Pajak & faktur pajak diatur',           'icon' => 'bi-percent',       'color' => 'info'],
        'SET_COA' => ['label' => 'COA dibebankan',                        'icon' => 'bi-diagram-3',     'color' => 'info'],
        'KIRIM_WA_KPA' => ['label' => 'Permohonan persetujuan dikirim ke KPA', 'icon' => 'bi-whatsapp',      'color' => 'info'],
        'KPA_SETUJU' => ['label' => 'KPA menyetujui tagihan',                'icon' => 'bi-patch-check',   'color' => 'success'],
        'KPA_TOLAK' => ['label' => 'KPA menolak tagihan',                   'icon' => 'bi-patch-exclamation', 'color' => 'danger'],
        'TTD_VENDOR' => ['label' => 'Vendor menandatangani dokumen (TTE)',   'icon' => 'bi-vector-pen',    'color' => 'success'],
        'TTD_PEMERIKSA' => ['label' => 'Tim Pemeriksa menandatangani (TTE)',    'icon' => 'bi-vector-pen',    'color' => 'success'],
        'UPLOAD_MANUAL_TTD' => ['label' => 'Scan TTD basah vendor diunggah manual', 'icon' => 'bi-upload',        'color' => 'info'],

        // ── Rantai dokumen pencairan ───────────────────────────────────
        'GENERATE_DRAFT_CHAIN' => ['label' => 'Draft SPP/SPM/NPI/SP2D diterbitkan', 'icon' => 'bi-collection',    'color' => 'primary'],
        'CANCEL_DRAFT_CHAIN' => ['label' => 'Rantai dokumen dibatalkan',          'icon' => 'bi-trash3',        'color' => 'danger'],
        'SUBMIT_SPP' => ['label' => 'SPP diajukan untuk verifikasi',               'icon' => 'bi-send',          'color' => 'primary'],
        'SUBMIT_SPM' => ['label' => 'SPM diajukan untuk verifikasi',               'icon' => 'bi-send',          'color' => 'primary'],
        'SUBMIT_NPI' => ['label' => 'NPI diajukan untuk verifikasi',               'icon' => 'bi-send',          'color' => 'primary'],
        'SUBMIT_SP2D' => ['label' => 'Bukti transfer diunggah; SP2D diajukan',      'icon' => 'bi-receipt',       'color' => 'primary'],
        'APPROVE_SPP' => ['label' => 'SPP disetujui verifikator',                  'icon' => 'bi-check-circle',  'color' => 'success'],
        'APPROVE_SPM' => ['label' => 'SPM disetujui verifikator',                  'icon' => 'bi-check-circle',  'color' => 'success'],
        'APPROVE_NPI' => ['label' => 'NPI disetujui verifikator',                  'icon' => 'bi-check-circle',  'color' => 'success'],
        'APPROVE_SP2D' => ['label' => 'SP2D disetujui PPK',                         'icon' => 'bi-check-circle',  'color' => 'success'],
        'FINAL_SPP' => ['label' => 'SPP disetujui final (seluruh verifikator)',   'icon' => 'bi-patch-check-fill', 'color' => 'success'],
        'FINAL_SPM' => ['label' => 'SPM disetujui final (seluruh verifikator)',   'icon' => 'bi-patch-check-fill', 'color' => 'success'],
        'FINAL_NPI' => ['label' => 'NPI disetujui final (seluruh verifikator)',   'icon' => 'bi-patch-check-fill', 'color' => 'success'],
        'REVISI_DIMINTA' => ['label' => 'Revisi diminta verifikator',        'icon' => 'bi-arrow-counterclockwise', 'color' => 'warning'],
        'REVISI_PAJAK' => ['label' => 'Dikembalikan untuk perbaikan pajak', 'icon' => 'bi-arrow-counterclockwise', 'color' => 'warning'],
        'REVISI_COA' => ['label' => 'Dikembalikan untuk perbaikan COA',  'icon' => 'bi-arrow-counterclockwise', 'color' => 'warning'],
        'REVISI_BUKTI_TRANSFER' => ['label' => 'Bukti transfer diminta diganti',    'icon' => 'bi-arrow-counterclockwise', 'color' => 'warning'],
        'KEMBALI_KE_PEMBUAT' => ['label' => 'Tagihan dikembalikan ke pembuatnya', 'icon' => 'bi-reply',        'color' => 'warning'],
        'EXECUTE_PAYMENT' => ['label' => 'SP2D terbit — dana dicairkan',            'icon' => 'bi-cash-coin',     'color' => 'success'],
        'SP2D_FINAL' => ['label' => 'Tagihan selesai (SP2D terbit)',           'icon' => 'bi-flag',          'color' => 'success'],
        'CREATE_SPP_KOMPONEN' => ['label' => 'SPP komponen perjaldin dibuat',       'icon' => 'bi-file-earmark-plus', 'color' => 'primary'],

        // ── Pajak & pembukuan ──────────────────────────────────────────
        'INPUT_KODE_BILLING' => ['label' => 'Kode billing pajak diinput',         'icon' => 'bi-upc-scan',      'color' => 'info'],
        'INPUT_NTPN' => ['label' => 'NTPN & bukti setor pajak diinput',   'icon' => 'bi-safe',          'color' => 'success'],
        'FINALIZE_BUPOT_HONOR' => ['label' => 'Bukti potong honor difinalkan',      'icon' => 'bi-file-earmark-check', 'color' => 'info'],
        'POST_BKU' => ['label' => 'Tercatat di Buku Kas Umum',          'icon' => 'bi-journal-check', 'color' => 'success'],
    ];

    /** Chip konteks dokumen untuk log ber-tipe anak (null = log Tagihan). */
    public const DOKUMEN_CONTEXT = [
        DokumenSpp::class => 'SPP',
        Spp::class => 'SPP',
        DokumenSpm::class => 'SPM',
        DokumenNpi::class => 'NPI',
        DokumenSp2d::class => 'SP2D',
        PotonganTagihan::class => 'Pajak',
    ];

    /** @return array{label: string, icon: string, color: string} */
    public static function meta(string $aksi): array
    {
        if (isset(self::CATALOG[$aksi])) {
            return self::CATALOG[$aksi];
        }

        // Fallback berpola untuk aksi dinamis (APPROVE_BENPEN, REVISI_KASUBBAG,
        // UPLOAD_SPD, …) agar tetap terbaca manusiawi tanpa entri eksplisit.
        return match (true) {
            str_starts_with($aksi, 'APPROVE_') => ['label' => 'Disetujui '.self::humanize(Str::after($aksi, 'APPROVE_')), 'icon' => 'bi-check-circle', 'color' => 'success'],
            str_starts_with($aksi, 'REVISI_') => ['label' => 'Revisi diminta '.self::humanize(Str::after($aksi, 'REVISI_')), 'icon' => 'bi-arrow-counterclockwise', 'color' => 'warning'],
            str_starts_with($aksi, 'REJECT_') => ['label' => 'Ditolak '.self::humanize(Str::after($aksi, 'REJECT_')), 'icon' => 'bi-x-circle', 'color' => 'danger'],
            str_starts_with($aksi, 'UPLOAD_') => ['label' => 'Unggah '.self::humanize(Str::after($aksi, 'UPLOAD_')), 'icon' => 'bi-upload', 'color' => 'info'],
            default => ['label' => Str::ucfirst(strtolower(str_replace('_', ' ', $aksi))), 'icon' => 'bi-dot', 'color' => 'secondary'],
        };
    }

    public static function dokumenContext(?string $dokumenType): ?string
    {
        return $dokumenType ? (self::DOKUMEN_CONTEXT[ltrim($dokumenType, '\\')] ?? null) : null;
    }

    private static function humanize(string $fragment): string
    {
        return str_replace('_', ' ', $fragment);
    }
}
