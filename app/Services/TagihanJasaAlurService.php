<?php

namespace App\Services;

use App\Models\IntegrationSetting;
use App\Models\TagihanJasa;

/**
 * Menentukan alur verifikasi tagihan jasa.
 *
 *  - KABANDARA (alur 1): Koordinator Jasa -> Kasi Jasa -> Kasubag TU -> KPA/PLT-PLH,
 *    TTE pejabat final lalu terbit otomatis.
 *  - KASI_JASA (alur 2): Koordinator Jasa -> Kasi Jasa -> Kasubag TU, surat otomatis
 *    ber-TTE Kasi Jasa, lalu Kasi Jasa yang menerbitkan.
 *
 * Super Admin memilih alur untuk tagihan layanan AERO (pengaturan tagihan_jasa.alur_aero).
 * Tagihan non-aero atau campuran aero + non-aero selalu memakai alur 1.
 */
class TagihanJasaAlurService
{
    public const KABANDARA = 'KABANDARA';
    public const KASI_JASA = 'KASI_JASA';
    public const SETTING_KEY = 'tagihan_jasa.alur_aero';

    public const WORKFLOW = [
        self::KABANDARA => 'TAGIHAN_JASA',
        self::KASI_JASA => 'TAGIHAN_JASA_KASI',
    ];

    public const LABEL = [
        self::KABANDARA => 'Alur Kabandara',
        self::KASI_JASA => 'Alur Kasi Jasa (aero)',
    ];

    public function pengaturanAero(): string
    {
        $nilai = IntegrationSetting::getValue(self::SETTING_KEY, self::KABANDARA);

        return $nilai === self::KASI_JASA ? self::KASI_JASA : self::KABANDARA;
    }

    public function tentukan(TagihanJasa $tagihan): string
    {
        if ($this->pengaturanAero() !== self::KASI_JASA) {
            return self::KABANDARA;
        }

        $details = $tagihan->details()->with('layananJasa.parent.parent.parent.parent.parent')->get();
        $semuaAero = $details->isNotEmpty()
            && $details->every(fn ($detail) => $detail->layananJasa?->isAero() === true);

        return $semuaAero ? self::KASI_JASA : self::KABANDARA;
    }

    /**
     * Siapa yang boleh menerbitkan (publish) tagihan: pengelola tagihan jasa seperti biasa,
     * ditambah Kasi Jasa untuk tagihan alur Kasi Jasa (tagihan kembali ke Kasi untuk diterbitkan).
     */
    public function bolehTerbitkan(?\App\Models\User $user, TagihanJasa $tagihan): bool
    {
        if (! $user) {
            return false;
        }

        return $user->hasAnyRole(['Super Admin', 'Super Admin Jasa', 'Admin Jasa'])
            || ($user->hasRole('Kepala Seksi Pelayanan dan Kerjasama') && $tagihan->alur_verifikasi === self::KASI_JASA);
    }

    public function kodeWorkflow(string $alur): string
    {
        return self::WORKFLOW[$alur] ?? self::WORKFLOW[self::KABANDARA];
    }
}
