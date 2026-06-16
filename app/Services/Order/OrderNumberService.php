<?php

namespace App\Services\Order;

use App\Models\Order;

class OrderNumberService
{
    /**
     * Generate the next sequential order number.
     * Format: KT-ORD-100001, KT-ORD-100002, ...
     */
    public function generate(): string
    {
        $lastOrder = Order::withTrashed()
            ->where('order_number', 'like', 'KT-ORD-%')
            ->orderByRaw("CAST(REPLACE(order_number, 'KT-ORD-', '') AS UNSIGNED) DESC")
            ->first();

        if ($lastOrder) {
            $lastNumber = (int) str_replace('KT-ORD-', '', $lastOrder->order_number);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 100001;
        }

        return 'KT-ORD-' . $nextNumber;
    }
}
