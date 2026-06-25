<?php

namespace App\Livewire\Customer\Products;

use Livewire\Component;
use Livewire\Attributes\Layout;
use App\Models\Product;
use App\Models\ProductCombination;
use App\Models\ProductUnit;
use App\Services\Portal\ProductCatalogService;
use App\Services\Portal\CustomerPricingService;
use App\Services\Portal\ProductAvailabilityService;
use App\Services\Portal\ProductUnitPricingService;
use App\Services\Cart\CartService;
use Illuminate\Validation\ValidationException;

#[Layout('components.customer.layout')]
class ProductShowPage extends Component
{
    public $slug;
    public $productId;
    public $title;
    public $sku;
    public $brand;
    public $descriptionHtml;
    public $media = [];
    public $categories = [];
    public $breadcrumb = [];
    public $variations = [];
    public $combinations = [];
    public $units = [];
    
    // Selection state
    public $selectedValues = []; // e.g., ['Size' => 'M', 'Color' => 'White']
    public $qty_lvl1 = 0;
    public $qty_lvl2 = 0;
    public $hasLvl2Unit = false;
    public $minimumOrderQuantity = 1;
    
    // Live calculation display
    public $activeImage;
    public $pricePerPiece = 0.0;
    public $effectiveBasePrice = 0.0;
    public $discountPercentage = 0.0;
    
    public $unitPrice = 0.0;
    public $subtotal = 0.0;
    public $gstAmount = 0.0;
    public $total = 0.0;
    
    public $stockLabel = 'In Stock';
    public $stockStatus = 'in_stock';
    public $isPurchasable = true;
    public $currentSku;

    // Tax info from product record
    public ?float $gstPercentage = null;
    public ?string $hsnCode = null;

    public function mount($slug, ProductCatalogService $catalogService)
    {
        $this->slug = $slug;
        $this->loadProduct($catalogService);
    }

    protected function loadProduct(ProductCatalogService $catalogService)
    {
        $user = auth()->user();
        $detail = $catalogService->getProductForCustomer($user, $this->slug);
        
        $this->productId = $detail['id'];
        $this->title = $detail['title'];
        $this->sku = $detail['sku'];
        $this->currentSku = $detail['sku'];
        $this->brand = $detail['brand'];
        $this->descriptionHtml = $detail['description_html'];
        $this->media = $detail['media'];
        $this->categories = $detail['categories'];
        $this->breadcrumb = $detail['breadcrumb'];
        $this->variations = $detail['variations'];
        $this->combinations = $detail['combinations'];
        $this->units = $detail['units'];
        
        $this->activeImage = !empty($this->media) ? $this->media[0]['url'] : 'https://images.unsplash.com/photo-1596755094514-f87e34085b2c?w=800';

        $this->selectedValues = [];
        foreach ($this->variations as $group) {
            $defaultVal = collect($group['values'])->firstWhere('is_default', true) 
                ?? collect($group['values'])->first();
            
            if ($defaultVal) {
                $this->selectedValues[$group['name']] = $defaultVal['value'];
            }
        }

        $this->minimumOrderQuantity = $detail['purchase_defaults']['minimum_order_quantity'];

        $lvl2 = collect($this->units)->firstWhere('level', 2);
        $this->hasLvl2Unit = !empty($lvl2);

        // Default: satisfy MOQ using pieces (lvl1), lvl2 starts at 0
        $this->qty_lvl1 = $this->minimumOrderQuantity;
        $this->qty_lvl2 = 0;

        // Expose tax info for blade display
        $productModel = Product::find($this->productId);
        $this->gstPercentage = $productModel?->gst_percentage !== null ? (float) $productModel->gst_percentage : null;
        $this->hsnCode = $productModel?->hsn_code;

        $this->recalculate($catalogService);
    }

    public function selectVariationValue($groupName, $value, ProductCatalogService $catalogService)
    {
        $this->selectedValues[$groupName] = $value;
        
        // If this variation value has associated media/images, update the active image
        foreach ($this->variations as $group) {
            if ($group['name'] === $groupName) {
                foreach ($group['values'] as $val) {
                    if ($val['value'] === $value && !empty($val['media'])) {
                        $this->activeImage = $val['media'][0]['url'];
                    }
                }
            }
        }

        $this->recalculate($catalogService);
    }

    public function updatedQtyLvl1(ProductCatalogService $catalogService)
    {
        $this->qty_lvl1 = max(0, (int)$this->qty_lvl1);
        $this->recalculate($catalogService);
    }

    public function updatedQtyLvl2(ProductCatalogService $catalogService)
    {
        $this->qty_lvl2 = max(0, (int)$this->qty_lvl2);
        $this->recalculate($catalogService);
    }

    public function decrementQtyLvl1(ProductCatalogService $catalogService)
    {
        $this->qty_lvl1 = max(0, (int)$this->qty_lvl1 - 1);
        $this->recalculate($catalogService);
    }

    public function incrementQtyLvl1(ProductCatalogService $catalogService)
    {
        $this->qty_lvl1 = (int)$this->qty_lvl1 + 1;
        $this->recalculate($catalogService);
    }

    public function decrementQtyLvl2(ProductCatalogService $catalogService)
    {
        $this->qty_lvl2 = max(0, (int)$this->qty_lvl2 - 1);
        $this->recalculate($catalogService);
    }

    public function incrementQtyLvl2(ProductCatalogService $catalogService)
    {
        $this->qty_lvl2 = (int)$this->qty_lvl2 + 1;
        $this->recalculate($catalogService);
    }

