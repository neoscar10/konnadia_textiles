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
use App\Services\InventoryBatchLogger;

#[Layout('components.admin.layout')]
class JobDetailPage extends Component
{
    public ProductionJob $job;

    public string $activeTab = 'stages';
    public bool $showFinalCompletionModal = false;
    public $selectedTaskId = null;

    public int $wizardStep = 1;
    public $activeStep = 'workers';

    public function setActiveStep(string $step)
    {
        $targetStep = 1;
        $isCutting = $this->selectedTask && ($this->selectedTask->name === 'Cutting' || $this->selectedTask->code === 'TSK-001');
        
        if ($isCutting) {
            if ($step === 'workers') $targetStep = 2;
            elseif ($step === 'output') $targetStep = 3;
            elseif ($step === 'wastage') $targetStep = 4;
            else $targetStep = 1;
        } else {
            if ($step === 'workers') {
                $targetStep = $this->hasMaterialStep ? 2 : 1;
            } elseif ($step === 'output') {
                $targetStep = $this->hasMaterialStep ? 3 : 2;
            } elseif ($step === 'wastage') {
                $targetStep = $this->maxWizardSteps;
            } elseif ($step === 'material') {
                $targetStep = 1;
            }
        }
        
        $this->setWizardStep($targetStep);
    }
    public function getSelectedTaskProperty()
    {
        return $this->selectedTaskId ? Task::with('rawMaterialCategories')->find($this->selectedTaskId) : null;
    }

    public function getHasMaterialStepProperty(): bool
    {
        if (!$this->selectedTask) {
            return false;
        }

        $isCutting = $this->selectedTask->name === 'Cutting' || $this->selectedTask->code === 'TSK-001';
        if ($isCutting) {
            return true;
        }

        $consumesRawMaterial = $this->selectedTask->consumes_raw_material && ($this->selectedTask->name !== 'Stitching' && $this->selectedTask->code !== 'TSK-002');

        return $consumesRawMaterial;
    }

    public function getMaxWizardStepsProperty(): int
    {
        return $this->hasMaterialStep ? 4 : 3;
    }

    public function setWizardStep(int $step): void
    {
        $targetStep = max(1, min($this->maxWizardSteps, $step));

        if ($this->selectedTask && ($this->selectedTask->name === 'Cutting' || $this->selectedTask->code === 'TSK-001')) {
            // Validation when moving past Step 1
            if ($this->wizardStep === 1 && $targetStep > 1) {
                if (empty($this->cuttingFabricBatchId)) {
                    $this->addError('cuttingFabricBatchId', 'Please select a fabric inventory batch before proceeding.');
                    $this->dispatch('toast', message: 'Please select a fabric inventory batch before proceeding.', type: 'error');
                    return;
                }
                if (empty($this->cuttingConsumedLength) || (float)$this->cuttingConsumedLength <= 0) {
                    $this->addError('cuttingConsumedLength', 'Please record consumed fabric roll length before proceeding.');
                    $this->dispatch('toast', message: 'Please record consumed fabric roll length before proceeding.', type: 'error');
                    return;
                }
            }

            // Validation when moving past Step 2
            if ($this->wizardStep === 2 && $targetStep > 2) {
                if (!$this->validateCuttingStep2()) {
                    return;
                }
            }
        }

        $this->wizardStep = $targetStep;

        $isCutting = $this->selectedTask && ($this->selectedTask->name === 'Cutting' || $this->selectedTask->code === 'TSK-001');
        if (!$isCutting) {
            $workerStep = $this->hasMaterialStep ? 2 : 1;
            $outputStep = $this->hasMaterialStep ? 3 : 2;
            $wastageStep = $this->maxWizardSteps;
            
            if ($targetStep === $workerStep) {
                $this->activeStep = 'workers';
            } elseif ($targetStep === $outputStep) {
                $this->activeStep = 'output';
            } elseif ($targetStep === $wastageStep) {
                $this->activeStep = 'wastage';
            } elseif ($targetStep === 1 && $this->hasMaterialStep) {
                $this->activeStep = 'material';
            }
        }
    }

    public function validateCuttingStep2(): bool
    {
        $this->resetErrorBag(['cuttingOutputs', 'laborAllocations']);

        if (empty($this->cuttingOutputs)) {
            $this->addError('cuttingOutputs', 'Please specify at least one target product cut output.');
            $this->dispatch('toast', message: 'Please specify at least one target product cut output.', type: 'error');
            return false;
        }

        $cutTotalsPerProduct = [];
        foreach ($this->cuttingOutputs as $idx => $out) {
            if (empty($out['manufacturing_product_id'])) {
                $this->addError("cuttingOutputs.{$idx}.manufacturing_product_id", 'Please select a target product SKU.');
                $this->dispatch('toast', message: 'Please select a target product SKU for all output rows.', type: 'error');
                return false;
            }
            $qty = (int) ($out['quantity'] ?? 0);
            if ($qty <= 0) {
                $this->addError("cuttingOutputs.{$idx}.quantity", 'Cut output quantity must be at least 1.');
                $this->dispatch('toast', message: 'Cut output quantity must be at least 1.', type: 'error');
                return false;
            }
            $pId = (int) $out['manufacturing_product_id'];
            $cutTotalsPerProduct[$pId] = ($cutTotalsPerProduct[$pId] ?? 0) + $qty;
        }

        $filledLaborAllocations = array_filter($this->laborAllocations, function ($alloc) {
            return !empty($alloc['labor_id']);
        });

        if (!empty($filledLaborAllocations)) {
            $laborTotalsPerProduct = [];
            foreach ($filledLaborAllocations as $idx => $alloc) {
                if (empty($alloc['manufacturing_product_id'])) {
                    $this->addError("laborAllocations.{$idx}.manufacturing_product_id", 'Please select the target product SKU for worker assignment.');
                    $this->dispatch('toast', message: 'Please select the target product SKU for worker assignment.', type: 'error');
                    return false;
                }
                $qty = (int) ($alloc['quantity'] ?? 0);
                if ($qty <= 0) {
                    $this->addError("laborAllocations.{$idx}.quantity", 'Processed quantity must be at least 1.');
                    $this->dispatch('toast', message: 'Processed quantity must be at least 1.', type: 'error');
                    return false;
                }

                $pId = (int) $alloc['manufacturing_product_id'];
                if (!isset($cutTotalsPerProduct[$pId])) {
                    $prod = ManufacturingProduct::find($pId);
                    $prodName = $prod ? "{$prod->name} ({$prod->code})" : "product SKU #{$pId}";
                    $msg = "Cutter labor assigned to {$prodName}, but this SKU is not listed in the Cut Piece Output Grid.";
                    $this->addError('laborAllocations', $msg);
                    $this->dispatch('toast', message: $msg, type: 'error');
                    return false;
                }

                $laborTotalsPerProduct[$pId] = ($laborTotalsPerProduct[$pId] ?? 0) + $qty;
            }

            foreach ($cutTotalsPerProduct as $pId => $cutQty) {
                $prod = ManufacturingProduct::find($pId);
                $prodName = $prod ? "{$prod->name} ({$prod->code})" : "Product SKU #{$pId}";
                $assignedLaborQty = $laborTotalsPerProduct[$pId] ?? 0;

                if ($assignedLaborQty > 0 && $assignedLaborQty > $cutQty) {
                    $msg = "Total labor processed pieces ({$assignedLaborQty} Pcs) for {$prodName} exceeds total cut output ({$cutQty} Pcs). Assigned labor pieces must match cut output exactly.";
                    $this->addError('laborAllocations', $msg);
                    $this->dispatch('toast', message: $msg, type: 'error');
                    return false;
                }

                if ($assignedLaborQty > 0 && $assignedLaborQty < $cutQty) {
                    $msg = "Total labor processed pieces ({$assignedLaborQty} Pcs) for {$prodName} is less than total cut output ({$cutQty} Pcs). All {$cutQty} cut pieces must be assigned to workers.";
                    $this->addError('laborAllocations', $msg);
                    $this->dispatch('toast', message: $msg, type: 'error');
                    return false;
                }
            }
        }

        return true;
    }

