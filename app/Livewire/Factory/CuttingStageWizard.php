<?php

namespace App\Livewire\Factory;

use App\Models\RawMaterial;
use App\Models\InventoryBatch;
use App\Models\InventoryBale;
use App\Models\InventoryBaleRoll;
use App\Models\FactorySupervisor;
use App\Models\ManufacturingProduct;
use App\Models\ManufacturingProductPattern;
use App\Models\ProductionBatch;
use App\Models\ProductionJob;
use App\Models\JobStageExecution;
use App\Models\Labor;
use App\Models\Task;
use App\Models\JobMaterialConsumption;
use App\Models\JobLaborAllocation;
use App\Models\JobProductionOutput;
use App\Services\Manufacturing\ProductionWorkflowService;
use App\Services\InventoryBatchLogger;
use App\Services\FabricCuttingAreaService;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Illuminate\Support\Facades\DB;
use Exception;

#[Layout('components.admin.layout')]
class CuttingStageWizard extends Component
{
    #[Url(as: 'batch')]
    public string $batchCode = '';

    public ?ProductionBatch $batchModel = null;
    public int $currentStep = 1;

    // Step 1: Fabric Selection & Bales/Rolls Cutting + Per-Roll Products Allocation
    public array $selectedFabrics = []; 
    // Structure per fabric:
    // [
    //   'raw_material_id' => int,
    //   'inventory_batch_id' => int,
    //   'inventory_bale_id' => int,
    //   'selected_rolls' => [
    //       roll_id => [
    //           'roll_id' => int,
    //           'roll_number' => string,
    //           'max_length' => float,
    //           'cut_length' => float,
    //           'products' => [
    //               ['manufacturing_product_id' => int, 'pattern_id' => int, 'planned_quantity' => int]
    //           ]
    //       ]
    //   ]
    // ]

    // Unopened Bale Modal State
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

    // Step 2: Cutting Labor & Rates (per Product / Pattern allocation)
    public ?int $supervisor_id = null;
    public ?int $cutting_task_id = null;
    public array $laborAllocations = [];

    // Step 3: Output Items Definition (per Product / Pattern allocation)
    public array $outputItems = [];

