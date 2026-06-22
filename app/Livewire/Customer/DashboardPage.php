<?php

namespace App\Livewire\Customer;

use App\Services\Customer\Dashboard\CustomerDashboardService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('components.customer.layout')]
class DashboardPage extends Component
{
    public array $customer = [];
    public array $credit = [];
    public array $cart = [];
    public array $orders = [];
    public array $recentOrders = [];
    public array $alerts = [];
    public array $quickActions = [];
    public array $recommendedProducts = [];
    public string $lastUpdatedAt = '';

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

    public function handleAddClick($productId, \App\Services\Portal\ProductCatalogService $catalogService)
    {
        $user = auth()->user();
        $product = \App\Models\Product::with(['units', 'variationGroups.values.media', 'combinations'])->findOrFail($productId);
        
        $hasVariations = $product->variationGroups()->exists();
        
        if (!$hasVariations) {
            // Add directly to cart
            try {
                $lvl1Unit = $product->units()->where('level', 1)->first();
                $cartService = app(\App\Services\Cart\CartService::class);
                $cartService->addItem($user, [
                    'product_id' => $productId,
                    'unit_id' => $lvl1Unit?->id,
                    'quantity' => $product->minimum_order_quantity ?: 1,
                ]);
                $this->dispatch('toast', type: 'success', message: 'Product added to cart.');
                $this->dispatch('cart-updated', count: $cartService->getCartItemCount($user));
            } catch (\Exception $e) {
                $this->dispatch('toast', type: 'error', message: $e->getMessage());
            }
            return;
        }

        // Open variant modal
        $this->quickAddProductId = $productId;
        $this->quickAddProduct = $product;
        
        $detail = $catalogService->getProductForCustomer($user, $product->slug);
        $this->quickAddVariations = $detail['variations'];
        $this->quickAddUnits = $detail['units'];
        
        // Pre-select defaults
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

    public function selectQuickAddVariationValue($groupName, $value, \App\Services\Portal\ProductCatalogService $catalogService)
    {
        $this->quickAddSelectedValues[$groupName] = $value;
        $this->recalculateQuickAdd($catalogService);
    }

    public function updatedQuickAddQty(\App\Services\Portal\ProductCatalogService $catalogService)
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

    public function updatedQuickAddQtyLvl1(\App\Services\Portal\ProductCatalogService $catalogService)
    {
        $this->quickAddQtyLvl1 = max(0, (int)$this->quickAddQtyLvl1);
        $this->syncQuickAddDualUnitsToQty();
        $this->recalculateQuickAdd($catalogService);
    }

    public function updatedQuickAddQtyLvl2(\App\Services\Portal\ProductCatalogService $catalogService)
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

    public function recalculateQuickAdd(\App\Services\Portal\ProductCatalogService $catalogService)
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
                $this->pricePerPiece ?? $this->quickAddPricePerPiece,
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

    public function addVariantToCart(\App\Services\Portal\ProductCatalogService $catalogService)
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

    /**
     * Mount the component and load dashboard data.
     */
    public function mount(CustomerDashboardService $dashboardService)
    {
        $this->loadDashboardData($dashboardService);
    }

    /**
     * Refresh the dashboard data dynamically.
     */
    public function refreshDashboard(CustomerDashboardService $dashboardService)
    {
        $this->loadDashboardData($dashboardService);
        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Dashboard updated successfully.'
        ]);
    }

    /**
     * Helper to populate properties from the service.
     */
    protected function loadDashboardData(CustomerDashboardService $dashboardService)
    {
        $user = Auth::user();
        if (!$user || !$user->customer) {
            return;
        }

        $data = $dashboardService->getDashboard($user);

        if (!empty($data)) {
            $this->customer = $data['customer'];
            $this->credit = $data['credit'];
            $this->cart = $data['cart'];
            $this->orders = $data['orders'];
            $this->recentOrders = $data['recent_orders'];
            $this->alerts = $data['alerts'];
            $this->quickActions = $data['quick_actions'];
            $this->recommendedProducts = $data['recommended_products'];
        }

        $this->lastUpdatedAt = now()->format('h:i A');
    }

    public function render()
    {
        return view('livewire.customer.dashboard-page')
            ->layoutData(['title' => 'Dashboard']);
    }
}
