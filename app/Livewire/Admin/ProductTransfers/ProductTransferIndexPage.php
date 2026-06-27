<?php

namespace App\Livewire\Admin\ProductTransfers;

use App\Models\Product;
use App\Models\ProductCombination;
use App\Models\ProductUnit;
use App\Models\ProductTransfer;
use App\Models\RetailShop;
use App\Models\Category;
use App\Services\StockTransfer\ManufacturedProductTransferService;
use App\Services\StockTransfer\TransferInventoryService;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Exception;

#[Layout('components.admin.layout')]
class ProductTransferIndexPage extends Component
{
    use WithPagination;

    #[Url(history: true)]
    public $search = '';

    #[Url(history: true)]
    public $filterShop = '';

    #[Url(history: true)]
    public $status = '';

    // Wizard Modals
    public $showCreateModal = false;
    public $showDetailsModal = false;
    public $showSuccessModal = false;

    public ?ProductTransfer $selectedTransfer = null;
    public ?ProductTransfer $newlyCreatedTransfer = null;

    // Wizard Step State
    public $currentStep = 1;

    // Step 1 Form
    public $retailShopId = '';
    public $transferDate = '';
    public $notes = '';

    // Step 2 Catalog Filters & Product Selection
    public $wizardProductSearch = '';
    public $wizardCategoryFilter = '';
    
    public $selectedProductId = '';
    public $selectedCombinationId = '';
    public $unitQuantities = []; // array: [unit_id => quantity]
    public $selectedNote = '';

    // Added items in current transfer
    public $items = []; // array of items: [temp_id, product_id, product_title, product_combination_id, combination_name, product_unit_id, unit_name, quantity, conversion, base_quantity, stock_status, note]

    // Lists loaded for UI selectors
    public $activeShops = [];
    public $availableCombinations = [];
    public $availableUnits = [];

