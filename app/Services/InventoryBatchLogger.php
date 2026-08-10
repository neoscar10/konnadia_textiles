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
     * @param int|string|null $productionBatchId
     * @param string|null $description
     */
    public static function log(int $batchId, string $action, ?float $quantity = null, int|string|null $productionBatchId = null, ?string $description = null): void
    {
        $relId = null;
        if (is_numeric($productionBatchId)) {
            $relId = (int) $productionBatchId;
        } elseif (is_string($productionBatchId) && !empty($productionBatchId)) {
            $pb = \App\Models\ProductionBatch::where('batch_code', $productionBatchId)->first();
            $relId = $pb?->id;
        }

        InventoryBatchLog::create([
            'inventory_batch_id' => $batchId,
            'user_id' => Auth::id(),
            'action' => $action,
            'quantity' => $quantity,
            'related_production_batch_id' => $relId,
            'description' => $description,
        ]);
    }
}
?>
