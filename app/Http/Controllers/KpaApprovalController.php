<?php

namespace App\Http\Controllers;

use App\Models\Spp;
use App\Models\User;
use App\Services\WhatsappService;
use Illuminate\Support\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Carbon\Carbon;

class KpaApprovalController extends Controller
{
    /**
     * PPK mengirim WA ke KPA untuk meminta persetujuan
     */
    public function sendWa(Request $request, $sppId, WhatsappService $whatsappService)
    {
        $spp = Spp::with('tagihan')->findOrFail($sppId);
        
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
            ['sppId' => $spp->id, 'user_id' => $kpaUser->id]
        );

        $vendorName = $spp->tagihan?->detailKontrak?->kontrakTermin?->kontrak?->vendor?->nama_pihak 
            ?? $spp->tagihan?->pihak?->nama_pihak 
            ?? '-';
        $nominal = 'Rp ' . number_format($spp->nominal_spp, 0, ',', '.');

        $message = "*PENGAJUAN PERSETUJUAN TAGIHAN (KPA)*\n\n";
        $message .= "Yth. KPA,\n";
        $message .= "Terdapat tagihan baru yang memerlukan persetujuan Anda sebelum diproses lebih lanjut oleh PPK.\n\n";
        $message .= "*Nomor SPP:* {$spp->nomor_spp}\n";
        $message .= "*Vendor/Rekanan:* {$vendorName}\n";
        $message .= "*Nominal:* {$nominal}\n\n";
        $message .= "Silakan klik tautan di bawah ini untuk melihat detail dan memberikan persetujuan:\n";
        $message .= $url . "\n\n";
        $message .= "_Tautan ini valid selama 24 jam dan akan otomatis mengarahkan Anda ke sistem tanpa perlu login ulang._";

