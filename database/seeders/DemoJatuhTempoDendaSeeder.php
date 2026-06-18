<?php

namespace Database\Seeders;

use App\Models\KontrakMitraJasa;
use App\Models\LayananJasa;
use App\Models\MasterPihak;
use App\Models\MitraJasa;
use App\Models\TagihanJasa;
use App\Models\TagihanJasaDetail;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Demo jatuh tempo & denda untuk dua kelompok tagihan jasa:
 *  - PJP2U            : jatuh tempo 7 hari (khusus, lebih ketat).
 *  - Tagihan jasa lain: jatuh tempo 30 hari.
 *
 * Rumus denda SAMA untuk semua: 2% per periode 30 hari (dibulatkan ke atas),
 * terus berjalan tanpa pembekuan. Kualitas piutang: Lancar 0, Kurang Lancar
 * 1-90, Diragukan 91-180, Macet > 180 hari.
 *
 * Idempoten: memakai nomor tagihan stabil (updateOrCreate), nomor PJP2U sama
 * dengan DemoPjp2uJatuhTempoDendaSeeder agar tidak menggandakan baris.
 */
class DemoJatuhTempoDendaSeeder extends Seeder
{
    public function run(): void
    {
        $today = now()->startOfDay();

        $admin = User::where('email', 'admin.jasa@sikeren.id')->first()
            ?? User::where('email', 'super.admin.jasa@sikeren.id')->first()
            ?? User::first();

        if (! $admin) {
            $this->command?->warn('Seeder demo jatuh tempo & denda dilewati: belum ada user.');
            return;
        }

        $mitra = MitraJasa::where('kode_mitra', 'CIT')->first()
            ?? MitraJasa::where('nama_mitra', 'like', '%PT ABC%')->first()
            ?? MitraJasa::where('status_aktif', true)->first();

        if (! $mitra) {
            $this->command?->warn('Seeder demo jatuh tempo & denda dilewati: belum ada mitra jasa.');
            return;
        }

        $legacyMitraId = $this->resolveLegacyPihakId($mitra);

        $leaves = LayananJasa::with('parent.parent.parent')
            ->where('is_leaf', true)
            ->where('is_active', true)
            ->get();

        $pjp2u = $leaves->first(fn (LayananJasa $l) => $l->isPjp2u());
        $lain = $leaves
            ->filter(fn (LayananJasa $l) => ! $l->isPjp2u() && (float) $l->tarif_dasar > 0)
            ->sortByDesc(fn (LayananJasa $l) => (float) $l->tarif_dasar)
            ->first();

        // Aturan baru: setiap layanan demo wajib punya kontrak. Buat kontrak demo
        // dengan jenis dokumen sesuai, lalu pool mitra diturunkan dari kontrak aktif.
        $kontrakPjp2u = $pjp2u
            ? $this->ensureKontrak($mitra, $admin, 'KONTRAK-DEMO-PJP2U-' . ($mitra->kode_mitra ?: $mitra->id), 'Demo Penetapan Tarif PJP2U', 'SK_REGULASI_TARIF', [$pjp2u->id], $today)
            : null;
        $kontrakLain = $lain
            ? $this->ensureKontrak($mitra, $admin, 'KONTRAK-DEMO-LAIN-' . ($mitra->kode_mitra ?: $mitra->id), 'Demo Kontrak Layanan ' . $lain->nama_layanan, 'KONTRAK', [$lain->id], $today)
            : null;

        app(\App\Services\MitraLayananService::class)->syncFromKontrak($mitra, $admin->id);

        $groups = [];

        if ($pjp2u) {
            $groups[] = [
                'layanan' => $pjp2u,
                'kontrak' => $kontrakPjp2u,
                'jenis' => 'PJP2U',
                'due_days' => 7,
                'masa_denda' => 30, // periode denda PJP2U
                'va_prefix' => '8800100000',
                'scenarios' => [
                    ['suffix' => 'AKTIF-7H', 'label' => 'PJP2U - jatuh tempo 7 hari (belum jatuh tempo)', 'qty' => 25, 'publish_offset' => 0,    'note' => 'PJP2U: jatuh tempo 7 hari sejak publish.'],
                    ['suffix' => 'DENDA-1P', 'label' => 'PJP2U - lewat jatuh tempo 7 hari (telat 10 hari, 1 periode)', 'qty' => 25, 'publish_offset' => -17, 'note' => 'PJP2U: sudah lewat jatuh tempo (telat 10 hari) -> denda 2% x 1 periode = 2%.'],
                    ['suffix' => 'DENDA-2P', 'label' => 'PJP2U - telat 38 hari (denda 2 periode)',          'qty' => 25, 'publish_offset' => -45,  'note' => 'PJP2U: telat 38 hari -> denda 2% x 2 periode = 4% (terus berjalan).'],
                    ['suffix' => 'MACET',    'label' => 'PJP2U - telat 188 hari (macet)',                    'qty' => 25, 'publish_offset' => -195, 'note' => 'PJP2U: telat 188 hari -> denda 2% x 7 periode = 14%, kualitas macet.'],
                ],
            ];
        } else {
            $this->command?->warn('Layanan PJP2U leaf aktif tidak ditemukan; grup PJP2U dilewati.');
        }

        if ($lain) {
            $groups[] = [
                'layanan' => $lain,
                'kontrak' => $kontrakLain,
                'jenis' => 'LAIN',
                'due_days' => 30,
                'masa_denda' => 0, // 0 -> accessor memakai default 30 (denda tetap seragam)
                'va_prefix' => '8800200000',
                'scenarios' => [
                    ['suffix' => 'AKTIF-30H', 'label' => 'Tagihan lain - jatuh tempo 30 hari (belum jatuh tempo)', 'qty' => 4, 'publish_offset' => 0,    'note' => 'Tagihan jasa lain: jatuh tempo 30 hari sejak publish.'],
                    ['suffix' => 'DENDA-1P',  'label' => 'Tagihan lain - telat 10 hari (denda 1 periode)',          'qty' => 4, 'publish_offset' => -40,  'note' => 'Tagihan jasa lain: telat 10 hari -> denda 2% x 1 periode = 2%.'],
                    ['suffix' => 'MACET',     'label' => 'Tagihan lain - telat 200 hari (macet)',                   'qty' => 4, 'publish_offset' => -230, 'note' => 'Tagihan jasa lain: telat 200 hari -> denda 2% x 7 periode = 14%, kualitas macet.'],
                ],
            ];
        } else {
            $this->command?->warn('Layanan non-PJP2U leaf aktif (tarif > 0) tidak ditemukan; grup tagihan lain dilewati.');
        }

        if (empty($groups)) {
            $this->command?->warn('Seeder demo jatuh tempo & denda dilewati: tidak ada layanan yang cocok.');
            return;
        }

        DB::transaction(function () use ($groups, $today, $admin, $mitra, $legacyMitraId) {
            foreach ($groups as $group) {
                foreach (array_values($group['scenarios']) as $i => $sc) {
                    $this->buatTagihan($group, $sc, $i + 1, $today, $admin, $mitra, $legacyMitraId);
                }
            }
        });

        $this->command?->info('Seeded demo jatuh tempo & denda (PJP2U + tagihan lain) untuk ' . $mitra->nama_mitra . '.');
    }

