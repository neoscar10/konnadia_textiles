<?php

namespace App\Livewire\Admin\Inventory;

use App\Models\Product;
use App\Models\ProductCombination;
use App\Models\Category;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Illuminate\Support\Facades\DB;

#[Layout('components.admin.layout')]
class InventoryIndexPage extends Component
{
    use WithPagination;

    #[Url(history: true)]
    public $search = '';

    #[Url(history: true)]
    public $filterCategory = '';

    #[Url(history: true)]
    public $filterStock = '';

    // Modals & Adjustments State
    public $adjustingProductId = null;
    public $adjustingCombinationId = null;
    public $adjustingProductTitle = '';
    public $adjustingSku = '';
    public $currentStock = 0;
    
    public $adjustmentType = 'add'; // add, deduct, set
    public $adjustmentQuantity = '';
    public $adjustmentReason = '';

    // Expanded products tracker for variants
    public $expandedProducts = [];

    public ?Product $selectedProductForVariants = null;
    public $combinationStocks = [];

    public function mount()
    {
        // Category list is loaded in template
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedFilterCategory()
    {
        $this->resetPage();
    }

    public function updatedFilterStock()
    {
        $this->resetPage();
    }

    public function toggleExpandProduct($productId)
    {
        if (in_array($productId, $expandedKeys = $this->expandedProducts)) {
            $this->expandedProducts = array_values(array_diff($expandedKeys, [$productId]));
        } else {
            $this->expandedProducts[] = $productId;
        }
    }

    /**
     * Open stock adjustment modal.
     */
    public function openAdjustStockModal($productId, $combinationId = null)
    {
        $this->resetAdjustmentForm();

        $product = Product::findOrFail($productId);
        $this->adjustingProductId = $product->id;
        
        if ($combinationId) {
            $combination = ProductCombination::findOrFail($combinationId);
            $this->adjustingCombinationId = $combination->id;
            
            // Format combination values for display
            $options = [];
            if (is_array($combination->combination_values)) {
                foreach ($combination->combination_values as $key => $val) {
                    $options[] = "{$key}: {$val}";
                }
            }
            $this->adjustingProductTitle = $product->title . ' (' . implode(', ', $options) . ')';
            $this->adjustingSku = $combination->sku ?: $product->sku;
            $this->currentStock = (int) $combination->stock_quantity;
        } else {
            $this->adjustingCombinationId = null;
            $this->adjustingProductTitle = $product->title;
            $this->adjustingSku = $product->sku;
            $this->currentStock = (int) $product->stock_quantity;
        }

        $this->dispatch('open-modal', 'adjust-stock-modal');
    }

    public function saveAdjustment()
    {
        $this->validate([
            'adjustmentQuantity' => 'required|integer|min:0',
        ], [
            'adjustmentQuantity.required' => 'Stock value is required.',
            'adjustmentQuantity.integer' => 'Must be a whole number.',
            'adjustmentQuantity.min' => 'Cannot be negative.',
        ]);

        $qty = (int) $this->adjustmentQuantity;
        
        DB::transaction(function () use ($qty) {
            $product = Product::findOrFail($this->adjustingProductId);
            $product->update(['stock_quantity' => max(0, $qty)]);
        });

        $this->dispatch('toast', message: 'Stock updated successfully.', type: 'success');
        $this->dispatch('close-modal', 'adjust-stock-modal');
        $this->resetAdjustmentForm();
    }

    private function resetAdjustmentForm()
    {
        $this->resetValidation();
        $this->adjustingProductId = null;
        $this->adjustingCombinationId = null;
        $this->adjustingProductTitle = '';
        $this->adjustingSku = '';
        $this->currentStock = 0;
        $this->adjustmentQuantity = '';
        $this->adjustmentType = 'add';
        $this->adjustmentReason = '';
    }

    /**
     * Open manage variants modal.
     */
    public function openManageVariantsModal($productId)
    {
        $this->resetValidation();
        $product = Product::with('combinations')->findOrFail($productId);
        $this->selectedProductForVariants = $product;
        $this->combinationStocks = [];
        
        foreach ($product->combinations as $comb) {
            $this->combinationStocks[$comb->id] = (int) $comb->stock_quantity;
        }

        $this->dispatch('open-modal', 'manage-variants-modal');
    }

    /**
     * Save bulk variant stock updates.
     */
    public function saveVariantStocks()
    {
        $this->validate([
            'combinationStocks.*' => 'required|integer|min:0',
        ], [
            'combinationStocks.*.required' => 'Stock is required.',
            'combinationStocks.*.integer' => 'Must be a whole number.',
            'combinationStocks.*.min' => 'Cannot be negative.',
        ]);

        DB::transaction(function () {
            foreach ($this->combinationStocks as $combId => $stock) {
                ProductCombination::where('id', $combId)->update([
                    'stock_quantity' => (int) $stock
                ]);
            }
            
            if ($this->selectedProductForVariants) {
                $product = Product::findOrFail($this->selectedProductForVariants->id);
                $overallStock = (int) $product->combinations()->sum('stock_quantity');
                $product->update(['stock_quantity' => $overallStock]);
            }
        });

        $this->dispatch('toast', message: 'Variant stock updated successfully.', type: 'success');
        $this->dispatch('close-modal', 'manage-variants-modal');
        $this->selectedProductForVariants = null;
        $this->combinationStocks = [];
    }

    /**
     * Helper to get recursive category descendants.
     */
    protected function getCategoryDescendantIds(int $categoryId): array
    {
        $ids = [$categoryId];
        $children = Category::whereIn('parent_id', $ids)->pluck('id')->all();
        while (!empty($children)) {
            $ids = array_merge($ids, $children);
            $children = Category::whereIn('parent_id', $children)->pluck('id')->all();
        }
        return $ids;
    }

    public function render()
    {
        // 1. Build Stats
        $allProducts = Product::with('combinations')->get();
        
        $totalItems = 0;
        $totalValue = 0;
        $lowStockCount = 0;
        $outOfStockCount = 0;

        foreach ($allProducts as $p) {
            $hasCombinations = $p->combinations->count() > 0;
            $qty = $hasCombinations ? $p->combinations->sum('stock_quantity') : $p->stock_quantity;
            
            $totalItems += $qty;
            $totalValue += ($qty * (float) $p->base_price);
            
            if ($qty == 0) {
                $outOfStockCount++;
            } elseif ($qty < 10) {
                $lowStockCount++;
            }
        }

        // 2. Query Paginated Products
        $query = Product::with(['categories', 'combinations', 'primaryMedia']);

        if (!empty($this->search)) {
            $searchStr = trim($this->search);
            $query->where(function ($q) use ($searchStr) {
                $q->where('title', 'like', "%{$searchStr}%")
                  ->orWhere('sku', 'like', "%{$searchStr}%");
            });
        }

        if (!empty($this->filterCategory)) {
            $catIds = $this->getCategoryDescendantIds((int) $this->filterCategory);
            $query->whereHas('categories', function ($q) use ($catIds) {
                $q->whereIn('categories.id', $catIds);
            });
        }

        if (!empty($this->filterStock)) {
            if ($this->filterStock === 'instock') {
                $query->where('stock_quantity', '>', 10);
            } elseif ($this->filterStock === 'lowstock') {
                $query->where('stock_quantity', '>', 0)->where('stock_quantity', '<=', 10);
            } elseif ($this->filterStock === 'outofstock') {
                $query->where('stock_quantity', 0);
            }
        }

        $products = $query->orderBy('id', 'desc')->paginate(10);

        return view('livewire.admin.inventory.inventory-index-page', [
            'products' => $products,
            'categories' => Category::whereNull('parent_id')->with('children')->get(),
            'stats' => [
                'total_items' => $totalItems,
                'total_value' => $totalValue,
                'low_stock' => $lowStockCount,
                'out_of_stock' => $outOfStockCount,
            ]
        ]);
    }
}
