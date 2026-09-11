<?php

namespace App\Livewire\Admin\Production;

use App\Models\CustomizedProductionOrder;
use App\Models\ProductionJob;
use App\Models\JobStageExecution;
use App\Models\JobLaborAllocation;
use App\Models\JobProductionOutput;
use App\Models\JobWastage;
use App\Models\JobMaterialConsumption;
use App\Models\ManufacturingProduct;
use App\Models\ManufacturingProductPattern;
use App\Models\RawMaterial;
use App\Models\FabricWidth;
use App\Models\InventoryBatch;
use App\Models\InventoryBale;
use App\Models\InventoryBaleRoll;
use App\Models\Task;
use App\Models\Labor;
use App\Services\Manufacturing\ProductionWorkflowService;
use App\Services\InventoryBatchLogger;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.admin.layout')]
class CustomizedProductionDetailPage extends Component
{
    public int $orderId;
    public ?CustomizedProductionOrder $customOrder = null;
    public ?ProductionJob $job = null;
    public ?JobStageExecution $activeStage = null;
    public int $activeStep = 1;

    // Dynamic Task Routing Editing (Left Panel)
    public array $dynamicTaskRows = [];
    public bool $userClickedFinalStage = false;

    // Stage Processing Inputs (Matching JobStageWizard)
    public array $selectedFabrics = [];
    public bool $showOpenBaleModal = false;
    public bool $showMismatchConfirmationModal = false;
    public ?int $activeBaleIdToOpen = null;
    public $baleRollCount = '';
    public array $baleRollLengths = [];
    public array $baleRollMaterials = [];
    public array $baleRollWidths = [];
    public array $baleRollDesignNumbers = [];
    public array $baleRollStockIds = [];
    public array $baleAllowedMaterials = [];
    public ?string $baleMismatchWarning = null;

    // Labor Allocation Rows
    public array $laborRows = [];
    public $producedQty = 0;

    // Categorized Wastage & Reconciliation
    public $scrapQty = 0;
    public string $scrapNotes = '';
    public $damageQty = 0;
    public string $damageNotes = '';

    // Alteration Rows
    public array $alterationRows = [];
    public string $remarks = '';

    public function mount(int $id)
    {
        $this->orderId = $id;
        $this->loadOrder();
        $this->initDynamicTaskRows();
        $this->loadActiveStage();
    }

    public function loadOrder()
    {
        $this->customOrder = CustomizedProductionOrder::with([
            'rawMaterial',
            'productionJob.stageExecutions.task',
            'productionJob.stageExecutions.productOutputs',
            'productionJob.stageExecutions.allocations',
            'productionJob.materialConsumptions.inventoryBatch.rawMaterial',
            'productionJob.materialConsumptions.inventoryBaleRoll.bale',
        ])->findOrFail($this->orderId);

        if (!$this->customOrder->production_job_id) {
            $year = date('Y');
            $latestJobId = ProductionJob::max('id') ?? 0;
            $jobCode = sprintf("JOB-CUST-%s-%04d", $year, $latestJobId + 1);

            $job = ProductionJob::create([
                'job_code'        => $jobCode,
                'target_quantity' => $this->customOrder->target_quantity ?: 20,
                'status'          => $this->customOrder->status ?: 'in_progress',
                'notes'           => "Custom Production Order: {$this->customOrder->item_description}",
                'job_date'        => now()->format('Y-m-d'),
                'supervisor_id'   => auth()->id(),
            ]);

            $cuttingTask = Task::where('name', 'Cutting')->orWhere('code', 'TSK-001')->first()
                ?? Task::where('name', 'like', '%Cut%')->first()
                ?? Task::firstOrCreate(['name' => 'Cutting', 'code' => 'TSK-001'], ['status' => true]);

            JobStageExecution::create([
                'production_job_id' => $job->id,
                'task_id'          => $cuttingTask->id,
                'sequence_number'   => 1,
                'target_quantity'   => $this->customOrder->target_quantity ?: 20,
                'status'            => 'in_progress',
                'is_final_step'     => true,
                'started_at'        => now(),
            ]);

            $this->customOrder->update(['production_job_id' => $job->id]);
            $this->customOrder->load('productionJob.stageExecutions.task');
        }

        $this->job = $this->customOrder->productionJob;
    }

