<?php

namespace App\Http\Controllers;

use App\Models\MasterPegawai;
use App\Models\MasterPihak;
use App\Models\MitraJasa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    /**
     * Display the authenticated user's profile.
     */
    public function index()
    {
        $user = Auth::user();
        $user->loadMissing('profilable');

        $fields = $this->editableFields($user);

        return view('profile.index', compact('user', 'fields'));
    }

    /**
     * Update the authenticated user's profile information.
     */
    public function update(Request $request)
    {
        $user = Auth::user();
        $user->loadMissing('profilable');

        $fields = $this->editableFields($user);

        // Aturan validasi: email (akun) + field profil sesuai tipe profilable.
        $rules = [
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($user->id)],
        ];
        $attributes = ['email' => 'Email'];

        foreach ($fields as $name => $cfg) {
            $rules[$name] = $cfg['rules'];
            $attributes[$name] = $cfg['label'];
        }

        $validated = $request->validateWithBag('updateProfile', $rules, [], $attributes);

        // Update email pada akun user.
        $user->email = $validated['email'];
        $user->save();

        // Update field pada profil (pegawai / mitra / pihak) bila ada.
        $profile = $user->profilable;
        if ($profile) {
            foreach ($fields as $name => $cfg) {
                if (array_key_exists($name, $validated)) {
                    $profile->{$name} = $validated[$name];
                }
            }
            $profile->save();
        }

        return redirect()
            ->route('profile.index')
            ->with('success', 'Profil berhasil diperbarui.');
    }

    /**
     * Update the authenticated user's password.
     */
    public function updatePassword(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validateWithBag('updatePassword', [
            'current_password' => ['required', 'string'],
            'password'         => ['required', 'confirmed', Password::min(8)],
        ], [], [
            'current_password' => 'Password saat ini',
            'password'         => 'Password baru',
        ]);

        if (! Hash::check($validated['current_password'], $user->password)) {
            return back()
                ->withErrors(['current_password' => 'Password saat ini tidak sesuai.'], 'updatePassword');
        }

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        return redirect()
            ->route('profile.index')
            ->with('success', 'Password berhasil diperbarui.');
    }

    /**
     * Definisi field profil yang bisa diedit, menyesuaikan tipe profilable.
     * Dipakai bersama oleh form (view) dan validasi (update).
     *
     * @return array<string, array{label:string, rules:array, icon?:string}>
     */
    private function editableFields($user): array
    {
        $profile = $user->profilable;

        if ($profile instanceof MasterPegawai) {
            return [
                'nama_lengkap'   => ['label' => 'Nama Lengkap',   'rules' => ['required', 'string', 'max:150'], 'icon' => 'badge'],
                'jabatan'        => ['label' => 'Jabatan',        'rules' => ['nullable', 'string', 'max:100'], 'icon' => 'work_outline'],
                'npwp'           => ['label' => 'NPWP',           'rules' => ['nullable', 'string', 'max:50'],  'icon' => 'receipt_long'],
                'nomor_hp'       => ['label' => 'Nomor HP',       'rules' => ['nullable', 'string', 'max:50'],  'icon' => 'smartphone'],
                'nama_bank'      => ['label' => 'Nama Bank',      'rules' => ['nullable', 'string', 'max:100'], 'icon' => 'account_balance'],
                'nomor_rekening' => ['label' => 'Nomor Rekening', 'rules' => ['nullable', 'string', 'max:100'], 'icon' => 'credit_card'],
                'nama_rekening'  => ['label' => 'Nama Rekening',  'rules' => ['nullable', 'string', 'max:150'], 'icon' => 'person'],
            ];
        }

        if ($profile instanceof MitraJasa) {
            return [
                'nama_mitra'               => ['label' => 'Nama Mitra',             'rules' => ['required', 'string', 'max:191'], 'icon' => 'business'],
                'nama_penanggung_jawab'    => ['label' => 'Penanggung Jawab',       'rules' => ['nullable', 'string', 'max:191'], 'icon' => 'person'],
                'jabatan_penanggung_jawab' => ['label' => 'Jabatan Penanggung Jawab', 'rules' => ['nullable', 'string', 'max:191'], 'icon' => 'work_outline'],
                'npwp'                     => ['label' => 'NPWP',                   'rules' => ['nullable', 'string', 'max:50'],  'icon' => 'receipt_long'],
                'no_telepon'               => ['label' => 'Nomor Telepon',          'rules' => ['nullable', 'string', 'max:50'],  'icon' => 'smartphone'],
                'alamat'                   => ['label' => 'Alamat',                 'rules' => ['nullable', 'string', 'max:255'], 'icon' => 'place'],
            ];
        }

        if ($profile instanceof MasterPihak) {
            return [
                'nama_pihak'            => ['label' => 'Nama',               'rules' => ['required', 'string', 'max:191'], 'icon' => 'business'],
                'nama_penanggung_jawab' => ['label' => 'Penanggung Jawab',   'rules' => ['nullable', 'string', 'max:191'], 'icon' => 'person'],
                'jabatan_penandatangan' => ['label' => 'Jabatan Penandatangan', 'rules' => ['nullable', 'string', 'max:191'], 'icon' => 'work_outline'],
                'npwp'                  => ['label' => 'NPWP',               'rules' => ['nullable', 'string', 'max:50'],  'icon' => 'receipt_long'],
                'no_telepon'            => ['label' => 'Nomor Telepon',      'rules' => ['nullable', 'string', 'max:50'],  'icon' => 'smartphone'],
                'alamat'                => ['label' => 'Alamat',             'rules' => ['nullable', 'string', 'max:255'], 'icon' => 'place'],
            ];
        }

        // Profilable tidak dikenali: hanya email yang bisa diedit.
        return [];
    }
}
