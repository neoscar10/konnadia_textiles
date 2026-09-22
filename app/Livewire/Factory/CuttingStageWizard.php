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

    // Global Search by Bale Number / Batch Number
    public string $globalSearch = '';

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
    public array $baleRollUnits = [];
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
            'search' => '',
            'selected_rolls' => [],
        ];
    }

    public function removeFabricRow(int $index)
    {
        unset($this->selectedFabrics[$index]);
        $this->selectedFabrics = array_values($this->selectedFabrics);
    }

    public function computeMaxPcsForRollProduct($rollId, float $cutLengthMeters, int $productId, ?int $patternId): int
    {
        if ($cutLengthMeters <= 0 || !$productId) return 0;

        $product = ManufacturingProduct::find($productId);
        if (!$product) return 0;

        $pattern = $patternId ? ManufacturingProductPattern::find($patternId) : null;
        $roll = InventoryBaleRoll::with(['fabricWidth.unitModel', 'rawMaterial.unitModel', 'bale.batch.rawMaterial.unitModel'])->find($rollId);
        $rollContext = $roll ?? $product;

        $pieceReqLen = FabricCuttingAreaService::resolvePatternFabricLength($product, $rollContext, $patternId);
        if ($pieceReqLen > 0) {
            return max(1, (int) floor($cutLengthMeters / $pieceReqLen));
        }

        $pieceAreaM2 = FabricCuttingAreaService::calculateProductPatternAreaM2($product, $pattern, $rollContext);
        $cutAreaM2 = FabricCuttingAreaService::calculateCutArea($cutLengthMeters, $rollContext);
        if ($pieceAreaM2 > 0 && $cutAreaM2 > 0) {
            return max(1, (int) floor($cutAreaM2 / $pieceAreaM2));
        }

        return 1;
    }

    public function selectSearchedBaleOrBatch($baleId = null, $batchId = null, int $fabricIndex = 0)
    {
        $this->resetErrorBag();
        $bale = $baleId ? InventoryBale::with('batch.rawMaterial')->find($baleId) : null;
        $batch = $batchId ? InventoryBatch::with('rawMaterial')->find($batchId) : ($bale?->batch);

        if (!$batch || !$batch->raw_material_id) {
            return;
        }

        if (!isset($this->selectedFabrics[$fabricIndex])) {
            $fabricIndex = 0;
            if (empty($this->selectedFabrics)) {
                $this->addFabricRow();
            }
        }

        $this->selectedFabrics[$fabricIndex]['raw_material_id'] = (string) $batch->raw_material_id;
        $this->selectedFabrics[$fabricIndex]['inventory_batch_id'] = (string) $batch->id;

        $this->autoEnsureBalesAndSelect($fabricIndex, $batch);

        if ($bale) {
            $this->selectedFabrics[$fabricIndex]['inventory_bale_id'] = (string) $bale->id;
        }

        $this->selectedFabrics[$fabricIndex]['search'] = '';
        $this->globalSearch = '';
        $this->dispatch('toast', message: "Auto-filled " . ($bale ? "Bale {$bale->bale_number}" : "Batch {$batch->batch_number}") . " for Fabric Item #" . ($fabricIndex + 1), type: 'success');
    }

    public function getMatchingSearchResults(?string $term = null)
    {
        $term = trim($term ?? $this->globalSearch);
        if (strlen($term) < 2) {
            return collect();
        }

        $bales = InventoryBale::with(['batch.rawMaterial'])
            ->where('status', '!=', 'depleted')
            ->where(function ($q) use ($term) {
                $q->where('bale_number', 'like', "%{$term}%")
                  ->orWhereHas('batch', fn($b) => $b->where('batch_number', 'like', "%{$term}%")->orWhereHas('rawMaterial', fn($m) => $m->where('name', 'like', "%{$term}%")->orWhere('code', 'like', "%{$term}%")));
            })
            ->take(10)
            ->get()
            ->map(fn($b) => [
                'type' => 'bale',
                'bale_id' => $b->id,
                'batch_id' => $b->inventory_batch_id,
                'title' => "Bale {$b->bale_number}",
                'subtitle' => "Batch {$b->batch?->batch_number} · {$b->batch?->rawMaterial?->name} (Bal: {$b->current_balance_length}m)",
            ]);

        $batches = InventoryBatch::with(['rawMaterial'])
            ->where('balance_quantity', '>', 0)
            ->where('batch_number', 'like', "%{$term}%")
            ->take(10)
            ->get()
            ->map(fn($b) => [
                'type' => 'batch',
                'bale_id' => null,
                'batch_id' => $b->id,
                'title' => "Batch {$b->batch_number}",
                'subtitle' => "{$b->rawMaterial?->name} (Bal: {$b->balance_quantity} {$b->unit})",
            ]);

        return $bales->concat($batches)->take(10);
    }

    public function getMatchingSearchResultsProperty()
    {
        return $this->getMatchingSearchResults();
    }

    public function getAvailableFabricsForBaleOrBatch($baleId = null, $batchId = null)
    {
        $materialIds = collect();

        if (!empty($baleId)) {
            $bale = InventoryBale::with(['rolls.rawMaterial', 'batch.rawMaterial'])->find($baleId);
            if ($bale) {
                if (!empty($bale->raw_material_id)) {
                    $materialIds->push($bale->raw_material_id);
                }
                if ($bale->batch?->raw_material_id) {
                    $materialIds->push($bale->batch->raw_material_id);
                }
                foreach ($bale->rolls as $roll) {
                    if (!empty($roll->raw_material_id)) {
                        $materialIds->push($roll->raw_material_id);
                    }
                }
            }
        } elseif (!empty($batchId)) {
            $batch = InventoryBatch::with(['rawMaterial', 'bales.rolls.rawMaterial'])->find($batchId);
            if ($batch) {
                if (!empty($batch->raw_material_id)) {
                    $materialIds->push($batch->raw_material_id);
                }
                foreach ($batch->bales as $bale) {
                    if (!empty($bale->raw_material_id)) {
                        $materialIds->push($bale->raw_material_id);
                    }
                    foreach ($bale->rolls as $roll) {
                        if (!empty($roll->raw_material_id)) {
                            $materialIds->push($roll->raw_material_id);
                        }
                    }
                }
            }
        }

        $uniqueIds = $materialIds->filter()->unique()->values()->toArray();

        if (!empty($uniqueIds)) {
            return RawMaterial::whereIn('id', $uniqueIds)->orderBy('name')->get();
        }

        // Fallback if no bale or batch selected yet
        $fabrics = RawMaterial::fabricsOnly()->active()->orderBy('name')->get();
        if ($fabrics->isEmpty()) {
            $fabrics = RawMaterial::active()->orderBy('name')->get();
        }

        return $fabrics;
    }

    public function toggleRollSelection(int $fabricIndex, int $rollId)
    {
        $this->resetErrorBag();
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

            $units = $this->getAvailableUnitsForMaterial($roll->raw_material_id ?: $roll->bale?->batch?->raw_material_id);
            $rollMatObj = RawMaterial::find($roll->raw_material_id ?: $roll->bale?->batch?->raw_material_id);
            $defaultUnitId = (string) ($rollMatObj?->unit_id ?? $units->firstWhere('short_code', 'M')?->id ?? $units->first()?->id ?? '');

            $maxMeters = (float) $roll->current_balance_length;
            $maxInSelectedUnit = $this->convertLengthFromMeters($maxMeters, $defaultUnitId);

            $autoQty = ($firstProd && $maxMeters > 0) ? $this->computeMaxPcsForRollProduct($roll->id, $maxMeters, $firstProd->id, $firstPattern?->id) : 20;

            $this->selectedFabrics[$fabricIndex]['selected_rolls'][$rollId] = [
                'roll_id'                     => $roll->id,
                'roll_number'                 => $roll->roll_number,
                'selected_unit_id'            => $defaultUnitId,
                'max_length'                  => $maxMeters, // always base meters
                'max_length_in_selected_unit' => round($maxInSelectedUnit, 2),
                'cut_length_input'            => round($maxInSelectedUnit, 2), // input in selected unit
                'cut_length'                  => $maxMeters, // converted to meters
                'products'                    => [
                    [
                        'manufacturing_product_id' => $firstProd?->id,
                        'pattern_id'               => $firstPattern?->id,
                        'planned_quantity'         => $autoQty,
                    ]
                ],
            ];
        }
    }

    public function convertLengthToMeters(float $val, ?string $unitId): float
    {
        if ($val <= 0) return 0.0;
        if (empty($unitId)) return $val;
        $unitModel = \App\Models\Unit::find($unitId);
        if ($unitModel) {
            return (float) $unitModel->toBaseQuantity($val);
        }
        return $val;
    }

    public function convertLengthFromMeters(float $metersVal, ?string $unitId): float
    {
        if ($metersVal <= 0) return 0.0;
        if (empty($unitId)) return $metersVal;
        $unitModel = \App\Models\Unit::find($unitId);
        if ($unitModel) {
            return (float) $unitModel->fromBaseQuantity($metersVal);
        }
        return $metersVal;
    }

    public function updateRollUnit(int $fabricIndex, int $rollId, string $newUnitId)
    {
        if (isset($this->selectedFabrics[$fabricIndex]['selected_rolls'][$rollId])) {
            $this->selectedFabrics[$fabricIndex]['selected_rolls'][$rollId]['selected_unit_id'] = $newUnitId;

            $maxMeters = (float) ($this->selectedFabrics[$fabricIndex]['selected_rolls'][$rollId]['max_length'] ?? 0);
            $maxInNewUnit = $this->convertLengthFromMeters($maxMeters, $newUnitId);
            $this->selectedFabrics[$fabricIndex]['selected_rolls'][$rollId]['max_length_in_selected_unit'] = round($maxInNewUnit, 2);

            // Re-convert cut_length_input to new unit using current base meters cut_length
            $currentCutMeters = (float) ($this->selectedFabrics[$fabricIndex]['selected_rolls'][$rollId]['cut_length'] ?? $maxMeters);
            $newCutInput = $this->convertLengthFromMeters($currentCutMeters, $newUnitId);
            $this->selectedFabrics[$fabricIndex]['selected_rolls'][$rollId]['cut_length_input'] = round($newCutInput, 2);

            // Live recalculate planned_quantity for products on roll
            if (isset($this->selectedFabrics[$fabricIndex]['selected_rolls'][$rollId]['products']) && is_array($this->selectedFabrics[$fabricIndex]['selected_rolls'][$rollId]['products'])) {
                foreach ($this->selectedFabrics[$fabricIndex]['selected_rolls'][$rollId]['products'] as $pIdx => $pItem) {
                    $pId = intval($pItem['manufacturing_product_id'] ?? 0);
                    $patId = intval($pItem['pattern_id'] ?? 0);
                    if ($pId && $currentCutMeters > 0) {
                        $maxPcs = $this->computeMaxPcsForRollProduct($rollId, $currentCutMeters, $pId, $patId);
                        $this->selectedFabrics[$fabricIndex]['selected_rolls'][$rollId]['products'][$pIdx]['planned_quantity'] = $maxPcs;
                    }
                }
            }
        }
    }

    public function updatedSelectedFabricsCutLengthInput(int $fabricIndex, int $rollId, $val)
    {
        if (isset($this->selectedFabrics[$fabricIndex]['selected_rolls'][$rollId])) {
            $unitId = $this->selectedFabrics[$fabricIndex]['selected_rolls'][$rollId]['selected_unit_id'] ?? null;
            $inputVal = floatval($val);
            $metersVal = $this->convertLengthToMeters($inputVal, $unitId);
            $this->selectedFabrics[$fabricIndex]['selected_rolls'][$rollId]['cut_length'] = $metersVal;

            if (isset($this->selectedFabrics[$fabricIndex]['selected_rolls'][$rollId]['products']) && is_array($this->selectedFabrics[$fabricIndex]['selected_rolls'][$rollId]['products'])) {
                foreach ($this->selectedFabrics[$fabricIndex]['selected_rolls'][$rollId]['products'] as $pIdx => $pItem) {
                    $pId = intval($pItem['manufacturing_product_id'] ?? 0);
                    $patId = intval($pItem['pattern_id'] ?? 0);
                    if ($pId && $metersVal > 0) {
                        $maxPcs = $this->computeMaxPcsForRollProduct($rollId, $metersVal, $pId, $patId);
                        $this->selectedFabrics[$fabricIndex]['selected_rolls'][$rollId]['products'][$pIdx]['planned_quantity'] = $maxPcs;
                    } elseif ($metersVal <= 0) {
                        $this->selectedFabrics[$fabricIndex]['selected_rolls'][$rollId]['products'][$pIdx]['planned_quantity'] = 0;
                    }
                }
            }
        }
    }

    public function setFullRollCut(int $fabricIndex, int $rollId)
    {
        if (isset($this->selectedFabrics[$fabricIndex]['selected_rolls'][$rollId])) {
            $maxMeters = (float) $this->selectedFabrics[$fabricIndex]['selected_rolls'][$rollId]['max_length'];
            $unitId = $this->selectedFabrics[$fabricIndex]['selected_rolls'][$rollId]['selected_unit_id'] ?? null;
            $maxInSelectedUnit = $this->convertLengthFromMeters($maxMeters, $unitId);

            $this->selectedFabrics[$fabricIndex]['selected_rolls'][$rollId]['cut_length_input'] = round($maxInSelectedUnit, 2);
            $this->selectedFabrics[$fabricIndex]['selected_rolls'][$rollId]['cut_length']       = $maxMeters;

            if (isset($this->selectedFabrics[$fabricIndex]['selected_rolls'][$rollId]['products']) && is_array($this->selectedFabrics[$fabricIndex]['selected_rolls'][$rollId]['products'])) {
                foreach ($this->selectedFabrics[$fabricIndex]['selected_rolls'][$rollId]['products'] as $pIdx => $pItem) {
                    $pId = intval($pItem['manufacturing_product_id'] ?? 0);
                    $patId = intval($pItem['pattern_id'] ?? 0);
                    if ($pId && $maxMeters > 0) {
                        $maxPcs = $this->computeMaxPcsForRollProduct($rollId, $maxMeters, $pId, $patId);
                        $this->selectedFabrics[$fabricIndex]['selected_rolls'][$rollId]['products'][$pIdx]['planned_quantity'] = $maxPcs;
                    }
                }
            }
        }
    }

    public function addProductToRoll(int $fabricIndex, int $rollId)
    {
        $this->resetErrorBag();
        if (isset($this->selectedFabrics[$fabricIndex]['selected_rolls'][$rollId])) {
            $rData = $this->selectedFabrics[$fabricIndex]['selected_rolls'][$rollId];
            $cutLen = floatval($rData['cut_length'] ?? 0);

            $firstProd = ManufacturingProduct::active()->first();
            $firstPattern = null;
            if ($firstProd) {
                $patterns = ManufacturingProductPattern::where('manufacturing_product_id', $firstProd->id)->get();
                $firstPattern = $patterns->firstWhere('is_default', true) ?? $patterns->first();
            }

            $autoQty = ($firstProd && $cutLen > 0) ? $this->computeMaxPcsForRollProduct($rollId, $cutLen, $firstProd->id, $firstPattern?->id) : 20;

            $this->selectedFabrics[$fabricIndex]['selected_rolls'][$rollId]['products'][] = [
                'manufacturing_product_id' => $firstProd?->id,
                'pattern_id'               => $firstPattern?->id,
                'planned_quantity'         => $autoQty,
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
        $this->resetErrorBag();

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

                $baleId = $this->selectedFabrics[$index]['inventory_bale_id'] ?? null;
                $batchId = $this->selectedFabrics[$index]['inventory_batch_id'] ?? null;
                if ($baleId || $batchId) {
                    $availFabrics = $this->getAvailableFabricsForBaleOrBatch($baleId, $batchId);
                    if ($availFabrics->isNotEmpty()) {
                        $currentMatId = (int) ($this->selectedFabrics[$index]['raw_material_id'] ?? 0);
                        if (!$currentMatId || !$availFabrics->contains('id', $currentMatId)) {
                            $this->selectedFabrics[$index]['raw_material_id'] = (string) $availFabrics->first()->id;
                        }
                    }
                }
            } elseif ($field === 'selected_rolls') {
                // E.g. 0.selected_rolls.12.cut_length_input or 0.selected_rolls.12.selected_unit_id
                $rollId = (int) ($parts[2] ?? 0);
                $subField = $parts[3] ?? '';

                if ($rollId && isset($this->selectedFabrics[$index]['selected_rolls'][$rollId])) {
                    if ($subField === 'cut_length_input') {
                        $unitId = $this->selectedFabrics[$index]['selected_rolls'][$rollId]['selected_unit_id'] ?? null;
                        $inputVal = floatval($value);
                        $metersVal = $this->convertLengthToMeters($inputVal, $unitId);
                        $this->selectedFabrics[$index]['selected_rolls'][$rollId]['cut_length'] = $metersVal;

                        // Auto-recalculate planned_quantity for products on this roll
                        if (isset($this->selectedFabrics[$index]['selected_rolls'][$rollId]['products']) && is_array($this->selectedFabrics[$index]['selected_rolls'][$rollId]['products'])) {
                            foreach ($this->selectedFabrics[$index]['selected_rolls'][$rollId]['products'] as $pIdx => $pItem) {
                                $pId = intval($pItem['manufacturing_product_id'] ?? 0);
                                $patId = intval($pItem['pattern_id'] ?? 0);
                                if ($pId && $metersVal > 0) {
                                    $maxPcs = $this->computeMaxPcsForRollProduct($rollId, $metersVal, $pId, $patId);
                                    $this->selectedFabrics[$index]['selected_rolls'][$rollId]['products'][$pIdx]['planned_quantity'] = $maxPcs;
                                } elseif ($metersVal <= 0) {
                                    $this->selectedFabrics[$index]['selected_rolls'][$rollId]['products'][$pIdx]['planned_quantity'] = 0;
                                }
                            }
                        }
                    } elseif ($subField === 'selected_unit_id') {
                        $this->updateRollUnit($index, $rollId, (string)$value);
                    } elseif ($subField === 'products') {
                        $pIdx = intval($parts[4] ?? 0);
                        $productProp = $parts[5] ?? '';
                        if (isset($this->selectedFabrics[$index]['selected_rolls'][$rollId]['products'][$pIdx])) {
                            if ($productProp === 'manufacturing_product_id') {
                                $prodId = intval($value);
                                if ($prodId) {
                                    $patterns = ManufacturingProductPattern::where('manufacturing_product_id', $prodId)->get();
                                    $defaultPattern = $patterns->firstWhere('is_default', true) ?? $patterns->first();
                                    $this->selectedFabrics[$index]['selected_rolls'][$rollId]['products'][$pIdx]['pattern_id'] = $defaultPattern?->id;

                                    $cLen = floatval($this->selectedFabrics[$index]['selected_rolls'][$rollId]['cut_length'] ?? 0);
                                    if ($cLen > 0) {
                                        $this->selectedFabrics[$index]['selected_rolls'][$rollId]['products'][$pIdx]['planned_quantity'] = $this->computeMaxPcsForRollProduct($rollId, $cLen, $prodId, $defaultPattern?->id);
                                    }
                                }
                            } elseif ($productProp === 'pattern_id') {
                                $prodId = intval($this->selectedFabrics[$index]['selected_rolls'][$rollId]['products'][$pIdx]['manufacturing_product_id'] ?? 0);
                                $patId = intval($value);
                                $cLen = floatval($this->selectedFabrics[$index]['selected_rolls'][$rollId]['cut_length'] ?? 0);
                                if ($prodId && $cLen > 0) {
                                    $this->selectedFabrics[$index]['selected_rolls'][$rollId]['products'][$pIdx]['planned_quantity'] = $this->computeMaxPcsForRollProduct($rollId, $cLen, $prodId, $patId);
                                }
                            }
                        }
                    }
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

    public function getAvailableWidthsForMaterial($rawMaterialId)
    {
        if (!$rawMaterialId) return collect();
        $mat = RawMaterial::with('fabricWidths.unitModel')->find($rawMaterialId);
        return $mat ? $mat->available_widths : collect();
    }

    public function getAvailableUnitsForMaterial($rawMaterialId)
    {
        if (!$rawMaterialId) {
            $lengthGroup = \App\Models\UnitGroup::where('code', 'LENGTH')->first();
            return $lengthGroup ? $lengthGroup->activeUnits : \App\Models\Unit::where('is_active', true)->get();
        }
        $mat = RawMaterial::with('unitGroup.activeUnits', 'unitModel')->find($rawMaterialId);
        if ($mat && $mat->unitGroup && $mat->unitGroup->activeUnits->isNotEmpty()) {
            return $mat->unitGroup->activeUnits;
        }
        $lengthGroup = \App\Models\UnitGroup::where('code', 'LENGTH')->first();
        return $lengthGroup ? $lengthGroup->activeUnits : \App\Models\Unit::where('is_active', true)->get();
    }

    public function getRollConvertedLengthInMeters(int $index): float
    {
        $lenVal = (float) ($this->baleRollLengths[$index] ?? 0);
        if ($lenVal <= 0) return 0.0;

        $unitId = $this->baleRollUnits[$index] ?? null;
        if ($unitId) {
            $unitModel = \App\Models\Unit::find($unitId);
            if ($unitModel) {
                return (float) $unitModel->toBaseQuantity($lenVal);
            }
        }

        return $lenVal;
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
        $this->baleRollUnits = [];
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
            $this->baleRollUnits         = [];
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
                $matId = (string) $defaultMatId;
                $this->baleRollLengths[$i]       = '';
                $this->baleRollMaterials[$i]     = $matId;

                $widths = $this->getAvailableWidthsForMaterial($matId);
                $firstWidth = $widths->first();
                $wVal = $firstWidth ? ($firstWidth->id ?? ($firstWidth->value ?? '')) : '';
                $this->baleRollWidths[$i]        = (string) $wVal;

                $units = $this->getAvailableUnitsForMaterial($matId);
                $matObj = $matId ? RawMaterial::find($matId) : null;
                $defaultUnitId = $matObj?->unit_id ?? $units->firstWhere('short_code', 'M')?->id ?? $units->first()?->id ?? '';
                $this->baleRollUnits[$i]         = (string) $defaultUnitId;

                $this->baleRollDesignNumbers[$i] = $defaultDesign;
                $this->baleRollStockIds[$i]       = $defaultStock;
            }
        } else if ($currentCount > $count) {
            $this->baleRollLengths       = array_slice($this->baleRollLengths, 0, $count);
            $this->baleRollMaterials     = array_slice($this->baleRollMaterials, 0, $count);
            $this->baleRollWidths        = array_slice($this->baleRollWidths, 0, $count);
            $this->baleRollUnits         = array_slice($this->baleRollUnits, 0, $count);
            $this->baleRollDesignNumbers = array_slice($this->baleRollDesignNumbers, 0, $count);
            $this->baleRollStockIds       = array_slice($this->baleRollStockIds, 0, $count);
        }

        $this->checkBaleMismatchWarning();
    }

    public function updateRollMaterialWidthsAndUnits(int $index, int $matId)
    {
        if ($matId) {
            $widths = $this->getAvailableWidthsForMaterial($matId);
            $firstWidth = $widths->first();
            if ($firstWidth) {
                $wVal = $firstWidth->id ?? ($firstWidth->value ?? '');
                $this->baleRollWidths[$index] = (string) $wVal;
            } else {
                $this->baleRollWidths[$index] = '';
            }

            $units = $this->getAvailableUnitsForMaterial($matId);
            $matObj = RawMaterial::find($matId);
            $defaultUnitId = $matObj?->unit_id ?? $units->firstWhere('short_code', 'M')?->id ?? $units->first()?->id ?? '';
            $this->baleRollUnits[$index] = (string) $defaultUnitId;

            if (!empty($this->baleAllowedMaterials)) {
                foreach ($this->baleAllowedMaterials as $item) {
                    if ($item['id'] == $matId) {
                        $this->baleRollDesignNumbers[$index] = $item['design_number'] ?? '';
                        $this->baleRollStockIds[$index]       = $item['stock_id'] ?? '';
                        break;
                    }
                }
            }
        }

        $this->checkBaleMismatchWarning();
    }

    public function updated($property, $value = null)
    {
        if (str_starts_with($property, 'baleRollMaterials.')) {
            $index = (int) str_replace('baleRollMaterials.', '', $property);
            $this->updateRollMaterialWidthsAndUnits($index, (int) $value);
        }
    }

    public function updatedBaleRollMaterials($value, $key = null)
    {
        if (is_array($value)) {
            foreach ($value as $k => $v) {
                $this->updateRollMaterialWidthsAndUnits((int) $k, (int) $v);
            }
            return;
        }
        $index = (int) $key;
        $matId = (int) $value;
        $this->updateRollMaterialWidthsAndUnits($index, $matId);
    }

    public function updatedBaleRollUnits()
    {
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

        $filledIndices = array_filter(array_keys($this->baleRollLengths), fn($i) => isset($this->baleRollLengths[$i]) && $this->baleRollLengths[$i] !== '' && $this->baleRollLengths[$i] !== null);
        if (empty($filledIndices)) {
            $this->baleMismatchWarning = null;
            return;
        }

        $sumBaseMeters = 0.0;
        foreach ($filledIndices as $i) {
            $sumBaseMeters += $this->getRollConvertedLengthInMeters($i);
        }

        $sumBaseMeters = round($sumBaseMeters, 2);
        $declared = (float) $bale->declared_length;

        if (abs($sumBaseMeters - $declared) > 0.001) {
            $diff = round($sumBaseMeters - $declared, 2);
            $sign = $diff > 0 ? "+{$diff}" : "{$diff}";
            $this->baleMismatchWarning = "Warning: Total measured roll length ({$sumBaseMeters}m) differs from declared purchase bale length ({$declared}m) by {$sign}m. This measured length ({$sumBaseMeters}m) will override the declared length for material calculations.";
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
        $sumBaseMeters = 0.0;
        foreach (array_keys($this->baleRollLengths) as $i) {
            $sumBaseMeters += $this->getRollConvertedLengthInMeters($i);
        }
        $sumBaseMeters = round($sumBaseMeters, 2);
        $declared = (float) $bale->declared_length;

        if (abs($sumBaseMeters - $declared) > 0.001 && !$this->showMismatchConfirmationModal) {
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
            $lengthInBaseMeters = $this->getRollConvertedLengthInMeters($i);
            $rollData[] = [
                'length'          => (float) $lengthInBaseMeters,
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
        $this->dispatch('toast', message: "Bale {$bale->bale_number} opened with {$bale->roll_count} rolls! Measured length ({$result['total_recorded_length']}m) saved for stock calculations.", type: 'success');
    }

    public function getRollCutBreakdown(int $rollId, float $cutLength, $rawMaterialId = null, array $products = []): array
    {
        if ($cutLength <= 0) {
            return [];
        }

        $roll = InventoryBaleRoll::with(['fabricWidth.unitModel', 'rawMaterial.unitModel', 'bale.batch.rawMaterial.unitModel'])->find($rollId);
        $rawMaterial = $rawMaterialId ? RawMaterial::with(['unitModel', 'fabricWidths.unitModel'])->find($rawMaterialId) : ($roll?->rawMaterial ?? $roll?->bale?->batch?->rawMaterial);

        if (!$rawMaterial) {
            return [];
        }

        $purchaseRate = (float) ($roll?->bale?->batch?->unit_cost ?: ($roll?->bale?->batch?->purchase_rate ?: 0));

        $widthVal = 0.0;
        $widthUnitStr = 'IN';

        if ($roll && $roll->fabricWidth) {
            $fw = $roll->fabricWidth;
            $widthVal = (float) ($fw->value ?: $fw->width_inches ?: 0);
            $widthUnitStr = $fw->unitModel ? $fw->unitModel->short_code : ($fw->unit ?: 'IN');
        } elseif ($rawMaterial) {
            if ($rawMaterial->fabricWidths && $rawMaterial->fabricWidths->isNotEmpty()) {
                $fw = $rawMaterial->fabricWidths->first();
                $widthVal = (float) $fw->value;
                $widthUnitStr = $fw->unitModel ? $fw->unitModel->short_code : ($fw->unit ?: 'IN');
            } else {
                $widthVal = (float) ($rawMaterial->standard_width ?: 60);
                $widthUnitStr = $rawMaterial->width_unit ?: 'IN';
            }
        }

        if ($widthVal <= 0) {
            $widthVal = 60.0;
            $widthUnitStr = 'IN';
        }

        $widthMeters = FabricCuttingAreaService::convertToMeters($widthVal, $widthUnitStr);

        $rollContext = $roll ?? $rawMaterial;
        $cutAreaBase = FabricCuttingAreaService::calculateCutArea($cutLength, $rollContext);
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
            $rollContext = $roll ?? $rawMaterial;
            $pieceAreaM2 = FabricCuttingAreaService::calculateProductPatternAreaM2($product, $pattern, $rollContext);

            $itemUsedAreaBase = $pieceAreaM2 * $qty;
            $totalUsedAreaBase += $itemUsedAreaBase;

            $pieceReqLen = FabricCuttingAreaService::resolvePatternFabricLength($product, $rollContext, $patId);
            $itemReqLen = $pieceReqLen * $qty;
            $totalStandardReqLength += $itemReqLen;

            $dimDetails = FabricCuttingAreaService::formatProductPatternDimensions($product, $pattern, $rollContext);

            $maxPcs = $this->computeMaxPcsForRollProduct($rollId, $cutLength, $pId, $patId);

            $key = "{$pId}_{$patId}";
            $productDetails[$key] = [
                'product_id' => $product->id,
                'pattern_id' => $pattern?->id,
                'name' => $product->name,
                'pattern_name' => $pattern?->name ?? 'Standard',
                'piece_area_m2' => round($pieceAreaM2, 4),
                'quantity' => $qty,
                'max_pcs' => $maxPcs,
                'piece_req_length' => round($pieceReqLen, 2),
                'total_req_length' => round($itemReqLen, 2),
                'total_used_area_m2' => round($itemUsedAreaBase, 4),
                'dimensions_display' => $dimDetails['dimensions_display'],
                'length_display' => $dimDetails['length_display'],
                'width_display' => $dimDetails['width_display'],
                'is_configured' => $dimDetails['is_configured'] ?? true,
                'error_message' => $dimDetails['error_message'] ?? null,
            ];
        }

        $remainingAreaBase = max(0.0, $cutAreaBase - $totalUsedAreaBase);
        $isOverCapacity = $totalUsedAreaBase > ($cutAreaBase + 0.0001);

        $wastageLengthMeters = $widthMeters > 0 ? ($remainingAreaBase / $widthMeters) : 0.0;
        $wastageCost = round($wastageLengthMeters * $purchaseRate, 2);
        $usagePercentage = $cutAreaBase > 0 ? round(($totalUsedAreaBase / $cutAreaBase) * 100, 1) : 0;
        $wastagePercentage = $cutAreaBase > 0 ? round(($remainingAreaBase / $cutAreaBase) * 100, 1) : 0;

        $widthDisplay = FabricCuttingAreaService::formatSingleDimension($widthVal, $widthUnitStr);
        $cutLengthDisplay = FabricCuttingAreaService::formatSingleDimension($cutLength, 'Meters');
        $dimensionsDisplay = "Width: {$widthDisplay} · Length: {$cutLengthDisplay}";

        return [
            'cut_length' => round($cutLength, 2),
            'total_req_length' => round($totalStandardReqLength, 2),
            'cut_length_display' => $cutLengthDisplay,
            'roll_width_display' => $widthDisplay,
            'dimensions_display' => $dimensionsDisplay,
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
            $matId = $fab['raw_material_id'] ?? null;
            $rawMaterial = $matId ? RawMaterial::find($matId) : null;

            foreach ($fab['selected_rolls'] ?? [] as $rollId => $rData) {
                $cutLen = floatval($rData['cut_length'] ?? 0);
                if ($cutLen <= 0) continue;

                $roll = InventoryBaleRoll::with(['fabricWidth.unitModel', 'rawMaterial.fabricWidths.unitModel', 'bale.batch.rawMaterial.fabricWidths.unitModel'])->find($rollId);
                $rollContext = $roll ?? $rawMaterial;

                foreach ($rData['products'] ?? [] as $pItem) {
                    $pId = intval($pItem['manufacturing_product_id'] ?? 0);
                    $patId = intval($pItem['pattern_id'] ?? 0);
                    $qty = intval($pItem['planned_quantity'] ?? 0);

                    if (!$pId || $qty <= 0) continue;

                    $key = "{$pId}_{$patId}";
                    if (!isset($allocated[$key])) {
                        $product = ManufacturingProduct::find($pId);
                        $pattern = $patId ? ManufacturingProductPattern::find($patId) : null;
                        $dimDetails = FabricCuttingAreaService::formatProductPatternDimensions($product, $pattern, $rollContext);

                        $allocated[$key] = [
                            'key' => $key,
                            'manufacturing_product_id' => $pId,
                            'pattern_id' => $patId,
                            'product_name' => $product?->name ?? "Product #{$pId}",
                            'product_code' => $product?->code ?? '',
                            'pattern_name' => $pattern?->name ?? 'Default',
                            'dimensions_display' => $dimDetails['dimensions_display'],
                            'length_display' => $dimDetails['length_display'],
                            'width_display' => $dimDetails['width_display'],
                            'piece_area_m2' => $dimDetails['piece_area_m2'],
                            'total_quantity' => 0,
                            'total_product_area_m2' => 0.0,
                        ];
                    }
                    $allocated[$key]['total_quantity'] += $qty;
                    $allocated[$key]['total_product_area_m2'] += ($dimDetails['piece_area_m2'] * $qty);
                }
            }
        }

        // Calculate proportional wastage distribution across allocated products
        $totalAllocatedProductArea = array_sum(array_column($allocated, 'total_product_area_m2'));
        $breakdown = $this->fabricCuttingBreakdown;
        $totalWastageArea = floatval($breakdown['wastage_area_m2'] ?? 0);
        $totalWastageLength = floatval($breakdown['total_wastage_length'] ?? 0);
        $totalCutFabricCost = floatval($breakdown['total_fabric_cut_cost'] ?? 0);
        $totalCutArea = floatval($breakdown['cut_area_m2'] ?? 0);
        $totalWastageCost = $totalCutArea > 0 ? ($totalWastageArea / $totalCutArea) * $totalCutFabricCost : 0.0;

        foreach ($allocated as $k => $item) {
            $itemArea = floatval($item['total_product_area_m2']);
            $areaRatio = $totalAllocatedProductArea > 0 ? ($itemArea / $totalAllocatedProductArea) : 0.0;
            $allocatedWastageArea = $totalWastageArea * $areaRatio;
            $allocatedWastageLength = $totalWastageLength * $areaRatio;
            $allocatedWastageCost = $totalWastageCost * $areaRatio;
            $qty = max(1, intval($item['total_quantity']));
            $perPieceWastageCost = $allocatedWastageCost / $qty;
            $perPieceWastageArea = $allocatedWastageArea / $qty;
            $perPieceWastageLength = $allocatedWastageLength / $qty;

            $allocated[$k]['area_share_percentage'] = $totalAllocatedProductArea > 0 ? round($areaRatio * 100, 1) : 0.0;
            $allocated[$k]['allocated_wastage_area_m2'] = round($allocatedWastageArea, 4);
            $allocated[$k]['allocated_wastage_length'] = round($allocatedWastageLength, 2);
            $allocated[$k]['allocated_wastage_cost'] = round($allocatedWastageCost, 2);
            $allocated[$k]['per_piece_wastage_cost'] = round($perPieceWastageCost, 2);
            $allocated[$k]['per_piece_wastage_area_m2'] = round($perPieceWastageArea, 4);
            $allocated[$k]['per_piece_wastage_length'] = round($perPieceWastageLength, 2);
        }

        return array_values($allocated);
    }

    public function getFabricCuttingBreakdownProperty(): array
    {
        $totalCutAreaBase = 0.0;
        $totalCutLength = 0.0;
        $totalReqLength = 0.0;
        $totalWastageLength = 0.0;
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
                    $totalReqLength += ($bd['total_req_length'] ?? 0);
                    $totalWastageLength += ($bd['wastage_length'] ?? 0);
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
            'total_req_length' => round($totalReqLength, 2),
            'total_wastage_length' => round($totalWastageLength, 2),
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

    public function resolveCuttingFee(int $productId, ?int $patternId): float
    {
        $taskId = $this->cutting_task_id;

        // 1. Check pattern task routing pivot (manufacturing_pattern_tasks)
        if ($patternId && $taskId) {
            $patternPivotRate = DB::table('manufacturing_pattern_tasks')
                ->where('pattern_id', $patternId)
                ->where('task_id', $taskId)
                ->value('standard_labor_rate');

            if (!is_null($patternPivotRate) && (float) $patternPivotRate > 0) {
                return (float) $patternPivotRate;
            }
        }

        // 2. Check pattern model standard_labor_rate
        if ($patternId) {
            $patObj = ManufacturingProductPattern::find($patternId);
            if ($patObj && !is_null($patObj->standard_labor_rate) && (float) $patObj->standard_labor_rate > 0) {
                return (float) $patObj->standard_labor_rate;
            }
        }

        // 3. Check manufacturing_product_task pivot
        if ($productId && $taskId) {
            $pivotRate = DB::table('manufacturing_product_task')
                ->where('manufacturing_product_id', $productId)
                ->where('task_id', $taskId)
                ->value('standard_labor_rate');

            if (!is_null($pivotRate) && (float) $pivotRate > 0) {
                return (float) $pivotRate;
            }
        }

        // 4. Fall back to product standard_labor_rate
        if ($productId) {
            $product = ManufacturingProduct::find($productId);
            if ($product) {
                $rate = $product->getStandardLaborRateForTask($taskId);
                if (!is_null($rate) && (float) $rate > 0) {
                    return (float) $rate;
                }
            }
        }

        return 0.00;
    }

    public function resolveDefaultCutterId(): ?int
    {
        if ($this->batchModel && $this->batchModel->cutter_id) {
            return $this->batchModel->cutter_id;
        }
        $cuttingTask = $this->cutting_task_id ? Task::find($this->cutting_task_id) : null;
        if ($cuttingTask) {
            $eligible = $cuttingTask->getEligibleLabors();
            if ($eligible->isNotEmpty()) {
                return $eligible->first()->id;
            }
        }
        return Labor::active()->first()?->id;
    }

    protected function syncLaborAndOutputs()
    {
        $unique = $this->uniqueAllocatedProducts;
        $defaultCutterId = $this->resolveDefaultCutterId();

        $newLaborAllocations = [];
        $newOutputs = [];

        foreach ($unique as $item) {
            $key = $item['key'];
            $qty = intval($item['total_quantity']);
            $pId = $item['manufacturing_product_id'];
            $patId = $item['pattern_id'];

            $exactCuttingFee = $this->resolveCuttingFee($pId, $patId);
            $calculatedWage = round($exactCuttingFee * $qty, 2);

            $newLaborAllocations[$key] = [
                'product_key' => $key,
                'manufacturing_product_id' => $pId,
                'pattern_id' => $patId,
                'product_name' => $item['product_name'],
                'pattern_name' => $item['pattern_name'],
                'dimensions_display' => $item['dimensions_display'] ?? '',
                'total_cut_quantity' => $qty,
                'cutting_fee' => $exactCuttingFee,
                'calculated_wage' => $calculatedWage,
                'workers' => [
                    [
                        'labor_id' => $defaultCutterId,
                        'quantity' => $qty,
                        'base_rate' => $exactCuttingFee,
                        'bonus_rate' => 0.00,
                    ]
                ]
            ];

            $existingOutput = collect($this->outputItems)->firstWhere('product_key', $key);
            $newOutputs[] = [
                'product_key' => $key,
                'manufacturing_product_id' => $pId,
                'pattern_id' => $patId,
                'product_name' => $item['product_name'],
                'pattern_name' => $item['pattern_name'],
                'dimensions_display' => $item['dimensions_display'] ?? '',
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
            $pId = $this->laborAllocations[$productKey]['manufacturing_product_id'] ?? 0;
            $patId = $this->laborAllocations[$productKey]['pattern_id'] ?? null;
            $fee = $this->resolveCuttingFee($pId, $patId);

            $this->laborAllocations[$productKey]['workers'][] = [
                'labor_id' => $this->resolveDefaultCutterId(),
                'quantity' => $remQty,
                'base_rate' => $fee,
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
        $this->currentStep = $step;
    }

    protected function validateStep1(): bool
    {
        $this->resetErrorBag();
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

    public function submitCuttingStage()
    {
        if (!$this->validateStep1()) {
            return;
        }

        $batchCode = DB::transaction(function () {
            // 1. Resolve or Create ProductionBatch
            $batch = $this->batchModel;
            if (!$batch && !empty($this->batchCode)) {
                $batch = ProductionBatch::where('batch_code', $this->batchCode)->first();
            }

            if (!$batch) {
                $batchCodeStr = ProductionBatch::generateNextBatchCode();

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
                            'pattern_id'               => $patId,
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
        $cuttingTask = $this->cutting_task_id ? Task::find($this->cutting_task_id) : null;
        $labors = $cuttingTask ? $cuttingTask->getEligibleLabors() : Labor::active()->orderBy('name')->get();
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
