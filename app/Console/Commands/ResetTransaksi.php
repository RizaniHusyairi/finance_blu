<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Mengosongkan data TRANSAKSI/operasional dan MEMPERTAHANKAN seluruh data
 * master + identitas. Cocok untuk menyiapkan DB pra-deploy tanpa kehilangan
 * akun user, pegawai, dan role.
 *
 * Yang DIPERTAHANKAN (lihat $preserve):
 *  - Identitas/auth: users, pegawai, pihak, roles & permissions (Spatie),
 *    sessions, migrations.
 *  - Master/referensi: COA, DIPA, tarif pajak, uang harian, layanan jasa,
 *    workflow DEFINITION, kode transaksi, akun pendapatan, setup pembukuan,
 *    integration settings, rekening bank (konfigurasi).
 *
 * PENTING: command ini TIDAK mengubah skema (bukan migrate:fresh) dan TIDAK
 * menjalankan UserAccountSeeder (yang akan me-reset password semua user).
 * Tabel apa pun di luar kedua daftar akan DIPERTAHANKAN + dilaporkan agar
 * ditinjau manual (fail-safe: tabel baru tidak terhapus diam-diam).
 */
class ResetTransaksi extends Command
{
    protected $signature = 'db:reset-transaksi
        {--dry-run : Tampilkan rencana (jumlah baris per tabel) tanpa menghapus apa pun}
        {--force : Lewati konfirmasi interaktif (untuk skrip/CI)}
        {--skip-backup-check : Lewati pengingat/pengecekan backup}';

    protected $description = 'Kosongkan data transaksi/operasional; pertahankan semua master + identitas (users/pegawai/role).';

    /** Identitas, auth, framework, dan master/referensi — SELALU dipertahankan. */
    private array $preserve = [
        // — Identitas, auth, framework —
        'migrations', 'users', 'password_reset_tokens', 'sessions',
        'cache', 'cache_locks', 'jobs', 'job_batches', 'failed_jobs',
        'roles', 'permissions', 'model_has_roles', 'model_has_permissions', 'role_has_permissions',
        // — Master / referensi —
        'master_coas', 'master_dipas', 'master_pegawai', 'master_pihak',
        'master_tarif_pajak', 'master_uang_harian_perjaldins',
        'master_item_tarif_layanan', 'master_jenis_layanan', 'master_kategori_layanan',
        'akun_pendapatan', 'kode_transaksi', 'layanan_jasas', 'layanan_jasa_tarifs',
        'workflow_definitions', 'workflow_definition_steps',
        'pembukuan_setup', 'integration_settings',
        'rekening_bank',          // konfigurasi rekening (termasuk default terkunci)
        'mitra_jasa',             // registry mitra/tenant (master partner) — [tinjau]
        'pnbp_umum_items',        // referensi item PNBP umum — [tinjau]
        'pembukuan_saldo_awal',   // setup saldo awal pembukuan — [tinjau]
    ];

    /** Transaksi/operasional — DIKOSONGKAN (TRUNCATE). */
    private array $transactional = [
        // Tagihan & pembayaran
        'tagihan', 'potongan_tagihan', 'tagihan_jasas', 'tagihan_jasa_details',
        'tagihan_jasa_payment_proofs', 'tagihan_perjaldin_komponen',
        'payment_transactions', 'transaksi_penerimaan', 'transaksi_pembukuan',
        // Dokumen pencairan, penomoran & arsip
        'dokumen_spp', 'dokumen_spm', 'dokumen_sp2d', 'dokumen_npi',
        'document_numbers', 'document_number_sequences', 'document_signatures',
        'arsip_dokumen', 'log_status_dokumen',
        // BKU, mutasi bank, rekonsiliasi
        'buku_kas_umum', 'detail_mutasi_bank', 'import_mutasi_bank',
        'rekonsiliasi_bank', 'rekonsiliasi_bank_logs',
        // Anggaran / DIPA (revisi & realisasi = riwayat operasional) — [tinjau]
        'dipa_revisions', 'dipa_revision_items', 'realisasi_anggaran',
        // Kontrak pengadaan
        'kontrak_pengadaan', 'detail_kontrak', 'kontrak_termin', 'kontrak_addendum',
        'jaminan_kontrak', 'detail_honorarium',
        // Perjaldin
        'detail_perjaldin',
        // Modul jasa (operasional)
        'kontrak_mitra_jasa', 'kontrak_mitra_jasa_layanan',
        'mitra_jasa_konsesi', 'mitra_jasa_layanan', 'mitra_layanan_jasa',
        'mitra_jasa_penjualan', 'mitra_jasa_penjualan_details', 'mitra_jasa_pjp2u',
        'admin_jasa_layanan',
        // PNBP umum / utilitas / garbarata / non-schedule
        'pnbp_umum_realisasis', 'pemakaian_garbarata',
        'pengajuan_penagihan_garbarata', 'permohonan_non_schedule',
        'jadwal_penerbangan', 'laporan_utilitas', 'laporan_pengesahan_blu',
        'log_perubahan_tarif_pjp2u',
        // Workflow runtime (instance & approval — BUKAN definition)
        'workflow_instances', 'workflow_approvals',
        // Standing instruction (recurring billing) — [tinjau], notifikasi, log, short link
        'standing_instructions', 'notifications',
        'whatsapp_notification_logs', 'integration_logs', 'short_links',
    ];

