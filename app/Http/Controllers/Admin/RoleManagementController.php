<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Spatie\Permission\Models\Role;

class RoleManagementController extends Controller
{
    public function index()
    {
        $roles = Role::withCount('users')->orderBy('name')->get();

        // Catat juga jumlah user per role yang spesifiknya pegawai/mitra/sistem (untuk kartu detail).
        return view('admin.roles.index', compact('roles'));
    }

    public function show(Role $role)
    {
        $role->loadCount('users');
        $users = User::role($role->name)->with('profilable')->orderBy('email')->paginate(20);

        return view('admin.roles.show', compact('role', 'users'));
    }
}
