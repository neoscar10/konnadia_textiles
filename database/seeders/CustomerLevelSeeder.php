<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\CustomerLevel;

class CustomerLevelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $levels = [
            [
                'name' => 'Platinum Partner',
                'discount_percentage' => 15,
                'default_credit_limit' => 2500000,
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'Wholesale Distributor',
                'discount_percentage' => 10,
                'default_credit_limit' => 1000000,
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'Retail Outlet',
                'discount_percentage' => 0,
                'default_credit_limit' => 100000,
                'is_active' => true,
                'sort_order' => 3,
            ],
            [
                'name' => 'Bulk Buyer',
                'discount_percentage' => 5,
                'default_credit_limit' => 500000,
                'is_active' => true,
                'sort_order' => 4,
            ]
        ];

        foreach ($levels as $level) {
            CustomerLevel::updateOrCreate(
                ['name' => $level['name']],
                $level
            );
        }
    }
}
