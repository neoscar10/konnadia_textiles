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
    // Slider sections
    public array $recentProducts = [];
    public array $popularProducts = [];
    public array $recentPurchases = [];
    // Legacy alias (keep for any view references to $recommendedProducts)
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
        $this->quickAddHasLvl2Unit = !empty($lvl2);
        
        $selectedUnit = collect($this->quickAddUnits)->firstWhere('id', $this->quickAddSelectedUnitId);
        $conversion = ($selectedUnit && $selectedUnit['level'] === 2) ? (float)$selectedUnit['conversion_to_base'] : 1.0;
        $this->quickAddQty = (int) ceil($this->quickAddMoq / $conversion);

        $this->recalculateQuickAdd($catalogService);
        $this->showQuickAddModal = true;
    }

    public function selectQuickAddVariationValue($groupName, $value, \App\Services\Portal\ProductCatalogService $catalogService)
    {
        $this->quickAddSelectedValues[$groupName] = $value;
        $this->recalculateQuickAdd($catalogService);
    }

    public function updatedQuickAddSelectedUnitId(\App\Services\Portal\ProductCatalogService $catalogService)
    {
        $selectedUnit = collect($this->quickAddUnits)->firstWhere('id', $this->quickAddSelectedUnitId);
        $conversion = ($selectedUnit && $selectedUnit['level'] === 2) ? (float)$selectedUnit['conversion_to_base'] : 1.0;
        $minQty = (int) ceil($this->quickAddMoq / $conversion);
        if ($this->quickAddQty < $minQty) {
            $this->quickAddQty = $minQty;
        }
        $this->recalculateQuickAdd($catalogService);
    }

    public function updatedQuickAddQty(\App\Services\Portal\ProductCatalogService $catalogService)
    {
        $this->quickAddQty = max(1, (int)$this->quickAddQty);
        $this->recalculateQuickAdd($catalogService);
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

        $unit = \App\Models\ProductUnit::find($this->quickAddSelectedUnitId);
        $conversion = $unit ? (float) $unit->conversion_to_base : 1.0;
        $totalPieces = $this->quickAddQty * $conversion;

        // MOQ gate
        if ($totalPieces < $this->quickAddMoq) {
            $lvl2 = collect($this->quickAddUnits)->firstWhere('level', 2);
            if ($lvl2) {
                $conversion = (int) $lvl2['conversion_to_base'];
                $moqBoxes   = (int) ceil($this->quickAddMoq / $conversion);
                $this->dispatch('toast', type: 'error', message:
                    "Minimum order is {$this->quickAddMoq} pieces. "
                    . "Order at least {$moqBoxes} {$lvl2['name']}(s) or {$this->quickAddMoq} individual pieces."
                );
            } else {
                $this->dispatch('toast', type: 'error', message:
                    "Minimum order quantity is {$this->quickAddMoq} pieces."
                );
            }
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
            $this->customer        = $data['customer'];
            $this->credit          = $data['credit'];
            $this->cart            = $data['cart'];
            $this->orders          = $data['orders'];
            $this->recentOrders    = $data['recent_orders'];
            $this->alerts          = $data['alerts'];
            $this->quickActions    = $data['quick_actions'];
            // Slider sections
            $this->recentProducts  = $data['recent_products']   ?? [];
            $this->popularProducts = $data['popular_products']  ?? [];
            $this->recentPurchases = $data['recent_purchases']  ?? [];
            // Legacy
            $this->recommendedProducts = $this->recentProducts;
        }

        $this->lastUpdatedAt = now()->format('h:i A');
    }

    public function render()
    {
        return view('livewire.customer.dashboard-page')
            ->layoutData(['title' => 'Dashboard']);
    }
}
