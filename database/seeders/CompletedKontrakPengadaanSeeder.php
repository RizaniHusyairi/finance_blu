<?php

namespace Database\Seeders;

use App\Enums\JenisRekening;
use App\Models\ArsipDokumen;
use App\Models\BukuKasUmum;
use App\Models\DetailKontrak;
use App\Models\DokumenNpi;
use App\Models\DokumenSp2d;
use App\Models\DokumenSpm;
use App\Models\DokumenSpp;
use App\Models\KontrakPengadaan;
use App\Models\KontrakTermin;
use App\Models\LogStatusDokumen;
use App\Models\MasterDipa;
use App\Models\MasterPihak;
use App\Models\MasterTarifPajak;
use App\Models\PotonganTagihan;
use App\Models\RekeningBank;
use App\Models\StandingInstruction;
use App\Models\Tagihan;
use App\Models\User;
use App\Models\WorkflowApproval;
use App\Models\WorkflowDefinition;
use App\Models\WorkflowInstance;
use App\Services\BkuPostingService;
use App\Services\BudgetRealizationService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class CompletedKontrakPengadaanSeeder extends Seeder
{
    public function run(BkuPostingService $bkuPosting, BudgetRealizationService $budgetRealization): void
    {
        $context = $this->resolveContext();

        Auth::login($context['bendaharaPengeluaran']);

        try {
            foreach ($this->completedContracts() as $index => $payload) {
                [$tagihan, $sp2d] = DB::transaction(function () use ($payload, $index, $context) {
                    return $this->seedCompletedContract($payload, $index, $context);
                });

                $sp2d = $sp2d->fresh('npi.spm.spp.tagihan');
                $tagihan = $tagihan->fresh('spps.spm.npi.sp2d');

                $budgetRealization->recordFromSp2d($sp2d);

                $bku = $bkuPosting->postTagihanPengeluaran(
                    $tagihan,
                    $sp2d,
                    'Pembayaran tagihan kontrak pengadaan seeded setelah SP2D executed dan seluruh pajak tersetor.',
                    (float) $tagihan->total_bruto
                );

                BukuKasUmum::recalculateRunningBalance($bku->sumber_rekening_id);

                $this->command?->info("Seeded kontrak selesai: {$tagihan->nomor_tagihan} -> {$sp2d->nomor_sp2d} -> BKU #{$bku->id}");
            }
        } finally {
            Auth::logout();
        }
    }

    private function resolveContext(): array
    {
        $dipa = MasterDipa::with(['activeRevision.items.coa'])
            ->where('tahun_anggaran', 2026)
            ->where('status_aktif', true)
            ->first()
            ?: MasterDipa::with(['activeRevision.items.coa'])
                ->where('status_aktif', true)
                ->latest('tahun_anggaran')
                ->first();

        if (! $dipa || ! $dipa->activeRevision) {
            throw new RuntimeException('DIPA aktif beserta revisinya belum tersedia. Jalankan MasterDipaSeeder lebih dulu.');
        }

        $budgetItems = $dipa->activeRevision->items
            ->where('status_aktif', true)
            ->values();

        if ($budgetItems->count() < 3) {
            throw new RuntimeException('Minimal 3 item DIPA aktif dibutuhkan untuk CompletedKontrakPengadaanSeeder.');
        }

        $users = [
            'operator' => $this->firstUserByRole('Operator BLU'),
            'ppk' => $this->firstUserByRole('PPK'),
            'ppspm' => $this->firstUserByRole('PPSPM'),
            'kpa' => $this->firstUserByRole('KPA'),
            'kasubbag' => $this->firstUserByRole('Kepala Subbagian Keuangan dan Tata Usaha'),
            'koordinator' => $this->firstUserByRole('Koordinator Keuangan'),
            'bendaharaPenerimaan' => $this->firstUserByRole('Bendahara Penerimaan'),
            'bendaharaPengeluaran' => $this->firstUserByRole('Bendahara Pengeluaran'),
        ];

        $vendorCodes = collect($this->completedContracts())->pluck('vendor_code')->all();
        $vendors = MasterPihak::query()
            ->whereIn('kode_pihak', $vendorCodes)
            ->get()
            ->keyBy('kode_pihak');

        foreach ($vendorCodes as $code) {
            if (! $vendors->has($code)) {
                throw new RuntimeException("Vendor {$code} tidak ditemukan. Jalankan MasterPihakSeeder lebih dulu.");
            }
        }

        $taxes = MasterTarifPajak::query()
            ->whereIn('kode_pajak', ['PPN-WAPU', 'PPH22-BEND', 'PPH23-JASA', 'PPH4A2-KONST'])
            ->get()
            ->keyBy('kode_pajak');

        foreach (['PPN-WAPU', 'PPH22-BEND', 'PPH23-JASA', 'PPH4A2-KONST'] as $code) {
            if (! $taxes->has($code)) {
                throw new RuntimeException("Tarif pajak {$code} tidak ditemukan. Jalankan MasterTarifPajakSeeder lebih dulu.");
            }
        }

        $sourceAccount = $this->ensurePengeluaranAccount($users['bendaharaPengeluaran']);

        return array_merge($users, [
            'dipa' => $dipa,
            'budgetItems' => $budgetItems,
            'vendors' => $vendors,
            'taxes' => $taxes,
            'sourceAccount' => $sourceAccount,
        ]);
    }

    private function firstUserByRole(string $role): User
    {
        $user = User::role($role)->orderBy('id')->first();

        if (! $user) {
            throw new RuntimeException("User dengan role {$role} belum tersedia. Jalankan UserAccountSeeder lebih dulu.");
        }

        return $user;
    }

    private function seedCompletedContract(array $payload, int $index, array $context): array
    {
        $suffix = str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT);
        $now = now();
        $budgetItem = $context['budgetItems']->get($index);
        $vendor = $context['vendors']->get($payload['vendor_code']);
        $vendorAccount = $this->ensureVendorAccount($vendor, $payload, $index);
        $gross = (float) $payload['nilai'];
        $dpp = round($gross / 1.11, 2);
        $ppn = round($dpp * 0.11, 2);
        $pphRate = (float) $context['taxes']->get($payload['pph_tax'])->persentase;
        $pph = round($dpp * ($pphRate / 100), 2);
        $totalPotongan = round($ppn + $pph, 2);
        $netto = round($gross - $totalPotongan, 2);

        $contract = KontrakPengadaan::updateOrCreate(
            ['nomor_spk' => "SPK/SEED-KPG/{$suffix}/2026"],
            $this->attributesForTable('kontrak_pengadaan', [
                'vendor_id' => $vendor->id,
                'ppk_user_id' => $context['ppk']->id,
                'master_dipa_id' => $context['dipa']->id,
                'dipa_revision_item_id' => $budgetItem->id,
                'tanggal_spk' => $payload['tanggal_spk'],
                'nomor_spmk' => "SPMK/SEED-KPG/{$suffix}/2026",
                'tanggal_spmk' => $payload['tanggal_spmk'],
                'nama_pekerjaan' => $payload['pekerjaan'],
                'nomor_surat_undangan_pengadaan' => "UND/SEED-KPG/{$suffix}/2026",
                'nomor_ba_hasil_pengadaan' => "BAHP/SEED-KPG/{$suffix}/2026",
                'nilai_total_kontrak' => $gross,
                'metode_pembayaran' => 'LUMPSUM',
                'ada_uang_muka' => false,
                'nilai_uang_muka' => 0,
                'sisa_uang_muka_belum_lunas' => 0,
                'jangka_waktu' => $payload['jangka_waktu'],
                'satuan_waktu' => 'HARI',
                'tanggal_mulai' => $payload['tanggal_mulai'],
                'tanggal_selesai' => $payload['tanggal_selesai'],
                'masa_pemeliharaan_hari' => 30,
                'tanggal_mulai_pemeliharaan' => Carbon::parse($payload['tanggal_selesai'])->addDay()->toDateString(),
                'tanggal_selesai_pemeliharaan' => Carbon::parse($payload['tanggal_selesai'])->addDays(30)->toDateString(),
                'ketentuan_denda' => 'Denda keterlambatan sesuai ketentuan kontrak pengadaan.',
                'ketentuan_sanksi' => 'Sanksi administratif sesuai ketentuan kontrak.',
                'mata_uang' => 'IDR',
                'status_kontrak' => 'SELESAI',
                'diajukan_at' => Carbon::parse($payload['tanggal_spk'])->subDay(),
                'ppk_approved_at' => Carbon::parse($payload['tanggal_spk']),
                'ppk_approved_by' => $context['ppk']->id,
                'ppk_catatan' => 'Kontrak seed sudah disetujui PPK.',
                'created_at' => Carbon::parse($payload['tanggal_spk']),
                'updated_at' => $now,
            ])
        );

        $termin = KontrakTermin::updateOrCreate(
            [
                'kontrak_pengadaan_id' => $contract->id,
                'termin_ke' => 1,
            ],
            [
                'jenis_termin' => 'PELUNASAN',
                'keterangan_termin' => 'Pelunasan 100% pekerjaan',
                'persentase' => 100,
                'nilai_bruto_termin' => $gross,
                'potongan_angsuran_uang_muka' => 0,
                'nilai_retensi' => 0,
                'status_termin' => 'SUDAH_DITAGIH',
                'created_at' => Carbon::parse($payload['tanggal_selesai']),
                'updated_at' => $now,
            ]
        );

        $tagihan = Tagihan::updateOrCreate(
            ['nomor_tagihan' => "TAG-KPG-LUNAS-{$suffix}-2026"],
            $this->attributesForTable('tagihan', array_merge([
                'periode_bulan' => Carbon::parse($payload['tanggal_invoice'])->month,
                'periode_tahun' => 2026,
                'kota_ttd' => 'Samarinda',
                'tanggal_ttd' => $payload['tanggal_invoice'],
                'tipe_tagihan' => 'KONTRAK',
                'master_dipa_id' => $context['dipa']->id,
                'dipa_revision_item_id' => $budgetItem->id,
                'pihak_id' => $vendor->id,
                'deskripsi' => 'Tagihan pelunasan kontrak pengadaan: ' . $payload['pekerjaan'],
                'nama_supplier' => $vendor->nama_pihak,
                'total_bruto' => $gross,
                'total_potongan' => $totalPotongan,
                'total_netto' => $netto,
                'mekanisme_pembayaran' => 'LS_PIHAK_3',
                'status' => 'SELESAI',
                'kpa_approval_status' => 'APPROVED',
                'kpa_approved_at' => Carbon::parse($payload['tanggal_invoice'])->addHour(),
                'kpa_approved_by' => $context['kpa']->id,
                'kpa_approval_notes' => 'Tagihan seed disetujui KPA.',
                'created_by' => $context['operator']->id,
                'created_at' => Carbon::parse($payload['tanggal_invoice']),
                'updated_at' => $now,
            ], $this->verifierColumns($context)))
        );

        $detail = DetailKontrak::updateOrCreate(
            ['tagihan_id' => $tagihan->id],
            $this->attributesForTable('detail_kontrak', [
                'tanggal_invoice' => $payload['tanggal_invoice'],
                'nomor_invoice' => "INV/SEED-KPG/{$suffix}/2026",
                'kontrak_termin_id' => $termin->id,
                'nomor_bapp' => "BAPP/SEED-KPG/{$suffix}/2026",
                'tanggal_bapp' => $payload['tanggal_bapp'],
                'nomor_bast' => "BAST/SEED-KPG/{$suffix}/2026",
                'tanggal_bast' => $payload['tanggal_bast'],
                'nomor_bap' => "BAP/SEED-KPG/{$suffix}/2026",
                'tanggal_bap' => $payload['tanggal_bap'],
                'nama_pemeriksa' => 'Tim Pemeriksa Barang/Jasa',
                'nip_pemeriksa' => '198801012020121001',
                'jabatan_pemeriksa' => 'Pemeriksa Hasil Pekerjaan',
                'wa_pemeriksa' => '628123000' . $suffix,
                'created_at' => Carbon::parse($payload['tanggal_bapp']),
                'updated_at' => $now,
            ])
        );

        $this->syncContractArchives($detail, $suffix, $context['operator']);
        $this->syncTaxDeductions($tagihan, $context['taxes'], $budgetItem->coa_id, $suffix, $dpp, $ppn, $pph, $payload['pph_tax'], $context['bendaharaPengeluaran']);

        $spp = DokumenSpp::updateOrCreate(
            ['nomor_spp' => "SPP-BLU-KPG-LUNAS-{$suffix}-2026"],
            $this->attributesForTable('dokumen_spp', [
                'tagihan_id' => $tagihan->id,
                'dipa_revision_item_id' => $budgetItem->id,
                'kategori_pembayaran' => 'SP2D BLU - TRF',
                'jenis_tagihan' => 'NON REMUNERASI',
                'nominal_spp' => $netto,
                'tanggal_spp' => $payload['tanggal_spp'],
                'status' => 'APPROVED',
                'kpa_approval_status' => 'APPROVED',
                'kpa_approved_at' => Carbon::parse($payload['tanggal_spp'])->addHour(),
                'kpa_approved_by' => $context['kpa']->id,
                'kpa_approval_notes' => 'SPP seed disetujui lengkap.',
                'dibuat_oleh_id' => $context['operator']->id,
                'ppk_verifikator_id' => $context['ppk']->id,
                'created_at' => Carbon::parse($payload['tanggal_spp']),
                'updated_at' => $now,
            ])
        );

        StandingInstruction::updateOrCreate(
            ['dokumen_spp_id' => $spp->id],
            [
                'nomor_surat' => "SI/SEED-KPG/{$suffix}/2026",
                'tanggal_surat' => $payload['tanggal_spp'],
                'ppk_user_id' => $context['ppk']->id,
                'kpa_user_id' => $context['kpa']->id,
                'nama_ppk_snapshot' => $context['ppk']->name,
                'jabatan_ppk_snapshot' => $context['ppk']->pegawai?->jabatan,
                'nama_kpa_snapshot' => $context['kpa']->name,
                'jabatan_kpa_snapshot' => $context['kpa']->pegawai?->jabatan,
                'rekening_sumber_nomor' => $context['sourceAccount']->nomor_rekening,
                'rekening_sumber_nama' => $context['sourceAccount']->nama_rekening,
                'rekening_sumber_bank' => $context['sourceAccount']->nama_bank,
                'rekening_tujuan_nomor' => $vendorAccount->nomor_rekening,
                'rekening_tujuan_nama' => $vendorAccount->nama_rekening,
                'rekening_tujuan_bank' => $vendorAccount->nama_bank,
                'nominal_transfer' => $netto,
                'nominal_terbilang' => null,
                'uraian_penggunaan' => 'Pembayaran pelunasan ' . $payload['pekerjaan'],
                'status' => 'FINAL',
                'dibuat_oleh_id' => $context['operator']->id,
                'difinalkan_oleh_id' => $context['ppk']->id,
                'finalized_at' => Carbon::parse($payload['tanggal_spp'])->addHours(2),
            ]
        );

        $spm = DokumenSpm::updateOrCreate(
            ['nomor_spm' => "SPM-BLU-KPG-LUNAS-{$suffix}-2026"],
            $this->attributesForTable('dokumen_spm', [
                'spp_id' => $spp->id,
                'tanggal_spm' => $payload['tanggal_spm'],
                'ppspm_id' => $context['ppspm']->id,
                'dipa_revision_item_id' => $budgetItem->id,
                'tahun_anggaran' => '2026',
                'jenis_tagihan' => 'NON REMUNERASI',
                'jatuh_tempo' => 'Segera',
                'cara_bayar' => 'SP2D BLU - TRF',
                'nominal_spm' => $netto,
                'dibuat_oleh_id' => $context['operator']->id,
                'status' => DokumenSpm::STATUS_SPM_TERBIT,
                'created_at' => Carbon::parse($payload['tanggal_spm']),
                'updated_at' => $now,
            ])
        );

        $npi = DokumenNpi::updateOrCreate(
            ['nomor_npi' => "NPI-BLU-KPG-LUNAS-{$suffix}-2026"],
            $this->attributesForTable('dokumen_npi', [
                'spm_id' => $spm->id,
                'tanggal_npi' => $payload['tanggal_npi'],
                'bendahara_penerimaan_id' => $context['bendaharaPenerimaan']->id,
                'status' => DokumenNpi::STATUS_NPI_TERBIT,
                'created_at' => Carbon::parse($payload['tanggal_npi']),
                'updated_at' => $now,
            ])
        );

        $sp2d = DokumenSp2d::updateOrCreate(
            ['nomor_sp2d' => "SP2D-BLU-KPG-LUNAS-{$suffix}-2026"],
            $this->attributesForTable('dokumen_sp2d', [
                'npi_id' => $npi->id,
                'tanggal_sp2d' => $payload['tanggal_sp2d'],
                'bendahara_pengeluaran_id' => $context['bendaharaPengeluaran']->id,
                'status' => DokumenSp2d::STATUS_EXECUTED,
                'created_at' => Carbon::parse($payload['tanggal_sp2d']),
                'updated_at' => $now,
            ])
        );

        $this->syncPaymentArchives($spp, $spm, $sp2d, $suffix, $context['bendaharaPengeluaran']);
        $this->syncApprovedWorkflows($tagihan, $spp, $spm, $npi, $sp2d, $context, Carbon::parse($payload['tanggal_sp2d'])->subDay());
        $this->syncStatusLogs($tagihan, $spp, $spm, $npi, $sp2d, $context);

        return [$tagihan, $sp2d];
    }

    private function syncTaxDeductions(
        Tagihan $tagihan,
        Collection $taxes,
        int $akunPotonganId,
        string $suffix,
        float $dpp,
        float $ppn,
        float $pph,
        string $pphTaxCode,
        User $bendaharaPengeluaran
    ): void {
        $rows = [
            [
                'tax' => $taxes->get('PPN-WAPU'),
                'nominal' => $ppn,
                'billing' => "820260{$suffix}001",
                'ntpn' => "NTPN260{$suffix}PPN",
                'description' => 'Penyetoran PPN Wapu Bendaharawan kontrak pengadaan.',
            ],
            [
                'tax' => $taxes->get($pphTaxCode),
                'nominal' => $pph,
                'billing' => "820260{$suffix}002",
                'ntpn' => "NTPN260{$suffix}PPH",
                'description' => 'Penyetoran PPh kontrak pengadaan.',
            ],
        ];

        $expectedTaxIds = collect($rows)->pluck('tax.id')->all();

        $tagihan->potonganTagihan()
            ->where('jenis_potongan', 'PAJAK')
            ->whereNotIn('pajak_id', $expectedTaxIds)
            ->get()
            ->each(fn (PotonganTagihan $potongan) => $potongan->delete());

        foreach ($rows as $row) {
            $tax = $row['tax'];
            $potongan = PotonganTagihan::withTrashed()->updateOrCreate(
                [
                    'tagihan_id' => $tagihan->id,
                    'pajak_id' => $tax->id,
                    'jenis_potongan' => 'PAJAK',
                ],
                [
                    'akun_potongan_id' => $akunPotonganId,
                    'deskripsi' => $row['description'],
                    'dpp' => $dpp,
                    'persentase_tarif_snapshot' => $tax->persentase,
                    'nama_pajak_snapshot' => $tax->jenis_pajak,
                    'nominal_potongan' => $row['nominal'],
                    'kode_billing' => $row['billing'],
                    'ntpn' => $row['ntpn'],
                    'deleted_at' => null,
                ]
            );

            $this->upsertArchive($potongan, 'KODE_BILLING', "seeded/kontrak-pajak/kode-billing-{$suffix}-{$tax->kode_pajak}.pdf", $bendaharaPengeluaran);
            $this->upsertArchive($potongan, 'BUKTI_SETOR_PAJAK', "seeded/kontrak-pajak/bpn-{$suffix}-{$tax->kode_pajak}.pdf", $bendaharaPengeluaran);
            $this->upsertArchive($potongan, 'BPPU', "seeded/kontrak-pajak/bppu-{$suffix}-{$tax->kode_pajak}.pdf", $bendaharaPengeluaran);

            $this->upsertLog(
                $potongan,
                $bendaharaPengeluaran,
                null,
                'SUDAH_SETOR',
                'INPUT_NTPN',
                'Kode billing dan NTPN pajak seed sudah terisi.'
            );
        }
    }

    private function syncContractArchives(DetailKontrak $detail, string $suffix, User $actor): void
    {
        foreach ([
            'BAPP_FINAL_TTD' => 'bapp',
            'BAST_FINAL_TTD' => 'bast',
            'BAP_FINAL_TTD' => 'bap',
            'INVOICE' => 'invoice',
            'KWITANSI' => 'kwitansi',
            'FAKTUR_PAJAK' => 'faktur-pajak',
        ] as $jenis => $file) {
            $this->upsertArchive($detail, $jenis, "seeded/kontrak/{$file}-{$suffix}.pdf", $actor);
        }
    }

    private function syncPaymentArchives(DokumenSpp $spp, DokumenSpm $spm, DokumenSp2d $sp2d, string $suffix, User $actor): void
    {
        $this->upsertArchive($spp, DokumenSpp::SPP_SIGNED_ARCHIVE_TYPE, "seeded/spp/spp-bertandatangan-{$suffix}.pdf", $actor);
        $this->upsertArchive($spp, DokumenSpp::STANDING_INSTRUCTION_SIGNED_ARCHIVE_TYPE, "seeded/spp/standing-instruction-{$suffix}.pdf", $actor);
        $this->upsertArchive($spm, DokumenSpm::SPM_SIGNED_ARCHIVE_TYPE, "seeded/spm/spm-bertandatangan-{$suffix}.pdf", $actor);
        $this->upsertArchive($sp2d, DokumenSp2d::SP2D_SIGNED_ARCHIVE_TYPE, "seeded/sp2d/sp2d-bertandatangan-{$suffix}.pdf", $actor);
        $this->upsertArchive($sp2d, 'BUKTI_TRANSFER_SP2D', "seeded/sp2d/bukti-transfer-{$suffix}.pdf", $actor);
    }

    private function syncApprovedWorkflows(
        Tagihan $tagihan,
        DokumenSpp $spp,
        DokumenSpm $spm,
        DokumenNpi $npi,
        DokumenSp2d $sp2d,
        array $context,
        Carbon $actedAt
    ): void {
        $this->seedApprovedWorkflow('TAGIHAN_KONTRAK_VERIFIKATOR', $tagihan, $context, $actedAt);
        $this->seedApprovedWorkflow('SPP_KONTRAK_PPK', $spp, $context, $actedAt->copy()->addHour());
        $this->seedApprovedWorkflow('SPM_KONTRAK_PPSPM', $spm, $context, $actedAt->copy()->addHours(2));
        $this->seedApprovedWorkflow('NPI_KONTRAK', $npi, $context, $actedAt->copy()->addHours(3));
        $this->seedApprovedWorkflow('SP2D_KONTRAK', $sp2d, $context, $actedAt->copy()->addHours(4));
    }

    private function seedApprovedWorkflow(string $code, Model $document, array $context, Carbon $actedAt): void
    {
        $definition = WorkflowDefinition::with('steps')
            ->where('kode', $code)
            ->where('status_aktif', true)
            ->first();

        if (! $definition) {
            throw new RuntimeException("Workflow definition {$code} tidak ditemukan.");
        }

        $instance = WorkflowInstance::updateOrCreate(
            [
                'workflow_definition_id' => $definition->id,
                'workflowable_type' => get_class($document),
                'workflowable_id' => $document->getKey(),
            ],
            [
                'step_saat_ini' => (int) $definition->steps->max('urutan_step'),
                'status' => 'APPROVED',
                'created_at' => $actedAt->copy()->subHours(2),
                'updated_at' => $actedAt,
            ]
        );

        foreach ($definition->steps as $step) {
            $assignee = $this->userForWorkflowRole($step->role_code, $context);

            WorkflowApproval::updateOrCreate(
                [
                    'workflow_instance_id' => $instance->id,
                    'urutan_step' => $step->urutan_step,
                    'role_code' => $step->role_code,
                ],
                [
                    'nama_step' => $step->nama_step,
                    'assigned_user_id' => $assignee?->id,
                    'acted_by_user_id' => $assignee?->id ?? $context['operator']->id,
                    'status' => 'APPROVED',
                    'catatan' => 'Disetujui otomatis oleh seeder kontrak selesai.',
                    'acted_at' => $actedAt,
                    'ip_address' => '127.0.0.1',
                    'created_at' => $actedAt->copy()->subHour(),
                    'updated_at' => $actedAt,
                ]
            );
        }
    }

    private function userForWorkflowRole(string $roleCode, array $context): ?User
    {
        return match ($roleCode) {
            'PPK' => $context['ppk'],
            'PPSPM' => $context['ppspm'],
            'KPA', 'PLT/PLH' => $context['kpa'],
            'KASUBBAG', 'Kepala Subbagian Keuangan dan Tata Usaha' => $context['kasubbag'],
            'KOORDINATOR_KEUANGAN', 'Koordinator Keuangan' => $context['koordinator'],
            'BENDAHARA_PENERIMAAN', 'Bendahara Penerimaan' => $context['bendaharaPenerimaan'],
            'BENDAHARA_PENGELUARAN', 'Bendahara Pengeluaran' => $context['bendaharaPengeluaran'],
            default => null,
        };
    }

    private function syncStatusLogs(
        Tagihan $tagihan,
        DokumenSpp $spp,
        DokumenSpm $spm,
        DokumenNpi $npi,
        DokumenSp2d $sp2d,
        array $context
    ): void {
        $this->upsertLog($tagihan, $context['kasubbag'], 'PROSES_VERIFIKASI', 'READY_FOR_SPP', 'VERIFIKASI_TAGIHAN_SELESAI', 'Tagihan kontrak seed sudah diverifikasi semua verifikator.');
        $this->upsertLog($tagihan, $context['bendaharaPengeluaran'], 'SP2D_TERBIT', 'SELESAI', 'SP2D_FINAL', 'SP2D executed dan tagihan seed selesai.');
        $this->upsertLog($spp, $context['ppk'], 'Menunggu Verifikasi', 'APPROVED', 'APPROVE_SPP', 'SPP kontrak seed disetujui semua verifikator.');
        $this->upsertLog($spm, $context['ppspm'], DokumenSpm::STATUS_MENUNGGU_VERIFIKASI, DokumenSpm::STATUS_SPM_TERBIT, 'SPM_TERBIT_TTE', 'SPM kontrak seed sudah terbit.');
        $this->upsertLog($npi, $context['bendaharaPenerimaan'], DokumenNpi::STATUS_MENUNGGU_VERIFIKASI, DokumenNpi::STATUS_NPI_TERBIT, 'NPI_TERBIT', 'NPI kontrak seed sudah terbit.');
        $this->upsertLog($sp2d, $context['bendaharaPengeluaran'], DokumenSp2d::STATUS_SP2D_TERBIT, DokumenSp2d::STATUS_EXECUTED, 'EXECUTE_PAYMENT', 'SP2D kontrak seed sudah executed.');
    }

    private function upsertArchive(Model $model, string $jenisDokumen, string $path, User $actor): void
    {
        ArsipDokumen::updateOrCreate(
            [
                'documentable_type' => get_class($model),
                'documentable_id' => $model->getKey(),
                'jenis_dokumen' => $jenisDokumen,
            ],
            [
                'nama_file_asli' => basename($path),
                'path_file' => $path,
                'disk' => 'local',
                'mime_type' => 'application/pdf',
                'ukuran_file' => 1024,
                'checksum' => sha1($path),
                'uploaded_by' => $actor->id,
                'uploaded_at' => now(),
                'keterangan' => 'Dokumen dummy dari CompletedKontrakPengadaanSeeder.',
                'is_active' => true,
            ]
        );
    }

    private function upsertLog(Model $model, User $actor, ?string $oldStatus, string $newStatus, string $action, ?string $note = null): void
    {
        LogStatusDokumen::updateOrCreate(
            [
                'dokumen_type' => get_class($model),
                'dokumen_id' => $model->getKey(),
                'aksi' => $action,
            ],
            [
                'user_id' => $actor->id,
                'role_saat_itu' => $actor->getRoleNames()->first() ?? 'SYSTEM',
                'status_sebelumnya' => $oldStatus,
                'status_baru' => $newStatus,
                'catatan' => $note,
                'ip_address' => '127.0.0.1',
            ]
        );
    }

    private function verifierColumns(array $context): array
    {
        return [
            'ppk_user_id' => $context['ppk']->id,
            'ppk_nama_snapshot' => $context['ppk']->name,
            'ppk_nip_snapshot' => $context['ppk']->pegawai?->nip,
            'ppspm_user_id' => $context['ppspm']->id,
            'ppspm_nama_snapshot' => $context['ppspm']->name,
            'ppspm_nip_snapshot' => $context['ppspm']->pegawai?->nip,
            'bendahara_penerimaan_user_id' => $context['bendaharaPenerimaan']->id,
            'bendahara_penerimaan_nama_snapshot' => $context['bendaharaPenerimaan']->name,
            'bendahara_penerimaan_nip_snapshot' => $context['bendaharaPenerimaan']->pegawai?->nip,
            'bendahara_pengeluaran_user_id' => $context['bendaharaPengeluaran']->id,
            'bendahara_pengeluaran_nama_snapshot' => $context['bendaharaPengeluaran']->name,
            'bendahara_pengeluaran_nip_snapshot' => $context['bendaharaPengeluaran']->pegawai?->nip,
            'kasubbag_user_id' => $context['kasubbag']->id,
            'kasubbag_nama_snapshot' => $context['kasubbag']->name,
            'kasubbag_nip_snapshot' => $context['kasubbag']->pegawai?->nip,
            'koordinator_keuangan_user_id' => $context['koordinator']->id,
            'koordinator_keuangan_nama_snapshot' => $context['koordinator']->name,
            'koordinator_keuangan_nip_snapshot' => $context['koordinator']->pegawai?->nip,
        ];
    }

    private function ensurePengeluaranAccount(User $bendaharaPengeluaran): RekeningBank
    {
        return RekeningBank::updateOrCreate(
            [
                'pemilik_type' => User::class,
                'pemilik_id' => $bendaharaPengeluaran->id,
                'nomor_rekening' => '777000260001',
            ],
            [
                'nama_bank' => 'Bank Operasional BLU',
                'nama_rekening' => 'Bendahara Pengeluaran BLU',
                'kode_bank' => '777',
                'jenis_rekening' => JenisRekening::PENGELUARAN->value,
                'saldo_awal' => 5000000000,
                'saldo_awal_per_tanggal' => '2026-01-01',
                'is_default' => true,
                'status_aktif' => true,
            ]
        );
    }

    private function ensureVendorAccount(MasterPihak $vendor, array $payload, int $index): RekeningBank
    {
        $existing = $vendor->rekening()
            ->where('status_aktif', true)
            ->orderByDesc('is_default')
            ->orderBy('id')
            ->first();

        if ($existing) {
            return $existing;
        }

        return RekeningBank::create([
            'pemilik_type' => MasterPihak::class,
            'pemilik_id' => $vendor->id,
            'nama_bank' => $payload['bank'] ?? 'Bank Mandiri',
            'nomor_rekening' => '880026000' . ($index + 1),
            'nama_rekening' => $vendor->nama_pihak,
            'kode_bank' => $payload['kode_bank'] ?? '008',
            'jenis_rekening' => JenisRekening::LAINNYA->value,
            'saldo_awal' => 0,
            'saldo_awal_per_tanggal' => null,
            'is_default' => true,
            'status_aktif' => true,
        ]);
    }

    private function attributesForTable(string $table, array $attributes): array
    {
        $columns = array_flip(Schema::getColumnListing($table));

        return array_intersect_key($attributes, $columns);
    }

    private function completedContracts(): array
    {
        return [
            [
                'vendor_code' => 'VND-003',
                'pekerjaan' => 'Pengadaan peralatan klinik gawat darurat',
                'nilai' => 125000000,
                'pph_tax' => 'PPH22-BEND',
                'tanggal_spk' => '2026-02-03',
                'tanggal_spmk' => '2026-02-05',
                'tanggal_mulai' => '2026-02-06',
                'tanggal_selesai' => '2026-03-20',
                'tanggal_bapp' => '2026-03-21',
                'tanggal_bast' => '2026-03-22',
                'tanggal_bap' => '2026-03-23',
                'tanggal_invoice' => '2026-03-24',
                'tanggal_spp' => '2026-03-26',
                'tanggal_spm' => '2026-03-30',
                'tanggal_npi' => '2026-04-01',
                'tanggal_sp2d' => '2026-04-03',
                'jangka_waktu' => 44,
            ],
            [
                'vendor_code' => 'VND-005',
                'pekerjaan' => 'Pengadaan lisensi dan perangkat jaringan operasional',
                'nilai' => 187500000,
                'pph_tax' => 'PPH23-JASA',
                'tanggal_spk' => '2026-03-04',
                'tanggal_spmk' => '2026-03-06',
                'tanggal_mulai' => '2026-03-07',
                'tanggal_selesai' => '2026-04-18',
                'tanggal_bapp' => '2026-04-19',
                'tanggal_bast' => '2026-04-20',
                'tanggal_bap' => '2026-04-21',
                'tanggal_invoice' => '2026-04-22',
                'tanggal_spp' => '2026-04-24',
                'tanggal_spm' => '2026-04-27',
                'tanggal_npi' => '2026-04-29',
                'tanggal_sp2d' => '2026-05-02',
                'jangka_waktu' => 42,
            ],
            [
                'vendor_code' => 'VND-010',
                'pekerjaan' => 'Pekerjaan pemeliharaan fasilitas teknik gedung operasional',
                'nilai' => 246000000,
                'pph_tax' => 'PPH4A2-KONST',
                'tanggal_spk' => '2026-03-24',
                'tanggal_spmk' => '2026-03-25',
                'tanggal_mulai' => '2026-03-26',
                'tanggal_selesai' => '2026-05-10',
                'tanggal_bapp' => '2026-05-11',
                'tanggal_bast' => '2026-05-12',
                'tanggal_bap' => '2026-05-13',
                'tanggal_invoice' => '2026-05-14',
                'tanggal_spp' => '2026-05-16',
                'tanggal_spm' => '2026-05-19',
                'tanggal_npi' => '2026-05-21',
                'tanggal_sp2d' => '2026-05-24',
                'jangka_waktu' => 46,
            ],
        ];
    }
}
