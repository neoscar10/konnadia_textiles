<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\CustomerLevel;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        $levels = CustomerLevel::all();
        if ($levels->isEmpty()) {
            return;
        }

        $wholesale = $levels->firstWhere('name', 'Wholesale Distributor');
        $retail = $levels->firstWhere('name', 'Retail Outlet');
        $platinum = $levels->firstWhere('name', 'Platinum Partner');
        $bulk = $levels->firstWhere('name', 'Bulk Buyer');

        $customers = [
            [
                'customer_number' => 'KT-001',
                'company_name' => 'Kumar Garments',
                'gst_number' => 'GSTIN-KUMAR-001',
                'contact_person' => 'Rajesh Kumar',
                'mobile_number' => '+91 98765 43210',
                'email' => 'rajesh.kumar@example.com',
                'customer_level_id' => $wholesale->id ?? $levels->first()->id,
                'credit_limit' => 500000,
                'outstanding_amount' => 0,
                'available_credit' => 500000,
                'overdue_amount' => 0,
                'allow_credit_beyond_limit' => false,
                'is_active' => true,
            ],
            [
                'customer_number' => 'KT-002',
                'company_name' => 'Desai Textiles',
                'gst_number' => 'GSTIN-DESAI-002',
                'contact_person' => 'Anita Desai',
                'mobile_number' => '+91 98765 43211',
                'email' => 'anita.desai@example.com',
                'customer_level_id' => $platinum->id ?? $levels->first()->id,
                'credit_limit' => 1500000,
                'outstanding_amount' => 0,
                'available_credit' => 1500000,
                'overdue_amount' => 0,
                'allow_credit_beyond_limit' => true,
                'is_active' => true,
            ],
            [
                'customer_number' => 'KT-003',
                'company_name' => 'Sharma Retail House',
                'gst_number' => 'GSTIN-SHARMA-003',
                'contact_person' => 'Suresh Sharma',
                'mobile_number' => '+91 98765 43212',
                'email' => 'suresh@sharmaretail.com',
                'customer_level_id' => $retail->id ?? $levels->first()->id,
                'credit_limit' => 100000,
                'outstanding_amount' => 0,
                'available_credit' => 100000,
                'overdue_amount' => 0,
                'allow_credit_beyond_limit' => false,
                'is_active' => true,
            ],
            [
                'customer_number' => 'KT-004',
                'company_name' => 'Mehta Fashion Traders',
                'gst_number' => 'GSTIN-MEHTA-004',
                'contact_person' => 'Ravi Mehta',
                'mobile_number' => '+91 98765 43213',
                'email' => 'ravi.mehta@example.com',
                'customer_level_id' => $bulk->id ?? $levels->first()->id,
                'credit_limit' => 800000,
                'outstanding_amount' => 0,
                'available_credit' => 800000,
                'overdue_amount' => 0,
                'allow_credit_beyond_limit' => false,
                'is_active' => false,
            ]
        ];

        foreach ($customers as $cust) {
            $user = User::updateOrCreate(
                ['mobile_number' => $cust['mobile_number']],
                [
                    'name' => $cust['contact_person'],
                    'email' => $cust['email'],
                    'password' => Hash::make('Password@123'),
                    'is_active' => $cust['is_active'],
                ]
            );

            $user->assignRole('customer');

            $cust['user_id'] = $user->id;

            Customer::updateOrCreate(
                ['customer_number' => $cust['customer_number']],
                $cust
            );
        }
    }
}
