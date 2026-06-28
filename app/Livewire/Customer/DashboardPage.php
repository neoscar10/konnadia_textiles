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
    public array $dynamicSections = [];
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

        $this->quickAddUnits = collect($detail['units'])->map(function($u) {
            $moq = max(1, $this->quickAddMoq);
            if ($u['level'] === 2) {
                $conversion = (float) ($u['conversion_to_base'] ?? 1.0);
                if ($conversion <= 0) $conversion = 1.0;
                $u['min_qty'] = (int) ceil($moq / $conversion);
            } else {
                $u['min_qty'] = $moq;
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

            $lvl2Qty = (int) floor($moq / $conversion);
            $lvl1Qty = (int) ($moq - ($lvl2Qty * $conversion));

            if ($lvl2Qty > 0) {
                $this->quickAddQueuedItems[$lvl2['id']] = [
                    'unit_id' => $lvl2['id'],
                    'unit_name' => $lvl2['name'],
                    'unit_short_code' => $lvl2['short_code'],
                    'conversion_to_base' => $conversion,
                    'quantity' => $lvl2Qty,
                ];
            }

            if ($lvl1Qty > 0 || $lvl2Qty === 0) {
                $this->quickAddQueuedItems[$lvl1['id']] = [
                    'unit_id' => $lvl1['id'],
                    'unit_name' => $lvl1['name'],
                    'unit_short_code' => $lvl1['short_code'],
                    'conversion_to_base' => 1.0,
                    'quantity' => $lvl1Qty > 0 ? $lvl1Qty : 1,
                ];
            }
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

    public function selectQuickAddVariationValue($groupName, $value, \App\Services\Portal\ProductCatalogService $catalogService)
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

        $this->recalculateQuickAdd(app(\App\Services\Portal\ProductCatalogService::class));
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
                $this->pricePerPiece ?? $this->quickAddPricePerPiece,
                $lvl1Unit,
                $totalPieces,
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

    public function addVariantToCart(\App\Services\Portal\ProductCatalogService $catalogService)
    {
        if (!$this->quickAddIsPurchasable) {
            $this->dispatch('toast', type: 'error', message: 'This variant is currently out of stock.');
            return;
        }

        // If no units queued, automatically add Level 1 unit with MOQ
        if (empty($this->quickAddQueuedItems)) {
            $lvl1 = collect($this->quickAddUnits)->firstWhere('level', 1);
            if ($lvl1) {
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

        $renderService = app(\App\Services\Home\HomeContentRenderService::class);
        $this->dynamicSections = $renderService->getHomeContentForCustomer($user);

        $this->lastUpdatedAt = now()->format('h:i A');
    }

    public function render()
    {
        return view('livewire.customer.dashboard-page')
            ->layoutData(['title' => 'Dashboard']);
    }
}
