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
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html)->setPaper('a4', 'portrait');
        return $pdf->stream('preview_dokumen_' . $type . '.pdf');
    }
}
