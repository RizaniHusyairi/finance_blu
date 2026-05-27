<?php

namespace App\Http\Controllers;

use App\Models\DokumenNpi;
use App\Models\DokumenSp2d;
use App\Models\DokumenSpm;
use App\Models\DokumenSpp;
use App\Models\LogStatusDokumen;
use App\Support\DocumentTte;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

class PublicDocumentSignatureController extends Controller
{
    public function show(Request $request, string $type, int $id)
    {
        $document = $this->resolveDocument($type, $id);

        abort_unless(DocumentTte::isFullyVerified($document), 403, 'TTE QR hanya tersedia setelah seluruh verifikator menyetujui dokumen.');

        $workflow = $document->workflowInstance;
        $verifikators = $this->buildVerifikators($document, $workflow);
        $documentHash = DocumentTte::hash($document);
        $qrHash = $request->query('hash');
        $hashStatus = $qrHash && hash_equals((string) $qrHash, $documentHash)
            ? 'cocok'
            : 'tidak_cocok';

        $scanInfo = [
            'user_id' => $request->user()?->id ?? 'PUBLIC',
            'timestamp' => now(),
            'ip_address' => $request->ip(),
            'dokumen_id' => $document->getKey(),
        ];

        $documentUrl = URL::signedRoute('public.document-tte.document', [
            'type' => $type,
            'id' => $document->getKey(),
            'hash' => $documentHash,
        ]);

        return view('public.spp-tte', [
            'spp' => $document instanceof DokumenSpp ? $document : null,
            'document' => $document,
            'documentType' => DocumentTte::typeFor($document),
            'documentLabel' => DocumentTte::labelFor($document),
            'documentNumber' => DocumentTte::numberFor($document),
            'documentDate' => DocumentTte::dateFor($document),
            'documentAmount' => DocumentTte::amountFor($document),
            'workflow' => $workflow,
            'verifikators' => $verifikators,
            'scanInfo' => $scanInfo,
            'documentUrl' => $documentUrl,
            'documentHash' => $documentHash,
            'qrHash' => $qrHash,
            'hashStatus' => $hashStatus,
            'signerInfo' => $this->buildSignerInfo($document, $workflow),
        ]);
    }

    public function document(string $type, int $id)
    {
        $document = $this->resolveDocument($type, $id);

        abort_unless(DocumentTte::isFullyVerified($document), 403, 'Dokumen hanya dapat dilihat setelah seluruh verifikator menyetujui dokumen.');

        return match ($type) {
            'spp' => app(SppController::class)->cetakPdf($id),
            'spm' => app(SpmController::class)->cetakPdfSpm($id),
            'npi' => app(NpiController::class)->cetakPdf($id),
            'sp2d' => app(DocumentController::class)->printSp2d($document),
            default => abort(404),
        };
    }

    private function resolveDocument(string $type, int $id): Model
    {
        return match ($type) {
            'spp' => DokumenSpp::with([
                'dibuatOleh',
                'ppkVerifikator.profilable',
                'tagihan',
                'workflowInstance.definition',
                'workflowInstance.approvals.assignedUser',
                'workflowInstance.approvals.actedByUser',
                'logs.user',
            ])->findOrFail($id),
            'spm' => DokumenSpm::with([
                'dibuatOleh',
                'ppspm.profilable',
                'spp.tagihan',
                'workflowInstance.definition',
                'workflowInstance.approvals.assignedUser',
                'workflowInstance.approvals.actedByUser',
                'logs.user',
            ])->findOrFail($id),
            'npi' => DokumenNpi::with([
                'bendaharaPenerimaan.profilable',
                'spm.spp.tagihan',
                'workflowInstance.definition',
                'workflowInstance.approvals.assignedUser',
                'workflowInstance.approvals.actedByUser',
                'logs.user',
            ])->findOrFail($id),
            'sp2d' => DokumenSp2d::with([
                'bendaharaPengeluaran.profilable',
                'npi.spm.spp.tagihan',
                'workflowInstance.definition',
                'workflowInstance.approvals.assignedUser',
                'workflowInstance.approvals.actedByUser',
                'logs.user',
            ])->findOrFail($id),
            default => abort(404),
        };
    }

    private function buildVerifikators(Model $document, $workflow)
    {
        $approvalLogs = LogStatusDokumen::query()
            ->where('dokumen_type', get_class($document))
            ->where('dokumen_id', $document->getKey())
            ->where('aksi', 'like', '%APPROVE%')
            ->latest()
            ->get();

        return $workflow->approvals
            ->sortBy(fn ($approval) => sprintf(
                '%010d-%s',
                (int) $approval->urutan_step,
                optional($approval->acted_at)->format('YmdHis') ?? '99999999999999'
            ))
            ->map(function ($approval) use ($approvalLogs) {
                $fallbackLog = $approvalLogs->first(function ($log) use ($approval) {
                    return (int) $log->user_id === (int) $approval->acted_by_user_id
                        || $log->role_saat_itu === $approval->role_code;
                });

                return [
                    'step' => $approval->urutan_step,
                    'role' => $approval->role_code,
                    'user_id' => $approval->acted_by_user_id ?? $approval->assigned_user_id,
                    'name' => $approval->actedByUser?->name
                        ?? $approval->assignedUser?->name
                        ?? 'Verifikator',
                    'status' => $approval->status,
                    'acted_at' => $approval->acted_at,
                    'ip_address' => $approval->ip_address ?? $fallbackLog?->ip_address,
                    'catatan' => $approval->catatan ?? $fallbackLog?->catatan,
                ];
            })
            ->values();
    }

    private function buildSignerInfo(Model $document, $workflow): array
    {
        $signedAt = collect($workflow->approvals ?? [])
            ->pluck('acted_at')
            ->filter()
            ->sort()
            ->last();

        $user = match (true) {
            $document instanceof DokumenSpp => $document->ppkVerifikator,
            $document instanceof DokumenSpm => $document->ppspm,
            $document instanceof DokumenNpi => $document->bendaharaPenerimaan,
            $document instanceof DokumenSp2d => $document->bendaharaPengeluaran,
            default => null,
        };

        $pegawai = $user?->pegawai;

        return [
            'nama' => match (true) {
                $document instanceof DokumenSpp => $document->penandatangan_nama ?? $user?->name,
                $document instanceof DokumenSpm => $document->penandatangan_spm_nama ?? $user?->name,
                default => $user?->name,
            } ?? '-',
            'nip' => match (true) {
                $document instanceof DokumenSpp => $document->penandatangan_nip ?? $pegawai?->nip,
                $document instanceof DokumenSpm => $document->penandatangan_spm_nip ?? $pegawai?->nip,
                default => $pegawai?->nip,
            } ?? '-',
            'jabatan' => $pegawai?->jabatan ?? match (true) {
                $document instanceof DokumenSpp => 'Pejabat Pembuat Komitmen',
                $document instanceof DokumenSpm => 'Pejabat Penandatangan SPM',
                $document instanceof DokumenNpi => 'Bendahara Penerimaan',
                $document instanceof DokumenSp2d => 'Bendahara Pengeluaran',
                default => 'Penandatangan Dokumen',
            },
            'unit_kerja' => 'Kantor UPBU Aji Pangeran Tumenggung Pranoto',
            'instansi' => 'Kementerian Perhubungan',
            'signed_at' => $signedAt ?? $workflow->updated_at ?? $document->updated_at,
        ];
    }
}
