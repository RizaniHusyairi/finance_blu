<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;
use App\Models\DocumentSignature;

class PublicMagicLinkSignatureController extends Controller
{
    public function show($token)
    {
        $signature = DocumentSignature::with('documentable')->where('magic_token', $token)->firstOrFail();
        
        if ($signature->status === 'signed') {
            return view('public.magic-link-signed', compact('signature'));
        }
        
        $tagihan = $signature->documentable;
        $detailKontrak = $tagihan->detailKontrak;
        // Use the public route instead of the authenticated export route
        $pdfUrl = route('public.magic-link.document', $token);

        return view('public.magic-link-sign', compact('signature', 'tagihan', 'detailKontrak', 'pdfUrl'));
    }

    public function sign(Request $request, $token)
    {
        $signature = DocumentSignature::where('magic_token', $token)->firstOrFail();
        
        if ($signature->status === 'signed') {
            return redirect()->back()->with('error', 'Dokumen sudah disetujui sebelumnya.');
        }

        $signature->update([
            'status' => 'signed',
            'signed_at' => now(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent()
        ]);

        return redirect()->route('public.magic-link.signed', $token)->with('success', 'Terima kasih, Anda telah menyetujui dokumen ini.');
    }
    
    public function signed($token)
    {
        $signature = DocumentSignature::with('documentable')->where('magic_token', $token)->firstOrFail();
        return view('public.magic-link-signed', compact('signature'));
    }

    public function verifyQr(Request $request, $id, $type)
    {
        $tagihan = \App\Models\Tagihan::with(['detailKontrak', 'documentSignatures'])->findOrFail($id);
        $signatures = $tagihan->documentSignatures->where('document_label', $type);
        
        return view('public.tagihan-document-tte-verify', compact('tagihan', 'type', 'signatures'));
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
