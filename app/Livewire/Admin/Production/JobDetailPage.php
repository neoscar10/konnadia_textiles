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
            return InventoryBatch::where('balance_quantity', '>', 0)->with('rawMaterial.category')->get();
        }

        $task = Task::with('rawMaterialCategories')->find($this->selectedTaskId);
        $allowedCategoryIds = $task ? $task->rawMaterialCategories->pluck('id')->toArray() : [];

        if (empty($allowedCategoryIds)) {
            return InventoryBatch::where('balance_quantity', '>', 0)->with('rawMaterial.category')->get();
        }

        return InventoryBatch::where('balance_quantity', '>', 0)
            ->whereHas('rawMaterial', function ($q) use ($allowedCategoryIds) {
                $q->whereIn('raw_material_category_id', $allowedCategoryIds);
            })
            ->with('rawMaterial.category')
            ->get();
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
            $batch = InventoryBatch::with('rawMaterial')->find($row['inventory_batch_id']);
            if (!$batch) {
                $this->addError("materialConsumptions.{$idx}.inventory_batch_id", "Selected inventory batch not found.");
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

                $batch->decrement('balance_quantity', $consumedQty);
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

        $response = $workflowService->completeJob($this->job->id);
        $responseData = $response->getData(true);

        if (isset($responseData['success']) && $responseData['success']) {
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
}
