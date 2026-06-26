<?php

namespace App\Livewire\Customer\Products;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use App\Services\Portal\ProductCatalogService;

#[Layout('components.customer.layout')]
class ProductIndexPage extends Component
{
    use WithPagination;

    public $search = '';
    public $category = '';
    public $availability = 'all';
    public $min_price = 0;
    public $max_price = 10000;
    public $sort = 'newest';

    public $expandedCategories = [];

    // Modal properties
    public $showQuickAddModal = false;
    public $quickAddProductId = null;
    public $quickAddProduct = null;
    public $quickAddVariations = [];
    public $quickAddSelectedValues = [];
    public $quickAddUnits = [];
    public array $quickAddQueuedItems = [];
    public array $quickAddUnitQuantities = [];
    public $quickAddHasLvl2Unit = false;
    public $quickAddMoq = 1;

    // Estimate variables
    public $quickAddUnitPrice = 0.0;
    public $quickAddSubtotal = 0.0;
    public $quickAddGstAmount = 0.0;
    public $quickAddTotal = 0.0;
    public $quickAddPricePerPiece = 0.0;
    public $quickAddEffectiveBasePrice = 0.0;
    public $quickAddDiscountPercentage = 0.0;
    public $quickAddStockLabel = '';
    public $quickAddStockStatus = '';
    public $quickAddIsPurchasable = true;

    protected $queryString = [
        'search' => ['except' => ''],
        'category' => ['except' => ''],
        'availability' => ['except' => 'all'],
        'min_price' => ['except' => 0],
        'max_price' => ['except' => 10000],
        'sort' => ['except' => 'newest'],
    ];

    public function handleAddClick($productId, ProductCatalogService $catalogService)
    {
        $user = auth()->user();
        $product = \App\Models\Product::with(['units', 'variationGroups.values.media', 'combinations'])->findOrFail($productId);

        // Always open the modal so the user can confirm quantity,
        // even if the product has no variant groups.
        $this->quickAddProductId = $productId;
        $this->quickAddProduct = $product;
        $this->quickAddSelectedValues = [];

        $detail = $catalogService->getProductForCustomer($user, $product->slug);
        $this->quickAddVariations = $detail['variations'];
        $this->quickAddUnits = $detail['units'];

        // Pre-select defaults for any variation groups
        foreach ($this->quickAddVariations as $group) {
            $defaultVal = collect($group['values'])->firstWhere('is_default', true)
                ?? collect($group['values'])->first();
            if ($defaultVal) {
                $this->quickAddSelectedValues[$group['name']] = $defaultVal['value'];
            }
        }

        $this->quickAddMoq = $detail['purchase_defaults']['minimum_order_quantity'];

        $lvl2 = collect($this->quickAddUnits)->firstWhere('level', 2);
        $this->quickAddHasLvl2Unit = !empty($lvl2);

        // Default all quantities to 0
        $this->quickAddUnitQuantities = [];
        $this->quickAddQueuedItems = [];
        foreach ($this->quickAddUnits as $u) {
            $this->quickAddUnitQuantities[$u['id']] = 0;
        }

        $this->recalculateQuickAdd($catalogService);
        $this->showQuickAddModal = true;
    }

    public function selectQuickAddVariationValue($groupName, $value, ProductCatalogService $catalogService)
    {
        $this->quickAddSelectedValues[$groupName] = $value;
        $this->recalculateQuickAdd($catalogService);
    }

    public function decrementQuickAddUnitQuantity($unitId)
    {
        $curr = (int) ($this->quickAddUnitQuantities[$unitId] ?? 0);
        $this->quickAddUnitQuantities[$unitId] = max(0, $curr - 1);
    }

    public function incrementQuickAddUnitQuantity($unitId)
    {
        $curr = (int) ($this->quickAddUnitQuantities[$unitId] ?? 0);
        $this->quickAddUnitQuantities[$unitId] = $curr + 1;
    }

