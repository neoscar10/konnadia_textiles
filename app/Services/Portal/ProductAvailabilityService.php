<?php

namespace App\Services\Portal;

use App\Models\Product;
use App\Models\ProductCombination;

class ProductAvailabilityService
{
    /**
     * Get stock details and availability attributes for a product.
     */
    public function getProductAvailability(Product $product): array
    {
        if ($product->product_type === 'manufactured') {
            return [
                'available_quantity' => 999999,
                'status' => 'in_stock',
                'label' => 'In Stock',
                'is_purchasable' => true,
            ];
        }

        $hasCombinations = $product->combinations()->where('is_active', true)->exists();

        if ($hasCombinations) {
            $qty = (int) $product->combinations()->where('is_active', true)->sum('stock_quantity');
        } else {
            $qty = (int) $product->stock_quantity;
        }

        $status = 'out_of_stock';
        $label = 'Out of Stock';
        $isPurchasable = false;

        if ($qty > 10) {
            $status = 'in_stock';
            $label = 'In Stock';
            $isPurchasable = true;
        } elseif ($qty > 0) {
            $status = 'low_stock';
            $label = 'Low Stock';
            $isPurchasable = true;
        }

        return [
            'available_quantity' => $qty,
            'status' => $status,
            'label' => $label,
            'is_purchasable' => $isPurchasable,
        ];
    }

    /**
     * Get availability attributes for a specific product combination.
     */
    public function getCombinationAvailability(ProductCombination $combination): array
    {
        if ($combination->product && $combination->product->product_type === 'manufactured') {
            return [
                'available_quantity' => 999999,
                'status' => 'in_stock',
                'label' => 'In Stock',
                'is_purchasable' => true,
            ];
        }

        $qty = (int) $combination->stock_quantity;
        $isActive = (bool) $combination->is_active;

        $status = 'out_of_stock';
        $label = 'Out of Stock';
        $isPurchasable = false;

        if ($isActive) {
            if ($qty > 10) {
                $status = 'in_stock';
                $label = 'In Stock';
                $isPurchasable = true;
            } elseif ($qty > 0) {
                $status = 'low_stock';
                $label = 'Low Stock';
                $isPurchasable = true;
            }
        }

        return [
            'available_quantity' => $qty,
            'status' => $status,
            'label' => $label,
            'is_purchasable' => $isPurchasable,
        ];
    }

    /**
     * Check if a product or combination is purchasable.
     */
    public function isPurchasable(Product $product, ?ProductCombination $combination = null): bool
    {
        if ($product->product_type === 'manufactured') {
            return true;
        }

        if ($combination) {
            $avail = $this->getCombinationAvailability($combination);
            return $avail['is_purchasable'];
        }

        $avail = $this->getProductAvailability($product);
        return $avail['is_purchasable'];
    }
}
