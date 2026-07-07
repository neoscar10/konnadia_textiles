<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'title' => $this->faker->words(3, true),
            'description' => $this->faker->text(200),
            'sku' => 'SKU-' . $this->faker->unique()->numerify('######'),
            'is_active' => $this->faker->boolean(85),
            'stock_quantity' => $this->faker->numberBetween(0, 1000),
            'minimum_order_quantity' => $this->faker->numberBetween(1, 10),
            'gst_percentage' => $this->faker->randomElement([5, 12, 18, 28]),
            'base_price' => $this->faker->numberBetween(100, 10000),
        ];
    }
}
