<?php

namespace App\Services;

use App\Models\LayananJasa;
use App\Models\LayananJasaTarif;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Resolusi tarif efektif layanan jasa berbasis tanggal (temporal pricing).
 *
 * Tarif normal = layanan_jasas.tarif_dasar. Bila ada periode diskon/tarif khusus
 * (layanan_jasa_tarifs) yang berlaku pada tanggal acuan, tarif itulah yang dipakai.
 * Begitu periode berakhir, otomatis kembali ke tarif normal.
 *
 * Prioritas bila ada lebih dari satu periode berlaku:
 *  1. Periode khusus mitra (mitra_jasa_id = X) mengalahkan periode umum (null).
 *  2. berlaku_mulai paling baru.
 *  3. id paling besar.
 */
class TarifLayananService
{
    /**
     * @return array{tarif: float, tarif_normal: float, is_diskon: bool, berlaku_sampai: ?string, persen: ?float, keterangan: ?string}
     */
    public function resolve(LayananJasa $layanan, Carbon|string|null $tanggal = null, ?int $mitraJasaId = null): array
    {
        $tarifNormal = (float) ($layanan->tarif_dasar ?? 0);

        $periode = LayananJasaTarif::query()
            ->where('layanan_jasa_id', $layanan->id)
            ->berlakuPada($tanggal)
            ->where(function ($q) use ($mitraJasaId) {
                $q->whereNull('mitra_jasa_id');
                if ($mitraJasaId) {
                    $q->orWhere('mitra_jasa_id', $mitraJasaId);
                }
            })
            ->get();

        return $this->pickEffective($periode, $tarifNormal, $mitraJasaId);
    }

    /**
     * Tempelkan atribut tarif efektif ke setiap LayananJasa pada koleksi (1 query).
     * Atribut yang ditambahkan: tarif_efektif, tarif_normal, diskon_aktif,
     * diskon_sampai, diskon_persen, diskon_keterangan.
     */
    public function attachTarifEfektif(Collection $layanans, Carbon|string|null $tanggal = null, ?int $mitraJasaId = null): void
    {
        $leafIds = $layanans->filter(fn ($l) => (bool) ($l->is_leaf ?? false))->pluck('id')->all();

        $periodesByLayanan = collect();
        if ($leafIds !== []) {
            $periodesByLayanan = LayananJasaTarif::query()
                ->whereIn('layanan_jasa_id', $leafIds)
                ->berlakuPada($tanggal)
                ->where(function ($q) use ($mitraJasaId) {
                    $q->whereNull('mitra_jasa_id');
                    if ($mitraJasaId) {
                        $q->orWhere('mitra_jasa_id', $mitraJasaId);
                    }
                })
                ->get()
                ->groupBy('layanan_jasa_id');
        }

        foreach ($layanans as $layanan) {
            $tarifNormal = (float) ($layanan->tarif_dasar ?? 0);
            $info = $this->pickEffective(
                $periodesByLayanan->get($layanan->id) ?? collect(),
                $tarifNormal,
                $mitraJasaId
            );

            $layanan->setAttribute('tarif_efektif', $info['tarif']);
            $layanan->setAttribute('tarif_normal', $info['tarif_normal']);
            $layanan->setAttribute('diskon_aktif', $info['is_diskon']);
            $layanan->setAttribute('diskon_sampai', $info['berlaku_sampai']);
            $layanan->setAttribute('diskon_persen', $info['persen']);
            $layanan->setAttribute('diskon_keterangan', $info['keterangan']);
        }
    }

    /**
     * @param  Collection<int, LayananJasaTarif>  $periode
     */
    private function pickEffective(Collection $periode, float $tarifNormal, ?int $mitraJasaId): array
    {
        $base = [
            'tarif' => $tarifNormal,
            'tarif_normal' => $tarifNormal,
            'is_diskon' => false,
            'berlaku_sampai' => null,
            'persen' => null,
            'keterangan' => null,
        ];

        if ($periode->isEmpty()) {
            return $base;
        }

        $winner = $periode
            ->sortByDesc(fn (LayananJasaTarif $p) => [
                $mitraJasaId && (int) $p->mitra_jasa_id === (int) $mitraJasaId ? 1 : 0,
                optional($p->berlaku_mulai)->timestamp ?? 0,
                $p->id,
            ])
            ->first();

        if (! $winner) {
            return $base;
        }

        return [
            'tarif' => (float) $winner->tarif,
            'tarif_normal' => $tarifNormal,
            'is_diskon' => true,
            'berlaku_sampai' => $winner->berlaku_sampai?->toDateString(),
            'persen' => $winner->persen_diskon !== null ? (float) $winner->persen_diskon : null,
            'keterangan' => $winner->keterangan,
        ];
    }
}
