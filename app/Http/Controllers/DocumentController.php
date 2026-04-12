<?php

namespace App\Http\Controllers;

use App\Models\ArsipDokumen;
use App\Models\DokumenNpi;
use App\Models\DokumenSp2d;
use App\Models\DokumenSpp;
use App\Models\DokumenSpm;
use App\Services\DocumentArchiveService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class DocumentController extends Controller
{
    public function __construct(
        private readonly DocumentArchiveService $documentArchiveService
    ) {
    }

    public function upload(Request $request)
    {
        $documentable = $this->resolveDocumentable($request->input('documentable_type'), $request->input('documentable_id'));

        $request->validate([
            'jenis_dokumen' => 'required|string|max:100',
            'file' => 'required|file|max:10240',
            'keterangan' => 'nullable|string|max:1000',
        ]);

        $arsip = $this->documentArchiveService->upload(
            $documentable,
            $request->string('jenis_dokumen')->toString(),
            $request->file('file'),
            [
                'directory' => 'arsip-dokumen/' . class_basename($documentable),
                'uploaded_by' => auth()->id(),
                'keterangan' => $request->input('keterangan'),
            ]
        );

        return back()->with('success', "Dokumen {$arsip->nama_file_asli} berhasil diunggah.");
    }

    public function replace(Request $request, ArsipDokumen $arsip)
    {
        $request->validate([
            'file' => 'required|file|max:10240',
            'keterangan' => 'nullable|string|max:1000',
        ]);

        $newArsip = $this->documentArchiveService->replace(
            $arsip->documentable,
            $arsip->jenis_dokumen,
            $request->file('file'),
            [
                'directory' => 'arsip-dokumen/' . class_basename($arsip->documentable),
                'uploaded_by' => auth()->id(),
                'keterangan' => $request->input('keterangan', $arsip->keterangan),
            ]
        );

        return back()->with('success', "Dokumen {$newArsip->nama_file_asli} berhasil menggantikan file sebelumnya.");
    }

    public function download(ArsipDokumen $arsip)
    {
        return $this->documentArchiveService->download($arsip);
    }

    public function destroy(ArsipDokumen $arsip)
    {
        $nama = $arsip->nama_file_asli;
        $this->documentArchiveService->delete($arsip);

        return back()->with('success', "Dokumen {$nama} berhasil dihapus.");
    }

    public function printSpp(DokumenSpp $spp)
    {
        $spp->load(['tagihan']);

        $pdf = Pdf::loadView('spps.pdf', [
            'spp' => $spp,
            'sppable' => $spp->tagihan,
            'jumlahUang' => $spp->nominal_spp,
            'terbilang' => terbilang_rupiah((float) $spp->nominal_spp),
            'uraianSupplier' => $spp->uraian,
        ]);
        $pdf->setPaper('a4', 'portrait');

        return $pdf->stream('SPP_' . str_replace('/', '-', $spp->nomor_spp) . '.pdf');
    }

    public function printSpm(DokumenSpm $spm)
    {
        $spm->load(['spp.tagihan', 'ppspm']);
        $spp = $spm->spp;

        $pdf = Pdf::loadView('spms.pdf', [
            'spm' => $spm,
            'spp' => $spp,
            'sppable' => $spp?->tagihan,
            'jumlahUang' => $spp?->nominal_spp,
            'terbilang' => terbilang_rupiah((float) ($spp?->nominal_spp ?? 0)),
            'uraianSupplier' => $spp?->uraian,
        ]);
        $pdf->setPaper('a4', 'portrait');

        return $pdf->stream('SPM_' . str_replace('/', '-', $spm->nomor_spm) . '.pdf');
    }

    public function printNpi(DokumenNpi $npi)
    {
        $npi->load(['spm.spp', 'bendaharaPenerimaan']);
        $spm = $npi->spm;
        $spp = $spm?->spp;

        $bendaharaPengeluaran = \App\Models\User::role('Bendahara Pengeluaran')->first();
        $ppk = \App\Models\User::role('PPK')->first();

        $pdf = Pdf::loadView('npis.pdf', [
            'npi' => $npi,
            'spm' => $spm,
            'spp' => $spp,
            'jumlahUang' => $spp?->nominal_spp,
            'terbilang' => terbilang_rupiah((float) ($spp?->nominal_spp ?? 0)),
            'penandatanganPengeluaran' => $bendaharaPengeluaran?->name ?? 'BENDAHARA PENGELUARAN',
            'nipPengeluaran' => '-',
            'penandatanganPenerimaan' => $npi->bendaharaPenerimaan?->name ?? 'BENDAHARA PENERIMAAN',
            'nipPenerimaan' => '-',
            'ppk' => $ppk,
        ]);
        $pdf->setPaper('a4', 'portrait');

        return $pdf->stream('NPI_' . str_replace('/', '-', $npi->nomor_npi) . '.pdf');
    }

    public function printSp2d(DokumenSp2d $sp2d)
    {
        $sp2d->load([
            'npi.spm.spp.tagihan.detailKontrak.kontrakTermin.kontrak.vendor.rekening',
            'npi.spm.spp.tagihan.potonganTagihan.pajak',
            'bendaharaPengeluaran',
            'workflowInstances.approvals.actedByUser'
        ]);

        $npi = $sp2d->npi;
        $spm = $npi?->spm;
        $spp = $spm?->spp;
        $tagihan = $spp?->tagihan;
        $kontrak = $tagihan?->detailKontrak?->kontrakTermin?->kontrak;
        $vendor = $kontrak?->vendor;
        $rekening = $vendor?->rekening?->first();
        
        $nominalSp2d = (float) ($spp?->nominal_spp ?? $tagihan?->total_netto ?? 0);
        $terbilang = terbilang_rupiah($nominalSp2d) . ' Rupiah';

        $approvals = collect($sp2d->workflowInstances->sortByDesc('created_at')->first()?->approvals ?? []);
        $ppkApproval = $approvals->firstWhere('role_code', 'PPK');
        $ppk = $ppkApproval?->actedByUser;

        $pdf = Pdf::loadView('sp2ds.pdf', compact(
            'sp2d', 'npi', 'spm', 'spp', 'tagihan', 'kontrak', 'vendor', 
            'rekening', 'nominalSp2d', 'terbilang', 'ppk'
        ));

        $pdf->setPaper('a4', 'portrait');

        return $pdf->stream('SP2D-BLU-' . str_replace('/', '-', $sp2d->nomor_sp2d) . '.pdf');
    }

    private function resolveDocumentable(string $type, int $id): Model
    {
        $map = [
            'kontrak' => \App\Models\KontrakPengadaan::class,
            'addendum' => \App\Models\KontrakAddendum::class,
            'tagihan' => \App\Models\Tagihan::class,
            'potongan' => \App\Models\PotonganTagihan::class,
            'spp' => DokumenSpp::class,
            'spm' => DokumenSpm::class,
            'npi' => DokumenNpi::class,
            'sp2d' => DokumenSp2d::class,
            'jaminan' => \App\Models\JaminanKontrak::class,
            'detail_kontrak' => \App\Models\DetailKontrak::class,
        ];

        $class = $map[$type] ?? null;

        abort_unless($class && class_exists($class), 404, 'Jenis dokumen tidak dikenali.');

        return $class::findOrFail($id);
    }
}
