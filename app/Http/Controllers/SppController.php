<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Spp;
use App\Models\Perjaldin;
use App\Models\Transaction;
use App\Models\Contract;
use App\Models\Tagihan;
use App\Models\DokumenSpp;
use App\Models\LogStatusDokumen;
use App\Models\MasterTarifPajak;
use App\Models\PotonganTagihan;
use App\Models\User;
use App\Notifications\WorkflowNotification;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Notification;

class SppController extends Controller
{
    /**
     * Menampilkan daftar Perjaldin yang sudah siap dibikinkan SPP
     */
    public function perjaldinIndex()
    {
        $perjaldins = Tagihan::where('tipe_tagihan', 'PERJALDIN')
            ->whereIn('status', ['DISETUJUI_PPK', 'PROSES_SPP', 'SPP_TERBIT'])
            ->with(['detailPerjaldin', 'spps', 'logs'])
            ->latest()
            ->get();

        return view('spps.perjaldin_index', compact('perjaldins'));
    }

    public function detailPerjaldin($perjaldin_id)
    {
        $perjaldin = Perjaldin::with([
            'pejabats.pegawai',
            'spps.dipaRevisionItem.coa',
            'dipa.activeRevision.items.coa',
        ])->findOrFail($perjaldin_id);
        
        // Menghitung total tiap kategori
        $kategoriTotals = [
            'Tiket' => 0,
            'Transport' => 0,
            'Penginapan' => 0,
            'Uang Harian' => 0,
            'Uang Representasi' => 0,
        ];

        foreach($perjaldin->pejabats as $p) {
            $kategoriTotals['Tiket'] += (float) $p->biaya_tiket;
            $kategoriTotals['Transport'] += (float) $p->biaya_transport;
            $kategoriTotals['Penginapan'] += (float) $p->biaya_penginapan;
            $kategoriTotals['Uang Harian'] += $p->uang_harian;
            $kategoriTotals['Uang Representasi'] += $p->uang_representasi;
        }

        $budgets = $this->buildBudgetOptions($perjaldin->dipa);

        return view('spps.detail_perjaldin', compact('perjaldin', 'kategoriTotals', 'budgets'));
    }

    public function storePerjaldin(Request $request, $perjaldin_id)
    {
        $request->validate([
            'kategori_biaya' => 'required|string',
            'jumlah_uang' => 'required|numeric',
            'nomor_spp' => 'required|string',
            'tanggal_spp' => 'required|date',
            'tahun_anggaran' => 'required|string',
            'nomor_dipa' => 'required|string',
            'tanggal_dipa' => 'required|date',
            'akun_mak' => 'required|string',
            'penandatangan_nama' => 'required|string',
            'penandatangan_nip' => 'required|string',
        ]);

        $perjaldin = Perjaldin::findOrFail($perjaldin_id);

        // Standard Uraian template based on PDF
        $uraianText = 'Belanja Barang Perjalanan Dinas Pegawai - ' . $request->kategori_biaya;

        DB::transaction(function () use ($request, $perjaldin, $uraianText) {
            // Polymorphic Create Spp untuk Kategori Tertentu
            $perjaldin->spps()->updateOrCreate(
                [
                    'kategori_biaya' => $request->kategori_biaya,
                ],
                [
                    'jumlah_uang' => $request->jumlah_uang,
                    'uraian' => $uraianText,
                    'nomor_spp' => $request->nomor_spp,
                    'tanggal_spp' => $request->tanggal_spp,
                    'tahun_anggaran' => $request->tahun_anggaran,
                    'nomor_dipa' => $request->nomor_dipa,
                    'tanggal_dipa' => $request->tanggal_dipa,
                    'akun_mak' => $request->akun_mak,
                    'penandatangan_nama' => $request->penandatangan_nama,
                    'penandatangan_nip' => $request->penandatangan_nip,
                    // Default values matching template
                    'jenis_tagihan' => 'NON REMUNERASI',
                    'jatuh_tempo' => 'Segera',
                    'cara_bayar' => 'SP2D BLU - TRF',
                    'status_spp' => 'Menunggu Verifikasi',
                    'catatan_revisi' => null,
                ]
            );

            // Update status perjaldin
            $perjaldin->update(['status' => 'Proses SPP']);
        });

        // Beritahu PPK ada SPP baru/diubah
        $ppks = \App\Models\User::role('PPK')->get();
        \Illuminate\Support\Facades\Notification::send($ppks, new \App\Notifications\WorkflowNotification([
            'title' => 'Pengajuan SPP',
            'message' => "Surat Permintaan Pembayaran ({$request->kategori_biaya}) menunggu verifikasi Anda.",
            'url' => route('verifikasi-ppk.spp.index'),
            'icon' => 'receipt_long',
            'color' => 'primary'
        ]));

        return redirect()->route('spps.perjaldin.detail', $perjaldin_id)->with('success', 'SPP '.$request->kategori_biaya.' berhasil diterbitkan. Silakan cetak PDF.');
    }

