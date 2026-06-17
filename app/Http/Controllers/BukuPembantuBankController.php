<?php

namespace App\Http\Controllers;

use App\Models\RekeningBank;
use App\Services\Pembukuan\PembukuanService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BukuPembantuBankController extends Controller
{
    public function __construct(
        private readonly PembukuanService $pembukuanService
    ) {
    }

    public function index(Request $request)
    {
        return view('pembukuan.bank.index', $this->pembukuanService->buildBankIndexData(
            $request->only(['start_date', 'end_date', 'rekening_bank_id', 'search'])
        ));
    }

    public function show(RekeningBank $rekening, Request $request)
    {
        return view('pembukuan.bank.show', $this->pembukuanService->buildBankRekeningDetail(
            $rekening,
            $request->only(['start_date', 'end_date', 'arah_mutasi', 'status_rekonsiliasi'])
        ));
    }

    public function mutasi(Request $request)
    {
        return view('pembukuan.bank.mutasi', $this->pembukuanService->buildBankMutasiData(
            $request->only(['start_date', 'end_date', 'rekening_bank_id', 'arah_mutasi', 'status_rekonsiliasi'])
        ));
    }

    /** Upload file rekening koran (disimpan sebagai lampiran/bukti) untuk satu rekening. */
    public function uploadKoran(RekeningBank $rekening, Request $request)
    {
        $data = $request->validate([
            'file_koran' => ['required', 'file', 'mimes:pdf,xls,xlsx,csv,jpg,jpeg,png', 'max:10240'],
            'periode_awal' => ['nullable', 'date'],
            'periode_akhir' => ['nullable', 'date', 'after_or_equal:periode_awal'],
        ]);

        $this->pembukuanService->uploadKoran($rekening, $request->file('file_koran'), $data, (int) Auth::id());

        return back()->with('success', 'File rekening koran berhasil diunggah.');
    }

    /** Tambah satu baris rekening koran manual untuk rekening ini. */
    public function storeKoranLine(RekeningBank $rekening, Request $request)
    {
        $data = $request->validate([
            'tanggal_transaksi' => ['required', 'date'],
            'deskripsi' => ['nullable', 'string', 'max:255'],
            'nomor_referensi_bank' => ['nullable', 'string', 'max:100'],
            'arah_mutasi' => ['required', 'in:MASUK,KELUAR'],
            'nominal' => ['required', 'numeric', 'min:0.01'],
            'saldo' => ['nullable', 'numeric'],
        ]);

        $this->pembukuanService->addKoranLine($rekening, $data);

        return back()->with('success', 'Baris rekening koran ditambahkan.');
    }

    /** Hapus satu baris rekening koran (hanya yang belum tersanding). */
    public function destroyKoranLine(RekeningBank $rekening, int $mutasi)
    {
        $this->pembukuanService->deleteKoranLine($rekening, $mutasi);

        return back()->with('success', 'Baris rekening koran dihapus.');
    }

    /** Pencocokan otomatis BKU ↔ rekening koran untuk rekening ini. */
    public function autoReconcile(RekeningBank $rekening, Request $request)
    {
        $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
        ]);

        $hasil = $this->pembukuanService->autoReconcileBank(
            array_merge(['rekening_bank_id' => $rekening->id], $request->only(['start_date', 'end_date'])),
            (int) Auth::id(),
        );

        return back()->with('success', "Pencocokan otomatis selesai: {$hasil['matched']} pasangan cocok, {$hasil['sisa_mutasi']} baris koran belum cocok.");
    }

    /** Pencocokan manual satu baris koran dengan satu BKU. */
    public function manualMatch(RekeningBank $rekening, Request $request)
    {
        $data = $request->validate([
            'detail_mutasi_bank_id' => ['required', 'integer', 'exists:detail_mutasi_bank,id'],
            'bku_id' => ['required', 'integer', 'exists:buku_kas_umum,id'],
            'catatan' => ['nullable', 'string', 'max:255'],
        ]);

        $this->pembukuanService->manualMatchBank(
            (int) $data['detail_mutasi_bank_id'],
            (int) $data['bku_id'],
            $data['catatan'] ?? null,
            (int) Auth::id(),
        );

        return back()->with('success', 'Baris koran berhasil dipasangkan dengan BKU.');
    }

    /** Batalkan satu pasangan rekonsiliasi. */
    public function unmatch(RekeningBank $rekening, Request $request)
    {
        $data = $request->validate([
            'rekonsiliasi_bank_id' => ['required', 'integer', 'exists:rekonsiliasi_bank,id'],
        ]);

        $this->pembukuanService->unmatchBank((int) $data['rekonsiliasi_bank_id'], (int) Auth::id());

        return back()->with('success', 'Pasangan rekonsiliasi dibatalkan.');
    }
}