    public function addQuickAddUnitToQueue($unitId)
    {
        $qty = (int) ($this->quickAddUnitQuantities[$unitId] ?? 0);
        if ($qty < 1) {
            $this->dispatch('toast', type: 'error', message: 'Please enter a quantity greater than 0.');
            return;
        }

        $unit = collect($this->quickAddUnits)->firstWhere('id', $unitId);
        if (!$unit) return;

        // Add or update in queuedItems
        $this->quickAddQueuedItems[$unitId] = [
            'unit_id' => $unitId,
            'unit_name' => $unit['name'],
            'unit_short_code' => $unit['short_code'],
            'conversion_to_base' => (float)$unit['conversion_to_base'],
            'quantity' => $qty,
        ];

        // Reset input quantity to 0
        $this->quickAddUnitQuantities[$unitId] = 0;

        $this->dispatch('toast', type: 'success', message: "Added {$qty} {$unit['name']}(s) to selection.");
        
        $this->recalculateQuickAdd(app(ProductCatalogService::class));
    }

    public function removeQuickAddUnitFromQueue($unitId)
    {
        unset($this->quickAddQueuedItems[$unitId]);
        $this->dispatch('toast', type: 'info', message: 'Removed from selection.');
        $this->recalculateQuickAdd(app(ProductCatalogService::class));
    }

    public function recalculateQuickAdd(ProductCatalogService $catalogService)
    {
        if (!$this->quickAddProduct) return;

        $user = auth()->user();
        
        // Resolve combination
        $combination = $catalogService->resolveSelectedCombination($this->quickAddProduct, $this->quickAddSelectedValues);
        
        // Calculate pricing
        $pricingService = app(\App\Services\Portal\CustomerPricingService::class);
        $pricing = $pricingService->calculateCustomerPrice($this->quickAddProduct, $user, $combination);
        
        $this->quickAddPricePerPiece = $pricing['customer_price'];
        $this->quickAddEffectiveBasePrice = $pricing['effective_base_price'];
        $this->quickAddDiscountPercentage = $pricing['discount_percentage'];

        // Compute total pieces from queued items
        $totalPieces = 0;
        foreach ($this->quickAddQueuedItems as $item) {
            $totalPieces += (int) ($item['quantity'] * $item['conversion_to_base']);
        }

        // Estimate based on lvl1 unit (piece-level) with total piece quantity
        $lvl1 = collect($this->quickAddUnits)->firstWhere('level', 1);
        $lvl1Unit = $lvl1 ? \App\Models\ProductUnit::find($lvl1['id']) : null;
        if ($lvl1Unit && $totalPieces > 0) {
            $unitPricingService = app(\App\Services\Portal\ProductUnitPricingService::class);
            $estimate = $unitPricingService->calculateLineEstimate(
                $this->quickAddPricePerPiece,
                $lvl1Unit,
                (int) $totalPieces,
                $this->quickAddProduct->gst_percentage !== null ? (float) $this->quickAddProduct->gst_percentage : null
            );

            $this->quickAddUnitPrice  = $estimate['unit_price'];
            $this->quickAddSubtotal   = $estimate['subtotal'];
            $this->quickAddGstAmount  = $estimate['gst_amount'];
            $this->quickAddTotal      = $estimate['total'];
        } else {
            $this->quickAddUnitPrice  = 0.0;
            $this->quickAddSubtotal   = 0.0;
            $this->quickAddGstAmount  = 0.0;
            $this->quickAddTotal      = 0.0;
        }

        // Calculate availability
        $availService = app(\App\Services\Portal\ProductAvailabilityService::class);
        $availability = $combination 
            ? $availService->getCombinationAvailability($combination)
            : $availService->getProductAvailability($this->quickAddProduct);
            
        $this->quickAddStockLabel = $availability['label'];
        $this->quickAddStockStatus = $availability['status'];
        $this->quickAddIsPurchasable = $availability['is_purchasable'];
    }

