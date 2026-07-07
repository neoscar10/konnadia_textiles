<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AdminPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // 12 permissions matching the sidebar pages
        $permissions = [
            'access dashboard',
            'access customers',
            'access customer-levels',
            'access products',
            'access design-catalog',
            'access categories',
            'access tags',
            'access inventory',
            'access retail-shops',
            'access product-transfers',
            'access orders',
            'manage manufactured orders',
            'manage retail orders',
            'access home-content',
            'access settings',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'api']);
        }
    }
}
