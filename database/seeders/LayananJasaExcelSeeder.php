<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\LayananJasa;
use PhpOffice\PhpSpreadsheet\IOFactory;

class LayananJasaExcelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $filePath = base_path('Update Tarif Layanan Spesifik BLU UPBU 111.xlsx');
        
        if (!file_exists($filePath)) {
            $this->command->error("File Excel tidak ditemukan di: $filePath");
            return;
        }

        $this->command->info("Membaca file Excel...");
        $spreadsheet = IOFactory::load($filePath);
        
        // Coba ambil sheet UPBU MUTIARA SIS AL-JUFRI, jika tidak ada ambil sheet ke-13
        $sheet = $spreadsheet->getSheetByName('UPBU MUTIARA SIS AL-JUFRI');
        if (!$sheet) {
            $sheet = $spreadsheet->getSheet(13); // fallback
        }
        
        $highestRow = $sheet->getHighestRow();

        // Kosongkan tabel sebelum seeding
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        LayananJasa::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $parentByLevel = [];
        $insertedCount = 0;

        for ($row = 2; $row <= $highestRow; $row++) { // Asumsi baris 1 adalah header
            $level = (int) $sheet->getCell('A' . $row)->getValue();
            $nama_layanan = $sheet->getCell('B' . $row)->getValue();

            // Skip jika baris kosong (tidak ada level atau nama)
            if (!$level || !$nama_layanan) {
                continue;
            }

            $kode_mak = $sheet->getCell('C' . $row)->getValue();
            $satuan = $sheet->getCell('D' . $row)->getValue();
            
            // Tarif baru ada di kolom H (Tarif), jika kosong ambil dari G (Tarif Lama), jika masih kosong 0
            $tarifBaru = $sheet->getCell('H' . $row)->getValue();
            $tarifLama = $sheet->getCell('G' . $row)->getValue();
            
            $tarif_dasar = 0;
            if (is_numeric($tarifBaru)) {
                $tarif_dasar = (float) $tarifBaru;
            } elseif (is_numeric($tarifLama)) {
                $tarif_dasar = (float) $tarifLama;
            }

            $kode_akun = $sheet->getCell('L' . $row)->getValue();

            // Tentukan parent_id
            $parent_id = null;
            if ($level > 1) {
                // Parent adalah node terakhir di level sebelumnya
                $parent_id = $parentByLevel[$level - 1] ?? null;
            }

            // Simpan ke DB
            $layanan = LayananJasa::create([
                'parent_id' => $parent_id,
                'level' => $level,
                'nama_layanan' => trim($nama_layanan),
                'kode_mak' => $kode_mak ? trim($kode_mak) : null,
                'satuan' => $satuan ? trim($satuan) : null,
                'tarif_dasar' => $tarif_dasar,
                'kode_akun' => $kode_akun ? trim($kode_akun) : null,
                'is_leaf' => true, // Default true, nanti diupdate jika dia jadi parent
                'is_active' => true,
            ]);

            // Catat ID ini sebagai parent potensial untuk level di bawahnya
            $parentByLevel[$level] = $layanan->id;

            // Jika node ini punya parent, berarti parent-nya BUKAN leaf
            if ($parent_id) {
                LayananJasa::where('id', $parent_id)->update(['is_leaf' => false]);
            }

            $insertedCount++;
        }

        $this->command->info("Berhasil melakukan seed $insertedCount baris layanan jasa dari Excel.");
    }
}
