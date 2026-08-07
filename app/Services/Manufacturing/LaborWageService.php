<?php

namespace App\Services\Manufacturing;

use App\Models\Labor;
use App\Models\ManufacturingProduct;
use App\Models\JobLaborAllocation;
use App\Traits\ApiResponseTrait;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Http\JsonResponse;

class LaborWageService
{
    use ApiResponseTrait;

    /**
     * Calculate Job Work Wage based on the formula:
     * Wage = Quantity * Task Standard Rate * (1 + Efficiency Modifier %)   * 
     * Note: Laborers with a Monthly Salary bypass this calculation, 
     * as they receive a fixed salary rather than a per-piece wage.
     *
     * @param float|int $quantityCompleted
     * @param float|int $standardLaborRate
     * @return float
     */
    public function calculateJobWorkWage($quantityCompleted, $standardLaborRate): float
    {
        return (float) $quantityCompleted * (float) $standardLaborRate;
    }

    /**
     * Process quantity allocations per laborer during a Job execution and calculate wages dynamically.
     *
     * @param array $allocations Array of allocations, e.g. [['labor_id' => 1, 'manufacturing_product_id' => 2, 'quantity' => 40]]
     * @param mixed $jobId
     * @param mixed $manufacturingProductId
     * @param mixed $taskId
     * @param mixed $productionBatchId
     * @return JsonResponse
     */
    public function processAllocations(array $allocations, $jobId, $manufacturingProductId, $taskId, $productionBatchId = null): JsonResponse
    {
        try {
            $createdAllocations = DB::transaction(function () use ($allocations, $jobId, $manufacturingProductId, $taskId, $productionBatchId) {
                $records = [];

                $defaultProduct = $manufacturingProductId instanceof ManufacturingProduct
                    ? $manufacturingProductId
                    : ManufacturingProduct::find($manufacturingProductId);

                foreach ($allocations as $allocation) {
                    $laborId = $allocation['labor_id'] ?? null;
                    $quantity = $allocation['quantity'] ?? $allocation['quantity_processed'] ?? 0;
                    $rowProductId = $allocation['manufacturing_product_id'] ?? ($defaultProduct ? $defaultProduct->id : $manufacturingProductId);

                    if (!$laborId) {
                        throw new Exception("Labor ID is required for each allocation.");
                    }

                    $labor = Labor::find($laborId);
                    if (!$labor) {
                        throw new Exception("Labor record not found for ID: {$laborId}.");
                    }

                    $product = ManufacturingProduct::find($rowProductId) ?? $defaultProduct;

                    $calculatedWage = null;

                    if ($labor->payment_method === 'job_work') {
                        $rate = null;

                        // Step 1: Explicitly query the manufacturing_product_task pivot for the configured
                        // task-specific standard_labor_rate by (manufacturing_product_id, task_id) combination.
                        if ($rowProductId && $taskId) {
                            $pivotRate = DB::table('manufacturing_product_task')
                                ->where('manufacturing_product_id', $rowProductId)
                                ->where('task_id', $taskId)
                                ->value('standard_labor_rate');

                            if (!is_null($pivotRate)) {
                                $rate = (float) $pivotRate;
                            }
                        }

                        // Step 2: Fall back to model accessor if pivot returned nothing.
                        if (is_null($rate) && $product) {
                            $rate = $product->getStandardLaborRateForTask($taskId);
                        }

                        if (is_null($rate)) {
                            $productName = $product ? $product->name : "ID {$rowProductId}";
                            throw new Exception("Standard Labor Rate is missing for Task ID {$taskId} on Product {$productName}. Please configure task routing rates in Manufacturing Product Master. Cannot calculate wage for Job Work laborer: {$labor->name}.");
                        }

                        $calculatedWage = $this->calculateJobWorkWage($quantity, $rate);
                    }

                    $records[] = JobLaborAllocation::create([
                        'production_batch_id' => $productionBatchId,
                        'job_id' => $jobId,
                        'labor_id' => $labor->id,
                        'manufacturing_product_id' => $product ? $product->id : $rowProductId,
                        'task_id' => $taskId,
                        'quantity_processed' => $quantity,
                        'calculated_wage' => $calculatedWage,
                    ]);
                }

                return $records;
            });

            return $this->successResponse(
                'Job labor allocations processed and wages calculated successfully.',
                ['allocations' => $createdAllocations],
                200
            );
        } catch (Exception $e) {
            return $this->errorResponse(
                $e->getMessage(),
                ['error' => $e->getMessage()],
                400
            );
        }
    }
}