    public function addVariantToCart(ProductCatalogService $catalogService)
    {
        if (!$this->quickAddIsPurchasable) {
            $this->dispatch('toast', type: 'error', message: 'This variant is currently out of stock.');
            return;
        }

        if (empty($this->quickAddQueuedItems)) {
            $this->dispatch('toast', type: 'error', message: 'Please add at least one unit to the selection first.');
            return;
        }

        // MOQ check on total pieces in queue
        $totalPieces = 0;
        foreach ($this->quickAddQueuedItems as $item) {
            $totalPieces += (int) ($item['quantity'] * $item['conversion_to_base']);
        }

        if ($totalPieces < $this->quickAddMoq) {
            $lvl2 = collect($this->quickAddUnits)->firstWhere('level', 2);
            if ($lvl2) {
                $moqBoxes = (int) ceil($this->quickAddMoq / (float)$lvl2['conversion_to_base']);
                $this->dispatch('toast', type: 'error', message:
                    "Minimum order is {$this->quickAddMoq} pieces. "
                    . "Your current selection total is {$totalPieces} pieces. "
                    . "Please select at least {$moqBoxes} {$lvl2['name']}(s) or {$this->quickAddMoq} Pieces."
                );
            } else {
                $this->dispatch('toast', type: 'error', message:
                    "Minimum order quantity is {$this->quickAddMoq} pieces. Your current selection total is {$totalPieces} pieces."
                );
            }
            return;
        }

        $user = auth()->user();
        $combination = $catalogService->resolveSelectedCombination($this->quickAddProduct, $this->quickAddSelectedValues);

        try {
            $cartService = app(\App\Services\Cart\CartService::class);
            // Add each queued item as a separate cart item
            foreach ($this->quickAddQueuedItems as $item) {
                $cartService->addItem($user, [
                    'product_id'          => $this->quickAddProductId,
                    'combination_id'      => $combination?->id,
                    'unit_id'             => $item['unit_id'],
                    'quantity'            => $item['quantity'],
                    'selected_options'    => $this->quickAddSelectedValues ?: null,
                    'skip_moq_validation' => true,
                ]);
            }

            $this->dispatch('toast', type: 'success', message: 'Product added to cart successfully.');
            $this->dispatch('cart-updated', count: $cartService->getCartItemCount($user));
            $this->showQuickAddModal = false;
        } catch (\Exception $e) {
            $this->dispatch('toast', type: 'error', message: $e->getMessage());
        }
    }

    public function toggleCategory($categoryId)
    {
        if (in_array($categoryId, $this->expandedCategories)) {
            $this->expandedCategories = array_diff($this->expandedCategories, [$categoryId]);
        } else {
            $this->expandedCategories[] = $categoryId;
        }
    }

    public function selectCategory($categoryId)
    {
        $this->category = $categoryId;
        $this->resetPage();
        $this->expandParentIfNeeded($categoryId);
    }

    protected function expandParentIfNeeded($categoryId)
    {
        if ($categoryId) {
            $cat = \App\Models\Category::find($categoryId);
            $curr = $cat;
            while ($curr && $curr->parent_id) {
                if (!in_array($curr->parent_id, $this->expandedCategories)) {
                    $this->expandedCategories[] = $curr->parent_id;
                }
                $curr = $curr->parent;
            }
        }
    }

    public function updatedCategory($value)
    {
        $this->expandParentIfNeeded($value);
        $this->resetPage();
    }

    public function updating($name)
    {
        if (in_array($name, ['search', 'category', 'availability', 'min_price', 'max_price', 'sort'])) {
            $this->resetPage();
        }
    }

    public function clearFilters()
    {
        $this->reset(['search', 'category', 'availability', 'min_price', 'max_price', 'sort', 'expandedCategories']);
        $this->resetPage();
    }

    public function render(ProductCatalogService $catalogService)
    {
        $this->expandParentIfNeeded($this->category);

        $filters = [
            'search' => $this->search,
            'category' => $this->category,
            'availability' => $this->availability,
            'min_price' => $this->min_price,
            'max_price' => $this->max_price,
            'sort' => $this->sort,
            'per_page' => 12
        ];

        $products = $catalogService->listProductsForCustomer(auth()->user(), $filters);
        $metadata = $catalogService->getAvailableFilters(auth()->user());

        return view('livewire.customer.products.product-index-page', [
            'products' => $products,
            'categoriesList' => $metadata['categories']
        ])->layoutData(['title' => 'Products Catalog']);
    }
}
