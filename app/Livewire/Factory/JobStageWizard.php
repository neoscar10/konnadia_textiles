<?php

namespace App\Livewire\Factory;

use App\Models\ProductionJob;
use App\Models\JobStageExecution;
use App\Models\Labor;
use App\Models\JobLaborAllocation;
use App\Models\JobWastage;
use App\Models\JobAlteration;
use App\Models\JobProductionOutput;
use App\Models\ManufacturingProduct;
use App\Models\ManufacturingProductPattern;
use App\Models\RawMaterial;
use App\Models\InventoryBatch;
use App\Models\InventoryBale;
use App\Models\InventoryBaleRoll;
use App\Models\FabricWidth;
use App\Models\JobMaterialConsumption;
use App\Services\Manufacturing\ProductionWorkflowService;
use App\Services\InventoryBatchLogger;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\DB;
use Exception;

#[Layout('components.admin.layout')]
class JobStageWizard extends Component
{
    public ProductionJob $job;
    public ?JobStageExecution $activeStage = null;
    public int $activeStep = 1;

    // Fabric & Bale Selection State (for Cutting / Raw Material Consuming Stage)
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

    // Stage Processing Inputs
    public array $laborRows = [];
    public int $producedQty = 0;
    
    // Categorized Wastage & Discrepancy
    public float $scrapQty = 0;
    public string $scrapNotes = '';
    public float $damageQty = 0;
    public string $damageNotes = '';

    // Alteration Rows
    public array $alterationRows = [];
    public string $remarks = '';

