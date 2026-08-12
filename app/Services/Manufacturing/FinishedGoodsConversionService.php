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

                        \App\Services\InventoryBatchLogger::log($invBatch->id, 'consumed', $deduct, $batch->id, 'Packaging material auto-consumed during finished goods conversion');

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

    /**
     * Convert multi-job completed products into a Storefront Product or Variant bundle set.
     *
     * @param int|null $targetProductId
     * @param int|null $targetCombinationId
     * @param int $assembledSets
     * @param array $jobComponents Array of ['production_job_id' => int, 'quantity_per_set' => int]
     * @param string|null $notes
     * @return \App\Models\StorefrontProductBundle
     * @throws Exception
     */
    public function convertJobsToStorefrontBundle(?int $targetProductId, ?int $targetCombinationId, int $assembledSets, array $jobComponents, ?string $notes = null): \App\Models\StorefrontProductBundle
    {
        if (empty($targetProductId) && empty($targetCombinationId)) {
            throw new Exception("Please select a Storefront Product or Variant.");
        }

        if ($assembledSets <= 0) {
            throw new Exception("Quantity of sets to convert must be at least 1.");
        }

        if (empty($jobComponents)) {
            throw new Exception("Please select at least one completed production job component.");
        }

        return DB::transaction(function () use ($targetProductId, $targetCombinationId, $assembledSets, $jobComponents, $notes) {
            // 1. Create StorefrontProductBundle record
            $bundle = \App\Models\StorefrontProductBundle::create([
                'product_id' => $targetProductId ?: null,
                'product_combination_id' => $targetCombinationId ?: null,
                'created_by' => auth()->id(),
                'quantity_created' => $assembledSets,
                'notes' => $notes ?: "Storefront Conversion from Production Jobs Hub",
            ]);

            // 2. Validate availability and deduct unconverted quantities from each source job
            foreach ($jobComponents as $comp) {
                $jobId = intval($comp['production_job_id'] ?? 0);
                $qtyPerSet = intval($comp['quantity_per_set'] ?? 1);
                $totalNeeded = $assembledSets * $qtyPerSet;

                if ($jobId <= 0 || $totalNeeded <= 0) {
                    continue;
                }

                $job = \App\Models\ProductionJob::findOrFail($jobId);

                if ($job->status !== 'completed') {
                    throw new Exception("Cannot convert Job {$job->job_code}: This job is currently '{$job->status}' and has not completed all manufacturing stages yet.");
                }

                $available = $job->remaining_unconverted_quantity;

                if ($totalNeeded > $available) {
                    throw new Exception("Insufficient unconverted units in Job {$job->job_code} ({$job->manufacturingProduct?->name}). Requested: {$totalNeeded} Pcs, Available: {$available} Pcs.");
                }

                // Update converted_quantity on job
                $newConvertedQty = (int) $job->converted_quantity + $totalNeeded;
                $job->update([
                    'converted_quantity' => $newConvertedQty,
                ]);

                // Also update parent ProductionBatch converted_quantity if linked
                if ($job->batch) {
                    $job->batch->update([
                        'converted_quantity' => (int) $job->batch->converted_quantity + $totalNeeded,
                        'is_converted' => ((int) $job->batch->converted_quantity + $totalNeeded) >= ((int) $job->batch->total_finished_quantity ?: (int) $job->batch->planned_quantity),
                    ]);
                }

                // Log Item link
                \App\Models\StorefrontProductBundleItem::create([
                    'storefront_product_bundle_id' => $bundle->id,
                    'production_batch_id' => $job->production_batch_db_id ?: ($job->batch?->id),
                    'manufacturing_product_id' => $job->manufacturing_product_id,
                    'quantity_used' => $totalNeeded,
                ]);
            }

            // 3. Increment Storefront Product or Combination Stock
            $targetEntity = null;
            if ($targetCombinationId) {
                $targetEntity = ProductCombination::findOrFail($targetCombinationId);
            } else {
                $targetEntity = Product::findOrFail($targetProductId);
            }

            $targetEntity->increment('stock_quantity', $assembledSets);

            // 4. Record Immutable Stock Movement Log
            InventoryMovement::create([
                'product_id' => $targetCombinationId ? $targetEntity->product_id : $targetEntity->id,
                'product_combination_id' => $targetCombinationId ? $targetEntity->id : null,
                'quantity_change' => $assembledSets,
                'unit_cost' => 0.00,
                'reference_type' => \App\Models\StorefrontProductBundle::class,
                'reference_id' => $bundle->id,
                'movement_type' => 'manufacturing_inward',
                'notes' => "Converted {$assembledSets} Storefront Set(s) via Bundle Code {$bundle->bundle_code}",
            ]);

            return $bundle;
        });
    }
}
