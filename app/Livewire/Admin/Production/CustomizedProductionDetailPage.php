<?php

namespace App\Livewire\Admin\Production;

use App\Models\CustomizedProductionOrder;
use App\Models\ProductionJob;
use App\Models\JobStageExecution;
use App\Models\JobLaborAllocation;
use App\Models\JobProductionOutput;
use App\Models\JobMaterialConsumption;
use App\Models\InventoryBatch;
use App\Models\InventoryBaleRoll;
use App\Models\RawMaterial;
use App\Models\Task;
use App\Models\Labor;
use App\Services\Manufacturing\ProductionWorkflowService;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.admin.layout')]
class CustomizedProductionDetailPage extends Component
{
    public int $orderId;
    public ?CustomizedProductionOrder $customOrder = null;
    public ?ProductionJob $job = null;
    public ?int $selectedStageId = null;

    public int $wizardStep = 1;

    // Dynamic Task Routing Editing (Left Panel)
    public array $dynamicTaskRows = [];

    // Stage Execution Properties (Right Panel)
    // Cutting Stage Specifics
    public string $cuttingFabricBatchId = '';
    public array $cuttingBaleRows = [];
    public array $cuttingLaborAllocations = [];

    // Non-Cutting Stage Specifics
    public array $stageLaborRows = [];
    public int $stageOutputQty = 0;

    public function mount(int $id)
    {
        $this->orderId = $id;
        $this->loadOrder();
        $this->initDynamicTaskRows();
        $this->selectInitialStage();
    }

    public function loadOrder()
    {
        $this->customOrder = CustomizedProductionOrder::with([
            'rawMaterial',
            'productionJob.stageExecutions.task',
            'productionJob.stageExecutions.productOutputs',
            'productionJob.stageExecutions.allocations',
        ])->findOrFail($this->orderId);

        $this->job = $this->customOrder->productionJob;
    }

    public function initDynamicTaskRows()
    {
        if (!$this->job) return;

        $this->job->load('stageExecutions.task');

        $this->dynamicTaskRows = $this->job->stageExecutions->map(fn($exec, $idx) => [
            'id' => $exec->id,
            'task_id' => (string) $exec->task_id,
            'task_name' => $exec->task?->name ?? "Task #".($idx+1),
            'status' => $exec->status,
            'is_final_step' => (bool) $exec->is_final_step,
        ])->toArray();
    }

    public function selectInitialStage()
    {
        if (!$this->job) return;

        $inProgressStage = $this->job->stageExecutions->firstWhere('status', 'in_progress');
        $pendingStage = $this->job->stageExecutions->firstWhere('status', 'pending');
        $firstStage = $this->job->stageExecutions->first();

        $selected = $inProgressStage ?? ($pendingStage ?? $firstStage);
        if ($selected) {
            $this->selectStage($selected->id);
        }
    }

    public function selectStage(int $stageId)
    {
        $this->selectedStageId = $stageId;
        $this->wizardStep = 1;
        $this->initSelectedStageData();
    }

    public function getSelectedStageProperty(): ?JobStageExecution
    {
        if (!$this->job || !$this->selectedStageId) return null;
        return $this->job->stageExecutions->firstWhere('id', $this->selectedStageId);
    }

    public function initSelectedStageData()
    {
        $stage = $this->selectedStage;
        if (!$stage) return;

        $taskName = strtolower($stage->task?->name ?? '');
        $isCutting = str_contains($taskName, 'cut');

        if ($isCutting) {
            $this->initCuttingData();
        } else {
            $this->initNonCuttingData();
        }
    }

    public function initCuttingData()
    {
        $rawMaterialId = $this->customOrder?->raw_material_id;

        // Auto-select inventory batch for custom order fabric material if available
        if ($rawMaterialId) {
            $batch = InventoryBatch::where('raw_material_id', $rawMaterialId)
                ->where('balance_quantity', '>', 0)
                ->first();
            if ($batch) {
                $this->cuttingFabricBatchId = (string) $batch->id;
            }
        }

        if (empty($this->cuttingFabricBatchId)) {
            $firstBatch = InventoryBatch::where('balance_quantity', '>', 0)->first();
            if ($firstBatch) {
                $this->cuttingFabricBatchId = (string) $firstBatch->id;
            }
        }

        $this->loadCuttingBaleRows();

        $this->cuttingLaborAllocations = [
            [
                'labor_id' => '',
                'quantity' => $this->customOrder?->target_quantity ?? 20,
                'base_rate' => 10.00,
                'bonus_rate' => 0.00,
            ]
        ];
    }

