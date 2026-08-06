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

                // Create the single master Production Job record
                $job = ProductionJob::create([
                    'production_batch_id' => $batch->batch_code,
                    'production_batch_db_id' => $batch->id,
                    'manufacturing_product_id' => $product->id,
                    'supervisor_id' => $supervisorId,
                    'job_date' => $batch->batch_date,
                    'target_quantity' => $plannedQuantity,
                    'status' => 'in_progress',
                    'notes' => $remarks ?? "Master Production Job for Batch {$batch->batch_code}",
                ]);

                // Populate stage executions from Product Routing tasks
                $routingTasks = $product->tasks;
                if ($routingTasks->isEmpty()) {
                    $fallbackTask = Task::where('status', true)->first();
                    if ($fallbackTask) {
                        $routingTasks = collect([$fallbackTask]);
                    }
                }

                if ($routingTasks->isEmpty()) {
                    throw new Exception("No production task routing found for {$product->name}. Please configure tasks in Product Master or Task Master first.");
                }

                foreach ($routingTasks as $idx => $task) {
                    \App\Models\JobStageExecution::create([
                        'production_job_id' => $job->id,
                        'task_id' => $task->id,
                        'sequence_number' => $idx + 1,
                        'target_quantity' => $plannedQuantity,
                        'status' => $idx === 0 ? 'in_progress' : 'pending',
                        'started_at' => $idx === 0 ? now() : null,
                    ]);
                }

                return ['batch' => $batch, 'job' => $job];
            });

            return $this->successResponse(
                "Production Batch {$result['batch']->batch_code} initiated successfully with Master Job {$result['job']->job_code}.",
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
     * Complete a stage execution (or current active stage on a job), evaluate forward quantities,
     * and unlock/progress to the next stage execution within the same job.
     *
     * @param mixed $jobId
     * @param mixed|null $taskId
     * @return JsonResponse
     */
    public function completeJob($jobId, $taskId = null): JsonResponse
    {
        try {
            $result = DB::transaction(function () use ($jobId, $taskId) {
                $job = ProductionJob::with([
                    'manufacturingProduct.tasks',
                    'batch',
                    'stageExecutions.task',
                    'productOutputs',
                    'wastages',
                    'alterations',
                    'allocations',
                ])->findOrFail($jobId);

                $job->ensureStageExecutionsExist();
                $job->unsetRelation('stageExecutions');
                $job->load('stageExecutions.task');

                // Identify current stage execution to complete
                $stageExecution = null;
                if ($taskId) {
                    $stageExecution = $job->stageExecutions->firstWhere('task_id', $taskId);
                } else {
                    $stageExecution = $job->stageExecutions->firstWhere('status', 'in_progress')
                        ?? $job->stageExecutions->firstWhere('status', 'pending');
                }

                if (!$stageExecution) {
                    throw new Exception("No active or pending stage execution found on Job {$job->job_code}.");
                }

                if ($stageExecution->status === 'completed') {
                    return [
                        'job' => $job,
                        'stageExecution' => $stageExecution,
                        'isFinalStep' => false,
                        'alreadyCompleted' => true,
                    ];
                }

                $currTaskId = $stageExecution->task_id;

                // 1. Calculate Produced, Wasted, and Altered quantities for this stage
                $explicitProductOutputSum = (int) $job->productOutputs()->where('task_id', $currTaskId)->sum('quantity_produced');
                $wastedQty = (float) $job->wastages()->where('task_id', $currTaskId)->sum('quantity_wasted');
                $alteredQty = (int) $job->alterations()->whereHas('sourceProduct', fn() => true)->sum('source_quantity');

                if ($explicitProductOutputSum > 0) {
                    $validForwardQuantity = (int) max(0, $explicitProductOutputSum - $wastedQty - $alteredQty);
                } else {
                    $workerProcessedQty = (int) $job->allocations()->where('task_id', $currTaskId)->sum('quantity_processed');
                    if ($workerProcessedQty <= 0) {
                        $workerProcessedQty = (int) $stageExecution->target_quantity;
                    }

                    $validForwardQuantity = (int) max(0, $workerProcessedQty - $wastedQty - $alteredQty);
                }

                // 2. Mark current stage execution as completed
                $stageExecution->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                ]);

                // 3. Find next stage execution in sequence
                $nextStage = $job->stageExecutions
                    ->where('sequence_number', '>', $stageExecution->sequence_number)
                    ->sortBy('sequence_number')
                    ->first();

                $isFinalStep = false;

                if ($nextStage) {
                    $nextStage->update([
                        'status' => 'in_progress',
                        'target_quantity' => $validForwardQuantity,
                        'started_at' => now(),
                    ]);
                } else {
                    // Final Task Handling: Mark master job and batch workflow as completed
                    $isFinalStep = true;
                    $job->update(['status' => 'completed']);
                    if ($job->batch) {
                        $job->batch->update([
                            'status' => 'Completed',
                            'completed_at' => now(),
                        ]);
                        event(new \App\Events\ProductionBatchCompleted($job->batch->id));
                    }
                }

                return [
                    'job' => $job,
                    'stageExecution' => $stageExecution,
                    'nextStage' => $nextStage,
                    'validForwardQuantity' => $validForwardQuantity,
                    'isFinalStep' => $isFinalStep,
                ];
            });

            $message = isset($result['alreadyCompleted'])
                ? "Stage {$result['stageExecution']->task?->name} is already marked as completed."
                : ($result['isFinalStep']
                    ? "Job {$result['job']->job_code} completed! Final step reached — Batch is ready for Finished Goods conversion."
                    : "Stage {$result['stageExecution']->task?->name} completed! Transferred {$result['validForwardQuantity']} Pcs to next stage: {$result['nextStage']?->task?->name}.");

            return $this->successResponse($message, $result, 200);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), ['error' => $e->getMessage()], 400);
        }
    }

    /**
     * SRS Wrapper: Initialize Production Batch and auto-spawn first job.
     */
    public function initializeProductionBatch(array $batchData, int $manufacturingProductId): JsonResponse
    {
        return $this->initiateBatch(
            $manufacturingProductId,
            $batchData['supervisor_id'] ?? auth()->id(),
            $batchData['planned_quantity'] ?? 500,
            $batchData['priority'] ?? 'Normal',
            $batchData['remarks'] ?? '',
            $batchData['batch_date'] ?? now()->format('Y-m-d')
        );
    }

    /**
     * SRS Wrapper: Complete a job and advance workflow progression.
     */
    public function completeJobAndProgress(ProductionJob $job, array $completionData = []): JsonResponse
    {
        return $this->completeJob($job->id);
    }
}

