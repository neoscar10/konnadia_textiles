<?php

namespace App\Livewire\Admin\Production;

use App\Models\ProductionJob;
use App\Models\ProductionBatch;
use App\Models\Labor;
use App\Models\Task;
use App\Models\ManufacturingProduct;
use App\Models\JobLaborAllocation;
use App\Models\InventoryBatch;
use App\Models\JobMaterialConsumption;
use App\Models\JobProductionOutput;
use App\Models\JobWastage;
use App\Models\JobAlteration;
use App\Services\Manufacturing\LaborWageService;
use App\Services\Manufacturing\ProductionWorkflowService;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('components.admin.layout')]
class JobDetailPage extends Component
{
    public ProductionJob $job;

    public string $activeTab = 'stages';
    public bool $showFinalCompletionModal = false;
    public $selectedTaskId = null;

    // Worker Allocations Form (Section 2 & 7)
    public array $laborAllocations = [];
    public array $bulkLaborSelections = [];

    // Raw Material Consumption Form
    public array $materialConsumptions = [];

    // Multi-Product Production Output Form (Section 4)
    public array $productionOutputs = [];

    // Wastage Form (Section 5)
    public array $wastageRecords = [];

    // Alteration Management Form (Section 6)
    public array $alterationRecords = [];

    // Fabric Cutting Session Form
    public $cuttingFabricBatchId = '';
    public $cuttingConsumedLength = '';
    public $cuttingWastageLength = 0;
    public $cuttingFabricWidth = 60.00;
    public array $cuttingOutputs = [];

    // Subsidiary Material Consumption Form (CAT-SUB BOM-driven)
    public array $subsidiaryConsumptions = []; // [['bom_raw_material_id'=>, 'bom_material_name'=>, 'unit'=>, 'expected_quantity'=>, 'inventory_batch_id'=>, 'actual_consumed'=>]]
    public bool $isTaskSubsidiary = false; // true when selected task is linked to CAT-SUB
    public bool $isTaskStitching = false;  // true when selected task is linked to CAT-STITCH

    public function mount($id)
    {
        $this->job = ProductionJob::with([
            'batch.childBatches',
            'manufacturingProduct.tasks',
            'allocations.labor',
            'allocations.task',
            'materialConsumptions.inventoryBatch.rawMaterial.category',
            'materialConsumptions.task',
            'productOutputs.manufacturingProduct',
            'productOutputs.task',
            'wastages.manufacturingProduct',
            'wastages.task',
            'alterations.sourceProduct',
            'alterations.targetProduct',
            'alterations.childBatch',
        ])->findOrFail($id);

        $firstTask = $this->routingTasks->first();
        $this->selectedTaskId = $firstTask ? $firstTask->id : null;

        $this->resetFormRows();
        $this->resetCuttingForm();
    }

    private function resetFormRows(): void
    {
        $defaultProdId = $this->job->manufacturing_product_id ?? '';

        if (empty($this->laborAllocations)) {
            $this->laborAllocations = [
                ['labor_id' => '', 'manufacturing_product_id' => $defaultProdId, 'quantity' => '']
            ];
        }

        if (empty($this->materialConsumptions)) {
            $this->materialConsumptions = [
                ['inventory_batch_id' => '', 'quantity_consumed' => '']
            ];
        }

        if (empty($this->productionOutputs)) {
            $this->productionOutputs = [
                ['manufacturing_product_id' => $defaultProdId, 'quantity_produced' => '']
            ];
        }

        if (empty($this->wastageRecords)) {
            $this->wastageRecords = [
                ['manufacturing_product_id' => $defaultProdId, 'quantity_wasted' => '', 'reason' => '']
            ];
        }

        if (empty($this->alterationRecords)) {
            $this->alterationRecords = [
                ['source_product_id' => $defaultProdId, 'source_quantity' => '', 'target_product_id' => '', 'target_quantity' => '']
            ];
        }
    }

    public function selectTask($taskId): void
    {
        $this->selectedTaskId = $taskId;
        $this->resetValidation();

        // Refresh job relationships to reflect latest stage state
        $this->job->load([
            'batch.childBatches',
            'allocations.labor',
            'allocations.task',
            'materialConsumptions.inventoryBatch.rawMaterial.category',
            'productOutputs.manufacturingProduct',
            'productOutputs.task',
            'wastages.manufacturingProduct',
            'wastages.task',
            'alterations.sourceProduct',
            'alterations.targetProduct',
            'alterations.childBatch',
        ]);

        $defaultProdId = $this->job->manufacturing_product_id ?? '';
        $this->laborAllocations = [['labor_id' => '', 'manufacturing_product_id' => $defaultProdId, 'quantity' => '']];
        $this->materialConsumptions = [['inventory_batch_id' => '', 'quantity_consumed' => '']];
        $this->productionOutputs = [['manufacturing_product_id' => $defaultProdId, 'quantity_produced' => '']];
        $this->wastageRecords = [['manufacturing_product_id' => $defaultProdId, 'quantity_wasted' => '', 'reason' => '']];
        $this->alterationRecords = [['source_product_id' => $defaultProdId, 'source_quantity' => '', 'target_product_id' => '', 'target_quantity' => '']];
        
        $this->resetCuttingForm();

        // Pre-fill subsidiary material consumptions if this task involves CAT-SUB
        $this->preloadSubsidiaryConsumptions();
    }