    public function loadCuttingBaleRows()
    {
        $this->cuttingBaleRows = [];
        if (empty($this->cuttingFabricBatchId)) return;

        $batch = InventoryBatch::with(['bales.rolls'])->find($this->cuttingFabricBatchId);
        if (!$batch) return;

        foreach ($batch->bales as $bale) {
            $rollsData = [];
            foreach ($bale->rolls as $roll) {
                if ($roll->balance_length <= 0) continue;

                $rollsData[$roll->id] = [
                    'roll_id' => $roll->id,
                    'roll_number' => $roll->roll_number,
                    'is_selected' => false,
                    'max_length' => $roll->balance_length,
                    'width_display' => "{$roll->width} " . ($roll->width_unit ?? 'Inch'),
                    'cut_length' => (string) min($roll->balance_length, 25.00),
                    'wastage_length' => '0.00',
                    'outputs' => [
                        [
                            'item_name' => $this->customOrder?->item_description ?? 'Custom Item',
                            'quantity' => $this->customOrder?->target_quantity ?? 20,
                        ]
                    ]
                ];
            }

            if (!empty($rollsData)) {
                $this->cuttingBaleRows[] = [
                    'bale_id' => $bale->id,
                    'bale_number' => $bale->bale_number,
                    'selected_rolls' => $rollsData,
                ];
            }
        }
    }

    public function updatedCuttingFabricBatchId()
    {
        $this->loadCuttingBaleRows();
    }

    public function toggleRollSelection(int $bIndex, int $rollId)
    {
        if (isset($this->cuttingBaleRows[$bIndex]['selected_rolls'][$rollId])) {
            $curr = !empty($this->cuttingBaleRows[$bIndex]['selected_rolls'][$rollId]['is_selected']);
            $this->cuttingBaleRows[$bIndex]['selected_rolls'][$rollId]['is_selected'] = !$curr;
        }
    }

    public function addCuttingWorkerRow()
    {
        $this->cuttingLaborAllocations[] = [
            'labor_id' => '',
            'quantity' => $this->customOrder?->target_quantity ?? 20,
            'base_rate' => 10.00,
            'bonus_rate' => 0.00,
        ];
    }

    public function removeCuttingWorkerRow(int $index)
    {
        if (count($this->cuttingLaborAllocations) > 1) {
            unset($this->cuttingLaborAllocations[$index]);
            $this->cuttingLaborAllocations = array_values($this->cuttingLaborAllocations);
        }
    }

    public function initNonCuttingData()
    {
        $targetQty = $this->selectedStage?->target_quantity ?: ($this->customOrder?->target_quantity ?? 20);
        $this->stageOutputQty = $targetQty;

        $this->stageLaborRows = [
            [
                'labor_id' => '',
                'quantity' => $targetQty,
                'base_rate' => 10.00,
                'bonus_rate' => 0.00,
            ]
        ];
    }

    public function addNonCuttingWorkerRow()
    {
        $targetQty = $this->selectedStage?->target_quantity ?: ($this->customOrder?->target_quantity ?? 20);
        $this->stageLaborRows[] = [
            'labor_id' => '',
            'quantity' => $targetQty,
            'base_rate' => 10.00,
            'bonus_rate' => 0.00,
        ];
    }

    public function removeNonCuttingWorkerRow(int $index)
    {
        if (count($this->stageLaborRows) > 1) {
            unset($this->stageLaborRows[$index]);
            $this->stageLaborRows = array_values($this->stageLaborRows);
        }
    }

    // --- Dynamic Tasks Editing Actions ---
    public function addDynamicTaskStage()
    {
        $taskCount = count($this->dynamicTaskRows);
        $seq = $taskCount + 1;
        $defaultTask = Task::whereNotIn('id', array_column($this->dynamicTaskRows, 'task_id'))->first()
            ?? Task::first();

        $this->dynamicTaskRows[] = [
            'id' => null,
            'task_id' => $defaultTask ? (string)$defaultTask->id : '',
            'task_name' => $defaultTask?->name ?? "Task #{$seq}",
            'status' => 'pending',
            'is_final_step' => false,
        ];

        $this->syncDynamicTaskRowsToDatabase();
    }

    public function removeDynamicTaskStage(int $index)
    {
        if (count($this->dynamicTaskRows) <= 1) {
            $this->dispatch('toast', message: 'Custom order must have at least one dynamic task stage.', type: 'error');
            return;
        }

        $row = $this->dynamicTaskRows[$index];
        if (!empty($row['id'])) {
            JobStageExecution::destroy($row['id']);
        }

        unset($this->dynamicTaskRows[$index]);
        $this->dynamicTaskRows = array_values($this->dynamicTaskRows);

        $this->syncDynamicTaskRowsToDatabase();
    }

    public function setFinalStage(int $index)
    {
        foreach ($this->dynamicTaskRows as $i => &$row) {
            $row['is_final_step'] = ($i === $index);
        }

        $this->syncDynamicTaskRowsToDatabase();
    }

    public function syncDynamicTaskRowsToDatabase()
    {
        if (!$this->job) return;

        DB::transaction(function () {
            $targetQty = $this->customOrder?->target_quantity ?? 20;

            // Delete removed stages
            $keptIds = array_filter(array_column($this->dynamicTaskRows, 'id'));
            if (!empty($keptIds)) {
                JobStageExecution::where('production_job_id', $this->job->id)
                    ->whereNotIn('id', $keptIds)
                    ->delete();
            }

            foreach ($this->dynamicTaskRows as $idx => $row) {
                $seq = $idx + 1;
                $tId = intval($row['task_id']);
                if ($tId <= 0) continue;

                $exec = JobStageExecution::updateOrCreate(
                    [
                        'id' => $row['id'] ?? null,
                        'production_job_id' => $this->job->id,
                    ],
                    [
                        'task_id' => $tId,
                        'sequence_number' => $seq,
                        'target_quantity' => $targetQty,
                        'status' => $row['status'] ?? ($idx === 0 ? 'in_progress' : 'pending'),
                        'is_final_step' => !empty($row['is_final_step']),
                    ]
                );

                $this->dynamicTaskRows[$idx]['id'] = $exec->id;
            }
        });

        $this->loadOrder();
        $this->initDynamicTaskRows();
    }