    private function resolveLegacyPihakId(MitraJasa $mitra): int
    {
        $query = MasterPihak::where('nama_pihak', $mitra->nama_mitra);
        if (filled($mitra->kode_mitra)) {
            $query->orWhere('kode_pihak', $mitra->kode_mitra);
        }

        $pihak = $query->first() ?? MasterPihak::create([
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

        return $pihak->id;
    }

    private function ensureKontrak(MitraJasa $mitra, User $admin, string $nomor, string $nama, string $jenis, array $layananIds, $today): KontrakMitraJasa
    {
        $kontrak = KontrakMitraJasa::firstOrCreate(
            ['mitra_jasa_id' => $mitra->id, 'nomor_kontrak' => $nomor],
            [
                'nama_kontrak' => $nama,
                'jenis_dokumen' => $jenis,
                'tanggal_kontrak' => $today->toDateString(),
                'tanggal_mulai' => $today->copy()->subYear()->toDateString(),
                'tanggal_selesai' => $today->copy()->addYears(2)->toDateString(),
                'status_kontrak' => 'AKTIF',
                'keterangan' => 'Kontrak demo untuk skenario jatuh tempo & denda.',
                'created_by' => $admin->id,
                'updated_by' => $admin->id,
            ],
        );

        $sync = collect($layananIds)
            ->filter()
            ->mapWithKeys(fn ($id) => [(int) $id => ['created_by' => $admin->id]])
            ->all();
        $kontrak->layananJasa()->syncWithoutDetaching($sync);

        return $kontrak;
    }

    private function buatTagihan(array $group, array $sc, int $seq, $today, User $admin, MitraJasa $mitra, int $legacyMitraId): void
    {
        $layanan = $group['layanan'];
        $tarif = (float) ($layanan->tarif_dasar ?: 40000);
        $publishDate = $today->copy()->addDays($sc['publish_offset']);
        $dueDate = $publishDate->copy()->addDays($group['due_days']);
        $total = $sc['qty'] * $tarif;
        $nomor = 'TAG-' . $group['jenis'] . '-DEMO-' . $sc['suffix'];
        $nomorVa = $group['va_prefix'] . str_pad((string) $seq, 2, '0', STR_PAD_LEFT);

        $tagihan = TagihanJasa::withTrashed()->updateOrCreate(
            ['nomor_tagihan' => $nomor],
            [
                'mitra_id' => $legacyMitraId,
                'mitra_jasa_id' => $mitra->id,
                'kontrak_mitra_jasa_id' => ($group['kontrak'] ?? null)?->id,
                'nomor_kontrak' => ($group['kontrak'] ?? null)?->nomor_kontrak,
                'nomor_tagihan' => $nomor,
                'nomor_surat_pengantar' => null,
                'tanggal_tagihan' => $today->toDateString(),
                'tanggal_publish' => $publishDate->toDateString(),
                'jumlah_hari_jatuh_tempo' => $group['due_days'],
                'masa_toleransi_hari' => 0,
                'masa_denda_hari' => $group['masa_denda'],
                'tanggal_jatuh_tempo' => $dueDate->toDateString(),
                'tanggal_akhir_toleransi' => $dueDate->toDateString(),
                'catatan_jatuh_tempo' => $sc['note'],
                'total_tagihan' => $total,
                'status' => 'PUBLISHED',
                'status_pembayaran' => 'belum_dibayar',
                'tanggal_lunas' => null,
                'jumlah_dibayar' => 0,
                'sisa_tagihan' => $total,
                'nomor_va' => $nomorVa,
                'va_provider' => 'manual',
                'va_reference' => 'DEMO-JT-DENDA',
                'va_expired_at' => $dueDate->copy()->endOfDay(),
                'tipe_pnbp' => 'FUNGSI',
                'created_by' => $admin->id,
                'deleted_at' => null,
            ],
        );

        // Demo tagihan = satu detail. Bersihkan detail lama dulu agar tetap konsisten
        // meski layanan demo berganti antar-run (tidak meninggalkan detail ganda).
        TagihanJasaDetail::where('tagihan_jasa_id', $tagihan->id)->delete();
        TagihanJasaDetail::create([
            'tagihan_jasa_id' => $tagihan->id,
            'layanan_jasa_id' => $layanan->id,
            'qty' => $sc['qty'],
            'harga_satuan' => $tarif,
            'subtotal' => $total,
            'keterangan' => $sc['label'],
            'calculation_payload' => [
                'demo' => true,
                'jenis' => $group['jenis'],
                'due_days' => $group['due_days'],
                'masa_denda_hari' => $group['masa_denda'],
                'qty' => $sc['qty'],
                'tarif' => $tarif,
            ],
        ]);
    }
}
