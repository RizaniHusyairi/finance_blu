<?php

namespace App\Http\Controllers;

use App\Models\ArsipDokumen;
use App\Models\DetailKontrak;
use App\Models\DokumenNpi;
use App\Models\DokumenSp2d;
use App\Models\DokumenSpm;
use App\Models\DokumenSpp;
use App\Models\JaminanKontrak;
use App\Models\KontrakAddendum;
use App\Models\KontrakPengadaan;
use App\Models\PotonganTagihan;
use App\Models\Tagihan;
use App\Models\User;
use App\Services\DocumentArchiveService;
use App\Support\PaymentPdfReference;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class DocumentController extends Controller
{
    public function __construct(
        private readonly DocumentArchiveService $documentArchiveService
    ) {}

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
                'directory' => 'arsip-dokumen/'.class_basename($documentable),
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
                'directory' => 'arsip-dokumen/'.class_basename($arsip->documentable),
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

    /**
     * Unduh arsip keuangan sensitif (bukti setor pajak & bukti transfer SP2D)
     * dari disk privat. Bendahara Pengeluaran / Super Admin boleh mengunduh
     * seluruh arsip sensitif; verifier SP2D Kontrak (PPK, PPSPM, Kepala
     * Subbagian Keuangan dan Tata Usaha, Koordinator Keuangan) hanya boleh
     * mengunduh BUKTI_TRANSFER_SP2D agar tautan di halaman verifikasi resolve.
     */
    public function downloadArsipSensitif(ArsipDokumen $arsip)
    {
        $user = auth()->user();

        $jenisSensitif = ['KODE_BILLING', 'BUKTI_SETOR_PAJAK', 'BPPU', 'BUKTI_TRANSFER_SP2D'];
        abort_unless($user && in_array($arsip->jenis_dokumen, $jenisSensitif, true), 404, 'Dokumen tidak ditemukan.');

        $canDownloadAll = $user->hasRole(['Bendahara Pengeluaran', 'Super Admin']);
        $canDownloadBuktiTransfer = $arsip->jenis_dokumen === 'BUKTI_TRANSFER_SP2D'
            && $user->hasRole(['PPK', 'PPSPM', 'Kepala Subbagian Keuangan dan Tata Usaha', 'Koordinator Keuangan']);

        abort_unless($canDownloadAll || $canDownloadBuktiTransfer, 403, 'Anda tidak berwenang mengunduh dokumen ini.');

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
        $spp->load(['tagihan.detailKontrak.kontrakTermin.kontrak.vendor.rekening']);
        $tagihan = $spp->tagihan;
        $uraianSupplier = PaymentPdfReference::uraianForTagihan($tagihan, $spp->uraian ?? null);

        $pdf = Pdf::loadView('spps.pdf', [
            'spp' => $spp,
            'sppable' => $tagihan,
            'jumlahUang' => $spp->nominal_spp,
            'terbilang' => terbilang_rupiah((float) $spp->nominal_spp),
            'uraianSupplier' => $uraianSupplier,
        ]);
        $pdf->setPaper('a4', 'portrait');

        return $pdf->stream('SPP_'.str_replace('/', '-', $spp->nomor_spp).'.pdf');
    }

    public function printSpm(DokumenSpm $spm)
    {
        $spm->load([
            'spp.dipaRevisionItem.coa',
            'spp.tagihan.detailKontrak.kontrakTermin.kontrak.vendor.rekening',
            'spp.tagihan.dipaRevisionItem.coa',
            'spp.tagihan.potonganTagihan.pajak',
            'spp.tagihan.potonganTagihan.akunPotongan',
            'ppspm',
        ]);
        $spp = $spm->spp;
        $tagihan = $spp?->tagihan;
        $uraianSupplier = PaymentPdfReference::uraianForTagihan($tagihan, $spp?->uraian ?? null);
        $kodeCoa = $spp?->dipaRevisionItem?->coa?->kode_mak_lengkap
            ?? $tagihan?->dipaRevisionItem?->coa?->kode_mak_lengkap
            ?? $spp?->akun_mak
            ?? '-';
        $potonganPajak = collect($tagihan?->potonganTagihan ?? [])
            ->filter(fn ($potongan) => $potongan->jenis_potongan !== 'ANGSURAN_UANG_MUKA')
            ->values();
        $jumlahPotonganPajak = $potonganPajak->sum('nominal_potongan');

        $pdf = Pdf::loadView('spms.pdf', [
            'spm' => $spm,
            'spp' => $spp,
            'sppable' => $tagihan,
            'jumlahUang' => $spp?->nominal_spp,
            'terbilang' => terbilang_rupiah((float) ($spp?->nominal_spp ?? 0)),
            'uraianSupplier' => $uraianSupplier,
            'kodeCoa' => $kodeCoa,
            'potonganPajak' => $potonganPajak,
            'jumlahPotonganPajak' => $jumlahPotonganPajak,
        ]);
        $pdf->setPaper('a4', 'portrait');

        return $pdf->stream('SPM_'.str_replace('/', '-', $spm->nomor_spm).'.pdf');
    }

    public function printNpi(DokumenNpi $npi)
    {
        $npi->load(['spm.spp', 'bendaharaPenerimaan']);
        $spm = $npi->spm;
        $spp = $spm?->spp;

        $bendaharaPengeluaran = User::role('Bendahara Pengeluaran')->first();
        $ppk = User::role('PPK')->first();

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

        return $pdf->stream('NPI_'.str_replace('/', '-', $npi->nomor_npi).'.pdf');
    }

    public function printSp2d(DokumenSp2d $sp2d)
    {
        $sp2d->load([
            'npi.spm.spp.tagihan.detailKontrak.kontrakTermin.kontrak.vendor.rekening',
            'npi.spm.spp.tagihan.dipaRevisionItem.coa',
            'npi.spm.spp.tagihan.pihak',
            'npi.spm.spp.tagihan.potonganTagihan.pajak',
            'npi.spm.spp.dipaRevisionItem.coa',
            'bendaharaPengeluaran.profilable',
            'workflowInstance.approvals',
            'workflowInstances.approvals.actedByUser',
        ]);

        $npi = $sp2d->npi;
        $spm = $npi?->spm;
        $spp = $spm?->spp;
        $tagihan = $spp?->tagihan;
        $kontrak = $tagihan?->detailKontrak?->kontrakTermin?->kontrak;
        $vendor = $kontrak?->vendor;
        $rekening = $vendor?->rekening?->first();

        $nominalSp2d = (float) ($spp?->nominal_spp ?? $tagihan?->total_netto ?? 0);
        $terbilang = terbilang_rupiah($nominalSp2d);

        // Klasifikasi belanja (nama akun COA) — dari item DIPA tagihan/SPP.
        $coa = $tagihan?->dipaRevisionItem?->coa ?? $spp?->dipaRevisionItem?->coa;
        $klasifikasi = $coa?->jenis_akun ?: ($coa?->nama_akun ?? 'Belanja Barang');

        // Kategori cara bayar (SP2D BLU - TRF, dll).
        $caraBayar = $spp?->kategori_pembayaran ?? $spm?->cara_bayar ?? 'SP2D BLU - TRF';
        $tahunAnggaran = $spm?->tahun_anggaran ?? $spp?->tahun_anggaran ?? date('Y');

        // Tentukan penerima sesuai tipe tagihan.
        $tipe = $tagihan?->tipe_tagihan;
        if ($tipe === 'KONTRAK') {
            $penerimaNama = $vendor?->nama_pihak ?? $tagihan?->pihak?->nama_pihak ?? '-';
            $penerimaAlamat = $vendor?->alamat ?? $tagihan?->pihak?->alamat ?? '-';
            $penerimaNpwp = $vendor?->npwp ?? $tagihan?->pihak?->npwp ?? '-';
            $penerimaBank = $rekening?->nama_bank ?? '-';
            $penerimaNoRek = $rekening?->nomor_rekening ?? '-';
            $penerimaNamaRek = $rekening?->nama_rekening ?? $penerimaNama;
        } else {
            // PERJALDIN & HONORARIUM: banyak penerima → "Terlampir".
            $penerimaNama = $tagihan?->pihak?->nama_pihak ?? 'PARA PENERIMA';
            $penerimaAlamat = $tagihan?->pihak?->alamat ?? 'Terlampir';
            $penerimaNpwp = 'Terlampir';
            $penerimaBank = 'Terlampir';
            $penerimaNoRek = 'Terlampir';
            $penerimaNamaRek = 'Terlampir';
        }

        $uraian = $tagihan?->deskripsi ?? $npi?->catatan ?? $kontrak?->nama_pekerjaan ?? '-';

        $approvals = collect($sp2d->workflowInstances->sortByDesc('created_at')->first()?->approvals ?? []);
        $ppkApproval = $approvals->firstWhere('role_code', 'PPK');
        $ppk = $ppkApproval?->actedByUser ?: User::role('PPK')->with('profilable')->first();
        $ppkNip = $ppk?->pegawai?->nip ?? '-';

        $bendaharaPengeluaran = $sp2d->bendaharaPengeluaran ?: User::role('Bendahara Pengeluaran')->with('profilable')->first();
        $bendaharaNip = $bendaharaPengeluaran?->pegawai?->nip ?? '-';

        $pdf = Pdf::loadView('sp2ds.pdf', compact(
            'sp2d', 'npi', 'spm', 'spp', 'tagihan', 'kontrak', 'vendor',
            'rekening', 'nominalSp2d', 'terbilang', 'ppk', 'ppkNip',
            'bendaharaPengeluaran', 'bendaharaNip',
            'klasifikasi', 'caraBayar', 'tahunAnggaran',
            'penerimaNama', 'penerimaAlamat', 'penerimaNpwp', 'penerimaBank', 'penerimaNoRek', 'penerimaNamaRek',
            'uraian'
        ));

        $pdf->setPaper('a4', 'portrait');

        return $pdf->stream('SP2D-BLU-'.str_replace('/', '-', $sp2d->nomor_sp2d).'.pdf');
    }

    private function resolveDocumentable(string $type, int $id): Model
    {
        $map = [
            'kontrak' => KontrakPengadaan::class,
            'addendum' => KontrakAddendum::class,
            'tagihan' => Tagihan::class,
            'potongan' => PotonganTagihan::class,
            'spp' => DokumenSpp::class,
            'spm' => DokumenSpm::class,
            'npi' => DokumenNpi::class,
            'sp2d' => DokumenSp2d::class,
            'jaminan' => JaminanKontrak::class,
            'detail_kontrak' => DetailKontrak::class,
        ];

        $class = $map[$type] ?? null;

        abort_unless($class && class_exists($class), 404, 'Jenis dokumen tidak dikenali.');

        return $class::findOrFail($id);
    }
}
