<?php

namespace App\Livewire\Factory;

use App\Models\RawMaterial;
use App\Models\InventoryBatch;
use App\Models\InventoryBale;
use App\Models\InventoryBaleRoll;
use App\Models\ManufacturingProduct;
use App\Models\ProductionBatch;
use App\Models\ProductionJob;
use App\Models\JobStageExecution;
use App\Models\Labor;
use App\Models\Task;
use App\Models\User;
use App\Models\JobMaterialConsumption;
use App\Models\JobLaborAllocation;
use App\Services\Manufacturing\ProductionWorkflowService;
use App\Services\InventoryBatchLogger;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\DB;
use Exception;

#[Layout('components.admin.layout')]
class CuttingStageWizard extends Component
{
    public int $currentStep = 1;

    // Step 1: Fabric Selection & Bales/Rolls Cutting
    public array $selectedFabrics = []; 
    // Structure per fabric:
    // [
    //   'raw_material_id' => int,
    //   'inventory_batch_id' => int,
    //   'inventory_bale_id' => int,
    //   'selected_rolls' => [
    //       roll_id => ['roll_id' => int, 'cut_length' => float, 'max_length' => float]
    //   ]
    // ]

    // Unopened Bale Modal State
    public bool $showOpenBaleModal = false;
    public bool $showMismatchConfirmationModal = false;
    public ?int $activeBaleIdToOpen = null;
    public $baleRollCount = '';
    public array $baleRollLengths = [];
    public ?string $baleMismatchWarning = null;

    // Step 2: Cutting Labor & Rates
    public ?int $supervisor_id = null;
    public ?int $cutting_task_id = null;
    public array $laborAllocations = [];
    // Structure: ['labor_id' => int, 'rate' => float, 'hours_or_pcs' => float, 'total' => float]

    // Step 3: Target Output Products & Quantities (Multi-Job Creation)
    public array $targetProducts = [];
    // Structure: ['manufacturing_product_id' => int, 'planned_quantity' => int, 'priority' => string]

    public function mount()
    {
        $this->supervisor_id = auth()->id();

        // Default cutting task
        $cuttingTask = Task::where('name', 'like', '%Cut%')->where('status', true)->first();
        $this->cutting_task_id = $cuttingTask?->id ?? Task::where('status', true)->first()?->id;

        // Initialize with 1 empty fabric selection row and 1 target product row
        $this->addFabricRow();
        $this->addTargetProductRow();
    }

    public function addFabricRow()
    {
        $this->selectedFabrics[] = [
            'raw_material_id' => '',
            'inventory_batch_id' => '',
            'inventory_bale_id' => '',
            'selected_rolls' => [],
        ];
    }

    public function removeFabricRow($index)
    {
        unset($this->selectedFabrics[$index]);
        $this->selectedFabrics = array_values($this->selectedFabrics);
    }

    public function addTargetProductRow()
    {
        $this->targetProducts[] = [
            'manufacturing_product_id' => '',
            'planned_quantity' => 50,
            'priority' => 'Normal',
        ];
    }

    public function removeTargetProductRow($index)
    {
        unset($this->targetProducts[$index]);
        $this->targetProducts = array_values($this->targetProducts);
    }

    // Modal Trigger: Open Unopened Bale
    public function triggerOpenBaleModal(int $baleId)
    {
        $bale = InventoryBale::with('batch')->findOrFail($baleId);
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

        $result = $bale->openBale($this->baleRollLengths);
        $this->showOpenBaleModal = false;
        $this->showMismatchConfirmationModal = false;
        $this->activeBaleIdToOpen = null;
        $this->dispatch('toast', message: "Bale {$bale->bale_number} opened with {$bale->roll_count} rolls! Measured length ({$result['total_recorded_length']}m) recorded for stock calculations.", type: 'success');
    }

