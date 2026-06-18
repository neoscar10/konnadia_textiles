<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\CustomerLevel;
use Illuminate\Database\Eloquent\Factories\Factory;

class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    public function definition(): array
    {
        return [
            'customer_level_id' => CustomerLevel::factory(),
            'company_name' => $this->faker->company(),
            'customer_number' => 'CUST-' . $this->faker->unique()->numerify('######'),
            'contact_person' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'mobile_number' => $this->faker->phoneNumber(),
            'gst_number' => $this->faker->regexify('[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}'),
            'credit_limit' => $this->faker->numberBetween(50000, 500000),
            'outstanding_amount' => $this->faker->numberBetween(0, 250000),
            'available_credit' => $this->faker->numberBetween(50000, 500000),
            'is_active' => $this->faker->boolean(80),
            'credit_hold' => false,
            'billing_address' => $this->faker->address(),
        ];
    }
}
