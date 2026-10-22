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

        if ($mitra && ! $this->canManageAllJasa($user)) {
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

    /**
     * Pola jalur layanan (nama layanan beserta induknya) yang memakai data operasional AMC.
     * - penerbangan: tagihan pendaratan / penempatan / penyimpanan pesawat dan penggunaan bandara
     *   di luar jam operasi — bisa untuk penerbangan non-schedule, jadi butuh Permohonan Non-Schedule.
     *   "Luar Jam Operasional Bandar Udara" (fasilitas & personil) sengaja tidak cocok.
     * - garbarata: Jasa Pemakaian Garbarata (Aviobridge), ditagih dari data pemakaian AMC.
     *   "Garbarata/Bis Layanan Penumpang di Apron" adalah sewa per m², jadi tidak termasuk.
     * Pola yang sama dipakai panel "Jenis Penerbangan" di form tagihan jasa.
     */
    public const POLA_DATA_AMC = [
        'penerbangan' => '/(pendaratan|penempatan|penyimpanan) pesawat|luar jam operasi\b/i',
        'garbarata' => '/pemakaian garbarata|aviobridge/i',
    ];

    /** Kategori data AMC dari satu layanan, berdasarkan jalur nama layanan & induknya. */
    public function kategoriDataAmc(LayananJasa $layanan): array
    {
        $jalur = [];
        for ($node = $layanan, $i = 0; $node && $i < 6; $node = $node->parent, $i++) {
            $jalur[] = $node->nama_layanan;
        }
        $teks = implode(' > ', $jalur);

        return array_keys(array_filter(self::POLA_DATA_AMC, fn ($pola) => preg_match($pola, $teks) === 1));
    }

    /**
     * Menu/akses data AMC yang boleh dipakai user: ['penerbangan' => bool, 'garbarata' => bool].
     * Admin Jasa murni hanya mendapat kategori yang sesuai layanan yang ia kelola; peran lain
     * yang sudah diizinkan di route/controller tidak dipersempit.
     */
    public function aksesDataAmc(?User $user): array
    {
        $semua = ['penerbangan' => true, 'garbarata' => true];
        if (! $user || ! $user->hasRole('Admin Jasa')
            || $user->hasAnyRole(['Super Admin', 'Super Admin Jasa', 'Koordinator Jasa', 'AMC', 'Operator BLU'])) {
            return $semua;
        }

        $hasil = ['penerbangan' => false, 'garbarata' => false];
        $user->layananJasaDikelolaAktif()->with('parent.parent.parent.parent.parent')->get()
            ->each(function (LayananJasa $layanan) use (&$hasil) {
                foreach ($this->kategoriDataAmc($layanan) as $kategori) {
                    $hasil[$kategori] = true;
                }
            });

        return $hasil;
    }

    public function bolehNonSchedule(?User $user): bool
    {
        return $this->aksesDataAmc($user)['penerbangan'];
    }

    public function bolehGarbarata(?User $user): bool
    {
        return $this->aksesDataAmc($user)['garbarata'];
    }
}
