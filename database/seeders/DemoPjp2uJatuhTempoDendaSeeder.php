<?php

namespace Database\Seeders;

use App\Models\LayananJasa;
use App\Models\MasterPihak;
use App\Models\MitraJasa;
use App\Models\TagihanJasa;
use App\Models\TagihanJasaDetail;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DemoPjp2uJatuhTempoDendaSeeder extends Seeder
{
    public function run(): void
    {
        $today = now()->startOfDay();
        $admin = User::where('email', 'admin.jasa@sikeren.id')->first()
            ?? User::where('email', 'super.admin.jasa@sikeren.id')->first()
            ?? User::first();

        if (! $admin) {
            $this->command?->warn('Seeder demo PJP2U dilewati: belum ada user.');
            return;
        }

        $mitra = MitraJasa::where('kode_mitra', 'CIT')->first()
            ?? MitraJasa::where('nama_mitra', 'like', '%PT ABC%')->first()
            ?? MitraJasa::where('jenis_mitra', 'Maskapai')->where('status_aktif', true)->first()
            ?? MitraJasa::where('status_aktif', true)->first();

        if (! $mitra) {
            $this->command?->warn('Seeder demo PJP2U dilewati: belum ada mitra jasa.');
            return;
        }

        $layanan = LayananJasa::with('parent.parent')
            ->where('is_leaf', true)
            ->where('is_active', true)
            ->get()
            ->first(fn (LayananJasa $item) => $item->isPjp2u());

        if (! $layanan) {
            $this->command?->warn('Seeder demo PJP2U dilewati: belum ada layanan leaf PJP2U aktif.');
            return;
        }

        $tarif = (float) ($layanan->tarif_dasar ?: 40000);
        $legacyPihakQuery = MasterPihak::where('nama_pihak', $mitra->nama_mitra);
        if (filled($mitra->kode_mitra)) {
            $legacyPihakQuery->orWhere('kode_pihak', $mitra->kode_mitra);
        }

        $legacyPihak = $legacyPihakQuery->first() ?? MasterPihak::create([
            'kategori' => 'PENERIMAAN',
            'jenis_entitas' => 'BADAN_USAHA',
            'kode_pihak' => $mitra->kode_mitra ?: ('MTR-JASA-' . $mitra->id),
            'npwp' => $mitra->npwp,
            'nama_pihak' => $mitra->nama_mitra,
            'nama_penanggung_jawab' => $mitra->nama_penanggung_jawab,
            'alamat' => $mitra->alamat,
            'email' => $mitra->email,
            'no_telepon' => $mitra->no_telepon,
            'status_aktif' => true,
        ]);

        $legacyMitraId = $legacyPihak->id;

        $scenarios = [
            [
                'nomor' => 'TAG-PJP2U-DEMO-AWAL-7H',
                'label' => 'Demo PJP2U tagihan pertama - jatuh tempo 7 hari',
                'pax' => 25,
                'due_days' => 7,
                'publish_offset_days' => 0,
                'nomor_va' => '880010000001',
                'note' => 'Tagihan PJP2U pertama: jatuh tempo 7 hari sejak publish.',
            ],
            [
                'nomor' => 'TAG-PJP2U-DEMO-LANJUT-H3',
                'label' => 'Demo PJP2U tagihan berikutnya - jatuh tempo 30 hari (H-3)',
                'pax' => 25,
                'due_days' => 30,
                'publish_offset_days' => -27,
                'nomor_va' => '880010000002',
                'note' => 'Tagihan PJP2U berikutnya: jatuh tempo 30 hari sejak publish, sekarang H-3.',
            ],
            [
                'nomor' => 'TAG-PJP2U-DEMO-DENDA-H3',
                'label' => 'Demo PJP2U lewat jatuh tempo - denda berjalan',
                'pax' => 25,
                'due_days' => 30,
                'publish_offset_days' => -33,
                'nomor_va' => '880010000003',
                'note' => 'Tagihan PJP2U berikutnya: sudah lewat jatuh tempo 3 hari, denda 2% per hari.',
            ],
        ];

        DB::transaction(function () use ($scenarios, $today, $admin, $mitra, $legacyMitraId, $layanan, $tarif) {
            foreach ($scenarios as $scenario) {
                $publishDate = $today->copy()->addDays($scenario['publish_offset_days']);
                $dueDate = $publishDate->copy()->addDays($scenario['due_days']);
                $total = $scenario['pax'] * $tarif;

                $tagihan = TagihanJasa::withTrashed()->updateOrCreate(
                    ['nomor_tagihan' => $scenario['nomor']],
                    [
                        'mitra_id' => $legacyMitraId,
                        'mitra_jasa_id' => $mitra->id,
                        'nomor_tagihan' => $scenario['nomor'],
                        'nomor_surat_pengantar' => null,
                        'tanggal_tagihan' => $today->toDateString(),
                        'tanggal_publish' => $publishDate->toDateString(),
                        'jumlah_hari_jatuh_tempo' => $scenario['due_days'],
                        'masa_toleransi_hari' => 0,
                        'tanggal_jatuh_tempo' => $dueDate->toDateString(),
                        'tanggal_akhir_toleransi' => $dueDate->toDateString(),
                        'catatan_jatuh_tempo' => $scenario['note'],
                        'total_tagihan' => $total,
                        'status' => 'PUBLISHED',
                        'status_pembayaran' => 'belum_dibayar',
                        'tanggal_lunas' => null,
                        'jumlah_dibayar' => 0,
                        'sisa_tagihan' => $total,
                        'nomor_va' => $scenario['nomor_va'],
                        'va_provider' => 'manual',
                        'va_reference' => 'DEMO-PJP2U',
                        'va_expired_at' => $dueDate->copy()->endOfDay(),
                        'tipe_pnbp' => 'FUNGSI',
                        'created_by' => $admin->id,
                        'deleted_at' => null,
                    ],
                );

                TagihanJasaDetail::updateOrCreate(
                    [
                        'tagihan_jasa_id' => $tagihan->id,
                        'layanan_jasa_id' => $layanan->id,
                    ],
                    [
                        'qty' => $scenario['pax'],
                        'harga_satuan' => $tarif,
                        'subtotal' => $total,
                        'keterangan' => $scenario['label'],
                        'calculation_payload' => [
                            'demo' => true,
                            'jenis' => 'PJP2U',
                            'aturan_jatuh_tempo' => $scenario['due_days'] === 7 ? 'pertama_7_hari' : 'lanjutan_30_hari',
                            'pax' => $scenario['pax'],
                            'tarif' => $tarif,
                        ],
                    ],
                );
            }
        });

        $this->command?->info('Seeded demo PJP2U jatuh tempo & denda untuk ' . $mitra->nama_mitra . '.');
    }
}