    /**
     * Build subsidiary consumption rows pre-filled from the product's BOM
     * when the selected task is linked to the CAT-SUB raw material category.
     */
    private function preloadSubsidiaryConsumptions(): void
    {
        $this->subsidiaryConsumptions = [];
        $this->isTaskSubsidiary = false;
        $this->isTaskStitching = false;

        if (!$this->selectedTaskId) {
            return;
        }

        $task = Task::with('rawMaterialCategories')->find($this->selectedTaskId);
        if (!$task) {
            return;
        }

        $this->isTaskSubsidiary = $task->rawMaterialCategories->contains('code', 'CAT-SUB');
        $this->isTaskStitching  = $task->rawMaterialCategories->contains('code', 'CAT-STITCH');

        if (!$this->isTaskSubsidiary) {
            return;
        }

        $product = $this->job->manufacturingProduct;
        if (!$product || !$product->is_subsidiary_used) {
            return;
        }

        $bomItems = $product->subsidiaryMaterials;
        $targetQty = (int) $this->job->target_quantity;

        foreach ($bomItems as $mat) {
            $this->subsidiaryConsumptions[] = [
                'bom_raw_material_id' => $mat->id,
                'bom_material_name'   => $mat->name,
                'unit'                => $mat->unit,
                'expected_quantity'   => round($mat->pivot->consumption_quantity * $targetQty, 4),
                'inventory_batch_id'  => '',
                'actual_consumed'     => round($mat->pivot->consumption_quantity * $targetQty, 4),
            ];
        }
    }

    public function addLaborRow($manufacturingProductId = null): void
    {
        $prodId = $manufacturingProductId ?? ($this->job->manufacturing_product_id ?? '');
        array_unshift($this->laborAllocations, [
            'labor_id' => '',
            'manufacturing_product_id' => $prodId,
            'quantity' => ''
        ]);
    }

    public function removeLaborRow(int $index): void
    {
        if (count($this->laborAllocations) > 1) {
            unset($this->laborAllocations[$index]);
            $this->laborAllocations = array_values($this->laborAllocations);
        }
    }

    /**
     * Bulk Allocate 100% of a product's output quantity to a single worker.
     */
    public function bulkAllocate($manufacturingProductId = null): void
    {
        $prodId = $manufacturingProductId ?? ($this->job->manufacturing_product_id ?? '');
        $laborId = $this->bulkLaborSelections[$prodId] ?? '';

        if (empty($laborId)) {
            $this->addError('laborAllocations', 'Please select a worker from the Bulk Allocation dropdown first.');
            return;
        }

        $labor = Labor::find($laborId);
        if (!$labor) {
            $this->addError('laborAllocations', 'Selected worker not found.');
            return;
        }

        $targetQty = $this->stagePendingQuantity;
        if ($targetQty <= 0) {
            $this->addError('laborAllocations', 'No pending units available to allocate for this stage.');
            return;
        }

        $this->laborAllocations = array_values(array_filter($this->laborAllocations, function ($row) use ($prodId) {
            return ($row['manufacturing_product_id'] ?? '') != $prodId;
        }));

        array_unshift($this->laborAllocations, [
            'labor_id' => $laborId,
            'manufacturing_product_id' => $prodId,
            'quantity' => $targetQty,
        ]);

        $this->dispatch('toast', message: "Bulk allocation applied: 100% ({$targetQty} Pcs) assigned to {$labor->name}!", type: 'success');
    }

    public function addMaterialRow(): void
    {
        array_unshift($this->materialConsumptions, ['inventory_batch_id' => '', 'quantity_consumed' => '']);
    }

    public function removeMaterialRow(int $index): void
    {
        if (count($this->materialConsumptions) > 1) {
            unset($this->materialConsumptions[$index]);
            $this->materialConsumptions = array_values($this->materialConsumptions);
        }
    }

    public function addOutputRow(): void
    {
        array_unshift($this->productionOutputs, ['manufacturing_product_id' => $this->job->manufacturing_product_id ?? '', 'quantity_produced' => '']);
    }

    public function removeOutputRow(int $index): void
    {
        if (count($this->productionOutputs) > 1) {
            unset($this->productionOutputs[$index]);
            $this->productionOutputs = array_values($this->productionOutputs);
        }
    }

    public function addWastageRow(): void
    {
        array_unshift($this->wastageRecords, ['manufacturing_product_id' => $this->job->manufacturing_product_id ?? '', 'quantity_wasted' => '', 'reason' => '']);
    }

    public function removeWastageRow(int $index): void
    {
        if (count($this->wastageRecords) > 1) {
            unset($this->wastageRecords[$index]);
            $this->wastageRecords = array_values($this->wastageRecords);
        }
    }

    public function addAlterationRow(): void
    {
        array_unshift($this->alterationRecords, ['source_product_id' => $this->job->manufacturing_product_id ?? '', 'source_quantity' => '', 'target_product_id' => '', 'target_quantity' => '']);
    }

    public function removeAlterationRow(int $index): void
    {
        if (count($this->alterationRecords) > 1) {
            unset($this->alterationRecords[$index]);
            $this->alterationRecords = array_values($this->alterationRecords);
        }
    }

    public function getRoutingTasksProperty()
    {
        $tasks = $this->job->manufacturingProduct?->tasks;
        if (!$tasks || $tasks->isEmpty()) {
            return Task::where('status', true)->get();
        }
        return $tasks;
    }

    /**
     * Returns true if the currently selected task is linked to the CAT-SUB category.
     * Used to decide whether to show the subsidiary BOM consumption form.
     */
    public function getIsTaskSubsidiaryProperty(): bool
    {
        if (!$this->selectedTaskId) {
            return false;
        }
        $task = Task::with('rawMaterialCategories')->find($this->selectedTaskId);
        return $task && $task->rawMaterialCategories->contains('code', 'CAT-SUB');
    }

    /**
     * Returns true if the currently selected task is linked to the CAT-STITCH category.
     * Stitching tasks do NOT deduct per-unit inventory — costs go to the cost pool.
     */
    public function getIsTaskStitchingProperty(): bool
    {
        if (!$this->selectedTaskId) {
            return false;
        }
        $task = Task::with('rawMaterialCategories')->find($this->selectedTaskId);
        return $task && $task->rawMaterialCategories->contains('code', 'CAT-STITCH');
    }

