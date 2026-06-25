<?php

namespace App\Http\Controllers;

use App\Support\Panduan\PanduanRegistry;
use Illuminate\Http\Request;

/**
 * Pusat Panduan — menampilkan panduan penggunaan aplikasi yang menyesuaikan
 * peran (role) user yang sedang login.
 *
 * Mekanisme:
 *  - Konten diambil dari PanduanRegistry (file-based).
 *  - Secara default menampilkan panduan untuk role pertama user yang memiliki entri.
 *  - User bisa menelusuri panduan role lain miliknya lewat ?role=... ; Super Admin
 *    boleh menelusuri panduan semua role (untuk keperluan penyusunan/peninjauan).
 */
class PanduanController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $userRoles = $user->getRoleNames()->all();
        $isSuperAdmin = in_array('Super Admin', $userRoles, true);

        $registry = PanduanRegistry::all();

        // Role milik user yang sudah punya panduan (urut sesuai prioritas registry).
        $roleKeysWithGuide = array_values(array_filter(
            array_keys($registry),
            fn ($role) => in_array($role, $userRoles, true),
        ));

        // Daftar panduan yang boleh ditelusuri: Super Admin = semua, selain itu = miliknya.
        $browsable = $isSuperAdmin ? array_keys($registry) : $roleKeysWithGuide;

        // Tentukan role aktif: dari ?role= (panduan bersifat bantuan, boleh ditelusuri lintas peran),
        // lalu role pertama user, lalu — khusus Super Admin — role pertama di registry.
        $requested = $request->query('role');
        $activeRole = null;

        if ($requested && isset($registry[$requested])) {
            $activeRole = $requested;
        } elseif (! empty($roleKeysWithGuide)) {
            $activeRole = $roleKeysWithGuide[0];
        } elseif ($isSuperAdmin) {
            $activeRole = array_key_first($registry);
        }

        // Pastikan peran yang sedang dibuka muncul di pemilih (mis. via tautan ?role= atau redirect).
        if ($activeRole && ! in_array($activeRole, $browsable, true)) {
            $browsable[] = $activeRole;
        }

        $guide = $activeRole ? $registry[$activeRole] : null;

        return view('panduan.index', [
            'guide' => $guide,
            'activeRole' => $activeRole,
            'browsable' => $browsable,
            'registry' => $registry,
            'userRoles' => $userRoles,
            'isSuperAdmin' => $isSuperAdmin,
        ]);
    }
}
