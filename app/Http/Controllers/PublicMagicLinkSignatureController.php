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

        return view('public.magic-link-sign', compact('signatures', 'signature', 'documents', 'tagihan', 'detailKontrak', 'token'));
    }

    public function sign(Request $request, $token)
    {
        $signatures = $this->resolveGroup($token);
        abort_if($signatures->isEmpty(), 404);

        if ($signatures->every(fn($s) => $s->status === 'signed')) {
            return redirect()->route('public.magic-link.signed', $token)->with('error', 'Dokumen sudah disetujui sebelumnya.');
        }

        DB::transaction(function () use ($signatures, $request) {
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
                    }
                }
            }

            foreach ($signatures as $signature) {
                if ($signature->status === 'signed') {
                    continue;
                }
                $signature->update([
                    'status' => 'signed',
                    'signed_at' => now(),
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);
            }
        });

        return redirect()->route('public.magic-link.signed', $token)->with('success', 'Terima kasih, Anda telah menyetujui seluruh dokumen.');
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

        return back()->with('success', 'Dokumen ' . str_replace('_FINAL_TTD', '', $jenis) . ' Final berhasil diunggah.');
    }
}