        try {
            $sent = $whatsappService->sendMessage($noHp, $message);
            if ($sent) {
                $spp->update([
                    'kpa_approval_status' => 'PENDING_KPA',
                    'kpa_approved_at' => null,
                    'kpa_approved_by' => null,
                    'kpa_approval_notes' => null,
                ]);
                return back()->with('success', 'Pesan WA pengajuan persetujuan berhasil dikirim ke KPA.');
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
    public function showApproval(Request $request, $sppId)
    {
        if (!$request->hasValidSignature()) {
            abort(403, 'Tautan tidak valid atau sudah kedaluwarsa.');
        }

        $userId = $request->query('user_id');
        $user = User::findOrFail($userId);

        // Auto login KPA
        Auth::loginUsingId($user->id);

        $spp = Spp::with([
            'tagihan.detailKontrak.kontrakTermin.kontrak.vendor', 
            'tagihan.detailKontrak.arsipDokumen',
            'tagihan.detailPerjaldin', 
            'tagihan.detailHonorarium',
            'tagihan.arsipDokumen.uploader',
            'dipaRevisionItem', 
            'tagihan.potonganTagihan.arsipDokumen',
            'arsipDokumen.uploader',
        ])->findOrFail($sppId);

        if ($spp->kpa_approval_status === 'APPROVED') {
            return redirect()->route('dashboard')->with('success', 'Tagihan ini sudah Anda setujui sebelumnya.');
        }

        $dokumenItems = $this->buildDokumenPendukung($spp);

        return view('spps.kpa_approval_page', compact('spp', 'user', 'dokumenItems'));
    }

    /**
     * KPA memproses (Setuju / Tolak)
     */
    public function processApproval(Request $request, $sppId)
    {
        $request->validate([
            'action' => 'required|in:approve,reject',
            'notes' => 'nullable|string'
        ]);

        $spp = Spp::findOrFail($sppId);

        if ($request->action === 'approve') {
            $spp->update([
                'kpa_approval_status' => 'APPROVED',
                'kpa_approved_at' => now(),
                'kpa_approved_by' => Auth::id(),
                'kpa_approval_notes' => $request->notes,
            ]);
            $msg = 'Anda telah menyetujui tagihan ini.';
        } else {
            $spp->update([
                'kpa_approval_status' => 'REJECTED',
                'kpa_approved_at' => now(),
                'kpa_approved_by' => Auth::id(),
                'kpa_approval_notes' => $request->notes,
            ]);
            $msg = 'Anda telah menolak tagihan ini.';
        }

        return redirect()->route('dashboard')->with('success', $msg);
    }

    private function buildDokumenPendukung(Spp $spp): Collection
    {
        $items = collect();
        $tagihan = $spp->tagihan;

        $addFile = function (?string $title, ?string $path, ?string $source = null) use ($items) {
            $path = trim((string) $path);
            if ($path === '') {
                return;
            }

            $items->push([
                'title' => $title ?: basename($path),
                'path' => $path,
                'url' => \Illuminate\Support\Facades\Storage::url($path),
                'source' => $source,
                'is_generated' => false,
            ]);
        };

        $addUrl = function (string $title, string $url, ?string $source = null) use ($items) {
            $items->push([
                'title' => $title,
                'path' => null,
                'url' => $url,
                'source' => $source,
                'is_generated' => true,
            ]);
        };

        $addArsip = function ($arsip, ?string $source = null) use ($addFile) {
            $path = $arsip?->path_file ?? $arsip?->file_path ?? null;
            $title = $arsip?->nama_file_asli
                ?: ($arsip?->jenis_dokumen ? ucwords(str_replace('_', ' ', $arsip->jenis_dokumen)) : null);

            $addFile($title, $path, $source);
        };

        $addArsip($spp->signedStandingInstructionArsip ?? null, 'SPP');
        foreach ($spp->arsipDokumen ?? collect() as $arsip) {
            $addArsip($arsip, 'SPP');
        }

        foreach ($tagihan?->arsipDokumen ?? collect() as $arsip) {
            $addArsip($arsip, 'Tagihan');
        }

        if ($tagihan?->detailKontrak) {
            $detail = $tagihan->detailKontrak;
            $kontrakFiles = [
                'Berita Acara Pemeriksaan Pekerjaan (BAPP)' => $detail->file_bapp,
                'Berita Acara Serah Terima (BAST)' => $detail->file_bast,
                'Berita Acara Pembayaran (BAP)' => $detail->file_bap,
                'Invoice Tagihan' => $detail->file_invoice,
                'Kwitansi Pembayaran' => $detail->file_kwitansi,
                'Faktur Pajak' => $detail->file_faktur_pajak,
                'Lampiran Lainnya' => $detail->file_lampiran_lainnya,
            ];

            foreach ($kontrakFiles as $title => $path) {
                $addFile($title, $path, 'Detail Kontrak');
            }

            foreach ($detail->arsipDokumen ?? collect() as $arsip) {
                $addArsip($arsip, 'Detail Kontrak');
            }
        }

        foreach ($tagihan?->detailPerjaldin ?? collect() as $detail) {
            $nama = $detail->nama_pegawai ?? $detail->pegawai?->nama_lengkap ?? 'Peserta';
            $addFile('Surat Tugas / SPT - ' . $nama, $detail->spt_file_path ?? null, 'Perjaldin');
            $addFile('Tiket Perjalanan - ' . $nama, $detail->tiket_file_path ?? null, 'Perjaldin');
            $addFile('Bukti Transport - ' . $nama, $detail->transport_file_path ?? null, 'Perjaldin');
            $addFile('Bukti Penginapan - ' . $nama, $detail->penginapan_file_path ?? null, 'Perjaldin');
            $addFile('Bukti Uang Harian - ' . $nama, $detail->uang_harian_file_path ?? null, 'Perjaldin');
        }

        foreach ($tagihan?->potonganTagihan ?? collect() as $potongan) {
            foreach ($potongan->arsipDokumen ?? collect() as $arsip) {
                $addArsip($arsip, 'Pajak/Potongan');
            }
        }

        if ($tagihan?->tipe_tagihan === 'HONORARIUM') {
            $addUrl('Daftar Nominatif Honorarium', route('honorarium.pdf-nominatif', $tagihan->id), 'Dokumen Sistem');
            $addUrl('Dokumen Honorarium', route('honorarium.pdf', $tagihan->id), 'Dokumen Sistem');
        }

        return $items
            ->unique(fn ($item) => $item['url'] . '|' . $item['title'])
            ->values();
    }
}
