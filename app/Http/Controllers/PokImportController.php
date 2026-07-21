<?php

namespace App\Http\Controllers;

use App\Support\Pok\PokPdfParser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Pembaca POK (Rincian Kertas Kerja Satker) untuk form Tambah DIPA/Revisi:
 * unggah PDF → ringkasan + seluruh baris detil sebagai JSON untuk mengisi
 * form dan menampilkan pratinjau. File disimpan bertoken agar dapat diimpor
 * saat form disimpan (DipaController::store / storeRevision dengan pok_token).
 */
class PokImportController extends Controller
{
    public function parse(Request $request)
    {
        $request->validate([
            'file_pok' => 'required|file|mimes:pdf|max:20480',
        ]);

        $token = Str::uuid()->toString();
        $path = $request->file('file_pok')->storeAs('pok-import', $token . '.pdf', 'local');

        try {
            $hasil = (new PokPdfParser())->parse(Storage::disk('local')->path($path));
        } catch (\Throwable $e) {
            Storage::disk('local')->delete($path);

            return response()->json(['ok' => false, 'pesan' => 'PDF tidak dapat dibaca sebagai POK.'], 422);
        }

        if (empty($hasil['rows'])) {
            Storage::disk('local')->delete($path);

            return response()->json(['ok' => false, 'pesan' => 'Tidak ada baris detil POK yang dikenali. Pastikan PDF hasil cetak aplikasi anggaran, bukan hasil scan.'], 422);
        }

        $total = array_sum(array_column($hasil['rows'], 'jumlah'));
        $saranNomor = null;
        if ($hasil['kode_kemen'] && $hasil['kode_unit'] && $hasil['kode_satker']) {
            $saranNomor = sprintf(
                'DIPA-%s.%s.2.%s/%s',
                $hasil['kode_kemen'],
                $hasil['kode_unit'],
                $hasil['kode_satker'],
                $hasil['tahun'] ?? now()->year
            );
        }

        return response()->json([
            'ok' => true,
            'token' => $token,
            'tahun' => $hasil['tahun'],
            'alokasi' => $hasil['alokasi'],
            'total' => $total,
            'seimbang' => $hasil['alokasi'] !== null && (float) $total === (float) $hasil['alokasi'],
            'jumlah_baris' => count($hasil['rows']),
            'satker' => $hasil['satker'],
            'saran_nomor' => $saranNomor,
            'tanggal_ttd' => $hasil['tanggal_ttd'],
            'warnings' => $hasil['warnings'],
            'rows' => array_map(fn (array $row) => [
                'kode_mak_lengkap' => $row['kode_mak_lengkap'],
                'nama' => $row['nama'],
                'volume' => $row['volume'],
                'satuan' => $row['satuan'],
                'harga_satuan' => $row['harga_satuan'],
                'jumlah' => $row['jumlah'],
                'sumber_dana' => $row['sumber_dana'],
            ], $hasil['rows']),
        ]);
    }
}
