<?php

namespace App\Http\Controllers;

use App\Models\KontrakPengadaan;
use App\Support\ContractDocumentTte;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

class PublicContractSignatureController extends Controller
{
    public function show(Request $request, string $type, int $id)
    {
        abort_unless(ContractDocumentTte::isValidType($type), 404);

        $kontrak = KontrakPengadaan::with(['vendor', 'ppkApprover.profilable', 'ppkUser.profilable'])
            ->findOrFail($id);

        abort_unless(
            ContractDocumentTte::isApproved($kontrak),
            403,
            'QR TTE hanya tersedia setelah kontrak disetujui PPK.'
        );

        $documentHash = ContractDocumentTte::hash($kontrak, $type);
        $qrHash = $request->query('hash');
        $hashStatus = $qrHash && hash_equals($documentHash, (string) $qrHash) ? 'cocok' : 'tidak_cocok';

        $approver = $kontrak->ppkApprover ?? $kontrak->ppkUser;
        $pegawai = $approver?->pegawai;

        $scanInfo = [
            'user_id' => $request->user()?->id ?? 'PUBLIC',
            'timestamp' => now(),
            'ip_address' => $request->ip(),
            'dokumen_id' => $kontrak->getKey(),
        ];

        $documentUrl = URL::signedRoute('public.contract-tte.document', [
            'type' => $type,
            'id' => $kontrak->getKey(),
            'hash' => $documentHash,
        ]);

        return view('public.contract-tte', [
            'kontrak' => $kontrak,
            'documentType' => $type,
            'documentLabel' => ContractDocumentTte::labelFor($type),
            'documentNumber' => ContractDocumentTte::numberFor($kontrak, $type),
            'scanInfo' => $scanInfo,
            'documentUrl' => $documentUrl,
            'documentHash' => $documentHash,
            'qrHash' => $qrHash,
            'hashStatus' => $hashStatus,
            'signerInfo' => [
                'nama' => $pegawai?->nama_lengkap ?? $approver?->name ?? '-',
                'nip' => $pegawai?->nip ?? '-',
                'jabatan' => $pegawai?->jabatan ?? 'Pejabat Pembuat Komitmen',
                'unit_kerja' => 'Kantor UPBU Aji Pangeran Tumenggung Pranoto',
                'instansi' => 'Kementerian Perhubungan',
                'signed_at' => $kontrak->ppk_approved_at,
            ],
        ]);
    }

    public function document(string $type, int $id)
    {
        abort_unless(ContractDocumentTte::isValidType($type), 404);

        $kontrak = KontrakPengadaan::findOrFail($id);

        abort_unless(
            ContractDocumentTte::isApproved($kontrak),
            403,
            'Dokumen hanya dapat dilihat setelah kontrak disetujui PPK.'
        );

        $controller = app(ContractController::class);

        return match ($type) {
            'spk' => $controller->exportSpkPdf($id),
            'spmk' => $controller->exportSpmkPdf($id),
            'ringkasan_kontrak' => $controller->exportRingkasanKontrakPdf($id),
            default => abort(404),
        };
    }
}