    // ===================================================================
    // SPP HONOR
    // ===================================================================

    public function honorIndex()
    {
        $honorariums = Tagihan::where('tipe_tagihan', 'HONORARIUM')
            ->whereIn('status', ['DISETUJUI_PPK', 'PROSES_SPP', 'SPP_TERBIT'])
            ->with(['detailHonorarium', 'spps', 'logs'])
            ->latest()
            ->get();

        return view('spps.honor_index', compact('honorariums'));
    }

    public function detailHonor($honorarium_id)
    {
        $honorarium = Transaction::with([
                'budget.activeRevision.items.coa',
                'honorariumItems',
                'spps.dipaRevisionItem.coa',
            ])
            ->where('tipe_tagihan', 'HONORARIUM')
            ->findOrFail($honorarium_id);

        $budgets = $this->buildBudgetOptions($honorarium->budget);

        return view('spps.detail_honor', compact('honorarium', 'budgets'));
    }

    public function storeHonor(Request $request, $honorarium_id)
    {
        $request->validate([
            'jumlah_uang' => 'required|numeric',
            'nomor_spp' => 'required|string',
            'tanggal_spp' => 'required|date',
            'tahun_anggaran' => 'required|string',
            'nomor_dipa' => 'required|string',
            'tanggal_dipa' => 'required|date',
            'akun_mak' => 'required|string',
            'penandatangan_nama' => 'required|string',
            'penandatangan_nip' => 'required|string',
        ]);

        $honorarium = Transaction::where('tipe_tagihan', 'HONORARIUM')->findOrFail($honorarium_id);

        $uraianText = 'Pembayaran Belanja Barang - ' . $honorarium->description;

        DB::transaction(function () use ($request, $honorarium, $uraianText) {
            $honorarium->spps()->updateOrCreate(
                [
                    'kategori_biaya' => 'Honorarium',
                ],
                [
                    'jumlah_uang' => $request->jumlah_uang,
                    'uraian' => $uraianText,
                    'nomor_spp' => $request->nomor_spp,
                    'tanggal_spp' => $request->tanggal_spp,
                    'tahun_anggaran' => $request->tahun_anggaran,
                    'nomor_dipa' => $request->nomor_dipa,
                    'tanggal_dipa' => $request->tanggal_dipa,
                    'no_kontrak' => $honorarium->bast_number ?? null,
                    'tgl_kontrak' => $honorarium->bast_date ?? null,
                    'akun_mak' => $request->akun_mak,
                    'penandatangan_nama' => $request->penandatangan_nama,
                    'penandatangan_nip' => $request->penandatangan_nip,
                    'jenis_tagihan' => 'NON REMUNERASI',
                    'jatuh_tempo' => 'Segera',
                    'cara_bayar' => 'SP2D BLU - TRF',
                    'status_spp' => 'Menunggu Verifikasi',
                    'catatan_revisi' => null,
                ]
            );

            if ($honorarium->status === 'Disetujui PPK') {
                $honorarium->update(['status' => 'Proses SPP']);
            }
        });

        // Notify PPK
        $ppks = \App\Models\User::role('PPK')->get();
        \Illuminate\Support\Facades\Notification::send($ppks, new \App\Notifications\WorkflowNotification([
            'title' => 'SPP Honor Baru',
            'message' => "SPP Honorarium ({$honorarium->transaction_number}) menunggu verifikasi Anda.",
            'url' => route('verifikasi-ppk.spp.index'),
            'icon' => 'receipt_long',
            'color' => 'primary'
        ]));

        return redirect()->route('spps.honor.detail', $honorarium_id)->with('success', 'SPP Honorarium berhasil diterbitkan.');
    }

    // ===================================================================
    // SPP KONTRAK
    // ===================================================================

    public function kontrakIndex()
    {
        $contracts = Tagihan::where('tipe_tagihan', 'KONTRAK')
            ->whereIn('status', ['READY_FOR_SPP', 'PROSES_SPP', 'SPP_TERBIT'])
            ->with(['detailKontrak.kontrakTermin.kontrak.vendor', 'spps', 'logs'])
            ->latest()
            ->get();

        return view('spps.kontrak_index', compact('contracts'));
    }

