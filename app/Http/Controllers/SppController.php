<?php

namespace App\Http\Controllers;

use App\Models\Spp;
use App\Support\PaymentPdfReference;
use Barryvdh\DomPDF\Facade\Pdf;

/**
 * Endpoint SPP yang masih dipakai alur Proses Tagihan terpadu: cetak PDF.
 * SPP/SPM/NPI/SP2D memakai TTE QR otomatis setelah diverifikasi, sehingga
 * tidak ada lagi upload scan TTD manual. Pembuatan/pengajuan draft SPP
 * ditangani DokumenChainService dari halaman Proses Tagihan.
 */
class SppController extends Controller
{
    public function cetakPdf($spp_id)
    {
        require_once app_path('Helpers/TerbilangHelper.php');

        // Pertama, cek menggunakan model alur baru (DokumenSpp)
        $dokumenSpp = \App\Models\DokumenSpp::with([
            'dipaRevisionItem.coa',
            'dipaRevisionItem.dipaRevision.masterDipa',
            'tagihan.pihak.rekening',
            'tagihan.detailKontrak.kontrakTermin.kontrak.vendor.rekening',
            'tagihan.detailPerjaldin',
            'tagihan.detailHonorarium',
            'tagihan.dipa',
            'tagihan.dipaRevisionItem.coa',
            'tagihan.dipaRevisionItem.dipaRevision.masterDipa',
            'tagihan.potonganTagihan.pajak',
            'tagihan.potonganTagihan.akunPotongan',
            'ppkVerifikator.profilable',
            'workflowInstance.approvals',
        ])->find($spp_id);

        if ($dokumenSpp) {
            $spp = $dokumenSpp;
            $sppable = $dokumenSpp->tagihan;
            $jumlahUang = $spp->nominal_spp;
            $uraianSupplier = PaymentPdfReference::uraianForTagihan($sppable, $spp->uraian ?? null);
            $kodeCoa = $spp->dipaRevisionItem?->coa?->kode_mak_lengkap
                ?? $sppable?->dipaRevisionItem?->coa?->kode_mak_lengkap
                ?? $spp->akun_mak
                ?? '-';
            $potonganPajak = collect($sppable?->potonganTagihan ?? [])
                ->filter(fn ($potongan) => $potongan->jenis_potongan !== 'ANGSURAN_UANG_MUKA')
                ->values();
            $jumlahPotonganPajak = $potonganPajak->sum('nominal_potongan');
            $pdfReference = PaymentPdfReference::forTagihan($sppable);
            $dipaInfo = PaymentPdfReference::dipaForSpp($spp);
            $supplierInfo = PaymentPdfReference::supplierForTagihan($sppable, $uraianSupplier);

            $terbilang = terbilang_rupiah($jumlahUang);

            $pdf = Pdf::loadView('spps.pdf', compact(
                'spp',
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
            ));
            $pdf->setPaper('a4', 'portrait');

            return $pdf->stream('SPP-BLU-' . str_replace('/', '-', $spp->nomor_spp) . '.pdf');
        }

        // Jika tidak ditemukan, fallback ke alur legacy (Spp)
        $spp = Spp::with([
            'sppable.pihak.rekening',
            'sppable.detailKontrak.kontrakTermin.kontrak.vendor.rekening',
            'sppable.detailPerjaldin',
            'sppable.detailHonorarium',
            'sppable.dipa',
            'sppable.dipaRevisionItem.dipaRevision.masterDipa',
            'sppable.potonganTagihan.pajak',
            'sppable.potonganTagihan.akunPotongan',
            'dipaRevisionItem.coa',
            'dipaRevisionItem.dipaRevision.masterDipa',
            'tagihan.dipaRevisionItem.coa',
            'tagihan.dipaRevisionItem.dipaRevision.masterDipa',
            'tagihan.pihak.rekening',
            'tagihan.detailKontrak.kontrakTermin.kontrak.vendor.rekening',
            'workflowInstance.approvals',
        ])->findOrFail($spp_id);
        $sppable = $spp->sppable;

        $jumlahUang = $spp->jumlah_uang;
        $uraianSupplier = PaymentPdfReference::uraianForTagihan($sppable, $spp->uraian ?? null);
        $kodeCoa = $spp->dipaRevisionItem?->coa?->kode_mak_lengkap
            ?? $spp->tagihan?->dipaRevisionItem?->coa?->kode_mak_lengkap
            ?? $spp->akun_mak
            ?? '-';
        $potonganPajak = collect($sppable?->potonganTagihan ?? [])
            ->filter(fn ($potongan) => $potongan->jenis_potongan !== 'ANGSURAN_UANG_MUKA')
            ->values();
        $jumlahPotonganPajak = $potonganPajak->sum('nominal_potongan');
        $pdfReference = PaymentPdfReference::forTagihan($sppable);
        $dipaInfo = PaymentPdfReference::dipaForSpp($spp);
        $supplierInfo = PaymentPdfReference::supplierForTagihan($sppable, $uraianSupplier);

        $terbilang = terbilang_rupiah($jumlahUang);

        $pdf = Pdf::loadView('spps.pdf', compact(
            'spp',
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
        ));
        $pdf->setPaper('a4', 'portrait');

        return $pdf->stream('SPP-BLU-' . str_replace('/', '-', $spp->nomor_spp) . '.pdf');
    }
}