    public function toggleRollSelection($fabricIndex, $rollId)
    {
        $roll = InventoryBaleRoll::findOrFail($rollId);

        if (isset($this->selectedFabrics[$fabricIndex]['selected_rolls'][$rollId])) {
            unset($this->selectedFabrics[$fabricIndex]['selected_rolls'][$rollId]);
        } else {
            $this->selectedFabrics[$fabricIndex]['selected_rolls'][$rollId] = [
                'roll_id' => $roll->id,
                'roll_number' => $roll->roll_number,
                'max_length' => (float) $roll->current_balance_length,
                'cut_length' => (float) $roll->current_balance_length, // Default full roll cut
            ];
        }
    }

    public function setFullRollCut($fabricIndex, $rollId)
    {
        if (isset($this->selectedFabrics[$fabricIndex]['selected_rolls'][$rollId])) {
            $max = $this->selectedFabrics[$fabricIndex]['selected_rolls'][$rollId]['max_length'];
            $this->selectedFabrics[$fabricIndex]['selected_rolls'][$rollId]['cut_length'] = $max;
        }
    }

    public function addLaborRow()
    {
        $this->laborAllocations[] = [
            'labor_id' => '',
            'rate' => 150.00,
            'hours_or_pcs' => 1,
            'total' => 150.00,
        ];
    }

    public function removeLaborRow($index)
    {
        unset($this->laborAllocations[$index]);
        $this->laborAllocations = array_values($this->laborAllocations);
    }

    public function getFabricCuttingBreakdownProperty(): array
    {
        if (empty($this->selectedFabrics)) {
            return [];
        }

        $totalCutAreaBase = 0.0;
        $firstRawMaterial = null;

        foreach ($this->selectedFabrics as $fab) {
            $matId = $fab['raw_material_id'] ?? null;
            if (!$matId) continue;

            $rawMaterial = RawMaterial::with(['unitGroup', 'unitModel'])->find($matId);
            if (!$rawMaterial) continue;

            if (!$firstRawMaterial) {
                $firstRawMaterial = $rawMaterial;
            }

            foreach ($fab['selected_rolls'] ?? [] as $rollId => $rData) {
                $cutLen = floatval($rData['cut_length'] ?? 0);
                if ($cutLen <= 0) continue;

                $totalCutAreaBase += \App\Services\FabricCuttingAreaService::calculateCutArea($cutLen, $rawMaterial);
            }
        }

        if (!$firstRawMaterial) {
            return [];
        }

        $unitGroupId = $firstRawMaterial->unit_group_id;
        $widthBase = \App\Services\FabricCuttingAreaService::convertToBaseUnit((float) ($firstRawMaterial->standard_width ?: 0), $firstRawMaterial->width_unit ?: 'Centimeters', $unitGroupId);

        $totalUsedAreaBase = 0.0;
        $productDetails = [];

        foreach ($this->targetProducts as $tp) {
            $productId = $tp['manufacturing_product_id'] ?? null;
            $qty = floatval($tp['planned_quantity'] ?? 0);

            if (!$productId) continue;

            $product = ManufacturingProduct::find($productId);
            if (!$product) continue;

            $pieceAreaBase = \App\Services\FabricCuttingAreaService::calculateProductPieceArea($product, $unitGroupId);
            $itemTotalUsedAreaBase = $pieceAreaBase * $qty;
            $totalUsedAreaBase += $itemTotalUsedAreaBase;

            $productDetails[$productId] = [
                'product_id' => $product->id,
                'name' => $product->name,
                'piece_area_base' => $pieceAreaBase,
                'quantity' => $qty,
                'total_used_area_base' => $itemTotalUsedAreaBase,
            ];
        }

        $remainingAreaBase = max(0.0, $totalCutAreaBase - $totalUsedAreaBase);
        $isOverCapacity = $totalUsedAreaBase > ($totalCutAreaBase + 0.0001);

        $wastageLengthBase = $widthBase > 0 ? ($remainingAreaBase / $widthBase) : 0.0;
        $wastageLengthDisplay = \App\Services\FabricCuttingAreaService::convertFromBaseUnit($wastageLengthBase, $firstRawMaterial->unitModel ?? $firstRawMaterial->unit, $unitGroupId);

        $maxQuantities = [];
        foreach ($productDetails as $pId => $details) {
            $pieceArea = $details['piece_area_base'];
            if ($pieceArea > 0) {
                $availableAreaForThis = $remainingAreaBase + $details['total_used_area_base'];
                $maxQuantities[$pId] = max(0, (int) floor($availableAreaForThis / $pieceArea));
            } else {
                $maxQuantities[$pId] = 999999;
            }
        }

        $usagePercentage = $totalCutAreaBase > 0 ? round(($totalUsedAreaBase / $totalCutAreaBase) * 100, 1) : 0;

        return [
            'cut_area_base' => round($totalCutAreaBase, 4),
            'used_area_base' => round($totalUsedAreaBase, 4),
            'remaining_area_base' => round($remainingAreaBase, 4),
            'wastage_length' => round($wastageLengthDisplay, 2),
            'usage_percentage' => $usagePercentage,
            'is_over_capacity' => $isOverCapacity,
            'over_capacity_diff_base' => $isOverCapacity ? round($totalUsedAreaBase - $totalCutAreaBase, 4) : 0.0,
            'product_details' => $productDetails,
            'max_quantities' => $maxQuantities,
            'unit_name' => $firstRawMaterial->unit,
        ];
    }

