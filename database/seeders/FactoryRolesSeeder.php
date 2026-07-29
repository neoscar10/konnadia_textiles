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
        $manageLabor = \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'manage_labor']);

        $factorySupervisor = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Factory Supervisor']);
        $laborer = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Laborer']);

        $factorySupervisor->givePermissionTo($manageLabor);
    }
}
