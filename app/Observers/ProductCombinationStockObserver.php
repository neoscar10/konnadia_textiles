<?php

namespace App\Observers;

use App\Models\ProductCombination;
use App\Services\Inventory\StockReminderService;

class ProductCombinationStockObserver
{
    protected StockReminderService $reminderService;

    public function __construct(StockReminderService $reminderService)
    {
        $this->reminderService = $reminderService;
    }

    /**
     * Handle ProductCombination updated event.
     */
    public function updated(ProductCombination $combination): void
    {
        if ($combination->wasChanged('stock_quantity') && $combination->product) {
            $originalStock = (int) $combination->getOriginal('stock_quantity');
            $newStock = $combination->stock_quantity === null ? PHP_INT_MAX : (int) $combination->stock_quantity;

            if ($originalStock <= 0 && $newStock > 0) {
                $this->reminderService->notifySubscribersIfReplenished($combination->product, $combination);
            }
        }
    }
}