    public function goToStep($step)
    {
        if ($step === 2) {
            $this->validateStep1();
        } elseif ($step === 3) {
            $this->validateStep2();
        }
        $this->currentStep = $step;
    }

    protected function validateStep1()
    {
        $hasSelectedRolls = false;
        foreach ($this->selectedFabrics as $index => $fab) {
            if (empty($fab['raw_material_id'])) {
                $this->addError("selectedFabrics.{$index}.raw_material_id", "Please select a fabric raw material.");
            }
            if (empty($fab['inventory_bale_id'])) {
                $this->addError("selectedFabrics.{$index}.inventory_bale_id", "Please select a fabric bale.");
            }
            if (!empty($fab['selected_rolls'])) {
                $hasSelectedRolls = true;
                foreach ($fab['selected_rolls'] as $rollId => $rollData) {
                    $cutLen = floatval($rollData['cut_length'] ?? 0);
                    $maxLen = floatval($rollData['max_length'] ?? 0);
                    if ($cutLen <= 0 || $cutLen > $maxLen) {
                        $this->addError("selectedFabrics.{$index}.selected_rolls.{$rollId}.cut_length", "Cut length must be between 0.01 and {$maxLen}.");
                    }
                }
            }
        }

        if (!$hasSelectedRolls) {
            $this->addError('step1_rolls', 'Please select at least one roll and enter cut length.');
        }

        if ($this->getErrorBag()->isNotEmpty()) {
            throw new Exception("Step 1 validation failed.");
        }
    }

    protected function validateStep2()
    {
        if (empty($this->supervisor_id)) {
            $this->addError('supervisor_id', 'Supervisor is required.');
        }
        if (empty($this->cutting_task_id)) {
            $this->addError('cutting_task_id', 'Cutting Task is required.');
        }

        if ($this->getErrorBag()->isNotEmpty()) {
            throw new Exception("Step 2 validation failed.");
        }
    }

