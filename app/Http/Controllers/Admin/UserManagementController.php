<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\MasterPegawai;
use App\Models\MitraJasa;
use App\Models\User;
use App\Services\Admin\UserProvisioningService;
use App\Services\WhatsappService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class UserManagementController extends Controller
{
    public function __construct(private UserProvisioningService $provisioner)
    {
    }

    public function index(Request $request)
    {
        $users = User::query()
            ->with(['roles', 'profilable'])
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%' . $request->string('q')->lower() . '%';
                $q->whereRaw('LOWER(email) LIKE ?', [$term]);
            })
            ->when($request->filled('role'), function ($q) use ($request) {
                $q->whereHas('roles', fn ($r) => $r->where('name', $request->input('role')));
            })
            ->when($request->filled('tipe'), function ($q) use ($request) {
                $tipe = $request->input('tipe');
                if ($tipe === 'sistem') {
                    $q->whereNull('profilable_type');
                } elseif ($tipe === 'pegawai') {
                    $q->where('profilable_type', MasterPegawai::class);
                } elseif ($tipe === 'mitra') {
                    $q->where('profilable_type', MitraJasa::class);
                }
            })
            ->orderBy('email')
            ->paginate(15)
            ->withQueryString();

        $roleList = Role::orderBy('name')->pluck('name');

        $stats = [
            'total'   => User::count(),
            'pegawai' => User::where('profilable_type', MasterPegawai::class)->count(),
            'mitra'   => User::where('profilable_type', MitraJasa::class)->count(),
            'sistem'  => User::whereNull('profilable_type')->count(),
        ];

        return view('admin.users.index', compact('users', 'roleList', 'stats'));
    }

    public function create()
    {
        return view('admin.users.create', [
            'roles' => $this->availableRoles(),
            'pegawaiOptions' => $this->pegawaiBelumPunyaUser(),
            'mitraOptions' => $this->mitraBelumPunyaUser(),
        ]);
    }

    public function store(StoreUserRequest $request)
    {
        try {
            $tipe = $request->input('tipe_akun');
            $email = $request->input('email');
            $roles = $request->input('roles', []);
            $password = $request->input('password') ?: null;
            $activeFrom = $request->input('active_from');
            $activeUntil = $request->input('active_until');

            $user = match ($tipe) {
                'pegawai' => $this->provisioner->createForPegawai(
                    MasterPegawai::findOrFail($request->input('pegawai_id')),
                    $email,
                    $roles,
                    $password,
                    $activeFrom,
                    $activeUntil,
                ),
                'mitra' => $this->provisioner->createForMitraJasa(
                    MitraJasa::findOrFail($request->input('mitra_id')),
                    $email,
                    $roles,
                    $password,
                    $activeFrom,
                    $activeUntil,
                ),
                'sistem' => $this->provisioner->createSystemAccount($email, $roles, $password),
            };

            $flash = "Akun {$user->email} berhasil dibuat.";
            if (! $password) {
                // password digenerate otomatis tetap di-tampilkan ke admin sebagai info one-time
                $flash .= ' Password sementara dikirim ke flash session.';
            }

            return redirect()
                ->route('admin.users.show', $user)
                ->with('success', $flash);
        } catch (\DomainException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function show(User $user)
    {
        $user->load(['roles', 'profilable']);

        return view('admin.users.show', compact('user'));
    }

    public function edit(User $user)
    {
        $user->load(['roles', 'profilable']);

        return view('admin.users.edit', [
            'user'  => $user,
            'roles' => $this->availableRoles(),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        try {
            $roles = $request->input('roles', []);

            $this->provisioner->updateEmail($user, $request->input('email'));
            $this->provisioner->syncRoles($user, $roles);
            $this->provisioner->syncTemporaryRolePeriod(
                $user,
                $roles,
                $request->input('active_from'),
                $request->input('active_until'),
            );

            return redirect()
                ->route('admin.users.show', $user)
                ->with('success', "Akun {$user->email} berhasil diperbarui.");
        } catch (\DomainException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function destroy(User $user)
    {
        try {
            $this->provisioner->deleteUser($user, auth()->id());

            return redirect()
                ->route('admin.users.index')
                ->with('success', "Akun {$user->email} berhasil dihapus.");
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function resetPassword(User $user, WhatsappService $whatsapp)
    {
        $user->loadMissing('profilable');

        // Reset ke password default (UserProvisioningService::DEFAULT_RESET_PASSWORD).
        $plain = $this->provisioner->resetPassword($user);

        $number = $user->whatsappNumber();

        // Tidak ada nomor WA (mis. akun sistem) — beri tahu admin agar sampaikan manual.
        if (! $number) {
            return back()->with('success',
                "Password {$user->email} berhasil direset menjadi: {$plain}. "
                . 'Nomor WhatsApp user tidak tersedia, mohon sampaikan secara manual.');
        }

        $terkirim = $whatsapp->sendMessage($number, $this->buildResetPasswordMessage($user, $plain));

        if ($terkirim) {
            return back()->with('success',
                "Password {$user->email} berhasil direset dan notifikasi WhatsApp dikirim ke {$number}.");
        }

        return back()->with('error',
            "Password {$user->email} berhasil direset menjadi: {$plain}, "
            . "namun pengiriman WhatsApp ke {$number} gagal. Mohon sampaikan secara manual.");
    }

    /**
     * Susun isi pesan WhatsApp pemberitahuan reset password.
     */
    private function buildResetPasswordMessage(User $user, string $password): string
    {
        $nama = $user->name ?: $user->email;
        $aplikasi = config('app.name', 'SIKEREN-BLU');

        return implode("\n", [
            "*{$aplikasi} - Reset Password*",
            '',
            "Halo {$nama},",
            'Password akun Anda telah direset oleh administrator. Berikut kredensial login terbaru Anda:',
            '',
            "Email    : {$user->email}",
            "Password : {$password}",
            '',
            'Demi keamanan, segera ganti password Anda setelah berhasil masuk.',
            'Mohon jangan bagikan informasi ini kepada siapa pun.',
        ]);
    }

    public function syncRoles(Request $request, User $user)
    {
        $request->validate([
            'roles'        => ['required', 'array', 'min:1'],
            'roles.*'      => ['string', 'exists:roles,name'],
            'active_from'  => [Rule::requiredIf(fn () => in_array('PLT/PLH', (array) $request->input('roles', []), true)), 'nullable', 'date'],
            'active_until' => [
                Rule::requiredIf(fn () => in_array('PLT/PLH', (array) $request->input('roles', []), true)),
                'nullable',
                'date',
                'after_or_equal:active_from',
                'after_or_equal:today',
            ],
        ]);

        try {
            $roles = $request->input('roles', []);

            $this->provisioner->syncRoles($user, $roles);
            $this->provisioner->syncTemporaryRolePeriod(
                $user,
                $roles,
                $request->input('active_from'),
                $request->input('active_until'),
            );

            return back()->with('success', "Role untuk {$user->email} berhasil diperbarui.");
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /* ----------------------------------------------------------------------
     | Helpers
     |---------------------------------------------------------------------- */

    private function availableRoles()
    {
        return Role::where('guard_name', 'web')->orderBy('name')->pluck('name');
    }

    private function pegawaiBelumPunyaUser()
    {
        $usedIds = User::where('profilable_type', MasterPegawai::class)->pluck('profilable_id');

        return MasterPegawai::whereNotIn('id', $usedIds)
            ->orderBy('nama_lengkap')
            ->get(['id', 'nama_lengkap', 'nip', 'jabatan']);
    }

    private function mitraBelumPunyaUser()
    {
        $usedIds = User::where('profilable_type', MitraJasa::class)->pluck('profilable_id');

        return MitraJasa::whereNotIn('id', $usedIds)
            ->orderBy('nama_mitra')
            ->get(['id', 'nama_mitra', 'kode_mitra']);
    }
}
