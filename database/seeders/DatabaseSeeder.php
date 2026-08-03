<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            SuperAdminSeeder::class,
            CategorySeeder::class,
            ProductSeeder::class,
            CustomerLevelSeeder::class,
            CustomerSeeder::class,
            FactoryRolesSeeder::class,
            TaskSeeder::class,
            ManufacturingProductSeeder::class,
            RawMaterialInventorySeeder::class,
        ]);
    }
}
