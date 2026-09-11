<?php

namespace App\Livewire\Admin\Production;

use App\Models\Category;
use App\Models\FinishedGoodsBatch;
use App\Models\FrontEndProduct;
use App\Models\ManufacturingProduct;
use App\Models\Product;
use App\Services\Catalog\CategoryService;
use App\Services\Manufacturing\FinishedGoodsConversionService;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

#[Layout('components.admin.layout')]
class FinishedGoodsConversionHub extends Component
{
    use WithPagination, WithFileUploads;

    public string $searchBarcode = '';
    public string $barcodeQuery = '';

    // Modals
    public bool $showWizardModal = false;
    public int $wizardStep = 1;

    public bool $showPrintModal = false;
    public ?int $activePrintBatchId = null;
    public $printStickerQty = 10;
    public string $printStickerSize = 'Standard Sticker (50mm × 25mm)';

    public bool $showAuditModal = false;
    public ?FinishedGoodsBatch $auditBatch = null;

    // Wizard form properties (Sketch-based flow)
    public ?int $selectedCategoryId = null;
    public $produceQty = '';
    public string $designType = 'new'; // 'new' or 'existing'
    public string $designId = '';
    public bool $reuseCuttingPhoto = false;
    public $productImage = null;
    public ?int $selectedStorefrontProductId = null;
    public array $componentSelections = [];
    public string $notes = '';

    // Live Stock Check state
    public array $stockCheck = [
        'canProceed' => true,
        'mfgStock' => [],
        'pkgStock' => [],
        'missingItems' => [],
    ];

    public function mount(CategoryService $categoryService)
    {
        $leafCategories = $categoryService->getLeafCategories();

        // Auto-select first configured leaf category if available
        $configuredFeProduct = FrontEndProduct::whereNotNull('category_id')->where('is_active', true)->first();
        if ($configuredFeProduct) {
            $this->selectedCategoryId = $configuredFeProduct->category_id;
        } else if ($leafCategories->isNotEmpty()) {
            $this->selectedCategoryId = $leafCategories->first()->id;
        }

        $this->produceQty = '';
        $this->designType = 'new';
        $this->designId = '';
        $this->reuseCuttingPhoto = false;
        $this->productImage = null;
        $this->autoSelectStorefrontProduct();
        $this->ensureComponentSelectionsInitialized();
        $this->recalculateStockCheck();
    }

    public function updatedSelectedCategoryId()
    {
        $this->autoSelectStorefrontProduct();
        $this->ensureComponentSelectionsInitialized();
        $this->recalculateStockCheck();
    }

    public function updatedProduceQty()
    {
        $this->ensureComponentSelectionsInitialized();
        $this->recalculateStockCheck();
    }

    public function updatedComponentSelections()
    {
        $this->recalculateStockCheck();
    }

    public function updatedDesignType()
    {
        if ($this->designType === 'existing') {
            $this->autoSelectStorefrontProduct();
        }
    }

    public function ensureComponentSelectionsInitialized()
    {
        $config = $this->categoryConfiguration;
        if (!$config || $config->components->isEmpty()) {
            return;
        }

        $targetQty = max(0, intval($this->produceQty));

        foreach ($config->components as $idx => $comp) {
            $reqQty = (int) ($comp->quantity * $targetQty);
            $existing = $this->componentSelections[$idx] ?? $this->componentSelections[(string)$idx] ?? null;

            $mfg = $comp->manufacturingProduct;
            $defaultPatId = ($mfg && $mfg->patterns->isNotEmpty()) ? (string) $mfg->patterns->first()->id : '';

            if (!$existing || !is_array($existing) || empty($existing)) {
                $this->componentSelections[$idx] = [
                    ['pattern_id' => $defaultPatId, 'quantity' => $reqQty]
                ];
            } else {
                if (count($existing) === 1) {
                    if (empty($existing[0]['pattern_id']) && $defaultPatId) {
                        $this->componentSelections[$idx][0]['pattern_id'] = $defaultPatId;
                    }
                    $this->componentSelections[$idx][0]['quantity'] = $reqQty;
                }
            }
        }
    }

