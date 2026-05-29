<?php

namespace App\Http\Controllers;

use App\Models\DokumenSpp;
use App\Models\LogStatusDokumen;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

class PublicSppSignatureController extends Controller
{
    public function show(Request $request, int $id)
    {
        $spp = DokumenSpp::with([
            'dibuatOleh',
            'dipaRevisionItem.coa',
            'ppkVerifikator.profilable',
            'tagihan',
            'workflowInstance.definition',
            'workflowInstance.approvals.assignedUser',
            'workflowInstance.approvals.actedByUser',
            'logs.user',
        ])->findOrFail($id);

        abort_unless($spp->isFullyVerifiedForTte(), 403, 'TTE QR hanya tersedia setelah seluruh verifikator menyetujui dokumen SPP.');

        $workflow = $spp->workflowInstance;
        $approvalLogs = LogStatusDokumen::query()
            ->where('dokumen_type', DokumenSpp::class)
            ->where('dokumen_id', $spp->id)
            ->whereIn('aksi', [
                'APPROVE_SPP',
                'APPROVE_PPK',
                'APPROVE_KASUBBAG',
                'APPROVE_KOORDINATOR_KEUANGAN',
                'APPROVE_KEPALA_SUBBAGIAN_KEUANGAN_DAN_TATA_USAHA',
            ])
            ->latest()
            ->get();

        $verifikators = $workflow->approvals
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

        $scanInfo = [
            'user_id' => $request->user()?->id ?? 'PUBLIC',
            'timestamp' => now(),
            'ip_address' => $request->ip(),
            'dokumen_id' => $spp->id,
        ];

        $documentHash = $spp->tteHash();
        $qrHash = $request->query('hash');
        $hashStatus = $qrHash && hash_equals((string) $qrHash, $documentHash)
            ? 'cocok'
            : 'tidak_cocok';
        $signerInfo = $this->buildSignerInfo($spp, $workflow);

        $documentUrl = URL::signedRoute('public.spp-tte.document', [
            'id' => $spp->id,
            'hash' => $documentHash,
        ]);

        return view('public.spp-tte', compact(
            'spp',
            'workflow',
            'verifikators',
            'scanInfo',
            'documentUrl',
            'documentHash',
            'qrHash',
            'hashStatus',
            'signerInfo',
        ));
    }

    public function document(int $id)
    {
        $spp = DokumenSpp::with('workflowInstance.approvals')->findOrFail($id);

        abort_unless($spp->isFullyVerifiedForTte(), 403, 'Dokumen SPP hanya dapat dilihat setelah seluruh verifikator menyetujui dokumen.');

        return app(SppController::class)->cetakPdf($id);
    }

    private function buildSignerInfo(DokumenSpp $spp, $workflow): array
    {
        $pegawai = $spp->ppkVerifikator?->pegawai;
        $cleanSignerValue = static function ($value): ?string {
            $value = trim((string) $value);

            return in_array($value, ['', '-', 'NIP', 'NIP.'], true) ? null : $value;
        };
        $signedAt = collect($workflow->approvals ?? [])
            ->pluck('acted_at')
            ->filter()
            ->sort()
            ->last();

        return [
            'nama' => $cleanSignerValue($spp->penandatangan_nama)
                ?? $cleanSignerValue($pegawai?->nama_lengkap)
                ?? $cleanSignerValue($spp->ppkVerifikator?->name)
                ?? '-',
            'nip' => $cleanSignerValue($spp->penandatangan_nip)
                ?? $cleanSignerValue($pegawai?->nip)
                ?? '-',
            'jabatan' => $pegawai?->jabatan ?? 'Pejabat Pembuat Komitmen',
            'unit_kerja' => 'Kantor UPBU Aji Pangeran Tumenggung Pranoto',
            'instansi' => 'Kementerian Perhubungan',
            'signed_at' => $signedAt ?? $workflow->updated_at ?? $spp->updated_at,
        ];
    }
}
