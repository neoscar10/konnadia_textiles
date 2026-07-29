<?php

namespace App\Services\Manufacturing;

use App\Models\ProductionBatch;
use App\Models\ProductionJob;
use App\Models\ManufacturingProduct;
use App\Models\Task;
use App\Traits\ApiResponseTrait;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Http\JsonResponse;

class ProductionWorkflowService
{
    use ApiResponseTrait;

    /**
     * Initiate a new Production Batch and automatically create its first linked Job
     * based on the product's routing task.
     *
     * @param mixed $productId
     * @param mixed $supervisorId
     * @param int $plannedQuantity
     * @param string $priority
     * @param string|null $remarks
     * @param string|null $batchDate
     * @return JsonResponse
     */
    public function initiateBatch($productId, $supervisorId, int $plannedQuantity, string $priority = 'Normal', ?string $remarks = null, ?string $batchDate = null): JsonResponse
    {
        try {
            $result = DB::transaction(function () use ($productId, $supervisorId, $plannedQuantity, $priority, $remarks, $batchDate) {
                $product = ManufacturingProduct::find($productId);
                if (!$product) {
                    throw new Exception("Manufacturing Product not found for ID: {$productId}.");
                }

                // Create the Production Batch record
                $batch = ProductionBatch::create([
                    'batch_date' => $batchDate ?? now()->format('Y-m-d'),
                    'supervisor_id' => $supervisorId,
                    'manufacturing_product_id' => $product->id,
                    'planned_quantity' => $plannedQuantity,
                    'priority' => $priority,
                    'status' => 'Created',
                    'remarks' => $remarks,
                ]);

                // Identify the first task in product's routing sequence
                $firstTask = $product->tasks()->first();
                if (!$firstTask) {
                    $firstTask = Task::where('status', true)->first();
                }

                if (!$firstTask) {
                    throw new Exception("No production task routing found. Please configure tasks in Task Master first.");
                }

                // Automatically create the first linked Job record
                $job = ProductionJob::create([
                    'production_batch_id' => $batch->batch_code,
                    'production_batch_db_id' => $batch->id,
                    'manufacturing_product_id' => $product->id,
                    'task_id' => $firstTask->id,
                    'supervisor_id' => $supervisorId,
                    'job_date' => $batch->batch_date,
                    'target_quantity' => $plannedQuantity,
                    'status' => 'pending',
                    'notes' => "Auto-initiated initial stage: {$firstTask->name}",
                ]);

                return ['batch' => $batch, 'job' => $job];
            });

            return $this->successResponse(
                "Production Batch {$result['batch']->batch_code} initiated successfully with First Job {$result['job']->job_code}.",
                $result,
                201
            );
        } catch (Exception $e) {
            return $this->errorResponse(
                $e->getMessage(),
                ['error' => $e->getMessage()],
                400
            );
        }
    }

    /**
     * Complete a Production Job, evaluate forward quantities, and automatically
     * generate the next stage Job ID based on product routing rules.
     *
     * @param mixed $jobId
     * @return JsonResponse
     */
    public function completeJob($jobId): JsonResponse
    {
        try {
            $result = DB::transaction(function () use ($jobId) {
                $job = ProductionJob::with([
                    'manufacturingProduct.tasks',
                    'batch',
                    'productOutputs',
                    'wastages',
                    'alterations',
                    'allocations',
                ])->findOrFail($jobId);

                if ($job->status === 'completed') {
                    return [
                        'job' => $job,
                        'nextJob' => null,
                        'isFinalStep' => false,
                        'alreadyCompleted' => true,
                    ];
                }

                // 1. Calculate Produced, Wasted, and Altered quantities
                $producedQty = (int) $job->productOutputs()->sum('quantity_produced');
                if ($producedQty <= 0) {
                    // Fallback to total processed quantity in stage allocations if productOutputs were not explicitly recorded
                    $producedQty = (int) $job->allocations()->where('task_id', $job->task_id)->sum('quantity_processed');
                }
                if ($producedQty <= 0) {
                    $producedQty = (int) $job->target_quantity;
                }

                $wastedQty = (float) $job->wastages()->where('task_id', $job->task_id)->sum('quantity_wasted');
                $alteredQty = (int) $job->alterations()->sum('source_quantity');

                // Formula: Next Job Input = (Total Produced Qty) - (Quantity Wasted) - (Quantity Altered)
                $validForwardQuantity = (int) max(0, $producedQty - $wastedQty - $alteredQty);

                // 2. Mark current job as completed
                $job->update(['status' => 'completed']);

                // 3. Consult Product Routing sequence to identify next task
                $product = $job->manufacturingProduct;
                $nextTask = $product ? $product->getNextTask($job->task_id) : null;

                $nextJob = null;
                $isFinalStep = false;

                if ($nextTask) {
                    // Auto-generate downstream Job ID for the next manufacturing stage
                    $latestJobId = ProductionJob::max('id') ?? 0;
                    $nextJobCode = "JOB-" . date('Y') . "-" . str_pad($latestJobId + 1, 4, '0', STR_PAD_LEFT);

                    $nextJob = ProductionJob::create([
                        'job_code' => $nextJobCode,
                        'production_batch_id' => $job->production_batch_id,
                        'production_batch_db_id' => $job->production_batch_db_id,
                        'manufacturing_product_id' => $job->manufacturing_product_id,
                        'task_id' => $nextTask->id,
                        'supervisor_id' => $job->supervisor_id,
                        'job_date' => now()->format('Y-m-d'),
                        'target_quantity' => $validForwardQuantity,
                        'status' => 'pending',
                        'notes' => "Auto-generated downstream stage: {$nextTask->name} (Transferred Input: {$validForwardQuantity} Pcs)",
                    ]);
                } else {
                    // Final Task Handling: Mark batch workflow as completed
                    $isFinalStep = true;
                    if ($job->batch) {
                        $job->batch->update(['status' => 'Completed']);
                    }
                }

                return [
                    'job' => $job,
                    'nextJob' => $nextJob,
                    'validForwardQuantity' => $validForwardQuantity,
                    'isFinalStep' => $isFinalStep,
                ];
            });

            $message = isset($result['alreadyCompleted'])
                ? "Job {$result['job']->job_code} is already marked as completed."
                : ($result['isFinalStep']
                    ? "Job {$result['job']->job_code} completed! Final production step reached — Batch is ready for Finished Goods conversion."
                    : "Job {$result['job']->job_code} completed! Auto-generated downstream Job {$result['nextJob']?->job_code} (Target Input: {$result['validForwardQuantity']} Pcs).");

            return $this->successResponse($message, $result, 200);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), ['error' => $e->getMessage()], 400);
        }
    }
}
