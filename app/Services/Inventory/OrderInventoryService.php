<?php

namespace App\Services\Inventory;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Facades\DB;

class OrderInventoryService
{
    /**
     * Validate current stock is enough for the order.
     * Returns an array with 'has_enough_stock' (bool) and 'shortages' (array).
     */
    public function validateOrderStock(Order $order): array
    {
        $order->load(['items.product', 'items.combination']);
        $shortages = [];
        $hasEnough = true;

        foreach ($order->items as $item) {
            if ($item->product && $item->product->product_type === 'manufactured') {
                continue; // Manufactured products have infinite stock and no inventory tracking
            }

            $baseQty = (int) ($item->quantity * $item->unit_conversion_quantity);
            $available = 0;

            if ($item->product_combination_id && $item->combination) {
                $available = (int) $item->combination->stock_quantity;
            } elseif ($item->product) {
                $available = (int) $item->product->stock_quantity;
            }

            if ($baseQty > $available) {
                $hasEnough = false;
                $shortages[] = [
                    'item_id' => $item->id,
                    'product_title' => $item->product_title,
                    'requested_base_quantity' => $baseQty,
                    'available_quantity' => $available,
                    'shortage' => $baseQty - $available,
                ];
            }
        }

        return [
            'has_enough_stock' => $hasEnough,
            'shortages' => $shortages,
        ];
    }

    /**
     * Deduct stock for the order.
     */
    public function deductStockForOrder(Order $order): void
    {
        if ($order->stock_deducted_at !== null) {
            return; // Already deducted
        }

        $order->load(['items.product', 'items.combination']);

        DB::transaction(function () use ($order) {
            $validation = $this->validateOrderStock($order);
            if (!$validation['has_enough_stock']) {
                throw new \RuntimeException("Unable to deduct stock. Insufficient stock for some items.");
            }

            foreach ($order->items as $item) {
                if ($item->product && $item->product->product_type === 'manufactured') {
                    continue;
                }

                $baseQty = (int) ($item->quantity * $item->unit_conversion_quantity);

                if ($item->product_combination_id && $item->combination) {
                    $item->combination->decrement('stock_quantity', $baseQty);
                } elseif ($item->product) {
                    $item->product->decrement('stock_quantity', $baseQty);
                }
            }

            $order->update(['stock_deducted_at' => now()]);
        });
    }

    /**
     * Restore stock for the order.
     */
    public function restoreStockForOrder(Order $order): void
    {
        if ($order->stock_deducted_at === null) {
            return; // Not deducted yet, nothing to restore
        }

        $order->load(['items.product', 'items.combination']);

        DB::transaction(function () use ($order) {
            foreach ($order->items as $item) {
                if ($item->product && $item->product->product_type === 'manufactured') {
                    continue;
                }

                $baseQty = (int) ($item->quantity * $item->unit_conversion_quantity);

                if ($item->product_combination_id && $item->combination) {
                    $item->combination->increment('stock_quantity', $baseQty);
                } elseif ($item->product) {
                    $item->product->increment('stock_quantity', $baseQty);
                }
            }

            $order->update(['stock_deducted_at' => null]);
        });
    }
}