    public function nextWizardStep(): void
    {
        $this->setWizardStep($this->wizardStep + 1);
    }

    public function previousWizardStep(): void
    {
        if ($this->wizardStep > 1) {
            $this->setWizardStep($this->wizardStep - 1);
        }
    }

    public function getStageVarianceInfoProperty(): array
    {
        if (!$this->selectedTaskId) {
            return ['input_qty' => 0, 'output_qty' => 0, 'shortfall_qty' => 0, 'has_shortfall' => false, 'is_target_met' => false];
        }

        $stageExec = $this->job->stageExecutions->where('task_id', $this->selectedTaskId)->first();
        $inputQty = $stageExec ? (int) $stageExec->target_quantity : (int) $this->job->target_quantity;
        if ($inputQty <= 0) {
            $inputQty = (int) $this->job->target_quantity;
        }

        $outputQty = (int) $this->job->productOutputs->where('task_id', $this->selectedTaskId)->sum('quantity_produced');
        $shortfallQty = max(0, $inputQty - $outputQty);

        return [
            'input_qty' => $inputQty,
            'output_qty' => $outputQty,
            'shortfall_qty' => $shortfallQty,
            'has_shortfall' => $outputQty > 0 && $shortfallQty > 0,
            'is_target_met' => $outputQty >= $inputQty && $inputQty > 0,
        ];
    }

    public function completeStageAndProgress()
    {
        if (!$this->selectedTaskId) {
            return;
        }

        $stageExecution = $this->job->stageExecutions()->where('task_id', $this->selectedTaskId)->first();
        if (!$stageExecution) {
            return;
        }

        $outputQty = (int) $this->job->productOutputs()->where('task_id', $this->selectedTaskId)->sum('quantity_produced');
        $laborQty = (int) $this->job->allocations()->where('task_id', $this->selectedTaskId)->sum('quantity_processed');
        $wastedQty = (int) $this->job->wastages()->where('task_id', $this->selectedTaskId)->sum('quantity_wasted');
        
        // Sum all alterations for this job to count towards completion resolution
        $alteredQty = (int) $this->job->alterations()->sum('source_quantity');
        
        $effectiveOutput = $outputQty + $wastedQty + $alteredQty;
        
        $totalLoggedQty = max($effectiveOutput, $laborQty, $stageExecution->completed_quantity);

        $targetQty = $stageExecution->target_quantity > 0 ? $stageExecution->target_quantity : $this->job->target_quantity;
        if ($totalLoggedQty < $targetQty && $targetQty > 0) {
            $this->dispatch('toast', message: "Cannot complete stage: Recorded output ({$totalLoggedQty} Pcs) has not met stage target ({$targetQty} Pcs).", type: 'error');
            return;
        }

        $stageExecution->update([
            'status' => 'completed',
        ]);

        $this->syncStageAndJobCompletion();

        // Progress job workflow to next uncompleted stage if available
        $nextStageExec = $this->job->stageExecutions()
            ->where('status', '!=', 'completed')
            ->orderBy('sequence_number')
            ->orderBy('id')
            ->first();

        if ($nextStageExec) {
            $this->selectTask($nextStageExec->task_id);
            $this->dispatch('toast', message: 'Stage marked completed! Progressed workflow to ' . ($nextStageExec->task?->name ?? 'next stage') . '.', type: 'success');
        } else {
            // All stages completed! Mark job as completed and redirect to Production Jobs Hub
            $this->job->update(['status' => 'completed']);
            session()->flash('success', "Production Job {$this->job->job_code} completed all stages successfully (100% target achieved)!");
            return $this->redirect(route('admin.production.jobs.index'), navigate: true);
        }
    }

    protected function syncStageAndJobCompletion(): void
    {
        if (!$this->selectedTaskId) {
            return;
        }

        $stageExec = $this->job->stageExecutions()->where('task_id', $this->selectedTaskId)->first();
        if ($stageExec) {
            $outputQty = (int) $this->job->productOutputs()->where('task_id', $this->selectedTaskId)->sum('quantity_produced');
            $laborQty = (int) $this->job->allocations()->where('task_id', $this->selectedTaskId)->sum('quantity_processed');
            $wastedQty = (int) $this->job->wastages()->where('task_id', $this->selectedTaskId)->sum('quantity_wasted');
            
            $targetQty = $stageExec->target_quantity > 0 ? $stageExec->target_quantity : $this->job->target_quantity;
            
            // If the stage is already marked as completed (e.g. manually via completeStageAndProgress), do NOT revert it!
            if ($stageExec->status === 'completed') {
                // Just ensure propagation of actual output yield downstream
                $this->job->stageExecutions()
                    ->where('sequence_number', '>', $stageExec->sequence_number)
                    ->update(['target_quantity' => $outputQty]);
                return;
            }
            
            $effectiveDone = $outputQty + $wastedQty;

            // A stage is marked completed when effective done (output yield + wastage) meets target
            $isOutputTargetMet = ($effectiveDone > 0 && $effectiveDone >= $targetQty);

            if ($isOutputTargetMet && $targetQty > 0) {
                $stageExec->update([
                    'status' => 'completed',
                ]);

                // Propagate actual output yield as the new target for all subsequent stages
                $this->job->stageExecutions()
                    ->where('sequence_number', '>', $stageExec->sequence_number)
                    ->update(['target_quantity' => $outputQty]);

            } else if ($effectiveDone > 0 || $laborQty > 0) {
                $stageExec->update([
                    'status' => 'in_progress',
                ]);
            }
        }

        // Auto-mark Job as completed if all stage executions are completed
        $uncompletedCount = $this->job->stageExecutions()->where('status', '!=', 'completed')->count();
        if ($uncompletedCount === 0 && $this->job->stageExecutions()->count() > 0) {
            $this->job->update(['status' => 'completed']);
            session()->flash('success', "Production Job {$this->job->job_code} completed all stages successfully (100% target achieved)!");
            $this->redirect(route('admin.production.jobs.index'), navigate: true);
            return;
        }

        $this->job->unsetRelation('stageExecutions');
    }

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
    public $cuttingFabricMaterialId = '';
    public $cuttingFabricBatchId = '';
    public $cuttingFabricBaleId = '';
    public array $cuttingBaleRows = [
        ['bale_id' => '', 'selected_rolls' => []]
    ];
    public $cuttingConsumedLength = '';
    public $cuttingWastageLength = 0;
    public $cuttingFabricWidth = 60.00;
    public array $cuttingOutputs = [];

