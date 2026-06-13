<?php

namespace App\Http\Controllers;

use App\Models\DokumenSpm;
use App\Support\PaymentPdfReference;

/**
 * Endpoint SPM yang masih dipakai alur Proses Tagihan terpadu: cetak PDF.
 * Pembuatan/pengajuan draft SPM kini ditangani DokumenChainService.
 */
class SpmController extends Controller
{
    public function cetakPdfSpm($spm_id)
    {
        require_once app_path('Helpers/TerbilangHelper.php');

        $spm = DokumenSpm::with([
            'spp.dipaRevisionItem.dipaRevision.masterDipa',
            'spp.dipaRevisionItem.coa',
            'spp.tagihan.pihak.rekening',
            'spp.tagihan.detailKontrak.kontrakTermin.kontrak.vendor.rekening',
            'spp.tagihan.detailPerjaldin',
            'spp.tagihan.detailHonorarium',
            'spp.tagihan.dipa',
            'spp.tagihan.dipaRevisionItem.coa',
            'spp.tagihan.dipaRevisionItem.dipaRevision.masterDipa',
            'spp.tagihan.potonganTagihan.pajak',
            'spp.tagihan.potonganTagihan.akunPotongan',
            'ppspm.profilable',
            'workflowInstance.approvals',
        ])->findOrFail($spm_id);
        $spp = $spm->spp;
        $sppable = $spp?->tagihan;
        $jumlahUang = (float) ($spp?->nominal_spp ?? 0);
        $uraianSupplier = PaymentPdfReference::uraianForTagihan($sppable, $spp?->uraian ?? 'Belanja Perjalanan Dinas');
        $kodeCoa = $spp?->dipaRevisionItem?->coa?->kode_mak_lengkap
            ?? $sppable?->dipaRevisionItem?->coa?->kode_mak_lengkap
            ?? $spp?->akun_mak
            ?? '-';
        $potonganPajak = collect($sppable?->potonganTagihan ?? [])
            ->filter(fn ($potongan) => $potongan->jenis_potongan !== 'ANGSURAN_UANG_MUKA')
            ->values();
        $jumlahPotonganPajak = $potonganPajak->sum('nominal_potongan');
        $terbilang = \terbilang_rupiah($jumlahUang);
        $pdfReference = PaymentPdfReference::forTagihan($spp?->tagihan);
        $dipaInfo = PaymentPdfReference::dipaForSpp($spp);
        $supplierInfo = PaymentPdfReference::supplierForTagihan($spp?->tagihan, $uraianSupplier);

        if (! $spm->nomor_spm) {
            $spm->nomor_spm = 'SPM-BLU/APTP-' . date('Y') . '/DRAFT';
        }

        if (! $spm->tanggal_spm) {
            $spm->tanggal_spm = now()->toDateString();
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
            'spms.pdf',
            compact(
                'spp',
                'spm',
                'sppable',
                'jumlahUang',
                'terbilang',
                'uraianSupplier',
                'kodeCoa',
                'potonganPajak',
                'jumlahPotonganPajak',
                'pdfReference',
                'dipaInfo',
                'supplierInfo'
            )
        );
        $pdf->setPaper('a4', 'portrait');

        return $pdf->stream('SPM-BLU-' . str_replace('/', '-', $spm->nomor_spm) . '.pdf');
    }
}