    public function getStageCompletedQuantityProperty(): int
    {
        if (!$this->selectedTaskId) {
            return 0;
        }

        return (int) $this->job->allocations()->where('task_id', $this->selectedTaskId)->sum('quantity_processed');
    }

    public function getStageMaxAllowedOutputProperty(): int
    {
        if (!$this->selectedTaskId) {
            return (int) $this->job->target_quantity;
        }

        $routingTasks = $this->routingTasks;
        $currentIndex = null;

        foreach ($routingTasks as $idx => $t) {
            if ($t->id == $this->selectedTaskId) {
                $currentIndex = $idx;
                break;
            }
        }

        if ($currentIndex === null || $currentIndex === 0) {
            return (int) $this->job->target_quantity;
        }

        $precedingTask = $routingTasks[$currentIndex - 1];
        $precedingCompleted = (int) $this->job->allocations()
            ->where('task_id', $precedingTask->id)
            ->sum('quantity_processed');

        return $precedingCompleted;
    }

    public function getPrecedingStageInfoProperty(): ?array
    {
        if (!$this->selectedTaskId) {
            return null;
        }

        $routingTasks = $this->routingTasks;
        $currentIndex = null;

        foreach ($routingTasks as $idx => $t) {
            if ($t->id == $this->selectedTaskId) {
                $currentIndex = $idx;
                break;
            }
        }

        if ($currentIndex === null || $currentIndex === 0) {
            return null;
        }

        $precedingTask = $routingTasks[$currentIndex - 1];
        $precedingCompleted = (int) $this->job->allocations()
            ->where('task_id', $precedingTask->id)
            ->sum('quantity_processed');

        return [
            'task' => $precedingTask,
            'completed' => $precedingCompleted,
            'target' => (int) $this->job->target_quantity,
            'pending_in_preceding' => max(0, (int) $this->job->target_quantity - $precedingCompleted),
        ];
    }

    public function getStagePendingQuantityProperty(): int
    {
        return max(0, $this->stageMaxAllowedOutput - $this->stageCompletedQuantity);
    }

    public function getAuthorizedLaborsProperty()
    {
        if (!$this->selectedTaskId) {
            return Labor::where('status', true)->get();
        }

        return Labor::where('status', true)
            ->whereHas('tasks', function ($q) {
                $q->where('tasks.id', $this->selectedTaskId);
            })
            ->get();
    }

    public function getAvailableInventoryBatchesProperty()
    {
        if (!$this->selectedTaskId) {
            return collect();
        }

        $task = Task::with('rawMaterialCategories')->find($this->selectedTaskId);
        if (!$task || !$task->consumes_raw_material) {
            return collect();
        }

        $allowedCategoryIds = $task->rawMaterialCategories->pluck('id')->toArray();

        // Exclude CAT-STITCH from the general inventory picker — those costs go to the cost pool
        $stitchCatId = \App\Models\RawMaterialCategory::where('code', 'CAT-STITCH')->value('id');
        $allowedCategoryIds = array_diff($allowedCategoryIds, array_filter([$stitchCatId]));

        if (empty($allowedCategoryIds)) {
            return collect();
        }

        return InventoryBatch::where('balance_quantity', '>', 0)
            ->whereHas('rawMaterial', function ($q) use ($allowedCategoryIds) {
                $q->whereIn('raw_material_category_id', $allowedCategoryIds);
            })
            ->with('rawMaterial.category')
            ->get();
    }

    /**
     * Blade-accessible alias for $availableBatches (used in job-detail-page.blade.php).
     * Delegates to the CAT-STITCH-excluding getAvailableInventoryBatchesProperty().
     */
    public function getAvailableBatchesProperty()
    {
        return $this->availableInventoryBatches;
    }

    public function saveStageAllocations(LaborWageService $wageService)
    {
        $this->validate([
            'selectedTaskId' => 'required|exists:tasks,id',
            'laborAllocations' => 'required|array|min:1',
            'laborAllocations.*.labor_id' => 'required|exists:labors,id',
            'laborAllocations.*.manufacturing_product_id' => 'nullable|exists:manufacturing_products,id',
            'laborAllocations.*.quantity' => 'required|numeric|min:1',
        ], [
            'laborAllocations.*.labor_id.required' => 'Please select a worker.',
            'laborAllocations.*.quantity.required' => 'Quantity is required.',
            'laborAllocations.*.quantity.min' => 'Quantity must be at least 1 unit.',
        ]);

        $selectedLaborIds = array_column($this->laborAllocations, 'labor_id');
        if (count($selectedLaborIds) !== count(array_unique($selectedLaborIds))) {
            $this->addError('laborAllocations', 'Duplicate workers selected. Each worker should only be added once per allocation set.');
            return;
        }

        $allocatedSum = (int) array_sum(array_column($this->laborAllocations, 'quantity'));
        $pendingQty = $this->stagePendingQuantity;

        if ($allocatedSum > $pendingQty) {
            $precedingInfo = $this->precedingStageInfo;
            if ($precedingInfo) {
                $precedingName = $precedingInfo['task']->name;
                $precedingCompleted = $precedingInfo['completed'];
                $currentName = Task::find($this->selectedTaskId)?->name ?? 'this stage';
                $currentCompleted = $this->stageCompletedQuantity;

                $this->addError(
                    'laborAllocations',
                    "Cannot allocate {$allocatedSum} Pcs for {$currentName}. Preceding stage ({$precedingName}) has only completed {$precedingCompleted} Pcs (Current {$currentName} output: {$currentCompleted} Pcs. Max available to process: {$pendingQty} Pcs)."
                );
            } else {
                $this->addError('laborAllocations', "Total allocated quantity ({$allocatedSum} Pcs) exceeds remaining pending quantity ({$pendingQty} Pcs) for this stage.");
            }
            return;
        }

        $response = $wageService->processAllocations(
            $this->laborAllocations,
            $this->job->job_code,
            $this->job->manufacturing_product_id,
            $this->selectedTaskId,
            $this->job->production_batch_id
        );

        $responseData = $response->getData(true);

        if (isset($responseData['success']) && $responseData['success']) {
            if ($this->job->status === 'pending') {
                $this->job->update(['status' => 'in_progress']);
            }

            $this->job->load(['allocations.labor', 'allocations.task']);

            $defaultProdId = $this->job->manufacturing_product_id ?? '';
            $this->laborAllocations = [
                ['labor_id' => '', 'manufacturing_product_id' => $defaultProdId, 'quantity' => '']
            ];

            $this->dispatch('toast', message: 'Stage worker allocations & wages recorded successfully!', type: 'success');
        } else {
            $errorMessage = $responseData['message'] ?? 'Failed to record stage allocations.';
            $this->addError('laborAllocations', $errorMessage);
        }
    }

