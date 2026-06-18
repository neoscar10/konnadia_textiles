<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        $subtotal = $this->faker->numberBetween(1000, 100000);
        $gstAmount = $subtotal * 0.18;
        $totalAmount = $subtotal + $gstAmount;

        return [
            'order_number' => 'ORD-' . $this->faker->unique()->numerify('######'),
            'customer_id' => Customer::factory(),
            'status' => $this->faker->randomElement([
                'submitted',
                'under_review',
                'pending_approval',
                'pending_payment_verification',
                'approved',
                'dispatched',
                'rejected',
            ]),
            'subtotal' => $subtotal,
            'gst_amount' => $gstAmount,
            'total_amount' => $totalAmount,
            'created_at' => $this->faker->dateTimeBetween('-90 days'),
        ];
    }
}
