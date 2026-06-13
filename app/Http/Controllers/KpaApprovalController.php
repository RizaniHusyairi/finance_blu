<?php

namespace App\Http\Controllers;

use App\Models\Tagihan;
use App\Models\User;
use App\Services\DokumenChainService;
use App\Services\EmailNotificationService;
use App\Services\WhatsappService;
use Illuminate\Support\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;

class KpaApprovalController extends Controller
{
    /**
     * PPK mengirim WA ke KPA untuk meminta persetujuan tagihan
     * (diajukan dari halaman verifikasi tagihan kontrak/perjaldin/honorarium).
     */
    public function sendWa(Request $request, $tagihanId, WhatsappService $whatsappService, EmailNotificationService $emailNotificationService)
    {
        $tagihan = Tagihan::with(['detailKontrak.kontrakTermin.kontrak.vendor', 'pihak'])->findOrFail($tagihanId);

        // SI hanya boleh diajukan untuk tagihan yang sudah disetujui penuh.
        // Tagihan yang dikembalikan untuk revisi (REVISI_*) masih punya COA
        // terisi, jadi tanpa cek ini tombol ajukan/kirim ulang tetap lolos.
        if (! app(DokumenChainService::class)->isTagihanFullyApproved($tagihan)) {
            return back()->with('error', 'Standing Instruction baru dapat diajukan setelah tagihan disetujui seluruh verifikator. Selesaikan proses revisi/verifikasi tagihan terlebih dahulu.');
        }

        // Urutan proses: COA harus dipilih PPK lebih dulu sebelum SI diajukan ke KPA.
        if (! app(DokumenChainService::class)->isCoaComplete($tagihan)) {
            return back()->with('error', 'Pilih COA terlebih dahulu sebelum mengajukan persetujuan ke KPA.');
        }

        // SI memuat nominal netto & sumber dana — tahan pengajuan selama
        // verifikator masih meminta perbaikan pajak/COA pada tagihan ini.
        if ($tagihan->chain_correction_target) {
            $bagian = $tagihan->chain_correction_target === 'PAJAK' ? 'pajak & faktur pajak' : 'pembebanan COA';

            return back()->with('error', "Selesaikan perbaikan {$bagian} yang diminta verifikator terlebih dahulu sebelum mengajukan persetujuan ke KPA.");
        }

        // Cari user KPA
        $kpaUser = User::role('KPA')->first();
        if (!$kpaUser) {
            return back()->with('error', 'User dengan role KPA tidak ditemukan dalam sistem.');
        }

        $noHp = $kpaUser->profilable->nomor_hp ?? null;
        if (!$noHp) {
            return back()->with('error', 'User KPA belum memiliki nomor HP yang terdaftar.');
        }

        // Generate Signed URL valid for 24 hours
        $url = URL::temporarySignedRoute(
            'kpa.approval.show',
            now()->addHours(24),
            ['tagihanId' => $tagihan->id, 'user_id' => $kpaUser->id]
        );

        $vendorName = $tagihan->detailKontrak?->kontrakTermin?->kontrak?->vendor?->nama_pihak
            ?? $tagihan->pihak?->nama_pihak
            ?? '-';
        $nominal = 'Rp ' . number_format($tagihan->total_netto, 0, ',', '.');

        $message = "*PENGAJUAN PERSETUJUAN TAGIHAN (KPA)*\n\n";
        $message .= "Yth. KPA,\n";
        $message .= "Terdapat tagihan baru yang memerlukan persetujuan Anda sebelum diproses lebih lanjut oleh PPK.\n\n";
        $message .= "*Nomor Tagihan:* {$tagihan->nomor_tagihan}\n";
        $message .= "*Vendor/Rekanan:* {$vendorName}\n";
        $message .= "*Nominal:* {$nominal}\n\n";
        $message .= "Silakan klik tautan di bawah ini untuk melihat detail dan memberikan persetujuan:\n";
        $message .= $url . "\n\n";
        $message .= "_Tautan ini valid selama 24 jam dan akan otomatis mengarahkan Anda ke sistem tanpa perlu login ulang._";

        $emailMessage = "Yth. KPA,\n\n"
            . "Dengan hormat,\n\n"
            . "Terdapat pengajuan persetujuan tagihan yang memerlukan tindak lanjut Bapak/Ibu sebelum diproses lebih lanjut.\n\n"
            . "Nomor Tagihan : {$tagihan->nomor_tagihan}\n"
            . "Vendor/Rekanan : {$vendorName}\n"
            . "Nominal : {$nominal}\n\n"
            . "Silakan meninjau detail dan memberikan persetujuan melalui tautan berikut:\n"
            . $url . "\n\n"
            . "Tautan ini berlaku selama 24 jam dan akan mengarahkan Bapak/Ibu ke sistem untuk proses persetujuan.\n\n"
            . "Hormat kami,\n"
            . "SIKEREN-BLU";

        try {
            $sent = $whatsappService->sendMessage($noHp, $message);
            if ($sent) {
                if ((bool) \App\Models\IntegrationSetting::getValue('email.kpa_approval.enabled', true)) {
                    $emailNotificationService->sendNotification(
                        (string) $kpaUser->email,
                        'Permohonan Persetujuan Tagihan KPA - ' . $tagihan->nomor_tagihan,
                        $emailMessage,
                        $tagihan,
                        'send_kpa_approval_email'
                    );
                }

                $tagihan->update([
                    'kpa_approval_status' => 'PENDING_KPA',
                    'kpa_approved_at' => null,
                    'kpa_approved_by' => null,
                    'kpa_approval_notes' => null,
                ]);
                return back()->with('success', 'Pesan WA pengajuan persetujuan berhasil dikirim ke KPA dan email diproses.');
            }
            return back()->with('error', 'Gagal mengirim pesan WA ke KPA. Silakan cek pengaturan integrasi WA.');
        } catch (\Exception $e) {
            Log::error('KPA Approval WA Send Error: ' . $e->getMessage());
            return back()->with('error', 'Terjadi kesalahan saat mengirim pesan WA: ' . $e->getMessage());
        }
    }

