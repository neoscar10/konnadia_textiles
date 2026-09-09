<?php

namespace App\Livewire\Factory;

use App\Models\RawMaterial;
use App\Models\RawMaterialCategory;
use App\Models\InventoryBatch;
use App\Models\Supplier;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Services\InventoryBatchLogger;

#[Layout('components.admin.layout')]
class RawMaterialPurchaseEntry extends Component
{
    use WithFileUploads;

    public ?int $supplier_id = null;
    public string $supplier_name = '';
    public string $purchase_date = '';
    public string $invoice_number = '';
    public string $lot_number = '';
    
    // Category First selection
    public ?int $raw_material_category_id = null;
    public ?int $raw_material_id = null; // Used for non-fabric single material selection

    public $quantity_received = '';
    public $purchase_rate = '';
    public $total_amount = 0.00;

    // Fabric Bale specific properties
    public $num_bales = 1;
    public $declared_bale_length = '';
    public bool $all_bales_equal_length = true;
    public array $individual_bale_lengths = [];

    // Detailed per-bale items (Rich Bale Metadata with per-bale Raw Material)
    public array $bale_items = [];

    // GST properties
    public bool $gst_included = true;
    public $gst_percent = 18.0;

    // View helper variables
    public ?string $unitType = null; // 'length_based' or 'other'
    public ?string $unitName = null; // e.g. Meters, Pieces

    public function mount()
    {
        $this->purchase_date = Carbon::now()->format('Y-m-d');
        
        // Auto-generate default Lot Number
        $nextLotNum = InventoryBatch::count() + 1;
        $this->lot_number = 'LOT-' . Carbon::now()->year . '-' . str_pad((string) $nextLotNum, 3, '0', STR_PAD_LEFT);
        
        // Auto select Fabric category if available by default
        $fabricCat = RawMaterialCategory::where('code', 'CAT-FAB')->orWhere('name', 'like', '%Fabric%')->first();
        if ($fabricCat) {
            $this->raw_material_category_id = $fabricCat->id;
            $this->updatedRawMaterialCategoryId($fabricCat->id);
        } else {
            $firstCat = RawMaterialCategory::active()->first();
            if ($firstCat) {
                $this->raw_material_category_id = $firstCat->id;
                $this->updatedRawMaterialCategoryId($firstCat->id);
            }
        }

        $this->individual_bale_lengths = [''];
    }

    public function updatedRawMaterialCategoryId($value)
    {
        if ($value) {
            $category = RawMaterialCategory::find($value);
            if ($category) {
                $unitTypeVal = is_object($category->unit_type) ? $category->unit_type->value : (string) $category->unit_type;
                if ($unitTypeVal === 'length_based' || $category->code === 'CAT-FAB' || stripos($category->name, 'Fabric') !== false) {
                    $this->unitType = 'length_based';
                    $this->unitName = 'Meters';
                } else {
                    $this->unitType = $unitTypeVal ?: 'other';
                    $this->unitName = $category->default_unit ?? 'Pieces';
                }

                $availableMaterials = RawMaterial::where('raw_material_category_id', $value)->active()->orderBy('name')->get();
                $defaultMaterial = $availableMaterials->first();

                if ($defaultMaterial) {
                    $this->raw_material_id = $defaultMaterial->id;
                    $this->unitName = $defaultMaterial->unit;
                } else {
                    $this->raw_material_id = null;
                }

                if ($this->unitType === 'length_based') {
                    $this->initBaleItems(max(1, intval($this->num_bales ?: 1)), $defaultMaterial?->id);
                }
            } else {
                $this->unitType = null;
                $this->unitName = null;
                $this->raw_material_id = null;
            }
        } else {
            $this->unitType = null;
            $this->unitName = null;
            $this->raw_material_id = null;
        }

        $this->recalculateTotal();
    }

