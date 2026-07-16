<?php

namespace App\Http\Controllers\Concerns;

use App\Models\User;

/**
 * Helper bersama pemilihan & snapshot 6 verifikator/penanda tangan dokumen
 * pencairan (SPP/SPM/NPI/SP2D) — dipakai master Kontrak Eksternal (pemilihan)
 * dan pembuatan tagihan termin (snapshot ke kolom tagihan).
 */
trait BuildsVerifikatorSnapshots
{
    /** @var array<string, string> key kolom => nama role Spatie */
    protected static array $verifikatorRoles = [
        'ppk' => 'PPK',
        'ppspm' => 'PPSPM',
        'koordinator_keuangan' => 'Koordinator Keuangan',
        'bendahara_pengeluaran' => 'Bendahara Pengeluaran',
        'bendahara_penerimaan' => 'Bendahara Penerimaan',
        'kasubbag' => 'Kepala Subbagian Keuangan dan Tata Usaha',
    ];

    protected function buildVerifikatorOptions(): array
    {
        $options = [];
        foreach (static::$verifikatorRoles as $key => $roleName) {
            try {
                $users = User::role($roleName)->with('profilable')->orderByDisplayName()->get();
            } catch (\Exception) {
                $users = collect();
            }

            $options[$key] = $users->map(fn ($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'nip' => optional($u->profilable)->nip ?? '-',
                'jabatan' => optional($u->profilable)->jabatan ?? $roleName,
            ])->values();
        }

        return $options;
    }

    /**
     * Kolom snapshot verifikator untuk Tagihan: {key}_user_id + nama/nip snapshot.
     *
     * @param  array<string, int|null>  $userIdsByRole
     */
    protected function buildVerifikatorSnapshots(array $userIdsByRole): array
    {
        $out = [];
        foreach ($userIdsByRole as $key => $userId) {
            if (empty($userId)) {
                continue;
            }
            $user = User::with('profilable')->find($userId);
            if (! $user) {
                continue;
            }

            $out["{$key}_user_id"] = $user->id;
            $out["{$key}_nama_snapshot"] = $user->name;
            $out["{$key}_nip_snapshot"] = optional($user->profilable)->nip ?? null;
        }

        return $out;
    }
}