    // Unopened Bale Modal State
    public bool $showOpenBaleModal = false;
    public bool $showMismatchConfirmationModal = false;
    public ?int $activeBaleIdToOpen = null;
    public $baleRollCount = '';
    public array $baleRollLengths = [];
    public ?string $baleMismatchWarning = null;

    public function triggerOpenBaleModal(int $baleId)
    {
        $bale = \App\Models\InventoryBale::with('batch')->findOrFail($baleId);
        $this->activeBaleIdToOpen = $bale->id;
        $this->baleRollCount = '';
        $this->baleRollLengths = [];
        $this->baleMismatchWarning = null;
        $this->showMismatchConfirmationModal = false;

        $this->showOpenBaleModal = true;
    }

    public function updatedBaleRollCount($count)
    {
        if ($count === '' || $count === null || intval($count) <= 0) {
            $this->baleRollLengths = [];
            $this->baleMismatchWarning = null;
            return;
        }

        $count = max(1, min(50, intval($count)));
        $this->baleRollCount = $count;

        $currentCount = count($this->baleRollLengths);
        if ($currentCount < $count) {
            for ($i = $currentCount; $i < $count; $i++) {
                $this->baleRollLengths[$i] = '';
            }
        } else if ($currentCount > $count) {
            $this->baleRollLengths = array_slice($this->baleRollLengths, 0, $count);
        }

        $this->checkBaleMismatchWarning();
    }

    public function updatedBaleRollLengths()
    {
        $this->checkBaleMismatchWarning();
    }

    protected function checkBaleMismatchWarning()
    {
        if (!$this->activeBaleIdToOpen) return;
        $bale = \App\Models\InventoryBale::find($this->activeBaleIdToOpen);
        if (!$bale) return;

        $filledLengths = array_filter($this->baleRollLengths, fn($val) => $val !== '' && $val !== null);
        if (empty($filledLengths)) {
            $this->baleMismatchWarning = null;
            return;
        }

        $sum = array_sum(array_map('floatval', $filledLengths));
        $declared = (float) $bale->declared_length;

        if (abs($sum - $declared) > 0.001) {
            $diff = round($sum - $declared, 2);
            $sign = $diff > 0 ? "+{$diff}" : "{$diff}";
            $this->baleMismatchWarning = "Warning: Total measured roll length ({$sum}m) differs from declared purchase bale length ({$declared}m) by {$sign}m. This measured length ({$sum}m) will override the declared length for material calculations.";
        } else {
            $this->baleMismatchWarning = null;
        }
    }

    public function submitOpenedBaleForm()
    {
        if (!$this->activeBaleIdToOpen) return;

        if (empty($this->baleRollCount) || count($this->baleRollLengths) < 1) {
            $this->addError('baleRollCount', 'Please enter the number of rolls in the bale.');
            return;
        }

        foreach ($this->baleRollLengths as $i => $len) {
            if ($len === '' || $len === null || (float)$len <= 0) {
                $this->addError("baleRollLengths.{$i}", "Please enter a valid length for Roll #" . ($i + 1));
                return;
            }
        }

        $bale = \App\Models\InventoryBale::findOrFail($this->activeBaleIdToOpen);
        $sum = array_sum(array_map('floatval', $this->baleRollLengths));
        $declared = (float) $bale->declared_length;

        if (abs($sum - $declared) > 0.001 && !$this->showMismatchConfirmationModal) {
            $this->showMismatchConfirmationModal = true;
            return;
        }

        $this->saveOpenedBale();
    }

    public function saveOpenedBale()
    {
        if (!$this->activeBaleIdToOpen) return;
        $bale = \App\Models\InventoryBale::findOrFail($this->activeBaleIdToOpen);

        $result = $bale->openBale($this->baleRollLengths);
        $bale->refresh();
        $bale->load('activeRolls');

        // Immediately populate cuttingBaleRows for this bale with its newly recorded rolls
        foreach ($this->cuttingBaleRows as $index => $row) {
            if (isset($row['bale_id']) && (int)$row['bale_id'] === (int)$bale->id) {
                $this->cuttingBaleRows[$index]['selected_rolls'] = [];
                foreach ($bale->activeRolls as $roll) {
                    $this->cuttingBaleRows[$index]['selected_rolls'][$roll->id] = [
                        'roll_id' => $roll->id,
                        'roll_number' => $roll->roll_number,
                        'max_length' => (float) $roll->current_balance_length,
                        'cut_length' => '',
                    ];
                }
            }
        }

        $this->showOpenBaleModal = false;
        $this->showMismatchConfirmationModal = false;
        $this->activeBaleIdToOpen = null;
        $this->dispatch('toast', message: "Bale {$bale->bale_number} opened with {$bale->roll_count} rolls! Actual measured length ({$result['total_recorded_length']}m) saved for stock calculations.", type: 'success');
    }

    // Subsidiary Material Consumption Form (CAT-SUB BOM-driven)

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

        $this->job->ensureStageExecutionsExist();
        $this->job->unsetRelation('stageExecutions');

        // If job is 100% completed, redirect immediately to production jobs hub
        $uncompletedCount = $this->job->stageExecutions->where('status', '!=', 'completed')->count();
        if ($this->job->status === 'completed' || ($this->job->stageExecutions->count() > 0 && $uncompletedCount === 0)) {
            session()->flash('success', "Production Job {$this->job->job_code} is 100% completed.");
            return $this->redirect(route('admin.production.jobs.index'), navigate: true);
        }

        // Auto-select the current active (first uncompleted) stage execution
        $activeStageExec = $this->job->stageExecutions
            ->filter(fn($se) => $se->status !== 'completed')
            ->first();

        if ($activeStageExec) {
            $this->selectedTaskId = $activeStageExec->task_id;
        } else {
            $lastStageExec = $this->job->stageExecutions->last();
            $this->selectedTaskId = $lastStageExec ? $lastStageExec->task_id : ($this->routingTasks->first()?->id);
        }

