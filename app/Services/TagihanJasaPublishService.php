<?php

namespace App\Services;

use App\Models\IntegrationSetting;
use App\Models\MitraJasa;
use App\Models\ShortLink;
use App\Models\TagihanJasa;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

/**
 * Logika publish tagihan jasa yang dapat dipanggil dari dua tempat:
 *  - TagihanJasaController::publish() (publish manual lewat tombol).
 *  - TagihanJasaVerifikasiController::approve() (auto-publish setelah verifikator final).
 *
 * Menentukan nomor VA, jatuh tempo, sinkron piutang, dan notifikasi WA + email.
 */
class TagihanJasaPublishService
{
    public function __construct(
        private BtnVirtualAccountService $btnVirtualAccountService,
        private WhatsappService $whatsappService,
        private EmailNotificationService $emailNotificationService,
    ) {
    }

    /**
     * @param array{nomor_va?: string|null, wa_tujuan?: string|null, email_tujuan?: string|null} $options
     * @return array{nomor_va: string, wa_message: string, wa_tujuan: ?string, email_message: string, account: array}
     *
     * @throws \RuntimeException bila nomor VA belum tersedia (mode manual tanpa VA).
     */
    public function publish(TagihanJasa $tagihan, array $options = []): array
    {
        $tagihan->loadMissing(['mitra', 'mitraLegacy', 'details.layananJasa']);

        if (! $tagihan->mitra) {
            throw new \RuntimeException('Tagihan belum terhubung ke data Mitra Jasa.');
        }

        // Toggle "btn.enabled": ON = VA otomatis (API BTN), OFF = VA diisi manual.
        $vaOtomatis = (bool) IntegrationSetting::getValue('btn.enabled', false);

        $vaData = [];
        if ($vaOtomatis) {
            $vaData = $this->btnVirtualAccountService->createVirtualAccount($tagihan);
            $nomorVa = $vaData['number'] ?? $tagihan->nomor_va;
        } else {
            // Mode manual: nomor VA berasal dari opsi (form publish) atau yang sudah
            // diisi sejak form Buat Tagihan.
            $nomorVa = $options['nomor_va'] ?? $tagihan->nomor_va;
        }

        if (blank($nomorVa)) {
            throw new \RuntimeException('Nomor Virtual Account belum tersedia. Aktifkan VA otomatis atau isi nomor VA manual.');
        }

        $accountInfo = $this->ensureMitraAccount($tagihan->mitra);
        $dueData = $this->resolveDueDateData($tagihan);

        $tagihan->update([
            'status' => 'PUBLISHED',
            'status_pembayaran' => 'belum_dibayar',
            'nomor_va' => $nomorVa,
            'va_provider' => $vaData['provider'] ?? 'btn',
            'va_reference' => $vaData['reference'] ?? $tagihan->va_reference,
            'va_expired_at' => $vaData['expired_at'] ?? ($dueData['tanggal_jatuh_tempo'] ?? null),
            'tanggal_publish' => now()->toDateString(),
            'jumlah_hari_jatuh_tempo' => $dueData['jumlah_hari_jatuh_tempo'],
            'masa_toleransi_hari' => $dueData['masa_toleransi_hari'],
            'tanggal_jatuh_tempo' => $dueData['tanggal_jatuh_tempo'],
            'tanggal_akhir_toleransi' => $dueData['tanggal_akhir_toleransi'],
            'catatan_jatuh_tempo' => $dueData['catatan_jatuh_tempo'],
            'jumlah_dibayar' => 0,
            'sisa_tagihan' => $tagihan->total_tagihan,
        ]);

        $publishedTagihan = $tagihan->fresh(['mitra', 'mitraLegacy', 'details']);

        if (! empty($options['email_tujuan'])) {
            $accountInfo['notification_email'] = $options['email_tujuan'];
        }

        // Sync ke piutang (TransaksiPenerimaan) — muncul di menu Piutang Bendahara Penerimaan.
        try {
            app(\App\Services\Pembukuan\PiutangSyncService::class)->syncFromPublished($publishedTagihan);
        } catch (\Throwable $e) {
            Log::error('Gagal sync piutang saat publish: ' . $e->getMessage());
        }

        // Tujuan WA: dari opsi (form publish) atau default ke nomor telepon mitra (auto-publish).
        $waTujuan = $options['wa_tujuan'] ?? $publishedTagihan->mitra?->no_telepon;
        $waMessage = $this->buildWhatsappMessage($publishedTagihan, $accountInfo);
        if (filled($waTujuan)) {
            $this->whatsappService->sendMessage($waTujuan, $waMessage, $publishedTagihan);
        }

        $emailMessage = $this->emailNotificationService->buildPublishedTagihanMessage($publishedTagihan, $accountInfo);
        $this->emailNotificationService->sendPublishedTagihan($publishedTagihan, $accountInfo);

        return [
            'nomor_va' => $nomorVa,
            'wa_message' => $waMessage,
            'wa_tujuan' => $waTujuan,
            'email_message' => $emailMessage,
            'account' => $accountInfo,
        ];
    }