    public function saveMaterialConsumption()
    {
        if (!auth()->user()->hasAnyRole(['super_admin', 'admin', 'Factory Supervisor']) && !auth()->user()->can('manage_labor')) {
            abort(403, 'Unauthorized action. Only Factory Supervisors can record raw material consumption.');
        }

        $this->validate([
            'selectedTaskId' => 'required|exists:tasks,id',
            'materialConsumptions' => 'required|array|min:1',
            'materialConsumptions.*.inventory_batch_id' => 'required|exists:inventory_batches,id',
            'materialConsumptions.*.quantity_consumed' => 'required|numeric|gt:0',
        ], [
            'materialConsumptions.*.inventory_batch_id.required' => 'Please select an inventory batch.',
            'materialConsumptions.*.quantity_consumed.required' => 'Consumed quantity is required.',
            'materialConsumptions.*.quantity_consumed.gt' => 'Consumed quantity must be greater than 0.',
        ]);

        $selectedBatchIds = array_column($this->materialConsumptions, 'inventory_batch_id');
        if (count($selectedBatchIds) !== count(array_unique($selectedBatchIds))) {
            $this->addError('materialConsumptions', 'Duplicate inventory batches selected. Each batch should only be added once.');
            return;
        }

        foreach ($this->materialConsumptions as $idx => $row) {
            $batch = InventoryBatch::with('rawMaterial.category')->find($row['inventory_batch_id']);
            if (!$batch) {
                $this->addError("materialConsumptions.{$idx}.inventory_batch_id", "Selected inventory batch not found.");
                return;
            }

            // Enforce task-level raw material category constraint
            $task = Task::with('rawMaterialCategories')->find($this->selectedTaskId);
            if ($task && $task->consumes_raw_material) {
                $allowedCategoryIds = $task->rawMaterialCategories->pluck('id')->toArray();
                $stitchCatId = \App\Models\RawMaterialCategory::where('code', 'CAT-STITCH')->value('id');
                $allowedCategoryIds = array_diff($allowedCategoryIds, array_filter([$stitchCatId]));

                $matCatId = $batch->rawMaterial->raw_material_category_id;
                if (!in_array($matCatId, $allowedCategoryIds)) {
                    $categoryName = $batch->rawMaterial->category ? $batch->rawMaterial->category->name : 'Unknown';
                    $this->addError("materialConsumptions.{$idx}.inventory_batch_id", "Raw material category [{$categoryName}] is not allowed for task [{$task->name}].");
                    return;
                }
            } else {
                $taskName = $task ? $task->name : 'this task';
                $this->addError("materialConsumptions.{$idx}.inventory_batch_id", "Task [{$taskName}] does not consume raw materials.");
                return;
            }

            $consumedQty = (float) $row['quantity_consumed'];
            if ($consumedQty > (float) $batch->balance_quantity) {
                $this->addError("materialConsumptions.{$idx}.quantity_consumed", "Consumed quantity ({$consumedQty} {$batch->unit}) exceeds available stock ({$batch->balance_quantity} {$batch->unit}) for batch {$batch->batch_number}.");
                return;
            }
        }

        DB::transaction(function () {
            foreach ($this->materialConsumptions as $row) {
                $batch = InventoryBatch::findOrFail($row['inventory_batch_id']);
                $consumedQty = (float) $row['quantity_consumed'];
                $totalCost = $consumedQty * (float) $batch->unit_cost;

                JobMaterialConsumption::create([
                    'job_code' => $this->job->job_code,
                    'production_job_id' => $this->job->id,
                    'inventory_batch_id' => $batch->id,
                    'task_id' => $this->selectedTaskId,
                    'quantity_consumed' => $consumedQty,
                    'unit_cost' => $batch->unit_cost,
                    'total_cost' => $totalCost,
                ]);

                $batch->deductQuantity($consumedQty);
            }

            if ($this->job->status === 'pending') {
                $this->job->update(['status' => 'in_progress']);
            }
        });

        $this->job->load(['materialConsumptions.inventoryBatch.rawMaterial.category', 'materialConsumptions.task']);

        $this->materialConsumptions = [
            ['inventory_batch_id' => '', 'quantity_consumed' => '']
        ];

        $this->dispatch('toast', message: 'Raw material inventory consumption recorded successfully!', type: 'success');
    }

