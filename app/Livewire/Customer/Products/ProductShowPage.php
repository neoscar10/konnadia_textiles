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
    public $selectedUnitId;
    public $qty = 1;
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

        // Pre-select defaults for variation groups
        foreach ($this->variations as $group) {
            $defaultVal = collect($group['values'])->firstWhere('is_default', true) 
                ?? collect($group['values'])->first();
            
            if ($defaultVal) {
                $this->selectedValues[$group['name']] = $defaultVal['value'];
            }
        }

        $this->selectedUnitId = $detail['purchase_defaults']['default_unit_id'];
        $this->minimumOrderQuantity = $detail['purchase_defaults']['minimum_order_quantity'];
        $this->qty = $this->minimumOrderQuantity;

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

    public function updatedSelectedUnitId(ProductCatalogService $catalogService)
    {
        $this->recalculate($catalogService);
    }

    public function updatedQty(ProductCatalogService $catalogService)
    {
        $this->qty = max($this->minimumOrderQuantity, (int)$this->qty);
        $this->recalculate($catalogService);
    }

    public function decrementQty(ProductCatalogService $catalogService)
    {
        $this->qty = max($this->minimumOrderQuantity, (int)$this->qty - 1);
        $this->recalculate($catalogService);
    }

    public function incrementQty(ProductCatalogService $catalogService)
    {
        $this->qty = (int)$this->qty + 1;
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

        // Calculate unit pricing using product's saved GST percentage
        $unit = ProductUnit::find($this->selectedUnitId);
        if ($unit) {
            $unitPricingService = app(ProductUnitPricingService::class);
            $estimate = $unitPricingService->calculateLineEstimate(
                $this->pricePerPiece,
                $unit,
                $this->qty,
                $product->gst_percentage !== null ? (float) $product->gst_percentage : null
            );

            $this->unitPrice  = $estimate['unit_price'];
            $this->subtotal   = $estimate['subtotal'];
            $this->gstAmount  = $estimate['gst_amount'];
            $this->total      = $estimate['total'];
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

        $user = auth()->user();
        $product = Product::with(['variationGroups', 'combinations'])->find($this->productId);

        // Resolve combination from selected variation values
        $combination = $catalogService->resolveSelectedCombination($product, $this->selectedValues);

        try {
            $existingItem = null;
            $cart = $cartService->getOrCreateActiveCart($user);
            $existingItem = $cart->items()
                ->where('product_id', $product->id)
                ->where('product_combination_id', $combination?->id)
                ->where('product_unit_id', $this->selectedUnitId)
                ->first();

            $cartService->addItem($user, [
                'product_id' => $this->productId,
                'combination_id' => $combination?->id,
                'unit_id' => $this->selectedUnitId,
                'quantity' => $this->qty,
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
