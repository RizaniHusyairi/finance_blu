<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class DisableExpiredTemporaryUsersCommand extends Command
{
    protected $signature = 'users:disable-expired-temporary
                            {--dry-run : Tampilkan kandidat tanpa menonaktifkan akun}';

    protected $description = 'Nonaktifkan akun PLT/PLH yang masa aktifnya sudah berakhir';

    public function handle(): int
    {
        if (! $this->hasActivePeriodColumns()) {
            $this->warn('Kolom masa aktif user belum tersedia. Jalankan migrasi terlebih dahulu.');
            return self::SUCCESS;
        }

        $expiredUsers = User::role('PLT/PLH')
            ->where('is_active', true)
            ->whereNotNull('active_until')
            ->whereDate('active_until', '<', now()->toDateString())
            ->orderBy('active_until')
            ->get();

        if ($expiredUsers->isEmpty()) {
            $this->info('Tidak ada akun PLT/PLH yang perlu dinonaktifkan.');
            return self::SUCCESS;
        }

        foreach ($expiredUsers as $user) {
            $line = sprintf(
                '%s | aktif sampai %s',
                $user->email,
                $user->active_until?->format('Y-m-d') ?? '-',
            );

            if ($this->option('dry-run')) {
                $this->line('[DRY] ' . $line);
                continue;
            }

            $user->disableIfExpired();
            $this->line('[DISABLED] ' . $line);
        }

        $this->info("Selesai. Akun diproses: {$expiredUsers->count()}.");

        return self::SUCCESS;
    }

    private function hasActivePeriodColumns(): bool
    {
        return Schema::hasTable('users')
            && Schema::hasColumn('users', 'is_active')
            && Schema::hasColumn('users', 'active_until')
            && Schema::hasColumn('users', 'disabled_at');
    }
}
