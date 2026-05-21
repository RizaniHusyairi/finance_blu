<?php

namespace App\Services;

use App\Models\LayananJasa;
use App\Models\MasterPihak;
use App\Models\MitraJasa;
use App\Models\User;

class JasaAccessService
{
    public function canManageAllJasa(User $user): bool
    {
        return $user->hasAnyRole(['Super Admin', 'Super Admin Jasa', 'Koordinator Jasa']);
    }

    public function getAllowedLayananForTagihan(User $user, MasterPihak|MitraJasa|null $mitra = null)
    {
        $query = LayananJasa::query()
            ->where('is_active', true)
            ->where('is_leaf', true)
            ->where(function ($query) {
                $query->where('tipe_layanan', 'PNBP')
                    ->orWhere('tipe_layanan', 'KONSESI')
                    ->orWhere('mendukung_konsesi', true);
            })
            ->orderBy('level')
            ->orderBy('id');

        if ($mitra) {
            $mitraIds = $mitra->layananJasaAktif()
                ->where('layanan_jasas.is_active', true)
                ->where('layanan_jasas.is_leaf', true)
                ->where(function ($query) {
                    $query->where('layanan_jasas.tipe_layanan', 'PNBP')
                        ->orWhere('layanan_jasas.tipe_layanan', 'KONSESI')
                        ->orWhere('layanan_jasas.mendukung_konsesi', true);
                })
                ->pluck('layanan_jasas.id');

            if ($mitraIds->isEmpty()) {
                return collect();
            }

            $query->whereIn('id', $mitraIds);
        }

        if (! $this->canManageAllJasa($user)) {
            $adminIds = $user->layananJasaDikelolaAktif()
                ->where('layanan_jasas.is_active', true)
                ->where('layanan_jasas.is_leaf', true)
                ->where(function ($query) {
                    $query->where('layanan_jasas.tipe_layanan', 'PNBP')
                        ->orWhere('layanan_jasas.tipe_layanan', 'KONSESI')
                        ->orWhere('layanan_jasas.mendukung_konsesi', true);
                })
                ->pluck('layanan_jasas.id');

            if ($adminIds->isEmpty()) {
                return collect();
            }

            $query->whereIn('id', $adminIds);
        }

        return $query->get();
    }

    public function canUseLayananForMitra(User $user, MasterPihak|MitraJasa $mitra, int $layananJasaId): bool
    {
        return $this->getAllowedLayananForTagihan($user, $mitra)
            ->contains('id', $layananJasaId);
    }
}
