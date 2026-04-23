<?php

namespace Tests\Feature;

use App\Models\KontrakAddendum;
use App\Models\KontrakPengadaan;
use App\Models\KontrakTermin;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ContractAddendumWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_addendum_can_be_created_submitted_and_approved_end_to_end(): void
    {
        Role::findOrCreate('Pejabat Pengadaan', 'web');
        Role::findOrCreate('PPK', 'web');

        $pejabat = User::factory()->create();
        $pejabat->assignRole('Pejabat Pengadaan');

        $ppk = User::factory()->create();
        $ppk->assignRole('PPK');

        $contract = $this->createContract($ppk, 100000);
        $termin = KontrakTermin::where('kontrak_pengadaan_id', $contract->id)->firstOrFail();

        $this->actingAs($pejabat)
            ->get(route('addendums.index', $contract))
            ->assertOk()
            ->assertSee('Daftar Addendum Kontrak');

        $this->actingAs($pejabat)
            ->get(route('addendums.create', $contract))
            ->assertOk()
            ->assertSee('Buat Addendum Kontrak');

        $storeResponse = $this->actingAs($pejabat)->post(route('addendums.store', $contract), [
            'nomor_addendum' => 'ADD-001/SPK/2026',
            'tanggal_addendum' => '2026-04-20',
            'jenis_addendum' => KontrakAddendum::TYPE_TAMBAH_KURANG_NILAI,
            'keterangan_alasan' => 'Penyesuaian nilai kontrak berdasarkan kebutuhan lapangan.',
            'nilai_kontrak_baru' => 120000,
            'action' => 'draft',
        ]);

        $addendum = KontrakAddendum::query()->firstOrFail();

        $storeResponse->assertRedirect(route('addendums.show', [$contract, $addendum]));
        $this->assertDatabaseHas('kontrak_addendum', [
            'id' => $addendum->id,
            'kontrak_pengadaan_id' => $contract->id,
            'nomor_addendum' => 'ADD-001/SPK/2026',
            'status_addendum' => KontrakAddendum::STATUS_DRAFT,
            'status_proses' => null,
            'nilai_kontrak_lama' => 100000,
            'nilai_kontrak_baru' => 120000,
        ]);

        $this->actingAs($pejabat)
            ->get(route('addendums.show', [$contract, $addendum]))
            ->assertOk()
            ->assertSee('Workspace detail addendum');

        $this->actingAs($pejabat)
            ->post(route('addendums.submit', [$contract, $addendum]))
            ->assertSessionHas('success');

        $addendum->refresh();
        $this->assertSame(KontrakAddendum::STATUS_SUBMITTED, $addendum->status_workflow);

        $approveResponse = $this->actingAs($ppk)->post(route('addendums.approve', [$contract, $addendum]), [
            'approval_note' => 'Disetujui untuk menyesuaikan nilai kontrak aktif.',
        ]);

        $approveResponse->assertRedirect(route('addendums.show', [$contract, $addendum]));

        $contract->refresh();
        $addendum->refresh();
        $termin->refresh();

        $this->assertSame(KontrakAddendum::STATUS_APPROVED, $addendum->status_workflow);
        $this->assertSame(120000.0, (float) $contract->nilai_total_kontrak);
        $this->assertSame(100000.0, (float) $termin->nilai_bruto_termin, 'Termin existing tidak boleh berubah otomatis.');
        $this->assertDatabaseHas('log_status_dokumen', [
            'dokumen_type' => KontrakAddendum::class,
            'dokumen_id' => $addendum->id,
            'status_baru' => KontrakAddendum::STATUS_APPROVED,
            'aksi' => 'APPROVE_ADDENDUM',
        ]);
    }

    public function test_submitted_addendum_can_be_rejected_without_updating_contract(): void
    {
        Role::findOrCreate('Pejabat Pengadaan', 'web');
        Role::findOrCreate('PPK', 'web');

        $pejabat = User::factory()->create();
        $pejabat->assignRole('Pejabat Pengadaan');

        $ppk = User::factory()->create();
        $ppk->assignRole('PPK');

        $contract = $this->createContract($ppk, 200000);

        $storeResponse = $this->actingAs($pejabat)->post(route('addendums.store', $contract), [
            'nomor_addendum' => 'ADD-002/SPK/2026',
            'tanggal_addendum' => '2026-04-21',
            'jenis_addendum' => KontrakAddendum::TYPE_PERPANJANGAN_WAKTU,
            'keterangan_alasan' => 'Penyesuaian jadwal kerja karena kondisi lapangan.',
            'tanggal_selesai_baru' => '2026-06-15',
            'jangka_waktu_baru' => 45,
            'action' => 'submit',
        ]);

        $addendum = KontrakAddendum::query()->firstOrFail();
        $storeResponse->assertRedirect(route('addendums.show', [$contract, $addendum]));

        $addendum->refresh();
        $this->assertSame(KontrakAddendum::STATUS_SUBMITTED, $addendum->status_workflow);

        $this->actingAs($ppk)->post(route('addendums.reject', [$contract, $addendum]), [
            'rejection_note' => 'Mohon lengkapi justifikasi teknis dan dasar penyesuaian waktunya.',
        ])->assertSessionHas('success');

        $contract->refresh();
        $addendum->refresh();

        $this->assertSame(KontrakAddendum::STATUS_REJECTED, $addendum->status_workflow);
        $this->assertSame(200000.0, (float) $contract->nilai_total_kontrak);
        $this->assertDatabaseHas('log_status_dokumen', [
            'dokumen_type' => KontrakAddendum::class,
            'dokumen_id' => $addendum->id,
            'status_baru' => KontrakAddendum::STATUS_REJECTED,
            'aksi' => 'REJECT_ADDENDUM',
        ]);
    }

    private function createContract(User $ppk, float $nilaiTotalKontrak): KontrakPengadaan
    {
        $coaId = DB::table('master_coas')->insertGetId([
            'kode_mak_lengkap' => '001.01.01.0001',
            'nama_akun' => 'Belanja Kontrak Pengadaan',
            'jenis_akun' => 'BELANJA',
            'status_aktif' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $dipaId = DB::table('master_dipas')->insertGetId([
            'nomor_dipa' => 'DIPA-2026-001',
            'tahun_anggaran' => 2026,
            'tanggal_disahkan' => '2026-01-02',
            'revisi_aktif_ke' => 1,
            'status_aktif' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $revisionId = DB::table('dipa_revisions')->insertGetId([
            'master_dipa_id' => $dipaId,
            'nomor_revisi' => 1,
            'tanggal_revisi' => '2026-01-02',
            'total_pagu' => 1000000,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $itemId = DB::table('dipa_revision_items')->insertGetId([
            'dipa_revision_id' => $revisionId,
            'coa_id' => $coaId,
            'nilai_pagu' => 1000000,
            'status_aktif' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $vendorId = DB::table('master_pihak')->insertGetId([
            'kategori' => 'PENGELUARAN',
            'jenis_entitas' => 'BADAN_USAHA',
            'nama_pihak' => 'PT Vendor Pengadaan',
            'status_aktif' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $contract = KontrakPengadaan::create([
            'vendor_id' => $vendorId,
            'ppk_user_id' => $ppk->id,
            'master_dipa_id' => $dipaId,
            'dipa_revision_item_id' => $itemId,
            'nomor_spk' => 'SPK-TEST-' . uniqid(),
            'tanggal_spk' => '2026-04-01',
            'nomor_spmk' => 'SPMK-TEST-' . uniqid(),
            'tanggal_spmk' => '2026-04-02',
            'nama_pekerjaan' => 'Pengadaan Barang Uji Addendum',
            'nilai_total_kontrak' => $nilaiTotalKontrak,
            'metode_pembayaran' => 'LUMPSUM',
            'ada_uang_muka' => false,
            'nilai_uang_muka' => 0,
            'sisa_uang_muka_belum_lunas' => 0,
            'jangka_waktu' => 30,
            'satuan_waktu' => 'HARI',
            'tanggal_mulai' => '2026-04-03',
            'tanggal_selesai' => '2026-05-03',
            'masa_pemeliharaan_hari' => 0,
            'status_kontrak' => 'AKTIF',
        ]);

        KontrakTermin::create([
            'kontrak_pengadaan_id' => $contract->id,
            'jenis_termin' => 'PELUNASAN',
            'termin_ke' => 1,
            'keterangan_termin' => 'Pelunasan Sekaligus',
            'persentase' => 100,
            'nilai_bruto_termin' => $nilaiTotalKontrak,
            'potongan_angsuran_uang_muka' => 0,
            'status_termin' => 'READY_TO_BILL',
        ]);

        return $contract;
    }
}
