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

            if (!empty($data['packaging'])) {
                $finalTask = $product->getFinalTask();
                $finalJob = $batch->job;

                foreach ($data['packaging'] as $pkg) {
                    $pkgMatId = intval($pkg['raw_material_id'] ?? 0);
                    $qtyUsed = floatval($pkg['quantity_used'] ?? 0);
                    if ($pkgMatId <= 0 || $qtyUsed <= 0) {
                        continue;
                    }

                    $pkgMat = \App\Models\RawMaterial::findOrFail($pkgMatId);

                    // Fetch active batches for the packaging material using FIFO
                    $invBatches = \App\Models\InventoryBatch::active()
                        ->byMaterial($pkgMatId)
                        ->orderBy('purchase_date', 'asc')
                        ->orderBy('id', 'asc')
                        ->get();

                    $remaining = $qtyUsed;
                    foreach ($invBatches as $invBatch) {
                        if ($remaining <= 0) {
                            break;
                        }

                        $deduct = min($remaining, (float) $invBatch->balance_quantity);
                        $invBatch->deductQuantity($deduct);

                        $allocatedCost = $deduct * (float) ($invBatch->purchase_rate ?: $invBatch->unit_cost);

                        // Log packaging material consumption
                        $taskId = $finalTask?->id
                            ?? ($finalJob?->task_id)
                            ?? \App\Models\Task::orderBy('id')->value('id');

                        \App\Models\JobMaterialConsumption::create([
                            'job_code' => $finalJob?->job_code ?? 'CONVERSION',
                            'production_job_id' => $finalJob?->id,
                            'inventory_batch_id' => $invBatch->id,
                            'task_id' => $taskId,
                            'quantity_consumed' => $deduct,
                            'unit_cost' => $invBatch->purchase_rate ?: $invBatch->unit_cost,
                            'total_cost' => $allocatedCost,
                        ]);

                        \App\Services\InventoryBatchLogger::log($invBatch->id, 'consumed', $deduct, $batch->id, 'Packaging material consumed during finished goods conversion');

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
     * Convert multi-job completed products into a Storefront Product bundle set.
     *
     * @param int $targetProductId
     * @param int $assembledSets
     * @param array $jobComponents Array of ['production_job_id' => int, 'quantity_per_set' => int]
     * @param string|null $notes
     * @return \App\Models\StorefrontProductBundle
     * @throws Exception
     */
    public function convertJobsToStorefrontBundle(int $targetProductId, int $assembledSets, array $jobComponents, ?string $notes = null, array $packaging = []): \App\Models\StorefrontProductBundle
    {
        if (empty($targetProductId)) {
            throw new Exception("Please select a Storefront Product.");
        }

        if ($assembledSets <= 0) {
            throw new Exception("Quantity of sets to convert must be at least 1.");
        }

        if (empty($jobComponents)) {
            throw new Exception("Please select at least one completed production job component.");
        }

        return DB::transaction(function () use ($targetProductId, $assembledSets, $jobComponents, $notes, $packaging) {
            // 1. Create StorefrontProductBundle record
            $bundle = \App\Models\StorefrontProductBundle::create([
                'product_id' => $targetProductId,
                'product_combination_id' => null,
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

                // Resolve batch ID or auto-create batch for standalone jobs if missing
                $batchId = $job->production_batch_db_id ?: ($job->batch?->id);
                if (!$batchId && $job->production_batch_id) {
                    // Try to find existing batch by batch_code first
                    $existingBatch = \App\Models\ProductionBatch::where('batch_code', $job->production_batch_id)->first();
                    if ($existingBatch) {
                        $batchId = $existingBatch->id;
                        $job->update(['production_batch_db_id' => $existingBatch->id]);
                    }
                }
                if (!$batchId && $job->manufacturing_product_id) {
                    $batch = \App\Models\ProductionBatch::create([
                        'batch_code' => $job->production_batch_id ?: ('PB-JOB-' . $job->id),
                        'manufacturing_product_id' => $job->manufacturing_product_id,
                        'planned_quantity' => $job->target_quantity ?: 1,
                        'total_finished_quantity' => $job->target_quantity ?: 1,
                        'unconverted_quantity' => $job->target_quantity ?: 1,
                        'status' => 'Completed',
                        'supervisor_id' => $job->supervisor_id ?: auth()->id(),
                        'batch_date' => $job->created_at ?? now(),
                    ]);
                    $batchId = $batch->id;
                    $job->update([
                        'production_batch_db_id' => $batch->id,
                        'production_batch_id' => $batch->batch_code,
                    ]);
                }

                // Log Item link
                \App\Models\StorefrontProductBundleItem::create([
                    'storefront_product_bundle_id' => $bundle->id,
                    'production_batch_id' => $batchId,
                    'manufacturing_product_id' => $job->manufacturing_product_id,
                    'quantity_used' => $totalNeeded,
                ]);
            }

            // 3. FIFO Deduct packaging materials if provided
            if (!empty($packaging)) {
                foreach ($packaging as $pkg) {
                    $pkgMatId = intval($pkg['raw_material_id'] ?? 0);
                    $qtyUsed = floatval($pkg['quantity_used'] ?? 0);
                    if ($pkgMatId <= 0 || $qtyUsed <= 0) {
                        continue;
                    }

                    $pkgMat = \App\Models\RawMaterial::findOrFail($pkgMatId);

                    // Fetch active batches for the packaging material using FIFO
                    $invBatches = \App\Models\InventoryBatch::active()
                        ->byMaterial($pkgMatId)
                        ->orderBy('purchase_date', 'asc')
                        ->orderBy('id', 'asc')
                        ->get();

                    $remaining = $qtyUsed;
                    foreach ($invBatches as $invBatch) {
                        if ($remaining <= 0) {
                            break;
                        }

                        $deduct = min($remaining, (float) $invBatch->balance_quantity);
                        $invBatch->deductQuantity($deduct);

                        $allocatedCost = $deduct * (float) ($invBatch->purchase_rate ?: $invBatch->unit_cost);

                        // Log packaging material consumption
                        $firstJobId = intval($jobComponents[0]['production_job_id'] ?? 0);
                        $firstJob = $firstJobId ? \App\Models\ProductionJob::find($firstJobId) : null;
                        $taskId = $firstJob?->task_id ?? \App\Models\Task::orderBy('id')->value('id');

                        \App\Models\JobMaterialConsumption::create([
                            'job_code' => $firstJob?->job_code ?? 'CONVERSION',
                            'production_job_id' => $firstJob?->id,
                            'inventory_batch_id' => $invBatch->id,
                            'task_id' => $taskId,
                            'quantity_consumed' => $deduct,
                            'unit_cost' => $invBatch->purchase_rate ?: $invBatch->unit_cost,
                            'total_cost' => $allocatedCost,
                        ]);

                        \App\Services\InventoryBatchLogger::log($invBatch->id, 'consumed', $deduct, null, 'Packaging material consumed during storefront conversion');

                        $remaining -= $deduct;
                    }

                    if ($remaining > 0) {
                        throw new Exception("Insufficient inventory for packaging material: {$pkgMat->name}. Shortage: {$remaining}");
                    }
                }
            }

            // 4. Increment Storefront Product Stock
            $targetEntity = Product::findOrFail($targetProductId);
            $targetEntity->increment('stock_quantity', $assembledSets);

            // 5. Record Immutable Stock Movement Log
            InventoryMovement::create([
                'product_id' => $targetEntity->id,
                'product_combination_id' => null,
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

    /**
     * Perform stock availability check for a Front-End Product bundle conversion.
     */
    public function checkFrontEndStockAvailability(\App\Models\FrontEndProduct $frontEndProduct, int $targetQty, int $unitFactor = 1): array
    {
        $targetQty = max(1, $targetQty);
        $unitFactor = max(1, $unitFactor);

        $mfgStock = [];
        $pkgStock = [];
        $missingItems = [];
        $canProceed = true;

        $frontEndProduct->loadMissing(['components.manufacturingProduct', 'packagingItems.rawMaterial']);

        foreach ($frontEndProduct->components as $comp) {
            $mfgProduct = $comp->manufacturingProduct;
            if (!$mfgProduct) {
                continue;
            }

            $totalReq = (int) ($comp->quantity * $unitFactor * $targetQty);

            // Calculate total unconverted factory stock available across completed Jobs & Batches
            $jobAvailable = (int) \App\Models\ProductionJob::where('manufacturing_product_id', $mfgProduct->id)
                ->where('status', 'completed')
                ->get()
                ->sum(fn($j) => $j->remaining_unconverted_quantity);

            $batchAvailable = (int) \App\Models\ProductionBatch::where('manufacturing_product_id', $mfgProduct->id)
                ->where('status', 'Completed')
                ->where('is_converted', false)
                ->get()
                ->sum(fn($b) => $b->remaining_unconverted_quantity);

            // Total available is sum of available completed WIP items
            $available = max($jobAvailable, $batchAvailable);

            $isEnough = $available >= $totalReq;
            if (!$isEnough) {
                $canProceed = false;
                $shortage = $totalReq - $available;
                $missingItems[] = "{$mfgProduct->name} (Short by {$shortage} Pcs)";
            }

            $mfgStock[] = [
                'manufacturing_product_id' => $mfgProduct->id,
                'name' => $mfgProduct->name,
                'qty_per_unit' => $comp->quantity,
                'unit_factor' => $unitFactor,
                'total_required' => $totalReq,
                'available_stock' => $available,
                'is_enough' => $isEnough,
            ];
        }

        foreach ($frontEndProduct->packagingItems as $pkg) {
            $rawMat = $pkg->rawMaterial;
            if (!$rawMat) {
                continue;
            }

            $totalReqPkg = (int) ($pkg->quantity * $targetQty);

            $pkgStock[] = [
                'raw_material_id' => $rawMat->id,
                'name' => $rawMat->name,
                'qty_per_unit' => $pkg->quantity,
                'total_required' => $totalReqPkg,
            ];
        }

        return [
            'canProceed' => $canProceed,
            'mfgStock' => $mfgStock,
            'pkgStock' => $pkgStock,
            'missingItems' => $missingItems,
        ];
    }

    /**
     * Convert completed factory WIP output into a finished goods lot batch tied to a Front-End Product.
     */
    public function convertFrontEndProductBatch(array $data): \App\Models\FinishedGoodsBatch
    {
        $feProductId = intval($data['front_end_product_id'] ?? 0);
        $produceQty = max(1, intval($data['converted_qty'] ?? 1));
        $unitVal = $data['unit'] ?? 'Piece (Pcs)';
        $unitFactor = max(1, intval($data['unit_factor'] ?? 1));
        $designId = trim($data['design_id'] ?? 'DSG-108');
        $isPublished = isset($data['is_published']) ? (bool) $data['is_published'] : true;
        $notes = $data['notes'] ?? null;
        $storefrontMode = $data['storefront_mode'] ?? 'new';
        $existingStorefrontProductId = intval($data['existing_storefront_product_id'] ?? 0);

        $feProduct = \App\Models\FrontEndProduct::with(['components.manufacturingProduct', 'packagingItems.rawMaterial'])->findOrFail($feProductId);

        // Run availability check
        $stockCheck = $this->checkFrontEndStockAvailability($feProduct, $produceQty, $unitFactor);
        if (!$stockCheck['canProceed']) {
            throw new Exception("Cannot proceed to conversion: Insufficient unconverted manufacturing stock for: " . implode(', ', $stockCheck['missingItems']));
        }

        // Generate barcode
        $dIdClean = strtoupper(preg_replace('/[^a-zA-Z0-9]/', '', $designId));
        if (empty($dIdClean)) {
            $dIdClean = 'DSG108';
        }
        $barcodeBase = "FG-{$dIdClean}-" . now()->format('Y') . "-" . str_pad((string) $produceQty, 4, '0', STR_PAD_LEFT);
        $barcode = $barcodeBase;
        $counter = 1;
        while (\App\Models\FinishedGoodsBatch::where('barcode', $barcode)->exists()) {
            $barcode = "{$barcodeBase}-" . str_pad((string) $counter, 2, '0', STR_PAD_LEFT);
            $counter++;
        }

        return DB::transaction(function () use ($feProduct, $produceQty, $unitVal, $unitFactor, $designId, $barcode, $isPublished, $notes, $storefrontMode, $existingStorefrontProductId) {
            // 1. Create FinishedGoodsBatch record
            $fgBatch = \App\Models\FinishedGoodsBatch::create([
                'barcode' => $barcode,
                'front_end_product_id' => $feProduct->id,
                'design_id' => $designId,
                'converted_qty' => $produceQty,
                'unit' => $unitVal,
                'unit_factor' => $unitFactor,
                'converted_date' => now(),
                'is_published' => $isPublished,
                'created_by' => auth()->id(),
                'notes' => $notes,
                'costing_summary' => [
                    'fabricCost' => '₹310.00',
                    'laborCost' => '₹42.00',
                    'packagingCost' => '₹10.00',
                    'totalUnitCost' => '₹362.00',
                ],
            ]);

            // 2. Deduct manufacturing output stock FIFO from completed ProductionJobs / Batches
            foreach ($feProduct->components as $comp) {
                $mfgProduct = $comp->manufacturingProduct;
                if (!$mfgProduct) {
                    continue;
                }

                $totalNeeded = $comp->quantity * $unitFactor * $produceQty;
                $remainingNeeded = $totalNeeded;

                // First try deducting from completed ProductionJobs
                $completedJobs = \App\Models\ProductionJob::where('manufacturing_product_id', $mfgProduct->id)
                    ->where('status', 'completed')
                    ->orderBy('created_at', 'asc')
                    ->get();

                foreach ($completedJobs as $job) {
                    if ($remainingNeeded <= 0) {
                        break;
                    }

                    $unconverted = $job->remaining_unconverted_quantity;
                    if ($unconverted <= 0) {
                        continue;
                    }

                    $deduct = min($remainingNeeded, $unconverted);
                    $job->update([
                        'converted_quantity' => (int) $job->converted_quantity + $deduct,
                    ]);

                    \App\Models\FinishedGoodsBatchItem::create([
                        'finished_goods_batch_id' => $fgBatch->id,
                        'manufacturing_product_id' => $mfgProduct->id,
                        'production_batch_id' => $job->production_batch_db_id ?: ($job->batch?->id),
                        'production_job_id' => $job->id,
                        'quantity_used' => $deduct,
                    ]);

                    $remainingNeeded -= $deduct;
                }

                // If remaining needed, try deducting from completed ProductionBatches
                if ($remainingNeeded > 0) {
                    $completedBatches = \App\Models\ProductionBatch::where('manufacturing_product_id', $mfgProduct->id)
                        ->where('status', 'Completed')
                        ->where('is_converted', false)
                        ->orderBy('created_at', 'asc')
                        ->get();

                    foreach ($completedBatches as $batch) {
                        if ($remainingNeeded <= 0) {
                            break;
                        }

                        $unconverted = $batch->remaining_unconverted_quantity;
                        if ($unconverted <= 0) {
                            continue;
                        }

                        $deduct = min($remainingNeeded, $unconverted);
                        $newConverted = (int) $batch->converted_quantity + $deduct;
                        $batch->update([
                            'converted_quantity' => $newConverted,
                            'is_converted' => $newConverted >= ($batch->total_finished_quantity ?: $batch->planned_quantity),
                        ]);

                        \App\Models\FinishedGoodsBatchItem::create([
                            'finished_goods_batch_id' => $fgBatch->id,
                            'manufacturing_product_id' => $mfgProduct->id,
                            'production_batch_id' => $batch->id,
                            'production_job_id' => null,
                            'quantity_used' => $deduct,
                        ]);

                        $remainingNeeded -= $deduct;
                    }
                }
            }

            // 3. FIFO deduct packaging materials
            foreach ($feProduct->packagingItems as $pkg) {
                $rawMat = $pkg->rawMaterial;
                if (!$rawMat) {
                    continue;
                }

                $totalPkgNeeded = $pkg->quantity * $produceQty;
                $remainingPkg = $totalPkgNeeded;

                $invBatches = \App\Models\InventoryBatch::active()
                    ->byMaterial($rawMat->id)
                    ->orderBy('purchase_date', 'asc')
                    ->orderBy('id', 'asc')
                    ->get();

                foreach ($invBatches as $invBatch) {
                    if ($remainingPkg <= 0) {
                        break;
                    }

                    $deduct = min($remainingPkg, (float) $invBatch->balance_quantity);
                    $invBatch->deductQuantity($deduct);
                    $remainingPkg -= $deduct;

                    \App\Services\InventoryBatchLogger::log($invBatch->id, 'consumed', $deduct, null, "Packaging material consumed during Front-End conversion {$fgBatch->barcode}");
                }

                \App\Models\FinishedGoodsBatchPackaging::create([
                    'finished_goods_batch_id' => $fgBatch->id,
                    'raw_material_id' => $rawMat->id,
                    'quantity_deducted' => $totalPkgNeeded,
                ]);
            }

            // 4. Update or Create Storefront Product stock
            $storefrontProduct = null;

            if ($storefrontMode === 'existing' && $existingStorefrontProductId > 0) {
                $storefrontProduct = \App\Models\Product::find($existingStorefrontProductId);
            }

            if (!$storefrontProduct) {
                // Find existing product by SKU or title, or create a new Product
                $storefrontProduct = \App\Models\Product::where('sku', $feProduct->sku)
                    ->orWhere('title', $feProduct->name)
                    ->first();

                if (!$storefrontProduct) {
                    $storefrontProduct = \App\Models\Product::create([
                        'title' => $feProduct->name,
                        'sku' => $feProduct->sku,
                        'description' => $feProduct->description,
                        'is_active' => $isPublished,
                        'stock_quantity' => 0,
                        'base_price' => 0.00,
                    ]);

                    if ($feProduct->category_id) {
                        $storefrontProduct->categories()->syncWithoutDetaching([$feProduct->category_id]);
                    }
                }
            }

            if ($storefrontProduct) {
                $storefrontProduct->increment('stock_quantity', $produceQty);

                \App\Models\InventoryMovement::create([
                    'product_id' => $storefrontProduct->id,
                    'product_combination_id' => null,
                    'quantity_change' => $produceQty,
                    'unit_cost' => 0.00,
                    'reference_type' => \App\Models\FinishedGoodsBatch::class,
                    'reference_id' => $fgBatch->id,
                    'movement_type' => 'manufacturing_inward',
                    'notes' => "Finished Goods Conversion Barcode {$fgBatch->barcode} ({$produceQty} {$unitVal} of {$feProduct->name})",
                ]);
            }

            return $fgBatch;
        });
    }
}

