<?php

namespace App\Services\Manufacturing;

use App\Models\ProductionBatch;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\ProductCombination;
use Illuminate\Support\Facades\DB;
use Exception;

class FinishedGoodsConversionService
{
    /**
     * Convert completed Production Batch WIP units into sellable storefront finished goods inventory.
     *
     * @param ProductionBatch $batch
     * @param array $data Additional optional mapping overrides (productId, productCombinationId, lotNumber, targetWarehouse)
     * @return ProductionBatch
     * @throws Exception
     */
    public function convertBatchToFinishedGoods(ProductionBatch $batch, array $data = []): ProductionBatch
    {
        return DB::transaction(function () use ($batch, $data) {
            $product = $batch->manufacturingProduct;
            if (!$product) {
                throw new Exception("Cannot convert batch: No manufacturing product linked.");
            }

            // Fallback dynamically to custom values passed during conversion form submission if not configured on the master
            $productId = $data['productId'] ?? $product->product_id;
            $combinationId = $data['product_combination_id'] ?? $product->product_combination_id;

            if (empty($productId) && empty($combinationId)) {
                throw new Exception("Cannot convert batch: Manufacturing Product [{$product->name}] has no mapped Storefront Product/Variant.");
            }

            if ($batch->is_converted) {
                throw new Exception("Cannot convert batch: This batch has already been converted to finished goods.");
            }

            if (!$batch->isReadyForConversion()) {
                throw new Exception("Cannot convert batch: designated Final Production Step is pending or batch status is not Completed.");
            }

            $goodUnits = (int) ($batch->total_finished_quantity ?: $batch->planned_quantity);
            if ($goodUnits <= 0) {
                throw new Exception("Cannot convert batch: Good units available for conversion must be greater than zero.");
            }

            if ($product->is_packaging_used) {
                $finalTask = $product->getFinalTask();
                $finalJob = $batch->job;

                foreach ($product->packagingMaterials as $pkgMat) {
                    $requiredQty = $goodUnits * (float) $pkgMat->pivot->required_quantity;

                    // Fetch active batches for the packaging material using FIFO
                    $invBatches = \App\Models\InventoryBatch::active()
                        ->byMaterial($pkgMat->id)
                        ->orderBy('purchase_date', 'asc')
                        ->orderBy('id', 'asc')
                        ->get();

                    $remaining = $requiredQty;
                    foreach ($invBatches as $invBatch) {
                        if ($remaining <= 0) {
                            break;
                        }

                        $deduct = min($remaining, (float) $invBatch->balance_quantity);
                        $invBatch->deductQuantity($deduct);

                        $allocatedCost = $deduct * (float) ($invBatch->purchase_rate ?: $invBatch->unit_cost);

                        // Log packaging material consumption
                        \App\Models\JobMaterialConsumption::create([
                            'job_code' => $finalJob?->job_code ?? 'CONVERSION',
                            'production_job_id' => $finalJob?->id,
                            'inventory_batch_id' => $invBatch->id,
                            'task_id' => $finalTask?->id ?? ($finalJob?->task_id),
                            'quantity_consumed' => $deduct,
                            'unit_cost' => $invBatch->purchase_rate ?: $invBatch->unit_cost,
                            'total_cost' => $allocatedCost,
                        ]);

                        $remaining -= $deduct;
                    }

                    if ($remaining > 0) {
                        throw new Exception("Insufficient inventory for packaging material: {$pkgMat->name}. Shortage: {$remaining}");
                    }
                }
            }

            // Recalculate and cache batch cost summary before conversion finishes
            $costingService = resolve(\App\Services\Manufacturing\ProductionCostingService::class);
            $costingService->cacheBatchCostSummary($batch->id);
            $batch->refresh(); // Reload batch with latest calculated costs

            // Determine stock increment target
            $targetEntity = null;
            if (!empty($combinationId)) {
                $targetEntity = ProductCombination::findOrFail($combinationId);
            } else {
                $targetEntity = Product::findOrFail($productId);
            }

            // Increment target stock
            $targetEntity->increment('stock_quantity', $goodUnits);

            $lotNumber = $data['lotNumber'] ?? ("LOT-" . ($product->code ?? 'PROD') . "-" . now()->format('Y-m-d'));

            // Record immutable Stock Movement Log
            InventoryMovement::create([
                'product_id' => !empty($combinationId) ? $targetEntity->product_id : $targetEntity->id,
                'product_combination_id' => !empty($combinationId) ? $targetEntity->id : null,
                'quantity_change' => $goodUnits,
                'unit_cost' => $batch->total_production_cost > 0 && $goodUnits > 0 ? round($batch->total_production_cost / $goodUnits, 2) : 0.00,
                'reference_type' => ProductionBatch::class,
                'reference_id' => $batch->id,
                'movement_type' => 'manufacturing_inward',
                'notes' => $data['notes'] ?? "Finished Goods Conversion for Batch {$batch->batch_code} (Lot #{$lotNumber}, WH: " . ($data['targetWarehouse'] ?? 'Finished Goods WH - Zone A') . ")",
            ]);

            // Mark batch as converted
            $batch->update([
                'is_converted' => true,
                'remarks' => trim($batch->remarks . "\n[System: Converted {$goodUnits} units to storefront stock under Lot: {$lotNumber} at " . now()->format('Y-m-d H:i:s') . "]"),
            ]);

            return $batch;
        });
    }
}
