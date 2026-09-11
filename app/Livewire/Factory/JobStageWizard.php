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
    public $producedQty = 0;
    public array $subsidiaryRows = [];
    
    // Categorized Wastage & Discrepancy
    public $scrapQty = 0;
    public string $scrapNotes = '';
    public $damageQty = 0;
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

    public function getHasSubsidiaryMaterialsProperty(): bool
    {
        return $this->job->getEffectiveSubsidiaryMaterials()->isNotEmpty();
    }

    public function initSubsidiaryRows()
    {
        $this->subsidiaryRows = [];
        $materials = $this->job->getEffectiveSubsidiaryMaterials();
        $outputQty = max(0, intval($this->producedQty));

        foreach ($materials as $mat) {
            $bomPerUnit = (float) ($mat->pivot->consumption_quantity ?? 1.0);
            $stdReqQty = round($bomPerUnit * $outputQty, 4);

            $batches = InventoryBatch::where('raw_material_id', $mat->id)
                ->where('balance_quantity', '>', 0)
                ->orderBy('id', 'asc')
                ->get();

            $selectedBatch = $batches->first();
            $selectedBatchId = $selectedBatch?->id;
            $unitCost = (float) ($selectedBatch?->purchase_rate ?: ($selectedBatch?->unit_cost ?: 0.0));

            $this->subsidiaryRows[] = [
                'raw_material_id'     => $mat->id,
                'material_name'       => $mat->name,
                'material_code'       => $mat->code,
                'unit'                => $mat->unit ?? 'Pieces',
                'bom_per_unit'        => $bomPerUnit,
                'output_qty'          => $outputQty,
                'std_req_qty'         => $stdReqQty,
                'extra_qty'           => 0,
                'total_qty'           => $stdReqQty,
                'inventory_batch_id'  => $selectedBatchId,
                'unit_cost'           => $unitCost,
                'total_cost'          => round($stdReqQty * $unitCost, 2),
                'available_batches'   => $batches->map(fn($b) => [
                    'id'               => $b->id,
                    'batch_number'     => $b->batch_number,
                    'balance_quantity' => (float) $b->balance_quantity,
                    'unit_cost'        => (float) ($b->purchase_rate ?: $b->unit_cost),
                ])->toArray(),
            ];
        }
    }

    public function updated($property)
    {
        if ($property === 'producedQty' || str_starts_with($property, 'subsidiaryRows')) {
            $this->recalculateSubsidiaryRows();
        }
    }

    public function recalculateSubsidiaryRows()
    {
        $outputQty = max(0, intval($this->producedQty));
        foreach ($this->subsidiaryRows as $i => &$row) {
            $bomPerUnit = floatval($row['bom_per_unit'] ?? 1);
            $stdReqQty  = round($bomPerUnit * $outputQty, 4);
            $extraQty   = max(0, floatval($row['extra_qty'] ?? 0));

            $row['output_qty']  = $outputQty;
            $row['std_req_qty'] = $stdReqQty;
            $row['total_qty']   = round($stdReqQty + $extraQty, 4);

            $batchId = $row['inventory_batch_id'] ?? null;
            if ($batchId) {
                $batch = InventoryBatch::find($batchId);
                if ($batch) {
                    $row['unit_cost'] = (float) ($batch->purchase_rate ?: ($batch->unit_cost ?: 0.0));
                }
            }

            $row['total_cost']  = round($row['total_qty'] * floatval($row['unit_cost'] ?? 0), 2);
        }
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

        // Init subsidiary material rows for final stage
        $this->initSubsidiaryRows();
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

    public function getRollCutBreakdown(int $rollId, float $cutLength, $rawMaterialId = null): array
    {
        if ($cutLength <= 0) {
            return [];
        }

        $roll = InventoryBaleRoll::with(['fabricWidth', 'rawMaterial', 'bale.batch.rawMaterial'])->find($rollId);
        $rawMaterial = $rawMaterialId ? RawMaterial::find($rawMaterialId) : null;
        $product = $this->job->manufacturingProduct;
        $targetQty = (float) ($this->activeStage?->target_quantity ?: $this->job->target_quantity ?: 10);
        $purchaseRate = (float) ($roll?->bale?->batch?->unit_cost ?: $roll?->bale?->batch?->purchase_rate ?: 0);

        return \App\Services\FabricCuttingAreaService::calculateLiveRollCutBreakdown(
            $cutLength,
            $roll,
            $rawMaterial,
            $product,
            $targetQty,
            $purchaseRate
        );
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

                        $rawMat = $batch->rawMaterial;
                        $allocWastageCost = 0.0;
                        if ($rawMat) {
                            $targetOutputs = [
                                [
                                    'manufacturing_product_id' => $this->job->manufacturing_product_id,
                                    'planned_quantity' => $this->job->target_quantity,
                                    'pattern_id' => $this->job->pattern_id,
                                ]
                            ];
                            $bd = \App\Services\FabricCuttingAreaService::computeCuttingBreakdown($cutLen, $rawMat, $targetOutputs, $rate);
                            $allocWastageCost = (float) ($bd['total_wastage_cost'] ?? 0.0);
                        }

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
                            'calculated_base_cost'   => round(max(0, $cost - $allocWastageCost), 2),
                            'allocated_wastage_cost' => $allocWastageCost,
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
    }

    public function addSubsidiaryRow()
    {
        $subMaterials = RawMaterial::whereHas('category', function ($q) {
            $q->where('code', 'CAT-SUB')->orWhere('code', 'like', '%SUB%')->orWhere('name', 'like', '%Subsidiary%')->orWhere('name', 'like', '%Trim%');
        })->orWhere(function ($q) {
            $q->where('name', 'like', '%button%')->orWhere('name', 'like', '%zipper%')->orWhere('name', 'like', '%thread%')->orWhere('name', 'like', '%elastic%')->orWhere('name', 'like', '%label%');
        })->get();

        if ($subMaterials->isEmpty()) {
            $subMaterials = RawMaterial::where('status', 'active')->get();
        }

        $firstMat = $subMaterials->first();
        $matId = $firstMat?->id;
        $outputQty = max(0, intval($this->producedQty));

        $batches = $matId ? InventoryBatch::where('raw_material_id', $matId)->where('balance_quantity', '>', 0)->get() : collect();
        $selectedBatch = $batches->first();
        $unitCost = (float) ($selectedBatch?->purchase_rate ?: ($selectedBatch?->unit_cost ?: 0.0));

        $this->subsidiaryRows[] = [
            'raw_material_id'     => $matId,
            'material_name'       => $firstMat?->name ?? 'Subsidiary Material',
            'material_code'       => $firstMat?->code ?? '',
            'unit'                => $firstMat?->unit ?? 'Pcs',
            'bom_per_unit'        => 1.0,
            'output_qty'          => $outputQty,
            'std_req_qty'         => $outputQty,
            'extra_qty'           => 0,
            'total_qty'           => $outputQty,
            'inventory_batch_id'  => $selectedBatch?->id,
            'unit_cost'           => $unitCost,
            'total_cost'          => round($outputQty * $unitCost, 2),
            'available_batches'   => $batches->map(fn($b) => [
                'id'               => $b->id,
                'batch_number'     => $b->batch_number,
                'balance_quantity' => (float) $b->balance_quantity,
                'unit_cost'        => (float) ($b->purchase_rate ?: $b->unit_cost),
            ])->toArray(),
        ];
    }

    public function removeSubsidiaryRow(int $index)
    {
        unset($this->subsidiaryRows[$index]);
        $this->subsidiaryRows = array_values($this->subsidiaryRows);
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

    public function getFabricCuttingBreakdownProperty(): array
    {
        if (!$this->job) {
            return [];
        }

        $totalCutAreaBase = 0.0;
        $totalCutLength = 0.0;
        $totalFabricCutCost = 0.0;
        $firstRawMaterial = null;

        if (!empty($this->selectedFabrics)) {
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
        }

        if (!$firstRawMaterial && $this->job->materialConsumptions->isNotEmpty()) {
            $consumptions = $this->job->materialConsumptions;
            $totalCutLength = (float) $consumptions->sum('quantity_consumed');
            $totalFabricCutCost = (float) $consumptions->sum('total_cost');
            $firstMat = $consumptions->first()?->inventoryBatch?->rawMaterial;
            if ($firstMat) {
                $firstRawMaterial = $firstMat;
                $totalCutAreaBase = \App\Services\FabricCuttingAreaService::calculateCutArea($totalCutLength, $firstRawMaterial);
            }
        }

        if (!$firstRawMaterial) {
            return [];
        }

        $targetOutputs = [
            [
                'manufacturing_product_id' => $this->job->manufacturing_product_id,
                'planned_quantity' => $this->job->target_quantity,
                'pattern_id' => $this->job->pattern_id,
            ]
        ];

        $avgRate = $totalCutLength > 0 ? ($totalFabricCutCost / $totalCutLength) : 0.0;
        return \App\Services\FabricCuttingAreaService::computeCuttingBreakdown(
            $totalCutLength,
            $firstRawMaterial,
            $targetOutputs,
            $avgRate
        );
    }

    public function getCostSummaryProperty(): array
    {
        if (!$this->job) {
            return [];
        }
        $costingService = resolve(\App\Services\Manufacturing\ProductionCostingService::class);
        return $costingService->getJobCostSummary($this->job->id);
    }

    public function goToStep(int $step)
    {
        $laborStep = $this->isCuttingStage($this->activeStage) ? 2 : 1;

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
            $laborStep = $this->isCuttingStage($this->activeStage) ? 2 : 1;
            $this->activeStep = $laborStep;
            return;
        }

        // Validate alteration surface area before transaction
        if ($this->isFinalStage($this->activeStage) && !empty($this->alterationRows)) {
            $srcProduct = $this->job->manufacturingProduct;
            $srcPattern = $this->job->pattern;
            $srcArea = \App\Services\FabricCuttingAreaService::calculateProductPatternAreaM2($srcProduct, $srcPattern);

            foreach ($this->alterationRows as $altRow) {
                $altQty = intval($altRow['altered_qty'] ?? 0);
                $targetPId = $altRow['target_product_id'] ?? null;
                $targetPatId = $altRow['target_pattern_id'] ?? null;

                if ($altQty > 0) {
                    if (!$targetPId || !$targetPatId) {
                        $this->dispatch('toast', message: "Please select a Target Product and Target Pattern for all alteration items with altered quantity greater than 0.", type: 'error');
                        return;
                    }
                    $targetProduct = ManufacturingProduct::find($targetPId);
                    $targetPattern = $targetPatId ? \App\Models\ManufacturingProductPattern::find($targetPatId) : null;
                    if ($targetProduct) {
                        $targetArea = \App\Services\FabricCuttingAreaService::calculateProductPatternAreaM2($targetProduct, $targetPattern);
                        if ($srcArea > 0 && $targetArea > 0 && $targetArea > ($srcArea + 0.0001)) {
                            $this->dispatch('toast', message: "Cannot alter to target product '{$targetProduct->name}' ({$targetArea} m²) because its surface area is larger than source product '{$srcProduct->name}' ({$srcArea} m²).", type: 'error');
                            return;
                        }
                    }
                }
            }
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
            $actualProduced = max(0, intval($this->producedQty ?? 0));
            if ($actualProduced > 0) {
                JobProductionOutput::create([
                    'job_code'                 => $this->job->job_code,
                    'production_job_id'        => $this->job->id,
                    'manufacturing_product_id' => $this->job->manufacturing_product_id,
                    'task_id'                  => $taskId,
                    'quantity_produced'        => $actualProduced,
                ]);
            }

            // 3. Subsidiary Materials Consumption Logging (Final Stage)
            $isFinalStep = $this->isFinalStage($this->activeStage);
            if ($isFinalStep && !empty($this->subsidiaryRows)) {
                foreach ($this->subsidiaryRows as $sRow) {
                    $totQty  = floatval($sRow['total_qty'] ?? 0);
                    $batchId = $sRow['inventory_batch_id'] ?? null;

                    if ($totQty > 0 && $batchId) {
                        $invBatch = InventoryBatch::find($batchId);
                        if ($invBatch) {
                            if ($totQty > (float) $invBatch->balance_quantity) {
                                throw new Exception("Selected inventory batch {$invBatch->batch_number} has insufficient balance ({$invBatch->balance_quantity} {$invBatch->unit}) for material {$sRow['material_name']}. Requested: {$totQty}");
                            }

                            $invBatch->deductQuantity($totQty);
                            $unitCost = (float) ($invBatch->purchase_rate ?: $invBatch->unit_cost);
                            $itemTotalCost = round($totQty * $unitCost, 2);

                            JobMaterialConsumption::create([
                                'job_code'            => $this->job->job_code,
                                'production_job_id'    => $this->job->id,
                                'inventory_batch_id'  => $invBatch->id,
                                'task_id'             => $taskId,
                                'quantity_consumed'   => $totQty,
                                'unit_cost'           => $unitCost,
                                'total_cost'          => $itemTotalCost,
                            ]);

                            InventoryBatchLogger::log(
                                $invBatch->id,
                                'consumed',
                                $totQty,
                                null,
                                "Subsidiary material consumption ({$sRow['material_name']}) for Job {$this->job->job_code} Stage {$this->activeStage->task?->name}"
                            );
                        }
                    }
                }
            }

            // 4. Final Step Reconciliation: Record Wastage (Scrap & Damage) and Alteration Jobs
            $isFinalStep = $this->isFinalStage($this->activeStage);
            if ($isFinalStep) {
                // Record Scrap Wastage (Completely Unusable Loss)
                if ($this->scrapQty > 0) {
                    JobWastage::create([
                        'job_code'                 => $this->job->job_code,
                        'production_job_id'        => $this->job->id,
                        'manufacturing_product_id' => $this->job->manufacturing_product_id,
                        'pattern_id'               => $this->job->pattern_id,
                        'task_id'                  => $taskId,
                        'wastage_type'             => 'scrap',
                        'quantity_wasted'          => $this->scrapQty,
                        'reason'                   => $this->scrapNotes ?: "Completely damaged / unsalvageable scrap loss",
                    ]);
                }

                // Record Damage Wastage (Partially Damaged / Resold)
                if ($this->damageQty > 0) {
                    JobWastage::create([
                        'job_code'                 => $this->job->job_code,
                        'production_job_id'        => $this->job->id,
                        'manufacturing_product_id' => $this->job->manufacturing_product_id,
                        'pattern_id'               => $this->job->pattern_id,
                        'task_id'                  => $taskId,
                        'wastage_type'             => 'damage',
                        'quantity_wasted'          => $this->damageQty,
                        'reason'                   => $this->damageNotes ?: "Partially damaged / resold items",
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
