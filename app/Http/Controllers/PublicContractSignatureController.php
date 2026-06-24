<?php

namespace App\Http\Controllers;

use App\Models\KontrakPengadaan;
use App\Support\ContractDocumentTte;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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

    public function document(Request $request, string $type, int $id)
    {
        abort_unless(ContractDocumentTte::isValidType($type), 404);

        $kontrak = KontrakPengadaan::findOrFail($id);

        abort_unless(
            ContractDocumentTte::isApproved($kontrak),
            403,
            'Dokumen hanya dapat dilihat setelah kontrak disetujui PPK.'
        );

        // KP-06 — sajikan artefak PDF yang DIBEKUKAN (imutabel per-hash) alih-alih
        // me-render ulang dari data hidup. Hash diambil dari URL bertanda-tangan
        // (middleware `signed`), jadi tidak dapat dipalsukan.
        $disk = Storage::disk(ContractDocumentTte::FROZEN_DISK);
        $requestedHash = (string) $request->query('hash', '');
        $currentHash = ContractDocumentTte::hash($kontrak, $type);

        // 1) Artefak beku untuk hash yang diminta sudah ada → sajikan apa adanya
        //    (persis seperti saat ditandatangani/di-scan).
        if ($requestedHash !== '') {
            $frozen = ContractDocumentTte::frozenPdfPath($kontrak, $type, $requestedHash);
            if ($disk->exists($frozen)) {
                return $disk->response($frozen, $this->frozenFilename($kontrak, $type), [
                    'Content-Type' => 'application/pdf',
                ]);
            }
        }

        // 2) Belum ada artefak → render state saat ini sekali.
        $rendered = $this->renderContractPdf($kontrak, $type);
        $content = $rendered->getContent();
        $isPdf = $rendered->getStatusCode() === 200
            && is_string($content)
            && str_starts_with($content, '%PDF');

        // 3) Bekukan HANYA bila data saat ini masih konsisten dengan hash yang
        //    diminta (atau tanpa hash) — agar artefak mencerminkan state yang
        //    benar-benar ditandatangani, bukan state yang sudah berubah.
        if ($isPdf && ($requestedHash === '' || hash_equals($currentHash, $requestedHash))) {
            $frozen = ContractDocumentTte::frozenPdfPath($kontrak, $type, $currentHash);
            if (! $disk->exists($frozen)) {
                $disk->put($frozen, $content);
            }

            return $disk->response($frozen, $this->frozenFilename($kontrak, $type), [
                'Content-Type' => 'application/pdf',
            ]);
        }

        // 4) Data berubah sejak QR dibuat & snapshot lama tak tersedia, atau render
        //    gagal (mis. Gambar RAB belum diunggah) → kembalikan respons render apa adanya.
        return $rendered;
    }

    private function renderContractPdf(KontrakPengadaan $kontrak, string $type)
    {
        $controller = app(ContractController::class);

        return match ($type) {
            'spk' => $controller->exportSpkPdf($kontrak->getKey()),
            'spmk' => $controller->exportSpmkPdf($kontrak->getKey()),
            'ringkasan_kontrak' => $controller->exportRingkasanKontrakPdf($kontrak->getKey()),
            default => abort(404),
        };
    }

    private function frozenFilename(KontrakPengadaan $kontrak, string $type): string
    {
        $nomor = ContractDocumentTte::numberFor($kontrak, $type) ?: $kontrak->getKey();
        $safe = str_replace(['/', '\\', ' '], ['-', '-', '_'], (string) $nomor);

        return strtoupper($type) . '_' . $safe . '.pdf';
    }
}
