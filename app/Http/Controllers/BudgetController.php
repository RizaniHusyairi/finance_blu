<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use App\Models\MasterCoa;
use App\Models\MasterDipa;
use App\Models\RiwayatRevisiDipa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BudgetController extends Controller
{
    public function index()
    {
        $budgets = Budget::with(['coa', 'dipaRevision.masterDipa', 'realisasi'])->get();

        $totalPagu = $budgets->sum('initial_budget');
        $totalRealisasi = $budgets->sum('realized_budget');
        $sisaPagu = $budgets->sum('remaining_budget');
        $persenSerapan = $totalPagu > 0 ? round(($totalRealisasi / $totalPagu) * 100, 1) : 0;

        return view('budgets.index', compact('budgets', 'totalPagu', 'totalRealisasi', 'sisaPagu', 'persenSerapan'));
    }

    public function create()
    {
        $existingCoas = MasterCoa::pluck('kode_mak_lengkap')->toArray();

        return view('budgets.create', compact('existingCoas'));
    }

    public function store(Request $request)
    {
        $validated = $this->validateBudget($request);
        $coa = $this->buildCoa($validated);

        if (MasterCoa::where('kode_mak_lengkap', $coa)->exists()) {
            return back()->withInput()->withErrors(['coa' => 'Kombinasi kode COA "' . $coa . '" sudah digunakan.']);
        }

        DB::transaction(function () use ($validated, $request, $coa) {
            $dipa = $this->resolveOrCreateDipa((int) $validated['year'], $validated['status_pagu'] === 'Aktif');
            $revision = $this->resolveOrCreateActiveRevision($dipa);
            $coaModel = $this->createCoa($validated, $coa);

            Budget::create([
                'dipa_revision_id' => $revision->id,
                'coa_id' => $coaModel->id,
                'nilai_pagu' => $validated['initial_budget'],
                'status_aktif' => $validated['status_pagu'] === 'Aktif',
            ]);

            $revision->update([
                'keterangan' => $validated['catatan'] ?? $revision->keterangan,
                'total_pagu' => (float) $revision->items()->sum('nilai_pagu'),
            ]);

            if ($request->has('simpan_draft')) {
                $dipa->update(['status_aktif' => false]);
            }
        });

        return redirect()->route('budgets.index')->with('success', 'Pagu anggaran berhasil ditambahkan.');
    }

    public function show(Budget $budget)
    {
        $budget->load(['coa', 'dipaRevision.masterDipa', 'realisasi']);

        $pagu = $budget->initial_budget;
        $realisasi = $budget->realized_budget;
        $sisa = $budget->remaining_budget;
        $persen = $pagu > 0 ? round(($realisasi / $pagu) * 100, 2) : 0;

        if ($persen == 0) {
            $statusPenggunaan = 'Belum Terealisasi';
            $badgePenggunaan = 'secondary';
        } elseif ($persen < 80) {
            $statusPenggunaan = 'Sebagian';
            $badgePenggunaan = 'primary';
        } elseif ($persen < 100) {
            $statusPenggunaan = 'Hampir Habis';
            $badgePenggunaan = 'warning';
        } else {
            $statusPenggunaan = 'Habis';
            $badgePenggunaan = 'danger';
        }

        $progressColor = $persen < 50 ? 'bg-success' : ($persen < 80 ? 'bg-warning' : 'bg-danger');

        return view('budgets.show', compact(
            'budget',
            'pagu',
            'realisasi',
            'sisa',
            'persen',
            'statusPenggunaan',
            'badgePenggunaan',
            'progressColor'
        ));
    }

    public function edit(Budget $budget)
    {
        $budget->load(['coa', 'dipaRevision.masterDipa', 'realisasi']);

        $existingCoas = MasterCoa::where('id', '!=', $budget->coa_id)->pluck('kode_mak_lengkap')->toArray();

        return view('budgets.edit', compact('budget', 'existingCoas'));
    }

    public function update(Request $request, Budget $budget)
    {
        $validated = $this->validateBudget($request);
        $coa = $this->buildCoa($validated);

        if (MasterCoa::where('kode_mak_lengkap', $coa)->where('id', '!=', $budget->coa_id)->exists()) {
            return back()->withInput()->withErrors(['coa' => 'Kombinasi kode COA "' . $coa . '" sudah digunakan.']);
        }

        DB::transaction(function () use ($validated, $budget, $coa) {
            $budget->load(['coa', 'dipaRevision.masterDipa', 'realisasi']);

            $coaModel = $budget->coa ?? new MasterCoa();
            $coaModel->fill([
                'kd_program' => $validated['program_code'],
                'kd_giat' => $validated['activity_code'],
                'kd_output' => $validated['output_code'],
                'kd_suboutput' => $validated['suboutput_code'],
                'kd_komponen' => $validated['component_code'],
                'kd_subkomponen' => $validated['subcomponent_code'],
                'kd_akun' => $validated['account_code'],
                'kd_item' => $validated['item_code'],
                'kode_mak_lengkap' => $coa,
                'nama_akun' => $validated['description'],
                'jenis_akun' => 'BELANJA',
                'status_aktif' => $validated['status_pagu'] === 'Aktif',
            ]);
            $coaModel->save();

            $budget->update([
                'coa_id' => $coaModel->id,
                'nilai_pagu' => $validated['initial_budget'],
                'status_aktif' => $validated['status_pagu'] === 'Aktif',
            ]);

            if ($budget->dipaRevision) {
                $budget->dipaRevision->update([
                    'keterangan' => $validated['catatan'] ?? $budget->dipaRevision->keterangan,
                    'total_pagu' => (float) $budget->dipaRevision->items()->sum('nilai_pagu'),
                ]);
            }

            if ($budget->dipaRevision?->masterDipa) {
                $budget->dipaRevision->masterDipa->update([
                    'tahun_anggaran' => $validated['year'],
                    'status_aktif' => $validated['status_pagu'] === 'Aktif',
                ]);
            }
        });

        return redirect()->route('budgets.index')->with('success', 'Pagu anggaran berhasil diperbarui.');
    }

    public function destroy(Budget $budget)
    {
        $revision = $budget->dipaRevision;
        $budget->delete();

        if ($revision) {
            $revision->update([
                'total_pagu' => (float) $revision->items()->sum('nilai_pagu'),
            ]);
        }

        return redirect()->route('budgets.index')->with('success', 'Budget deleted successfully.');
    }

    private function validateBudget(Request $request): array
    {
        return $request->validate([
            'year' => 'required|integer',
            'program_code' => 'required|string|max:50',
            'activity_code' => 'required|string|max:50',
            'output_code' => 'required|string|max:50',
            'suboutput_code' => 'required|string|max:50',
            'component_code' => 'required|string|max:50',
            'subcomponent_code' => 'required|string|max:50',
            'account_code' => 'required|string|max:50',
            'item_code' => 'required|string|max:50',
            'description' => 'required|string',
            'initial_budget' => 'required|numeric|min:1',
            'status_pagu' => 'required|string|in:Aktif,Nonaktif',
            'catatan' => 'nullable|string',
        ]);
    }

    private function buildCoa(array $validated): string
    {
        return implode('.', [
            $validated['program_code'],
            $validated['activity_code'],
            $validated['output_code'],
            $validated['suboutput_code'],
            $validated['component_code'],
            $validated['subcomponent_code'],
            $validated['account_code'],
            $validated['item_code'],
        ]);
    }

    private function resolveOrCreateDipa(int $year, bool $isActive): MasterDipa
    {
        return MasterDipa::firstOrCreate(
            ['nomor_dipa' => 'DIPA-BLU-' . $year],
            [
                'tahun_anggaran' => $year,
                'tanggal_disahkan' => now()->toDateString(),
                'revisi_aktif_ke' => 1,
                'status_aktif' => $isActive,
            ]
        );
    }

    private function resolveOrCreateActiveRevision(MasterDipa $dipa): RiwayatRevisiDipa
    {
        $revision = $dipa->activeRevision()->first();

        if ($revision) {
            return $revision;
        }

        return RiwayatRevisiDipa::create([
            'master_dipa_id' => $dipa->id,
            'nomor_revisi' => max(1, (int) $dipa->revisi_aktif_ke),
            'tanggal_revisi' => now()->toDateString(),
            'total_pagu' => 0,
            'keterangan' => 'Dibuat dari menu Pagu Anggaran.',
            'is_active' => true,
        ]);
    }

    private function createCoa(array $validated, string $coa): MasterCoa
    {
        return MasterCoa::create([
            'kd_program' => $validated['program_code'],
            'kd_giat' => $validated['activity_code'],
            'kd_output' => $validated['output_code'],
            'kd_suboutput' => $validated['suboutput_code'],
            'kd_komponen' => $validated['component_code'],
            'kd_subkomponen' => $validated['subcomponent_code'],
            'kd_akun' => $validated['account_code'],
            'kd_item' => $validated['item_code'],
            'kode_mak_lengkap' => $coa,
            'nama_akun' => $validated['description'],
            'jenis_akun' => 'BELANJA',
            'status_aktif' => $validated['status_pagu'] === 'Aktif',
        ]);
    }
}
