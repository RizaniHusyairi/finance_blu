<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AdminJasaLayananService;
use Illuminate\Http\Request;

/**
 * Layanan yang dikelola Admin Jasa kini diatur di form Edit Admin Jasa (satu halaman
 * dengan profil & akun). Route lama tetap ada agar tautan/penanda lama tidak putus.
 */
class AdminJasaLayananController extends Controller
{
    public function edit(Request $request, User $user)
    {
        abort_unless($user->hasRole('Admin Jasa'), 404);

        return redirect()->to(route('jasa.admin.edit', $user) . '#layanan-admin');
    }

    public function update(Request $request, User $user, AdminJasaLayananService $service)
    {
        abort_unless($user->hasRole('Admin Jasa'), 404);

        $validated = $request->validate([
            'layanan_ids' => ['nullable', 'array'],
            'layanan_ids.*' => ['integer', 'exists:layanan_jasas,id'],
        ]);

        $service->sync($user, $validated['layanan_ids'] ?? [], auth()->id());

        return back()->with('success', 'Pengaturan layanan admin jasa berhasil disimpan.');
    }
}
