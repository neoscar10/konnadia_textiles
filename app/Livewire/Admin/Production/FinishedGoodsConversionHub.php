<?php

namespace App\Livewire\Admin\Production;

use App\Models\FinishedGoodsBatch;
use App\Models\FrontEndProduct;
use App\Services\Manufacturing\FinishedGoodsConversionService;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.admin.layout')]
class FinishedGoodsConversionHub extends Component
{
    use WithPagination;

    public string $searchBarcode = '';
    public string $barcodeQuery = '';

    // Modals
    public bool $showWizardModal = false;
    public int $wizardStep = 1;

    public bool $showPrintModal = false;
    public ?int $activePrintBatchId = null;
    public int $printStickerQty = 10;
    public string $printStickerSize = 'Standard Sticker (50mm × 25mm)';

    public bool $showAuditModal = false;
    public ?FinishedGoodsBatch $auditBatch = null;

    // Wizard form properties
    public ?int $selectedFrontEndProductId = null;
    public int $produceQty = 10;
    public string $unitSelection = 'Piece (Pcs)';
    public int $unitFactor = 1;
    public string $designId = 'DSG-108-GOLD';
    public bool $publishStorefront = true;
    public bool $reusePhoto = true;
    public string $notes = '';
    public string $storefrontMode = 'existing';
    public ?int $selectedStorefrontProductId = null;

    // Live Stock Check state
    public array $stockCheck = [
        'canProceed' => true,
        'mfgStock' => [],
        'pkgStock' => [],
        'missingItems' => [],
    ];

    public function mount()
    {
        $this->storefrontMode = 'existing';
        $first = FrontEndProduct::where('is_active', true)->first();
        if ($first) {
            $this->selectedFrontEndProductId = $first->id;
            $this->autoSelectStorefrontProduct();
            $this->recalculateStockCheck();
        }
    }

    public function updatedSelectedFrontEndProductId()
    {
        $this->autoSelectStorefrontProduct();
        $this->recalculateStockCheck();
    }

    public function autoSelectStorefrontProduct()
    {
        $available = $this->availableStorefrontProducts;
        if ($available->isNotEmpty()) {
            $this->selectedStorefrontProductId = $available->first()->id;
            $this->storefrontMode = 'existing';
        } else {
            $this->selectedStorefrontProductId = null;
            $this->storefrontMode = 'new';
        }
    }

    public function getAvailableStorefrontProductsProperty()
    {
        if (!$this->selectedFrontEndProductId) {
            return collect();
        }

        $feProduct = FrontEndProduct::find($this->selectedFrontEndProductId);
        if (!$feProduct) {
            return collect();
        }

        if ($feProduct->category_id) {
            $products = \App\Models\Product::whereHas('categories', function ($q) use ($feProduct) {
                $q->where('categories.id', $feProduct->category_id);
            })->orderBy('title')->get();

            if ($products->isNotEmpty()) {
                return $products;
            }
        }

        return \App\Models\Product::orderBy('title')->get();
    }

    public function updatedProduceQty()
    {
        $this->produceQty = max(1, intval($this->produceQty));
        $this->recalculateStockCheck();
    }

    public function updatedUnitSelection()
    {
        if ($this->unitSelection === 'Pack / Set (10 Pcs)') {
            $this->unitFactor = 10;
        } else {
            $this->unitFactor = 1;
        }
        $this->recalculateStockCheck();
    }

    public function recalculateStockCheck()
    {
        if (!$this->selectedFrontEndProductId) {
            $this->stockCheck = ['canProceed' => false, 'mfgStock' => [], 'pkgStock' => [], 'missingItems' => []];
            return;
        }

        $feProduct = FrontEndProduct::find($this->selectedFrontEndProductId);
        if (!$feProduct) {
            return;
        }

        $service = resolve(FinishedGoodsConversionService::class);
        $this->stockCheck = $service->checkFrontEndStockAvailability($feProduct, $this->produceQty, $this->unitFactor);
    }

    public function getGeneratedBarcodeProperty(): string
    {
        $dIdClean = strtoupper(preg_replace('/[^a-zA-Z0-9]/', '', $this->designId ?: 'DSG108'));
        return "FG-{$dIdClean}-" . now()->format('Y') . "-" . str_pad((string) $this->produceQty, 4, '0', STR_PAD_LEFT);
    }

