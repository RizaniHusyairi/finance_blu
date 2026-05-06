<?php

namespace App\Support;

use App\Models\Tagihan;
use Carbon\Carbon;

class PaymentPdfReference
{
    private const DEFAULT_SUPPLIER_NAME = 'PARA PEGAWAI KANTOR UPBU AJI PANGERAN TUMENGGUNG PRANOTO';
    private const DEFAULT_SUPPLIER_ADDRESS = "Jl. Poros Samarinda - Bontang, Kel. Sunga Siring,\nSamarinda-Kalimantan Timur";

    public static function forTagihan(?Tagihan $tagihan): array
    {
        if (! $tagihan) {
            return self::emptyReference();
        }

        return match ($tagihan->tipe_tagihan) {
            'KONTRAK' => self::contractReference($tagihan),
            'PERJALDIN' => self::singleReference(
                'No. Perjaldin',
                $tagihan->nomor_tagihan,
                'Tgl. Perjaldin',
                $tagihan->tanggal_ttd ?? $tagihan->created_at
            ),
            'HONORARIUM' => self::singleReference(
                'No. Honorarium',
                $tagihan->nomor_tagihan,
                'Tgl. Honorarium',
                $tagihan->created_at
            ),
            default => self::singleReference(
                'No. Tagihan',
                $tagihan->nomor_tagihan,
                'Tgl. Tagihan',
                $tagihan->created_at
            ),
        };
    }

    public static function dipaForSpp(mixed $spp): array
    {
        if (! $spp) {
            return self::emptyDipa();
        }

        $tagihan = $spp->tagihan ?? null;
        $masterDipa = $spp->dipaRevisionItem?->dipaRevision?->masterDipa
            ?? $tagihan?->dipaRevisionItem?->dipaRevision?->masterDipa
            ?? $tagihan?->dipa
            ?? null;

        $nomorDipa = $masterDipa?->nomor_dipa
            ?? $spp->nomor_dipa
            ?? '-';

        $tanggalDipa = $masterDipa?->tanggal_disahkan
            ?? $spp->tanggal_dipa
            ?? null;

        return [
            'nomor' => $nomorDipa ?: '-',
            'tanggal' => self::formatLongDate($tanggalDipa),
        ];
    }

    public static function supplierForTagihan(?Tagihan $tagihan, ?string $fallbackUraian = null): array
    {
        if (! $tagihan) {
            return self::attachedSupplier($fallbackUraian);
        }

        return match ($tagihan->tipe_tagihan) {
            'KONTRAK' => self::contractSupplier($tagihan, $fallbackUraian),
            'HONORARIUM' => self::attachedSupplier(
                self::firstFilled($tagihan->deskripsi, $fallbackUraian),
                self::firstFilled($tagihan->getAttribute('nama_supplier'), self::DEFAULT_SUPPLIER_NAME)
            ),
            default => self::attachedSupplier(self::firstFilled($tagihan->deskripsi, $fallbackUraian)),
        };
    }

    private static function contractReference(Tagihan $tagihan): array
    {
        $detail = $tagihan->detailKontrak;
        $termin = $detail?->kontrakTermin;
        $kontrak = $termin?->kontrak;
        $isPelunasan = strtoupper((string) $termin?->jenis_termin) === 'PELUNASAN';

        return [
            'primary_label' => 'No. Kontrak',
            'primary_value' => $kontrak?->nomor_spk ?? '-',
            'primary_date_label' => 'Tgl. Kontrak',
            'primary_date_value' => self::formatDate($kontrak?->tanggal_spk),
            'secondary_label' => $isPelunasan ? 'No. BAST' : 'No. BAPP',
            'secondary_value' => $isPelunasan
                ? ($detail?->nomor_bast ?? '-')
                : ($detail?->nomor_bapp ?? '-'),
            'secondary_date_label' => $isPelunasan ? 'Tgl. BAST' : 'Tgl. BAPP',
            'secondary_date_value' => self::formatDate(
                $isPelunasan ? $detail?->tanggal_bast : $detail?->tanggal_bapp
            ),
        ];
    }

    private static function singleReference(string $numberLabel, ?string $number, string $dateLabel, mixed $date): array
    {
        return [
            'primary_label' => $numberLabel,
            'primary_value' => $number ?: '-',
            'primary_date_label' => $dateLabel,
            'primary_date_value' => self::formatDate($date),
            'secondary_label' => null,
            'secondary_value' => null,
            'secondary_date_label' => null,
            'secondary_date_value' => null,
        ];
    }

    private static function emptyReference(): array
    {
        return self::singleReference('No. Tagihan', null, 'Tgl. Tagihan', null);
    }

    private static function contractSupplier(Tagihan $tagihan, ?string $fallbackUraian): array
    {
        $detail = $tagihan->detailKontrak;
        $termin = $detail?->kontrakTermin;
        $kontrak = $termin?->kontrak;
        $vendor = $tagihan->pihak ?? $kontrak?->vendor;
        $rekening = self::defaultRekening($vendor);

        return [
            'nama_supplier' => self::valueOrDash($vendor?->nama_pihak),
            'npwp' => self::valueOrDash($vendor?->npwp),
            'bank_pos' => self::valueOrDash($rekening?->nama_bank),
            'rekening' => self::valueOrDash($rekening?->nomor_rekening),
            'alamat' => self::valueOrDash($vendor?->alamat),
            'nama_rekening' => self::valueOrDash($rekening?->nama_rekening ?? $vendor?->nama_pihak),
            'uraian' => self::valueOrDash(self::firstFilled(
                $tagihan->deskripsi,
                $fallbackUraian,
                $termin?->keterangan_termin,
                $kontrak?->nama_pekerjaan
            )),
        ];
    }

    private static function attachedSupplier(?string $uraian = null, ?string $namaSupplier = null): array
    {
        return [
            'nama_supplier' => self::firstFilled($namaSupplier, self::DEFAULT_SUPPLIER_NAME),
            'npwp' => 'Terlampir',
            'bank_pos' => 'Terlampir',
            'rekening' => 'Terlampir',
            'alamat' => self::DEFAULT_SUPPLIER_ADDRESS,
            'nama_rekening' => 'Terlampir',
            'uraian' => self::valueOrDash($uraian),
        ];
    }

    private static function defaultRekening(mixed $vendor): mixed
    {
        if (! $vendor) {
            return null;
        }

        $rekenings = $vendor->relationLoaded('rekening')
            ? $vendor->rekening
            : $vendor->rekening()->get();

        return $rekenings->first(fn ($rekening) => $rekening->is_default && $rekening->status_aktif)
            ?? $rekenings->firstWhere('is_default', true)
            ?? $rekenings->firstWhere('status_aktif', true)
            ?? $rekenings->first();
    }

    private static function emptyDipa(): array
    {
        return [
            'nomor' => '-',
            'tanggal' => '-',
        ];
    }

    private static function formatDate(mixed $date): string
    {
        return $date ? Carbon::parse($date)->format('d/m/Y') : '-';
    }

    private static function formatLongDate(mixed $date): string
    {
        return $date ? Carbon::parse($date)->locale('id')->isoFormat('D MMMM Y') : '-';
    }

    private static function firstFilled(mixed ...$values): ?string
    {
        foreach ($values as $value) {
            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }

            if (! is_string($value) && filled($value)) {
                return (string) $value;
            }
        }

        return null;
    }

    private static function valueOrDash(mixed $value): string
    {
        return self::firstFilled($value) ?? '-';
    }
}