        $this->resetFormRows();
        $this->resetCuttingForm();

    }

    public function getIsSelectedStageCompletedProperty(): bool
    {
        if (!$this->selectedTaskId) {
            return false;
        }

        $stageExec = $this->job->stageExecutions->where('task_id', $this->selectedTaskId)->first();
        return $stageExec && $stageExec->status === 'completed';
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
        $this->activeStep = 'workers';
        $this->wizardStep = 1;
        $this->resetValidation();

        // Refresh job relationships to reflect latest stage state
        $this->job->unsetRelation('stageExecutions');
        $this->job->unsetRelation('allocations');
        $this->job->unsetRelation('productOutputs');
        $this->job->load([
            'stageExecutions.task',
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


        if ($this->isSelectedStageCompleted) {
            $this->loadCompletedStageData();
        }
    }

    /**
     * Populate form models with recorded data when viewing a completed stage.
     */
    private function loadCompletedStageData(): void
    {
        if (!$this->selectedTaskId || !$this->isSelectedStageCompleted) {
            return;
        }

        $selectedTask = $this->routingTasks->firstWhere('id', $this->selectedTaskId);
        $isCutting = $selectedTask && ($selectedTask->name === 'Cutting' || $selectedTask->code === 'TSK-001');

        if ($isCutting) {
            $cuttingConsumptions = $this->job->materialConsumptions->where('task_id', $this->selectedTaskId);
            $firstMat = $cuttingConsumptions->first();
            if ($firstMat) {
                $this->cuttingFabricBatchId = $firstMat->inventory_batch_id;
                $this->cuttingConsumedLength = (string) $cuttingConsumptions->sum('quantity_consumed');
                $batch = $firstMat->inventoryBatch;
                if ($batch) {
                    $this->cuttingFabricWidth = (string) ($batch->width ?? 60.0);
                }
            }

            $cuttingOutputs = $this->job->productOutputs->where('task_id', $this->selectedTaskId);
            if ($cuttingOutputs->isNotEmpty()) {
                $this->cuttingOutputs = $cuttingOutputs->map(function ($po) {
                    return [
                        'manufacturing_product_id' => $po->manufacturing_product_id,
                        'quantity' => (string) $po->quantity_produced,
                    ];
                })->values()->toArray();
            }

            $cuttingWastage = $this->job->wastages->where('task_id', $this->selectedTaskId)->first();
            if ($cuttingWastage) {
                $this->cuttingWastageLength = (string) $cuttingWastage->quantity_wasted;
            }
        } else {
            $recordedConsumptions = $this->job->materialConsumptions->where('task_id', $this->selectedTaskId);
            if ($recordedConsumptions->isNotEmpty()) {
                $this->materialConsumptions = $recordedConsumptions->map(function ($mc) {
                    return [
                        'inventory_batch_id' => $mc->inventory_batch_id,
                        'quantity_consumed' => (string) $mc->quantity_consumed,
                    ];
                })->values()->toArray();
            }

            $recordedAllocations = $this->job->allocations->where('task_id', $this->selectedTaskId);
            if ($recordedAllocations->isNotEmpty()) {
                $this->laborAllocations = $recordedAllocations->map(function ($la) {
                    return [
                        'labor_id' => $la->labor_id,
                        'manufacturing_product_id' => $this->job->manufacturing_product_id ?? '',
                        'quantity' => (string) ($la->assigned_quantity ?? $la->completed_quantity),
                    ];
                })->values()->toArray();
            }

            $recordedOutputs = $this->job->productOutputs->where('task_id', $this->selectedTaskId);
            if ($recordedOutputs->isNotEmpty()) {
                $this->productionOutputs = $recordedOutputs->map(function ($po) {
                    return [
                        'manufacturing_product_id' => $po->manufacturing_product_id,
                        'quantity_produced' => (string) $po->quantity_produced,
                    ];
                })->values()->toArray();
            }
        }
    }

    /**
     * Build subsidiary consumption rows pre-filled from the product's BOM
     * when the selected task is linked to the CAT-SUB raw material category.
     */


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
        $product = $this->job->manufacturingProduct;
        if ($product) {
            $tasks = $product->tasks()->orderBy('manufacturing_product_task.sequence_number', 'asc')->get();
            if ($tasks->isNotEmpty()) {
                return $tasks;
            }
        }

        return Task::where('status', true)->get()->sortBy(function ($t) {
            $code = strtoupper($t->code ?? '');
            $name = strtoupper($t->name ?? '');
            if (str_contains($code, 'CUT') || str_contains($name, 'CUT')) return 1;
            if (str_contains($code, 'STITCH') || str_contains($name, 'STITCH')) return 2;
            if (str_contains($code, 'QC') || str_contains($name, 'QC')) return 3;
            if (str_contains($code, 'IRON') || str_contains($name, 'IRON')) return 4;
            if (str_contains($code, 'PKG') || str_contains($name, 'PKG')) return 5;
            return 10 + $t->id;
        })->values();
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

        $currentExecution = $this->job->stageExecutions->firstWhere('task_id', $this->selectedTaskId);
        if (!$currentExecution || $currentExecution->sequence_number === 1) {
            return (int) $this->job->target_quantity;
        }

        // Get target quantity assigned to this stage execution from preceding stage output
        if ($currentExecution->target_quantity > 0) {
            return (int) $currentExecution->target_quantity;
        }

        // Fallback to preceding stage execution's completed output, labor, or target quantity
        $precedingExecution = $this->job->stageExecutions
            ->where('sequence_number', '<', $currentExecution->sequence_number)
            ->sortByDesc('sequence_number')
            ->first();

        if ($precedingExecution) {
            $precedingOutput = (int) $this->job->productOutputs()
                ->where('task_id', $precedingExecution->task_id)
                ->sum('quantity_produced');

            $precedingLabor = (int) $this->job->allocations()
                ->where('task_id', $precedingExecution->task_id)
                ->sum('quantity_processed');

            $precedingCompleted = max(
                $precedingOutput,
                $precedingLabor,
                (int) $precedingExecution->completed_quantity,
                (int) $precedingExecution->target_quantity,
                (int) $this->job->target_quantity
            );

            if ($precedingCompleted > 0) {
                return $precedingCompleted;
            }
        }

        return (int) $this->job->target_quantity;
    }

    public function getPrecedingStageInfoProperty(): ?array
    {
        if (!$this->selectedTaskId) {
            return null;
        }

        $currentExecution = $this->job->stageExecutions->firstWhere('task_id', $this->selectedTaskId);
        if (!$currentExecution || $currentExecution->sequence_number === 1) {
            return null;
        }

        $precedingExecution = $this->job->stageExecutions
            ->where('sequence_number', '<', $currentExecution->sequence_number)
            ->sortByDesc('sequence_number')
            ->first();

        if (!$precedingExecution) {
            return null;
        }

        $precedingOutput = (int) $this->job->productOutputs()
            ->where('task_id', $precedingExecution->task_id)
            ->sum('quantity_produced');

        $precedingLabor = (int) $this->job->allocations()
            ->where('task_id', $precedingExecution->task_id)
            ->sum('quantity_processed');

        $precedingCompleted = max(
            $precedingOutput,
            $precedingLabor,
            (int) $precedingExecution->completed_quantity,
            (int) $precedingExecution->target_quantity,
            (int) $this->job->target_quantity
        );

        $targetQty = $precedingExecution->target_quantity > 0 ? (int) $precedingExecution->target_quantity : (int) $this->job->target_quantity;

        return [
            'task'     => $precedingExecution->task,
            'completed' => $precedingCompleted,
            'target'   => $targetQty,
            'pending_in_preceding' => max(0, $targetQty - $precedingCompleted),
        ];
    }

    public function getStagePendingQuantityProperty(): int
    {
        return max(0, $this->stageMaxAllowedOutput - $this->stageCompletedQuantity);
    }

    public function getStageAlreadyLoggedOutputProperty(): int
    {
        if (!$this->selectedTaskId) {
            return 0;
        }

        return (int) $this->job->productOutputs()
            ->where('task_id', $this->selectedTaskId)
            ->sum('quantity_produced');
    }

    public function getStageRemainingOutputYieldProperty(): int
    {
        if (!$this->selectedTaskId) {
            return 0;
        }

        $task = Task::find($this->selectedTaskId);
        $stageWorkerCompleted = $this->stageCompletedQuantity;
        $stageMaxAllowed = $this->stageMaxAllowedOutput;

        $effectiveMax = ($task && $task->is_labor_required) ? $stageWorkerCompleted : $stageMaxAllowed;

        return max(0, $effectiveMax - $this->stageAlreadyLoggedOutput);
    }

    public function getAuthorizedLaborsProperty()
    {
        if (!$this->selectedTaskId) {
            return Labor::where('status', true)->get();
        }

        $labors = Labor::where('status', true)
            ->whereHas('tasks', function ($q) {
                $q->where('tasks.id', $this->selectedTaskId);
            })
            ->get();

        if ($labors->isEmpty()) {
            return Labor::where('status', true)->get();
        }

        return $labors;
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

        // Ensure subsidiary category (CAT‑SUB) batches are available when needed
        $subCatId = \App\Models\RawMaterialCategory::where('code', 'CAT-SUB')->value('id');
        if ($subCatId && !in_array($subCatId, $allowedCategoryIds)) {
            $allowedCategoryIds[] = $subCatId;
        }
        
        // Ensure packaging materials (CAT-PKG) are available for all tasks
        $packagingCatId = \App\Models\RawMaterialCategory::where('code', 'CAT-PKG')->value('id');
        if ($packagingCatId && !in_array($packagingCatId, $allowedCategoryIds)) {
            $allowedCategoryIds[] = $packagingCatId;
        }

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

        if ($pendingQty <= 0) {
            $this->addError('laborAllocations', 'No pending quantity available for this stage (0 Pcs pending).');
            return;
        }

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

        foreach ($this->laborAllocations as $idx => $row) {
            $qty = (int) ($row['quantity'] ?? 0);
            if ($qty > $pendingQty) {
                $this->addError("laborAllocations.{$idx}.quantity", "Worker allocation quantity ({$qty} Pcs) exceeds remaining pending quantity ({$pendingQty} Pcs) for this stage.");
                return;
            }
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
            $this->syncStageAndJobCompletion();

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

                // Include packaging category for packaging tasks
                $isPackagingTask = stripos($task->name ?? '', 'pack') !== false || $task->code === 'TSK-006';
                if ($isPackagingTask) {
                    $packagingCatId = \App\Models\RawMaterialCategory::where('code', 'CAT-PKG')->value('id');
                    if ($packagingCatId && !in_array($packagingCatId, $allowedCategoryIds)) {
                        $allowedCategoryIds[] = $packagingCatId;
                    }
                }

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
        // Log each consumption
        foreach ($this->materialConsumptions as $row) {
            $batch = InventoryBatch::find($row['inventory_batch_id']);
            $consumedQty = (float) $row['quantity_consumed'];
            InventoryBatchLogger::log($batch->id, 'consumed', $consumedQty, $this->job->production_batch_id ?? null, 'Consumed for production task');
        }

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

        $producedSum = (int) array_sum(array_column($this->productionOutputs, 'quantity_produced'));
        $task = Task::find($this->selectedTaskId);

        $alreadyLoggedOutput = $this->stageAlreadyLoggedOutput;
        $stageWorkerCompleted = $this->stageCompletedQuantity;
        $remainingAllowed = $this->stageRemainingOutputYield;

        if ($task && $task->is_labor_required && $stageWorkerCompleted <= 0) {
            $taskName = $task->name;
            $this->addError('productionOutputs', "Cannot record product output for {$taskName}: Workers must complete labor allocations first (Current worker output: 0 Pcs).");
            return;
        }

        if ($remainingAllowed <= 0) {
            $taskName = $task ? $task->name : 'this stage';
            $this->addError('productionOutputs', "No remaining unlogged yield available for {$taskName} ({$alreadyLoggedOutput} Pcs already transferred to next stage out of {$stageWorkerCompleted} Pcs worker output).");
            return;
        }

        if ($producedSum > $remainingAllowed) {
            $taskName = $task ? $task->name : 'this stage';
            $this->addError(
                'productionOutputs',
                "Total product output quantity ({$producedSum} Pcs) cannot exceed the remaining unlogged output yield for {$taskName} ({$remainingAllowed} Pcs). {$alreadyLoggedOutput} Pcs have already been recorded and transferred."
            );
            return;
        }

        foreach ($this->productionOutputs as $idx => $row) {
            $qty = (int) ($row['quantity_produced'] ?? 0);
            if ($qty > $remainingAllowed) {
                $this->addError("productionOutputs.{$idx}.quantity_produced", "Product output quantity ({$qty} Pcs) exceeds remaining unlogged yield limit ({$remainingAllowed} Pcs).");
                return;
            }
        }

        $subsidiaryDeductions = [];
        if ($this->isTaskSubsidiary) {
            foreach ($this->productionOutputs as $idx => $row) {
                $prodId = !empty($row['manufacturing_product_id']) ? $row['manufacturing_product_id'] : ($this->job->manufacturing_product_id ?? null);
                $qty = (int) ($row['quantity_produced'] ?? 0);
                
                $product = ManufacturingProduct::with('subsidiaryMaterials')->find($prodId);
                if ($product && $product->subsidiaryMaterials) {
                    foreach ($product->subsidiaryMaterials as $mat) {
                        $requiredQty = $mat->pivot->consumption_quantity * $qty;
                        if ($requiredQty > 0) {
                            $matId = $mat->id;
                            if (!isset($subsidiaryDeductions[$matId])) {
                                $subsidiaryDeductions[$matId] = [
                                    'material' => $mat,
                                    'total_required' => 0,
                                ];
                            }
                            $subsidiaryDeductions[$matId]['total_required'] += $requiredQty;
                        }
                    }
                }
            }
            
            // Validate availability
            foreach ($subsidiaryDeductions as $matId => &$data) {
                $required = $data['total_required'];
                $availableBatches = InventoryBatch::where('raw_material_id', $matId)
                                                  ->where('balance_quantity', '>', 0)
                                                  ->orderBy('created_at', 'asc')
                                                  ->get();
                $totalAvailable = $availableBatches->sum('balance_quantity');
                if ($totalAvailable < $required) {
                    $matName = $data['material']->name;
                    $shortage = $required - $totalAvailable;
                    $this->addError('productionOutputs', "Insufficient inventory for subsidiary material '{$matName}'. Required: {$required}. Available: {$totalAvailable}. Shortage: {$shortage}.");
                    return;
                }
                $data['batches'] = $availableBatches;
            }
        }

        DB::transaction(function () use ($subsidiaryDeductions) {
            foreach ($this->productionOutputs as $row) {
                $prodId = !empty($row['manufacturing_product_id']) ? $row['manufacturing_product_id'] : ($this->job->manufacturing_product_id ?? null);
                JobProductionOutput::create([
                    'job_code' => $this->job->job_code,
                    'production_job_id' => $this->job->id,
                    'manufacturing_product_id' => $prodId,
                    'task_id' => $this->selectedTaskId,
                    'quantity_produced' => (int) $row['quantity_produced'],
                ]);
            }

            if ($this->isTaskSubsidiary && !empty($subsidiaryDeductions)) {
                foreach ($subsidiaryDeductions as $matId => $data) {
                    $required = $data['total_required'];
                    foreach ($data['batches'] as $batch) {
                        if ($required <= 0) break;
                        
                        $deduct = min($required, $batch->balance_quantity);
                        $totalCost = $deduct * (float) $batch->unit_cost;
                        
                        JobMaterialConsumption::create([
                            'job_code'           => $this->job->job_code,
                            'production_job_id'  => $this->job->id,
                            'inventory_batch_id' => $batch->id,
                            'task_id'            => $this->selectedTaskId,
                            'quantity_consumed'  => $deduct,
                            'unit_cost'          => $batch->unit_cost,
                            'total_cost'         => $totalCost,
                        ]);
                        
                        $batch->deductQuantity($deduct);
                        
                        InventoryBatchLogger::log($batch->id, 'consumed', $deduct, $this->job->production_batch_id ?? null, 'Subsidiary material automatically consumed for product output');
                        
                        $required -= $deduct;
                    }
                }
            }

            if ($this->job->status === 'pending') {
                $this->job->update(['status' => 'in_progress']);
            }
        });

        $this->job->load(['productOutputs.manufacturingProduct', 'productOutputs.task']);
        $this->syncStageAndJobCompletion();

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

        $wastedSum = (float) array_sum(array_column($this->wastageRecords, 'quantity_wasted'));
        $maxAllowed = $this->stageMaxAllowedOutput;

        if ($maxAllowed <= 0) {
            $this->addError('wastageRecords', 'No pending/allowed quantity available for wastage entry for this stage.');
            return;
        }

        if ($wastedSum > $maxAllowed) {
            $this->addError('wastageRecords', "Total wasted quantity ({$wastedSum} Pcs) cannot exceed maximum allowed stage quantity ({$maxAllowed} Pcs).");
            return;
        }

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

        // Auto-populate source_product_id and source_quantity if missing from alteration converter UI
        $varInfo = $this->stageVarianceInfo;
        foreach ($this->alterationRecords as $idx => $row) {
            if (empty($row['source_product_id'])) {
                $this->alterationRecords[$idx]['source_product_id'] = $this->job->manufacturing_product_id ?: (ManufacturingProduct::first()?->id);
            }
            if (empty($row['source_quantity'])) {
                $this->alterationRecords[$idx]['source_quantity'] = !empty($row['target_quantity']) ? $row['target_quantity'] : ($varInfo['shortfall_qty'] ?? 1);
            }
        }

        $this->validate([
            'alterationRecords' => 'required|array|min:1',
            'alterationRecords.*.source_product_id' => 'required|exists:manufacturing_products,id',
            'alterationRecords.*.source_quantity' => 'required|numeric|min:1',
            'alterationRecords.*.target_product_id' => 'required|exists:manufacturing_products,id',
            'alterationRecords.*.target_quantity' => 'required|numeric|min:1',
        ], [
            'alterationRecords.*.target_product_id.required' => 'Please select a target converted product.',
            'alterationRecords.*.target_product_id.exists' => 'Selected target product does not exist.',
            'alterationRecords.*.target_quantity.required' => 'Please enter quantity converted.',
            'alterationRecords.*.target_quantity.numeric' => 'Quantity converted must be a number.',
            'alterationRecords.*.target_quantity.min' => 'Quantity converted must be at least 1 unit.',
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
                    'supervisor_id' => $this->job->supervisor_id ?? auth()->id(),
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
        $task = Task::find($this->selectedTaskId);
        if (!$task) {
            $stageExec = $this->job->stageExecutions()->where('status', 'in_progress')->first();
            $task = $stageExec?->task;
        }

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
            'stageAlreadyLoggedOutput' => $this->stageAlreadyLoggedOutput,
            'stageRemainingOutputYield' => $this->stageRemainingOutputYield,
            'precedingInfo' => $this->precedingStageInfo,
        ])->title("Job {$this->job->job_code} — Detail & Stage Management");
    }

    public function addCuttingOutputRow(): void
    {
        $this->cuttingOutputs[] = [
            'manufacturing_product_id' => '',
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

    public function addCuttingBaleRow(): void
    {
        $this->cuttingBaleRows[] = ['bale_id' => '', 'selected_rolls' => []];
    }

    public function removeCuttingBaleRow(int $index): void
    {
        if (count($this->cuttingBaleRows) > 1) {
            unset($this->cuttingBaleRows[$index]);
            $this->cuttingBaleRows = array_values($this->cuttingBaleRows);
            $this->recalculateTotalCuttingConsumedLength();
        }
    }

    public function updatedCuttingBaleRows($value, $key)
    {
        $parts = explode('.', $key);
        if (count($parts) >= 2) {
            $index = intval($parts[0]);
            $field = $parts[1];

            if ($field === 'bale_id') {
                $this->cuttingBaleRows[$index]['selected_rolls'] = [];
                $baleId = $value;
                if ($baleId) {
                    $bale = \App\Models\InventoryBale::with('activeRolls')->find($baleId);
                    if ($bale && $bale->status === 'opened') {
                        foreach ($bale->activeRolls as $roll) {
                            $this->cuttingBaleRows[$index]['selected_rolls'][$roll->id] = [
                                'roll_id' => $roll->id,
                                'roll_number' => $roll->roll_number,
                                'max_length' => (float) $roll->current_balance_length,
                                'cut_length' => '',
                            ];
                        }
                    }
                }
                $this->cuttingFabricBaleId = $baleId;
            } elseif ($field === 'selected_rolls') {
                $this->recalculateTotalCuttingConsumedLength();
            }
        }
    }

    public function useFullRoll(int $baleRowIndex, int $rollId): void
    {
        if (isset($this->cuttingBaleRows[$baleRowIndex]['selected_rolls'][$rollId])) {
            $maxLen = $this->cuttingBaleRows[$baleRowIndex]['selected_rolls'][$rollId]['max_length'];
            $this->cuttingBaleRows[$baleRowIndex]['selected_rolls'][$rollId]['cut_length'] = $maxLen;
            $this->recalculateTotalCuttingConsumedLength();
        }
    }

    public function recalculateTotalCuttingConsumedLength(): void
    {
        $total = 0.0;
        foreach ($this->cuttingBaleRows as $row) {
            if (!empty($row['selected_rolls'])) {
                foreach ($row['selected_rolls'] as $r) {
                    $val = floatval($r['cut_length'] ?? 0);
                    if ($val > 0) {
                        $total += $val;
                    }
                }
            }
        }
        $this->cuttingConsumedLength = $total > 0 ? round($total, 2) : '';
    }

    public function updatedCuttingFabricMaterialId($value)
    {
        $this->cuttingFabricBatchId = '';
        $this->cuttingFabricBaleId = '';
        $this->cuttingBaleRows = [['bale_id' => '', 'selected_rolls' => []]];
        $this->cuttingConsumedLength = '';

        if ($value) {
            $mat = \App\Models\RawMaterial::find($value);
            if ($mat) {
                $this->cuttingFabricWidth = (float) ($mat->standard_width ?: 60.00);
            }

            $batches = InventoryBatch::where('raw_material_id', $value)
                ->where('balance_quantity', '>', 0)
                ->orderBy('id', 'desc')
                ->get();

            if ($batches->count() === 1) {
                $batch = $batches->first();
                $this->cuttingFabricBatchId = $batch->id;
                $this->autoEnsureBaleAndSelect($batch);
            }
        }
    }

    public function updatedCuttingFabricBatchId($value)
    {
        $this->cuttingFabricBaleId = '';
        $this->cuttingBaleRows = [['bale_id' => '', 'selected_rolls' => []]];
        $this->cuttingConsumedLength = '';

        if ($value) {
            $batch = InventoryBatch::find($value);
            if ($batch) {
                $this->autoEnsureBaleAndSelect($batch);
            }
        }
    }

    protected function autoEnsureBaleAndSelect(InventoryBatch $batch)
    {
        if ($batch->bales()->count() === 0 && (float)$batch->balance_quantity > 0) {
            $batch->createBales(1, (float) $batch->balance_quantity);
        }

        $bales = \App\Models\InventoryBale::where('inventory_batch_id', $batch->id)
            ->where('status', '!=', 'depleted')
            ->get();

        if ($bales->count() === 1) {
            $bale = $bales->first();
            $this->cuttingFabricBaleId = $bale->id;
            $this->cuttingBaleRows = [['bale_id' => $bale->id, 'selected_rolls' => []]];
            if ($bale->status === 'opened') {
                $bale->load('activeRolls');
                foreach ($bale->activeRolls as $roll) {
                    $this->cuttingBaleRows[0]['selected_rolls'][$roll->id] = [
                        'roll_id' => $roll->id,
                        'roll_number' => $roll->roll_number,
                        'max_length' => (float) $roll->current_balance_length,
                        'cut_length' => '',
                    ];
                }
            }
        }
    }

    public function getFabricMaterialsListProperty()
    {
        $materials = \App\Models\RawMaterial::active()
            ->where(function ($query) {
                $query->whereHas('category', function ($q) {
                    $q->where('unit_type', 'length_based')
                      ->orWhere('unit_type', \App\Enums\RawMaterialUnitType::LENGTH_BASED)
                      ->orWhere('code', 'like', '%FAB%')
                      ->orWhere('name', 'like', '%Fabric%');
                })
                ->orWhereHas('batches', function ($q) {
                    $q->where('balance_quantity', '>', 0);
                });
            })
            ->orderBy('name')
            ->get();

        if ($materials->isEmpty()) {
            $materials = \App\Models\RawMaterial::active()->orderBy('name')->get();
        }

        return $materials;
    }

    public function getBatchesForSelectedFabricProperty()
    {
        if (empty($this->cuttingFabricMaterialId)) {
            return collect();
        }

        return InventoryBatch::where('raw_material_id', $this->cuttingFabricMaterialId)
            ->where('balance_quantity', '>', 0)
            ->orderBy('id', 'desc')
            ->get();
    }

    public function getBalesForSelectedBatchProperty()
    {
        if (empty($this->cuttingFabricBatchId)) {
            return collect();
        }

        $batch = InventoryBatch::find($this->cuttingFabricBatchId);
        if (!$batch) {
            return collect();
        }

        if ($batch->bales()->count() === 0 && (float)$batch->balance_quantity > 0) {
            $batch->createBales(1, (float)$batch->balance_quantity);
        }

        return \App\Models\InventoryBale::where('inventory_batch_id', $batch->id)
            ->where('status', '!=', 'depleted')
            ->get();
    }

    public function resetCuttingForm()
    {
        $this->cuttingFabricMaterialId = '';
        $this->cuttingFabricBatchId = '';
        $this->cuttingFabricBaleId = '';
        $this->cuttingBaleRows = [['bale_id' => '', 'selected_rolls' => []]];
        $this->cuttingConsumedLength = '';
        $this->cuttingWastageLength = 0;
        $this->cuttingFabricWidth = 60.00;
        
        $mainProduct = $this->job->manufacturingProduct;
        $this->cuttingOutputs = [
            [
                'manufacturing_product_id' => $mainProduct?->id ?? $this->job->manufacturing_product_id ?? '',
                'quantity' => $this->job->target_quantity ?? 1,
            ]
        ];
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

        $totalCutQty = (int) array_sum(array_column($this->cuttingOutputs, 'quantity'));
        $preview = [];

        foreach ($this->cuttingOutputs as $output) {
            if (empty($output['manufacturing_product_id']) || empty($output['quantity'])) {
                continue;
            }
            $qty = (int) $output['quantity'];
            $prod = ManufacturingProduct::find($output['manufacturing_product_id']);
            $shareRatio = $totalCutQty > 0 ? ($qty / $totalCutQty) : (1 / count($this->cuttingOutputs));

            $baseCost = round($totalFabricCost * $shareRatio, 2);
            $allocatedWastage = round($totalWastageCost * $shareRatio, 2);
            $totalCost = $baseCost + $allocatedWastage;

            $preview[] = [
                'product_name' => $prod ? $prod->name : 'Unknown Product',
                'quantity' => $qty,
                'base_cost' => $baseCost,
                'allocated_wastage' => $allocatedWastage,
                'total_cost' => $totalCost,
                'cost_per_unit' => $qty > 0 ? ($totalCost / $qty) : 0.0,
            ];
        }

        return [
            'total_fabric_cost' => $totalFabricCost,
            'total_wastage_cost' => $totalWastageCost,
            'consolidated_fabric_valuation' => $totalFabricCost + $totalWastageCost,
            'preview_items' => $preview,
        ];
    }

    public function saveCuttingSession(\App\Services\FabricCostingService $costingService)
    {
        if (!auth()->user()->hasAnyRole(['super_admin', 'admin', 'Factory Supervisor']) && !auth()->user()->can('manage_labor')) {
            abort(403, 'Unauthorized action. Only Factory Supervisors can record cutting sessions.');
        }

        if ($this->isSelectedStageCompleted) {
            $this->dispatch('toast', message: 'This stage has already been completed (100% target reached) and is locked from further entry.', type: 'error');
            return;
        }

        $this->validate([
            'cuttingFabricBatchId' => 'required|exists:inventory_batches,id',
            'cuttingConsumedLength' => 'required|numeric|gt:0',
            'cuttingWastageLength' => 'required|numeric|min:0',
            'cuttingOutputs' => 'required|array|min:1',
            'cuttingOutputs.*.manufacturing_product_id' => 'required|exists:manufacturing_products,id',
            'cuttingOutputs.*.quantity' => 'required|integer|min:1',
        ], [
            'cuttingFabricBatchId.required' => 'Please select a fabric inventory batch.',
            'cuttingConsumedLength.required' => 'Consumed length is required.',
            'cuttingConsumedLength.gt' => 'Consumed length must be greater than 0.',
            'cuttingWastageLength.required' => 'Wastage length is required.',
            'cuttingOutputs.*.manufacturing_product_id.required' => 'Product is required.',
            'cuttingOutputs.*.quantity.required' => 'Quantity is required.',
        ]);

        $batch = InventoryBatch::findOrFail($this->cuttingFabricBatchId);
        if ($this->cuttingConsumedLength > (float)$batch->balance_quantity) {
            $this->addError('cuttingConsumedLength', "Consumed length exceeds available stock ({$batch->balance_quantity} {$batch->unit}).");
            return;
        }

        if (!$this->validateCuttingStep2()) {
            return;
        }

        $totalCutOutputQty = (int) array_sum(array_column($this->cuttingOutputs, 'quantity'));
        $targetQty = (int) $this->job->target_quantity;
        if ($targetQty > 0 && $totalCutOutputQty > $targetQty) {
            $this->addError('cuttingOutputs', "Total cut piece output ({$totalCutOutputQty} Pcs) cannot exceed the job target quantity ({$targetQty} Pcs).");
            return;
        }

        DB::transaction(function () use ($costingService, $batch, $totalCutOutputQty) {
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

            // Deduct lengths from selected rolls & bales across all rows
            foreach ($this->cuttingBaleRows as $bRow) {
                $baleId = $bRow['bale_id'] ?? null;
                $bale = $baleId ? \App\Models\InventoryBale::find($baleId) : null;
                $baleTotalCut = 0.0;

                if (!empty($bRow['selected_rolls'])) {
                    foreach ($bRow['selected_rolls'] as $rId => $rData) {
                        $cLen = floatval($rData['cut_length'] ?? 0);
                        if ($cLen > 0) {
                            $baleTotalCut += $cLen;
                            $roll = \App\Models\InventoryBaleRoll::find($rId);
                            if ($roll) {
                                $roll->deductLength($cLen);
                            }
                        }
                    }
                }

                if ($bale && $baleTotalCut > 0) {
                    $bale->deductLength($baleTotalCut);
                } elseif ($bale && empty($bRow['selected_rolls']) && (float)$this->cuttingConsumedLength > 0) {
                    $bale->deductLength((float) $this->cuttingConsumedLength);
                }
            }

            InventoryBatchLogger::log($batch->id, 'consumed', (float) $this->cuttingConsumedLength, $this->job->production_batch_db_id ?? $this->job->production_batch_id, 'Cutting session fabric consumption recorded');

            if ($this->job->status === 'pending') {
                $this->job->update(['status' => 'in_progress']);
            }

            // Assign primary product and set target quantity if initiated without them
            $firstOutputProdId = $this->cuttingOutputs[0]['manufacturing_product_id'] ?? null;
            $updateData = [];
            if (!$this->job->manufacturing_product_id && $firstOutputProdId) {
                $updateData['manufacturing_product_id'] = $firstOutputProdId;
            }
            if (($this->job->target_quantity <= 0) && $totalCutOutputQty > 0) {
                $updateData['target_quantity'] = $totalCutOutputQty;
            }
            if (!empty($updateData)) {
                $this->job->update($updateData);
            }

            // Save cutter labor allocations if provided
            if (!empty($this->laborAllocations)) {
                foreach ($this->laborAllocations as $alloc) {
                    if (empty($alloc['labor_id']) || empty($alloc['quantity'])) {
                        continue;
                    }
                    $labor = Labor::find($alloc['labor_id']);
                    if (!$labor) {
                        continue;
                    }

                    $prodId = $alloc['manufacturing_product_id'] ?? ($this->job->manufacturing_product_id ?? $firstOutputProdId);
                    $product = $prodId ? ManufacturingProduct::find($prodId) : null;
                    
                    $pivotRate = null;
                    if ($prodId && $this->selectedTaskId) {
                        $pivotRate = DB::table('manufacturing_product_task')
                            ->where('manufacturing_product_id', $prodId)
                            ->where('task_id', $this->selectedTaskId)
                            ->value('standard_labor_rate');
                    }
                    $pieceRate = !is_null($pivotRate) ? (float)$pivotRate : ($product ? (float)$product->getStandardLaborRateForTask($this->selectedTaskId) : 0.0);
                    $calculatedWage = $labor->payment_method === 'job_work' ? round((float)$alloc['quantity'] * $pieceRate, 2) : 0.0;

                    JobLaborAllocation::create([
                        'production_job_id' => $this->job->id,
                        'task_id' => $this->selectedTaskId,
                        'labor_id' => $labor->id,
                        'manufacturing_product_id' => $prodId,
                        'quantity_processed' => (int)$alloc['quantity'],
                        'piece_rate' => $pieceRate,
                        'calculated_wage' => $calculatedWage,
                    ]);
                }
            }

            // Sync Cutting Stage Execution progress & spawn distinct product job flows
            $stageExecution = $this->job->stageExecutions()->where('task_id', $this->selectedTaskId)->first();
            if ($stageExecution) {
                $totalLoggedCutQty = (int) $this->job->productOutputs()->where('task_id', $this->selectedTaskId)->sum('quantity_produced');
                $effectiveTarget = $this->job->target_quantity > 0 ? $this->job->target_quantity : $totalLoggedCutQty;
                $stageExecution->update([
                    'status' => ($totalLoggedCutQty > 0 && $totalLoggedCutQty >= $effectiveTarget) ? 'completed' : 'in_progress',
                    'completed_quantity' => $totalLoggedCutQty,
                    'target_quantity' => $effectiveTarget,
                ]);
            }

            // Spawn distinct downstream ProductionJob flows per cut product SKU
            $workflowService = resolve(\App\Services\Manufacturing\ProductionWorkflowService::class);
            $spawnedFlows = $workflowService->spawnDistinctProductFlowsFromCutting($this->job, $this->cuttingOutputs);
        });

        $this->job->load([
            'batch.childBatches',
            'stageExecutions',
            'materialConsumptions.inventoryBatch.rawMaterial.category',
            'productOutputs.manufacturingProduct',
            'productOutputs.task',
            'wastages.manufacturingProduct',
            'wastages.task',
        ]);

        $this->resetErrorBag();

        $flowCount = count($this->cuttingOutputs);
        $msg = "Cutting session saved! Created {$flowCount} distinct production flow(s) for the cut product SKUs.";

        $this->dispatch('toast', message: $msg, type: 'success');
        session()->flash('toast', [
            'message' => $msg,
            'type' => 'success'
        ]);

        return redirect()->route('admin.production.jobs.index');
    }
}
