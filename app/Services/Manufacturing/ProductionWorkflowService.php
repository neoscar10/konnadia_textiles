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
    public function initiateBatch($productId, $supervisorId, int $plannedQuantity, string $priority = 'Normal', ?string $remarks = null, ?string $batchDate = null, $patternId = null, array $items = []): JsonResponse
    {
        try {
            $result = DB::transaction(function () use ($productId, $supervisorId, $plannedQuantity, $priority, $remarks, $batchDate, $patternId, $items) {
                if (empty($items)) {
                    $items = [
                        [
                            'manufacturing_product_id' => $productId,
                            'pattern_id'               => $patternId,
                            'planned_quantity'         => $plannedQuantity,
                        ]
                    ];
                }

                $totalPlannedQty = array_sum(array_column($items, 'planned_quantity'));
                $firstItem = $items[0];

                $firstProduct = !empty($firstItem['manufacturing_product_id']) ? ManufacturingProduct::find($firstItem['manufacturing_product_id']) : null;
                $firstPattern = !empty($firstItem['pattern_id']) ? \App\Models\ManufacturingProductPattern::with('tasks')->find($firstItem['pattern_id']) : null;

                $factorySupervisor = $supervisorId ? \App\Models\FactorySupervisor::find($supervisorId) : null;
                $factorySupervisorId = $factorySupervisor?->id;

                // Create the Production Batch record
                $batch = ProductionBatch::create([
                    'batch_date'               => $batchDate ?? now()->format('Y-m-d'),
                    'supervisor_id'            => auth()->id() ?: 1,
                    'factory_supervisor_id'    => $factorySupervisorId,
                    'manufacturing_product_id' => $firstProduct?->id,
                    'pattern_id'               => $firstPattern?->id,
                    'planned_quantity'         => $totalPlannedQty ?: $plannedQuantity,
                    'priority'                 => $priority,
                    'status'                   => 'Created',
                    'remarks'                  => $remarks,
                ]);

                $createdJobs = [];

                foreach ($items as $index => $itemData) {
                    $pId = $itemData['manufacturing_product_id'] ?? null;
                    $patId = $itemData['pattern_id'] ?? null;
                    $itemQty = intval($itemData['planned_quantity'] ?? 200);

                    $product = $pId ? ManufacturingProduct::find($pId) : null;
                    $pattern = $patId ? \App\Models\ManufacturingProductPattern::with('tasks')->find($patId) : null;

                    $year = date('Y');
                    $maxNum = ProductionJob::where('job_code', 'like', "JOB-{$year}-%")
                        ->get()
                        ->map(fn($j) => (int) str_replace("JOB-{$year}-", '', $j->job_code))
                        ->max() ?: 0;
                    $newJobCode = sprintf("JOB-%s-%04d", $year, $maxNum + 1);

                    $job = ProductionJob::create([
                        'job_code'                 => $newJobCode,
                        'production_batch_id'      => $batch->batch_code,
                        'production_batch_db_id'   => $batch->id,
                        'manufacturing_product_id' => $product?->id,
                        'pattern_id'               => $pattern?->id,
                        'supervisor_id'            => auth()->id() ?: 1,
                        'factory_supervisor_id'    => $factorySupervisorId,
                        'job_date'                 => $batch->batch_date,
                        'target_quantity'          => $itemQty,
                        'status'                   => 'in_progress',
                        'notes'                    => $remarks ?? "Production Job for {$product?->name} under Batch {$batch->batch_code}",
                    ]);

                    // Populate stage executions from Pattern Tasks, Product Routing tasks or default active tasks
                    $routingTasks = collect();
                    if ($pattern && $pattern->tasks->isNotEmpty()) {
                        $routingTasks = $pattern->tasks;
                    } elseif ($product && $product->tasks->isNotEmpty()) {
                        $routingTasks = $product->tasks;
                    } else {
                        $routingTasks = Task::where('status', true)->get();
                    }

                    if ($routingTasks->isEmpty()) {
                        $fallbackTask = Task::where('status', true)->first();
                        if ($fallbackTask) {
                            $routingTasks = collect([$fallbackTask]);
                        }
                    }

                    foreach ($routingTasks as $idx => $task) {
                        \App\Models\JobStageExecution::create([
                            'production_job_id' => $job->id,
                            'task_id'          => $task->id,
                            'sequence_number'   => $idx + 1,
                            'target_quantity'   => $itemQty,
                            'status'            => $idx === 0 ? 'in_progress' : 'pending',
                            'started_at'        => $idx === 0 ? now() : null,
                        ]);
                    }

                    $createdJobs[] = $job;
                }

                return ['batch' => $batch, 'jobs' => $createdJobs, 'job' => $createdJobs[0]];
            });

            $jobCount = count($result['jobs']);
            return $this->successResponse(
                "Production Batch {$result['batch']->batch_code} initiated successfully with {$jobCount} Production Job(s).",
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
     * Initialize Production Batch and auto-spawn first job.
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
     * Complete a job and advance workflow progression.
     */
    public function completeJobAndProgress(ProductionJob $job, array $completionData = []): JsonResponse
    {
        return $this->completeJob($job->id);
    }

    /**
     * Spawn distinct production job flows for each product output cut during the cutting stage.
     */
    public function spawnDistinctProductFlowsFromCutting(ProductionJob $job, array $cuttingOutputs): array
    {
        $productOutputs = [];
        foreach ($cuttingOutputs as $out) {
            $pId = (int) ($out['manufacturing_product_id'] ?? 0);
            $qty = (int) ($out['quantity'] ?? 0);
            if ($pId > 0 && $qty > 0) {
                $productOutputs[$pId] = ($productOutputs[$pId] ?? 0) + $qty;
            }
        }

        if (empty($productOutputs)) {
            return [$job];
        }

        $productIds = array_values(array_keys($productOutputs));
        $firstProductId = $productIds[0];
        $firstProductQty = $productOutputs[$firstProductId];

        // 1. Update current primary job for first product SKU cut output
        $job->update([
            'manufacturing_product_id' => $firstProductId,
            'target_quantity' => $firstProductQty,
        ]);

        $cuttingTask = Task::where('name', 'like', '%Cut%')->first() ?? Task::first();
        $cuttingTaskId = $cuttingTask?->id;

        $job->ensureStageExecutionsExist();
        $job->unsetRelation('stageExecutions');
        $job->load('stageExecutions');

        $cuttingStage = $job->stageExecutions->firstWhere('task_id', $cuttingTaskId)
            ?? $job->stageExecutions->first();

        if ($cuttingStage) {
            $cuttingStage->update([
                'status' => 'completed',
                'target_quantity' => $firstProductQty,
                'completed_quantity' => $firstProductQty,
                'completed_at' => now(),
            ]);

            $nextStage = $job->stageExecutions
                ->where('sequence_number', '>', $cuttingStage->sequence_number)
                ->sortBy('sequence_number')
                ->first();

            if ($nextStage) {
                $nextStage->update([
                    'status' => 'in_progress',
                    'target_quantity' => $firstProductQty,
                    'started_at' => now(),
                ]);
            }
        }

        $allFlowJobs = [$job];

        // 2. Create distinct production jobs for subsequent product SKUs
        for ($i = 1; $i < count($productIds); $i++) {
            $pId = $productIds[$i];
            $pQty = $productOutputs[$pId];
            $product = ManufacturingProduct::find($pId);

            $existingJob = ProductionJob::where('production_batch_id', $job->production_batch_id)
                ->where('manufacturing_product_id', $pId)
                ->where('id', '!=', $job->id)
                ->first();

            if ($existingJob) {
                $childJob = $existingJob;
                $childJob->update([
                    'target_quantity' => $pQty,
                    'status' => 'in_progress',
                ]);
            } else {
                $year = date('Y');
                $maxNum = ProductionJob::where('job_code', 'like', "JOB-{$year}-%")
                    ->get()
                    ->map(fn($j) => (int) str_replace("JOB-{$year}-", '', $j->job_code))
                    ->max() ?: 0;
                $newJobCode = sprintf("JOB-%s-%04d", $year, $maxNum + 1);

                $childJob = ProductionJob::create([
                    'job_code' => $newJobCode,
                    'production_batch_id' => $job->production_batch_id,
                    'production_batch_db_id' => $job->production_batch_db_id,
                    'manufacturing_product_id' => $pId,
                    'supervisor_id' => $job->supervisor_id,
                    'factory_supervisor_id' => $job->factory_supervisor_id ?? $job->batch?->factory_supervisor_id,
                    'job_date' => $job->job_date ?? now()->format('Y-m-d'),
                    'target_quantity' => $pQty,
                    'status' => 'in_progress',
                    'notes' => "Distinct production flow for {$product?->name} spawned from Cutting Job {$job->job_code}",
                ]);
            }

            $childJob->ensureStageExecutionsExist();
            $childJob->unsetRelation('stageExecutions');
            $childJob->load('stageExecutions');

            $childCuttingStage = $childJob->stageExecutions->firstWhere('task_id', $cuttingTaskId)
                ?? $childJob->stageExecutions->first();

            if ($childCuttingStage) {
                $childCuttingStage->update([
                    'status' => 'completed',
                    'target_quantity' => $pQty,
                    'completed_quantity' => $pQty,
                    'completed_at' => now(),
                ]);

                $childNextStage = $childJob->stageExecutions
                    ->where('sequence_number', '>', $childCuttingStage->sequence_number)
                    ->sortBy('sequence_number')
                    ->first();

                if ($childNextStage) {
                    $childNextStage->update([
                        'status' => 'in_progress',
                        'target_quantity' => $pQty,
                        'started_at' => now(),
                    ]);
                }
            }

            \App\Models\JobProductionOutput::where('production_job_id', $job->id)
                ->where('manufacturing_product_id', $pId)
                ->update(['production_job_id' => $childJob->id]);

            \App\Models\JobLaborAllocation::where('job_id', $job->job_code)
                ->where('manufacturing_product_id', $pId)
                ->update([
                    'job_id' => $childJob->job_code,
                ]);

            // Move and split JobMaterialConsumption and JobWastage for child jobs
            $childOutputs = \App\Models\JobProductionOutput::where('production_job_id', $childJob->id)->get();
            foreach ($childOutputs as $cOut) {
                if ($cOut->inventory_bale_roll_id) {
                    $parentConsumption = \App\Models\JobMaterialConsumption::where('production_job_id', $job->id)
                        ->where('inventory_bale_roll_id', $cOut->inventory_bale_roll_id)
                        ->first();

                    if ($parentConsumption) {
                        $shareRatio = $parentConsumption->total_fabric_cost > 0 
                            ? ((float)$cOut->total_fabric_cost / (float)$parentConsumption->total_fabric_cost) 
                            : 0;

                        $childConsQty = round((float)$parentConsumption->quantity_consumed * $shareRatio, 4);
                        $childTotalCost = round((float)$parentConsumption->total_cost * $shareRatio, 2);

                        \App\Models\JobMaterialConsumption::create([
                            'job_code' => $childJob->job_code,
                            'production_job_id' => $childJob->id,
                            'inventory_batch_id' => $parentConsumption->inventory_batch_id,
                            'inventory_bale_roll_id' => $parentConsumption->inventory_bale_roll_id,
                            'task_id' => $parentConsumption->task_id,
                            'quantity_consumed' => $childConsQty,
                            'unit_cost' => $parentConsumption->unit_cost,
                            'total_cost' => $childTotalCost,
                            'consumed_length' => $childConsQty,
                            'calculated_base_cost' => $cOut->calculated_base_cost,
                            'allocated_wastage_cost' => $cOut->allocated_wastage_cost,
                            'total_fabric_cost' => $cOut->total_fabric_cost,
                        ]);

                        $parentConsQty = max(0, (float)$parentConsumption->quantity_consumed - $childConsQty);
                        $parentTotalCost = max(0, (float)$parentConsumption->total_cost - $childTotalCost);
                        $parentBaseCost = max(0, (float)$parentConsumption->calculated_base_cost - (float)$cOut->calculated_base_cost);
                        $parentWastageCost = max(0, (float)$parentConsumption->allocated_wastage_cost - (float)$cOut->allocated_wastage_cost);

                        $parentConsumption->update([
                            'quantity_consumed' => $parentConsQty,
                            'total_cost' => $parentTotalCost,
                            'consumed_length' => $parentConsQty,
                            'calculated_base_cost' => $parentBaseCost,
                            'allocated_wastage_cost' => $parentWastageCost,
                            'total_fabric_cost' => $parentTotalCost,
                        ]);
                    }

                    $parentWastage = \App\Models\JobWastage::where('production_job_id', $job->id)
                        ->where('inventory_bale_roll_id', $cOut->inventory_bale_roll_id)
                        ->first();

                    if ($parentWastage) {
                        $purchaseRate = $parentConsumption ? (float)$parentConsumption->unit_cost : 1;
                        $childWastageQty = $purchaseRate > 0 ? round((float)$cOut->allocated_wastage_cost / $purchaseRate, 4) : 0;

                        if ($childWastageQty > 0) {
                            \App\Models\JobWastage::create([
                                'job_code' => $childJob->job_code,
                                'production_job_id' => $childJob->id,
                                'manufacturing_product_id' => $childJob->manufacturing_product_id,
                                'inventory_bale_roll_id' => $cOut->inventory_bale_roll_id,
                                'task_id' => $parentWastage->task_id,
                                'quantity_wasted' => $childWastageQty,
                                'reason' => $parentWastage->reason,
                            ]);

                            $parentWastageQty = max(0, (float)$parentWastage->quantity_wasted - $childWastageQty);
                            if ($parentWastageQty <= 0) {
                                $parentWastage->delete();
                            } else {
                                $parentWastage->update([
                                    'quantity_wasted' => $parentWastageQty,
                                ]);
                            }
                        }
                    }
                }
            }

            $allFlowJobs[] = $childJob;
        }

        return $allFlowJobs;
    }

    /**
     * Skip an intermediate stage execution on a job, passing target quantity forward.
     *
     * @param mixed $jobId
     * @param mixed $taskId
     * @return JsonResponse
     */
    public function skipStage($jobId, $taskId): JsonResponse
    {
        try {
            $result = DB::transaction(function () use ($jobId, $taskId) {
                $job = ProductionJob::with(['stageExecutions.task'])->findOrFail($jobId);
                $stageExecution = $job->stageExecutions->firstWhere('task_id', $taskId);

                if (!$stageExecution) {
                    throw new Exception("Stage not found on job.");
                }

                // Identify carry-forward quantity from previous stage
                $prevStage = $job->stageExecutions
                    ->where('sequence_number', '<', $stageExecution->sequence_number)
                    ->sortByDesc('sequence_number')
                    ->first();

                $forwardQty = $prevStage ? ($prevStage->completed_quantity > 0 ? $prevStage->completed_quantity : $prevStage->target_quantity) : $stageExecution->target_quantity;

                $stageExecution->update([
                    'status' => 'completed',
                    'is_skipped' => true,
                    'completed_at' => now(),
                ]);

                // Progress to next stage
                $nextStage = $job->stageExecutions
                    ->where('sequence_number', '>', $stageExecution->sequence_number)
                    ->sortBy('sequence_number')
                    ->first();

                if ($nextStage) {
                    $nextStage->update([
                        'status' => 'in_progress',
                        'target_quantity' => $forwardQty,
                        'started_at' => now(),
                    ]);
                }

                return [
                    'job' => $job,
                    'skippedStage' => $stageExecution,
                    'nextStage' => $nextStage,
                    'forwardQty' => $forwardQty,
                ];
            });

            return $this->successResponse("Stage {$result['skippedStage']->task?->name} skipped.", $result, 200);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), ['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Unskip a previously skipped stage execution on a job.
     *
     * @param mixed $jobId
     * @param mixed $taskId
     * @return JsonResponse
     */
    public function unskipStage($jobId, $taskId): JsonResponse
    {
        try {
            $result = DB::transaction(function () use ($jobId, $taskId) {
                $job = ProductionJob::with(['stageExecutions.task'])->findOrFail($jobId);
                $stageExecution = $job->stageExecutions->firstWhere('task_id', $taskId);

                if (!$stageExecution) {
                    throw new Exception("Stage not found on job.");
                }

                // Identify carry-forward target quantity from previous completed stage
                $prevStage = $job->stageExecutions
                    ->where('sequence_number', '<', $stageExecution->sequence_number)
                    ->sortByDesc('sequence_number')
                    ->first();

                $targetQty = $prevStage 
                    ? ($prevStage->completed_quantity > 0 ? $prevStage->completed_quantity : $prevStage->target_quantity) 
                    : $job->target_quantity;

                $stageExecution->update([
                    'status'          => 'in_progress',
                    'is_skipped'      => false,
                    'target_quantity' => $targetQty,
                    'started_at'      => now(),
                ]);

                // Reset job overall status to in_progress if it was previously completed
                if ($job->status === 'completed') {
                    $job->update(['status' => 'in_progress']);
                }

                return [
                    'job'            => $job,
                    'unskippedStage' => $stageExecution,
                    'targetQty'      => $targetQty,
                ];
            });

            return $this->successResponse("Stage {$result['unskippedStage']->task?->name} unskipped successfully.", $result, 200);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), ['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Record a product alteration: creates child ProductionBatch, child ProductionJob, and JobAlteration record.
     *
     * @param ProductionJob $job
     * @param int $sourceProductId
     * @param int $sourceQty
     * @param int $targetProductId
     * @param int $targetQty
     * @param string|null $reason
     * @param int|null $targetPatternId
     * @return \App\Models\JobAlteration
     */
    public function recordJobAlteration(ProductionJob $job, int $sourceProductId, int $sourceQty, int $targetProductId, int $targetQty, ?string $reason = null, ?int $targetPatternId = null): \App\Models\JobAlteration
    {
        return DB::transaction(function () use ($job, $sourceProductId, $sourceQty, $targetProductId, $targetQty, $reason, $targetPatternId) {
            $parentBatch = $job->batch;
            if (!$parentBatch && !empty($job->production_batch_id)) {
                $parentBatch = ProductionBatch::where('batch_code', $job->production_batch_id)->first();
            }
            if (!$parentBatch && !empty($job->production_batch_db_id)) {
                $parentBatch = ProductionBatch::find($job->production_batch_db_id);
            }
            if (!$parentBatch) {
                $candidateCode = 'PB-' . date('Y') . '-' . str_pad($job->id, 4, '0', STR_PAD_LEFT);
                $parentBatch = ProductionBatch::where('batch_code', $candidateCode)->first();
                if (!$parentBatch) {
                    $latestBatchId = (int) (ProductionBatch::max('id') ?? 0);
                    $candidateCode = 'PB-' . date('Y') . '-' . str_pad($latestBatchId + 1, 4, '0', STR_PAD_LEFT);
                    while (ProductionBatch::where('batch_code', $candidateCode)->exists()) {
                        $latestBatchId++;
                        $candidateCode = 'PB-' . date('Y') . '-' . str_pad($latestBatchId + 1, 4, '0', STR_PAD_LEFT);
                    }
                    $parentBatch = ProductionBatch::create([
                        'batch_code'               => $candidateCode,
                        'manufacturing_product_id' => $job->manufacturing_product_id,
                        'planned_quantity'         => $job->target_quantity,
                        'status'                   => 'In Progress',
                        'supervisor_id'            => $job->supervisor_id ?? auth()->id(),
                    ]);
                }
                $job->update(['production_batch_db_id' => $parentBatch->id, 'production_batch_id' => $parentBatch->batch_code]);
            }

            $childCount = $parentBatch->childBatches()->count() + 1;
            $childBatchCode = $parentBatch->batch_code . "-A{$childCount}";
            while (ProductionBatch::where('batch_code', $childBatchCode)->exists()) {
                $childCount++;
                $childBatchCode = $parentBatch->batch_code . "-A{$childCount}";
            }

            $childBatch = ProductionBatch::create([
                'parent_batch_id'          => $parentBatch->id,
                'batch_code'               => $childBatchCode,
                'batch_date'               => now()->format('Y-m-d'),
                'supervisor_id'            => $job->supervisor_id ?? auth()->id(),
                'factory_supervisor_id'    => $job->factory_supervisor_id ?? $parentBatch->factory_supervisor_id,
                'manufacturing_product_id' => $targetProductId,
                'pattern_id'               => $targetPatternId,
                'planned_quantity'         => (int) $targetQty,
                'priority'                 => $parentBatch->priority ?? 'Normal',
                'status'                   => 'In Progress',
                'remarks'                  => "Child Alteration Batch derived from Parent Batch {$parentBatch->batch_code} (Source Job {$job->job_code})",
            ]);

            $targetProduct = ManufacturingProduct::find($targetProductId);
            $targetPattern = $targetPatternId ? \App\Models\ManufacturingProductPattern::with('tasks')->find($targetPatternId) : null;

            $routingTasks = collect();
            if ($targetPattern && $targetPattern->tasks->isNotEmpty()) {
                $routingTasks = $targetPattern->tasks;
            } elseif ($targetProduct && $targetProduct->tasks->isNotEmpty()) {
                $routingTasks = $targetProduct->tasks;
            } else {
                $routingTasks = Task::where('status', true)->get();
            }

            $firstTask = $routingTasks->first() ?? Task::where('status', true)->first();

            $latestJobId = ProductionJob::max('id') ?? 0;
            $childJobCode = "JOB-" . date('Y') . "-" . str_pad($latestJobId + 1, 4, '0', STR_PAD_LEFT);
            while (ProductionJob::where('job_code', $childJobCode)->exists()) {
                $latestJobId++;
                $childJobCode = "JOB-" . date('Y') . "-" . str_pad($latestJobId + 1, 4, '0', STR_PAD_LEFT);
            }

            $childJob = ProductionJob::create([
                'job_code'                 => $childJobCode,
                'production_batch_id'      => $childBatch->batch_code,
                'production_batch_db_id'   => $childBatch->id,
                'manufacturing_product_id' => $targetProductId,
                'pattern_id'               => $targetPattern?->id,
                'task_id'                  => $firstTask ? $firstTask->id : $job->task_id,
                'supervisor_id'            => $childBatch->supervisor_id,
                'factory_supervisor_id'    => $childBatch->factory_supervisor_id,
                'job_date'                 => now()->format('Y-m-d'),
                'target_quantity'          => (int) $targetQty,
                'status'                   => 'in_progress',
                'notes'                    => "Auto-initialized Job for Alteration Child Batch {$childBatch->batch_code}",
            ]);

            // Populate stage executions for the child alteration job
            foreach ($routingTasks as $idx => $task) {
                \App\Models\JobStageExecution::create([
                    'production_job_id' => $childJob->id,
                    'task_id'           => $task->id,
                    'sequence_number'   => $idx + 1,
                    'target_quantity'   => (int) $targetQty,
                    'status'            => $idx === 0 ? 'in_progress' : 'pending',
                    'started_at'        => $idx === 0 ? now() : null,
                ]);
            }

            $alteration = \App\Models\JobAlteration::create([
                'job_code'                  => $job->job_code,
                'production_job_id'         => $job->id,
                'source_product_id'         => $sourceProductId,
                'source_quantity'           => (int) $sourceQty,
                'target_product_id'         => $targetProductId,
                'target_pattern_id'         => $targetPatternId,
                'target_quantity'           => (int) $targetQty,
                'child_production_batch_id' => $childBatch->id,
                'child_production_job_id'   => $childJob->id,
            ]);

            return $alteration;
        });
    }
}
