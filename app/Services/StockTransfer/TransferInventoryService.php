<?php

namespace App\Services\StockTransfer;

use App\Models\Product;
use App\Models\ProductCombination;
use App\Models\ProductTransfer;
use App\Models\ProductTransferItem;

class TransferInventoryService
{
    /**
     * Get available stock for a product or a specific variation.
     */
    public function getAvailableStock(Product $product, $combination = null): ?float
    {
        if ($combination) {
            $compModel = $combination instanceof ProductCombination 
                ? $combination 
                : ProductCombination::find($combination);
            return $compModel ? ($compModel->stock_quantity !== null ? (float)$compModel->stock_quantity : null) : null;
        }

        return $product->stock_quantity !== null ? (float)$product->stock_quantity : null;
    }

    /**
     * Determine if stock is tracked for a product or a specific variation.
     */
    public function isStockTracked(Product $product, $combination = null): bool
    {
        return $this->getAvailableStock($product, $combination) !== null;
    }

    /**
     * Deduct stock for all items in a transfer.
     */
    public function deductStock(ProductTransfer $transfer): void
    {
        $hasDeductions = false;

        foreach ($transfer->items as $item) {
            $product = Product::find($item->product_id);
            if (!$product) {
                continue;
            }

            $combination = null;
            if ($item->product_combination_id) {
                $combination = ProductCombination::find($item->product_combination_id);
            }

            $isTracked = $this->isStockTracked($product, $combination);
            $item->stock_tracked = $isTracked;

            if ($isTracked) {
                $availableBefore = $this->getAvailableStock($product, $combination);
                $item->available_stock_before = $availableBefore;

                $quantityToDeduct = (int)$item->quantity;
                $availableAfter = $availableBefore - $quantityToDeduct;
                $item->available_stock_after = $availableAfter;

                if ($combination) {
                    $combination->stock_quantity = (int)$availableAfter;
                    $combination->save();
                    
                    // Also decrement the parent product total stock if it's stored on the product model
                    if ($product->stock_quantity !== null) {
                        $product->stock_quantity = max(0, $product->stock_quantity - $quantityToDeduct);
                        $product->save();
                    }
                } else {
                    $product->stock_quantity = (int)$availableAfter;
                    $product->save();
                }

                $item->stock_deducted = true;
                $hasDeductions = true;
            } else {
                $item->stock_deducted = false;
                $item->available_stock_before = null;
                $item->available_stock_after = null;
            }

            $item->save();
        }

        $transfer->stock_deducted = $hasDeductions;
        $transfer->stock_deducted_at = $hasDeductions ? now() : null;
        $transfer->save();
    }
}
