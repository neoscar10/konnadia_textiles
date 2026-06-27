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
        // 'manufactured' type OR null stock = unlimited/N/A — always purchasable
        if ($product->product_type === 'manufactured' || $product->stock_quantity === null) {
            return [
                'available_quantity' => PHP_INT_MAX,
                'status' => 'in_stock',
                'label' => $product->stock_quantity === null ? 'N/A (Unlimited)' : 'In Stock',
                'is_purchasable' => true,
            ];
        }

        $hasCombinations = $product->combinations()->where('is_active', true)->exists();
        $isFallbackStock = false;

        if ($hasCombinations) {
            $hasDefinedComboStock = $product->combinations()
                ->where('is_active', true)
                ->whereNotNull('stock_quantity')
                ->exists();

            if ($hasDefinedComboStock) {
                $qty = (int) $product->combinations()->where('is_active', true)->sum('stock_quantity');
            } else {
                $qty = (int) $product->stock_quantity;
                $isFallbackStock = true;
            }
        } else {
            $qty = (int) $product->stock_quantity;
        }

        $status = 'out_of_stock';
        $label = 'Out of Stock';
        $isPurchasable = false;

        if ($qty > 10 || ($qty > 0 && $isFallbackStock)) {
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
        // 'manufactured' type OR null combination stock = unlimited/N/A — always purchasable
        if (($combination->product && $combination->product->product_type === 'manufactured')
            || $combination->stock_quantity === null
        ) {
            return [
                'available_quantity' => PHP_INT_MAX,
                'status' => 'in_stock',
                'label' => $combination->stock_quantity === null ? 'N/A (Unlimited)' : 'In Stock',
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
        // 'manufactured' type or null stock = always purchasable (N/A / unlimited)
        if ($product->product_type === 'manufactured' || $product->stock_quantity === null) {
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
