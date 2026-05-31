<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Hapus role "Admin Konsesi" dari database.
     *
     * Pivot dibersihkan manual agar tidak bergantung pada cascade FK.
     * Referensi role ini di kode (routes/controller/view) sengaja dibiarkan
     * sesuai keputusan: cukup hapus role kosong di DB.
     */
    public function up(): void
    {
        $role = DB::table('roles')
            ->where('name', 'Admin Konsesi')
            ->where('guard_name', 'web')
            ->first();

        if ($role) {
            DB::table('model_has_roles')->where('role_id', $role->id)->delete();
            DB::table('role_has_permissions')->where('role_id', $role->id)->delete();
            DB::table('roles')->where('id', $role->id)->delete();
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * Buat ulang role bila migration di-rollback (tanpa penugasan apa pun).
     */
    public function down(): void
    {
        Role::findOrCreate('Admin Konsesi', 'web');

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