    public function normalizeBaleItems()
    {
        if (empty($this->bale_items)) {
            return;
        }

        $first = reset($this->bale_items);
        if (is_array($first) && !isset($first['items'])) {
            // Convert 1D item array (e.g. from tests or legacy structure) into nested bale structure
            $newBales = [];
            foreach ($this->bale_items as $i => $item) {
                $baleNum = $item['bale_number'] ?? ('BALE-' . Carbon::now()->year . '-' . str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT));
                $newBales[] = [
                    'bale_number' => $baleNum,
                    'items' => [
                        [
                            'raw_material_id' => $item['raw_material_id'] ?? $this->raw_material_id,
                            'item_name' => $item['item_name'] ?? '',
                            'design_number' => $item['design_number'] ?? '',
                            'stock_id' => $item['stock_id'] ?? ('STK-' . Carbon::now()->format('ymd') . '-' . ($i + 1) . '-1'),
                            'declared_length' => $item['declared_length'] ?? '',
                            'cost_per_unit' => $item['cost_per_unit'] ?? '',
                            'photo' => $item['photo'] ?? null,
                        ]
                    ]
                ];
            }
            $this->bale_items = $newBales;
        }
    }

    protected function initBaleItems(int $count, ?int $defaultMaterialId = null)
    {
        $availableMaterials = $this->raw_material_category_id 
            ? RawMaterial::where('raw_material_category_id', $this->raw_material_category_id)->active()->orderBy('name')->get()
            : collect();

        $defaultMatId = $defaultMaterialId ?? $availableMaterials->first()?->id;

        $bales = [];
        for ($i = 0; $i < $count; $i++) {
            $existingBale = $this->bale_items[$i] ?? [];
            $baleNumber = $existingBale['bale_number'] ?? ('BALE-' . Carbon::now()->year . '-' . str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT));
            
            $items = [];
            if (isset($existingBale['items']) && is_array($existingBale['items']) && count($existingBale['items']) > 0) {
                $items = $existingBale['items'];
            } else {
                $len = $existingBale['declared_length'] ?? ($this->individual_bale_lengths[$i] ?? $this->declared_bale_length);
                $rate = $existingBale['cost_per_unit'] ?? $this->purchase_rate;
                $matId = $existingBale['raw_material_id'] ?? $defaultMatId;

                $items[] = [
                    'raw_material_id' => $matId ? intval($matId) : null,
                    'design_number' => $existingBale['design_number'] ?? '',
                    'stock_id' => $existingBale['stock_id'] ?? ('STK-' . Carbon::now()->format('ymd') . '-' . ($i + 1) . '-1'),
                    'declared_length' => $len !== '' ? $len : '',
                    'cost_per_unit' => $rate !== '' ? $rate : '',
                    'photo' => $existingBale['photo'] ?? null,
                ];
            }

            $bales[] = [
                'bale_number' => $baleNumber,
                'items' => $items,
            ];
        }
        $this->bale_items = $bales;
    }

    public function addItemToBale(int $baleIndex)
    {
        $this->normalizeBaleItems();

        if (!isset($this->bale_items[$baleIndex])) {
            return;
        }

        $availableMaterials = $this->raw_material_category_id 
            ? RawMaterial::where('raw_material_category_id', $this->raw_material_category_id)->active()->orderBy('name')->get()
            : collect();
        $defaultMatId = $availableMaterials->first()?->id;

        $itemCount = count($this->bale_items[$baleIndex]['items']);
        $stockId = 'STK-' . Carbon::now()->format('ymd') . '-' . ($baleIndex + 1) . '-' . ($itemCount + 1);

        $this->bale_items[$baleIndex]['items'][] = [
            'raw_material_id' => $defaultMatId ? intval($defaultMatId) : null,
            'design_number' => '',
            'stock_id' => $stockId,
            'declared_length' => '',
            'cost_per_unit' => $this->purchase_rate ?: '',
            'photo' => null,
        ];

        $this->recalculateTotal();
    }

    public function removeItemFromBale(int $baleIndex, int $itemIndex)
    {
        $this->normalizeBaleItems();

        if (isset($this->bale_items[$baleIndex]['items'][$itemIndex])) {
            if (count($this->bale_items[$baleIndex]['items']) > 1) {
                array_splice($this->bale_items[$baleIndex]['items'], $itemIndex, 1);
            } else {
                $availableMaterials = $this->raw_material_category_id 
                    ? RawMaterial::where('raw_material_category_id', $this->raw_material_category_id)->active()->orderBy('name')->get()
                    : collect();
                $defaultMatId = $availableMaterials->first()?->id;

                $this->bale_items[$baleIndex]['items'][0] = [
                    'raw_material_id' => $defaultMatId ? intval($defaultMatId) : null,
                    'design_number' => '',
                    'stock_id' => 'STK-' . Carbon::now()->format('ymd') . '-' . ($baleIndex + 1) . '-1',
                    'declared_length' => '',
                    'cost_per_unit' => $this->purchase_rate ?: '',
                    'photo' => null,
                ];
            }
            $this->recalculateTotal();
        }
    }

    public function addBale()
    {
        $this->normalizeBaleItems();
        $baleIndex = count($this->bale_items);
        
        $availableMaterials = $this->raw_material_category_id 
            ? RawMaterial::where('raw_material_category_id', $this->raw_material_category_id)->active()->orderBy('name')->get()
            : collect();
        $defaultMatId = $availableMaterials->first()?->id;

        $baleNumber = 'BALE-' . Carbon::now()->year . '-' . str_pad((string) ($baleIndex + 1), 4, '0', STR_PAD_LEFT);
        $defaultLen = ($this->all_bales_equal_length && $this->declared_bale_length !== '') ? $this->declared_bale_length : '';

        $this->bale_items[] = [
            'bale_number' => $baleNumber,
            'items' => [
                [
                    'raw_material_id' => $defaultMatId ? intval($defaultMatId) : null,
                    'design_number' => '',
                    'stock_id' => 'STK-' . Carbon::now()->format('ymd') . '-' . ($baleIndex + 1) . '-1',
                    'declared_length' => $defaultLen,
                    'cost_per_unit' => $this->purchase_rate ?: '',
                    'photo' => null,
                ]
            ]
        ];

        $this->num_bales = count($this->bale_items);
        $this->recalculateTotal();
    }

    public function removeBale(int $baleIndex)
    {
        $this->normalizeBaleItems();
        if (count($this->bale_items) > 1 && isset($this->bale_items[$baleIndex])) {
            array_splice($this->bale_items, $baleIndex, 1);
            
            foreach ($this->bale_items as $i => &$bale) {
                if (empty($bale['bale_number']) || str_starts_with($bale['bale_number'], 'BALE-') || str_starts_with($bale['bale_number'], 'Bale #')) {
                    $bale['bale_number'] = 'BALE-' . Carbon::now()->year . '-' . str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT);
                }
            }
            unset($bale);

            $this->num_bales = count($this->bale_items);
            $this->recalculateTotal();
        }
    }

    public function updatedGstIncluded()
    {
        $this->recalculateTotal();
    }

    public function updatedGstPercent()
    {
        $this->recalculateTotal();
    }

    public function getGstAmountProperty(): float
    {
        if ($this->gst_included) {
            return 0.00;
        }

        $rate = floatval($this->gst_percent ?: 0);
        return round($this->total_amount * ($rate / 100), 2);
    }

    public function getGrandTotalProperty(): float
    {
        return round($this->total_amount + $this->gstAmount, 2);
    }

    public function updatedSupplierId($value)
    {
        if ($value) {
            $supplier = Supplier::find($value);
            if ($supplier) {
                $this->supplier_name = $supplier->name;
            }
        }
    }

    public function updatedRawMaterialId($value)
    {
        if ($value) {
            $material = RawMaterial::find($value);
            if ($material) {
                $this->unitName = $material->unit;
            }
        }
        $this->recalculateTotal();
    }

    public function updatedNumBales($value)
    {
        if ($value === '' || $value === null) {
            $this->num_bales = '';
            $this->recalculateTotal();
            return;
        }

        $count = max(1, min(100, intval($value)));
        $this->num_bales = $count;

        $this->normalizeBaleItems();
        $currentCount = count($this->bale_items);

        if ($count > $currentCount) {
            $availableMaterials = $this->raw_material_category_id 
                ? RawMaterial::where('raw_material_category_id', $this->raw_material_category_id)->active()->orderBy('name')->get()
                : collect();
            $defaultMatId = $availableMaterials->first()?->id;
            $defaultLen = ($this->all_bales_equal_length && $this->declared_bale_length !== '') ? $this->declared_bale_length : '';

            for ($i = $currentCount; $i < $count; $i++) {
                $baleNumber = 'BALE-' . Carbon::now()->year . '-' . str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT);
                $this->bale_items[] = [
                    'bale_number' => $baleNumber,
                    'items' => [
                        [
                            'raw_material_id' => $defaultMatId ? intval($defaultMatId) : null,
                            'design_number' => '',
                            'stock_id' => 'STK-' . Carbon::now()->format('ymd') . '-' . ($i + 1) . '-1',
                            'declared_length' => $defaultLen,
                            'cost_per_unit' => $this->purchase_rate ?: '',
                            'photo' => null,
                        ]
                    ]
                ];
            }
        } elseif ($count < $currentCount) {
            $this->bale_items = array_slice($this->bale_items, 0, $count);
        }

        $this->recalculateTotal();
    }

    public function updatedAllBalesEqualLength($value)
    {
        if ($value && !empty($this->declared_bale_length)) {
            $this->normalizeBaleItems();
            foreach ($this->bale_items as $i => &$bale) {
                if (count($bale['items']) === 1) {
                    $bale['items'][0]['declared_length'] = $this->declared_bale_length;
                }
            }
            unset($bale);
        }
        $this->recalculateTotal();
    }

    public function updatedIndividualBaleLengths()
    {
        $this->normalizeBaleItems();
        foreach ($this->individual_bale_lengths as $i => $len) {
            if (isset($this->bale_items[$i]['items'][0])) {
                $this->bale_items[$i]['items'][0]['declared_length'] = $len;
            }
        }
        $this->recalculateTotal();
    }

    public function updatedBaleItems()
    {
        $this->recalculateTotal();
    }

    public function updatedDeclaredBaleLength($value)
    {
        if ($this->all_bales_equal_length) {
            $this->normalizeBaleItems();
            foreach ($this->bale_items as $i => &$bale) {
                if (count($bale['items']) === 1) {
                    $bale['items'][0]['declared_length'] = $value;
                }
            }
            unset($bale);
        }
        $this->recalculateTotal();
    }

    public function updatedQuantityReceived()
    {
        $this->recalculateTotal();
    }

    public function updatedPurchaseRate($value)
    {
        if ($this->unitType === 'length_based' && $value !== '') {
            $this->normalizeBaleItems();
            foreach ($this->bale_items as $i => &$bale) {
                foreach ($bale['items'] as &$item) {
                    if (empty($item['cost_per_unit'])) {
                        $item['cost_per_unit'] = $value;
                    }
                }
            }
            unset($bale, $item);
        }
        $this->recalculateTotal();
    }

    protected function recalculateTotal()
    {
        if ($this->unitType === 'length_based') {
            $this->normalizeBaleItems();
            $balesCount = max(1, intval($this->num_bales ?: 1));

            if (count($this->bale_items) === 0) {
                $this->initBaleItems($balesCount);
            }

            $totalLength = 0.0;
            $calculatedSum = 0.0;

            foreach ($this->bale_items as $bale) {
                foreach ($bale['items'] ?? [] as $item) {
                    $len = floatval($item['declared_length'] ?? 0);
                    $rate = floatval(($item['cost_per_unit'] !== '' && $item['cost_per_unit'] !== null) ? $item['cost_per_unit'] : ($this->purchase_rate ?: 0));
                    
                    $totalLength += $len;
                    $calculatedSum += ($len * $rate);
                }
            }

            $this->quantity_received = $totalLength > 0 ? (string) $totalLength : '';

            if ($calculatedSum > 0) {
                $this->total_amount = round($calculatedSum, 2);
                if ($totalLength > 0 && (empty($this->purchase_rate) || floatval($this->purchase_rate) == 0)) {
                    $this->purchase_rate = (string) round($calculatedSum / $totalLength, 2);
                }
            } else {
                $qty = floatval($this->quantity_received ?: 0);
                $rate = floatval($this->purchase_rate ?: 0);
                $this->total_amount = round($qty * $rate, 2);
            }
        } else {
            $qty = floatval($this->quantity_received ?: 0);
            $rate = floatval($this->purchase_rate ?: 0);
            $this->total_amount = round($qty * $rate, 2);
        }
    }

    protected function rules()
    {
        $rules = [
            'supplier_id' => 'nullable|exists:suppliers,id',
            'supplier_name' => 'required|string|max:255',
            'purchase_date' => 'required|date|before_or_equal:today',
            'invoice_number' => 'required|string|max:100',
            'lot_number' => 'required|string|max:100',
            'raw_material_category_id' => 'required|exists:raw_material_categories,id',
        ];

        if ($this->unitType === 'length_based') {
            $this->normalizeBaleItems();
            $rules['num_bales'] = 'required|integer|min:1';
            $rules['bale_items'] = 'required|array|min:1';
            $rules['bale_items.*.bale_number'] = 'required|string';
            $rules['bale_items.*.items'] = 'required|array|min:1';
            $rules['bale_items.*.items.*.raw_material_id'] = 'required|exists:raw_materials,id';
            $rules['bale_items.*.items.*.declared_length'] = 'required|numeric|gt:0';
            $rules['purchase_rate'] = 'nullable|numeric';
        } else {
            $rules['raw_material_id'] = 'required|exists:raw_materials,id';
            $rules['quantity_received'] = 'required|numeric|gt:0';
            $rules['purchase_rate'] = 'required|numeric|gt:0';
        }

        if (!$this->gst_included) {
            $rules['gst_percent'] = 'required|numeric|min:0|max:100';
        }

        return $rules;
    }

    protected function messages()
    {
        return [
            'supplier_name.required' => 'Supplier Name is required.',
            'purchase_date.required' => 'Purchase Date is required.',
            'invoice_number.required' => 'Invoice Number is required.',
            'lot_number.required' => 'Lot Number is required.',
            'raw_material_category_id.required' => 'Please select a raw material category.',
            'raw_material_id.required' => 'Please select a raw material item.',
            'num_bales.required' => 'Number of Bales is required.',
            'num_bales.min' => 'Number of Bales must be at least 1.',
            'declared_bale_length.required' => 'Declared Length per Bale is required.',
            'declared_bale_length.gt' => 'Declared Length per Bale must be greater than zero.',
            'bale_items.*.items.*.raw_material_id.required' => 'Please select the raw material item for each item line.',
            'bale_items.*.items.*.declared_length.required' => 'Declared length is required for each item line.',
            'bale_items.*.items.*.declared_length.gt' => 'Length for each item line must be greater than zero.',
            'bale_items.*.raw_material_id.required' => 'Please select the raw material item for each bale.',
            'bale_items.*.declared_length.required' => 'Declared length is required for each bale item.',
            'bale_items.*.declared_length.gt' => 'Length for each bale item must be greater than zero.',
            'quantity_received.required' => 'Quantity Received is required.',
            'quantity_received.gt' => 'Quantity Received must be greater than zero.',
            'purchase_rate.required' => 'Purchase Rate is required.',
            'purchase_rate.gt' => 'Purchase Rate must be greater than zero.',
            'gst_percent.required' => 'GST percentage is required when GST is not included.',
            'gst_percent.min' => 'GST percentage cannot be negative.',
            'gst_percent.max' => 'GST percentage cannot exceed 100%.',
        ];
    }

    public function savePurchaseEntry()
    {
        $this->normalizeBaleItems();
        $this->validate();

        $batchesCreated = [];

        DB::transaction(function () use (&$batchesCreated) {
            if ($this->unitType === 'length_based') {
                // Group item lines across all bales by raw_material_id
                $groupedItems = [];
                foreach ($this->bale_items as $baleIndex => $bale) {
                    $baleNumber = trim($bale['bale_number'] ?? '') ?: ('BALE-' . Carbon::now()->year . '-' . str_pad((string)($baleIndex + 1), 4, '0', STR_PAD_LEFT));
                    
                    foreach ($bale['items'] as $itemIndex => $it) {
                        $matId = intval($it['raw_material_id']);
                        $groupedItems[$matId][] = array_merge($it, [
                            'bale_number' => $baleNumber,
                            'bale_index' => $baleIndex,
                            'item_index' => $itemIndex,
                        ]);
                    }
                }

                $effectiveGrandTotal = $this->grandTotal;
                $effectiveSubtotal = max(0.01, $this->total_amount);

                foreach ($groupedItems as $matId => $items) {
                    $material = RawMaterial::with(['unitGroup', 'unitModel'])->findOrFail($matId);

                    $matQty = array_sum(array_map(fn($it) => floatval($it['declared_length']), $items));
                    $matSubtotal = 0.0;
                    foreach ($items as $it) {
                        $rate = floatval(($it['cost_per_unit'] !== '' && $it['cost_per_unit'] !== null) ? $it['cost_per_unit'] : $this->purchase_rate);
                        $matSubtotal += (floatval($it['declared_length']) * $rate);
                    }

                    // Proportionally distribute grand total / GST to each material's batch
                    $ratio = $effectiveSubtotal > 0 ? ($matSubtotal / $effectiveSubtotal) : (1 / count($groupedItems));
                    $batchTotalAmount = round($effectiveGrandTotal * $ratio, 2);
                    $effectiveRate = $matQty > 0 ? round($batchTotalAmount / $matQty, 4) : floatval($this->purchase_rate);

                    $baseQty = $matQty;
                    if ($material->unitModel) {
                        $baseQty = $material->unitModel->toBaseQuantity($matQty);
                    }

                    $distinctBaleNumbers = array_unique(array_column($items, 'bale_number'));
                    $numBales = count($distinctBaleNumbers);
                    $avgDeclaredLength = $numBales > 0 ? ($matQty / $numBales) : 0;

                    $batch = InventoryBatch::create([
                        'raw_material_id' => $matId,
                        'supplier_id' => $this->supplier_id,
                        'supplier_name' => $this->supplier_name,
                        'purchase_date' => $this->purchase_date,
                        'invoice_number' => $this->invoice_number,
                        'lot_number' => trim($this->lot_number),
                        'quantity_received' => $matQty,
                        'balance_quantity' => $matQty,
                        'base_quantity' => $baseQty,
                        'base_current_balance' => $baseQty,
                        'quantity_consumed' => 0.0000,
                        'purchase_rate' => $effectiveRate,
                        'total_amount' => $batchTotalAmount,
                        'unit' => $material->unit,
                        'purchase_unit_id' => $material->unit_id,
                        'num_bales' => $numBales,
                        'declared_bale_length' => $avgDeclaredLength,
                        'status' => 'active',
                    ]);

                    foreach ($items as $it) {
                        $len = floatval($it['declared_length']);
                        $rate = floatval(($it['cost_per_unit'] !== '' && $it['cost_per_unit'] !== null) ? $it['cost_per_unit'] : $this->purchase_rate);
                        $baleTotal = round($len * $rate, 2);

                        $photoPath = null;
                        if (isset($it['photo']) && is_object($it['photo']) && method_exists($it['photo'], 'store')) {
                            $photoPath = $it['photo']->store('bale_photos', 'public');
                        }

                        $batch->bales()->create([
                            'bale_number' => $it['bale_number'],
                            'item_name' => $material->name,
                            'design_number' => trim($it['design_number'] ?? '') ?: null,
                            'stock_id' => trim($it['stock_id'] ?? '') ?: null,
                            'status' => 'unopened',
                            'declared_length' => $len,
                            'current_balance_length' => $len,
                            'cost_per_unit' => $rate,
                            'total_cost' => $baleTotal,
                            'photo_path' => $photoPath,
                        ]);
                    }

                    InventoryBatchLogger::log($batch->id, 'created', $batch->quantity_received, null, "Fabric purchase entry recorded ({$numBales} bales, Lot: {$batch->lot_number})");
                    $batchesCreated[] = $batch;
                }
            } else {
                $material = RawMaterial::with(['unitGroup', 'unitModel'])->findOrFail($this->raw_material_id);
                $qtyReceived = floatval($this->quantity_received);
                $baseQty = $material->unitModel ? $material->unitModel->toBaseQuantity($qtyReceived) : $qtyReceived;
                $effectiveTotal = $this->grandTotal;
                $effectiveRate = $qtyReceived > 0 ? round($effectiveTotal / $qtyReceived, 4) : floatval($this->purchase_rate);

                $batch = InventoryBatch::create([
                    'raw_material_id' => $this->raw_material_id,
                    'supplier_id' => $this->supplier_id,
                    'supplier_name' => $this->supplier_name,
                    'purchase_date' => $this->purchase_date,
                    'invoice_number' => $this->invoice_number,
                    'lot_number' => trim($this->lot_number),
                    'quantity_received' => $qtyReceived,
                    'balance_quantity' => $qtyReceived,
                    'base_quantity' => $baseQty,
                    'base_current_balance' => $baseQty,
                    'quantity_consumed' => 0.0000,
                    'purchase_rate' => $effectiveRate,
                    'total_amount' => $effectiveTotal,
                    'unit' => $material->unit,
                    'purchase_unit_id' => $material->unit_id,
                    'status' => 'active',
                ]);

                InventoryBatchLogger::log($batch->id, 'created', $batch->quantity_received, null, "Purchase entry recorded (Lot: {$batch->lot_number})");
                $batchesCreated[] = $batch;
            }
        });

        session()->flash('toast', [
            'message' => 'Purchase entry saved and inventory batch(es) created successfully!',
            'type' => 'success'
        ]);

        return redirect()->route('factory.raw-materials.index');
    }

    public function render()
    {
        $categories = RawMaterialCategory::active()->orderBy('name')->get();
        $suppliers = Supplier::orderBy('name')->get();

        $availableMaterials = $this->raw_material_category_id 
            ? RawMaterial::where('raw_material_category_id', $this->raw_material_category_id)->active()->orderBy('name')->get()
            : collect();

        return view('livewire.factory.raw-material-purchase-entry', [
            'categories' => $categories,
            'suppliers' => $suppliers,
            'availableMaterials' => $availableMaterials,
        ])->title('Record Raw Material Purchase');
    }
}