    public function recalculate(ProductCatalogService $catalogService)
    {
        $user = auth()->user();
        $product = Product::find($this->productId);
        
        if (!$product) return;

        // Resolve combination
        $combination = $catalogService->resolveSelectedCombination($product, $this->selectedValues);
        
        // Calculate pricing
        $pricingService = app(CustomerPricingService::class);
        $pricing = $pricingService->calculateCustomerPrice($product, $user, $combination);
        
        $this->pricePerPiece = $pricing['customer_price'];
        $this->effectiveBasePrice = $pricing['effective_base_price'];
        $this->discountPercentage = $pricing['discount_percentage'];
        
        $this->currentSku = $combination ? $combination->sku : $this->sku;

        // Compute total pieces from both qty inputs
        $lvl2 = collect($this->units)->firstWhere('level', 2);
        $lvl1 = collect($this->units)->firstWhere('level', 1);
        $conversion = $lvl2 ? (float)$lvl2['conversion_to_base'] : 1.0;
        $totalPieces = (int)(($this->qty_lvl2 * $conversion) + $this->qty_lvl1);

        // Calculate unit pricing using lvl1 unit with total pieces
        $lvl1Unit = $lvl1 ? ProductUnit::find($lvl1['id']) : null;
        if ($lvl1Unit && $totalPieces > 0) {
            $unitPricingService = app(ProductUnitPricingService::class);
            $estimate = $unitPricingService->calculateLineEstimate(
                $this->pricePerPiece,
                $lvl1Unit,
                $totalPieces,
                $product->gst_percentage !== null ? (float) $product->gst_percentage : null
            );

            $this->unitPrice  = $estimate['unit_price'];
            $this->subtotal   = $estimate['subtotal'];
            $this->gstAmount  = $estimate['gst_amount'];
            $this->total      = $estimate['total'];
        } else {
            $this->unitPrice  = 0.0;
            $this->subtotal   = 0.0;
            $this->gstAmount  = 0.0;
            $this->total      = 0.0;
        }

        // Calculate availability
        $availService = app(ProductAvailabilityService::class);
        $availability = $combination 
            ? $availService->getCombinationAvailability($combination)
            : $availService->getProductAvailability($product);
            
        $this->stockLabel = $availability['label'];
        $this->stockStatus = $availability['status'];
        $this->isPurchasable = $availability['is_purchasable'];
    }

    public function addToCart(CartService $cartService, ProductCatalogService $catalogService)
    {
        if (!$this->isPurchasable) {
            $this->dispatch('toast', type: 'error', message: 'This product is currently out of stock.');
            return;
        }

        $lvl2 = collect($this->units)->firstWhere('level', 2);
        $lvl1 = collect($this->units)->firstWhere('level', 1);
        $conversion = $lvl2 ? (float)$lvl2['conversion_to_base'] : 1.0;
        $totalPieces = (int)(($this->qty_lvl2 * $conversion) + $this->qty_lvl1);

        if ($totalPieces < 1) {
            $this->dispatch('toast', type: 'error', message: 'Please enter at least a quantity of 1.');
            return;
        }

        // MOQ gate — total pieces
        if ($totalPieces < $this->minimumOrderQuantity) {
            if ($lvl2) {
                $moqBoxes = (int) ceil($this->minimumOrderQuantity / $conversion);
                $this->dispatch('toast', type: 'error', message:
                    "Minimum order is {$this->minimumOrderQuantity} pieces. "
                    . "Order at least {$moqBoxes} {$lvl2['name']}(s) or {$this->minimumOrderQuantity} individual pieces."
                );
            } else {
                $this->dispatch('toast', type: 'error', message:
                    "Minimum order quantity is {$this->minimumOrderQuantity} pieces."
                );
            }
            return;
        }

        $user = auth()->user();
        $product = Product::with(['variationGroups', 'combinations'])->find($this->productId);

        // Resolve combination from selected variation values
        $combination = $catalogService->resolveSelectedCombination($product, $this->selectedValues);

        // Always use lvl1 unit with total piece count
        $lvl1UnitId = $lvl1 ? $lvl1['id'] : null;

        try {
            $existingItem = null;
            $cart = $cartService->getOrCreateActiveCart($user);
            $existingItem = $cart->items()
                ->where('product_id', $product->id)
                ->where('product_combination_id', $combination?->id)
                ->where('product_unit_id', $lvl1UnitId)
                ->first();

            $cartService->addItem($user, [
                'product_id'       => $this->productId,
                'combination_id'   => $combination?->id,
                'unit_id'          => $lvl1UnitId,
                'quantity'         => $totalPieces,
                'quantity_lvl1'    => (int) $this->qty_lvl1,
                'quantity_lvl2'    => (int) $this->qty_lvl2,
                'selected_options' => $this->selectedValues ?: null,
            ]);

            $message = $existingItem
                ? 'Cart quantity updated successfully.'
                : 'Product added to cart successfully.';

            $this->dispatch('toast', type: 'success', message: $message);
            $this->dispatch('cart-updated', count: $cartService->getCartItemCount($user));
        } catch (ValidationException $e) {
            $firstError = collect($e->errors())->flatten()->first();
            $this->dispatch('toast', type: 'error', message: $firstError);
        }
    }

    public function render()
    {
        return view('livewire.customer.products.product-show-page')
            ->layoutData(['title' => $this->title]);
    }
}
