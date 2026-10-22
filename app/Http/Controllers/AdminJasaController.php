<?php

namespace App\Http\Controllers;

use App\Models\LayananJasa;
use App\Models\MasterPegawai;
use App\Models\User;
use App\Services\AdminJasaLayananService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class AdminJasaController extends Controller
{
    public function index()
    {
        $admins = User::role('Admin Jasa')
            ->with([
                'profilable',
                'layananJasaDikelola' => fn ($query) => $query->wherePivot('status_aktif', true),
            ])
            ->orderBy('email')
            ->paginate(15)
            ->withQueryString();

        return view('super_admin_jasa.admin.index', compact('admins'));
    }

    public function create()
    {
        return view('super_admin_jasa.admin.form', [
            'admin' => new User(),
            'pegawai' => new MasterPegawai(),
            'pegawaiOptions' => $this->pegawaiTanpaUser(),
            'layanans' => $this->layananPilihan(),
            'selectedLayananIds' => [],
        ]);
    }

    public function store(Request $request, AdminJasaLayananService $layananService)
    {
        $validated = $request->validate([
            'pegawai_id' => ['required', 'integer', 'exists:master_pegawai,id'],
            'email'      => ['required', 'email', 'max:255', 'unique:users,email'],
            'password'   => ['required', 'string', 'min:6', 'max:100'],
            'layanan_ids' => ['nullable', 'array'],
            'layanan_ids.*' => ['integer', 'exists:layanan_jasas,id'],
        ]);

        $pegawai = MasterPegawai::findOrFail($validated['pegawai_id']);

        $sudahPunyaUser = User::where('profilable_type', MasterPegawai::class)
            ->where('profilable_id', $pegawai->id)
            ->exists();

        if ($sudahPunyaUser) {
            return back()
                ->withInput()
                ->with('error', 'Pegawai ini sudah memiliki akun pengguna.');
        }

        Role::findOrCreate('Admin Jasa', 'web');

        // Akun & layanan yang dikelola disimpan bersamaan agar admin baru langsung bisa bekerja.
        $user = DB::transaction(function () use ($validated, $pegawai, $layananService) {
            $user = User::create([
                'email'           => $validated['email'],
                'password'        => Hash::make($validated['password']),
                'profilable_type' => MasterPegawai::class,
                'profilable_id'   => $pegawai->id,
            ]);
            $user->assignRole('Admin Jasa');
            $layananService->sync($user, $validated['layanan_ids'] ?? [], auth()->id());

            return $user;
        });

        return redirect()
            ->route('jasa.admin.show', $user)
            ->with('success', 'Admin Jasa berhasil dibuat.');
    }

    public function show(User $admin)
    {
        abort_unless($admin->hasRole('Admin Jasa'), 404);

        $admin->load([
            'profilable',
            'layananJasaDikelola' => fn ($query) => $query->wherePivot('status_aktif', true),
        ]);

        $selectedLayananIds = $admin->layananJasaDikelola->pluck('id')->all();
        $visibleLayananIds = $this->buildVisibleLayananIds($selectedLayananIds);
        $layananTreeItems = LayananJasa::query()
            ->whereIn('id', $visibleLayananIds)
            ->orderBy('level')
            ->orderBy('id')
            ->get();

        return view('super_admin_jasa.admin.show', compact(
            'admin',
            'layananTreeItems',
            'selectedLayananIds',
            'visibleLayananIds'
        ));
    }

    public function edit(User $admin)
    {
        abort_unless($admin->hasRole('Admin Jasa'), 404);

        return view('super_admin_jasa.admin.form', [
            'admin' => $admin,
            'pegawai' => $admin->profilable instanceof MasterPegawai ? $admin->profilable : new MasterPegawai(),
            'layanans' => $this->layananPilihan(),
            'selectedLayananIds' => $admin->layananJasaDikelola()->wherePivot('status_aktif', true)->pluck('layanan_jasas.id')->all(),
        ]);
    }

    public function update(Request $request, User $admin, AdminJasaLayananService $layananService)
    {
        abort_unless($admin->hasRole('Admin Jasa'), 404);

        $validated = $this->validateAdmin($request, $admin->id, true);

        DB::transaction(function () use ($request, $admin, $validated, $layananService) {
            $pegawai = $admin->profilable instanceof MasterPegawai
                ? $admin->profilable
                : MasterPegawai::create(['nama_lengkap' => $validated['nama_lengkap'], 'status_aktif' => true]);

            $pegawai->update([
                'nip' => ($validated['nip'] ?? null) ?: null,
                'nama_lengkap' => $validated['nama_lengkap'],
                'jabatan' => ($validated['jabatan'] ?? null) ?: 'Admin Jasa',
                'npwp' => ($validated['npwp'] ?? null) ?: null,
                'status_aktif' => $request->boolean('status_aktif'),
            ]);

            $userPayload = [
                'email' => $validated['email'],
                'profilable_type' => MasterPegawai::class,
                'profilable_id' => $pegawai->id,
            ];

            if (! empty($validated['password'])) {
                $userPayload['password'] = Hash::make($validated['password']);
            }

            $admin->update($userPayload);
            // Peran lain milik user (mis. Koordinator Jasa) tidak boleh hilang saat profil diedit.
            if (! $admin->hasRole('Admin Jasa')) {
                $admin->assignRole('Admin Jasa');
            }

            $layananService->sync($admin, $validated['layanan_ids'] ?? [], auth()->id());
        });

        return redirect()
            ->route('jasa.admin.show', $admin)
            ->with('success', 'Admin Jasa berhasil diperbarui.');
    }

    public function destroy(User $admin)
    {
        abort_unless(auth()->user()?->hasAnyRole(['Super Admin', 'Super Admin Jasa', 'Koordinator Jasa']) === true, 403);
        abort_unless($admin->hasRole('Admin Jasa'), 404);

        if ((int) $admin->id === (int) auth()->id()) {
            return back()->with('error', 'Akun yang sedang login tidak dapat menghapus dirinya sendiri.');
        }

        $pegawai = $admin->profilable instanceof MasterPegawai ? $admin->profilable : null;

        $admin->layananJasaDikelola()->detach();
        $admin->syncRoles([]);
        $admin->delete();

        if ($pegawai && ! $pegawai->user()->exists()) {
            $pegawai->delete();
        }

        return redirect()
            ->route('jasa.admin.index')
            ->with('success', 'Admin Jasa berhasil dihapus.');
    }

    private function validateAdmin(Request $request, ?int $ignoreUserId = null, bool $isUpdate = false): array
    {
        return $request->validate([
            'nama_lengkap' => ['required', 'string', 'max:150'],
            'nip' => ['nullable', 'string', 'max:50'],
            'jabatan' => ['nullable', 'string', 'max:100'],
            'npwp' => ['nullable', 'string', 'max:50'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $ignoreUserId],
            'password' => [$isUpdate ? 'nullable' : 'required', 'string', 'min:6', 'max:100'],
            'status_aktif' => ['nullable', 'boolean'],
            'layanan_ids' => ['nullable', 'array'],
            'layanan_ids.*' => ['integer', 'exists:layanan_jasas,id'],
        ]);
    }

    /** Layanan daun aktif yang bisa dikelola Admin Jasa (untuk pohon pemilih layanan). */
    private function layananPilihan()
    {
        return LayananJasa::query()
            ->where('is_active', true)
            ->where('is_leaf', true)
            ->with('parent.parent.parent.parent.parent')
            ->orderBy('nama_layanan')
            ->get();
    }

    private function pegawaiTanpaUser()
    {
        $usedIds = User::where('profilable_type', MasterPegawai::class)->pluck('profilable_id');

        return MasterPegawai::whereNotIn('id', $usedIds)
            ->where('status_aktif', true)
            ->orderBy('nama_lengkap')
            ->get(['id', 'nama_lengkap', 'nip', 'jabatan', 'npwp']);
    }

    private function buildVisibleLayananIds(array $selectedIds): array
    {
        $visibleIds = collect($selectedIds);
        $itemsById = LayananJasa::query()
            ->whereIn('id', $selectedIds)
            ->with('parent.parent.parent.parent.parent')
            ->get()
            ->keyBy('id');

        foreach ($selectedIds as $id) {
            $parent = $itemsById->get($id)?->parent;
            $guard = 0;

            while ($parent && $guard < 10) {
                $visibleIds->push($parent->id);
                $parent = $parent->parent;
                $guard++;
            }
        }

        return $visibleIds->unique()->values()->all();
    }
}
