<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Str;
use App\Models\Tagihan;
use App\Models\DocumentSignature;
use App\Models\MasterPegawai;
use App\Services\EmailNotificationService;
use App\Services\WhatsappService;
use Illuminate\Support\Facades\Auth;
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
            // Hapus hanya TTE yang masih pending; persetujuan yang sudah
            // diberikan (mis. BAP vendor — prasyarat rantai pencairan) tetap
            // dipertahankan sebagai jejak audit dan agar gate tidak batal.
            $tagihan->documentSignatures()->where('status', 'pending')->delete();

            // ---- Vendor: satu tautan untuk seluruh dokumen ----
            $vendorGroupToken = Str::random(40);
            $vendorSignedLabels = $tagihan->documentSignatures()
                ->where('role', 'vendor')
                ->where('status', 'signed')
                ->pluck('document_label')
                ->all();
            $tagihan->documentSignatures()
                ->where('role', 'vendor')
                ->update(['group_token' => $vendorGroupToken]);
            foreach (array_diff($vendorDocs, $vendorSignedLabels) as $label) {
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
            $pemeriksaSignedLabels = $tagihan->documentSignatures()
                ->where('role', 'tim_pemeriksa')
                ->where('status', 'signed')
                ->pluck('document_label')
                ->all();
            $tagihan->documentSignatures()
                ->where('role', 'tim_pemeriksa')
                ->update(['group_token' => $pemeriksaGroupToken]);
            foreach (array_diff($pemeriksaDocs, $pemeriksaSignedLabels) as $label) {
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
            $menyusulList = 'BAPP' . ($wajibBast ? ' dan BAST' : '');
            $vendorMessage = "Yth. $vendorName,\n\n"
                . "Dengan hormat,\n\n"
                . "Kami mengajukan permohonan persetujuan tanda tangan elektronik untuk dokumen {$vendorDocList} pada tagihan kontrak {$tagihan->nomor_tagihan}.\n\n"
                . "Dokumen BAP wajib disetujui dan diunggah hasil scan ber-TTD saat ini juga; dokumen {$menyusulList} dapat diunggah menyusul melalui tautan yang sama.\n\n"
                . "Silakan meninjau dan menyetujui dokumen melalui tautan berikut:\n"
                . "$vendorUrl\n\n"
                . "Mohon tautan ini digunakan secara bertanggung jawab dan tidak diteruskan kepada pihak yang tidak berkepentingan.\n\n"
                . "Hormat kami,\n"
                . "SIKEREN-BLU";
            $waService->queueMessage($vendorWa, str_replace("{$vendorDocList}", "*{$vendorDocList}*", str_replace($tagihan->nomor_tagihan, "*{$tagihan->nomor_tagihan}*", $vendorMessage)));

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
            $waService->queueMessage($pemeriksaWa, str_replace('BAPP', '*BAPP*', str_replace($tagihan->nomor_tagihan, "*{$tagihan->nomor_tagihan}*", $pemeriksaMessage)));

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
            return back()->with('success', 'Akses TTE telah dikirim: 1 tautan ke Vendor (' . $vendorDocList . ' — BAP wajib, ' . $menyusulList . ' dapat menyusul) dan 1 tautan ke Pemeriksa (BAPP) via WhatsApp, serta email diproses bila alamat tersedia.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal mengirim akses TTE: ' . $e->getMessage());
        }
    }

    /**
     * Unggah manual dokumen bertanda tangan basah vendor (BAP/BAPP/BAST)
     * oleh staf — alternatif jalur TTE online. Persetujuan per dokumen
     * mengikuti file yang diunggah (signed_via = MANUAL) sehingga seluruh
     * gate hilir (BAP → draft SPP) terpenuhi setara jalur TTE.
     */
    public function uploadManualTtd(Request $request, $id)
    {
        $tagihan = Tagihan::with(['detailKontrak.termin.kontrak.vendor', 'documentSignatures'])->findOrFail($id);
        abort_unless($tagihan->tipe_tagihan === 'KONTRAK', 404);

        if (! in_array($tagihan->status, ['APPROVED', 'DISETUJUI_KONTRAK', 'READY_FOR_SPP', 'PROSES_SPP', 'SELESAI'], true)) {
            return back()->with('error', 'Unggah manual hanya dapat dilakukan setelah tagihan disetujui seluruh verifikator.');
        }

        $termin = $tagihan->detailKontrak?->termin;
        if (! $termin) {
            return back()->with('error', 'Detail kontrak tidak ditemukan pada tagihan ini.');
        }

        $wajibBast = $termin->jenis_termin === 'PELUNASAN';
        $vendorDocs = $wajibBast ? ['BAPP', 'BAP', 'BAST'] : ['BAPP', 'BAP'];

        $bapSigned = $tagihan->documentSignatures
            ->where('role', 'vendor')->where('document_label', 'BAP')
            ->contains(fn ($s) => $s->status === 'signed');

        $request->validate([
            'dokumen.BAP_FINAL_TTD' => [$bapSigned ? 'nullable' : 'required', 'file', 'mimes:pdf', 'max:10240'],
            'dokumen.BAPP_FINAL_TTD' => 'nullable|file|mimes:pdf|max:10240',
            'dokumen.BAST_FINAL_TTD' => ($wajibBast ? 'nullable|file|mimes:pdf|max:10240' : 'prohibited'),
            'tanggal_ttd_vendor' => 'required|date|before_or_equal:today',
            'keterangan' => 'nullable|string|max:1000',
            'pernyataan' => 'required|accepted',
        ], [
            'dokumen.BAP_FINAL_TTD.required' => 'Scan BAP final ber-TTD wajib diunggah.',
            'pernyataan.required' => 'Centang pernyataan tanggung jawab terlebih dahulu.',
            'pernyataan.accepted' => 'Centang pernyataan tanggung jawab terlebih dahulu.',
        ]);

        $files = collect($request->file('dokumen', []))->filter();
        if ($files->isEmpty()) {
            return back()->with('error', 'Pilih minimal satu dokumen untuk diunggah.');
        }

        // Berbeda dengan jalur TTE online, unggah manual BAPP TIDAK menunggu
        // TTE Tim Pemeriksa: scan yang diunggah dianggap sudah memuat TTD
        // basah vendor DAN Tim Pemeriksa sekaligus (ditegaskan pada pernyataan
        // tanggung jawab), sehingga tanda tangan pemeriksa ikut ditandai.

        $bapBaruSigned = false;

        DB::transaction(function () use ($request, $tagihan, $files, $vendorDocs, &$bapBaruSigned) {
            // Pastikan baris signature vendor tersedia meski akses TTE belum
            // pernah dikirim — unggah manual berdiri sendiri tanpa magic link.
            $kontrak = $tagihan->detailKontrak->termin->kontrak;
            $vendorGroupToken = $tagihan->documentSignatures()
                ->where('role', 'vendor')->value('group_token') ?: Str::random(40);

            foreach ($vendorDocs as $label) {
                $tagihan->documentSignatures()->firstOrCreate(
                    ['role' => 'vendor', 'document_label' => $label],
                    [
                        'signer_name' => $kontrak->vendor->nama_pihak ?? 'Vendor',
                        'signer_phone' => preg_replace('/\D+/', '', $kontrak->vendor->no_telepon ?? ''),
                        'status' => 'pending',
                        'magic_token' => Str::random(40),
                        'group_token' => $vendorGroupToken,
                    ],
                );
            }

            foreach ($files as $jenisDok => $file) {
                $tagihan->detailKontrak->arsipDokumen()
                    ->where('jenis_dokumen', $jenisDok)
                    ->update(['is_active' => false]);

                $tagihan->detailKontrak->arsipDokumen()->create([
                    'jenis_dokumen' => $jenisDok,
                    'nama_file_asli' => $file->getClientOriginalName(),
                    'path_file' => $file->store('tagihan/final_docs', 'public'),
                    'disk' => 'public',
                    'mime_type' => $file->getMimeType(),
                    'ukuran_file' => $file->getSize(),
                    'uploaded_by' => Auth::id(),
                    'uploaded_at' => now(),
                    'keterangan' => 'Unggah manual scan TTD basah vendor (tanggal TTD: ' . $request->input('tanggal_ttd_vendor') . ').',
                    'is_active' => true,
                ]);

                $label = str_replace('_FINAL_TTD', '', $jenisDok);
                $sig = $tagihan->documentSignatures()
                    ->where('role', 'vendor')->where('document_label', $label)->first();
                if ($sig && $sig->status !== 'signed') {
                    $sig->update([
                        'status' => 'signed',
                        'signed_via' => 'MANUAL',
                        'signed_by_user_id' => Auth::id(),
                        'signed_at' => now(),
                        'ip_address' => $request->ip(),
                        'user_agent' => $request->userAgent(),
                    ]);
                    if ($label === 'BAP') {
                        $bapBaruSigned = true;
                    }
                }

                // Scan BAPP manual memuat TTD basah Tim Pemeriksa sekaligus —
                // tandai persetujuan pemeriksa (buat barisnya bila TTE belum
                // pernah dikirim) agar status & QR TTE BAPP konsisten.
                if ($label === 'BAPP') {
                    $detail = $tagihan->detailKontrak;
                    $pemeriksaSig = $tagihan->documentSignatures()->firstOrCreate(
                        ['role' => 'tim_pemeriksa', 'document_label' => 'BAPP'],
                        [
                            'signer_name' => $detail->nama_pemeriksa ?? 'Tim Pemeriksa',
                            'signer_phone' => preg_replace('/\D+/', '', $detail->wa_pemeriksa ?? ''),
                            'status' => 'pending',
                            'magic_token' => Str::random(40),
                            'group_token' => Str::random(40),
                        ],
                    );
                    if ($pemeriksaSig->status !== 'signed') {
                        $pemeriksaSig->update([
                            'status' => 'signed',
                            'signed_via' => 'MANUAL',
                            'signed_by_user_id' => Auth::id(),
                            'signed_at' => now(),
                            'ip_address' => $request->ip(),
                            'user_agent' => $request->userAgent(),
                        ]);
                    }
                }
            }

            \App\Models\LogStatusDokumen::create([
                'dokumen_type' => Tagihan::class,
                'dokumen_id' => $tagihan->id,
                'user_id' => Auth::id(),
                'role_saat_itu' => Auth::user()?->getRoleNames()->first() ?? '-',
                'status_sebelumnya' => $tagihan->status,
                'status_baru' => $tagihan->status,
                'aksi' => 'UPLOAD_MANUAL_TTD',
                'catatan' => 'Unggah manual TTD basah vendor: ' . $files->keys()
                    ->map(fn ($j) => str_replace('_FINAL_TTD', '', $j))->implode(', ')
                    . ' (tanggal TTD vendor: ' . $request->input('tanggal_ttd_vendor') . ').'
                    . ($files->has('BAPP_FINAL_TTD') ? ' Scan BAPP dinyatakan memuat TTD basah Tim Pemeriksa — persetujuan pemeriksa ikut ditandai.' : '')
                    . ($request->filled('keterangan') ? ' Keterangan: ' . $request->input('keterangan') : ''),
                'ip_address' => $request->ip(),
            ]);
        });

        // BAP terpenuhi → coba buat draft rantai pencairan. Actor = pembuat
        // tagihan (bukan PPK/pengunggah) agar guard maker≠checker tidak
        // memblokir PPK memverifikasi SPP.
        if ($bapBaruSigned) {
            try {
                $actor = \App\Models\User::find($tagihan->created_by);
                app(\App\Services\DokumenChainService::class)->maybeGenerateDraftChain($tagihan->fresh(), $actor);
            } catch (\RuntimeException $e) {
                \Illuminate\Support\Facades\Log::warning('Draft chain generation after manual BAP upload failed.', [
                    'tagihan_id' => $tagihan->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return back()->with('success', 'Dokumen ' . $files->keys()
            ->map(fn ($j) => str_replace('_FINAL_TTD', '', $j))->implode(', ')
            . ' berhasil diunggah dan ditandai ditandatangani vendor (manual).');
    }
}
