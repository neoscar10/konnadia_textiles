<?php

namespace App\Services;

use App\Models\InventoryBatchLog;
use Illuminate\Support\Facades\Auth;

class InventoryBatchLogger
{
    /**
     * Record a log entry for an inventory batch.
     *
     * @param int $batchId
     * @param string $action
     * @param float|null $quantity
     * @param int|null $productionBatchId
     * @param string|null $description
     */
    public static function log(int $batchId, string $action, ?float $quantity = null, ?int $productionBatchId = null, ?string $description = null): void
    {
        InventoryBatchLog::create([
            'inventory_batch_id' => $batchId,
            'user_id' => Auth::id(),
            'action' => $action,
            'quantity' => $quantity,
            'related_production_batch_id' => $productionBatchId,
            'description' => $description,
        ]);
    }
}
?>
