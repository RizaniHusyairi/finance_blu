<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Str;
use App\Models\Tagihan;
use App\Models\DocumentSignature;
use App\Services\WhatsappService;
use Illuminate\Support\Facades\DB;

class TagihanTteController extends Controller
{
    public function sendTte(Request $request, $id)
    {
        $request->validate([
            'document_label' => 'required|in:BAPP,BAST,BAP',
        ]);

        $tagihan = Tagihan::with('detailKontrak.termin.kontrak.vendor')->findOrFail($id);
        $detail = $tagihan->detailKontrak;
        $kontrak = $detail->termin->kontrak;
        
        $vendorName = $kontrak->vendor->nama_pihak ?? 'Vendor';
        $vendorWa = preg_replace('/\D+/', '', $kontrak->vendor->no_telepon ?? '');
        
        $pemeriksaName = $detail->nama_pemeriksa;
        $pemeriksaWa = preg_replace('/\D+/', '', $detail->wa_pemeriksa ?? '');

        if (strlen($vendorWa) < 9) {
            return back()->withErrors(['error' => 'Gagal mengirim WA: Nomor WhatsApp Vendor (' . ($kontrak->vendor->no_telepon ?? 'Kosong') . ') tidak valid. Silakan lengkapi di menu Master Data Vendor.']);
        }

        if (strlen($pemeriksaWa) < 9) {
            return back()->withErrors(['error' => 'Gagal mengirim WA: Nomor WhatsApp Tim Pemeriksa (' . ($detail->wa_pemeriksa ?? 'Kosong') . ') tidak valid.']);
        }

        DB::beginTransaction();
        try {
            // Hapus TTE lama jika ada
            $tagihan->documentSignatures()->where('document_label', $request->document_label)->delete();

            // Buat record untuk Vendor
            $vendorToken = Str::random(40);
            $tagihan->documentSignatures()->create([
                'document_label' => $request->document_label,
                'role' => 'vendor',
                'signer_name' => $vendorName,
                'signer_phone' => $vendorWa,
                'status' => 'pending',
                'magic_token' => $vendorToken,
            ]);

            // Buat record untuk Tim Pemeriksa HANYA JIKA dokumen adalah BAPP
            if ($request->document_label === 'BAPP') {
                $pemeriksaToken = Str::random(40);
                $tagihan->documentSignatures()->create([
                    'document_label' => $request->document_label,
                    'role' => 'tim_pemeriksa',
                    'signer_name' => $pemeriksaName,
                    'signer_phone' => $pemeriksaWa,
                    'status' => 'pending',
                    'magic_token' => $pemeriksaToken,
                ]);
            }

            // Kirim WA
            $waService = app(WhatsappService::class);
            
            $vendorUrl = url('/public/tte/sign/' . $vendorToken);
            $waService->sendMessage($vendorWa, "Yth. $vendorName,\n\nMohon persetujuan (Tanda Tangan Elektronik) untuk dokumen *{$request->document_label}* pada kontrak *{$tagihan->nomor_tagihan}*.\n\nSilakan klik tautan berikut untuk menyetujui dokumen:\n$vendorUrl\n\nTerima kasih.");

            if ($request->document_label === 'BAPP') {
                $pemeriksaUrl = url('/public/tte/sign/' . $pemeriksaToken);
                $waService->sendMessage($pemeriksaWa, "Yth. $pemeriksaName,\n\nMohon persetujuan (Tanda Tangan Elektronik) untuk dokumen *{$request->document_label}* pada kontrak *{$tagihan->nomor_tagihan}*.\n\nSilakan klik tautan berikut untuk menyetujui dokumen:\n$pemeriksaUrl\n\nTerima kasih.");
            }

            DB::commit();
            $msg = $request->document_label === 'BAPP' ? "Akses TTE $request->document_label telah dikirim ke Vendor dan Pemeriksa via WhatsApp." : "Akses TTE $request->document_label telah dikirim ke Vendor via WhatsApp.";
            return back()->with('success', $msg);
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal mengirim akses TTE: ' . $e->getMessage());
        }
    }
}