    /**
     * Save subsidiary material consumption (CAT-SUB BOM-driven).
     *
     * SRS Rules:
     *  - No wastage calculation.
     *  - Inventory reduces strictly by actual consumed quantity.
     *  - Costs logged as: actual_consumed * batch_purchase_rate.
     */
    public function saveSubsidiaryConsumption()
    {
        if (!auth()->user()->hasAnyRole(['super_admin', 'admin', 'Factory Supervisor'])) {
            abort(403, 'Unauthorized action. Only Factory Supervisors can record subsidiary material consumption.');
        }

        $this->validate([
            'subsidiaryConsumptions'                           => 'required|array|min:1',
            'subsidiaryConsumptions.*.inventory_batch_id'      => 'required|exists:inventory_batches,id',
            'subsidiaryConsumptions.*.actual_consumed'         => 'required|numeric|gt:0',
        ], [
            'subsidiaryConsumptions.*.inventory_batch_id.required' => 'Please select an inventory batch for each subsidiary material.',
            'subsidiaryConsumptions.*.actual_consumed.required'    => 'Actual consumed quantity is required.',
            'subsidiaryConsumptions.*.actual_consumed.gt'          => 'Actual consumed quantity must be greater than 0.',
        ]);

        // Validate stock availability
        foreach ($this->subsidiaryConsumptions as $idx => $row) {
            $batch = InventoryBatch::find($row['inventory_batch_id']);
            if (!$batch) {
                $this->addError("subsidiaryConsumptions.{$idx}.inventory_batch_id", "Selected batch not found.");
                return;
            }
            $consumed = (float) $row['actual_consumed'];
            if ($consumed > (float) $batch->balance_quantity) {
                $this->addError(
                    "subsidiaryConsumptions.{$idx}.actual_consumed",
                    "Consumed ({$consumed} {$batch->unit}) exceeds available stock ({$batch->balance_quantity} {$batch->unit}) in batch {$batch->batch_number}."
                );
                return;
            }
        }

        DB::transaction(function () {
            foreach ($this->subsidiaryConsumptions as $row) {
                $batch       = InventoryBatch::findOrFail($row['inventory_batch_id']);
                $consumedQty = (float) $row['actual_consumed'];
                $totalCost   = $consumedQty * (float) $batch->unit_cost;

                // Log consumption — NO wastage factor applied (SRS Module 1, Section 6B)
                JobMaterialConsumption::create([
                    'job_code'           => $this->job->job_code,
                    'production_job_id'  => $this->job->id,
                    'inventory_batch_id' => $batch->id,
                    'task_id'            => $this->selectedTaskId,
                    'quantity_consumed'  => $consumedQty,
                    'unit_cost'          => $batch->unit_cost,
                    'total_cost'         => $totalCost,
                ]);

                $batch->deductQuantity($consumedQty);
            }

            if ($this->job->status === 'pending') {
                $this->job->update(['status' => 'in_progress']);
            }
        });

        $this->job->load(['materialConsumptions.inventoryBatch.rawMaterial.category', 'materialConsumptions.task']);

        // Reload pre-filled BOM rows for next submission
        $this->preloadSubsidiaryConsumptions();

        $this->dispatch('toast', message: 'Subsidiary material consumption recorded successfully!', type: 'success');
    }

    public function saveProductionOutput()
    {
        if (!auth()->user()->hasAnyRole(['super_admin', 'admin', 'Factory Supervisor']) && !auth()->user()->can('manage_labor')) {
            abort(403, 'Unauthorized action. Only Factory Supervisors can record production output.');
        }

        $this->validate([
            'selectedTaskId' => 'required|exists:tasks,id',
            'productionOutputs' => 'required|array|min:1',
            'productionOutputs.*.manufacturing_product_id' => 'required|exists:manufacturing_products,id',
            'productionOutputs.*.quantity_produced' => 'required|numeric|min:1',
        ], [
            'productionOutputs.*.manufacturing_product_id.required' => 'Please select a manufacturing product.',
            'productionOutputs.*.quantity_produced.required' => 'Quantity produced is required.',
            'productionOutputs.*.quantity_produced.min' => 'Quantity produced must be at least 1 unit.',
        ]);

        $selectedProductIds = array_column($this->productionOutputs, 'manufacturing_product_id');
        if (count($selectedProductIds) !== count(array_unique($selectedProductIds))) {
            $this->addError('productionOutputs', 'Duplicate manufacturing products selected. Each product should only be added once.');
            return;
        }

        DB::transaction(function () {
            foreach ($this->productionOutputs as $row) {
                JobProductionOutput::create([
                    'job_code' => $this->job->job_code,
                    'production_job_id' => $this->job->id,
                    'manufacturing_product_id' => $row['manufacturing_product_id'],
                    'task_id' => $this->selectedTaskId,
                    'quantity_produced' => (int) $row['quantity_produced'],
                ]);
            }

            if ($this->job->status === 'pending') {
                $this->job->update(['status' => 'in_progress']);
            }
        });

        $this->job->load(['productOutputs.manufacturingProduct', 'productOutputs.task']);

        $defaultProdId = $this->job->manufacturing_product_id ?? '';
        $this->productionOutputs = [
            ['manufacturing_product_id' => $defaultProdId, 'quantity_produced' => '']
        ];

        $this->dispatch('toast', message: 'Multi-product production output recorded successfully!', type: 'success');
    }

    public function saveJobWastage()
    {
        if (!auth()->user()->hasAnyRole(['super_admin', 'admin', 'Factory Supervisor']) && !auth()->user()->can('manage_labor')) {
            abort(403, 'Unauthorized action. Only Factory Supervisors can record production wastage.');
        }

        $this->validate([
            'selectedTaskId' => 'required|exists:tasks,id',
            'wastageRecords' => 'required|array|min:1',
            'wastageRecords.*.manufacturing_product_id' => 'nullable|exists:manufacturing_products,id',
            'wastageRecords.*.quantity_wasted' => 'required|numeric|gt:0',
            'wastageRecords.*.reason' => 'nullable|string|max:255',
        ], [
            'wastageRecords.*.quantity_wasted.required' => 'Wasted quantity is required.',
            'wastageRecords.*.quantity_wasted.gt' => 'Wasted quantity must be greater than 0.',
        ]);

        DB::transaction(function () {
            foreach ($this->wastageRecords as $row) {
                JobWastage::create([
                    'job_code' => $this->job->job_code,
                    'production_job_id' => $this->job->id,
                    'manufacturing_product_id' => !empty($row['manufacturing_product_id']) ? $row['manufacturing_product_id'] : null,
                    'task_id' => $this->selectedTaskId,
                    'quantity_wasted' => (float) $row['quantity_wasted'],
                    'reason' => !empty($row['reason']) ? $row['reason'] : 'Production Loss / Damaged Scraps',
                ]);
            }

            if ($this->job->status === 'pending') {
                $this->job->update(['status' => 'in_progress']);
            }
        });

        $this->job->load(['wastages.manufacturingProduct', 'wastages.task']);

        $defaultProdId = $this->job->manufacturing_product_id ?? '';
        $this->wastageRecords = [
            ['manufacturing_product_id' => $defaultProdId, 'quantity_wasted' => '', 'reason' => '']
        ];

        $this->dispatch('toast', message: 'Production loss & wastage recorded successfully!', type: 'success');
    }

