<?php

namespace App\Observers;

use App\Models\Product;
use App\Models\ProductCombination;
use App\Services\Inventory\StockReminderService;

class ProductStockObserver
{
    protected StockReminderService $reminderService;

    public function __construct(StockReminderService $reminderService)
    {
        $this->reminderService = $reminderService;
    }

    /**
     * Handle Product updated event.
     */
    public function updated(Product $product): void
    {
        if ($product->wasChanged('stock_quantity')) {
            $originalStock = (int) $product->getOriginal('stock_quantity');
            $newStock = $product->stock_quantity === null ? PHP_INT_MAX : (int) $product->stock_quantity;

            if ($originalStock <= 0 && $newStock > 0) {
                $this->reminderService->notifySubscribersIfReplenished($product);
            }
        }
    }
}
