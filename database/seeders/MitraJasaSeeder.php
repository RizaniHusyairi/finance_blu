<?php

namespace Database\Seeders;

use App\Models\MitraJasa;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Mitra jasa (penyewa/pengguna layanan PNBP bandara) contoh untuk menguji alur
 * Penagihan Jasa → Pelunasan → BKU Penerimaan.
 *
 * Dipakai oleh [[TagihanJasaLunasSeeder]] sebagai pemilik tagihan.
 * Idempoten: updateOrCreate by kode_mitra.
 */
class MitraJasaSeeder extends Seeder
{
    public function run(): void
    {
        $creatorId = User::role('Super Admin')->value('id')
            ?? User::query()->value('id');

        // [kode_mitra, nama_mitra, jenis_mitra, npwp, email, no_telepon, alamat, penanggung_jawab, jabatan]
        $mitras = [
            ['MJ-001', 'PT Lion Mentari Airlines', 'MASKAPAI', '01.234.567.8-091.000', 'finance@lionair.example.id', '021-6379888', 'Lion Air Tower, Jl. Gajah Mada No. 7, Jakarta Pusat', 'Rudi Hartono', 'Manajer Keuangan'],
            ['MJ-002', 'PT Garuda Indonesia (Persero) Tbk', 'MASKAPAI', '01.000.013.6-051.000', 'station.aap@garuda.example.id', '0804-1807-807', 'Garuda City Center, Bandara Soekarno-Hatta, Tangerang', 'Siti Nurhaliza', 'Station Manager'],
            ['MJ-003', 'PT Solusi Bandara Mandiri', 'PENYEWA_RUANG', '02.345.678.9-721.000', 'admin@sbm.example.id', '0541-748100', 'Jl. Pipit No. 12, Samarinda, Kalimantan Timur', 'Bambang Wijaya', 'Direktur Operasional'],
            ['MJ-004', 'CV Aero Parkir Nusantara', 'PENGELOLA_LAHAN', '03.456.789.0-721.000', 'kontak@aeroparkir.example.id', '0541-7300200', 'Jl. Poros Bandara APT Pranoto Km. 22, Samarinda', 'Dewi Lestari', 'Pemilik'],
            ['MJ-005', 'PT Boga Konsesi Nusantara', 'KONSESIONER', '04.567.890.1-721.000', 'finance@bogakonsesi.example.id', '0541-7500300', 'Terminal Keberangkatan Lt. 2, Bandara APT Pranoto, Samarinda', 'Andi Pratama', 'General Manager'],
        ];

        foreach ($mitras as [$kode, $nama, $jenis, $npwp, $email, $telp, $alamat, $pj, $jabatan]) {
            MitraJasa::updateOrCreate(
                ['kode_mitra' => $kode],
                [
                    'nama_mitra' => $nama,
                    'jenis_mitra' => $jenis,
                    'npwp' => $npwp,
                    'email' => $email,
                    'no_telepon' => $telp,
                    'alamat' => $alamat,
                    'nama_penanggung_jawab' => $pj,
                    'jabatan_penanggung_jawab' => $jabatan,
                    'status_aktif' => true,
                    'created_by' => $creatorId,
                    'updated_by' => $creatorId,
                ],
            );
        }

        $this->command?->info('✓ ' . count($mitras) . ' mitra jasa diseed.');
    }
}
