<?php

namespace Database\Factories;

use App\Models\CustomerLevel;
use Illuminate\Database\Eloquent\Factories\Factory;

class CustomerLevelFactory extends Factory
{
    protected $model = CustomerLevel::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->randomElement(['Bronze', 'Silver', 'Gold', 'Platinum']),
            'description' => $this->faker->sentence(),
            'is_active' => true,
        ];
    }
}
