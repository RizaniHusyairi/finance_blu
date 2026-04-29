<?php

namespace Database\Seeders;

use App\Models\BukuKasUmum;
use App\Models\DetailMutasiBank;
use App\Models\ImportMutasiBank;
use App\Models\RekonsiliasiBank;
use App\Models\RekonsiliasiBankLog;
use App\Models\RekeningBank;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class BukuPembantuBungaRekeningSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            Role::findOrCreate('Bendahara Pengeluaran', 'web');

            // User Bendahara Pengeluaran dibuat oleh RoleAndPermissionSeeder
            // yang berjalan lebih dulu di DatabaseSeeder.
            // Cari berdasarkan email primer; fallback berdasarkan role kalau email berbeda.
            $bendahara = User::query()
                ->where('email', 'bendahara.pengeluaran@sikeren.id')
                ->first()
                ?? User::role('Bendahara Pengeluaran')->first();

            if (! $bendahara) {
                throw new \RuntimeException(
                    'User Bendahara Pengeluaran tidak ditemukan. Pastikan RoleAndPermissionSeeder dijalankan lebih dulu.'
                );
            }

            if (! $bendahara->hasRole('Bendahara Pengeluaran')) {
                $bendahara->assignRole('Bendahara Pengeluaran');
            }

            $rekeningOperasional = RekeningBank::query()->updateOrCreate(
                [
                    'pemilik_type' => User::class,
                    'pemilik_id' => $bendahara->id,
                    'nomor_rekening' => '9876543210001',
                ],
                [
                    'nama_bank' => 'Bank Mandiri',
                    'nama_rekening' => 'Bendahara Pengeluaran BLU',
                    'kode_bank' => '008',
                    'is_default' => true,
                    'status_aktif' => true,
                ]
            );

            $rekeningGiro = RekeningBank::query()->updateOrCreate(
                [
                    'pemilik_type' => User::class,
                    'pemilik_id' => $bendahara->id,
                    'nomor_rekening' => '9876543210002',
                ],
                [
                    'nama_bank' => 'BRI',
                    'nama_rekening' => 'Rekening Giro BLU',
                    'kode_bank' => '002',
                    'is_default' => false,
                    'status_aktif' => true,
                ]
            );

            $periodeAwal = now()->copy()->startOfMonth()->toDateString();
            $periodeAkhir = now()->copy()->endOfMonth()->toDateString();

            $importMandiri = ImportMutasiBank::query()->updateOrCreate(
                [
                    'rekening_bank_id' => $rekeningOperasional->id,
                    'periode_awal' => $periodeAwal,
                    'periode_akhir' => $periodeAkhir,
                    'nama_file_asli' => 'mutasi_mandiri_bunga_' . now()->format('Ym') . '.xlsx',
                ],
                [
                    'path_file' => 'seeders/mutasi_mandiri_bunga_' . now()->format('Ym') . '.xlsx',
                    'uploaded_by' => $bendahara->id,
                    'uploaded_at' => now(),
                    'status_import' => 'PARSED',
                    'catatan_error' => null,
                ]
            );

            $importBri = ImportMutasiBank::query()->updateOrCreate(
                [
                    'rekening_bank_id' => $rekeningGiro->id,
                    'periode_awal' => $periodeAwal,
                    'periode_akhir' => $periodeAkhir,
                    'nama_file_asli' => 'mutasi_bri_bunga_' . now()->format('Ym') . '.xlsx',
                ],
                [
                    'path_file' => 'seeders/mutasi_bri_bunga_' . now()->format('Ym') . '.xlsx',
                    'uploaded_by' => $bendahara->id,
                    'uploaded_at' => now(),
                    'status_import' => 'PARSED',
                    'catatan_error' => null,
                ]
            );

            $dataset = [
                [
                    'import_id' => $importMandiri->id,
                    'rekening_id' => $rekeningOperasional->id,
                    'tanggal' => now()->copy()->startOfMonth()->addDays(2)->toDateString(),
                    'deskripsi' => 'BUNGA REKENING OPERASIONAL BLU',
                    'referensi' => 'BGA-' . now()->format('Ym') . '-001',
                    'debit' => 154250,
                    'saldo' => 125154250,
                    'status_rekonsiliasi' => 'MATCHED',
                    'buat_bku' => true,
                    'status_rekonsiliasi_bank' => 'MATCHED',
                    'catatan' => 'Bunga rekening bulan berjalan sudah dicatat pada BKU.',
                ],
                [
                    'import_id' => $importMandiri->id,
                    'rekening_id' => $rekeningOperasional->id,
                    'tanggal' => now()->copy()->startOfMonth()->addDays(11)->toDateString(),
                    'deskripsi' => 'JASA GIRO REKENING OPERASIONAL',
                    'referensi' => 'GIR-' . now()->format('Ym') . '-002',
                    'debit' => 98250,
                    'saldo' => 125252500,
                    'status_rekonsiliasi' => 'MATCHED',
                    'buat_bku' => true,
                    'status_rekonsiliasi_bank' => 'MATCHED',
                    'catatan' => 'Jasa giro cocok dengan pencatatan BKU.',
                ],
                [
                    'import_id' => $importBri->id,
                    'rekening_id' => $rekeningGiro->id,
                    'tanggal' => now()->copy()->subDays(4)->toDateString(),
                    'deskripsi' => 'INTEREST CREDIT REKENING GIRO BLU',
                    'referensi' => 'INT-' . now()->format('Ym') . '-003',
                    'debit' => 120500,
                    'saldo' => 78500500,
                    'status_rekonsiliasi' => 'BELUM',
                    'buat_bku' => false,
                    'status_rekonsiliasi_bank' => null,
                    'catatan' => 'Transaksi bunga sudah masuk mutasi bank tetapi belum dicatat ke BKU.',
                ],
            ];

            foreach ($dataset as $index => $item) {
                $mutasi = DetailMutasiBank::query()->updateOrCreate(
                    [
                        'import_mutasi_bank_id' => $item['import_id'],
                        'nomor_referensi_bank' => $item['referensi'],
                    ],
                    [
                        'tanggal_transaksi' => $item['tanggal'],
                        'deskripsi' => $item['deskripsi'],
                        'debit' => $item['debit'],
                        'kredit' => 0,
                        'saldo' => $item['saldo'],
                        'arah_mutasi' => 'MASUK',
                        'status_rekonsiliasi' => $item['status_rekonsiliasi'],
                    ]
                );

                if (!$item['buat_bku']) {
                    continue;
                }

                $bku = BukuKasUmum::query()->updateOrCreate(
                    [
                        'nomor_bukti' => 'BKU-BUNGA-' . now()->format('Ym') . '-' . str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT),
                    ],
                    [
                        'tanggal_transaksi' => $item['tanggal'],
                        'uraian' => $item['deskripsi'],
                        'arus_kas' => 'DEBIT_MASUK',
                        'nominal' => $item['debit'],
                        'saldo_akhir' => $item['saldo'],
                        'sumber_rekening_id' => $item['rekening_id'],
                        'referensi_pengeluaran_id' => null,
                        'referensi_penerimaan_id' => null,
                    ]
                );

                $rekonsiliasi = RekonsiliasiBank::query()->updateOrCreate(
                    [
                        'detail_mutasi_bank_id' => $mutasi->id,
                    ],
                    [
                        'bku_id' => $bku->id,
                        'transaksi_penerimaan_id' => null,
                        'tagihan_id' => null,
                        'nominal_mutasi' => $item['debit'],
                        'nominal_sistem' => $item['debit'],
                        'selisih' => 0,
                        'status' => $item['status_rekonsiliasi_bank'],
                        'catatan' => $item['catatan'],
                        'direkonsiliasi_oleh' => $bendahara->id,
                        'direkonsiliasi_pada' => now(),
                    ]
                );

                RekonsiliasiBankLog::query()->updateOrCreate(
                    [
                        'rekonsiliasi_bank_id' => $rekonsiliasi->id,
                        'aksi' => 'SEED_MATCH_BUNGA',
                    ],
                    [
                        'user_id' => $bendahara->id,
                        'catatan' => 'Seeder bunga rekening membuat pasangan mutasi bank dengan BKU.',
                    ]
                );
            }
        });
    }
}
