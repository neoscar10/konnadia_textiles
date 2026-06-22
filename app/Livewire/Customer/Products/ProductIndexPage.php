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
    public $max_price = 5000;
    public $sort = 'newest';

    public $expandedCategories = [];

    // Modal properties
    public $showQuickAddModal = false;
    public $quickAddProductId = null;
    public $quickAddProduct = null;
    public $quickAddVariations = [];
    public $quickAddSelectedValues = [];
    public $quickAddSelectedUnitId = null;
    public $quickAddUnits = [];
    public $quickAddQty = 1;
    public $quickAddQtyLvl1 = 0;
    public $quickAddQtyLvl2 = 0;
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
        'max_price' => ['except' => 5000],
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

        $lvl1 = collect($this->quickAddUnits)->firstWhere('level', 1);
        $this->quickAddSelectedUnitId = $lvl1 ? $lvl1['id'] : $detail['purchase_defaults']['default_unit_id'];
        $this->quickAddMoq = $detail['purchase_defaults']['minimum_order_quantity'];

        $lvl2 = collect($this->quickAddUnits)->firstWhere('level', 2);
        if ($lvl2) {
            $this->quickAddHasLvl2Unit = true;
            $conversion = (int) $lvl2['conversion_to_base'];
            $this->quickAddQtyLvl2 = floor($this->quickAddMoq / $conversion);
            $this->quickAddQtyLvl1 = $this->quickAddMoq % $conversion;
            $this->quickAddQty = $this->quickAddMoq;
        } else {
            $this->quickAddHasLvl2Unit = false;
            $this->quickAddQtyLvl1 = $this->quickAddMoq;
            $this->quickAddQtyLvl2 = 0;
            $this->quickAddQty = $this->quickAddMoq;
        }

        $this->recalculateQuickAdd($catalogService);
        $this->showQuickAddModal = true;
    }

    public function selectQuickAddVariationValue($groupName, $value, ProductCatalogService $catalogService)
    {
        $this->quickAddSelectedValues[$groupName] = $value;
        $this->recalculateQuickAdd($catalogService);
    }

    public function updatedQuickAddQty(ProductCatalogService $catalogService)
    {
        $this->quickAddQty = max($this->quickAddMoq, (int)$this->quickAddQty);
        $lvl2 = collect($this->quickAddUnits)->firstWhere('level', 2);
        if ($lvl2) {
            $conversion = (int) $lvl2['conversion_to_base'];
            $this->quickAddQtyLvl2 = floor($this->quickAddQty / $conversion);
            $this->quickAddQtyLvl1 = $this->quickAddQty % $conversion;
        } else {
            $this->quickAddQtyLvl1 = $this->quickAddQty;
            $this->quickAddQtyLvl2 = 0;
        }
        $this->recalculateQuickAdd($catalogService);
    }

    public function updatedQuickAddQtyLvl1(ProductCatalogService $catalogService)
    {
        $this->quickAddQtyLvl1 = max(0, (int)$this->quickAddQtyLvl1);
        $this->syncQuickAddDualUnitsToQty();
        $this->recalculateQuickAdd($catalogService);
    }

    public function updatedQuickAddQtyLvl2(ProductCatalogService $catalogService)
    {
        $this->quickAddQtyLvl2 = max(0, (int)$this->quickAddQtyLvl2);
        $this->syncQuickAddDualUnitsToQty();
        $this->recalculateQuickAdd($catalogService);
    }

    protected function syncQuickAddDualUnitsToQty()
    {
        $lvl2 = collect($this->quickAddUnits)->firstWhere('level', 2);
        if ($lvl2) {
            $conversion = (float)$lvl2['conversion_to_base'];
            $totalPieces = ($this->quickAddQtyLvl2 * $conversion) + $this->quickAddQtyLvl1;
            $this->quickAddQty = max($this->quickAddMoq, $totalPieces);
        } else {
            $this->quickAddQty = max($this->quickAddMoq, $this->quickAddQtyLvl1);
        }
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

        // Calculate unit pricing
        $unit = \App\Models\ProductUnit::find($this->quickAddSelectedUnitId);
        if ($unit) {
            $unitPricingService = app(\App\Services\Portal\ProductUnitPricingService::class);
            $estimate = $unitPricingService->calculateLineEstimate(
                $this->quickAddPricePerPiece,
                $unit,
                $this->quickAddQty,
                $this->quickAddProduct->gst_percentage !== null ? (float) $this->quickAddProduct->gst_percentage : null
            );

            $this->quickAddUnitPrice  = $estimate['unit_price'];
            $this->quickAddSubtotal   = $estimate['subtotal'];
            $this->quickAddGstAmount  = $estimate['gst_amount'];
            $this->quickAddTotal      = $estimate['total'];
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

        $user = auth()->user();
        $combination = $catalogService->resolveSelectedCombination($this->quickAddProduct, $this->quickAddSelectedValues);

        try {
            $cartService = app(\App\Services\Cart\CartService::class);
            $cartService->addItem($user, [
                'product_id' => $this->quickAddProductId,
                'combination_id' => $combination?->id,
                'unit_id' => $this->quickAddSelectedUnitId,
                'quantity' => $this->quickAddQty,
                'quantity_lvl1' => $this->quickAddQtyLvl1,
                'quantity_lvl2' => $this->quickAddQtyLvl2,
                'selected_options' => $this->quickAddSelectedValues ?: null,
            ]);

            $this->dispatch('toast', type: 'success', message: 'Variant added to cart successfully.');
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
            if ($cat && $cat->parent_id && !in_array($cat->parent_id, $this->expandedCategories)) {
                $this->expandedCategories[] = $cat->parent_id;
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
