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

        Permission::firstOrCreate(['name' => 'manage manufactured orders', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'manage manufactured orders', 'guard_name' => 'api']);

        Permission::firstOrCreate(['name' => 'manage retail orders', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'manage retail orders', 'guard_name' => 'api']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        Permission::whereIn('name', ['manage manufactured orders', 'manage retail orders'])->delete();
    }
};