    public function initDynamicTaskRows()
    {
        if (!$this->job) return;

        $this->job->load('stageExecutions.task');
        $stageExecs = $this->job->stageExecutions->sortBy('sequence_number')->values();
        $lastIdx = max(0, $stageExecs->count() - 1);

        $this->dynamicTaskRows = $stageExecs->map(function ($exec, $idx) use ($lastIdx) {
            $isFinal = $this->userClickedFinalStage ? (bool) $exec->is_final_step : ($idx === $lastIdx);
            return [
                'id'            => $exec->id,
                'task_id'       => (string) $exec->task_id,
                'task_name'     => $exec->task?->name ?? "Task #" . ($idx + 1),
                'status'        => $exec->status,
                'is_skipped'     => (bool) $exec->is_skipped,
                'is_final_step' => $isFinal,
            ];
        })->toArray();

        if (!$this->userClickedFinalStage && !empty($this->dynamicTaskRows)) {
            $lastIndex = count($this->dynamicTaskRows) - 1;
            foreach ($this->dynamicTaskRows as $i => &$r) {
                $r['is_final_step'] = ($i === $lastIndex);
            }
        }
    }

    // --- STAGE SKIP & UNSKIP ACTIONS ---
    public function toggleSkipStage(int $executionId)
    {
        $stage = $this->job->stageExecutions->firstWhere('id', $executionId);
        if (!$stage || $stage->sequence_number === 1) {
            $this->dispatch('toast', message: "Cutting/Step 1 is mandatory and cannot be skipped.", type: 'error');
            return;
        }

        $workflowService = resolve(ProductionWorkflowService::class);
        $workflowService->skipStage($this->job->id, $stage->task_id);

        $this->dispatch('toast', message: "Stage {$stage->task?->name} skipped successfully.", type: 'success');
        $this->loadOrder();
        $this->initDynamicTaskRows();
        $this->loadActiveStage();
    }

    public function unskipStage(int $executionId)
    {
        $stage = $this->job->stageExecutions->firstWhere('id', $executionId);
        if (!$stage) return;

        $workflowService = resolve(ProductionWorkflowService::class);
        $workflowService->unskipStage($this->job->id, $stage->task_id);

        $this->dispatch('toast', message: "Stage {$stage->task?->name} re-enabled successfully.", type: 'success');
        $this->loadOrder();
        $this->initDynamicTaskRows();
        $this->loadActiveStage();
    }

    public function loadActiveStage()
    {
        if (!$this->job) return;
        $this->job->refresh();

        $this->activeStage = $this->job->stageExecutions
            ->where('status', 'in_progress')
            ->sortBy('sequence_number')
            ->first();

        if (!$this->activeStage) {
            $this->activeStage = $this->job->stageExecutions
                ->where('status', 'pending')
                ->sortBy('sequence_number')
                ->first()
                ?? $this->job->stageExecutions->sortByDesc('sequence_number')->first();
        }

        $this->activeStep = 1;
        $this->initStageInputs();
    }

    public function selectStage(int $executionId)
    {
        $stage = $this->job->stageExecutions->firstWhere('id', $executionId);
        if ($stage) {
            $this->activeStage = $stage;
            $this->activeStep = 1;
            $this->initStageInputs();
        }
    }

    protected function initStageInputs()
    {
        $targetQty = $this->activeStage ? (int) $this->activeStage->target_quantity : ($this->customOrder?->target_quantity ?? 20);
        $this->producedQty = $targetQty;

        $this->laborRows = [];
        $this->addLaborRow();

        $this->alterationRows = [];
        $this->addAlterationRow();

        $this->scrapQty = 0;
        $this->scrapNotes = '';
        $this->damageQty = 0;
        $this->damageNotes = '';
        $this->remarks = '';

        $this->selectedFabrics = [];
        $this->addFabricRow();
    }

    // --- DYNAMIC TASKS EDITING (LEFT PANEL) ---
    public function addDynamicTaskStage()
    {
        $taskCount = count($this->dynamicTaskRows);
        $seq = $taskCount + 1;
        $defaultTask = Task::whereNotIn('id', array_column($this->dynamicTaskRows, 'task_id'))->first()
            ?? Task::first();

        $this->dynamicTaskRows[] = [
            'id'            => null,
            'task_id'       => $defaultTask ? (string)$defaultTask->id : '',
            'task_name'     => $defaultTask?->name ?? "Task #{$seq}",
            'status'        => 'pending',
            'is_final_step' => false,
        ];

        // Unless user explicitly clicked a specific card to be final stage, new last card step defaults to final
        if (!$this->userClickedFinalStage) {
            $lastIdx = count($this->dynamicTaskRows) - 1;
            foreach ($this->dynamicTaskRows as $i => &$row) {
                $row['is_final_step'] = ($i === $lastIdx);
            }
        }

        $this->syncDynamicTaskRowsToDatabase();
    }

