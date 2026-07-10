<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

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

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

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

        Permission::whereIn('name', $permissions)->delete();
    }
};
