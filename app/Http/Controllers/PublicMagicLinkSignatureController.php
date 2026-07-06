<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;
use App\Models\DocumentSignature;

class PublicMagicLinkSignatureController extends Controller
{
    /**
     * Mengambil seluruh dokumen pada satu tautan penerima.
     * Mendukung tautan baru (group_token) maupun tautan lama (magic_token).
     */
    private function resolveGroup($token)
    {
        $group = DocumentSignature::with('documentable')
            ->where('group_token', $token)
            ->orderBy('id')
            ->get();

        if ($group->isNotEmpty()) {
            return $group;
        }

        // Fallback tautan lama (per-dokumen)
        $single = DocumentSignature::with('documentable')->where('magic_token', $token)->first();
        return $single ? collect([$single]) : collect();
    }

    public function show($token)
    {
        $signatures = $this->resolveGroup($token);
        abort_if($signatures->isEmpty(), 404);

        // Jika seluruh dokumen sudah disetujui, tampilkan halaman konfirmasi
        if ($signatures->every(fn($s) => $s->status === 'signed')) {
            $signature = $signatures->first();
            return view('public.magic-link-signed', compact('signature', 'signatures'));
        }

        $signature = $signatures->first();
        $tagihan = $signature->documentable;
        $detailKontrak = $tagihan->detailKontrak;

        // Setiap dokumen punya pratinjau PDF tersendiri (memakai magic_token-nya)
        $documents = $signatures->map(fn($s) => [
            'signature' => $s,
            'pdfUrl' => route('public.magic-link.document', $s->magic_token),
        ]);

        // Vendor yang sudah menyetujui BAP masuk mode "unggah menyusul"
        // (BAPP/BAST diunggah belakangan lewat tautan yang sama).
        $bapVendorSigned = $signature->role === 'vendor'
            && $signatures->firstWhere('document_label', 'BAP')?->status === 'signed';

        return view('public.magic-link-sign', compact('signatures', 'signature', 'documents', 'tagihan', 'detailKontrak', 'token', 'bapVendorSigned'));
    }

    public function sign(Request $request, $token)
    {
        $signatures = $this->resolveGroup($token);
        abort_if($signatures->isEmpty(), 404);

        if ($signatures->every(fn($s) => $s->status === 'signed')) {
            return redirect()->route('public.magic-link.signed', $token)->with('error', 'Dokumen sudah disetujui sebelumnya.');
        }

        $isVendor = $signatures->first()->role === 'vendor';

        // Vendor: BAP wajib diunggah saat menyetujui; BAPP/BAST boleh menyusul
        // lewat tautan yang sama (persetujuan per dokumen mengikuti file-nya).
        if ($isVendor) {
            $bapSigned = $signatures->firstWhere('document_label', 'BAP')?->status === 'signed';

            $request->validate([
                'files.BAP_FINAL_TTD' => [$bapSigned ? 'nullable' : 'required', 'file', 'mimes:pdf', 'max:10240'],
                'files.BAPP_FINAL_TTD' => 'nullable|file|mimes:pdf|max:10240',
                'files.BAST_FINAL_TTD' => 'nullable|file|mimes:pdf|max:10240',
            ], [
                'files.BAP_FINAL_TTD.required' => 'Scan BAP final ber-TTD wajib diunggah untuk menyetujui dokumen.',
            ]);

            // BAPP hanya boleh diunggah setelah Tim Pemeriksa menyetujui (TTE).
            if ($request->hasFile('files.BAPP_FINAL_TTD')) {
                $tagihan = $signatures->first()->documentable;
                $pemeriksaSigs = $tagihan->documentSignatures->where('role', 'tim_pemeriksa');
                $pemeriksaSigned = $pemeriksaSigs->count() > 0 && $pemeriksaSigs->every(fn($s) => $s->status === 'signed');
                if (! $pemeriksaSigned) {
                    return back()->with('error', 'Dokumen BAPP Final belum dapat diunggah karena Pemeriksa belum memberikan persetujuan (TTE).');
                }
            }
        }

        $bapBaruSigned = false;

        DB::transaction(function () use ($signatures, $request, $isVendor, &$bapBaruSigned) {
            $tagihan = $signatures->first()->documentable;

            // Handle simultaneous file uploads (khusus vendor)
            if ($request->hasFile('files') && is_array($request->file('files'))) {
                foreach ($request->file('files') as $jenisDok => $file) {
                    if ($file->isValid()) {
                        // Nonaktifkan file lama
                        $tagihan->detailKontrak->arsipDokumen()
                            ->where('jenis_dokumen', $jenisDok)
                            ->update(['is_active' => false]);

                        // Simpan file baru
                        $tagihan->detailKontrak->arsipDokumen()->create([
                            'jenis_dokumen' => $jenisDok,
                            'nama_file_asli' => $file->getClientOriginalName(),
                            'path_file' => $file->store('tagihan/final_docs', 'public'),
                            'disk' => 'public',
                            'mime_type' => $file->getMimeType(),
                            'ukuran_file' => $file->getSize(),
                            'uploaded_by' => null, // diunggah via public link
                            'is_active' => true,
                        ]);

                        // Vendor: persetujuan per dokumen mengikuti file yang diunggah.
                        if ($isVendor) {
                            $label = str_replace('_FINAL_TTD', '', $jenisDok);
                            $sig = $signatures->firstWhere('document_label', $label);
                            if ($sig && $sig->status !== 'signed') {
                                $sig->update([
                                    'status' => 'signed',
                                    'signed_via' => 'ONLINE',
                                    'signed_at' => now(),
                                    'ip_address' => $request->ip(),
                                    'user_agent' => $request->userAgent(),
                                ]);
                                if ($label === 'BAP') {
                                    $bapBaruSigned = true;
                                }
                            }
                        }
                    }
                }
            }

            // Non-vendor (Tim Pemeriksa): persetujuan tanpa file — tandai seluruh
            // dokumen pada tautan ini sebagaimana perilaku semula.
            if (! $isVendor) {
                foreach ($signatures as $signature) {
                    if ($signature->status === 'signed') {
                        continue;
                    }
                    $signature->update([
                        'status' => 'signed',
                        'signed_via' => 'ONLINE',
                        'signed_at' => now(),
                        'ip_address' => $request->ip(),
                        'user_agent' => $request->userAgent(),
                    ]);
                }
            }
        });

        // BAP vendor terpenuhi → coba buat draft rantai pencairan (prasyarat lain
        // mungkin sudah lengkap lebih dulu, mis. KPA sudah menyetujui).
        if ($bapBaruSigned) {
            $this->maybeGenerateChainAfterBap($signatures->first()->documentable);
        }

        if ($signatures->map->fresh()->every(fn($s) => $s->status === 'signed')) {
            return redirect()->route('public.magic-link.signed', $token)->with('success', 'Terima kasih, Anda telah menyetujui seluruh dokumen.');
        }

        return redirect()->route('public.magic-link.show', $token)
            ->with('success', 'Terima kasih, persetujuan BAP Anda telah tercatat. Dokumen yang tersisa dapat diunggah menyusul melalui tautan yang sama.');
    }

