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
    public $qty = 0; // Keep for test compatibility
    public array $queuedItems = [];
    public array $unitQuantities = [];
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

        $lvl2 = collect($detail['units'])->firstWhere('level', 2);
        $this->hasLvl2Unit = !empty($lvl2);

        $this->units = collect($detail['units'])->map(function($u) {
            $moq = max(1, $this->minimumOrderQuantity);
            if ($this->hasLvl2Unit) {
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
        $this->queuedItems = [];
        $moq = max(1, $this->minimumOrderQuantity);
        $lvl1 = collect($this->units)->firstWhere('level', 1);
        $lvl2 = collect($this->units)->firstWhere('level', 2);

        if ($lvl2) {
            $conversion = (float) ($lvl2['conversion_to_base'] ?? 1.0);
            if ($conversion <= 0) $conversion = 1.0;

            $this->queuedItems[$lvl2['id']] = [
                'unit_id' => $lvl2['id'],
                'unit_name' => $lvl2['name'],
                'unit_short_code' => $lvl2['short_code'],
                'conversion_to_base' => $conversion,
                'quantity' => $moq,
            ];
        } else {
            if ($lvl1) {
                $this->queuedItems[$lvl1['id']] = [
                    'unit_id' => $lvl1['id'],
                    'unit_name' => $lvl1['name'],
                    'unit_short_code' => $lvl1['short_code'],
                    'conversion_to_base' => 1.0,
                    'quantity' => $moq,
                ];
            }
        }

        // Prepopulate quantities for each unit input fields
        $this->unitQuantities = [];
        foreach ($this->units as $u) {
            $this->unitQuantities[$u['id']] = isset($this->queuedItems[$u['id']])
                ? $this->queuedItems[$u['id']]['quantity']
                : 0;
        }

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

    public function updatedUnitQuantities($value, $key)
    {
        $this->updateUnitQuantityInQueue((int)$key, (int)$value);
    }

    public function updateUnitQuantityInQueue($unitId, $qty)
    {
        $unit = collect($this->units)->firstWhere('id', $unitId);
        if (!$unit) return;

        $qty = max(0, (int)$qty);
        $this->unitQuantities[$unitId] = $qty;

        if ($qty > 0) {
            $this->queuedItems[$unitId] = [
                'unit_id' => $unitId,
                'unit_name' => $unit['name'],
                'unit_short_code' => $unit['short_code'],
                'conversion_to_base' => (float)$unit['conversion_to_base'],
                'quantity' => $qty,
            ];
        } else {
            unset($this->queuedItems[$unitId]);
        }

        $this->recalculate(app(ProductCatalogService::class));
    }

    public function decrementUnitQuantity($unitId)
    {
        $curr = (int) ($this->unitQuantities[$unitId] ?? 0);
        $this->updateUnitQuantityInQueue($unitId, max(0, $curr - 1));
    }

    public function incrementUnitQuantity($unitId)
    {
        $curr = (int) ($this->unitQuantities[$unitId] ?? 0);
        $this->updateUnitQuantityInQueue($unitId, $curr + 1);
    }

    public function removeUnitFromQueue($unitId)
    {
        $this->updateUnitQuantityInQueue($unitId, 0);
        $this->dispatch('toast', type: 'info', message: 'Removed from selection.');
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

        // Compute total pieces from queued items
        $totalPieces = 0;
        foreach ($this->queuedItems as $item) {
            $totalPieces += (int) ($item['quantity'] * $item['conversion_to_base']);
        }

        // Calculate estimate pricing using lvl1 unit with total pieces
        $lvl1 = collect($this->units)->firstWhere('level', 1);
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
        $user = auth()->user();
        if (!$user) {
            $this->dispatch('toast', type: 'error', message: 'Please log in to purchase products.');
            return;
        }
        if (!$this->isPurchasable) {
            $this->dispatch('toast', type: 'error', message: 'This product is currently out of stock.');
            return;
        }

        // If no units queued, automatically add Level 2 unit with MOQ (if exists), else Level 1
        if (empty($this->queuedItems)) {
            $lvl2 = collect($this->units)->firstWhere('level', 2);
            $lvl1 = collect($this->units)->firstWhere('level', 1);
            if ($lvl2) {
                $this->queuedItems[$lvl2['id']] = [
                    'unit_id' => $lvl2['id'],
                    'unit_name' => $lvl2['name'],
                    'unit_short_code' => $lvl2['short_code'],
                    'conversion_to_base' => (float)$lvl2['conversion_to_base'],
                    'quantity' => max(1, $this->minimumOrderQuantity),
                ];
            } elseif ($lvl1) {
                $this->queuedItems[$lvl1['id']] = [
                    'unit_id' => $lvl1['id'],
                    'unit_name' => $lvl1['name'],
                    'unit_short_code' => $lvl1['short_code'],
                    'conversion_to_base' => 1.0,
                    'quantity' => max(1, $this->minimumOrderQuantity),
                ];
            }
        }

        // Handle backward compatibility for test suite setting qty
        if ($this->qty > 0) {
            $lvl2 = collect($this->units)->firstWhere('level', 2);
            $lvl1 = collect($this->units)->firstWhere('level', 1);
            if ($lvl2) {
                $this->queuedItems = [
                    $lvl2['id'] => [
                        'unit_id' => $lvl2['id'],
                        'unit_name' => $lvl2['name'],
                        'unit_short_code' => $lvl2['short_code'],
                        'conversion_to_base' => (float)$lvl2['conversion_to_base'],
                        'quantity' => $this->qty,
                    ]
                ];
            } elseif ($lvl1) {
                $this->queuedItems = [
                    $lvl1['id'] => [
                        'unit_id' => $lvl1['id'],
                        'unit_name' => $lvl1['name'],
                        'unit_short_code' => $lvl1['short_code'],
                        'conversion_to_base' => 1.0,
                        'quantity' => $this->qty,
                    ]
                ];
            }
        }

        if (empty($this->queuedItems)) {
            $this->dispatch('toast', type: 'error', message: 'Please add at least one unit to the selection first.');
            return;
        }

        // MOQ check
        $lvl2 = collect($this->units)->firstWhere('level', 2);
        if ($lvl2) {
            $totalUnits = 0;
            foreach ($this->queuedItems as $item) {
                if ($item['unit_id'] == $lvl2['id']) {
                    $totalUnits += $item['quantity'];
                }
            }
            if ($totalUnits < $this->minimumOrderQuantity) {
                $this->dispatch('toast', type: 'error', message:
                    "Minimum order is {$this->minimumOrderQuantity} {$lvl2['name']}(s). Your current selection is {$totalUnits} {$lvl2['name']}(s)."
                );
                return;
            }
        } else {
            $totalPieces = 0;
            foreach ($this->queuedItems as $item) {
                $totalPieces += (int) ($item['quantity'] * $item['conversion_to_base']);
            }
            if ($totalPieces < $this->minimumOrderQuantity) {
                $this->dispatch('toast', type: 'error', message:
                    "Minimum order quantity is {$this->minimumOrderQuantity} pieces. Your current selection total is {$totalPieces} pieces."
                );
                return;
            }
        }

        $user = auth()->user();
        $product = Product::with(['variationGroups', 'combinations'])->find($this->productId);
        $combination = $catalogService->resolveSelectedCombination($product, $this->selectedValues);

        try {
            // Add each queued item as a separate cart item
            foreach ($this->queuedItems as $item) {
                $cartService->addItem($user, [
                    'product_id'          => $this->productId,
                    'combination_id'      => $combination?->id,
                    'unit_id'             => $item['unit_id'],
                    'quantity'            => $item['quantity'],
                    'selected_options'    => $this->selectedValues ?: null,
                    'skip_moq_validation' => true,
                ]);
            }

            $this->dispatch('toast', type: 'success', message: 'Product added to cart successfully.');
            $this->dispatch('cart-updated', count: $cartService->getCartItemCount($user));
            
            // Clear queue and reset
            $this->queuedItems = [];
            $this->qty = 0;
            $this->recalculate($catalogService);
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