    public function addPatternRow(int $compIdx)
    {
        if (!isset($this->componentSelections[$compIdx])) {
            $this->componentSelections[$compIdx] = [];
        }
        $comp = $this->categoryConfiguration?->components->get($compIdx);
        $mfg = $comp?->manufacturingProduct;
        $defaultPatId = ($mfg && $mfg->patterns->isNotEmpty()) ? (string) $mfg->patterns->first()->id : '';
        $this->componentSelections[$compIdx][] = ['pattern_id' => $defaultPatId, 'quantity' => 0];
    }

    public function removePatternRow(int $compIdx, int $pIdx)
    {
        if (isset($this->componentSelections[$compIdx][$pIdx])) {
            unset($this->componentSelections[$compIdx][$pIdx]);
            $this->componentSelections[$compIdx] = array_values($this->componentSelections[$compIdx]);
        }

        if (empty($this->componentSelections[$compIdx])) {
            $comp = $this->categoryConfiguration?->components->get($compIdx);
            $targetQty = max(0, intval($this->produceQty));
            $reqQty = ($comp?->quantity ?? 1) * $targetQty;
            $mfg = $comp?->manufacturingProduct;
            $defaultPatId = ($mfg && $mfg->patterns->isNotEmpty()) ? (string) $mfg->patterns->first()->id : '';
            $this->componentSelections[$compIdx] = [
                ['pattern_id' => $defaultPatId, 'quantity' => $reqQty]
            ];
        }
    }

    public function getComponentAllocatedQty(int $compIdx): int
    {
        $rows = $this->componentSelections[$compIdx] ?? $this->componentSelections[(string)$compIdx] ?? [];
        if (!is_array($rows)) {
            return 0;
        }
        return array_sum(array_map(fn($row) => intval($row['quantity'] ?? 0), $rows));
    }

    public function goToStep2()
    {
        if (!$this->selectedCategoryId) {
            session()->flash('toast', ['type' => 'error', 'message' => 'Please select a Leaf Category.']);
            return;
        }

        if (empty($this->produceQty) || intval($this->produceQty) <= 0) {
            session()->flash('toast', ['type' => 'error', 'message' => 'Target Quantity (Sets) is required and must be at least 1.']);
            return;
        }

        $targetQty = intval($this->produceQty);
        $config = $this->categoryConfiguration;
        if ($config && $config->components->isNotEmpty()) {
            foreach ($config->components as $idx => $comp) {
                $mfg = $comp->manufacturingProduct;
                $reqQty = (int) ($comp->quantity * $targetQty);
                $allocated = $this->getComponentAllocatedQty($idx);

                if ($allocated !== $reqQty) {
                    $mfgName = $mfg?->name ?? ("Item #" . ($idx + 1));
                    session()->flash('toast', [
                        'type' => 'error',
                        'message' => "Total pattern quantity for '{$mfgName}' ({$allocated} Pcs) must equal required quantity of {$reqQty} Pcs."
                    ]);
                    return;
                }
            }
        }

        $this->recalculateStockCheck();
        $this->wizardStep = 2;
    }

    public function goToStep1()
    {
        $this->wizardStep = 1;
    }

    public function getGeneratedBarcodeProperty(): string
    {
        $codeStr = trim($this->designId);
        if ($this->designType === 'existing' && $this->selectedStorefrontProductId) {
            $prod = Product::find($this->selectedStorefrontProductId);
            $codeStr = $prod?->sku ?: $prod?->title ?: 'EXST';
        }

        $dIdClean = strtoupper(preg_replace('/[^a-zA-Z0-9]/', '', $codeStr ?: 'DSG'));
        $targetQty = max(0, intval($this->produceQty));
        return "FG-{$dIdClean}-" . now()->format('Y') . "-" . str_pad((string) $targetQty, 4, '0', STR_PAD_LEFT);
    }

    public function autoSelectStorefrontProduct()
    {
        $available = $this->availableStorefrontProducts;
        if ($available->isNotEmpty()) {
            $this->selectedStorefrontProductId = $available->first()->id;
        } else {
            $this->selectedStorefrontProductId = null;
        }
    }

    public function getSelectedCategoryProperty()
    {
        return $this->selectedCategoryId ? Category::find($this->selectedCategoryId) : null;
    }

    public function getSelectedCategoryNameProperty(): string
    {
        return $this->selectedCategory?->name ?? 'Royal Touch 108"';
    }

