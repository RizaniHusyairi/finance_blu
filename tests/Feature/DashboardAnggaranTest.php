<?php

namespace Tests\Feature;

use App\Models\DetailDipa;
use App\Models\MasterCoa;
use App\Models\MasterDipa;
use App\Models\RealisasiAnggaran;
use App\Models\RiwayatRevisiDipa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Angka anggaran di dashboard: pagu hanya dari revisi aktif, jenis belanja
 * dari awalan kd_akun (bukan substring kode MAK), dan realisasi hanya yang
 * TERCATAT serta tidak dibatalkan.
 */
class DashboardAnggaranTest extends TestCase
{
    use RefreshDatabase;

    public function test_serapan_per_jenis_belanja_memakai_revisi_aktif_dan_realisasi_sah(): void
    {
        Role::findOrCreate('Kepala Subbagian Keuangan dan Tata Usaha', 'web');
        $user = User::factory()->create();
        $user->assignRole('Kepala Subbagian Keuangan dan Tata Usaha');

        $dipa = MasterDipa::create([
            'nomor_dipa' => 'DIPA-DASH-UJI',
            'tahun_anggaran' => 2026,
            'tanggal_disahkan' => '2026-01-03',
            'revisi_aktif_ke' => 1,
            'status_aktif' => true,
        ]);
        $revisi0 = RiwayatRevisiDipa::create(['master_dipa_id' => $dipa->id, 'nomor_revisi' => 0, 'total_pagu' => 1000000, 'is_active' => false]);
        $revisi1 = RiwayatRevisiDipa::create(['master_dipa_id' => $dipa->id, 'nomor_revisi' => 1, 'total_pagu' => 1200000, 'is_active' => true]);

        // Kode MAK memuat "53" dan "51" di tengah, tetapi akunnya belanja barang (52).
        $coa = MasterCoa::create([
            'kode_mak_lengkap' => '023.04.WA.4478.EBA.953.051.A.521211',
            'kd_akun' => '521211',
            'nama_akun' => 'Belanja Bahan',
            'status_aktif' => true,
        ]);
        $itemLama = DetailDipa::create(['dipa_revision_id' => $revisi0->id, 'coa_id' => $coa->id, 'nilai_pagu' => 1000000, 'status_aktif' => true]);
        DetailDipa::create(['dipa_revision_id' => $revisi1->id, 'coa_id' => $coa->id, 'nilai_pagu' => 1200000, 'status_aktif' => true]);

        RealisasiAnggaran::create([
            'dipa_revision_item_id' => $itemLama->id,
            'tanggal_pencairan' => now()->toDateString(),
            'nomor_bukti' => 'SP2D-SAH',
            'nominal_cair' => 300000,
            'status' => 'TERCATAT',
        ]);
        // SP2D dibatalkan: rollbackRealization menandai DIBATALKAN lalu soft delete.
        $batal = RealisasiAnggaran::create([
            'dipa_revision_item_id' => $itemLama->id,
            'tanggal_pencairan' => now()->toDateString(),
            'nomor_bukti' => 'SP2D-BATAL',
            'nominal_cair' => 500000,
            'status' => 'DIBATALKAN',
        ]);
        $batal->delete();
        // Baris lama yang tidak pernah di-soft-delete tetapi berstatus batal.
        DB::table('realisasi_anggaran')->insert([
            'dipa_revision_item_id' => $itemLama->id,
            'tanggal_pencairan' => now()->toDateString(),
            'nomor_bukti' => 'SP2D-BATAL-2',
            'nominal_cair' => 70000,
            'status' => 'DIBATALKAN',
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertViewHas('totalRealisasi', 300000.0)
            // [51, 52, 53, 525]
            ->assertViewHas('chartBarPagu', [0.0, 1200000.0, 0.0, 0.0])
            ->assertViewHas('chartBarRealisasi', [0.0, 300000.0, 0.0, 0.0]);
    }
}
