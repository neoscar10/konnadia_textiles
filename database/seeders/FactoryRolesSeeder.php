<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class FactoryRolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $manageLabor = \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'manage_labor', 'guard_name' => 'web']);

        $factorySupervisor = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Factory Supervisor', 'guard_name' => 'web']);
        $laborer = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Laborer', 'guard_name' => 'web']);

        $factorySupervisor->givePermissionTo($manageLabor);
    }
}