    public function getProductTitlePrefillProperty(): string
    {
        if ($this->designType === 'existing' && $this->selectedStorefrontProductId) {
            $prod = Product::find($this->selectedStorefrontProductId);
            if ($prod) {
                return $prod->title;
            }
        }

        $dId = trim($this->designId);
        $catName = $this->selectedCategoryName;
        return trim("{$dId} {$catName}");
    }

    public function getAvailableStorefrontProductsProperty()
    {
        if (!$this->selectedCategoryId) {
            return collect();
        }

        return Product::whereHas('categories', function ($q) {
            $q->where('categories.id', $this->selectedCategoryId);
        })->orderBy('title')->get();
    }

    public function getSelectedStorefrontProductStockProperty(): int
    {
        if (!$this->selectedStorefrontProductId) {
            return 0;
        }

        $prod = Product::find($this->selectedStorefrontProductId);
        return (int) ($prod?->stock_quantity ?? 0);
    }

    public function getCategoryConfigurationProperty()
    {
        if (!$this->selectedCategoryId) {
            return null;
        }

        return FrontEndProduct::with([
            'components.manufacturingProduct.patterns.fabricWidth',
            'components.manufacturingProduct.patterns',
            'packagingItems.rawMaterial'
        ])->where('category_id', $this->selectedCategoryId)->first();
    }

    public function recalculateStockCheck()
    {
        if (!$this->selectedCategoryId) {
            $this->stockCheck = ['canProceed' => false, 'mfgStock' => [], 'pkgStock' => [], 'missingItems' => []];
            return;
        }

        $this->ensureComponentSelectionsInitialized();
        $targetQty = max(0, intval($this->produceQty));
        $service = resolve(FinishedGoodsConversionService::class);
        $this->stockCheck = $service->checkCategoryStockAvailability($this->selectedCategoryId, $targetQty, $this->componentSelections);
    }

    public function getPatternAvailableStock(?ManufacturingProduct $mfg, int $patternId, int $totalMfgStock): int
    {
        if (!$mfg) {
            return 0;
        }

        $jobStock = (int) \App\Models\ProductionJob::where('manufacturing_product_id', $mfg->id)
            ->where('pattern_id', $patternId)
            ->where('status', 'completed')
            ->get()
            ->sum(fn($j) => $j->remaining_unconverted_quantity);

        $batchStock = (int) \App\Models\ProductionBatch::where('manufacturing_product_id', $mfg->id)
            ->where('pattern_id', $patternId)
            ->where('status', 'Completed')
            ->where('is_converted', false)
            ->get()
            ->sum(fn($b) => $b->remaining_unconverted_quantity);

        $specificStock = max($jobStock, $batchStock);

        if ($specificStock > 0) {
            return $specificStock;
        }

        if ($totalMfgStock > 0) {
            $hasOtherPatternJobs = \App\Models\ProductionJob::where('manufacturing_product_id', $mfg->id)
                ->whereNotNull('pattern_id')
                ->where('pattern_id', '!=', $patternId)
                ->where('status', 'completed')
                ->exists();

            if (!$hasOtherPatternJobs || $mfg->patterns->count() === 1) {
                return $totalMfgStock;
            }
        }

        return 0;
    }

    public function openWizardModal()
    {
        $this->wizardStep = 1;
        $this->produceQty = '';
        $this->designType = 'new';
        $this->designId = '';
        $this->reuseCuttingPhoto = false;
        $this->productImage = null;
        $this->notes = '';

        if (!$this->selectedCategoryId) {
            $firstFe = FrontEndProduct::whereNotNull('category_id')->where('is_active', true)->first();
            if ($firstFe) {
                $this->selectedCategoryId = $firstFe->category_id;
            }
        }

        $this->autoSelectStorefrontProduct();
        $this->ensureComponentSelectionsInitialized();
        $this->recalculateStockCheck();
        $this->showWizardModal = true;
    }

