<?php

namespace App\Http\Controllers;

use App\Models\ArsipDokumen;
use App\Models\DokumenSp2d;
use App\Models\PotonganTagihan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PenyetoranPajakController extends Controller
{
    /**
     * Menampilkan daftar potongan pajak yang bersumber dari tagihan dengan SP2D final.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        if (! $user->hasRole('Bendahara Pengeluaran')) {
            abort(403, 'Akses ditolak. Modul ini khusus untuk Bendahara Pengeluaran.');
        }

        // Query potongan pajak yang SP2D-nya sudah 'Disetujui Final'
        $query = PotonganTagihan::with([
            'tagihan.spps.spm.npi.sp2d',
            'pajak',
        ])
            ->whereHas('tagihan.spps.spm.npi.sp2d', function ($q) {
                $q->where('status', DokumenSp2d::STATUS_DISETUJUI_FINAL);
            });

        // Search
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('kode_billing', 'like', "%{$search}%")
                    ->orWhere('ntpn', 'like', "%{$search}%")
                    ->orWhere('jenis_potongan', 'like', "%{$search}%")
                    ->orWhereHas('tagihan', function ($sq) use ($search) {
                        $sq->where('nomor_tagihan', 'like', "%{$search}%")
                            ->orWhere('deskripsi', 'like', "%{$search}%");
                    })
                    ->orWhereHas('pajak', function ($sq) use ($search) {
                        $sq->where('nama_pajak', 'like', "%{$search}%");
                    });
            });
        }

        // Filter status setor
        $statusFilter = $request->input('status', 'semua');
        if ($statusFilter === 'belum_billing') {
            $query->whereNull('kode_billing')->whereNull('ntpn');
        } elseif ($statusFilter === 'sudah_billing') {
            $query->whereNotNull('kode_billing')->whereNull('ntpn');
        } elseif ($statusFilter === 'sudah_setor') {
            $query->whereNotNull('kode_billing')->whereNotNull('ntpn');
        }

        $potonganList = $query->latest()->get();

        $summary = [
            'belum_billing' => $potonganList->whereNull('kode_billing')->whereNull('ntpn')->count(),
            'sudah_billing' => $potonganList->whereNotNull('kode_billing')->whereNull('ntpn')->count(),
            'sudah_setor' => $potonganList->whereNotNull('ntpn')->count(),
        ];

        return view('penyetoran_pajak.index', compact('potonganList', 'summary', 'statusFilter', 'search'));
    }

    /**
     * Menampilkan workspace detail penyetoran pajak
     */
    public function show($id)
    {
        $potongan = PotonganTagihan::with([
            'tagihan.spps.spm.npi.sp2d',
            'pajak',
            'akunPotongan',
            'tagihan.arsipDokumen',
            'arsipDokumen.uploader',
        ])->findOrFail($id);

        $tagihan = $potongan->tagihan;
        $spp = $tagihan?->spps?->first();
        $spm = $spp?->spm;
        $npi = $spm?->npi;
        $sp2d = $npi?->sp2d;

        $isSp2dFinal = $sp2d && $sp2d->status === DokumenSp2d::STATUS_DISETUJUI_FINAL;

        $statusSetor = 'Belum Billing';
        if ($potongan->ntpn) {
            $statusSetor = 'Sudah Setor';
        } elseif ($potongan->kode_billing) {
            $statusSetor = 'Sudah Billing';
        }

        return view('penyetoran_pajak.detail', compact(
            'potongan', 'tagihan', 'spp', 'spm', 'npi', 'sp2d', 'isSp2dFinal', 'statusSetor'
        ));
    }

    /**
     * Simpan / Update Kode Billing
     */
    public function storeBilling(Request $request, $id)
    {
        $request->validate([
            'kode_billing' => 'required|string|max:50',
            'file_billing' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $potongan = PotonganTagihan::findOrFail($id);

        if ($potongan->ntpn) {
            return back()->withErrors('Billing tidak dapat diubah karena NTPN sudah terinput.');
        }

        DB::transaction(function () use ($request, $potongan) {
            $potongan->update([
                'kode_billing' => $request->kode_billing,
            ]);

            if ($request->hasFile('file_billing')) {
                $file = $request->file('file_billing');
                $path = $file->store('arsip/pajak', 'local');

                ArsipDokumen::create([
                    'documentable_type' => PotonganTagihan::class,
                    'documentable_id' => $potongan->id,
                    'jenis_dokumen' => 'KODE_BILLING',
                    'nama_file_asli' => $file->getClientOriginalName(),
                    'path_file' => $path,
                    'disk' => 'local',
                    'mime_type' => $file->getClientMimeType(),
                    'ukuran_file' => $file->getSize(),
                    'uploaded_by' => auth()->id(),
                    'uploaded_at' => now(),
                    'keterangan' => 'Dokumen Kode Billing Pajak',
                ]);
            }
        });

        return back()->with('success', 'Kode Billing berhasil disimpan.');
    }

    /**
     * Simpan / Update NTPN
     */
    public function storeNtpn(Request $request, $id)
    {
        $request->validate([
            'ntpn' => 'required|string|max:50',
            'file_bukti_setor' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $potongan = PotonganTagihan::findOrFail($id);

        if (! $potongan->kode_billing) {
            return back()->withErrors('Tidak dapat menginput NTPN sebelum Kode Billing diisi.');
        }

        DB::transaction(function () use ($request, $potongan) {
            $potongan->update([
                'ntpn' => $request->ntpn,
            ]);

            if ($request->hasFile('file_bukti_setor')) {
                $file = $request->file('file_bukti_setor');
                $path = $file->store('arsip/pajak', 'local');

                ArsipDokumen::create([
                    'documentable_type' => PotonganTagihan::class,
                    'documentable_id' => $potongan->id,
                    'jenis_dokumen' => 'BUKTI_SETOR_PAJAK',
                    'nama_file_asli' => $file->getClientOriginalName(),
                    'path_file' => $path,
                    'disk' => 'local',
                    'mime_type' => $file->getClientMimeType(),
                    'ukuran_file' => $file->getSize(),
                    'uploaded_by' => auth()->id(),
                    'uploaded_at' => now(),
                    'keterangan' => 'Dokumen Bukti Setor Pajak (NTPN)',
                ]);
            }
        });

        return back()->with('success', 'NTPN beserta Bukti Setor berhasil disimpan. Status telah menjadi Sudah Setor.');
    }

    /**
     * Cetak ringkasan (opsional / placeholder)
     */
    public function cetak($id)
    {
        $potongan = PotonganTagihan::findOrFail($id);

        // Implementasi cetak ke PDF atau view khusus cetak
        return view('penyetoran_pajak.cetak', compact('potongan'));
    }
}