    public function saveJobAlteration()
    {
        if (!auth()->user()->hasAnyRole(['super_admin', 'admin', 'Factory Supervisor']) && !auth()->user()->can('manage_labor')) {
            abort(403, 'Unauthorized action. Only Factory Supervisors can manage alterations.');
        }

        $this->validate([
            'alterationRecords' => 'required|array|min:1',
            'alterationRecords.*.source_product_id' => 'required|exists:manufacturing_products,id',
            'alterationRecords.*.source_quantity' => 'required|numeric|min:1',
            'alterationRecords.*.target_product_id' => 'required|exists:manufacturing_products,id',
            'alterationRecords.*.target_quantity' => 'required|numeric|min:1',
        ], [
            'alterationRecords.*.source_product_id.required' => 'Source product is required.',
            'alterationRecords.*.source_quantity.required' => 'Source quantity is required.',
            'alterationRecords.*.target_product_id.required' => 'Target product is required.',
            'alterationRecords.*.target_quantity.required' => 'Target quantity is required.',
        ]);

        $lastChildBatchCode = '';

        DB::transaction(function () use (&$lastChildBatchCode) {
            $parentBatch = $this->job->batch;
            if (!$parentBatch) {
                $parentBatch = ProductionBatch::create([
                    'batch_code' => 'PB-2026-' . str_pad($this->job->id, 4, '0', STR_PAD_LEFT),
                    'manufacturing_product_id' => $this->job->manufacturing_product_id,
                    'planned_quantity' => $this->job->target_quantity,
                    'status' => 'In Progress',
                    'supervisor_id' => $this->job->supervisor_id,
                ]);
                $this->job->update(['production_batch_db_id' => $parentBatch->id, 'production_batch_id' => $parentBatch->batch_code]);
            }

            foreach ($this->alterationRecords as $row) {
                $childCount = $parentBatch->childBatches()->count() + 1;
                $childBatchCode = $parentBatch->batch_code . "-A{$childCount}";
                $lastChildBatchCode = $childBatchCode;

                $childBatch = ProductionBatch::create([
                    'parent_batch_id' => $parentBatch->id,
                    'batch_code' => $childBatchCode,
                    'batch_date' => now()->format('Y-m-d'),
                    'supervisor_id' => $this->job->supervisor_id ?? auth()->id(),
                    'manufacturing_product_id' => $row['target_product_id'],
                    'planned_quantity' => (int) $row['target_quantity'],
                    'priority' => $parentBatch->priority ?? 'Normal',
                    'status' => 'In Progress',
                    'remarks' => "Child Alteration Batch derived from Parent Batch {$parentBatch->batch_code} (Source Job {$this->job->job_code})",
                ]);

                $targetProduct = ManufacturingProduct::find($row['target_product_id']);
                $firstTask = $targetProduct ? $targetProduct->tasks()->orderByPivot('sequence_number', 'asc')->first() : null;
                if (!$firstTask) {
                    $firstTask = Task::where('status', true)->first();
                }

                $latestJobId = ProductionJob::max('id') ?? 0;
                $childJobCode = "JOB-" . date('Y') . "-" . str_pad($latestJobId + 1, 4, '0', STR_PAD_LEFT);

                ProductionJob::create([
                    'job_code' => $childJobCode,
                    'production_batch_id' => $childBatch->batch_code,
                    'production_batch_db_id' => $childBatch->id,
                    'manufacturing_product_id' => $row['target_product_id'],
                    'task_id' => $firstTask ? $firstTask->id : $this->selectedTaskId,
                    'supervisor_id' => $childBatch->supervisor_id,
                    'job_date' => now()->format('Y-m-d'),
                    'target_quantity' => (int) $row['target_quantity'],
                    'status' => 'in_progress',
                    'notes' => "Auto-initialized Job for Alteration Child Batch {$childBatch->batch_code}",
                ]);

                JobAlteration::create([
                    'job_code' => $this->job->job_code,
                    'production_job_id' => $this->job->id,
                    'source_product_id' => $row['source_product_id'],
                    'source_quantity' => (int) $row['source_quantity'],
                    'target_product_id' => $row['target_product_id'],
                    'target_quantity' => (int) $row['target_quantity'],
                    'child_production_batch_id' => $childBatch->id,
                ]);
            }

            if ($this->job->status === 'pending') {
                $this->job->update(['status' => 'in_progress']);
            }
        });

        $this->job->load([
            'batch.childBatches',
            'alterations.sourceProduct',
            'alterations.targetProduct',
            'alterations.childBatch',
        ]);

        $defaultProdId = $this->job->manufacturing_product_id ?? '';
        $this->alterationRecords = [
            ['source_product_id' => $defaultProdId, 'source_quantity' => '', 'target_product_id' => '', 'target_quantity' => '']
        ];

        $this->dispatch('toast', message: "Alteration recorded & Child Production Batch {$lastChildBatchCode} generated successfully!", type: 'success');
    }