    /**
     * KPA membuka halaman dari Signed URL
     */
    public function showApproval(Request $request, $tagihanId)
    {
        if (!Auth::check() || !Auth::user()->hasAnyRole(['KPA', 'PLT/PLH', 'Super Admin'])) {
            if (!$request->hasValidSignature()) {
                abort(403, 'Tautan tidak valid atau sudah kedaluwarsa.');
            }

            $userId = $request->query('user_id');
            $user = User::findOrFail($userId);

            // Auto login KPA
            Auth::loginUsingId($user->id);
        } else {
            $user = Auth::user();
        }

        $tagihan = Tagihan::with([
            'detailKontrak.kontrakTermin.kontrak.vendor',
            'detailKontrak.arsipDokumen',
            'detailPerjaldin.provinsi',
            'detailPerjaldin.pegawai',
            'detailHonorarium',
            'arsipDokumen.uploader',
            'dipaRevisionItem.coa',
            'komponenPerjaldin.dipaRevisionItem.coa',
            'potonganTagihan.arsipDokumen',
            'pihak',
            'workflowInstance.approvals.actedByUser',
            'logs.user',
        ])->findOrFail($tagihanId);

        // Check status redirect removed so KPA can view details even if approved.

        $dokumenItems = $this->buildDokumenPendukung($tagihan);

        return view('tagihan.kpa_approval_page', compact('tagihan', 'user', 'dokumenItems'));
    }

    /**
     * KPA memproses (Setuju / Tolak)
     */
    public function processApproval(Request $request, $tagihanId, DokumenChainService $chainService)
    {
        $request->validate([
            'action' => 'required|in:approve,reject',
            'notes' => 'nullable|string'
        ]);

        $tagihan = Tagihan::findOrFail($tagihanId);

        // Magic link berlaku 24 jam — tagihan bisa saja sudah dikembalikan
        // untuk revisi (atau sudah diproses) setelah tautan dikirim. Tolak
        // aksi bila tagihan tidak lagi menunggu persetujuan KPA.
        if ($tagihan->kpa_approval_status !== 'PENDING_KPA'
            || ! $chainService->isTagihanFullyApproved($tagihan)
        ) {
            return redirect()->route('dashboard')->with('error',
                'Tagihan ini tidak sedang menunggu persetujuan KPA — kemungkinan sedang direvisi atau permintaan persetujuan sudah tidak berlaku.');
        }

        if ($request->action === 'approve') {
            $tagihan->update([
                'kpa_approval_status' => 'APPROVED',
                'kpa_approved_at' => now(),
                'kpa_approved_by' => Auth::id(),
                'kpa_approval_notes' => $request->notes,
            ]);
            $msg = 'Anda telah menyetujui tagihan ini.';
            try {
                $chainService->maybeGenerateDraftChain($tagihan->fresh(), Auth::user());
            } catch (\RuntimeException $e) {
                Log::warning('Draft chain generation after KPA approval failed.', [
                    'tagihan_id' => $tagihan->id,
                    'error' => $e->getMessage(),
                ]);
                $msg .= ' Draft dokumen belum dibuat: '.$e->getMessage();
            }
        } else {
            $tagihan->update([
                'kpa_approval_status' => 'REJECTED',
                'kpa_approved_at' => now(),
                'kpa_approved_by' => Auth::id(),
                'kpa_approval_notes' => $request->notes,
            ]);
            $msg = 'Anda telah menolak tagihan ini.';
        }

        return redirect()->route('dashboard')->with('success', $msg);
    }

    private function buildDokumenPendukung(Tagihan $tagihan): Collection
    {
        // Logika dipindah ke support class agar bisa dipakai halaman lain
        // (mis. detail Proses Tagihan).
        return \App\Support\TagihanDokumenPendukung::collect($tagihan);
    }
}