    public function mount(?string $batch = null)
    {
        $batchCodeParam = $batch ?: request()->query('batch', '');
        if (!empty($batchCodeParam)) {
            $this->batchCode = $batchCodeParam;
            $this->batchModel = ProductionBatch::where('batch_code', $batchCodeParam)->first();
        }

        // Supervisor assignment
        if ($this->batchModel && $this->batchModel->factory_supervisor_id) {
            $this->supervisor_id = $this->batchModel->factory_supervisor_id;
        } else {
            $firstSupervisor = FactorySupervisor::active()->orderBy('name')->first();
            $this->supervisor_id = $firstSupervisor?->id;
        }

        // Default cutting task
        $cuttingTask = Task::where('name', 'like', '%Cut%')->where('status', true)->first();
        $this->cutting_task_id = $cuttingTask?->id ?? Task::where('status', true)->first()?->id;

        // Initialize with 1 empty fabric row
        $this->addFabricRow();
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

    public function removeFabricRow(int $index)
    {
        unset($this->selectedFabrics[$index]);
        $this->selectedFabrics = array_values($this->selectedFabrics);
    }

    public function toggleRollSelection(int $fabricIndex, int $rollId)
    {
        $roll = InventoryBaleRoll::findOrFail($rollId);

        if (isset($this->selectedFabrics[$fabricIndex]['selected_rolls'][$rollId])) {
            unset($this->selectedFabrics[$fabricIndex]['selected_rolls'][$rollId]);
        } else {
            $firstProd = ManufacturingProduct::active()->first();
            $firstPattern = null;
            if ($firstProd) {
                $patterns = ManufacturingProductPattern::where('manufacturing_product_id', $firstProd->id)->get();
                $firstPattern = $patterns->firstWhere('is_default', true) ?? $patterns->first();
            }

            $this->selectedFabrics[$fabricIndex]['selected_rolls'][$rollId] = [
                'roll_id'     => $roll->id,
                'roll_number' => $roll->roll_number,
                'max_length'  => (float) $roll->current_balance_length,
                'cut_length'  => (float) $roll->current_balance_length,
                'products'    => [
                    [
                        'manufacturing_product_id' => $firstProd?->id,
                        'pattern_id'               => $firstPattern?->id,
                        'planned_quantity'         => 50,
                    ]
                ],
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

    public function addProductToRoll(int $fabricIndex, int $rollId)
    {
        if (isset($this->selectedFabrics[$fabricIndex]['selected_rolls'][$rollId])) {
            $firstProd = ManufacturingProduct::active()->first();
            $firstPattern = null;
            if ($firstProd) {
                $patterns = ManufacturingProductPattern::where('manufacturing_product_id', $firstProd->id)->get();
                $firstPattern = $patterns->firstWhere('is_default', true) ?? $patterns->first();
            }

            $this->selectedFabrics[$fabricIndex]['selected_rolls'][$rollId]['products'][] = [
                'manufacturing_product_id' => $firstProd?->id,
                'pattern_id'               => $firstPattern?->id,
                'planned_quantity'         => 50,
            ];
        }
    }

    public function removeProductFromRoll(int $fabricIndex, int $rollId, int $productIndex)
    {
        if (isset($this->selectedFabrics[$fabricIndex]['selected_rolls'][$rollId]['products'][$productIndex])) {
            unset($this->selectedFabrics[$fabricIndex]['selected_rolls'][$rollId]['products'][$productIndex]);
            $this->selectedFabrics[$fabricIndex]['selected_rolls'][$rollId]['products'] = array_values(
                $this->selectedFabrics[$fabricIndex]['selected_rolls'][$rollId]['products']
            );
        }
    }

    public function updatedSelectedFabrics($value, $key)
    {
        $parts = explode('.', $key);
        if (count($parts) >= 2) {
            $index = (int) $parts[0];
            $field = $parts[1];

            if ($field === 'raw_material_id') {
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
            } elseif ($field === 'selected_rolls' && str_contains($key, 'manufacturing_product_id')) {
                // E.g. selectedFabrics.0.selected_rolls.12.products.0.manufacturing_product_id
                $rollId = $parts[3] ?? null;
                $pIdx = intval($parts[5] ?? 0);
                $prodId = intval($value);
                if ($prodId && $rollId && isset($this->selectedFabrics[$index]['selected_rolls'][$rollId]['products'][$pIdx])) {
                    $patterns = ManufacturingProductPattern::where('manufacturing_product_id', $prodId)->get();
                    $defaultPattern = $patterns->firstWhere('is_default', true) ?? $patterns->first();
                    $this->selectedFabrics[$index]['selected_rolls'][$rollId]['products'][$pIdx]['pattern_id'] = $defaultPattern?->id;
                }
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

    // Modal Trigger: Open Unopened Bale
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
        $defaultMatId = $defaultMat['id'] ?? ($bale?->batch?->raw_material_id ?? '');
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
        $this->dispatch('toast', message: "Bale {$bale->bale_number} opened with {$bale->roll_count} rolls!", type: 'success');
    }

    public function getRollCutBreakdown(int $rollId, float $cutLength, $rawMaterialId = null, array $products = []): array
    {
        if ($cutLength <= 0) {
            return [];
        }

        $roll = InventoryBaleRoll::with(['fabricWidth', 'rawMaterial', 'bale.batch.rawMaterial'])->find($rollId);
        $rawMaterial = $rawMaterialId ? RawMaterial::find($rawMaterialId) : ($roll?->rawMaterial ?? $roll?->bale?->batch?->rawMaterial);

        if (!$rawMaterial) {
            return [];
        }

        $purchaseRate = (float) ($roll?->bale?->batch?->unit_cost ?: ($roll?->bale?->batch?->purchase_rate ?: 0));

        $widthVal = (float) ($rawMaterial->standard_width ?: 60);
        $widthUnitStr = $rawMaterial->width_unit ?: 'Inches';
        $widthMeters = FabricCuttingAreaService::convertToMeters($widthVal, $widthUnitStr);

        $unitGroupId = $rawMaterial->unit_group_id;
        $cutAreaBase = FabricCuttingAreaService::calculateCutArea($cutLength, $rawMaterial);
        $totalFabricCutCost = round($cutLength * $purchaseRate, 2);

        $totalUsedAreaBase = 0.0;
        $totalStandardReqLength = 0.0;
        $productDetails = [];

        foreach ($products as $pItem) {
            $pId = intval($pItem['manufacturing_product_id'] ?? 0);
            $patId = intval($pItem['pattern_id'] ?? 0);
            $qty = floatval($pItem['planned_quantity'] ?? 0);

            if (!$pId || $qty <= 0) continue;

            $product = ManufacturingProduct::find($pId);
            if (!$product) continue;

            $pattern = $patId ? ManufacturingProductPattern::find($patId) : null;
            $pieceAreaM2 = FabricCuttingAreaService::calculateProductPatternAreaM2($product, $pattern);
            if ($pieceAreaM2 <= 0) {
                $pieceAreaM2 = FabricCuttingAreaService::calculateProductPieceArea($product, $unitGroupId);
            }

            $itemUsedAreaBase = $pieceAreaM2 * $qty;
            $totalUsedAreaBase += $itemUsedAreaBase;

            $pieceReqLen = FabricCuttingAreaService::resolvePatternFabricLength($product, $rawMaterial, $patId);
            $itemReqLen = $pieceReqLen * $qty;
            $totalStandardReqLength += $itemReqLen;

            $key = "{$pId}_{$patId}";
            $productDetails[$key] = [
                'product_id' => $product->id,
                'pattern_id' => $pattern?->id,
                'name' => $product->name,
                'pattern_name' => $pattern?->name ?? 'Standard',
                'piece_area_m2' => round($pieceAreaM2, 4),
                'quantity' => $qty,
                'piece_req_length' => round($pieceReqLen, 2),
                'total_req_length' => round($itemReqLen, 2),
                'total_used_area_m2' => round($itemUsedAreaBase, 4),
            ];
        }

        $remainingAreaBase = max(0.0, $cutAreaBase - $totalUsedAreaBase);
        $isOverCapacity = $totalUsedAreaBase > ($cutAreaBase + 0.0001);

        $wastageLengthMeters = $widthMeters > 0 ? ($remainingAreaBase / $widthMeters) : 0.0;
        $wastageCost = round($wastageLengthMeters * $purchaseRate, 2);
        $usagePercentage = $cutAreaBase > 0 ? round(($totalUsedAreaBase / $cutAreaBase) * 100, 1) : 0;
        $wastagePercentage = $cutAreaBase > 0 ? round(($remainingAreaBase / $cutAreaBase) * 100, 1) : 0;

        return [
            'cut_length' => round($cutLength, 2),
            'roll_width_display' => round($widthVal, 1) . '" (' . round($widthMeters * 100, 1) . ' cm)',
            'cut_area_m2' => round($cutAreaBase, 4),
            'used_area_m2' => round($totalUsedAreaBase, 4),
            'remaining_area_m2' => round($remainingAreaBase, 4),
            'wastage_area_m2' => round($remainingAreaBase, 4),
            'wastage_length' => round($wastageLengthMeters, 2),
            'wastage_cost' => $wastageCost,
            'total_fabric_cut_cost' => $totalFabricCutCost,
            'usage_percentage' => $usagePercentage,
            'wastage_percentage' => $wastagePercentage,
            'is_over_capacity' => $isOverCapacity,
            'over_capacity_diff_m2' => $isOverCapacity ? round($totalUsedAreaBase - $cutAreaBase, 4) : 0.0,
            'product_details' => $productDetails,
        ];
    }

    public function getUniqueAllocatedProductsProperty(): array
    {
        $allocated = [];

        foreach ($this->selectedFabrics as $fab) {
            foreach ($fab['selected_rolls'] ?? [] as $rollId => $rData) {
                $cutLen = floatval($rData['cut_length'] ?? 0);
                if ($cutLen <= 0) continue;

                foreach ($rData['products'] ?? [] as $pItem) {
                    $pId = intval($pItem['manufacturing_product_id'] ?? 0);
                    $patId = intval($pItem['pattern_id'] ?? 0);
                    $qty = intval($pItem['planned_quantity'] ?? 0);

                    if (!$pId || $qty <= 0) continue;

                    $key = "{$pId}_{$patId}";
                    if (!isset($allocated[$key])) {
                        $product = ManufacturingProduct::find($pId);
                        $pattern = $patId ? ManufacturingProductPattern::find($patId) : null;

                        $allocated[$key] = [
                            'key' => $key,
                            'manufacturing_product_id' => $pId,
                            'pattern_id' => $patId,
                            'product_name' => $product?->name ?? "Product #{$pId}",
                            'product_code' => $product?->code ?? '',
                            'pattern_name' => $pattern?->name ?? 'Default',
                            'total_quantity' => 0,
                        ];
                    }
                    $allocated[$key]['total_quantity'] += $qty;
                }
            }
        }

        return array_values($allocated);
    }

    public function getFabricCuttingBreakdownProperty(): array
    {
        $totalCutAreaBase = 0.0;
        $totalCutLength = 0.0;
        $totalFabricCutCost = 0.0;
        $totalUsedAreaBase = 0.0;
        $hasOverCapacity = false;

        foreach ($this->selectedFabrics as $fab) {
            $matId = $fab['raw_material_id'] ?? null;
            if (!$matId) continue;

            $rawMaterial = RawMaterial::find($matId);
            if (!$rawMaterial) continue;

            $batchId = $fab['inventory_batch_id'] ?? null;
            $purchaseRate = 0.0;
            if ($batchId) {
                $bObj = InventoryBatch::find($batchId);
                if ($bObj) {
                    $purchaseRate = (float) ($bObj->purchase_rate ?: $bObj->unit_cost);
                }
            }

            foreach ($fab['selected_rolls'] ?? [] as $rollId => $rData) {
                $cutLen = floatval($rData['cut_length'] ?? 0);
                if ($cutLen <= 0) continue;

                $bd = $this->getRollCutBreakdown($rollId, $cutLen, $matId, $rData['products'] ?? []);
                if (!empty($bd)) {
                    $totalCutLength += $cutLen;
                    $totalCutAreaBase += $bd['cut_area_m2'];
                    $totalUsedAreaBase += $bd['used_area_m2'];
                    $totalFabricCutCost += $bd['total_fabric_cut_cost'];
                    if (!empty($bd['is_over_capacity'])) {
                        $hasOverCapacity = true;
                    }
                }
            }
        }

        $remainingAreaBase = max(0.0, $totalCutAreaBase - $totalUsedAreaBase);
        $usagePercentage = $totalCutAreaBase > 0 ? round(($totalUsedAreaBase / $totalCutAreaBase) * 100, 1) : 0;
        $wastagePercentage = $totalCutAreaBase > 0 ? round(($remainingAreaBase / $totalCutAreaBase) * 100, 1) : 0;

        return [
            'total_cut_length' => round($totalCutLength, 2),
            'cut_area_m2' => round($totalCutAreaBase, 4),
            'used_area_m2' => round($totalUsedAreaBase, 4),
            'remaining_area_m2' => round($remainingAreaBase, 4),
            'wastage_area_m2' => round($remainingAreaBase, 4),
            'total_fabric_cut_cost' => round($totalFabricCutCost, 2),
            'usage_percentage' => $usagePercentage,
            'wastage_percentage' => $wastagePercentage,
            'is_over_capacity' => $hasOverCapacity,
        ];
    }

    protected function syncLaborAndOutputs()
    {
        $unique = $this->uniqueAllocatedProducts;

        $newLaborAllocations = [];
        $newOutputs = [];

        foreach ($unique as $item) {
            $key = $item['key'];
            $qty = intval($item['total_quantity']);
            $existingGroup = $this->laborAllocations[$key] ?? null;

            if ($existingGroup && !empty($existingGroup['workers'])) {
                $workers = $existingGroup['workers'];
                $existingGroup['total_cut_quantity'] = $qty;

                if (count($workers) === 1 && intval($workers[0]['quantity']) != $qty) {
                    $workers[0]['quantity'] = $qty;
                }
                $existingGroup['workers'] = $workers;
                $newLaborAllocations[$key] = $existingGroup;
            } else {
                $newLaborAllocations[$key] = [
                    'product_key' => $key,
                    'manufacturing_product_id' => $item['manufacturing_product_id'],
                    'pattern_id' => $item['pattern_id'],
                    'product_name' => $item['product_name'],
                    'pattern_name' => $item['pattern_name'],
                    'total_cut_quantity' => $qty,
                    'workers' => [
                        [
                            'labor_id' => null,
                            'quantity' => $qty,
                            'base_rate' => 15.00,
                            'bonus_rate' => 0.00,
                        ]
                    ]
                ];
            }

            $existingOutput = collect($this->outputItems)->firstWhere('product_key', $key);
            $newOutputs[] = [
                'product_key' => $key,
                'manufacturing_product_id' => $item['manufacturing_product_id'],
                'pattern_id' => $item['pattern_id'],
                'product_name' => $item['product_name'],
                'pattern_name' => $item['pattern_name'],
                'expected_quantity' => $qty,
                'remarks' => $existingOutput['remarks'] ?? "Cutting output for {$item['product_name']} ({$item['pattern_name']})",
            ];
        }

        $this->laborAllocations = $newLaborAllocations;
        $this->outputItems = $newOutputs;
    }

    public function addWorkerToProduct(string $productKey)
    {
        if (isset($this->laborAllocations[$productKey])) {
            $totalCut = intval($this->laborAllocations[$productKey]['total_cut_quantity'] ?? 0);
            $usedSoFar = array_sum(array_column($this->laborAllocations[$productKey]['workers'] ?? [], 'quantity'));
            $remQty = max(0, $totalCut - $usedSoFar);

            $this->laborAllocations[$productKey]['workers'][] = [
                'labor_id' => null,
                'quantity' => $remQty,
                'base_rate' => 15.00,
                'bonus_rate' => 0.00,
            ];
        }
    }

    public function removeWorkerFromProduct(string $productKey, int $workerIndex)
    {
        if (isset($this->laborAllocations[$productKey]['workers'][$workerIndex])) {
            unset($this->laborAllocations[$productKey]['workers'][$workerIndex]);
            $this->laborAllocations[$productKey]['workers'] = array_values($this->laborAllocations[$productKey]['workers']);
        }
    }

    public function assignAllToWorker(string $productKey, int $workerIndex)
    {
        if (isset($this->laborAllocations[$productKey]['workers'][$workerIndex])) {
            $totalCut = intval($this->laborAllocations[$productKey]['total_cut_quantity'] ?? 0);
            foreach ($this->laborAllocations[$productKey]['workers'] as $idx => &$w) {
                if ($idx === $workerIndex) {
                    $w['quantity'] = $totalCut;
                } else {
                    $w['quantity'] = 0;
                }
            }
            unset($w);
        }
    }

    public function goToStep(int $step)
    {
        if ($step > 1) {
            if (!$this->validateStep1()) {
                return;
            }
            $this->syncLaborAndOutputs();
        }
        if ($step > 2) {
            if (!$this->validateStep2()) {
                return;
            }
        }
        if ($step > 3) {
            if (!$this->validateStep3()) {
                return;
            }
        }
        $this->currentStep = $step;
    }

    protected function validateStep1(): bool
    {
        $hasSelectedRolls = false;
        $hasOverCapacity = false;
        $hasProductAllocated = false;

        foreach ($this->selectedFabrics as $fIdx => $fab) {
            if (empty($fab['raw_material_id'])) {
                $this->addError("selectedFabrics.{$fIdx}.raw_material_id", "Please select a fabric raw material.");
            }
            if (empty($fab['inventory_bale_id'])) {
                $this->addError("selectedFabrics.{$fIdx}.inventory_bale_id", "Please select a fabric bale.");
            }
            if (!empty($fab['selected_rolls'])) {
                foreach ($fab['selected_rolls'] as $rollId => $rollData) {
                    $cutLen = floatval($rollData['cut_length'] ?? 0);
                    $maxLen = floatval($rollData['max_length'] ?? 0);
                    if ($cutLen <= 0 || $cutLen > $maxLen) {
                        $this->addError("selectedFabrics.{$fIdx}.selected_rolls.{$rollId}.cut_length", "Cut length must be between 0.01 and {$maxLen}m.");
                    } else {
                        $hasSelectedRolls = true;
                    }

                    $prods = $rollData['products'] ?? [];
                    if (empty($prods)) {
                        $this->addError("selectedFabrics.{$fIdx}.selected_rolls.{$rollId}", "Please add at least one product to produce from Roll #{$rollData['roll_number']}.");
                    } else {
                        foreach ($prods as $pIdx => $pItem) {
                            if (empty($pItem['manufacturing_product_id'])) {
                                $this->addError("selectedFabrics.{$fIdx}.selected_rolls.{$rollId}.products.{$pIdx}.manufacturing_product_id", "Product selection is required.");
                            }
                            if (empty($pItem['planned_quantity']) || intval($pItem['planned_quantity']) <= 0) {
                                $this->addError("selectedFabrics.{$fIdx}.selected_rolls.{$rollId}.products.{$pIdx}.planned_quantity", "Quantity must be > 0.");
                            } else {
                                $hasProductAllocated = true;
                            }
                        }

                        $rBreak = $this->getRollCutBreakdown($rollId, $cutLen, $fab['raw_material_id'] ?? null, $prods);
                        if (!empty($rBreak['is_over_capacity'])) {
                            $hasOverCapacity = true;
                            $this->addError("selectedFabrics.{$fIdx}.selected_rolls.{$rollId}", "Product area allocated on Roll #{$rollData['roll_number']} ({$rBreak['used_area_m2']} m²) exceeds cut area ({$rBreak['cut_area_m2']} m²) by {$rBreak['over_capacity_diff_m2']} m²!");
                        }
                    }
                }
            }
        }

        if (!$hasSelectedRolls) {
            $this->addError('step1_rolls', 'Please select at least one roll and enter a valid cut length.');
        }

        if (!$hasProductAllocated) {
            $this->addError('step1_rolls', 'Please allocate at least one product to produce from the selected roll(s).');
        }

        if ($hasOverCapacity) {
            $this->addError('step1_rolls', 'One or more rolls have allocated product area exceeding the cut area. Please fix over-capacity errors before proceeding.');
        }

        return $this->getErrorBag()->isEmpty();
    }

    protected function validateStep2(): bool
    {
        foreach ($this->laborAllocations as $key => $group) {
            $totalCut = intval($group['total_cut_quantity'] ?? 0);
            $workers = $group['workers'] ?? [];
            $assignedTotal = 0;

            if (empty($workers)) {
                $this->addError("laborAllocations.{$key}", "Please assign at least one worker for {$group['product_name']}.");
            }

            foreach ($workers as $wIdx => $w) {
                if (empty($w['labor_id'])) {
                    $this->addError("laborAllocations.{$key}.workers.{$wIdx}.labor_id", "Please select a worker for {$group['product_name']}.");
                }
                $wQty = intval($w['quantity'] ?? 0);
                if ($wQty <= 0) {
                    $this->addError("laborAllocations.{$key}.workers.{$wIdx}.quantity", "Quantity must be > 0.");
                }
                $assignedTotal += $wQty;

                if (!isset($w['base_rate']) || floatval($w['base_rate']) < 0) {
                    $this->addError("laborAllocations.{$key}.workers.{$wIdx}.base_rate", "Base rate cannot be negative.");
                }
                if (!isset($w['bonus_rate']) || floatval($w['bonus_rate']) < 0) {
                    $this->addError("laborAllocations.{$key}.workers.{$wIdx}.bonus_rate", "Bonus rate cannot be negative.");
                }
            }

            if ($assignedTotal > $totalCut) {
                $this->addError("laborAllocations.{$key}", "Total worker quantity ({$assignedTotal} pcs) assigned for {$group['product_name']} ({$group['pattern_name']}) exceeds total cut quantity ({$totalCut} pcs).");
            }
        }

        return $this->getErrorBag()->isEmpty();
    }

    protected function validateStep3(): bool
    {
        foreach ($this->outputItems as $idx => $out) {
            if (empty($out['expected_quantity']) || intval($out['expected_quantity']) <= 0) {
                $this->addError("outputItems.{$idx}.expected_quantity", "Expected output quantity must be > 0.");
            }
        }

        return $this->getErrorBag()->isEmpty();
    }

    public function submitCuttingStage()
    {
        if (!$this->validateStep1() || !$this->validateStep2() || !$this->validateStep3()) {
            return;
        }

        $batchCode = DB::transaction(function () {
            // 1. Resolve or Create ProductionBatch
            $batch = $this->batchModel;
            if (!$batch && !empty($this->batchCode)) {
                $batch = ProductionBatch::where('batch_code', $this->batchCode)->first();
            }

            if (!$batch) {
                $latestId = ProductionBatch::max('id') ?? 0;
                $batchCodeStr = 'PB-' . date('Y') . '-' . str_pad($latestId + 1, 4, '0', STR_PAD_LEFT);
                while (ProductionBatch::where('batch_code', $batchCodeStr)->exists()) {
                    $latestId++;
                    $batchCodeStr = 'PB-' . date('Y') . '-' . str_pad($latestId + 1, 4, '0', STR_PAD_LEFT);
                }

                $batch = ProductionBatch::create([
                    'batch_code'            => $batchCodeStr,
                    'batch_date'            => now()->format('Y-m-d'),
                    'supervisor_id'         => auth()->id() ?: 1,
                    'factory_supervisor_id' => $this->supervisor_id,
                    'planned_quantity'      => 0,
                    'priority'              => 'Normal',
                    'status'                => 'In Cutting',
                ]);
            }

            // 2. Process Fabric Deductions from Rolls & Inventory Batches
            $totalFabricCutLength = 0;
            $totalFabricCost = 0.00;
            $consumedRollLogs = [];

            foreach ($this->selectedFabrics as $fab) {
                foreach ($fab['selected_rolls'] ?? [] as $rollId => $rData) {
                    $cutLen = floatval($rData['cut_length']);
                    if ($cutLen <= 0) continue;

                    $roll = InventoryBaleRoll::with('bale.batch.rawMaterial')->findOrFail($rollId);
                    $roll->deductLength($cutLen);

                    $invBatch = $roll->bale?->batch;
                    if ($invBatch) {
                        $invBatch->deductQuantity($cutLen);
                        $rate = (float) ($invBatch->purchase_rate ?: $invBatch->unit_cost);
                        $cost = round($cutLen * $rate, 2);

                        $totalFabricCutLength += $cutLen;
                        $totalFabricCost += $cost;

                        $consumedRollLogs[] = [
                            'inventory_batch_id'     => $invBatch->id,
                            'inventory_bale_roll_id' => $roll->id,
                            'roll_number'            => $roll->roll_number,
                            'bale_number'            => $roll->bale?->bale_number,
                            'quantity'               => $cutLen,
                            'rate'                   => $rate,
                            'cost'                   => $cost,
                            'products'               => $rData['products'] ?? [],
                        ];

                        InventoryBatchLogger::log(
                            $invBatch->id,
                            'consumed',
                            $cutLen,
                            null,
                            "Cut {$cutLen}m from {$roll->bale?->bale_number} ({$roll->roll_number}) in Shared Cutting Stage for Batch {$batch->batch_code}"
                        );
                    }
                }
            }

            // 3. Collect Output Allocations & Spawn Individual Jobs per Product/Pattern
            $uniqueAllocations = $this->uniqueAllocatedProducts;
            $totalBatchTargetQty = array_sum(array_column($uniqueAllocations, 'total_quantity'));
            $createdJobCodes = [];

            foreach ($uniqueAllocations as $alloc) {
                $pId = $alloc['manufacturing_product_id'];
                $patId = $alloc['pattern_id'] ?: null;
                $targetQty = $alloc['total_quantity'];

                $product = ManufacturingProduct::find($pId);
                $pattern = $patId ? ManufacturingProductPattern::with('tasks')->find($patId) : null;

                $shareRatio = $totalBatchTargetQty > 0 ? ($targetQty / $totalBatchTargetQty) : (1 / count($uniqueAllocations));

                // Generate Job Code
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
                    'manufacturing_product_id' => $pId,
                    'pattern_id'               => $patId,
                    'supervisor_id'            => auth()->id() ?: 1,
                    'factory_supervisor_id'    => $this->supervisor_id,
                    'job_date'                 => $batch->batch_date ?? now()->format('Y-m-d'),
                    'target_quantity'          => $targetQty,
                    'status'                   => 'in_progress',
                    'notes'                    => "Job for {$product?->name} ({$alloc['pattern_name']}) under Batch {$batch->batch_code}",
                ]);

                // Create Routing Task Stage Executions
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
                    $isCuttingStage = ($this->cutting_task_id && $task->id == $this->cutting_task_id) || ($idx === 0);

                    JobStageExecution::create([
                        'production_job_id'  => $job->id,
                        'task_id'           => $task->id,
                        'sequence_number'    => $idx + 1,
                        'target_quantity'    => $targetQty,
                        'completed_quantity' => $isCuttingStage ? $targetQty : 0,
                        'status'             => $isCuttingStage ? 'completed' : ($idx === 1 ? 'in_progress' : 'pending'),
                        'started_at'         => $idx <= 1 ? now() : null,
                        'completed_at'       => $isCuttingStage ? now() : null,
                    ]);
                }

                // Record Fabric Material Consumptions for this Job
                foreach ($consumedRollLogs as $cLog) {
                    $jobShareQty = round($cLog['quantity'] * $shareRatio, 4);
                    $jobShareCost = round($cLog['cost'] * $shareRatio, 2);

                    JobMaterialConsumption::create([
                        'job_code'               => $job->job_code,
                        'production_job_id'       => $job->id,
                        'inventory_batch_id'     => $cLog['inventory_batch_id'],
                        'inventory_bale_roll_id' => $cLog['inventory_bale_roll_id'],
                        'task_id'                => $this->cutting_task_id,
                        'quantity_consumed'      => $jobShareQty,
                        'unit_cost'              => $cLog['rate'],
                        'total_cost'             => $jobShareCost,
                        'consumed_length'        => $jobShareQty,
                        'total_fabric_cost'      => $jobShareCost,
                    ]);
                }

                // Record Labor Allocations for this Job
                $laborGroup = $this->laborAllocations[$alloc['key']] ?? null;
                if ($laborGroup && !empty($laborGroup['workers'])) {
                    foreach ($laborGroup['workers'] as $wItem) {
                        $wQty = intval($wItem['quantity'] ?? 0);
                        $wLaborId = $wItem['labor_id'] ?? null;
                        if (!$wLaborId || $wQty <= 0) continue;

                        $bRate = floatval($wItem['base_rate'] ?? 15.00);
                        $bnRate = floatval($wItem['bonus_rate'] ?? 0.00);
                        $totalRate = $bRate + $bnRate;
                        $calculatedWage = round($totalRate * $wQty, 2);

                        JobLaborAllocation::create([
                            'production_batch_id'      => $batch->batch_code,
                            'job_id'                   => $job->job_code,
                            'labor_id'                 => $wLaborId,
                            'manufacturing_product_id' => $pId,
                            'task_id'                  => $this->cutting_task_id,
                            'rate_type'                => 'piece_rate',
                            'base_rate'                => $bRate,
                            'bonus_rate'               => $bnRate,
                            'quantity_processed'       => $wQty,
                            'calculated_wage'          => $calculatedWage,
                            'status'                   => 'approved',
                        ]);
                    }
                }

                // Record Job Output Item for Cutting Stage
                JobProductionOutput::create([
                    'job_code'                 => $job->job_code,
                    'production_job_id'        => $job->id,
                    'manufacturing_product_id' => $pId,
                    'task_id'                  => $this->cutting_task_id,
                    'quantity_produced'        => $targetQty,
                ]);

                $createdJobCodes[] = $job->job_code;
            }

            // Update Batch Status and Planned Qty
            $firstPatternId = !empty($uniqueAllocations[0]['pattern_id']) ? $uniqueAllocations[0]['pattern_id'] : null;
            $batch->update([
                'status'                   => 'In Progress',
                'planned_quantity'         => $totalBatchTargetQty,
                'manufacturing_product_id' => $uniqueAllocations[0]['manufacturing_product_id'] ?? null,
                'pattern_id'               => $firstPatternId,
            ]);

            session()->flash('toast', [
                'message' => "Shared Cutting Stage completed for Batch {$batch->batch_code}! Created " . count($createdJobCodes) . " Production Job(s) with Cutting COMPLETED: " . implode(', ', $createdJobCodes),
                'type'    => 'success'
            ]);

            return $batch->batch_code;
        });

        return redirect()->route('admin.production.batches.jobs', $batchCode);
    }

    public function render()
    {
        $fabricMaterials = RawMaterial::active()
            ->fabricsOnly()
            ->orderBy('name')
            ->get();

        $manufacturingProducts = ManufacturingProduct::active()->orderBy('name')->get();
        $supervisors = FactorySupervisor::active()->orderBy('name')->get();
        $labors = Labor::active()->orderBy('name')->get();
        $tasks = Task::where('status', true)->orderBy('name')->get();

        return view('livewire.factory.cutting-stage-wizard', [
            'fabricMaterials'       => $fabricMaterials,
            'manufacturingProducts' => $manufacturingProducts,
            'supervisors'           => $supervisors,
            'labors'                => $labors,
            'tasks'                 => $tasks,
        ])->title('Shared Cutting Stage Wizard');
    }
}