    /**
     * Mark Job as Completed and trigger Automatic Workflow Progression.
     */
    public function completeCurrentJob(ProductionWorkflowService $workflowService)
    {
        if (!auth()->user()->hasAnyRole(['super_admin', 'admin', 'Factory Supervisor']) && !auth()->user()->can('manage_labor')) {
            abort(403, 'Unauthorized action. Only Factory Supervisors can complete jobs.');
        }

        // Validate that if labor is required, all produced/target quantities have been allocated
        $task = Task::find($this->job->task_id);
        if ($task && $task->is_labor_required) {
            $allocatedQty = (int) $this->job->allocations()->where('task_id', $task->id)->sum('quantity_processed');
            $producedQty = (int) $this->job->productOutputs()->where('task_id', $task->id)->sum('quantity_produced');
            $expectedQty = $producedQty > 0 ? $producedQty : (int) $this->job->target_quantity;

            if ($allocatedQty < $expectedQty) {
                $this->addError('jobStatus', "Cannot complete job: Labor allocations ({$allocatedQty} Pcs) must equal the total output/target quantity ({$expectedQty} Pcs) for labor-dependent task [{$task->name}].");
                return;
            }
        }

        $isFinal = (bool) $this->job->is_final_step;

        $response = $workflowService->completeJob($this->job->id);
        $responseData = $response->getData(true);

        if (isset($responseData['success']) && $responseData['success']) {
            if ($isFinal) {
                $this->showFinalCompletionModal = true;
                $this->job->refresh();
                $this->dispatch('toast', message: 'Final task completed successfully! Batch ready for conversion.', type: 'success');
                return;
            }

            $message = $responseData['message'] ?? 'Job completed and workflow progressed successfully!';
            session()->flash('toast', ['message' => $message, 'type' => 'success']);
            return redirect()->route('admin.production.jobs.index');
        } else {
            $errorMessage = $responseData['message'] ?? 'Failed to complete job workflow.';
            $this->addError('jobStatus', $errorMessage);
        }
    }

    public function updateJobStatus(string $newStatus): void
    {
        if ($newStatus === 'completed') {
            $this->completeCurrentJob(app(ProductionWorkflowService::class));
            return;
        }

        if (in_array($newStatus, ['pending', 'in_progress', 'cancelled'])) {
            $this->job->update(['status' => $newStatus]);
            $this->dispatch('toast', message: "Job status updated to " . ucfirst(str_replace('_', ' ', $newStatus)), type: 'success');
        }
    }

    public function render()
    {
        $allTasks = $this->routingTasks;
        $selectedTask = Task::find($this->selectedTaskId);
        $allManufacturingProducts = ManufacturingProduct::all();

        $stageAllocations = $this->job->allocations()->where('task_id', $this->selectedTaskId)->with('labor')->get();
        $stageConsumptions = $this->job->materialConsumptions()->where('task_id', $this->selectedTaskId)->with(['inventoryBatch.rawMaterial.category'])->get();
        $stageOutputs = $this->job->productOutputs()->where('task_id', $this->selectedTaskId)->with('manufacturingProduct')->get();
        $stageWastages = $this->job->wastages()->where('task_id', $this->selectedTaskId)->with('manufacturingProduct')->get();
        $jobAlterations = $this->job->alterations()->with(['sourceProduct', 'targetProduct', 'childBatch'])->get();

        return view('livewire.admin.production.job-detail-page', [
            'allTasks' => $allTasks,
            'selectedTask' => $selectedTask,
            'allManufacturingProducts' => $allManufacturingProducts,
            'stageAllocations' => $stageAllocations,
            'stageConsumptions' => $stageConsumptions,
            'stageOutputs' => $stageOutputs,
            'stageWastages' => $stageWastages,
            'jobAlterations' => $jobAlterations,
            'authorizedLabors' => $this->authorizedLabors,
            'availableBatches' => $this->availableInventoryBatches,
            'stageCompleted' => $this->stageCompletedQuantity,
            'stagePending' => $this->stagePendingQuantity,
            'stageMaxAllowed' => $this->stageMaxAllowedOutput,
            'precedingInfo' => $this->precedingStageInfo,
        ])->title("Job {$this->job->job_code} — Detail & Stage Management");
    }

    public function addCuttingOutputRow(): void
    {
        $this->cuttingOutputs[] = [
            'manufacturing_product_id' => $this->job->manufacturing_product_id ?? '',
            'width' => 60,
            'length' => 2.5,
            'width_unit' => 'inch',
            'length_unit' => 'meter',
            'quantity' => 1,
        ];
    }

    public function removeCuttingOutputRow(int $index): void
    {
        if (count($this->cuttingOutputs) > 1) {
            unset($this->cuttingOutputs[$index]);
            $this->cuttingOutputs = array_values($this->cuttingOutputs);
        }
    }

    public function resetCuttingForm()
    {
        $this->cuttingFabricBatchId = '';
        $this->cuttingConsumedLength = '';
        $this->cuttingWastageLength = 0;
        $this->cuttingFabricWidth = 60.00;
        
        $mainProduct = $this->job->manufacturingProduct;
        if ($mainProduct && $mainProduct->is_fabric_used) {
            $this->cuttingOutputs = [
                [
                    'manufacturing_product_id' => $mainProduct->id,
                    'width' => $mainProduct->standard_fabric_width ?? 60,
                    'length' => $mainProduct->standard_fabric_length ?? 2.5,
                    'width_unit' => $mainProduct->fabric_width_unit ?? 'inch',
                    'length_unit' => $mainProduct->fabric_length_unit ?? 'meter',
                    'quantity' => $this->job->target_quantity ?? 1,
                ]
            ];
        } else {
            $this->cuttingOutputs = [
                [
                    'manufacturing_product_id' => $this->job->manufacturing_product_id ?? '',
                    'width' => 60,
                    'length' => 2.5,
                    'width_unit' => 'inch',
                    'length_unit' => 'meter',
                    'quantity' => 1,
                ]
            ];
        }
    }

