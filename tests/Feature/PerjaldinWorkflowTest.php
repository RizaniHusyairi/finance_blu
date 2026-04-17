<?php

namespace Tests\Feature;

use App\Models\Budget;
use App\Models\Tagihan;
use App\Models\User;
use App\Services\PerjaldinWorkflowService;
use Database\Seeders\SppPerjaldinWorkflowSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PerjaldinWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_perjaldin_requires_parallel_verifiers_before_kasubbag_final_approval(): void
    {
        $this->seed(SppPerjaldinWorkflowSeeder::class);

        $operator = User::factory()->create();
        $operator->assignRole('Operator Perjaldin');

        $verifiers = collect([
            'PPSPM',
            'Bendahara Penerimaan',
            'Bendahara Pengeluaran',
            'PPK',
            'Kepala Subbagian Keuangan dan Tata Usaha',
        ])->mapWithKeys(function (string $role) {
            Role::findOrCreate($role, 'web');
            $user = User::factory()->create();
            $user->assignRole($role);

            return [$role => $user];
        });

        $budget = Budget::create([
            'coa' => '524111',
            'description' => 'Belanja Perjaldin',
            'initial_budget' => 1000000,
            'year' => 2026,
        ]);

        $tagihan = Tagihan::create([
            'nomor_tagihan' => 'PJD-001',
            'tipe_tagihan' => 'PERJALDIN',
            'master_dipa_id' => $budget->dipaRevision->master_dipa_id,
            'dipa_revision_item_id' => $budget->id,
            'deskripsi' => 'Perjalanan dinas pengujian',
            'ppk_user_id' => $verifiers['PPK']->id,
            'ppk_nama_snapshot' => $verifiers['PPK']->name,
            'ppspm_user_id' => $verifiers['PPSPM']->id,
            'ppspm_nama_snapshot' => $verifiers['PPSPM']->name,
            'bendahara_penerimaan_user_id' => $verifiers['Bendahara Penerimaan']->id,
            'bendahara_penerimaan_nama_snapshot' => $verifiers['Bendahara Penerimaan']->name,
            'bendahara_pengeluaran_user_id' => $verifiers['Bendahara Pengeluaran']->id,
            'bendahara_pengeluaran_nama_snapshot' => $verifiers['Bendahara Pengeluaran']->name,
            'kasubbag_user_id' => $verifiers['Kepala Subbagian Keuangan dan Tata Usaha']->id,
            'kasubbag_nama_snapshot' => $verifiers['Kepala Subbagian Keuangan dan Tata Usaha']->name,
            'total_bruto' => 500000,
            'total_potongan' => 0,
            'total_netto' => 500000,
            'status' => 'DRAFT',
            'created_by' => $operator->id,
        ]);

        $service = app(PerjaldinWorkflowService::class);
        $instance = $service->submit($tagihan, $operator, '127.0.0.1');

        $this->assertSame('PENDING_VERIFIKASI_PERJALDIN', $tagihan->fresh()->status);
        $this->assertCount(5, $instance->fresh('approvals')->approvals);
        $this->assertSame(4, $instance->approvals()->where('urutan_step', 1)->where('status', 'PENDING')->count());
        $this->assertSame(1, $instance->approvals()->where('urutan_step', 2)->where('status', 'WAITING')->count());
        $this->assertSame($verifiers['PPSPM']->id, $instance->approvals()->where('role_code', 'PPSPM')->value('assigned_user_id'));
        $this->assertSame($verifiers['Bendahara Penerimaan']->id, $instance->approvals()->where('role_code', 'BENDAHARA_PENERIMAAN')->value('assigned_user_id'));
        $this->assertSame($verifiers['Bendahara Pengeluaran']->id, $instance->approvals()->where('role_code', 'BENDAHARA_PENGELUARAN')->value('assigned_user_id'));
        $this->assertSame($verifiers['PPK']->id, $instance->approvals()->where('role_code', 'PPK')->value('assigned_user_id'));
        $this->assertSame($verifiers['Kepala Subbagian Keuangan dan Tata Usaha']->id, $instance->approvals()->where('role_code', 'KASUBBAG')->value('assigned_user_id'));

        foreach (['PPSPM', 'BENDAHARA_PENERIMAAN', 'BENDAHARA_PENGELUARAN', 'PPK'] as $roleCode) {
            $approval = $instance->fresh('approvals')->approvals->firstWhere('role_code', $roleCode);
            $roleName = match ($roleCode) {
                'BENDAHARA_PENERIMAAN' => 'Bendahara Penerimaan',
                'BENDAHARA_PENGELUARAN' => 'Bendahara Pengeluaran',
                default => $roleCode,
            };

            $service->approve($approval, $verifiers[$roleName], 'Setuju', '127.0.0.1');
        }

        $this->assertSame('PENDING_KASUBBAG', $tagihan->fresh()->status);
        $this->assertSame(2, $instance->fresh()->step_saat_ini);

        $kasubbagApproval = $instance->fresh('approvals')->approvals->firstWhere('role_code', 'KASUBBAG');
        $service->approve($kasubbagApproval, $verifiers['Kepala Subbagian Keuangan dan Tata Usaha'], 'Final', '127.0.0.1');

        $this->assertSame('DISETUJUI_PERJALDIN', $tagihan->fresh()->status);
        $this->assertSame('APPROVED', $instance->fresh()->status);
    }
}