    public function mount($id = null, $job = null)
    {
        $jobId = $job ?? $id;
        if ($jobId instanceof ProductionJob) {
            $this->job = $jobId;
        } else {
            $this->job = ProductionJob::with([
                'manufacturingProduct.tasks',
                'pattern.tasks',
                'batch.factorySupervisor',
                'stageExecutions.task',
                'productOutputs',
                'wastages',
                'alterations',
                'allocations',
                'materialConsumptions.inventoryBatch.rawMaterial',
                'materialConsumptions.inventoryBaleRoll.bale',
            ])->findOrFail($jobId);
        }

        $this->job->ensureStageExecutionsExist();
        $this->loadActiveStage();
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

    protected function loadActiveStage()
    {
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

    protected function initStageInputs()
    {
        if ($this->activeStage) {
            $this->producedQty = (int) $this->activeStage->target_quantity;
        }

        $this->laborRows = [];
        $this->addLaborRow();

        $this->alterationRows = [];
        $this->addAlterationRow();

        $this->scrapQty = 0;
        $this->scrapNotes = '';
        $this->damageQty = 0;
        $this->damageNotes = '';
        $this->remarks = '';

        // Init fabric selection row if on Cutting or raw material stage
        $this->selectedFabrics = [];
        $this->addFabricRow();
    }

    // --- FABRIC SELECTION & BALE OPENING ACTIONS ---
    public function addFabricRow()
    {
        $this->selectedFabrics[] = [
            'raw_material_id'    => '',
            'fabric_width_id'    => '',
            'inventory_batch_id' => '',
            'inventory_bale_id'  => '',
            'selected_rolls'     => [],
        ];
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

        // Fetch distinct materials and their design numbers / stock IDs in this physical bale from purchase entry
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
                            "Cut {$cutLen}m from {$roll->bale?->bale_number} ({$roll->roll_number}) for Job {$this->job->job_code} Stage {$this->activeStage->task?->name}"
                        );
                    }
                }
            }
        });

        $this->job->refresh();
        $this->dispatch('toast', message: 'Fabric cut consumption recorded successfully!', type: 'success');
        $this->selectedFabrics = [];
        $this->addFabricRow();
    }

    // --- LABOR ROWS ACTIONS ---
    public function addLaborRow()
    {
        $defaultRate = 10.00;
        $qty = $this->activeStage ? (int) $this->activeStage->target_quantity : 200;

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
        $defaultProductId = $this->job->manufacturing_product_id;
        $defaultPatternId = $this->job->pattern_id;

        if (!$defaultPatternId && $defaultProductId) {
            $firstPattern = ManufacturingProductPattern::where('manufacturing_product_id', $defaultProductId)->first();
            $defaultPatternId = $firstPattern?->id;
        }

        $this->alterationRows[] = [
            'altered_qty'       => 1,
            'target_product_id' => $defaultProductId,
            'target_pattern_id' => $defaultPatternId,
        ];
    }

    public function removeAlterationRow($index)
    {
        unset($this->alterationRows[$index]);
        $this->alterationRows = array_values($this->alterationRows);
    }

    public function updatedAlterationRows($value, $key)
    {
        if (str_ends_with($key, '.target_product_id')) {
            $index = (int) explode('.', $key)[0];
            $productId = $value;

            if ($productId) {
                $firstPattern = ManufacturingProductPattern::where('manufacturing_product_id', $productId)->first();
                $this->alterationRows[$index]['target_pattern_id'] = $firstPattern?->id ?? '';
            } else {
                $this->alterationRows[$index]['target_pattern_id'] = '';
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
        $this->loadActiveStage();
    }

    public function unskipStage(int $executionId)
    {
        $stage = $this->job->stageExecutions->firstWhere('id', $executionId);
        if (!$stage) return;

        $workflowService = resolve(ProductionWorkflowService::class);
        $workflowService->unskipStage($this->job->id, $stage->task_id);

        $this->dispatch('toast', message: "Stage {$stage->task?->name} re-enabled successfully.", type: 'success');
        $this->loadActiveStage();
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
                        'production_batch_id' => $this->job->batch?->batch_code ?? 'BATCH',
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
                    'manufacturing_product_id' => $this->job->manufacturing_product_id,
                    'task_id'                  => $taskId,
                    'quantity_produced'        => $this->producedQty,
                ]);
            }

            // 3. Final Step Reconciliation: Record Wastage (Scrap & Damage) and Alteration Jobs
            $isFinalStep = $this->isFinalStage($this->activeStage);
            if ($isFinalStep) {
                // Record Scrap Wastage
                if ($this->scrapQty > 0) {
                    JobWastage::create([
                        'job_code'                 => $this->job->job_code,
                        'production_job_id'        => $this->job->id,
                        'manufacturing_product_id' => $this->job->manufacturing_product_id,
                        'pattern_id'               => $this->job->pattern_id,
                        'task_id'                  => $taskId,
                        'wastage_type'             => 'scrap',
                        'quantity_wasted'          => $this->scrapQty,
                        'reason'                   => $this->scrapNotes ?: "Scrap / Partially damaged items",
                    ]);
                }

                // Record Damage Wastage
                if ($this->damageQty > 0) {
                    JobWastage::create([
                        'job_code'                 => $this->job->job_code,
                        'production_job_id'        => $this->job->id,
                        'manufacturing_product_id' => $this->job->manufacturing_product_id,
                        'pattern_id'               => $this->job->pattern_id,
                        'task_id'                  => $taskId,
                        'wastage_type'             => 'damage',
                        'quantity_wasted'          => $this->damageQty,
                        'reason'                   => $this->damageNotes ?: "Completely damaged / unsalvageable loss",
                    ]);
                }

                // Spawn Alteration Production Jobs
                $workflowService = resolve(ProductionWorkflowService::class);
                foreach ($this->alterationRows as $altRow) {
                    $altQty    = intval($altRow['altered_qty'] ?? 0);
                    $targetPId = $altRow['target_product_id'] ?? null;
                    $targetPat = $altRow['target_pattern_id'] ?? null;

                    if ($altQty > 0 && $targetPId) {
                        $workflowService->recordJobAlteration(
                            job: $this->job,
                            sourceProductId: $this->job->manufacturing_product_id ?? $targetPId,
                            sourceQty: $altQty,
                            targetProductId: $targetPId,
                            targetQty: $altQty,
                            reason: $this->remarks ?: "Final Task Reconciliation Alteration",
                            targetPatternId: $targetPat
                        );
                    }
                }
            }

            // 4. Advance Workflow Service
            $workflowService = resolve(ProductionWorkflowService::class);
            $workflowService->completeJob($this->job->id, $taskId);
        });

        $this->dispatch('toast', message: "Stage {$this->activeStage->task?->name} completed successfully!", type: 'success');
        $this->loadActiveStage();
    }

    public function isFinalStage(JobStageExecution $stage): bool
    {
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
        if ($this->job->status === 'completed') {
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
        $allProducts     = ManufacturingProduct::with('patterns')->orderBy('name')->get();
        $stageExecutions = $this->job->stageExecutions()->with('task')->orderBy('sequence_number')->get();
        $fabricMaterials = RawMaterial::active()->fabricsOnly()->orderBy('name')->get();
        $fabricWidths    = FabricWidth::active()->orderBy('value', 'asc')->get();

        return view('livewire.factory.job-stage-wizard', [
            'labors'          => $labors,
            'allProducts'     => $allProducts,
            'stageExecutions' => $stageExecutions,
            'fabricMaterials' => $fabricMaterials,
            'fabricWidths'    => $fabricWidths,
        ])->title("Job {$this->job->job_code} — Work Order Terminal");
    }
}