    public function removeDynamicTaskStage(int $index)
    {
        if (count($this->dynamicTaskRows) <= 1) {
            $this->dispatch('toast', message: 'Custom order must have at least one task stage.', type: 'error');
            return;
        }

        $row = $this->dynamicTaskRows[$index];
        if (!empty($row['id'])) {
            JobStageExecution::destroy($row['id']);
        }

        unset($this->dynamicTaskRows[$index]);
        $this->dynamicTaskRows = array_values($this->dynamicTaskRows);

        if (!$this->userClickedFinalStage || !empty($row['is_final_step'])) {
            $lastIdx = count($this->dynamicTaskRows) - 1;
            foreach ($this->dynamicTaskRows as $i => &$r) {
                $r['is_final_step'] = ($i === $lastIdx);
            }
        }

        $this->syncDynamicTaskRowsToDatabase();
    }

    public function setFinalStage(int $index)
    {
        $this->userClickedFinalStage = true;
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

                if (!empty($row['id'])) {
                    $exec = JobStageExecution::updateOrCreate(
                        ['id' => $row['id']],
                        [
                            'production_job_id' => $this->job->id,
                            'task_id'          => $tId,
                            'sequence_number'   => $seq,
                            'target_quantity'   => $targetQty,
                            'status'            => $row['status'] ?? ($idx === 0 ? 'in_progress' : 'pending'),
                            'is_final_step'     => !empty($row['is_final_step']),
                        ]
                    );
                } else {
                    $exec = JobStageExecution::create([
                        'production_job_id' => $this->job->id,
                        'task_id'          => $tId,
                        'sequence_number'   => $seq,
                        'target_quantity'   => $targetQty,
                        'status'            => $row['status'] ?? ($idx === 0 ? 'in_progress' : 'pending'),
                        'is_final_step'     => !empty($row['is_final_step']),
                    ]);
                }

                $this->dynamicTaskRows[$idx]['id'] = $exec->id;
            }
        });

        $this->job->unsetRelation('stageExecutions');
        $this->loadOrder();
        $this->initDynamicTaskRows();
        $this->loadActiveStage();
    }

    // --- FABRIC SELECTION & BALE ACTIONS (MATCHING JOBSTAGEWIZARD) ---
    public function addFabricRow()
    {
        $defaultMaterialId = $this->customOrder?->raw_material_id ? (string) $this->customOrder->raw_material_id : '';

        $row = [
            'raw_material_id'    => $defaultMaterialId,
            'fabric_width_id'    => '',
            'inventory_batch_id' => '',
            'inventory_bale_id'  => '',
            'selected_rolls'     => [],
        ];

        if ($defaultMaterialId) {
            $batches = InventoryBatch::where('raw_material_id', $defaultMaterialId)
                ->where('balance_quantity', '>', 0)
                ->orderBy('id', 'desc')
                ->get();

            if ($batches->count() === 1) {
                $batch = $batches->first();
                $row['inventory_batch_id'] = $batch->id;
                $this->autoEnsureBalesAndSelectRow($row, $batch);
            }
        }

        $this->selectedFabrics[] = $row;
    }

    public function removeFabricRow(int $index)
    {
        unset($this->selectedFabrics[$index]);
        $this->selectedFabrics = array_values($this->selectedFabrics);
    }

    public function updatedSelectedFabrics($value, $key)
    {
        $parts = explode('.', $key);
        if (count($parts) >= 2) {
            $index = (int) $parts[0];
            $field = $parts[1];

            if ($field === 'raw_material_id') {
                $this->selectedFabrics[$index]['fabric_width_id']   = '';
                $this->selectedFabrics[$index]['inventory_batch_id'] = '';
                $this->selectedFabrics[$index]['inventory_bale_id']  = '';
                $this->selectedFabrics[$index]['selected_rolls']     = [];

                $matId = $this->selectedFabrics[$index]['raw_material_id'];
                if ($matId) {
                    $batches = InventoryBatch::where('raw_material_id', $matId)
                        ->where('balance_quantity', '>', 0)
                        ->orderBy('id', 'desc')
                        ->get();

                    if ($batches->count() === 1) {
                        $batch = $batches->first();
                        $this->selectedFabrics[$index]['inventory_batch_id'] = $batch->id;
                        $this->autoEnsureBalesAndSelect($index, $batch);
                    }
                }
            } elseif ($field === 'inventory_batch_id') {
                $this->selectedFabrics[$index]['inventory_bale_id']  = '';
                $this->selectedFabrics[$index]['selected_rolls']     = [];

                $batchId = $this->selectedFabrics[$index]['inventory_batch_id'];
                if ($batchId) {
                    $batch = InventoryBatch::find($batchId);
                    if ($batch) {
                        $this->autoEnsureBalesAndSelect($index, $batch);
                    }
                }
            } elseif ($field === 'inventory_bale_id') {
                $this->selectedFabrics[$index]['selected_rolls'] = [];
            }
        }
    }

    protected function autoEnsureBalesAndSelect(int $index, InventoryBatch $batch)
    {
        if ($batch->bales()->count() === 0 && (float) $batch->balance_quantity > 0) {
            $batch->createBales(1, (float) $batch->balance_quantity);
        }

        $bales = InventoryBale::where('inventory_batch_id', $batch->id)->where('status', '!=', 'depleted')->get();
        if ($bales->count() === 1) {
            $this->selectedFabrics[$index]['inventory_bale_id'] = $bales->first()->id;
        }
    }

    protected function autoEnsureBalesAndSelectRow(array &$row, InventoryBatch $batch)
    {
        if ($batch->bales()->count() === 0 && (float) $batch->balance_quantity > 0) {
            $batch->createBales(1, (float) $batch->balance_quantity);
        }

        $bales = InventoryBale::where('inventory_batch_id', $batch->id)->where('status', '!=', 'depleted')->get();
        if ($bales->count() === 1) {
            $row['inventory_bale_id'] = $bales->first()->id;
        }
    }

    public function getFabricCuttingBreakdownProperty(): array
    {
        if (empty($this->selectedFabrics)) {
            return [];
        }

        $totalCutAreaBase = 0.0;
        $totalCutLength = 0.0;
        $totalFabricCutCost = 0.0;
        $firstRawMaterial = null;

        foreach ($this->selectedFabrics as $fab) {
            $matId = $fab['raw_material_id'] ?? null;
            if (!$matId) continue;

            $rawMaterial = RawMaterial::with(['unitGroup', 'unitModel'])->find($matId);
            if (!$rawMaterial) continue;

            if (!$firstRawMaterial) {
                $firstRawMaterial = $rawMaterial;
            }

            $batchId = $fab['inventory_batch_id'] ?? null;
            $purchaseRate = 0.0;
            if ($batchId) {
                $batch = InventoryBatch::find($batchId);
                if ($batch) {
                    $purchaseRate = (float) ($batch->purchase_rate ?: $batch->unit_cost);
                }
            }

            foreach ($fab['selected_rolls'] ?? [] as $rollId => $rData) {
                $cutLen = floatval($rData['cut_length'] ?? 0);
                if ($cutLen <= 0) continue;

                $totalCutLength += $cutLen;
                $totalFabricCutCost += ($cutLen * $purchaseRate);
                $totalCutAreaBase += \App\Services\FabricCuttingAreaService::calculateCutArea($cutLen, $rawMaterial);
            }
        }

        if (!$firstRawMaterial) {
            return [];
        }

        $unitGroupId = $firstRawMaterial->unit_group_id;
        $widthBase = \App\Services\FabricCuttingAreaService::convertToBaseUnit((float) ($firstRawMaterial->standard_width ?: 0), $firstRawMaterial->width_unit ?: 'Centimeters', $unitGroupId);

        $targetQty = (float) ($this->customOrder?->target_quantity ?: 20);
        $orderLen = (float) ($this->customOrder?->length ?: 0);
        $orderWidth = (float) ($this->customOrder?->width ?: 0);
        $lenUnit = $this->customOrder?->length_unit ?: 'Meters';
        $widthUnit = $this->customOrder?->width_unit ?: 'Centimeters';

        if ($orderLen > 0 && $orderWidth > 0) {
            $pieceLenBase = \App\Services\FabricCuttingAreaService::convertToBaseUnit($orderLen, $lenUnit, $unitGroupId);
            $pieceWidthBase = \App\Services\FabricCuttingAreaService::convertToBaseUnit($orderWidth, $widthUnit, $unitGroupId);
            $pieceAreaBase = $pieceLenBase * $pieceWidthBase;
        } else {
            $pieceAreaBase = 0.0;
        }

        $totalUsedAreaBase = $pieceAreaBase * $targetQty;
        $remainingAreaBase = max(0.0, $totalCutAreaBase - $totalUsedAreaBase);
        $isOverCapacity = $totalUsedAreaBase > ($totalCutAreaBase + 0.0001);

        $wastageLengthBase = $widthBase > 0 ? ($remainingAreaBase / $widthBase) : 0.0;
        $wastageLengthDisplay = \App\Services\FabricCuttingAreaService::convertFromBaseUnit($wastageLengthBase, $firstRawMaterial->unitModel ?? $firstRawMaterial->unit, $unitGroupId);

        $avgRate = $totalCutLength > 0 ? ($totalFabricCutCost / $totalCutLength) : 0.0;
        $totalWastageCost = round($wastageLengthDisplay * $avgRate, 2);

        return [
            'total_cut_length' => round($totalCutLength, 2),
            'cut_area_base' => round($totalCutAreaBase, 4),
            'used_area_base' => round($totalUsedAreaBase, 4),
            'remaining_area_base' => round($remainingAreaBase, 4),
            'wastage_length' => round($wastageLengthDisplay, 2),
            'total_wastage_cost' => $totalWastageCost,
            'total_fabric_cut_cost' => round($totalFabricCutCost, 2),
            'usage_percentage' => $totalCutAreaBase > 0 ? round(($totalUsedAreaBase / $totalCutAreaBase) * 100, 1) : 0,
            'is_over_capacity' => $isOverCapacity,
            'over_capacity_diff_base' => $isOverCapacity ? round($totalUsedAreaBase - $totalCutAreaBase, 4) : 0.0,
            'unit_name' => $firstRawMaterial->unit,
        ];
    }

    public function getRollCutBreakdown(int $rollId, float $cutLength, $rawMaterialId = null): array
    {
        if ($cutLength <= 0) {
            return [];
        }

        $roll = InventoryBaleRoll::with(['fabricWidth', 'rawMaterial', 'bale.batch.rawMaterial'])->find($rollId);
        $rawMaterial = $rawMaterialId ? RawMaterial::find($rawMaterialId) : null;
        $targetQty = (float) ($this->customOrder?->target_quantity ?: 20);
        $purchaseRate = (float) ($roll?->bale?->batch?->unit_cost ?: $roll?->bale?->batch?->purchase_rate ?: 0);

        return \App\Services\FabricCuttingAreaService::calculateLiveRollCutBreakdown(
            $cutLength,
            $roll,
            $rawMaterial,
            null,
            $targetQty,
            $purchaseRate
        );
    }

    public function triggerOpenBaleModal(int $baleId)
    {
        $bale = InventoryBale::with('batch.rawMaterial')->findOrFail($baleId);
        $this->activeBaleIdToOpen = $bale->id;
        $this->baleRollCount = '';
        $this->baleRollLengths = [];
        $this->baleRollMaterials = [];
        $this->baleRollWidths = [];
        $this->baleRollDesignNumbers = [];
        $this->baleRollStockIds = [];
        $this->baleMismatchWarning = null;
        $this->showMismatchConfirmationModal = false;

        $balesInPhysicalBale = InventoryBale::where('bale_number', $bale->bale_number)
            ->with('batch.rawMaterial')
            ->get();

        $allowedMap = [];
        foreach ($balesInPhysicalBale as $b) {
            if ($b->batch && $b->batch->rawMaterial) {
                $m = $b->batch->rawMaterial;
                $allowedMap[$m->id] = [
                    'id'            => $m->id,
                    'name'          => $m->name,
                    'code'          => $m->code,
                    'design_number' => $b->design_number ?? '',
                    'stock_id'      => $b->stock_id ?? '',
                ];
            }
        }

        if (empty($allowedMap) && $bale->batch && $bale->batch->rawMaterial) {
            $m = $bale->batch->rawMaterial;
            $allowedMap[$m->id] = [
                'id'            => $m->id,
                'name'          => $m->name,
                'code'          => $m->code,
                'design_number' => $bale->design_number ?? '',
                'stock_id'      => $bale->stock_id ?? '',
            ];
        }

        $this->baleAllowedMaterials = array_values($allowedMap);
        $this->showOpenBaleModal = true;
    }

    public function updatedBaleRollCount($count)
    {
        if ($count === '' || $count === null || intval($count) <= 0) {
            $this->baleRollLengths       = [];
            $this->baleRollMaterials     = [];
            $this->baleRollWidths        = [];
            $this->baleRollDesignNumbers = [];
            $this->baleRollStockIds       = [];
            $this->baleMismatchWarning   = null;
            return;
        }

        $count = max(1, min(50, intval($count)));
        $this->baleRollCount = $count;

        $bale = $this->activeBaleIdToOpen ? InventoryBale::with('batch.rawMaterial')->find($this->activeBaleIdToOpen) : null;
        $defaultMat = !empty($this->baleAllowedMaterials[0]) ? $this->baleAllowedMaterials[0] : null;
        $defaultMatId = $defaultMat['id'] ?? ($bale?->batch?->raw_material_id ?? ($this->selectedFabrics[0]['raw_material_id'] ?? ''));
        $defaultDesign = $defaultMat['design_number'] ?? ($bale?->design_number ?? '');
        $defaultStock = $defaultMat['stock_id'] ?? ($bale?->stock_id ?? '');

        $currentCount = count($this->baleRollLengths);
        if ($currentCount < $count) {
            for ($i = $currentCount; $i < $count; $i++) {
                $this->baleRollLengths[$i]       = '';
                $this->baleRollMaterials[$i]     = (string) $defaultMatId;
                $this->baleRollWidths[$i]        = '';
                $this->baleRollDesignNumbers[$i] = $defaultDesign;
                $this->baleRollStockIds[$i]       = $defaultStock;
            }
        } else if ($currentCount > $count) {
            $this->baleRollLengths       = array_slice($this->baleRollLengths, 0, $count);
            $this->baleRollMaterials     = array_slice($this->baleRollMaterials, 0, $count);
            $this->baleRollWidths        = array_slice($this->baleRollWidths, 0, $count);
            $this->baleRollDesignNumbers = array_slice($this->baleRollDesignNumbers, 0, $count);
            $this->baleRollStockIds       = array_slice($this->baleRollStockIds, 0, $count);
        }

        $this->checkBaleMismatchWarning();
    }

    public function updatedBaleRollMaterials($value, $key)
    {
        $index = (int) $key;
        if (!empty($value) && !empty($this->baleAllowedMaterials)) {
            foreach ($this->baleAllowedMaterials as $item) {
                if ($item['id'] == $value) {
                    $this->baleRollDesignNumbers[$index] = $item['design_number'] ?? '';
                    $this->baleRollStockIds[$index]       = $item['stock_id'] ?? '';
                    break;
                }
            }
        }
    }

    public function updatedBaleRollLengths()
    {
        $this->checkBaleMismatchWarning();
    }

    protected function checkBaleMismatchWarning()
    {
        if (!$this->activeBaleIdToOpen) return;
        $bale = InventoryBale::find($this->activeBaleIdToOpen);
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

        $bale = InventoryBale::findOrFail($this->activeBaleIdToOpen);
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
        $bale = InventoryBale::findOrFail($this->activeBaleIdToOpen);

        $rollData = [];
        foreach ($this->baleRollLengths as $i => $len) {
            $rollData[] = [
                'length'          => (float) $len,
                'raw_material_id' => !empty($this->baleRollMaterials[$i]) ? (int) $this->baleRollMaterials[$i] : null,
                'fabric_width_id' => !empty($this->baleRollWidths[$i]) ? (int) $this->baleRollWidths[$i] : null,
                'design_number'   => $this->baleRollDesignNumbers[$i] ?? null,
                'stock_id'        => $this->baleRollStockIds[$i] ?? null,
            ];
        }

        $result = $bale->openBale($rollData);
        $this->showOpenBaleModal = false;
        $this->showMismatchConfirmationModal = false;
        $this->activeBaleIdToOpen = null;
        $this->dispatch('toast', message: "Bale {$bale->bale_number} opened with {$bale->roll_count} rolls! Measured length ({$result['total_recorded_length']}m) recorded for stock calculations.", type: 'success');
    }

    public function toggleRollSelection(int $fabricIndex, int $rollId)
    {
        $roll = InventoryBaleRoll::findOrFail($rollId);

        if (isset($this->selectedFabrics[$fabricIndex]['selected_rolls'][$rollId])) {
            unset($this->selectedFabrics[$fabricIndex]['selected_rolls'][$rollId]);
        } else {
            $this->selectedFabrics[$fabricIndex]['selected_rolls'][$rollId] = [
                'roll_id'     => $roll->id,
                'roll_number' => $roll->roll_number,
                'max_length'  => (float) $roll->current_balance_length,
                'cut_length'  => (float) $roll->current_balance_length,
            ];
        }
    }

    public function setFullRollCut(int $fabricIndex, int $rollId)
    {
        if (isset($this->selectedFabrics[$fabricIndex]['selected_rolls'][$rollId])) {
            $max = $this->selectedFabrics[$fabricIndex]['selected_rolls'][$rollId]['max_length'];
            $this->selectedFabrics[$fabricIndex]['selected_rolls'][$rollId]['cut_length'] = $max;
        }
    }

    public function recordFabricConsumption()
    {
        if (!$this->activeStage) return;

        $hasRolls = false;
        foreach ($this->selectedFabrics as $fab) {
            if (!empty($fab['selected_rolls'])) {
                $hasRolls = true;
                break;
            }
        }

        if (!$hasRolls) {
            $this->dispatch('toast', message: 'Please select at least one fabric roll and enter the length cut.', type: 'error');
            return;
        }

        DB::transaction(function () {
            foreach ($this->selectedFabrics as $fab) {
                foreach ($fab['selected_rolls'] ?? [] as $rollId => $rData) {
                    $cutLen = floatval($rData['cut_length'] ?? 0);
                    if ($cutLen <= 0) continue;

                    $roll = InventoryBaleRoll::with('bale.batch.rawMaterial')->findOrFail($rollId);
                    $roll->deductLength($cutLen);

                    $batch = $roll->bale?->batch;
                    if ($batch) {
                        $batch->deductQuantity($cutLen);
                        $rate = (float) ($batch->purchase_rate ?: $batch->unit_cost);
                        $cost = round($cutLen * $rate, 2);

                        JobMaterialConsumption::create([
                            'job_code'               => $this->job->job_code,
                            'production_job_id'       => $this->job->id,
                            'inventory_batch_id'     => $batch->id,
                            'inventory_bale_roll_id' => $roll->id,
                            'task_id'                => $this->activeStage->task_id,
                            'quantity_consumed'      => $cutLen,
                            'unit_cost'              => $rate,
                            'total_cost'             => $cost,
                            'consumed_length'        => $cutLen,
                            'total_fabric_cost'      => $cost,
                        ]);

                        InventoryBatchLogger::log(
                            $batch->id,
                            'consumed',
                            $cutLen,
                            null,
                            "Cut {$cutLen}m from {$roll->bale?->bale_number} ({$roll->roll_number}) for Custom Job {$this->job->job_code} Stage {$this->activeStage->task?->name}"
                        );
                    }
                }
            }
        });

        $this->loadOrder();
        $this->dispatch('toast', message: 'Fabric cut consumption recorded successfully!', type: 'success');
        $this->selectedFabrics = [];
        $this->addFabricRow();
    }

    // --- LABOR ROWS ACTIONS ---
    public function addLaborRow()
    {
        $defaultRate = 10.00;
        $qty = $this->activeStage ? (int) $this->activeStage->target_quantity : ($this->customOrder?->target_quantity ?? 20);

        $this->laborRows[] = [
            'labor_id'      => '',
            'processed_qty' => $qty,
            'base_rate'     => $defaultRate,
            'bonus_rate'    => 0.00,
        ];
    }

    public function removeLaborRow($index)
    {
        unset($this->laborRows[$index]);
        $this->laborRows = array_values($this->laborRows);
    }

    // --- ALTERATION ROWS ACTIONS ---
    public function addAlterationRow()
    {
        $this->alterationRows[] = [
            'altered_qty'       => 0,
            'target_product_id' => '',
            'target_pattern_id' => '',
        ];
    }

    public function removeAlterationRow($index)
    {
        unset($this->alterationRows[$index]);
        $this->alterationRows = array_values($this->alterationRows);
    }

    public function goToStep(int $step)
    {
        $isCutting = $this->isCuttingStage($this->activeStage);
        $laborStep = $isCutting ? 2 : 1;

        if ($step > $laborStep && $this->activeStep <= $laborStep) {
            if (!$this->validateLaborRows()) {
                return;
            }
        }

        $this->activeStep = $step;
    }

    public function updatedLaborRows($value, $key)
    {
        $this->resetErrorBag();
    }

    public function validateLaborRows(): bool
    {
        $this->resetErrorBag();
        $hasSelectedWorker = false;
        foreach ($this->laborRows as $idx => $row) {
            if (empty($row['labor_id'])) {
                $this->addError("laborRows.{$idx}.labor_id", "Please select a worker for Worker #" . ($idx + 1) . ".");
            } else {
                $hasSelectedWorker = true;
            }

            if (empty($row['processed_qty']) || intval($row['processed_qty']) <= 0) {
                $this->addError("laborRows.{$idx}.processed_qty", "Quantity worked must be > 0.");
            }
        }

        if (empty($this->laborRows) || !$hasSelectedWorker) {
            $this->addError('laborRows', 'Please select at least one worker for recorded labor.');
        }

        return $this->getErrorBag()->isEmpty();
    }

    // --- COMPLETE STAGE ACTION ---
    public function completeActiveStage()
    {
        if (!$this->activeStage) {
            $this->dispatch('toast', message: "No active stage available to complete.", type: 'error');
            return;
        }

        if ($this->activeStage->status === 'completed') {
            $this->dispatch('toast', message: "Stage is already completed.", type: 'error');
            return;
        }

        if (!$this->validateLaborRows()) {
            $isCutting = $this->isCuttingStage($this->activeStage);
            $this->activeStep = $isCutting ? 2 : 1;
            return;
        }

        DB::transaction(function () {
            $taskId = $this->activeStage->task_id;

            // 1. Record Labor Allocations with Base Rate + Bonus Rate
            foreach ($this->laborRows as $lRow) {
                if (!empty($lRow['labor_id'])) {
                    $baseRate  = floatval($lRow['base_rate'] ?? 0);
                    $bonusRate = floatval($lRow['bonus_rate'] ?? 0);
                    $processed = intval($lRow['processed_qty'] ?? 0);
                    $effective = $baseRate + $bonusRate;
                    $wage      = round($effective * $processed, 2);

                    JobLaborAllocation::create([
                        'job_id'              => $this->job->job_code,
                        'production_batch_id' => $this->job->batch?->batch_code ?? 'CUST-BATCH',
                        'labor_id'            => $lRow['labor_id'],
                        'task_id'             => $taskId,
                        'rate_type'           => 'piece_rate',
                        'base_rate'           => $baseRate,
                        'bonus_rate'          => $bonusRate,
                        'rate_applied'        => $effective,
                        'quantity_processed'  => $processed,
                        'calculated_wage'     => $wage,
                        'status'              => 'approved',
                    ]);
                }
            }

            // 2. Record Product Output for this stage
            if ($this->producedQty > 0) {
                JobProductionOutput::create([
                    'job_code'                 => $this->job->job_code,
                    'production_job_id'        => $this->job->id,
                    'manufacturing_product_id' => null,
                    'task_id'                  => $taskId,
                    'quantity_produced'        => $this->producedQty,
                ]);
            }

            // 3. Final Step Reconciliation: Record Wastage
            $isFinalStep = $this->isFinalStage($this->activeStage);
            if ($isFinalStep) {
                if ($this->scrapQty > 0) {
                    JobWastage::create([
                        'job_code'          => $this->job->job_code,
                        'production_job_id' => $this->job->id,
                        'task_id'           => $taskId,
                        'wastage_type'      => 'scrap',
                        'quantity_wasted'   => $this->scrapQty,
                        'reason'            => $this->scrapNotes ?: "Completely damaged / unsalvageable scrap loss",
                    ]);
                }

                if ($this->damageQty > 0) {
                    JobWastage::create([
                        'job_code'          => $this->job->job_code,
                        'production_job_id' => $this->job->id,
                        'task_id'           => $taskId,
                        'wastage_type'      => 'damage',
                        'quantity_wasted'   => $this->damageQty,
                        'reason'            => $this->damageNotes ?: "Partially damaged / resold items",
                    ]);
                }
            }

            // 4. Update stage execution and workflow progression
            $this->activeStage->update([
                'status'       => 'completed',
                'completed_at' => now(),
            ]);

            $nextStage = $this->job->stageExecutions()
                ->where('sequence_number', '>', $this->activeStage->sequence_number)
                ->orderBy('sequence_number')
                ->first();

            if ($isFinalStep || !$nextStage) {
                $this->job->update(['status' => 'completed']);
                $this->customOrder->update(['status' => 'completed']);
            } elseif ($nextStage) {
                $nextStage->update([
                    'status'     => 'in_progress',
                    'started_at' => now(),
                ]);
            }
        });

        $this->dispatch('toast', message: "Stage {$this->activeStage->task?->name} completed successfully!", type: 'success');
        $this->loadOrder();
        $this->initDynamicTaskRows();
        $this->loadActiveStage();
    }

    public function isFinalStage(JobStageExecution $stage): bool
    {
        if ($stage->is_final_step) return true;
        $maxSeq = $this->job->stageExecutions->max('sequence_number');
        return $stage->sequence_number === $maxSeq;
    }

    public function isCuttingStage(?JobStageExecution $stage): bool
    {
        if (!$stage) return false;
        return $stage->sequence_number === 1 
            || str_contains(strtolower($stage->task?->name ?? ''), 'cut')
            || (bool) ($stage->task?->consumes_raw_material ?? false);
    }

    public function isJobFullyCompleted(): bool
    {
        if ($this->customOrder->status === 'completed' || $this->job->status === 'completed') {
            return true;
        }

        $executions = $this->job->stageExecutions;
        if ($executions->isNotEmpty() && $executions->every(fn($stg) => $stg->status === 'completed' || $stg->is_skipped)) {
            return true;
        }

        return false;
    }

    public function render()
    {
        $labors          = Labor::active()->orderBy('name')->get();
        $stageExecutions = $this->job->stageExecutions()->with('task')->orderBy('sequence_number')->get();
        $fabricMaterials = RawMaterial::active()->fabricsOnly()->orderBy('name')->get();
        if ($fabricMaterials->isEmpty()) {
            $fabricMaterials = RawMaterial::active()->orderBy('name')->get();
        }
        $fabricWidths    = FabricWidth::active()->orderBy('value', 'asc')->get();
        $allTasks        = Task::where('status', true)->orderBy('name')->get();

        return view('livewire.admin.production.customized-production-detail-page', [
            'labors'          => $labors,
            'stageExecutions' => $stageExecutions,
            'fabricMaterials' => $fabricMaterials,
            'fabricWidths'    => $fabricWidths,
            'allTasks'        => $allTasks,
        ])->title("Custom Order — {$this->customOrder?->custom_order_id}");
    }
}