    public function confirmAndExecuteConversion(FinishedGoodsConversionService $conversionService)
    {
        if (!$this->selectedCategoryId) {
            session()->flash('toast', ['type' => 'error', 'message' => 'Please select a Leaf Category.']);
            return;
        }

        if (empty($this->produceQty) || intval($this->produceQty) <= 0) {
            session()->flash('toast', ['type' => 'error', 'message' => 'Target Quantity (Sets) is required and must be at least 1.']);
            return;
        }

        if ($this->designType === 'new' && empty(trim($this->designId))) {
            session()->flash('toast', ['type' => 'error', 'message' => 'Please enter a Design ID for the new design.']);
            return;
        }

        if ($this->designType === 'existing' && !$this->selectedStorefrontProductId) {
            session()->flash('toast', ['type' => 'error', 'message' => 'Please select an existing Storefront Product.']);
            return;
        }

        try {
            $imagePath = null;
            if ($this->productImage) {
                $imagePath = $this->productImage->store('products', 'public');
            }

            $fgBatch = $conversionService->convertCategoryToFinishedGoods([
                'category_id' => $this->selectedCategoryId,
                'target_qty' => $this->produceQty,
                'design_type' => $this->designType,
                'design_id' => $this->designId,
                'existing_storefront_product_id' => $this->selectedStorefrontProductId,
                'component_selections' => $this->componentSelections,
                'notes' => $this->notes,
                'reuse_cutting_photo' => $this->reuseCuttingPhoto,
                'product_image' => $imagePath,
            ]);

            $this->showWizardModal = false;

            session()->flash('toast', [
                'type' => 'success',
                'message' => "Finished Goods Lot {$fgBatch->barcode} converted successfully! Added {$fgBatch->converted_qty} set(s) to stock.",
            ]);

            $this->openPrintBarcodeModal($fgBatch->id);
        } catch (\Exception $e) {
            session()->flash('toast', [
                'type' => 'error',
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function searchBarcodeFromInput()
    {
        $query = trim($this->barcodeQuery);
        if (empty($query)) {
            session()->flash('toast', ['type' => 'error', 'message' => 'Please enter or scan a valid barcode number.']);
            return;
        }

        $cleanQuery = preg_replace('/[^a-zA-Z0-9\-]/', '', $query);

        $batch = FinishedGoodsBatch::with(['frontEndProduct', 'items.manufacturingProduct', 'items.productionJob', 'items.productionBatch', 'packagingDeductions.rawMaterial'])
            ->where('barcode', 'like', "%{$query}%")
            ->orWhere('barcode', 'like', "%{$cleanQuery}%")
            ->orWhere('design_id', 'like', "%{$query}%")
            ->orWhereHas('frontEndProduct', fn($fp) => $fp->where('title', 'like', "%{$query}%")->orWhere('sku', 'like', "%{$query}%"))
            ->first();

        if ($batch) {
            $this->auditBatch = $batch;
            $this->showAuditModal = true;
        } else {
            session()->flash('toast', ['type' => 'error', 'message' => "No converted batch found matching barcode '{$query}'."]);
        }
    }

    public function openAuditModal(int $id)
    {
        $this->auditBatch = FinishedGoodsBatch::with(['frontEndProduct', 'items.manufacturingProduct', 'items.productionJob', 'items.productionBatch', 'packagingDeductions.rawMaterial'])->findOrFail($id);
        $this->showAuditModal = true;
    }

    public function openPrintBarcodeModal(int $id)
    {
        $batch = FinishedGoodsBatch::findOrFail($id);
        $this->activePrintBatchId = $batch->id;
        $this->printStickerQty = $batch->converted_qty ?: 10;
        $this->showPrintModal = true;
    }

    public function render(CategoryService $categoryService)
    {
        $batches = FinishedGoodsBatch::with(['frontEndProduct', 'creator'])
            ->when($this->searchBarcode, function ($q) {
                $q->where('barcode', 'like', "%{$this->searchBarcode}%")
                  ->orWhere('design_id', 'like', "%{$this->searchBarcode}%")
                  ->orWhereHas('frontEndProduct', fn($fp) => $fp->where('name', 'like', "%{$this->searchBarcode}%"));
            })
            ->latest()
            ->paginate(10);

        $leafCategories = $categoryService->getLeafCategories();
        $configuredCategoryIds = FrontEndProduct::whereNotNull('category_id')->pluck('category_id')->toArray();

        $activePrintBatch = $this->activePrintBatchId ? FinishedGoodsBatch::with('frontEndProduct')->find($this->activePrintBatchId) : null;

        return view('livewire.admin.production.finished-goods-conversion-hub', [
            'batches' => $batches,
            'leafCategories' => $leafCategories,
            'configuredCategoryIds' => $configuredCategoryIds,
            'activePrintBatch' => $activePrintBatch,
        ])->title('Finished Goods Conversion & Barcode Hub');
    }
}
