<?php

namespace App\Services\StockTransfer;

use App\Models\Product;
use App\Models\ProductCombination;
use App\Models\ProductUnit;
use App\Models\ProductTransfer;
use App\Models\ProductTransferItem;
use App\Models\RetailShop;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Exception;

class ManufacturedProductTransferService
{
    protected $inventoryService;
    protected $numberService;

    public function __construct(TransferInventoryService $inventoryService, TransferNumberService $numberService)
    {
        $this->inventoryService = $inventoryService;
        $this->numberService = $numberService;
    }

    /**
     * List product transfers with filters.
     */
    public function list(array $filters = [], int $perPage = 10)
    {
        $query = ProductTransfer::with(['shop', 'createdBy', 'items']);

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where('transfer_number', 'like', "%{$search}%");
        }

        if (!empty($filters['retail_shop_id'])) {
            $query->where('retail_shop_id', $filters['retail_shop_id']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('transfer_date', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('transfer_date', '<=', $filters['date_to']);
        }

        return $query->latest()->paginate($perPage);
    }

    /**
     * Get transfer detail.
     */
    public function getDetail(ProductTransfer $transfer): ProductTransfer
    {
        return $transfer->load(['shop', 'createdBy', 'items.product', 'items.combination', 'items.unit']);
    }

    /**
     * Calculate total items and base quantities for a list of items.
     */
    public function calculateTotals(array $items): array
    {
        $totalItems = count($items);
        $totalQuantityBase = 0;

        foreach ($items as $item) {
            $qty = intval($item['quantity'] ?? 0);
            
            $conversion = 1;
            if (!empty($item['product_unit_id'])) {
                $unit = ProductUnit::find($item['product_unit_id']);
                if ($unit) {
                    $conversion = (float)$unit->conversion_to_base;
                }
            }

            $totalQuantityBase += ($qty * $conversion);
        }

        return [
            'total_items' => $totalItems,
            'total_quantity_base_units' => $totalQuantityBase,
        ];
    }

    /**
     * Create a completed product transfer.
     */
    public function createCompletedTransfer(User $admin, array $payload): ProductTransfer
    {
        // 1. Validate Retail Shop
        $shop = RetailShop::find($payload['retail_shop_id'] ?? null);
        if (!$shop) {
            throw new Exception("Please select a retail shop.");
        }
        if (!$shop->is_active) {
            throw new Exception("The selected retail shop is inactive.");
        }

        // 2. Validate Items Existence
        $payloadItems = $payload['items'] ?? [];
        if (empty($payloadItems)) {
            throw new Exception("Please add at least one manufactured product.");
        }

        return DB::transaction(function () use ($admin, $shop, $payload, $payloadItems) {
            
            // Create transfer header
            $transfer = new ProductTransfer();
            $transfer->transfer_number = $this->numberService->generate();
            $transfer->retail_shop_id = $shop->id;
            $transfer->created_by = $admin->id;
            $transfer->status = 'completed';
            $transfer->transfer_date = $payload['transfer_date'] ?? now()->toDateString();
            $transfer->notes = $payload['notes'] ?? null;
            $transfer->completed_at = now();
            $transfer->save();

            $totalQuantityBaseUnits = 0;
            $totalItemsCount = 0;

            foreach ($payloadItems as $itemData) {
                // Fetch product
                $product = Product::find($itemData['product_id'] ?? null);
                if (!$product) {
                    throw new Exception("Product not found.");
                }
                if (!$product->is_active) {
                    throw new Exception("Selected product is not active.");
                }
                if ($product->product_type !== 'retail') {
                    throw new Exception("Only manufactured products can be transferred.");
                }

                // Check combination
                $combination = null;
                if ($product->combinations()->exists()) {
                    if (empty($itemData['product_combination_id'])) {
                        throw new Exception("Please select a variation for product: {$product->title}.");
                    }
                    $combination = ProductCombination::where('product_id', $product->id)
                        ->find($itemData['product_combination_id']);
                    if (!$combination) {
                        throw new Exception("Selected product combination is not valid.");
                    }
                }

                // Check unit
                $unit = null;
                $conversionRate = 1.0;
                $unitName = null;
                $unitShortCode = null;

                if (!empty($itemData['product_unit_id'])) {
                    $unit = ProductUnit::where('product_id', $product->id)->find($itemData['product_unit_id']);
                    if (!$unit) {
                        throw new Exception("Selected unit is not valid for this product.");
                    }
                    $conversionRate = (float)$unit->conversion_to_base;
                    $unitName = $unit->name;
                    $unitShortCode = $unit->short_code;
                } else {
                    // Use base unit defaults if available, otherwise piece
                    $baseUnit = ProductUnit::where('product_id', $product->id)->where('level', 1)->first();
                    if ($baseUnit) {
                        $unitName = $baseUnit->name;
                        $unitShortCode = $baseUnit->short_code;
                    } else {
                        $unitName = 'Piece';
                        $unitShortCode = 'pcs';
                    }
                }

                $quantity = intval($itemData['quantity'] ?? 0);
                if ($quantity <= 0) {
                    throw new Exception("Quantity must be greater than zero.");
                }

                $baseQuantity = $quantity * $conversionRate;

                // Validate stock sufficiency
                $isTracked = $this->inventoryService->isStockTracked($product, $combination);
                if ($isTracked) {
                    $availableBaseStock = $this->inventoryService->getAvailableStock($product, $combination);
                    if ($availableBaseStock < $baseQuantity) {
                        throw new Exception("Insufficient stock for one or more products.");
                    }
                }

                // Create transfer item
                $transferItem = new ProductTransferItem([
                    'product_id' => $product->id,
                    'product_combination_id' => $combination ? $combination->id : null,
                    'product_unit_id' => $unit ? $unit->id : null,
                    'product_title' => $product->title,
                    'product_sku' => $combination ? $combination->sku : $product->sku,
                    'selected_options' => $combination ? $combination->combination_values : null,
                    'unit_name' => $unitName,
                    'unit_short_code' => $unitShortCode,
                    'unit_conversion_quantity' => $conversionRate,
                    'quantity' => $quantity,
                    'base_quantity' => $baseQuantity,
                    'stock_tracked' => $isTracked,
                    'note' => $itemData['note'] ?? null,
                ]);

                $transfer->items()->save($transferItem);

                $totalQuantityBaseUnits += $baseQuantity;
                $totalItemsCount++;
            }

            // Update transfer totals
            $transfer->total_items = $totalItemsCount;
            $transfer->total_quantity_base_units = $totalQuantityBaseUnits;
            $transfer->save();

            // Deduct inventory
            $this->inventoryService->deductStock($transfer);

            return $transfer;
        });
    }
}
