<?php

namespace App\Http\Controllers;

use App\Models\Tagihan;
use App\Support\TagihanDocumentTte;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

class PublicTagihanSignatureController extends Controller
{
    public function show(Request $request, string $type, int $id)
    {
        abort_unless(TagihanDocumentTte::isValidType($type), 404);

        $tagihan = Tagihan::with([
                'workflowInstance.approvals.actedByUser.profilable',
            ])
            ->findOrFail($id);

        abort_unless(
            in_array($type, TagihanDocumentTte::typesFor($tagihan->tipe_tagihan), true),
            404,
            'Jenis dokumen TTE tidak berlaku untuk tipe tagihan ini.'
        );

        abort_unless(
            TagihanDocumentTte::isApproved($tagihan),
            403,
            'QR TTE hanya tersedia setelah tagihan disetujui penuh dalam workflow.'
        );

        $documentHash = TagihanDocumentTte::hash($tagihan, $type);
        $qrHash = $request->query('hash');
        $hashStatus = $qrHash && hash_equals($documentHash, (string) $qrHash) ? 'cocok' : 'tidak_cocok';

        $approvals = $tagihan->workflowInstance?->approvals
            ?->sortBy(fn ($a) => sprintf('%010d-%010d', (int) $a->urutan_step, (int) $a->id))
            ->values()
            ?? collect();

        $signers = $approvals->map(function ($approval) {
            $user = $approval->actedByUser;
            $pegawai = $user?->profilable;

            return [
                'nama' => $pegawai?->nama_lengkap ?? $user?->name ?? '-',
                'nip' => $pegawai?->nip ?? '-',
                'jabatan' => $approval->nama_step ?: ($approval->role_code ?? '-'),
                'role' => $approval->role_code,
                'acted_at' => $approval->acted_at,
            ];
        })->all();

        $primarySigner = end($signers) ?: [
            'nama' => '-', 'nip' => '-', 'jabatan' => '-', 'role' => '-', 'acted_at' => null,
        ];

        $scanInfo = [
            'user_id' => $request->user()?->id ?? 'PUBLIC',
            'timestamp' => now(),
            'ip_address' => $request->ip(),
            'dokumen_id' => $tagihan->getKey(),
        ];

        $documentUrl = URL::signedRoute('public.tagihan-tte.document', [
            'type' => $type,
            'id' => $tagihan->getKey(),
            'hash' => $documentHash,
        ]);

        return view('public.tagihan-tte', [
            'tagihan' => $tagihan,
            'documentType' => $type,
            'documentLabel' => TagihanDocumentTte::labelFor($type),
            'documentNumber' => TagihanDocumentTte::numberFor($tagihan, $type),
            'scanInfo' => $scanInfo,
            'documentUrl' => $documentUrl,
            'documentHash' => $documentHash,
            'qrHash' => $qrHash,
            'hashStatus' => $hashStatus,
            'signers' => $signers,
            'primarySigner' => $primarySigner,
        ]);
    }

    public function document(string $type, int $id)
    {
        abort_unless(TagihanDocumentTte::isValidType($type), 404);

        $tagihan = Tagihan::findOrFail($id);

        abort_unless(
            in_array($type, TagihanDocumentTte::typesFor($tagihan->tipe_tagihan), true),
            404,
        );

        abort_unless(
            TagihanDocumentTte::isApproved($tagihan),
            403,
            'Dokumen hanya dapat dilihat setelah tagihan disetujui penuh.'
        );

        return match ($type) {
            'nominatif_perjaldin' => app(PerjaldinController::class)->exportPdfNominatif($id),
            'daftar_nominatif_pembayaran_perjaldin' => app(PerjaldinController::class)->exportPdfLampiran($id),
            'rekap_honorarium' => app(HonorariumController::class)->exportPdf($id),
            'nominatif_honorarium' => app(HonorariumController::class)->exportNominatifPdf($id),
            default => abort(404),
        };
    }
}
