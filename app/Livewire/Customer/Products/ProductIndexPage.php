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
    public array $selectedTags = [];

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
        'selectedTags' => ['except' => []],
    ];

    public function handleAddClick($productId, ProductCatalogService $catalogService)
    {
        $user = auth()->user();
        if (!$user) {
            $this->dispatch('toast', type: 'error', message: 'Please log in to purchase products.');
            return;
        }
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

        $this->quickAddUnits = collect($detail['units'])->map(function($u) {
            $moq = max(1, $this->quickAddMoq);
            if ($this->quickAddHasLvl2Unit) {
                if ($u['level'] === 2) {
                    $u['min_qty'] = $moq;
                    $u['is_purchasable'] = true;
                } else {
                    $u['min_qty'] = 0;
                    $u['is_purchasable'] = false;
                }
            } else {
                if ($u['level'] === 1) {
                    $u['min_qty'] = $moq;
                    $u['is_purchasable'] = true;
                } else {
                    $u['min_qty'] = 0;
                    $u['is_purchasable'] = false;
                }
            }
            return $u;
        })->toArray();

        // Prepopulate selection queue by resolving the MOQ into Boxes and/or Pieces
        $this->quickAddQueuedItems = [];
        $moq = max(1, $this->quickAddMoq);
        $lvl1 = collect($this->quickAddUnits)->firstWhere('level', 1);
        $lvl2 = collect($this->quickAddUnits)->firstWhere('level', 2);

        if ($lvl2) {
            $conversion = (float) ($lvl2['conversion_to_base'] ?? 1.0);
            if ($conversion <= 0) $conversion = 1.0;

            $this->quickAddQueuedItems[$lvl2['id']] = [
                'unit_id' => $lvl2['id'],
                'unit_name' => $lvl2['name'],
                'unit_short_code' => $lvl2['short_code'],
                'conversion_to_base' => $conversion,
                'quantity' => $moq,
            ];
        } else {
            if ($lvl1) {
                $this->quickAddQueuedItems[$lvl1['id']] = [
                    'unit_id' => $lvl1['id'],
                    'unit_name' => $lvl1['name'],
                    'unit_short_code' => $lvl1['short_code'],
                    'conversion_to_base' => 1.0,
                    'quantity' => $moq,
                ];
            }
        }

        // Default all quantities to match selection queue (or 0 if not queued)
        $this->quickAddUnitQuantities = [];
        foreach ($this->quickAddUnits as $u) {
            $this->quickAddUnitQuantities[$u['id']] = isset($this->quickAddQueuedItems[$u['id']])
                ? $this->quickAddQueuedItems[$u['id']]['quantity']
                : 0;
        }

        $this->recalculateQuickAdd($catalogService);
        $this->showQuickAddModal = true;
    }

    public function selectQuickAddVariationValue($groupName, $value, ProductCatalogService $catalogService)
    {
        $this->quickAddSelectedValues[$groupName] = $value;
        $this->recalculateQuickAdd($catalogService);
    }

    public function updatedQuickAddUnitQuantities($value, $key)
    {
        $this->updateQuickAddUnitQuantityInQueue((int)$key, (int)$value);
    }

    public function updateQuickAddUnitQuantityInQueue($unitId, $qty)
    {
        $unit = collect($this->quickAddUnits)->firstWhere('id', $unitId);
        if (!$unit) return;

        $qty = max(0, (int)$qty);
        $this->quickAddUnitQuantities[$unitId] = $qty;

        if ($qty > 0) {
            $this->quickAddQueuedItems[$unitId] = [
                'unit_id' => $unitId,
                'unit_name' => $unit['name'],
                'unit_short_code' => $unit['short_code'],
                'conversion_to_base' => (float)$unit['conversion_to_base'],
                'quantity' => $qty,
            ];
        } else {
            unset($this->quickAddQueuedItems[$unitId]);
        }

        $this->recalculateQuickAdd(app(ProductCatalogService::class));
    }

    public function decrementQuickAddUnitQuantity($unitId)
    {
        $curr = (int) ($this->quickAddUnitQuantities[$unitId] ?? 0);
        $this->updateQuickAddUnitQuantityInQueue($unitId, max(0, $curr - 1));
    }

    public function incrementQuickAddUnitQuantity($unitId)
    {
        $curr = (int) ($this->quickAddUnitQuantities[$unitId] ?? 0);
        $this->updateQuickAddUnitQuantityInQueue($unitId, $curr + 1);
    }

    public function removeQuickAddUnitFromQueue($unitId)
    {
        $this->updateQuickAddUnitQuantityInQueue($unitId, 0);
        $this->dispatch('toast', type: 'info', message: 'Removed from selection.');
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
        $user = auth()->user();
        if (!$user) {
            $this->dispatch('toast', type: 'error', message: 'Please log in to purchase products.');
            return;
        }
        if (!$this->quickAddIsPurchasable) {
            $this->dispatch('toast', type: 'error', message: 'This variant is currently out of stock.');
            return;
        }

        // If no units queued, automatically add Level 2 unit with MOQ (if exists), else Level 1
        if (empty($this->quickAddQueuedItems)) {
            $lvl2 = collect($this->quickAddUnits)->firstWhere('level', 2);
            $lvl1 = collect($this->quickAddUnits)->firstWhere('level', 1);
            if ($lvl2) {
                $this->quickAddQueuedItems[$lvl2['id']] = [
                    'unit_id' => $lvl2['id'],
                    'unit_name' => $lvl2['name'],
                    'unit_short_code' => $lvl2['short_code'],
                    'conversion_to_base' => (float)$lvl2['conversion_to_base'],
                    'quantity' => max(1, $this->quickAddMoq),
                ];
            } elseif ($lvl1) {
                $this->quickAddQueuedItems[$lvl1['id']] = [
                    'unit_id' => $lvl1['id'],
                    'unit_name' => $lvl1['name'],
                    'unit_short_code' => $lvl1['short_code'],
                    'conversion_to_base' => 1.0,
                    'quantity' => max(1, $this->quickAddMoq),
                ];
            }
        }

        if (empty($this->quickAddQueuedItems)) {
            $this->dispatch('toast', type: 'error', message: 'Please add at least one unit to the selection first.');
            return;
        }

        // MOQ check
        $lvl2 = collect($this->quickAddUnits)->firstWhere('level', 2);
        if ($lvl2) {
            $totalUnits = 0;
            foreach ($this->quickAddQueuedItems as $item) {
                if ($item['unit_id'] == $lvl2['id']) {
                    $totalUnits += $item['quantity'];
                }
            }
            if ($totalUnits < $this->quickAddMoq) {
                $this->dispatch('toast', type: 'error', message:
                    "Minimum order is {$this->quickAddMoq} {$lvl2['name']}(s). Your current selection is {$totalUnits} {$lvl2['name']}(s)."
                );
                return;
            }
        } else {
            $totalPieces = 0;
            foreach ($this->quickAddQueuedItems as $item) {
                $totalPieces += (int) ($item['quantity'] * $item['conversion_to_base']);
            }
            if ($totalPieces < $this->quickAddMoq) {
                $this->dispatch('toast', type: 'error', message:
                    "Minimum order quantity is {$this->quickAddMoq} pieces. Your current selection total is {$totalPieces} pieces."
                );
                return;
            }
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
        if (in_array($name, ['search', 'category', 'availability', 'min_price', 'max_price', 'sort', 'selectedTags'])) {
            $this->resetPage();
        }
    }

    public function clearFilters()
    {
        $this->reset(['search', 'category', 'availability', 'min_price', 'max_price', 'sort', 'expandedCategories', 'selectedTags']);
        $this->resetPage();
    }

    public function toggleTagFilter(int $tagId): void
    {
        if (in_array($tagId, $this->selectedTags)) {
            $this->selectedTags = array_diff($this->selectedTags, [$tagId]);
        } else {
            $this->selectedTags[] = $tagId;
        }
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
            'tags' => $this->selectedTags,
            'per_page' => 12
        ];

        $products = $catalogService->listProductsForCustomer(auth()->user(), $filters);
        $metadata = $catalogService->getAvailableFilters(auth()->user());
        $tagsList = \App\Models\Tag::orderBy('name')->get();

        return view('livewire.customer.products.product-index-page', [
            'products' => $products,
            'categoriesList' => $metadata['categories'],
            'tagsList' => $tagsList
        ])->layoutData(['title' => 'Products Catalog']);
    }
}