    public function getCuttingCostPreviewProperty()
    {
        if (empty($this->cuttingFabricBatchId) || empty($this->cuttingConsumedLength)) {
            return null;
        }

        $batch = InventoryBatch::find($this->cuttingFabricBatchId);
        if (!$batch) {
            return null;
        }

        $purchaseRate = (float) $batch->unit_cost;
        $totalFabricCost = (float) $this->cuttingConsumedLength * $purchaseRate;
        $totalWastageCost = (float) $this->cuttingWastageLength * $purchaseRate;

        $totalOutputArea = 0.0;
        $items = [];
        
        $costingService = app(\App\Services\FabricCostingService::class);
        $lengthUnit = $batch->unit ?: 'Meters';
        $consumedLengthInches = $costingService->convertToInches((float)$this->cuttingConsumedLength, $lengthUnit);
        $totalFabricArea = (float)$this->cuttingFabricWidth * $consumedLengthInches;

        foreach ($this->cuttingOutputs as $output) {
            if (empty($output['manufacturing_product_id']) || empty($output['quantity'])) {
                continue;
            }
            $width = (float)($output['width'] ?? 0);
            $length = (float)($output['length'] ?? 0);
            $wUnit = $output['width_unit'] ?: 'inch';
            $lUnit = $output['length_unit'] ?: 'meter';
            $qty = (int)($output['quantity'] ?? 0);

            $singleArea = $costingService->calculateArea($width, $wUnit, $length, $lUnit);
            $totalArea = $singleArea * $qty;
            $totalOutputArea += $totalArea;

            $items[] = [
                'manufacturing_product_id' => $output['manufacturing_product_id'],
                'quantity' => $qty,
                'total_area' => $totalArea,
            ];
        }

        $preview = [];
        foreach ($items as $item) {
            $baseCost = $totalFabricArea > 0 ? ($item['total_area'] / $totalFabricArea) * $totalFabricCost : 0.0;
            $allocatedWastage = $totalOutputArea > 0 ? ($item['total_area'] / $totalOutputArea) * $totalWastageCost : 0.0;
            $totalCost = $baseCost + $allocatedWastage;
            
            $prod = ManufacturingProduct::find($item['manufacturing_product_id']);

            $preview[] = [
                'product_name' => $prod ? $prod->name : 'Unknown Product',
                'quantity' => $item['quantity'],
                'base_cost' => $baseCost,
                'allocated_wastage' => $allocatedWastage,
                'total_cost' => $totalCost,
                'cost_per_unit' => $item['quantity'] > 0 ? ($totalCost / $item['quantity']) : 0.0,
            ];
        }

        return [
            'total_fabric_cost' => $totalFabricCost,
            'total_wastage_cost' => $totalWastageCost,
            'preview_items' => $preview,
        ];
    }

    public function saveCuttingSession(\App\Services\FabricCostingService $costingService)
    {
        if (!auth()->user()->hasAnyRole(['super_admin', 'admin', 'Factory Supervisor']) && !auth()->user()->can('manage_labor')) {
            abort(403, 'Unauthorized action. Only Factory Supervisors can record cutting sessions.');
        }

        $this->validate([
            'cuttingFabricBatchId' => 'required|exists:inventory_batches,id',
            'cuttingConsumedLength' => 'required|numeric|gt:0',
            'cuttingWastageLength' => 'required|numeric|min:0',
            'cuttingFabricWidth' => 'required|numeric|gt:0',
            'cuttingOutputs' => 'required|array|min:1',
            'cuttingOutputs.*.manufacturing_product_id' => 'required|exists:manufacturing_products,id',
            'cuttingOutputs.*.width' => 'required|numeric|gt:0',
            'cuttingOutputs.*.length' => 'required|numeric|gt:0',
            'cuttingOutputs.*.width_unit' => 'required|in:inch,cm',
            'cuttingOutputs.*.length_unit' => 'required|in:meter,yard',
            'cuttingOutputs.*.quantity' => 'required|integer|min:1',
        ], [
            'cuttingFabricBatchId.required' => 'Please select a fabric inventory batch.',
            'cuttingConsumedLength.required' => 'Consumed length is required.',
            'cuttingConsumedLength.gt' => 'Consumed length must be greater than 0.',
            'cuttingWastageLength.required' => 'Wastage length is required.',
            'cuttingOutputs.*.manufacturing_product_id.required' => 'Product is required.',
            'cuttingOutputs.*.width.required' => 'Width is required.',
            'cuttingOutputs.*.length.required' => 'Length is required.',
            'cuttingOutputs.*.quantity.required' => 'Quantity is required.',
        ]);

        $batch = InventoryBatch::findOrFail($this->cuttingFabricBatchId);
        if ($this->cuttingConsumedLength > (float)$batch->balance_quantity) {
            $this->addError('cuttingConsumedLength', "Consumed length exceeds available stock ({$batch->balance_quantity} {$batch->unit}).");
            return;
        }

        DB::transaction(function () use ($costingService, $batch) {
            $costingService->calculateFabricCostAllocation(
                $this->job->id,
                $batch->id,
                (float) $this->cuttingConsumedLength,
                $this->cuttingOutputs,
                (float) $this->cuttingWastageLength,
                (float) $this->cuttingFabricWidth
            );

            // Decrement the inventory batch balance
            $batch->deductQuantity((float) $this->cuttingConsumedLength);

            if ($this->job->status === 'pending') {
                $this->job->update(['status' => 'in_progress']);
            }
        });

        $this->job->load([
            'batch.childBatches',
            'materialConsumptions.inventoryBatch.rawMaterial.category',
            'productOutputs.manufacturingProduct',
            'productOutputs.task',
            'wastages.manufacturingProduct',
            'wastages.task',
        ]);

        $this->dispatch('toast', message: 'Cutting session output and costs allocated successfully!', type: 'success');
        $this->resetCuttingForm();
    }
}
