<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // ─── Permissions ─────────────────────────────────────────
        $permissions = [
            'dashboard.view',
            'supplier.view', 'supplier.create', 'supplier.edit', 'supplier.delete',
            'produk.view',   'produk.create',   'produk.edit',   'produk.delete',
            'whitelist.view', 'whitelist.create', 'whitelist.edit', 'whitelist.delete',
            'spending.view', 'spending.create', 'spending.edit', 'spending.delete', 'spending.approve',
            'user.view',     'user.create',     'user.edit',     'user.delete',
            'role.view',     'role.create',     'role.edit',     'role.delete',
            'laporan.view',  'laporan.export',
            'topup.view',    'topup.create',    'topup.approve', 'topup.pay',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm]);
        }

        // ─── Roles & permission mapping ───────────────────────────
        // Pengelolaan whitelist (buat/ubah/hapus) HANYA untuk role advertiser.
        // Admin/superadmin/owner cukup whitelist.view (melihat & memilah per pemilik).
        $whitelistMgmt = ['whitelist.create', 'whitelist.edit', 'whitelist.delete'];
        $allPermissions = Permission::all()->pluck('name')->toArray();

        $roleMap = [
            // owner = super admin level tertinggi, bisa semua + buat akun (kecuali kelola whitelist)
            'owner' => array_values(array_diff($allPermissions, $whitelistMgmt)),

            // super_admin = semua akses operasional (kecuali kelola whitelist)
            'super_admin' => array_values(array_diff($allPermissions, $whitelistMgmt)),

            'admin' => [
                'dashboard.view',
                'supplier.view', 'supplier.create', 'supplier.edit',
                'produk.view',   'produk.create',   'produk.edit',
                'whitelist.view',
                'spending.view', 'spending.approve',
                'user.view',     'user.create',     'user.edit',
                'laporan.view',  'laporan.export',
            ],

            'advertiser' => [
                'dashboard.view',
                'supplier.view',
                'produk.view',
                'whitelist.view', 'whitelist.create', 'whitelist.edit', 'whitelist.delete',
                'spending.view', 'spending.create', 'spending.edit', 'spending.delete',
            ],

            'mentor' => [
                'dashboard.view',
                'supplier.view',
                'produk.view',
                'whitelist.view',
                'spending.view',
                'laporan.view',
            ],

            'keuangan' => [
                'dashboard.view',
                'supplier.view',
                'produk.view',
                'spending.view', 'spending.approve',
                'laporan.view',  'laporan.export',
            ],

            'cs' => [
                'dashboard.view',
                'produk.view',
                'spending.view', 'spending.create', 'spending.edit',
            ],

            'pemilik_bank' => [
                'dashboard.view',
            ],
        ];

        foreach ($roleMap as $roleName => $rolePermissions) {
            $role = Role::firstOrCreate(['name' => $roleName]);
            $role->syncPermissions($rolePermissions);
        }
    }
}