    public function handle(): int
    {
        if (DB::getDriverName() !== 'mysql') {
            $this->warn('Command ini menggunakan SET FOREIGN_KEY_CHECKS (MySQL/MariaDB). Driver saat ini: '.DB::getDriverName());
        }

        $all = array_map([$this, 'stripPrefix'], Schema::getTableListing());

        $toTruncate   = array_values(array_intersect($this->transactional, $all));
        $missing      = array_values(array_diff($this->transactional, $all));
        $unclassified = array_values(array_diff($all, $this->preserve, $this->transactional));

        $this->info('== Rencana reset data transaksi ==');
        $this->line(sprintf('Total tabel di DB         : %d', count($all)));
        $this->line(sprintf('Dipertahankan (master+id) : %d', count(array_intersect($this->preserve, $all)) + count($unclassified)));
        $this->line(sprintf('Dikosongkan (transaksi)   : %d', count($toTruncate)));

        if ($unclassified) {
            $this->newLine();
            $this->warn('Tabel BELUM terklasifikasi → DIPERTAHANKAN (tinjau manual, mungkin perlu dimasukkan ke daftar transaksi):');
            foreach ($unclassified as $t) {
                $this->line(sprintf('  • %-34s %d baris', $t, DB::table($t)->count()));
            }
        }
        if ($missing) {
            $this->warn('Di daftar transaksi tapi tidak ada di DB (dilewati): '.implode(', ', $missing));
        }

        // Hitung baris yang akan dihapus.
        $rows = [];
        $totalRows = 0;
        foreach ($toTruncate as $t) {
            $rows[$t] = DB::table($t)->count();
            $totalRows += $rows[$t];
        }

        if ($this->option('dry-run')) {
            $this->newLine();
            $this->info('DRY-RUN — tidak ada yang dihapus. Tabel yang AKAN dikosongkan:');
            arsort($rows);
            foreach ($rows as $t => $c) {
                $this->line(sprintf('  %-34s %d', $t, $c));
            }
            $this->newLine();
            $this->info(sprintf('Total %d baris pada %d tabel akan dihapus saat dijalankan tanpa --dry-run.', $totalRows, count($toTruncate)));
            return self::SUCCESS;
        }

        if (! $this->option('skip-backup-check')) {
            $this->backupReminder();
        }

        $this->newLine();
        $this->warn(sprintf('Akan MENGHAPUS %d baris dari %d tabel transaksi. Environment: %s', $totalRows, count($toTruncate), app()->environment()));
        $this->line('Master + users/pegawai/role TIDAK tersentuh. Skema TIDAK diubah.');

        if (! $this->option('force')) {
            if ($this->ask('Ketik HAPUS untuk lanjut (lainnya = batal)') !== 'HAPUS') {
                $this->info('Dibatalkan. Tidak ada perubahan.');
                return self::SUCCESS;
            }
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        try {
            foreach ($toTruncate as $t) {
                DB::table($t)->truncate();
                $this->line('  ✓ truncate '.$t);
            }
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }

        $this->newLine();
        $this->info(sprintf('Selesai. %d tabel dikosongkan (%d baris). Master + identitas tetap utuh.', count($toTruncate), $totalRows));
        $this->line('Disarankan setelah ini: `php artisan cache:clear` & `php artisan queue:flush` (bersihkan cache + antrian sisa).');

        return self::SUCCESS;
    }

    private function backupReminder(): void
    {
        $this->warn('⚠ Pastikan sudah ada backup: `php artisan db:backup` (BR-01).');

        // Best-effort: cek kesegaran backup bila DB_BACKUP_PATH tersedia.
        $path = env('DB_BACKUP_PATH');
        if (! $path || ! is_dir($path)) {
            return;
        }
        $latest = 0;
        foreach (glob(rtrim($path, '/\\').'/*') ?: [] as $f) {
            $latest = max($latest, (int) @filemtime($f));
        }
        if ($latest === 0) {
            $this->warn('  Tidak ada berkas backup ditemukan di '.$path);
        } else {
            $ageH = (time() - $latest) / 3600;
            $ageH > 26
                ? $this->warn(sprintf('  Backup terakhir %.1f jam lalu (basi).', $ageH))
                : $this->info(sprintf('  ✓ Backup terakhir %.1f jam lalu.', $ageH));
        }
    }

    private function stripPrefix(string $table): string
    {
        $prefix = DB::getTablePrefix();
        return $prefix && str_starts_with($table, $prefix) ? substr($table, strlen($prefix)) : $table;
    }
}