    private function ensureMitraAccount(MitraJasa $mitra): array
    {
        $user = $mitra->user;

        if ($user) {
            return [
                'is_new' => false,
                'email' => $user->email,
                'password' => null,
            ];
        }

        $email = $mitra->email ?: 'mitra-' . $mitra->id . '@sikeren.id';

        if (User::where('email', $email)->exists()) {
            $email = 'mitra-' . $mitra->id . '-' . Str::lower(Str::random(5)) . '@sikeren.id';
        }
        $password = Str::password(10);

        Role::findOrCreate('Mitra Jasa', 'web');

        $user = User::create([
            'email' => $email,
            'password' => Hash::make($password),
            'profilable_type' => MitraJasa::class,
            'profilable_id' => $mitra->id,
        ]);
        $user->assignRole('Mitra Jasa');

        return [
            'is_new' => true,
            'email' => $email,
            'password' => $password,
        ];
    }

    private function resolveDueDateData(TagihanJasa $tagihan): array
    {
        $layanans = $tagihan->details->pluck('layananJasa')->filter();
        $dueDays = (int) ($layanans->min('jumlah_hari_jatuh_tempo') ?: 30);
        $toleranceDays = (int) ($layanans->min('masa_toleransi_hari') ?? 0);
        $publishDate = now()->startOfDay();
        $dueDate = $publishDate->copy()->addDays($dueDays);
        $toleranceDate = $dueDate->copy()->addDays($toleranceDays);
        $forcedSeparate = $layanans->where('wajib_tagihan_terpisah', true);
        $notes = $layanans
            ->pluck('catatan_jatuh_tempo')
            ->filter()
            ->unique()
            ->values();

        if ($forcedSeparate->isNotEmpty()) {
            $notes->push('Terdapat layanan yang wajib dibuat dalam tagihan terpisah.');
        }

        return [
            'jumlah_hari_jatuh_tempo' => $dueDays,
            'masa_toleransi_hari' => $toleranceDays,
            'tanggal_jatuh_tempo' => $dueDate->toDateString(),
            'tanggal_akhir_toleransi' => $toleranceDate->toDateString(),
            'catatan_jatuh_tempo' => $notes->isNotEmpty()
                ? $notes->implode(' ')
                : "Jatuh tempo {$dueDays} hari sejak tanggal publish tagihan.",
        ];
    }

    private function buildWhatsappMessage(TagihanJasa $tagihan, array $accountInfo): string
    {
        $message = "*PEMBERITAHUAN TAGIHAN PNBP*\n\n";
        $message .= "Yth. " . ($tagihan->mitra->nama_mitra ?? '-') . ",\n\n";
        $message .= "Berikut adalah informasi tagihan layanan Anda:\n";
        $message .= "No Tagihan: *" . $tagihan->nomor_tagihan . "*\n";
        $message .= "Total Tagihan: *Rp " . number_format((float) $tagihan->total_tagihan, 0, ',', '.') . "*\n\n";
        $message .= "Silakan lakukan pembayaran melalui Virtual Account Bank BTN berikut:\n";
        $message .= "No VA: *" . ($tagihan->nomor_va ?? '-') . "*\n\n";
        if ($tagihan->tanggal_jatuh_tempo) {
            $message .= "Jatuh Tempo: *" . $tagihan->tanggal_jatuh_tempo->format('d/m/Y') . "*\n";
        }
        $shortLink = ShortLink::forTarget('tagihan_jasa', $tagihan->id, auth()->id());
        $message .= "Link Surat Pengantar dan Nota Tagihan: " . $shortLink->publicUrl() . "\n\n";
        $message .= "----------------------------------------\n";
        $message .= "*AKUN PORTAL MITRA*\n";
        $message .= "Email Login: " . ($accountInfo['email'] ?? '-') . "\n";

        if (! empty($accountInfo['password'])) {
            $message .= "Password: " . $accountInfo['password'] . "\n";
            $message .= "Mohon segera ubah password setelah login pertama.\n";
        } else {
            $message .= "Gunakan password akun yang sudah terdaftar sebelumnya.\n";
        }

        $message .= "Login Portal: " . route('login') . "\n";
        $message .= "----------------------------------------\n\n";
        $message .= "Terima kasih atas kerja sama Anda.\n";
        $message .= "_Sistem Informasi Keuangan (SIKEREN)_";

        return $message;
    }
}
