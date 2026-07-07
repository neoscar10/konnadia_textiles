<?php

namespace App\Services\Portal;

use App\Models\User;
use App\Models\Product;
use App\Models\ProductCombination;
use App\Models\ProductUnit;

class CustomerPricingService
{
    /**
     * Get the discount percentage for a product and customer.
     */
    public function getDiscountForCustomer(Product $product, ?User $user): float
    {
        if (!$user) {
            return 0.0;
        }
        $customer = $user->customer;
        if (!$customer || !$customer->customer_level_id || !$customer->is_active) {
            return 0.0;
        }

        $levelId = $customer->customer_level_id;

        // Check if there is a product-specific override for this customer level
        $override = $product->customerLevelPrices()
            ->where('customer_level_id', $levelId)
            ->first();

        if ($override && $override->discount_percentage !== null) {
            return (float) $override->discount_percentage;
        }

        // Otherwise, fall back to the level's default discount
        $level = $customer->level;
        if ($level && $level->is_active) {
            return (float) $level->discount_percentage;
        }

        return 0.0;
    }

    /**
     * Calculate customer-specific price for a product or product combination.
     */
    public function calculateCustomerPrice(Product $product, ?User $user, ?ProductCombination $combination = null): array
    {
        $basePrice = $combination && $combination->price !== null 
            ? (float) $combination->price 
            : (float) $product->base_price;

        $customer = $user ? $user->customer : null;
        if (!$customer || !$customer->is_active) {
            return [
                'base_price' => (float) $product->base_price,
                'effective_base_price' => $basePrice,
                'discount_percentage' => 0.0,
                'customer_price' => $basePrice,
                'currency' => 'INR',
                'discount_source' => 'none'
            ];
        }

        $levelId = $customer->customer_level_id;
        $discount = 0.0;
        $source = 'none';

        // Check override first
        $override = $product->customerLevelPrices()
            ->where('customer_level_id', $levelId)
            ->first();

        if ($override && $override->discount_percentage !== null) {
            $discount = (float) $override->discount_percentage;
            $source = 'product_override';
        } else {
            $level = $customer->level;
            if ($level && $level->is_active) {
                $discount = (float) $level->discount_percentage;
                $source = 'customer_level_default';
            }
        }

        $sellingPrice = $basePrice - ($basePrice * ($discount / 100));

        return [
            'base_price' => (float) $product->base_price,
            'effective_base_price' => $basePrice,
            'discount_percentage' => $discount,
            'customer_price' => round($sellingPrice, 2),
            'currency' => 'INR',
            'discount_source' => $source
        ];
    }

    /**
     * Calculate price for a specific product unit based on base price per piece.
     */
    public function calculateUnitPrice(float $baseCustomerPrice, ProductUnit $unit): float
    {
        $conversion = $unit->conversion_to_base ? (float) $unit->conversion_to_base : 1.0;
        return round($baseCustomerPrice * $conversion, 2);
    }
}