    public function detailKontrak($contract_id)
    {
        $tagihan = Tagihan::where('tipe_tagihan', 'KONTRAK')
            ->with([
                'detailKontrak.kontrakTermin.kontrak.vendor.rekening',
                'detailKontrak.kontrakTermin.kontrak.dipa.activeRevision.items.coa',
                'spps.dipaRevisionItem.coa',
            ])
            ->findOrFail($contract_id);

        $termin = $tagihan->detailKontrak?->kontrakTermin;
        $kontrak = $termin?->kontrak;
        $budgetItems = collect(optional(optional($kontrak?->dipa)->activeRevision)->items)
            ->filter(fn ($item) => $item->status_aktif)
            ->values();
        $sppModel = $tagihan->spps->sortByDesc('created_at')->first();
        $ppkUsers = User::role('PPK')->orderBy('name')->get();
        $kasubbagUser = User::role('Kepala Subbagian Keuangan dan Tata Usaha')->orderBy('name')->first();
        $pajaks = MasterTarifPajak::orderBy('jenis_pajak')->get();

        return view('spps.detail_kontrak', compact('tagihan', 'termin', 'kontrak', 'budgetItems', 'sppModel', 'ppkUsers', 'kasubbagUser', 'pajaks'));
    }

    public function storeKontrak(Request $request, $contract_id)
    {
        $tagihan = Tagihan::where('tipe_tagihan', 'KONTRAK')
            ->with(['detailKontrak.kontrakTermin.kontrak.dipa.activeRevision.items'])
            ->findOrFail($contract_id);

        $existingSpp = $tagihan->spps()->latest()->first();

        $request->validate([
            'jumlah_uang' => 'required|numeric',
            'nomor_spp' => 'required|string',
            'tanggal_spp' => 'required|date',
            'ppk_verifikator_id' => 'required|exists:users,id',
            'pajak' => 'nullable|array',
            'pajak.*.id' => 'required|exists:master_tarif_pajak,id',
            'pajak.*.dpp' => 'required|numeric|min:0',
            'pajak.*.nominal' => 'required|numeric|min:0',
            'file_faktur_pajak' => 'nullable|file|mimes:pdf|max:5120',
            'file_ebilling' => 'nullable|file|mimes:pdf|max:5120',
            'nomor_spp' => [
                'required',
                'string',
                Rule::unique('dokumen_spp', 'nomor_spp')->ignore($existingSpp?->id),
            ],
        ]);

        $kontrak = $tagihan->detailKontrak?->kontrakTermin?->kontrak;
        $defaultBudgetItemId = $existingSpp?->dipa_revision_item_id
            ?? collect(optional(optional($kontrak?->dipa)->activeRevision)->items)
                ->firstWhere('status_aktif', true)?->id;

        if (!$defaultBudgetItemId) {
            return back()->withInput()->withErrors([
                'error' => 'Kontrak ini belum memiliki item DIPA aktif yang dapat dipakai untuk dokumen SPP.',
            ]);
        }

        DB::transaction(function () use ($request, $tagihan, $existingSpp, $defaultBudgetItemId) {
            $potonganAngsuranUangMuka = (float) $tagihan->potonganTagihan()
                ->where('jenis_potongan', 'ANGSURAN_UANG_MUKA')
                ->sum('nominal_potongan');

            $tagihan->potonganTagihan()
                ->where('jenis_potongan', 'PAJAK')
                ->get()
                ->each(function ($potongan) {
                    $potongan->arsipDokumen()->delete();
                    $potongan->delete();
                });

            $totalPotonganPajak = 0;
            foreach ($request->input('pajak', []) as $pj) {
                $pajakModel = MasterTarifPajak::find($pj['id']);
                $nominal = (float) ($pj['nominal'] ?? 0);
                $totalPotonganPajak += $nominal;

                PotonganTagihan::create([
                    'tagihan_id' => $tagihan->id,
                    'pajak_id' => $pj['id'],
                    'jenis_potongan' => 'PAJAK',
                    'deskripsi' => 'Potongan pajak pada saat pembuatan dokumen SPP.',
                    'dpp' => (float) ($pj['dpp'] ?? 0),
                    'persentase_tarif_snapshot' => $pajakModel?->persentase,
                    'nama_pajak_snapshot' => $pajakModel?->jenis_pajak,
                    'nominal_potongan' => $nominal,
                ]);
            }

            $totalPotongan = $potonganAngsuranUangMuka + $totalPotonganPajak;
            $totalNetto = max(0, (float) $tagihan->total_bruto - $totalPotongan);

            $tagihan->update([
                'total_potongan' => $totalPotongan,
                'total_netto' => $totalNetto,
            ]);

            $spp = DokumenSpp::updateOrCreate(
                ['id' => $existingSpp?->id],
                [
                    'tagihan_id' => $tagihan->id,
                    'dipa_revision_item_id' => $defaultBudgetItemId,
                    'kategori_pembayaran' => 'SP2D BLU - TRF',
                    'jenis_tagihan' => 'NON REMUNERASI',
                    'nominal_spp' => $totalNetto,
                    'nomor_spp' => $request->nomor_spp,
                    'tanggal_spp' => $request->tanggal_spp,
                    'status' => 'Menunggu Verifikasi',
                    'dibuat_oleh_id' => auth()->id(),
                ]
            );

            if ($request->hasFile('file_faktur_pajak') && $tagihan->detailKontrak) {
                $spp->loadMissing('tagihan.detailKontrak');

                $tagihan->detailKontrak->arsipDokumen()
                    ->where('jenis_dokumen', 'FAKTUR_PAJAK')
                    ->delete();

                $tagihan->detailKontrak->arsipDokumen()->create([
                    'jenis_dokumen' => 'FAKTUR_PAJAK',
                    'nama_file_asli' => $request->file('file_faktur_pajak')->getClientOriginalName(),
                    'path_file' => $request->file('file_faktur_pajak')->store('tagihan/pajak', 'public'),
                    'disk' => 'public',
                    'mime_type' => $request->file('file_faktur_pajak')->getMimeType(),
                    'ukuran_file' => $request->file('file_faktur_pajak')->getSize(),
                    'uploaded_by' => auth()->id(),
                    'uploaded_at' => now(),
                    'is_active' => true,
                ]);
            }

            if ($request->hasFile('file_ebilling')) {
                $spp->arsipDokumen()
                    ->where('jenis_dokumen', 'E_BILLING')
                    ->delete();

                $spp->arsipDokumen()->create([
                    'jenis_dokumen' => 'E_BILLING',
                    'nama_file_asli' => $request->file('file_ebilling')->getClientOriginalName(),
                    'path_file' => $request->file('file_ebilling')->store('spp/ebilling', 'public'),
                    'disk' => 'public',
                    'mime_type' => $request->file('file_ebilling')->getMimeType(),
                    'ukuran_file' => $request->file('file_ebilling')->getSize(),
                    'uploaded_by' => auth()->id(),
                    'uploaded_at' => now(),
                    'is_active' => true,
                ]);
            }

            $statusSebelumnya = $tagihan->status;
            $tagihan->update(['status' => 'PROSES_SPP']);

            LogStatusDokumen::create([
                'dokumen_type' => Tagihan::class,
                'dokumen_id' => $tagihan->id,
                'user_id' => auth()->id(),
                'role_saat_itu' => auth()->user()?->getRoleNames()->first() ?? 'Operator BLU',
                'status_sebelumnya' => $statusSebelumnya,
                'status_baru' => 'PROSES_SPP',
                'aksi' => $existingSpp ? 'UPDATE_SPP' : 'CREATE_SPP',
                'catatan' => 'Dokumen SPP kontrak dibuat/diperbarui oleh Operator BLU. Verifikator PPK dipilih: ' . optional(User::find($request->ppk_verifikator_id))->name,
                'ip_address' => request()->ip(),
            ]);
        });

        $selectedPpk = User::role('PPK')->find($request->ppk_verifikator_id);
        if ($selectedPpk) {
            Notification::send($selectedPpk, new WorkflowNotification([
                'title' => 'SPP Kontrak Baru',
                'message' => "SPP Kontrak ({$tagihan->nomor_tagihan}) menunggu verifikasi Anda.",
                'url' => route('verifikasi-ppk.spp.index'),
                'icon' => 'receipt_long',
                'color' => 'primary',
            ]));
        }

        return redirect()->route('spps.kontrak.detail', $tagihan->id)->with('success', 'SPP kontrak berhasil diterbitkan.');
    }

    // ===================================================================
    // CETAK PDF
    // ===================================================================

    public function cetakPdf($spp_id)
    {
        $spp = Spp::with('sppable')->findOrFail($spp_id);
        $sppable = $spp->sppable;
        
        $jumlahUang = $spp->jumlah_uang;
        $uraianSupplier = $spp->uraian;
        
        // Buat logic terbilang menggunakan helper rupiah
        $terbilang = terbilang_rupiah($jumlahUang);

        $pdf = Pdf::loadView('spps.pdf', compact('spp', 'sppable', 'jumlahUang', 'terbilang', 'uraianSupplier'));
        $pdf->setPaper('a4', 'portrait');

        return $pdf->stream('SPP-BLU-' . str_replace('/', '-', $spp->nomor_spp) . '.pdf');
    }

    private function buildBudgetOptions($dipa)
    {
        return collect(optional(optional($dipa)->activeRevision)->items)
            ->filter(fn ($item) => $item->status_aktif && $item->coa)
            ->map(function ($item) {
                return (object) [
                    'id' => $item->id,
                    'coa' => $item->coa->kode_mak_lengkap,
                    'description' => $item->coa->nama_akun,
                ];
            })
            ->values();
    }
}