    public function openWizardModal()
    {
        $this->wizardStep = 1;
        $this->produceQty = 10;
        $this->unitSelection = 'Piece (Pcs)';
        $this->unitFactor = 1;
        $this->designId = 'DSG-108-GOLD';
        $this->publishStorefront = true;
        $this->reusePhoto = true;
        $this->notes = '';

        if (!$this->selectedFrontEndProductId) {
            $first = FrontEndProduct::where('is_active', true)->first();
            if ($first) {
                $this->selectedFrontEndProductId = $first->id;
            }
        }

        $this->autoSelectStorefrontProduct();
        $this->recalculateStockCheck();
        $this->showWizardModal = true;
    }

    public function goToWizardStep(int $step)
    {
        if ($step === 2) {
            $this->recalculateStockCheck();
            if (!$this->stockCheck['canProceed']) {
                session()->flash('toast', [
                    'type' => 'error',
                    'message' => 'Cannot proceed to Step 2: Insufficient unconverted manufacturing stock.',
                ]);
                return;
            }

            if (!$this->selectedStorefrontProductId && $this->availableStorefrontProducts->isNotEmpty()) {
                $this->selectedStorefrontProductId = $this->availableStorefrontProducts->first()->id;
                $this->storefrontMode = 'existing';
            }
        }
        $this->wizardStep = $step;
    }

    public function confirmAndExecuteConversion(FinishedGoodsConversionService $conversionService)
    {
        if (!$this->selectedFrontEndProductId) {
            session()->flash('toast', ['type' => 'error', 'message' => 'Please select a Target Front-End Product.']);
            return;
        }

        if (!$this->selectedStorefrontProductId && $this->availableStorefrontProducts->isNotEmpty()) {
            $this->selectedStorefrontProductId = $this->availableStorefrontProducts->first()->id;
            $this->storefrontMode = 'existing';
        }

        try {
            $fgBatch = $conversionService->convertFrontEndProductBatch([
                'front_end_product_id' => $this->selectedFrontEndProductId,
                'converted_qty' => $this->produceQty,
                'unit' => $this->unitSelection,
                'unit_factor' => $this->unitFactor,
                'design_id' => $this->designId,
                'is_published' => $this->publishStorefront,
                'storefront_mode' => $this->storefrontMode,
                'existing_storefront_product_id' => $this->selectedStorefrontProductId,
                'notes' => $this->notes,
            ]);

            $this->showWizardModal = false;

            session()->flash('toast', [
                'type' => 'success',
                'message' => "Finished Goods Lot {$fgBatch->barcode} converted successfully! Added {$fgBatch->converted_qty} {$fgBatch->unit} to stock.",
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

        $batch = FinishedGoodsBatch::with(['frontEndProduct', 'items.manufacturingProduct', 'packagingDeductions.rawMaterial'])
            ->where('barcode', 'like', "%{$query}%")
            ->first();

        if (!$batch) {
            $batch = FinishedGoodsBatch::with(['frontEndProduct', 'items.manufacturingProduct', 'packagingDeductions.rawMaterial'])->latest()->first();
        }

        if ($batch) {
            $this->auditBatch = $batch;
            $this->showAuditModal = true;
        } else {
            session()->flash('toast', ['type' => 'error', 'message' => "No converted batch found for barcode {$query}."]);
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

    public function render()
    {
        $batches = FinishedGoodsBatch::with(['frontEndProduct', 'creator'])
            ->when($this->searchBarcode, function ($q) {
                $q->where('barcode', 'like', "%{$this->searchBarcode}%")
                  ->orWhere('design_id', 'like', "%{$this->searchBarcode}%")
                  ->orWhereHas('frontEndProduct', fn($fp) => $fp->where('name', 'like', "%{$this->searchBarcode}%"));
            })
            ->latest()
            ->paginate(10);

        $frontendProducts = FrontEndProduct::where('is_active', true)->orderBy('name')->get();

        $activePrintBatch = $this->activePrintBatchId ? FinishedGoodsBatch::with('frontEndProduct')->find($this->activePrintBatchId) : null;

        return view('livewire.admin.production.finished-goods-conversion-hub', [
            'batches' => $batches,
            'frontendProducts' => $frontendProducts,
            'activePrintBatch' => $activePrintBatch,
        ])->title('Finished Goods Conversion & Barcode Hub');
    }
}
