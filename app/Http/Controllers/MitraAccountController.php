<?php

namespace App\Http\Controllers;

use App\Models\MitraJasa;
use App\Services\MitraAccountService;

class MitraAccountController extends Controller
{
    public function store(MitraJasa $mitra, MitraAccountService $service)
    {
        $this->abortUnlessCanManageMitraMaster();

        try {
            $result = $service->createOrGetAccount($mitra);
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        if (! $result['is_new']) {
            return back()->with('success', 'Akun mitra sudah tersedia.');
        }

        return back()
            ->with('success', 'Akun mitra berhasil dibuat.')
            ->with('mitra_email', $result['user']->email)
            ->with('mitra_password', $result['password']);
    }

    public function reset(MitraJasa $mitra, MitraAccountService $service)
    {
        $this->abortUnlessCanManageMitraMaster();

        try {
            $result = $service->resetPassword($mitra);
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()
            ->with('success', 'Password awal akun mitra berhasil dibuat ulang.')
            ->with('mitra_email', $result['user']->email)
            ->with('mitra_password', $result['password']);
    }

    private function abortUnlessCanManageMitraMaster(): void
    {
        abort_unless(
            auth()->user()?->hasAnyRole(['Super Admin', 'Super Admin Jasa', 'Koordinator Jasa']) === true,
            403
        );
    }
}
