<?php

namespace App\Support;

use App\Models\DetailDipa;
use App\Models\MasterDipa;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;

class DipaBudgetOptionService
{
    public static function groupedOptions(): Collection
    {
        return MasterDipa::query()
            ->where('status_aktif', true)
            ->whereHas('activeRevision', function ($revisionQuery) {
                $revisionQuery
                    ->where('is_active', true)
                    ->whereHas('items', function ($itemQuery) {
                        $itemQuery->where('status_aktif', true)->whereHas('coa');
                    });
            })
            ->with([
                'activeRevision' => function ($revisionQuery) {
                    $revisionQuery
                        ->where('is_active', true)
                        ->with([
                            'items' => function ($itemQuery) {
                                $itemQuery
                                    ->where('status_aktif', true)
                                    ->with('coa')
                                    ->orderBy('id');
                            },
                        ]);
                },
            ])
            ->orderByDesc('tahun_anggaran')
            ->orderBy('nomor_dipa')
            ->get()
            ->map(function (MasterDipa $dipa) {
                $activeRevision = $dipa->activeRevision;

                return [
                    'label' => sprintf(
                        'TA %s | %s | Revisi %s',
                        $dipa->tahun_anggaran,
                        $dipa->nomor_dipa,
                        $activeRevision?->nomor_revisi ?? '-'
                    ),
                    'items' => $activeRevision?->items
                        ? $activeRevision->items
                            ->filter(fn (DetailDipa $item) => $item->coa !== null)
                            ->map(function (DetailDipa $item) use ($dipa, $activeRevision) {
                                return [
                                    'id' => $item->id,
                                    'master_dipa_id' => $dipa->id,
                                    'dipa_label' => $dipa->nomor_dipa,
                                    'revisi_label' => 'Revisi ' . ($activeRevision?->nomor_revisi ?? '-'),
                                    'coa_label' => $item->coa->kode_mak_lengkap,
                                    'nama_akun' => $item->coa->nama_akun,
                                    'jenis_akun' => $item->coa->jenis_akun,
                                    'nilai_pagu' => (float) $item->nilai_pagu,
                                    'sisa_pagu' => (float) $item->sisa_pagu,
                                    'option_label' => sprintf(
                                        '%s | %s | Pagu Rp %s | Sisa Rp %s',
                                        $item->coa->kode_mak_lengkap,
                                        $item->coa->nama_akun,
                                        number_format((float) $item->nilai_pagu, 0, ',', '.'),
                                        number_format((float) $item->sisa_pagu, 0, ',', '.')
                                    ),
                                ];
                            })
                            ->values()
                        : collect(),
                ];
            })
            ->filter(fn (array $group) => collect($group['items'])->isNotEmpty())
            ->values();
    }

    public static function resolveActiveItem(int|string $id): DetailDipa
    {
        $item = DetailDipa::query()
            ->whereKey($id)
            ->where('status_aktif', true)
            ->whereHas('coa')
            ->whereHas('dipaRevision', function ($revisionQuery) {
                $revisionQuery
                    ->where('is_active', true)
                    ->whereHas('masterDipa', function ($dipaQuery) {
                        $dipaQuery->where('status_aktif', true);
                    });
            })
            ->with(['coa', 'dipaRevision.masterDipa'])
            ->first();

        if (! $item) {
            throw (new ModelNotFoundException())->setModel(DetailDipa::class, [$id]);
        }

        return $item;
    }
}
