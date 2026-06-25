<?php

namespace Database\Seeders;

use App\Enums\JenisRekening;
use App\Models\RekeningBank;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Seeder;

/**
 * Rekening default bawaan sistem untuk BKU Penerimaan & Pengeluaran.
 *
 * Menyeed satu rekening default per peran bendahara:
 *  - PENERIMAAN : "BANK BTN" RPL 046 BLU — untuk rekonsiliasi rekening koran.
 *  - PENGELUARAN: "Bank Operasional BLU" — satu-satunya rujukan default sumber
 *    BKU pengeluaran. Saldo awal dibiarkan 0; admin mengisi nilai riil.
 *
 * Ditandai is_terkunci=true sehingga TIDAK bisa dihapus lewat menu Rekening
 * Bank (lihat RekeningBankController::destroy). Admin tetap boleh menyunting
 * nomor/nama/saldo rekening agar sesuai data riil.
 *
 * Idempoten: updateOrCreate by nomor_rekening (placeholder unik).
 */
class RekeningBankDefaultSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            [
                'role' => 'Bendahara Penerimaan',
                'jenis' => JenisRekening::PENERIMAAN->value,
                // Rekening RPL 046 BLU (sesuai statement CMS BTN) — agar siap untuk
                // rekonsiliasi rekening koran dengan BKU Penerimaan.
                'nama_bank' => 'BANK BTN',
                'nomor_rekening' => '0002001302887451',
                'nama_rekening' => 'RPL 046 BLU UPBU APT PRANOTO UNTUK OPS',
            ],
            [
                'role' => 'Bendahara Pengeluaran',
                'jenis' => JenisRekening::PENGELUARAN->value,
                // Rekening default operasional Bendahara Pengeluaran — satu-satunya
                // rujukan default sumber BKU pengeluaran. Saldo awal diisi admin.
                'nama_bank' => 'Bank Operasional BLU',
                'nomor_rekening' => '777000260001',
                'nama_rekening' => 'Bendahara Pengeluaran BLU',
            ],
        ];

        foreach ($defaults as $row) {
            $owner = $this->resolveOwner($row['role']);

            if (! $owner) {
                $this->command?->warn("⚠ Lewati rekening default {$row['jenis']}: tidak ada user pemilik.");
                continue;
            }

            RekeningBank::updateOrCreate(
                ['nomor_rekening' => $row['nomor_rekening']],
                [
                    'pemilik_type' => User::class,
                    'pemilik_id' => $owner->id,
                    'nama_bank' => $row['nama_bank'],
                    'nama_rekening' => $row['nama_rekening'],
                    'jenis_rekening' => $row['jenis'],
                    'is_default' => true,
                    'is_terkunci' => true,
                    'status_aktif' => true,
                ],
            );

            $this->command?->info("✓ Rekening default {$row['jenis']} → pemilik {$owner->email}");
        }
    }

    /**
     * Pemilik rekening default = user dengan peran terkait; jatuh ke user mana pun
     * yang punya peran bendahara bila peran spesifik belum ada.
     */
    private function resolveOwner(string $role): ?User
    {
        return User::query()
            ->whereHas('roles', fn (Builder $q) => $q->where('name', $role))
            ->first()
            ?? User::query()
                ->whereHas('roles', fn (Builder $q) => $q->whereIn('name', ['Bendahara Penerimaan', 'Bendahara Pengeluaran', 'Super Admin']))
                ->first();
    }
}
