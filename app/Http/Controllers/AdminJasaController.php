<?php

namespace App\Http\Controllers;

use App\Models\LayananJasa;
use App\Models\MasterPegawai;
use App\Models\User;
use Illuminate\Http\Request;
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
            ->get();

        return view('super_admin_jasa.admin.index', compact('admins'));
    }

    public function create()
    {
        return view('super_admin_jasa.admin.form', [
            'admin' => new User(),
            'pegawai' => new MasterPegawai(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateAdmin($request);

        $pegawai = MasterPegawai::create([
            'nip' => $validated['nip'] ?: null,
            'nama_lengkap' => $validated['nama_lengkap'],
            'jabatan' => $validated['jabatan'] ?: 'Admin Jasa',
            'npwp' => $validated['npwp'] ?: null,
            'status_aktif' => $request->boolean('status_aktif', true),
        ]);

        Role::findOrCreate('Admin Jasa', 'web');

        $user = User::create([
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'profilable_type' => MasterPegawai::class,
            'profilable_id' => $pegawai->id,
        ]);
        $user->assignRole('Admin Jasa');

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
        ]);
    }

    public function update(Request $request, User $admin)
    {
        abort_unless($admin->hasRole('Admin Jasa'), 404);

        $validated = $this->validateAdmin($request, $admin->id, true);

        $pegawai = $admin->profilable instanceof MasterPegawai
            ? $admin->profilable
            : MasterPegawai::create(['nama_lengkap' => $validated['nama_lengkap'], 'status_aktif' => true]);

        $pegawai->update([
            'nip' => $validated['nip'] ?: null,
            'nama_lengkap' => $validated['nama_lengkap'],
            'jabatan' => $validated['jabatan'] ?: 'Admin Jasa',
            'npwp' => $validated['npwp'] ?: null,
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
        $admin->syncRoles(['Admin Jasa']);

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
        ]);
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
