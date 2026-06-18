<?php

namespace Database\Seeders;

use App\Models\KodeTransaksi;
use Illuminate\Database\Seeder;

/**
 * Master kode transaksi SILABI + matriks distribusi ke buku.
 *
 * Sumber kebenaran: rumus IF pada sheet `Input_Transaksi` File 1
 * (`01._Pembukuan_Bulan Januari 2026.xlsx`), kolom O..DD. Untuk tiap buku ada
 * himpunan kode yang masuk sebagai TERIMA (debit kas) dan KELUAR (kredit kas).
 * Matriks per-buku di bawah ditranskripsi langsung dari rumus tsb lalu
 * di-INVERS menjadi posting_rules per kode.
 *
 * Catatan: pada BKU (buku 1), kode perpindahan internal (G1/G2) sengaja muncul
 * di TERIMA dan KELUAR sekaligus sehingga saling meniadakan (wash) — sesuai
 * perilaku Excel acuan untuk kas terkonsolidasi.
 */
class KodeTransaksiSeeder extends Seeder
{
    public function run(): void
    {
        // kode_buku => ['TERIMA' => [...kode], 'KELUAR' => [...kode]]
        $matrix = [
            1 => [ // BKU (O+P+Q terima ; U+V+W+X keluar)
                'TERIMA' => ['A','B','C1','D1','E','F','G1','G2','R1','L4','R2','L7','J1','J2','J4','K1','K2','K4','L1','M1','M6','M4'],
                'KELUAR' => ['A','C2','D2','E','F','G1','G2','H1','R1','H2','I1','I2','J1','J2','J3','J4','K1','K2','K3','K4','L2','L3','M2','M3','M7','L5','L6','N1','N2'],
            ],
            2 => [ // Kas Tunai (AN ; AQ)
                'TERIMA' => ['G1','J4','K4','M1'],
                'KELUAR' => ['G2','H1','I1','J1','K1','L2','M2','N1'],
            ],
            3 => [ // Bank (AT ; AU+AV)
                'TERIMA' => ['B','C1','D1','G2','L1','L4','R2','L7'],
                'KELUAR' => ['C2','D2','G1','H2','I2','J2','K2','L3','L5','L6','M3','N2','M9'],
            ],
            4 => [ // BPP (BB ; BE)
                'TERIMA' => ['J1','J2','M4'],
                'KELUAR' => ['J3','J4','M5'],
            ],
            5 => [ // UP (BH ; BK)
                'TERIMA' => ['B','D1'],
                'KELUAR' => ['D2','H1','H2','J3','K3','N1','N2'],
            ],
            6 => [ // LS Bendahara (BN ; BQ)
                'TERIMA' => ['C1','R2','L7'],
                'KELUAR' => ['C2','I1','I2','M9','M3','K3','R1','L6'],
            ],
            7 => [ // UM Perjadin (BT ; BW)
                'TERIMA' => ['K1','K2'],
                'KELUAR' => ['K3','K4'],
            ],
            8 => [ // Pajak (BZ ; CC)
                'TERIMA' => ['M1','M4','M8'],
                'KELUAR' => ['M2','M3','M5'],
            ],
            9 => [ // Bunga Rekening (CF ; CI)
                'TERIMA' => ['L1','R1'],
                'KELUAR' => ['L2','L3'],
            ],
            10 => [ // Pajak LS (CL ; CO)
                'TERIMA' => ['M1','M4','M6','M8'],
                'KELUAR' => ['M2','M3','M5','M7','M9'],
            ],
            11 => [ // Pengesahan (CU ; CX)
                'TERIMA' => ['C2','I1','I2','K3','M3','M9'],
                'KELUAR' => ['C3'],
            ],
            12 => [ // Pengembalian Belanja (DA ; DD)
                'TERIMA' => ['L4','R2'],
                'KELUAR' => ['L5'],
            ],
        ];

        $uraian = [
            'A'  => 'Membukukan Pagu DIPA',
            'B'  => 'Terima Uang Persediaan (UP)',
            'C1' => 'Terima Transfer Uang dari Rekening Bendahara Penerimaan (Bank)',
            'C2' => 'Pengesahan/Belanja LS melalui Bendahara',
            'C3' => 'Koreksi Pengesahan',
            'D1' => 'Terima Tambahan Uang Persediaan (TUP)',
            'D2' => 'Belanja dengan Tambahan Uang Persediaan (TUP)',
            'E'  => 'Setor Sisa Uang Persediaan',
            'F'  => 'Penyesuaian/Koreksi Kas',
            'G1' => 'Perpindahan dari Bank ke Tunai (Tarik Tunai)',
            'G2' => 'Perpindahan dari Tunai ke Bank (Setor Tunai ke Rekening Bendahara Pengeluaran)',
            'H1' => 'Belanja dengan UP/TUP (Tunai)',
            'H2' => 'Belanja dengan UP/TUP (Bank)',
            'I1' => 'Belanja dengan cara LS - Tunai',
            'I2' => 'Belanja dengan cara LS - Bank',
            'J1' => 'Pemberian Uang Muka ke BPP (Tunai)',
            'J2' => 'Pemberian Uang Muka ke BPP (Bank)',
            'J3' => 'Pertanggungjawaban Belanja BPP',
            'J4' => 'Terima Sisa Uang Muka dari BPP',
            'K1' => 'Pemberian Uang Muka Perjadin (Tunai)',
            'K2' => 'Pemberian Uang Muka Perjadin (Bank)',
            'K3' => 'Perhitungan Rampung Perjadin',
            'K4' => 'Terima Sisa UM Perjadin (Tunai)',
            'L1' => 'Terima Uang Lain-lain - Bunga Rekening',
            'L2' => 'Setor/Koreksi Bunga Rekening',
            'L3' => 'Transfer Uang Bunga Rekening ke Rekening Bendahara Penerimaan',
            'L4' => 'Terima Uang Lain-lain - Pengembalian Belanja',
            'L5' => 'Transfer Uang Pengembalian Belanja (Buku Pengembalian) ke Rekening Bendahara Penerimaan',
            'L6' => 'Transfer Uang Pengembalian Belanja (Buku LS Bendahara) ke Rekening Bendahara Penerimaan',
            'L7' => 'Terima Uang Retur',
            'M1' => 'Pungut Pajak oleh Bendahara - Tunai',
            'M2' => 'Setor Pajak oleh Bendahara - Tunai',
            'M3' => 'Penyetoran Pajak oleh Bendahara Pengeluaran Melalui Bank (Pajak Belum Disahkan)',
            'M4' => 'Pungut Pajak oleh BPP',
            'M5' => 'Setor Pajak oleh BPP',
            'M6' => 'Pungut Pajak atas Belanja LS',
            'M7' => 'Setor Pajak atas Belanja LS',
            'M8' => 'Pencatatan Pungut Pajak oleh Bendahara Pengeluaran',
            'M9' => 'Pencatatan Penyetoran Pajak oleh Bendahara Pengeluaran (Bank) yang Sudah Disahkan',
            'N1' => 'Setor Sisa UP/TUP (Tunai)',
            'N2' => 'Setor Sisa UP/TUP (Bank)',
            'R1' => 'Transaksi Koreksi Non Belanja - Buku Pembantu Bunga Bank (D) & LS Bendahara (K)',
            'R2' => 'Terima Transfer dari Rekening Bendahara Penerimaan berupa Pengembalian Belanja',
        ];

        $jenis = [
            'B' => 'UP', 'D1' => 'TU', 'H1' => 'UP', 'H2' => 'UP',
            'N1' => 'UP', 'N2' => 'UP', 'D2' => 'TU',
            'I1' => 'LS', 'I2' => 'LS', 'C2' => 'LS',
        ];

        // Urutan kanonik kode.
        $order = ['A','B','C1','C2','C3','D1','D2','E','F','G1','G2','H1','H2','I1','I2',
            'J1','J2','J3','J4','K1','K2','K3','K4','L1','L2','L3','L4','L5','L6','L7',
            'M1','M2','M3','M4','M5','M6','M7','M8','M9','N1','N2','R1','R2'];

        // Invers matriks per-buku → posting_rules per kode.
        $rules = [];
        foreach ($matrix as $kodeBuku => $arah) {
            foreach (['TERIMA', 'KELUAR'] as $a) {
                foreach (array_unique($arah[$a]) as $kode) {
                    $rules[$kode][] = ['kode_buku' => $kodeBuku, 'arah' => $a, 'sign' => 1];
                }
            }
        }

        foreach ($order as $i => $kode) {
            KodeTransaksi::updateOrCreate(
                ['kode' => $kode],
                [
                    'uraian' => $uraian[$kode] ?? $kode,
                    'jenis_pembayaran' => $jenis[$kode] ?? null,
                    'posting_rules' => $rules[$kode] ?? [],
                    'urutan' => $i + 1,
                    'status_aktif' => true,
                ],
            );
        }
    }
}