    public function submitCuttingStage()
    {
        $this->validateStep1();
        $this->validateStep2();

        if (empty($this->targetProducts)) {
            $this->addError('targetProducts', 'Please select at least one target output product.');
            return;
        }

        foreach ($this->targetProducts as $idx => $tp) {
            if (empty($tp['manufacturing_product_id'])) {
                $this->addError("targetProducts.{$idx}.manufacturing_product_id", "Target product is required.");
            }
            if (empty($tp['planned_quantity']) || intval($tp['planned_quantity']) <= 0) {
                $this->addError("targetProducts.{$idx}.planned_quantity", "Planned quantity must be greater than zero.");
            }
        }

        if ($this->getErrorBag()->isNotEmpty()) {
            return;
        }

        $breakdown = $this->fabricCuttingBreakdown;
        if (!empty($breakdown) && !empty($breakdown['is_over_capacity'])) {
            $this->addError('targetProducts', "Cannot submit cutting stage: Required fabric area ({$breakdown['used_area_base']} m²) exceeds available cut fabric area ({$breakdown['cut_area_base']} m²). Please adjust target product quantities.");
            return;
        }

        DB::transaction(function () {
            // 1. Process Fabric Deductions & Calculate Total Fabric Cost
            $totalFabricCutLength = 0;
            $totalFabricCost = 0.00;
            $consumedRollLogs = [];

            foreach ($this->selectedFabrics as $fab) {
                foreach ($fab['selected_rolls'] as $rollId => $rData) {
                    $cutLen = floatval($rData['cut_length']);
                    if ($cutLen <= 0) continue;

                    $roll = InventoryBaleRoll::with('bale.batch.rawMaterial')->findOrFail($rollId);
                    $roll->deductLength($cutLen);

                    $batch = $roll->bale?->batch;
                    if ($batch) {
                        $batch->deductQuantity($cutLen);
                        $rate = (float) ($batch->purchase_rate ?: $batch->unit_cost);
                        $cost = round($cutLen * $rate, 2);

                        $totalFabricCutLength += $cutLen;
                        $totalFabricCost += $cost;

                        $consumedRollLogs[] = [
                            'inventory_batch_id' => $batch->id,
                            'roll_number' => $roll->roll_number,
                            'bale_number' => $roll->bale?->bale_number,
                            'quantity' => $cutLen,
                            'rate' => $rate,
                            'cost' => $cost,
                        ];

                        InventoryBatchLogger::log($batch->id, 'consumed', $cutLen, null, "Cut {$cutLen}m from {$roll->bale?->bale_number} ({$roll->roll_number}) in Mandatory Cutting Stage");
                    }
                }
            }

            // 2. Calculate Cutting Labor Costs & Create Labor Allocations
            $totalLaborCost = 0.00;
            foreach ($this->laborAllocations as $lab) {
                if (!empty($lab['labor_id'])) {
                    $rate = floatval($lab['rate'] ?? 0);
                    $qty = floatval($lab['hours_or_pcs'] ?? 1);
                    $totalLaborCost += round($rate * $qty, 2);
                }
            }

            // 3. Create Multi-Product Production Jobs & Allocate Costs
            $totalPlannedOutputQty = array_sum(array_map(fn($tp) => intval($tp['planned_quantity']), $this->targetProducts));
            $workflowService = resolve(ProductionWorkflowService::class);
            $createdJobs = [];

            foreach ($this->targetProducts as $tp) {
                $mProductId = intval($tp['manufacturing_product_id']);
                $plannedQty = intval($tp['planned_quantity']);
                $priority = $tp['priority'] ?? 'Normal';
                $shareRatio = $totalPlannedOutputQty > 0 ? ($plannedQty / $totalPlannedOutputQty) : (1 / count($this->targetProducts));

                // Initiate Batch & Job via Workflow Service
                $response = $workflowService->initiateBatch($mProductId, $this->supervisor_id, $plannedQty, $priority, "Multi-Job created from Mandatory Cutting Stage Wizard");
                $data = $response->getData(true)['data'];
                $batch = ProductionBatch::findOrFail($data['batch']['id']);
                $job = ProductionJob::findOrFail($data['job']['id']);

                // Proportionately record fabric consumption for this job
                foreach ($consumedRollLogs as $cLog) {
                    $jobShareQty = round($cLog['quantity'] * $shareRatio, 4);
                    $jobShareCost = round($cLog['cost'] * $shareRatio, 2);

                    JobMaterialConsumption::create([
                        'job_code' => $job->job_code,
                        'production_job_id' => $job->id,
                        'inventory_batch_id' => $cLog['inventory_batch_id'],
                        'task_id' => $this->cutting_task_id,
                        'quantity_consumed' => $jobShareQty,
                        'unit_cost' => $cLog['rate'],
                        'total_cost' => $jobShareCost,
                    ]);
                }

                // Proportionately record labor allocation for this job
                foreach ($this->laborAllocations as $lab) {
                    if (!empty($lab['labor_id'])) {
                        $rate = floatval($lab['rate'] ?? 0);
                        $qty = floatval($lab['hours_or_pcs'] ?? 1);
                        $shareWage = round(($rate * $qty) * $shareRatio, 2);

                        JobLaborAllocation::create([
                            'job_id' => $job->job_code,
                            'production_batch_id' => $batch->batch_code,
                            'labor_id' => $lab['labor_id'],
                            'task_id' => $this->cutting_task_id,
                            'rate_type' => 'piece_rate',
                            'rate_applied' => $rate,
                            'quantity_processed' => round($qty * $shareRatio, 2),
                            'calculated_wage' => $shareWage,
                            'status' => 'approved',
                        ]);
                    }
                }

                // Automatically Mark Stage 1 (Cutting) as COMPLETED and Advance Stage 2 to in_progress!
                $stage1 = $job->stageExecutions()->where('task_id', $this->cutting_task_id)->first();
                if ($stage1) {
                    $stage1->update([
                        'status' => 'completed',
                        'completed_at' => now(),
                    ]);
                } else {
                    $firstStage = $job->stageExecutions()->orderBy('sequence_number')->first();
                    if ($firstStage) {
                        $firstStage->update([
                            'status' => 'completed',
                            'completed_at' => now(),
                        ]);
                    }
                }

                // Unlock Stage 2 (Stitching)
                $nextStage = $job->stageExecutions()->where('status', 'pending')->orderBy('sequence_number')->first();
                if ($nextStage) {
                    $nextStage->update([
                        'status' => 'in_progress',
                        'target_quantity' => $plannedQty,
                        'started_at' => now(),
                    ]);
                }

                $createdJobs[] = $job->job_code;
            }

            session()->flash('toast', [
                'message' => 'Mandatory Cutting Stage completed! Multi-Jobs created successfully: ' . implode(', ', $createdJobs),
                'type' => 'success'
            ]);
        });

        return redirect()->route('factory.tasks.index');
    }

