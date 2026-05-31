<?php

namespace App\Http\Controllers;

use App\Enums\JenisRekening;
use App\Models\BukuKasUmum;
use App\Models\RekeningBank;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RekeningBankController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->string('search'));
        $jenis = trim((string) $request->string('jenis_rekening'));
        $statusAktif = trim((string) $request->string('status_aktif'));

        $rekenings = RekeningBank::query()
            ->with('pemilik')
            ->when($search !== '', fn (Builder $b) => $this->applySearchFilter($b, $search))
            ->when($jenis !== '', fn (Builder $b) => $b->where('jenis_rekening', $jenis))
            ->when($statusAktif !== '', fn (Builder $b) => $b->where('status_aktif', $statusAktif === 'aktif'))
            ->orderBy('nama_bank')
            ->orderBy('nomor_rekening')
            ->paginate(15)
            ->withQueryString();

        if ($request->ajax() && $request->boolean('partial')) {
            return response()->view('rekening_bank._table', compact('rekenings'));
        }

        $summary = [
            'total' => RekeningBank::query()->count(),
            'aktif' => RekeningBank::query()->where('status_aktif', true)->count(),
            'penerimaan' => RekeningBank::query()->where('jenis_rekening', JenisRekening::PENERIMAAN->value)->count(),
            'pengeluaran' => RekeningBank::query()->where('jenis_rekening', JenisRekening::PENGELUARAN->value)->count(),
        ];

        return view('rekening_bank.index', [
            'rekenings' => $rekenings,
            'summary' => $summary,
            'jenisOptions' => JenisRekening::options(),
        ]);
    }

    public function create()
    {
        return view('rekening_bank.create', [
            'jenisOptions' => JenisRekening::options(),
            'pemilikOptions' => $this->pemilikOptions(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validatePayload($request);

        $rekening = RekeningBank::create($this->buildAttributes($validated));

        $this->syncDefaultPerJenis($rekening);
        BukuKasUmum::recalculateRunningBalance($rekening->id);

        return redirect()
            ->route('rekening-bank.index')
            ->with('success', 'Rekening ' . $rekening->nama_bank . ' - ' . $rekening->nomor_rekening . ' berhasil ditambahkan.');
    }

    public function show(RekeningBank $rekening)
    {
        $rekening->load('pemilik');

        $bkuStats = BukuKasUmum::query()
            ->where('sumber_rekening_id', $rekening->id)
            ->selectRaw("COUNT(*) as jumlah")
            ->selectRaw("SUM(CASE WHEN arus_kas = 'DEBIT_MASUK' THEN nominal ELSE 0 END) as total_masuk")
            ->selectRaw("SUM(CASE WHEN arus_kas = 'KREDIT_KELUAR' THEN nominal ELSE 0 END) as total_keluar")
            ->first();

        $saldoTerakhir = BukuKasUmum::query()
            ->where('sumber_rekening_id', $rekening->id)
            ->orderByDesc('tanggal_transaksi')
            ->orderByDesc('id')
            ->value('saldo_akhir');

        return view('rekening_bank.show', [
            'rekening' => $rekening,
            'bkuStats' => $bkuStats,
            'saldoTerakhir' => $saldoTerakhir ?? $rekening->saldo_awal,
        ]);
    }

    public function edit(RekeningBank $rekening)
    {
        return view('rekening_bank.edit', [
            'rekening' => $rekening,
            'jenisOptions' => JenisRekening::options(),
            'pemilikOptions' => $this->pemilikOptions(),
        ]);
    }

    public function update(Request $request, RekeningBank $rekening)
    {
        $validated = $this->validatePayload($request, $rekening);

        $rekening->update($this->buildAttributes($validated));

        $this->syncDefaultPerJenis($rekening);

        return redirect()
            ->route('rekening-bank.show', $rekening)
            ->with('success', 'Rekening ' . $rekening->nama_bank . ' - ' . $rekening->nomor_rekening . ' berhasil diperbarui.');
    }

    public function toggle(RekeningBank $rekening)
    {
        $rekening->update(['status_aktif' => ! $rekening->status_aktif]);

        return redirect()
            ->route('rekening-bank.index')
            ->with('success', 'Status rekening ' . $rekening->nomor_rekening . ' berhasil diperbarui.');
    }

    public function destroy(RekeningBank $rekening)
    {
        $dipakaiDiBku = BukuKasUmum::query()->where('sumber_rekening_id', $rekening->id)->exists();

        if ($dipakaiDiBku) {
            return redirect()
                ->route('rekening-bank.show', $rekening)
                ->with('error', 'Rekening tidak dapat dihapus karena sudah dipakai pada Buku Kas Umum.');
        }

        $label = $rekening->nomor_rekening;
        $rekening->delete();

        return redirect()
            ->route('rekening-bank.index')
            ->with('success', 'Rekening ' . $label . ' berhasil dihapus.');
    }

    private function applySearchFilter(Builder $builder, string $search): void
    {
        $like = '%' . addcslashes($search, '%_\\') . '%';

        $builder->where(function (Builder $q) use ($like) {
            $q->where('nama_bank', 'like', $like)
                ->orWhere('nomor_rekening', 'like', $like)
                ->orWhere('nama_rekening', 'like', $like);
        });
    }

    /**
     * Hanya boleh ada SATU rekening default per jenis. Saat satu rekening
     * ditandai default, lepaskan flag default rekening lain yang sejenis.
     */
    private function syncDefaultPerJenis(RekeningBank $rekening): void
    {
        if (! $rekening->is_default) {
            return;
        }

        RekeningBank::query()
            ->where('jenis_rekening', $rekening->jenis_rekening?->value ?? $rekening->jenis_rekening)
            ->whereKeyNot($rekening->id)
            ->where('is_default', true)
            ->update(['is_default' => false]);
    }

    private function buildAttributes(array $validated): array
    {
        return [
            'pemilik_type' => User::class,
            'pemilik_id' => $validated['pemilik_id'],
            'nama_bank' => trim($validated['nama_bank']),
            'nomor_rekening' => trim($validated['nomor_rekening']),
            'nama_rekening' => trim($validated['nama_rekening']),
            'kode_bank' => $validated['kode_bank'] ?? null,
            'jenis_rekening' => $validated['jenis_rekening'],
            // saldo_awal & saldo_awal_per_tanggal sengaja TIDAK di-set dari form ini.
            // Saldo awal dicatat sebagai baris BKU lewat menu Buku Kas Umum agar tidak
            // dobel dihitung dengan kolom rekening_bank.saldo_awal. Pada update, kolom
            // tersebut dibiarkan apa adanya; pada store, pakai default DB (0 / null).
            'is_default' => (bool) ($validated['is_default'] ?? false),
            'status_aktif' => (bool) ($validated['status_aktif'] ?? false),
        ];
    }

    /**
     * Daftar User bendahara sebagai kandidat pemilik rekening.
     *
     * @return \Illuminate\Support\Collection<int, User>
     */
    private function pemilikOptions()
    {
        return User::query()
            ->whereHas('roles', fn (Builder $q) => $q->whereIn('name', ['Bendahara Penerimaan', 'Bendahara Pengeluaran']))
            ->with('profilable')
            ->orderByDisplayName()
            ->get();
    }

    private function validatePayload(Request $request, ?RekeningBank $rekening = null): array
    {
        return $request->validate([
            'pemilik_id' => 'required|integer|exists:users,id',
            'nama_bank' => 'required|string|max:100',
            'nomor_rekening' => [
                'required',
                'string',
                'max:50',
                Rule::unique('rekening_bank', 'nomor_rekening')->ignore($rekening?->id)->whereNull('deleted_at'),
            ],
            'nama_rekening' => 'required|string|max:150',
            'kode_bank' => 'nullable|string|max:20',
            'jenis_rekening' => ['required', 'string', Rule::in(JenisRekening::values())],
            'is_default' => 'nullable|boolean',
            'status_aktif' => 'nullable|boolean',
        ]);
    }
}