    /**
     * Setelah BAP vendor tersetujui, coba generate draft rantai pencairan.
     * Best-effort: kegagalan (mis. sisa pagu) tidak boleh menggagalkan
     * request publik vendor — dicatat sebagai warning saja.
     *
     * Actor draft = pembuat tagihan (bukan PPK): dibuat_oleh_id SPP wajib
     * terisi, dan PPK tidak boleh menjadi pembuat karena ia verifikator SPP
     * (guard maker ≠ checker akan memblokirnya).
     */
    private function maybeGenerateChainAfterBap($tagihan): void
    {
        if (! $tagihan || $tagihan->tipe_tagihan !== 'KONTRAK') {
            return;
        }

        try {
            $actor = \App\Models\User::find($tagihan->created_by);
            app(\App\Services\DokumenChainService::class)->maybeGenerateDraftChain($tagihan->fresh(), $actor);
        } catch (\RuntimeException $e) {
            \Illuminate\Support\Facades\Log::warning('Draft chain generation after vendor BAP sign failed.', [
                'tagihan_id' => $tagihan->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function signed($token)
    {
        $signatures = $this->resolveGroup($token);
        abort_if($signatures->isEmpty(), 404);
        $signature = $signatures->first();
        return view('public.magic-link-signed', compact('signature', 'signatures'));
    }

    public function verifyQr(Request $request, $id, $type)
    {
        $tagihan = \App\Models\Tagihan::with([
            'detailKontrak.termin.kontrak.vendor',
            'detailKontrak.termin.kontrak.ppkUser.profilable',
            'detailKontrak.arsipDokumen',
            'documentSignatures',
        ])->findOrFail($id);

        $signatures = $tagihan->documentSignatures->where('document_label', $type);

        $documentHash = \App\Support\ContractBaTte::hash($tagihan, $type);
        $qrHash = $request->query('hash');
        // Tautan lama tanpa hash dianggap valid (legacy); bila hash dikirim, harus cocok.
        $hashStatus = ! $qrHash || hash_equals($documentHash, (string) $qrHash) ? 'cocok' : 'tidak_cocok';

        $ppkUser = $tagihan->detailKontrak->termin->kontrak->ppkUser;
        $ppkProfil = $ppkUser?->profilable;
        $finalArsip = $tagihan->detailKontrak->arsipDokumen->firstWhere('jenis_dokumen', $type . '_FINAL_TTD');

        $signerInfo = [
            'nama' => $ppkProfil?->nama_lengkap ?? $ppkUser?->name ?? 'PPK',
            'nip' => $ppkProfil?->nip ?? '-',
            'jabatan' => $ppkProfil?->jabatan ?? 'Pejabat Pembuat Komitmen',
            'unit_kerja' => 'Kantor UPBU Aji Pangeran Tumenggung Pranoto',
            'instansi' => 'Kementerian Perhubungan',
            'signed_at' => $finalArsip ? $finalArsip->created_at : null,
            'role' => 'PPK'
        ];

        return view('public.tagihan-document-tte-verify', compact(
            'tagihan',
            'type',
            'signatures',
            'documentHash',
            'hashStatus',
            'signerInfo'
        ));
    }

    public function documentPdf($token)
    {
        $signature = DocumentSignature::with('documentable')->where('magic_token', $token)->firstOrFail();
        $tagihan = $signature->documentable;
        $type = $signature->document_label;

        $html = app(\App\Http\Controllers\TagihanController::class)->exportPdfKontrakHtml($tagihan->id, $type, false);

        // BAPP mengembalikan null bila Gambar RAB belum diunggah pada tagihan.
        if ($html === null) {
            abort(422, $type === 'BAPP'
                ? 'Pratinjau BAPP belum dapat dibuat karena Gambar RAB BAPP belum diunggah pada tagihan. Hubungi pembuat tagihan untuk mengunggahnya terlebih dahulu.'
                : 'Pratinjau dokumen ' . $type . ' belum dapat dibuat.');
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html)->setPaper('a4', 'portrait');
        return $pdf->stream('preview_dokumen_' . $type . '.pdf');
    }

    public function uploadArsip(Request $request, $token)
    {
        $signatures = $this->resolveGroup($token);
        abort_if($signatures->isEmpty(), 404);

        $signature = $signatures->first();
        if ($signature->role !== 'vendor') {
            return back()->with('error', 'Hanya pihak vendor yang dapat mengunggah dokumen.');
        }

        $tagihan = $signature->documentable;
        $detailKontrak = $tagihan->detailKontrak;

        $jenis = $request->input('jenis_dokumen');
        
        $request->validate([
            'jenis_dokumen' => 'required|in:BAPP_FINAL_TTD,BAST_FINAL_TTD,BAP_FINAL_TTD',
            'file' => 'required|file|mimes:pdf|max:10240',
        ]);

        if ($jenis === 'BAPP_FINAL_TTD') {
            $pemeriksaSigs = $tagihan->documentSignatures->where('role', 'tim_pemeriksa');
            $pemeriksaSigned = $pemeriksaSigs->count() > 0 && $pemeriksaSigs->every(fn($s) => $s->status === 'signed');
            if (!$pemeriksaSigned) {
                return back()->with('error', 'Dokumen BAPP Final belum dapat diunggah karena Pemeriksa belum memberikan persetujuan (TTE).');
            }
        }

        $bapBaruSigned = false;

        DB::transaction(function () use ($request, $detailKontrak, $signatures, $jenis, &$bapBaruSigned) {
            // Nonaktifkan dokumen lama jika ada
            $detailKontrak->arsipDokumen()->where('jenis_dokumen', $jenis)->update(['is_active' => false]);
            $path = $request->file('file')->store('tagihan/final_docs', 'public');

            $detailKontrak->arsipDokumen()->create([
                'jenis_dokumen' => $jenis,
                'nama_file_asli' => $request->file('file')->getClientOriginalName(),
                'path_file' => $path,
                'disk' => 'public',
                'mime_type' => $request->file('file')->getMimeType(),
                'ukuran_file' => $request->file('file')->getSize(),
                'uploaded_by' => null,
                'uploaded_at' => now(),
                'is_active' => true,
            ]);

            // Persetujuan vendor per dokumen mengikuti file yang diunggah —
            // dokumen menyusul (BAPP/BAST) resmi disetujui saat file-nya masuk.
            $label = str_replace('_FINAL_TTD', '', $jenis);
            $sig = $signatures->firstWhere('document_label', $label);
            if ($sig && $sig->status !== 'signed') {
                $sig->update([
                    'status' => 'signed',
                    'signed_via' => 'ONLINE',
                    'signed_at' => now(),
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);
                if ($label === 'BAP') {
                    $bapBaruSigned = true;
                }
            }
        });

        if ($bapBaruSigned) {
            $this->maybeGenerateChainAfterBap($tagihan);
        }

        if ($signatures->map->fresh()->every(fn($s) => $s->status === 'signed')) {
            return redirect()->route('public.magic-link.signed', $token)
                ->with('success', 'Seluruh dokumen telah lengkap dan disetujui. Terima kasih.');
        }

        return back()->with('success', 'Dokumen ' . str_replace('_FINAL_TTD', '', $jenis) . ' Final berhasil diunggah.');
    }
}