    public function setWizardStep(int $step)
    {
        $this->wizardStep = max(1, min(3, $step));
    }

    public function completeCurrentStage(ProductionWorkflowService $workflowService)
    {
        $stage = $this->selectedStage;
        if (!$stage) return;

        $taskName = strtolower($stage->task?->name ?? '');
        $isCutting = str_contains($taskName, 'cut');

        DB::transaction(function () use ($stage, $isCutting) {
            if ($isCutting) {
                // Log Cutting Labor Allocations
                foreach ($this->cuttingLaborAllocations as $alloc) {
                    $lId = intval($alloc['labor_id'] ?? 0);
                    $qty = intval($alloc['quantity'] ?? 0);
                    $rate = floatval($alloc['base_rate'] ?? 10.0);
                    $bonus = floatval($alloc['bonus_rate'] ?? 0.0);

                    if ($lId > 0 && $qty > 0) {
                        JobLaborAllocation::create([
                            'job_id'             => $this->job->job_code,
                            'task_id'            => $stage->task_id,
                            'labor_id'           => $lId,
                            'quantity_processed' => $qty,
                            'base_rate'          => $rate,
                            'bonus_rate'         => $bonus,
                            'total_wage'         => $qty * ($rate + $bonus),
                        ]);
                    }
                }

                // Log Cutting Output Pcs
                JobProductionOutput::create([
                    'job_code'          => $this->job->job_code,
                    'production_job_id' => $this->job->id,
                    'task_id'           => $stage->task_id,
                    'quantity_produced' => $this->customOrder->target_quantity,
                    'output_date'       => now(),
                    'notes'             => "Cutting completed for custom item {$this->customOrder->item_description}",
                ]);

            } else {
                // Log Non-Cutting Labor Allocations
                foreach ($this->stageLaborRows as $alloc) {
                    $lId = intval($alloc['labor_id'] ?? 0);
                    $qty = intval($alloc['quantity'] ?? 0);
                    $rate = floatval($alloc['base_rate'] ?? 10.0);
                    $bonus = floatval($alloc['bonus_rate'] ?? 0.0);

                    if ($lId > 0 && $qty > 0) {
                        JobLaborAllocation::create([
                            'job_id'             => $this->job->job_code,
                            'task_id'            => $stage->task_id,
                            'labor_id'           => $lId,
                            'quantity_processed' => $qty,
                            'base_rate'          => $rate,
                            'bonus_rate'         => $bonus,
                            'total_wage'         => $qty * ($rate + $bonus),
                        ]);
                    }
                }

                JobProductionOutput::create([
                    'job_code'          => $this->job->job_code,
                    'production_job_id' => $this->job->id,
                    'task_id'           => $stage->task_id,
                    'quantity_produced' => $this->stageOutputQty ?: $this->customOrder->target_quantity,
                    'output_date'       => now(),
                    'notes'             => "Stage {$stage->task?->name} completed for custom item {$this->customOrder->item_description}",
                ]);
            }

            // Complete stage execution
            $stage->update([
                'status'       => 'completed',
                'completed_at' => now(),
            ]);

            // Check if this was marked as final step or last sequence
            $isFinal = (bool) $stage->is_final_step;
            $nextStage = $this->job->stageExecutions()
                ->where('sequence_number', '>', $stage->sequence_number)
                ->orderBy('sequence_number')
                ->first();

            if ($isFinal || !$nextStage) {
                $this->job->update(['status' => 'completed']);
                $this->customOrder->update(['status' => 'completed']);
            } elseif ($nextStage) {
                $nextStage->update([
                    'status' => 'in_progress',
                    'started_at' => now(),
                ]);
            }
        });

        session()->flash('toast', [
            'type'    => 'success',
            'message' => "Stage '{$stage->task?->name}' marked as COMPLETED!",
        ]);

        $this->loadOrder();
        $this->initDynamicTaskRows();
        $this->selectInitialStage();
    }

    public function render()
    {
        $allTasks = Task::where('status', true)->orderBy('name')->get();
        $allLabors = Labor::where('status', 'active')->orderBy('name')->get();
        $fabricBatches = InventoryBatch::where('balance_quantity', '>', 0)->orderBy('id', 'desc')->get();

        return view('livewire.admin.production.customized-production-detail-page', [
            'allTasks'      => $allTasks,
            'allLabors'     => $allLabors,
            'fabricBatches' => $fabricBatches,
            'selectedStage' => $this->selectedStage,
        ])->title("Custom Order — {$this->customOrder?->custom_order_id}");
    }
}