    public function updatedSelectedFabrics($value, $key)
    {
        $parts = explode('.', $key);
        if (count($parts) >= 2) {
            $index = intval($parts[0]);
            $field = $parts[1];

            if ($field === 'raw_material_id') {
                $this->selectedFabrics[$index]['inventory_batch_id'] = '';
                $this->selectedFabrics[$index]['inventory_bale_id'] = '';
                $this->selectedFabrics[$index]['selected_rolls'] = [];

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
                $this->selectedFabrics[$index]['inventory_bale_id'] = '';
                $this->selectedFabrics[$index]['selected_rolls'] = [];

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
        if ($batch->bales()->count() === 0 && (float)$batch->balance_quantity > 0) {
            $batch->createBales(1, (float) $batch->balance_quantity);
        }

        $bales = InventoryBale::where('inventory_batch_id', $batch->id)->where('status', '!=', 'depleted')->get();
        if ($bales->count() === 1) {
            $this->selectedFabrics[$index]['inventory_bale_id'] = $bales->first()->id;
        }
    }

    public function render()
    {
        $fabricMaterials = RawMaterial::active()
            ->fabricsOnly()
            ->orderBy('name')
            ->get();

        $manufacturingProducts = ManufacturingProduct::active()->orderBy('name')->get();
        $supervisors = User::where('is_active', true)->get();
        $labors = Labor::active()->orderBy('name')->get();
        $tasks = Task::where('status', true)->orderBy('name')->get();

        return view('livewire.factory.cutting-stage-wizard', [
            'fabricMaterials' => $fabricMaterials,
            'manufacturingProducts' => $manufacturingProducts,
            'supervisors' => $supervisors,
            'labors' => $labors,
            'tasks' => $tasks,
        ])->title('Mandatory Cutting Stage Wizard');
    }
}
