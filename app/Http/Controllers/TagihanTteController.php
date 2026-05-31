<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Str;
use App\Models\Tagihan;
use App\Models\DocumentSignature;
use App\Models\MasterPegawai;
use App\Services\EmailNotificationService;
use App\Services\WhatsappService;
use Illuminate\Support\Facades\DB;

class TagihanTteController extends Controller
{
    /**
     * Mengirim akses TTE secara terpadu:
     * - 1 pesan ke Vendor berisi satu tautan publik untuk menyetujui
     *   dokumen BAPP, BAP, dan BAST (jika termin PELUNASAN).
     * - 1 pesan ke Tim Pemeriksa berisi satu tautan publik untuk
     *   menyetujui dokumen BAPP.
     */
    public function sendTte(Request $request, $id)
    {
        $tagihan = Tagihan::with('detailKontrak.termin.kontrak.vendor')->findOrFail($id);
        $detail = $tagihan->detailKontrak;
        $termin = $detail->termin;
        $kontrak = $termin->kontrak;

        $vendorName = $kontrak->vendor->nama_pihak ?? 'Vendor';
        $vendorWa = preg_replace('/\D+/', '', $kontrak->vendor->no_telepon ?? '');

        $pemeriksaName = $detail->nama_pemeriksa ?? 'Tim Pemeriksa';
        $pemeriksaWa = preg_replace('/\D+/', '', $detail->wa_pemeriksa ?? '');

        if (strlen($vendorWa) < 9) {
            return back()->withErrors(['error' => 'Gagal mengirim WA: Nomor WhatsApp Vendor (' . ($kontrak->vendor->no_telepon ?? 'Kosong') . ') tidak valid. Silakan lengkapi di menu Master Data Vendor.']);
        }

        if (strlen($pemeriksaWa) < 9) {
            return back()->withErrors(['error' => 'Gagal mengirim WA: Nomor WhatsApp Tim Pemeriksa (' . ($detail->wa_pemeriksa ?? 'Kosong') . ') tidak valid.']);
        }

        // Tentukan dokumen yang harus disetujui masing-masing penerima
        $wajibBast = ($termin->jenis_termin === 'PELUNASAN');

        $vendorDocs = ['BAPP', 'BAP'];
        if ($wajibBast) {
            $vendorDocs[] = 'BAST';
        }
        $pemeriksaDocs = ['BAPP'];

        DB::beginTransaction();
        try {
            // Hapus seluruh TTE lama agar pengiriman ulang menghasilkan tautan baru
            $tagihan->documentSignatures()->delete();

            // ---- Vendor: satu tautan untuk seluruh dokumen ----
            $vendorGroupToken = Str::random(40);
            foreach ($vendorDocs as $label) {
                $tagihan->documentSignatures()->create([
                    'document_label' => $label,
                    'role' => 'vendor',
                    'signer_name' => $vendorName,
                    'signer_phone' => $vendorWa,
                    'status' => 'pending',
                    'magic_token' => Str::random(40),
                    'group_token' => $vendorGroupToken,
                ]);
            }

            // ---- Tim Pemeriksa: satu tautan untuk dokumen BAPP ----
            $pemeriksaGroupToken = Str::random(40);
            foreach ($pemeriksaDocs as $label) {
                $tagihan->documentSignatures()->create([
                    'document_label' => $label,
                    'role' => 'tim_pemeriksa',
                    'signer_name' => $pemeriksaName,
                    'signer_phone' => $pemeriksaWa,
                    'status' => 'pending',
                    'magic_token' => Str::random(40),
                    'group_token' => $pemeriksaGroupToken,
                ]);
            }

            // Kirim WA
            $waService = app(WhatsappService::class);
            $emailService = app(EmailNotificationService::class);
            $emailEnabled = (bool) \App\Models\IntegrationSetting::getValue('email.tte.enabled', true);

            $vendorUrl = url('/public/tte/sign/' . $vendorGroupToken);
            $vendorDocList = implode(', ', $vendorDocs);
            $vendorMessage = "Yth. $vendorName,\n\n"
                . "Dengan hormat,\n\n"
                . "Kami mengajukan permohonan persetujuan tanda tangan elektronik untuk dokumen {$vendorDocList} pada tagihan kontrak {$tagihan->nomor_tagihan}.\n\n"
                . "Silakan meninjau dan menyetujui dokumen melalui tautan berikut:\n"
                . "$vendorUrl\n\n"
                . "Mohon tautan ini digunakan secara bertanggung jawab dan tidak diteruskan kepada pihak yang tidak berkepentingan.\n\n"
                . "Hormat kami,\n"
                . "SIKEREN-BLU";
            $waService->sendMessage($vendorWa, str_replace("{$vendorDocList}", "*{$vendorDocList}*", str_replace($tagihan->nomor_tagihan, "*{$tagihan->nomor_tagihan}*", $vendorMessage)));

            if ($emailEnabled) {
                $emailService->sendNotification(
                    (string) ($kontrak->vendor?->email ?? ''),
                    'Permohonan TTE Dokumen Tagihan Kontrak ' . $tagihan->nomor_tagihan,
                    $vendorMessage,
                    $tagihan,
                    'send_contract_tte_email'
                );
            }

            $pemeriksaUrl = url('/public/tte/sign/' . $pemeriksaGroupToken);
            $pemeriksaMessage = "Yth. $pemeriksaName,\n\n"
                . "Dengan hormat,\n\n"
                . "Terdapat dokumen BAPP pada tagihan kontrak {$tagihan->nomor_tagihan} yang memerlukan persetujuan tanda tangan elektronik dari Tim Pemeriksa.\n\n"
                . "Silakan meninjau dan menyetujui dokumen melalui tautan berikut:\n"
                . "$pemeriksaUrl\n\n"
                . "Mohon tautan ini digunakan secara bertanggung jawab dan tidak diteruskan kepada pihak yang tidak berkepentingan.\n\n"
                . "Hormat kami,\n"
                . "SIKEREN-BLU";
            $waService->sendMessage($pemeriksaWa, str_replace('BAPP', '*BAPP*', str_replace($tagihan->nomor_tagihan, "*{$tagihan->nomor_tagihan}*", $pemeriksaMessage)));

            if ($emailEnabled) {
                $pemeriksaEmail = filled($detail->nip_pemeriksa)
                    ? MasterPegawai::where('nip', $detail->nip_pemeriksa)->first()?->user?->email
                    : null;
                $emailService->sendNotification(
                    (string) ($pemeriksaEmail ?? ''),
                    'Permohonan TTE Dokumen BAPP Tagihan Kontrak ' . $tagihan->nomor_tagihan,
                    $pemeriksaMessage,
                    $tagihan,
                    'send_contract_tte_email'
                );
            }

            DB::commit();
            return back()->with('success', 'Akses TTE telah dikirim: 1 tautan ke Vendor (' . $vendorDocList . ') dan 1 tautan ke Pemeriksa (BAPP) via WhatsApp, serta email diproses bila alamat tersedia.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal mengirim akses TTE: ' . $e->getMessage());
        }
    }
}
