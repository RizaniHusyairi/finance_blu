<?php

namespace Tests\Feature;

use App\Models\KontrakAddendum;
use App\Models\KontrakPengadaan;
use App\Models\KontrakTermin;
use App\Models\User;
use App\Support\ContractDocumentTte;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
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

    public function test_final_doc_upload_blocked_before_approval_and_works_after(): void
    {
        Role::findOrCreate('Pejabat Pengadaan', 'web');
        Role::findOrCreate('PPK', 'web');

        $ppk = User::factory()->create();
        $ppk->assignRole('PPK');
        $pejabat = User::factory()->create();
        $pejabat->assignRole('Pejabat Pengadaan');

        Storage::fake('local');

        $contract = $this->createContract($ppk, 100000);
        $pdf = fn () => UploadedFile::fake()->create('spk_final.pdf', 200, 'application/pdf');

        // KP-08 — sebelum disetujui PPK (DRAFT): upload final DITOLAK (tanpa error 500),
        // tanpa menyimpan arsip, status tak berubah.
        $contract->update(['status_kontrak' => 'DRAFT', 'ppk_approved_at' => null]);
        $this->actingAs($pejabat)
            ->post(route('contracts.spk.upload-final', $contract), ['file_spk_final_ttd' => $pdf()])
            ->assertSessionHas('error');
        $this->assertDatabaseMissing('arsip_dokumen', [
            'documentable_id' => $contract->id,
            'jenis_dokumen' => 'SPK_FINAL_TTD',
        ]);
        $this->assertSame('DRAFT', $contract->fresh()->status_kontrak);

        // KP-01 — setelah disetujui PPK (AKTIF): upload final BERHASIL tanpa error 500
        // (activateIfDocumentsComplete kini terdefinisi); kontrak tetap AKTIF.
        $contract->update(['status_kontrak' => 'AKTIF', 'ppk_approved_at' => now(), 'ppk_approved_by' => $ppk->id]);
        $this->actingAs($pejabat)
            ->post(route('contracts.spk.upload-final', $contract), ['file_spk_final_ttd' => $pdf()])
            ->assertSessionHas('success');
        $this->assertDatabaseHas('arsip_dokumen', [
            'documentable_id' => $contract->id,
            'jenis_dokumen' => 'SPK_FINAL_TTD',
            'is_active' => 1,
        ]);
        $this->assertSame('AKTIF', $contract->fresh()->status_kontrak);
    }

    public function test_assigned_ppk_can_approve_contract_submitted_by_pengadaan(): void
    {
        Role::findOrCreate('Pejabat Pengadaan', 'web');
        Role::findOrCreate('PPK', 'web');

        $ppk = User::factory()->create();
        $ppk->assignRole('PPK');
        $pejabat = User::factory()->create();
        $pejabat->assignRole('Pejabat Pengadaan');

        $contract = $this->createContract($ppk, 100000);
        $contract->update(['status_kontrak' => 'DRAFT', 'ppk_approved_at' => null, 'diajukan_by' => null]);

        $this->actingAs($pejabat)->post(route('contracts.submit', $contract))->assertSessionHasNoErrors();
        $this->assertSame('PENDING_REVIEW', $contract->fresh()->status_kontrak);
        $this->assertSame($pejabat->id, (int) $contract->fresh()->diajukan_by);

        // PPK tertugas (≠ pengaju) boleh menyetujui.
        $this->actingAs($ppk)->post(route('contracts.approve', $contract));
        $this->assertSame('AKTIF', $contract->fresh()->status_kontrak);
    }

    public function test_contract_submitter_cannot_approve_own_contract_even_with_dual_role(): void
    {
        Role::findOrCreate('Pejabat Pengadaan', 'web');
        Role::findOrCreate('PPK', 'web');

        // Akun peran ganda yang sekaligus PPK tertugas pada kontrak.
        $dual = User::factory()->create();
        $dual->assignRole('PPK');
        $dual->assignRole('Pejabat Pengadaan');

        $contract = $this->createContract($dual, 100000);
        $contract->update(['status_kontrak' => 'DRAFT', 'ppk_approved_at' => null, 'diajukan_by' => null]);

        $this->actingAs($dual)->post(route('contracts.submit', $contract))->assertSessionHasNoErrors();
        $this->assertSame($dual->id, (int) $contract->fresh()->diajukan_by);

        // Pengaju = calon penyetuju → ditolak (KP-02/KP-03 maker != checker).
        $this->actingAs($dual)->post(route('contracts.approve', $contract))->assertForbidden();
        $this->assertSame('PENDING_REVIEW', $contract->fresh()->status_kontrak);
    }

    public function test_contract_tte_document_serves_frozen_artifact_immutably(): void
    {
        Role::findOrCreate('PPK', 'web');
        $ppk = User::factory()->create();
        $ppk->assignRole('PPK');

        Storage::fake('local');

        $contract = $this->createContract($ppk, 100000);
        $contract->update(['status_kontrak' => 'AKTIF', 'ppk_approved_at' => now(), 'ppk_approved_by' => $ppk->id]);
        $contract->refresh();

        // Bekukan artefak untuk hash saat ini (mensimulasikan freeze pada akses pertama).
        $hash = ContractDocumentTte::hash($contract, 'spk');
        $frozenBytes = '%PDF-1.4 ARTEFAK-BEKU-SPK';
        Storage::disk('local')->put(ContractDocumentTte::frozenPdfPath($contract, 'spk', $hash), $frozenBytes);

        $url = URL::signedRoute('public.contract-tte.document', [
            'type' => 'spk', 'id' => $contract->id, 'hash' => $hash,
        ]);

        // KP-06: sajikan artefak beku apa adanya (tanpa render ulang).
        $response = $this->get($url);
        $response->assertOk();
        $this->assertSame($frozenBytes, $response->streamedContent());

        // Nilai kontrak berubah → hash baru, tetapi URL lama (hash lama) TETAP
        // menyajikan artefak beku yang sama → imutabel.
        $contract->update(['nilai_total_kontrak' => 999000]);
        $this->assertNotSame($hash, ContractDocumentTte::hash($contract->fresh(), 'spk'));

        $response2 = $this->get($url);
        $response2->assertOk();
        $this->assertSame($frozenBytes, $response2->streamedContent());
    }

    public function test_contract_approval_warns_when_commitment_exceeds_active_dipa_pagu(): void
    {
        Role::findOrCreate('Pejabat Pengadaan', 'web');
        Role::findOrCreate('PPK', 'web');

        $ppk = User::factory()->create();
        $ppk->assignRole('PPK');
        $pejabat = User::factory()->create();
        $pejabat->assignRole('Pejabat Pengadaan');

        // Pagu DIPA aktif = 1.000.000 (dari createContract). Nilai kontrak melampauinya.
        $contract = $this->createContract($ppk, 1200000);
        $contract->update(['status_kontrak' => 'DRAFT', 'ppk_approved_at' => null, 'diajukan_by' => null]);

        $this->actingAs($pejabat)->post(route('contracts.submit', $contract));
        $this->actingAs($ppk)->post(route('contracts.approve', $contract))->assertSessionHas('warning');

        // KP-05 guardrail lunak: kontrak tetap aktif (tidak diblokir).
        $this->assertSame('AKTIF', $contract->fresh()->status_kontrak);
    }

    public function test_contracts_datatable_endpoint_returns_server_side_json(): void
    {
        Role::findOrCreate('Pejabat Pengadaan', 'web');
        Role::findOrCreate('PPK', 'web');

        $ppk = User::factory()->create();
        $ppk->assignRole('PPK');
        $operator = User::factory()->create();
        $operator->assignRole('Pejabat Pengadaan');

        $this->createContract($ppk, 100000); // 1 kontrak (AKTIF) + termin

        $response = $this->actingAs($operator)
            ->getJson(route('contracts.index-data') . '?draw=1&start=0&length=10');

        $response->assertOk();
        $response->assertJsonStructure(['draw', 'recordsTotal', 'recordsFiltered', 'data']);
        $this->assertSame(1, $response->json('recordsTotal'));
        $this->assertSame(1, $response->json('recordsFiltered'));
        $this->assertCount(1, $response->json('data'));
        $this->assertCount(6, $response->json('data.0')); // 6 sel HTML per baris

        // Pencarian server-side: kata kunci tak cocok → recordsFiltered 0, total tetap.
        $empty = $this->actingAs($operator)
            ->getJson(route('contracts.index-data') . '?draw=2&start=0&length=10&search[value]=ZZZ-TIDAK-ADA');
        $empty->assertOk();
        $this->assertSame(0, $empty->json('recordsFiltered'));
        $this->assertSame(1, $empty->json('recordsTotal'));
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