    public function mount()
    {
        $this->activeShops = RetailShop::active()->orderBy('name')->get();
        $this->transferDate = now()->toDateString();
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedFilterShop()
    {
        $this->resetPage();
    }

    public function updatedStatus()
    {
        $this->resetPage();
    }

    public function updatedWizardProductSearch()
    {
        $this->resetPage('wizardPage');
    }

    public function updatedWizardCategoryFilter()
    {
        $this->resetPage('wizardPage');
    }

    /**
     * Start the creation wizard
     */
    public function create()
    {
        $this->resetWizard();
        $this->currentStep = 1;
        $this->dispatch('open-modal', 'create-transfer-modal');
    }

    /**
     * Handle product selection.
     */
    public function selectProduct($productId)
    {
        $product = Product::with('units')->findOrFail($productId);
        $this->selectedProductId = $product->id;

        // Load combinations & units
        $this->availableCombinations = $product->combinations()->where('is_active', true)->get();
        $this->selectedCombinationId = '';

        $this->availableUnits = $product->units;
        
        // Initialize quantities to 0
        $this->unitQuantities = [];
        foreach ($this->availableUnits as $unit) {
            $this->unitQuantities[$unit->id] = 0;
        }

        $this->selectedNote = '';
    }

    /**
     * Quantity adjustment
     */
    public function incrementUnitQty($unitId)
    {
        $current = intval($this->unitQuantities[$unitId] ?? 0);
        $this->unitQuantities[$unitId] = $current + 1;
    }

    public function decrementUnitQty($unitId)
    {
        $current = intval($this->unitQuantities[$unitId] ?? 0);
        $this->unitQuantities[$unitId] = max(0, $current - 1);
    }

    /**
     * Add selected product units to transfer list.
     */
    public function addSelectedProductToTransfer(TransferInventoryService $inventoryService)
    {
        if (empty($this->selectedProductId)) {
            $this->dispatch('toast', message: 'Please select a product first.', type: 'error');
            return;
        }

        $product = Product::findOrFail($this->selectedProductId);

        // Require variation check if variation exists
        $combination = null;
        if ($product->combinations()->exists()) {
            if (empty($this->selectedCombinationId)) {
                $this->dispatch('toast', message: 'Please select a variation.', type: 'error');
                return;
            }
            $combination = ProductCombination::find($this->selectedCombinationId);
            if (!$combination) {
                $this->dispatch('toast', message: 'Selected variation is invalid.', type: 'error');
                return;
            }
        }

        // Calculate combined base quantity and build list
        $totalBaseQty = 0;
        $itemsToAdd = [];
        $hasQty = false;

        foreach ($this->availableUnits as $unit) {
            $qty = intval($this->unitQuantities[$unit->id] ?? 0);
            if ($qty <= 0) {
                continue;
            }
            $hasQty = true;
            $conversion = (float)$unit->conversion_to_base;
            $baseQty = $qty * $conversion;
            $totalBaseQty += $baseQty;

            $itemsToAdd[] = [
                'unit' => $unit,
                'qty' => $qty,
                'conversion' => $conversion,
                'baseQty' => $baseQty,
            ];
        }

        if (!$hasQty) {
            $this->dispatch('toast', message: 'Please enter quantity greater than 0 for at least one unit.', type: 'error');
            return;
        }

        // Perform stock checks
        $isTracked = $inventoryService->isStockTracked($product, $combination);
        if ($isTracked) {
            $avail = $inventoryService->getAvailableStock($product, $combination);
            
            // Calculate existing base quantity already added in this list for this specific combination
            $existingBaseQty = 0;
            foreach ($this->items as $addedItem) {
                if ($addedItem['product_id'] == $product->id && $addedItem['product_combination_id'] == ($combination ? $combination->id : null)) {
                    $existingBaseQty += $addedItem['base_quantity'];
                }
            }

            if ($avail < ($totalBaseQty + $existingBaseQty)) {
                $this->dispatch('toast', message: 'Insufficient stock for one or more products.', type: 'error');
                return;
            }
        }

        // Add each configured unit item to list
        foreach ($itemsToAdd as $add) {
            $unit = $add['unit'];
            $qty = $add['qty'];
            $baseQty = $add['baseQty'];
            $conversion = $add['conversion'];

            // Compile options text
            $combinationName = 'None';
            $sku = $product->sku;
            if ($combination) {
                $sku = $combination->sku;
                if ($combination->combination_values) {
                    $options = [];
                    foreach ($combination->combination_values as $grp => $val) {
                        $options[] = "{$grp}: {$val}";
                    }
                    $combinationName = implode(', ', $options);
                }
            }

            $this->items[] = [
                'temp_id' => uniqid(),
                'product_id' => $product->id,
                'product_title' => $product->title,
                'product_sku' => $sku,
                'product_combination_id' => $combination ? $combination->id : null,
                'combination_name' => $combinationName,
                'product_unit_id' => $unit->id,
                'unit_name' => $unit->name,
                'unit_short_code' => $unit->short_code,
                'quantity' => $qty,
                'unit_conversion_quantity' => $conversion,
                'base_quantity' => $baseQty,
                'stock_status' => $isTracked ? 'tracked' : 'not_tracked',
                'note' => $this->selectedNote,
            ];
        }

        // Reset selected panel
        $this->selectedProductId = '';
        $this->selectedCombinationId = '';
        $this->unitQuantities = [];
        $this->selectedNote = '';
        $this->availableCombinations = [];
        $this->availableUnits = [];
        
        $this->dispatch('toast', message: 'Items added to transfer list.', type: 'success');
    }

    /**
     * Remove item from transfer list.
     */
    public function removeItem($tempId)
    {
        $this->items = array_values(array_filter($this->items, function ($item) use ($tempId) {
            return $item['temp_id'] !== $tempId;
        }));
    }

    /**
     * Check if any item in the list has insufficient stock.
     */
    public function hasStockErrors(): bool
    {
        foreach ($this->items as $item) {
            if ($item['stock_status'] === 'insufficient') {
                return true;
            }
        }
        return false;
    }

    /**
     * Proceed to next step.
     */
    public function nextStep()
    {
        if ($this->currentStep === 1) {
            $this->validate([
                'retailShopId' => ['required', 'exists:retail_shops,id'],
                'transferDate' => ['required', 'date'],
            ], [
                'retailShopId.required' => 'Please select a retail shop.',
                'transferDate.required' => 'Please select a transfer date.',
            ]);

            $this->currentStep = 2;
        } elseif ($this->currentStep === 2) {
            if (empty($this->items)) {
                $this->dispatch('toast', message: 'Please add at least one manufactured product.', type: 'error');
                return;
            }

            if ($this->hasStockErrors()) {
                $this->dispatch('toast', message: 'Insufficient stock for one or more products.', type: 'error');
                return;
            }

            $this->currentStep = 3;
        }
    }

    /**
     * Go back a step.
     */
    public function prevStep()
    {
        if ($this->currentStep > 1) {
            $this->currentStep--;
        }
    }

    /**
     * Complete the transfer using service.
     */
    public function completeTransfer(ManufacturedProductTransferService $service)
    {
        if ($this->hasStockErrors()) {
            $this->dispatch('toast', message: 'Insufficient stock for one or more products.', type: 'error');
            return;
        }

        try {
            $payload = [
                'retail_shop_id' => $this->retailShopId,
                'transfer_date' => $this->transferDate,
                'notes' => $this->notes,
                'items' => array_map(function ($item) {
                    return [
                        'product_id' => $item['product_id'],
                        'product_combination_id' => $item['product_combination_id'],
                        'product_unit_id' => $item['product_unit_id'],
                        'quantity' => $item['quantity'],
                        'note' => $item['note'],
                    ];
                }, $this->items),
            ];

            $adminUser = auth()->user();
            if (!$adminUser) {
                throw new Exception("Unauthorized session.");
            }

            $this->newlyCreatedTransfer = $service->createCompletedTransfer($adminUser, $payload);
            
            $this->dispatch('toast', message: 'Product transfer completed successfully.', type: 'success');
            $this->dispatch('close-modal', 'create-transfer-modal');
            $this->dispatch('open-modal', 'success-modal');
            $this->resetWizard();
        } catch (Exception $e) {
            $this->dispatch('toast', message: $e->getMessage(), type: 'error');
        }
    }

    /**
     * View Transfer Details.
     */
    public function showDetails($id, ManufacturedProductTransferService $service)
    {
        $transfer = ProductTransfer::findOrFail($id);
        $this->selectedTransfer = $service->getDetail($transfer);
        $this->dispatch('open-modal', 'transfer-details');
    }

    private function resetWizard()
    {
        $this->retailShopId = '';
        $this->transferDate = now()->toDateString();
        $this->notes = '';
        $this->wizardProductSearch = '';
        $this->wizardCategoryFilter = '';
        $this->selectedProductId = '';
        $this->selectedCombinationId = '';
        $this->unitQuantities = [];
        $this->selectedNote = '';
        $this->items = [];
        $this->availableCombinations = [];
        $this->availableUnits = [];
        $this->resetPage('wizardPage');
    }

    public function render(ManufacturedProductTransferService $service)
    {
        $transfers = $service->list([
            'search' => $this->search,
            'retail_shop_id' => $this->filterShop,
            'status' => $this->status,
        ], 10);

        // Fetch paginated list of manufactured products for Step 2
        $productsQuery = Product::where('is_active', true)
            ->where('product_type', 'retail');

        if (!empty($this->wizardProductSearch)) {
            $searchVal = $this->wizardProductSearch;
            $productsQuery->where(function ($q) use ($searchVal) {
                $q->where('title', 'like', "%{$searchVal}%")
                  ->orWhere('sku', 'like', "%{$searchVal}%");
            });
        }

        if (!empty($this->wizardCategoryFilter)) {
            $catId = $this->wizardCategoryFilter;
            $productsQuery->whereHas('categories', function ($q) use ($catId) {
                $q->where('categories.id', $catId);
            });
        }

        $wizardProducts = $productsQuery->orderBy('title')
            ->paginate(4, ['*'], 'wizardPage');

        $categories = Category::ordered()->get();

        return view('livewire.admin.product-transfers.product-transfer-index-page', [
            'transfers' => $transfers,
            'wizardProducts' => $wizardProducts,
            'categories' => $categories,
        ]);
    }
}
