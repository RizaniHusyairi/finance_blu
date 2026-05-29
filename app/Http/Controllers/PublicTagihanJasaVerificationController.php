<?php

namespace App\Http\Controllers;

use App\Models\TagihanJasa;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Fluent;

class PublicTagihanJasaVerificationController extends Controller
{
    public function show(Request $request, $id)
    {
        $tagihan = TagihanJasa::with([
            'mitra',
            'mitraLegacy',
            'kontrakMitraJasa',
            'creator',
            'suratPengantarSigner',
            'details.layananJasa',
            'workflowInstance.definition',
            'workflowInstance.approvals.assignedUser',
            'workflowInstance.approvals.actedByUser',
        ])->findOrFail($id);

        $expectedSeal = $tagihan->digitalSealHash();
        $providedSeal = (string) $request->query('seal', '');
        $isValid = hash_equals($expectedSeal, $providedSeal);
        $isSignedFinal = $tagihan->status_dokumen_pengantar === 'SUDAH_DITANDATANGANI'
            && ! empty($tagihan->file_surat_pengantar_final);

        abort_unless($isSignedFinal, 403, 'TTE QR surat pengantar hanya tersedia setelah seluruh verifikator menyetujui dokumen.');

        $workflow = $tagihan->workflowInstance ?: new Fluent([
            'status' => $tagihan->status,
            'definition' => null,
            'approvals' => collect(),
        ]);

        $verifikators = collect($workflow->approvals ?? [])
            ->sortBy(fn ($approval) => sprintf(
                '%010d-%s',
                (int) ($approval->urutan_step ?? 0),
                optional($approval->acted_at)->format('YmdHis') ?? '99999999999999'
            ))
            ->values()
            ->map(function ($approval, int $index) {
                return [
                    'step' => $approval->urutan_step ?: ($index + 1),
                    'role' => $approval->nama_step ?: ($approval->role_code ?? 'Verifikator'),
                    'user_id' => $approval->acted_by_user_id ?? $approval->assigned_user_id,
                    'name' => $approval->actedByUser?->name
                        ?? $approval->assignedUser?->name
                        ?? 'Verifikator',
                    'status' => $approval->status,
                    'acted_at' => $approval->acted_at,
                    'ip_address' => $approval->ip_address,
                    'catatan' => $approval->catatan,
                ];
            });

        $documentHash = $expectedSeal;
        $qrHash = $providedSeal;
        $hashStatus = $isValid ? 'cocok' : 'tidak_cocok';
        $tanggalSurat = $tagihan->tanggal_surat_pengantar ?: $tagihan->tanggal_tagihan;

        $signerInfo = [
            'nama' => $tagihan->pejabat_penandatangan_nama
                ?: ($tagihan->suratPengantarSigner?->name ?? '-'),
            'nip' => $tagihan->pejabat_penandatangan_nip ?: '-',
            'jabatan' => $tagihan->pejabat_penandatangan_jabatan ?: 'Pejabat Penandatangan Surat Pengantar',
            'unit_kerja' => 'Kantor UPBU Aji Pangeran Tumenggung Pranoto',
            'instansi' => 'Kementerian Perhubungan',
            'signed_at' => $tagihan->uploaded_surat_pengantar_at ?: $workflow->updated_at ?: $tagihan->updated_at,
        ];

        return view('public.spp-tte', [
            'spp' => null,
            'document' => $tagihan,
            'documentLabel' => 'Surat Pengantar Jasa',
            'documentNumber' => $tagihan->nomor_surat_pengantar ?: $tagihan->nomor_tagihan,
            'documentType' => 'surat-pengantar-jasa',
            'documentDate' => $tanggalSurat ? Carbon::parse($tanggalSurat) : null,
            'documentAmount' => (float) $tagihan->total_tagihan,
            'documentKind' => 'Tagihan PNBP Jasa',
            'workflow' => $workflow,
            'verifikators' => $verifikators,
            'scanInfo' => [
                'user_id' => $request->user()?->id ?? 'PUBLIC',
                'timestamp' => now(),
                'ip_address' => $request->ip(),
                'dokumen_id' => $tagihan->id,
            ],
            'documentUrl' => URL::signedRoute('public.tagihan-jasa.surat-pengantar-tte.document', [
                'id' => $tagihan->id,
                'seal' => $documentHash,
            ]),
            'documentHash' => $documentHash,
            'qrHash' => $qrHash,
            'hashStatus' => $hashStatus,
            'signerInfo' => $signerInfo,
        ]);
    }

    public function document(Request $request, $id)
    {
        $tagihan = TagihanJasa::with(['mitra', 'mitraLegacy', 'details'])->findOrFail($id);
        $expectedSeal = $tagihan->digitalSealHash();
        $providedSeal = (string) $request->query('seal', '');

        abort_unless(hash_equals($expectedSeal, $providedSeal), 403, 'Segel digital surat pengantar tidak valid.');
        abort_unless(
            $tagihan->status_dokumen_pengantar === 'SUDAH_DITANDATANGANI'
                && ! empty($tagihan->file_surat_pengantar_final),
            403,
            'Dokumen surat pengantar final TTE belum tersedia.'
        );

        abort_unless(
            Storage::disk('public')->exists($tagihan->file_surat_pengantar_final),
            404,
            'File surat pengantar final tidak ditemukan.'
        );

        return Storage::disk('public')->response(
            $tagihan->file_surat_pengantar_final,
            basename($tagihan->file_surat_pengantar_final)
        );
    }
}
