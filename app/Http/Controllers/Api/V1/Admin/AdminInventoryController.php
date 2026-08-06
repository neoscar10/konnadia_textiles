<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductCombination;
use App\Models\Category;
use App\Http\Requests\Api\V1\Admin\AdjustStockRequest;
use App\Http\Requests\Api\V1\Admin\UpdateVariantStocksRequest;
use App\Http\Resources\Api\V1\AdminInventoryResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class AdminInventoryController extends Controller
{
    /**
     * Get inventory dashboard statistics & metrics.
     */
    public function stats(): JsonResponse
    {
        $allProducts = Product::with('combinations')->get();

        $totalItems = 0;
        $totalValue = 0.0;
        $lowStockCount = 0;
        $outOfStockCount = 0;

        foreach ($allProducts as $p) {
            $hasCombinations = $p->combinations->count() > 0;
            $qty = $hasCombinations ? $p->combinations->sum('stock_quantity') : (int) $p->stock_quantity;

            $totalItems += $qty;
            $totalValue += ($qty * (float) $p->base_price);

            if ($qty == 0) {
                $outOfStockCount++;
            } elseif ($qty <= 10) {
                $lowStockCount++;
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'total_items' => $totalItems,
                'formatted_total_items' => number_format($totalItems),
                'total_value' => round($totalValue, 2),
                'formatted_total_value' => '₹' . number_format($totalValue, 2),
                'low_stock' => $lowStockCount,
                'out_of_stock' => $outOfStockCount,
            ]
        ]);
    }

    /**
     * Get paginated inventory stock list with search and stock status filters.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Product::with(['categories', 'combinations', 'primaryMedia', 'media']);

        if ($request->filled('search')) {
            $searchStr = trim($request->query('search'));
            $query->where(function ($q) use ($searchStr) {
                $q->where('title', 'like', "%{$searchStr}%")
                  ->orWhere('sku', 'like', "%{$searchStr}%");
            });
        }

        if ($request->filled('category_id')) {
            $catId = (int) $request->query('category_id');
            $catIds = $this->getCategoryDescendantIds($catId);
            $query->whereHas('categories', function ($q) use ($catIds) {
                $q->whereIn('categories.id', $catIds);
            });
        }

        if ($request->filled('tag_id')) {
            $tagId = (int) $request->query('tag_id');
            $query->whereHas('tags', function ($q) use ($tagId) {
                $q->where('tags.id', $tagId);
            });
        }

        if ($request->filled('status') && $request->query('status') !== 'all') {
            $statusVal = strtolower((string)$request->query('status'));
            if (in_array($statusVal, ['active', '1', 'true'], true)) {
                $query->where('is_active', true);
            } elseif (in_array($statusVal, ['inactive', '0', 'false'], true)) {
                $query->where('is_active', false);
            }
        }

        if ($request->filled('product_type') && $request->query('product_type') !== 'all') {
            $query->where('product_type', $request->query('product_type'));
        }

        if ($request->filled('stock_status') && $request->query('stock_status') !== 'all') {
            $rawStatus = $request->query('stock_status');
            $status = strtolower(str_replace('_', '', $rawStatus)); // instock, lowstock, outofstock

            if ($status === 'instock') {
                $query->where(function ($q) {
                    $q->where('stock_quantity', '>', 10)
                      ->orWhereHas('combinations', function ($sq) {
                          $sq->where('stock_quantity', '>', 10);
                      });
                });
            } elseif ($status === 'lowstock') {
                $query->where(function ($q) {
                    $q->where(function ($sq1) {
                        $sq1->where('stock_quantity', '>', 0)->where('stock_quantity', '<=', 10);
                    })
                    ->orWhereHas('combinations', function ($sq2) {
                        $sq2->where('stock_quantity', '>', 0)->where('stock_quantity', '<=', 10);
                    });
                });
            } elseif ($status === 'outofstock') {
                $query->where(function ($q) {
                    $q->where(function ($sq1) {
                        $sq1->whereNull('stock_quantity')->orWhere('stock_quantity', '<=', 0);
                    })
                    ->whereDoesntHave('combinations', function ($sq2) {
                        $sq2->where('stock_quantity', '>', 0);
                    });
                });
            }
        }

        $perPage = (int) $request->query('per_page', 10);
        $paginator = $query->orderBy('id', 'desc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => AdminInventoryResource::collection($paginator->getCollection()),
            'pagination' => [
                'total' => $paginator->total(),
                'count' => $paginator->count(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'total_pages' => $paginator->lastPage(),
            ]
        ]);
    }

    /**
     * Get single product inventory details.
     */
    public function show(int $id): JsonResponse
    {
        $product = Product::with(['categories', 'combinations', 'primaryMedia', 'media'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => new AdminInventoryResource($product),
        ]);
    }

    /**
     * Adjust stock for a single product or a specific product variant.
     */
    public function adjustStock(AdjustStockRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $productId = (int) $validated['product_id'];
        $combinationId = isset($validated['combination_id']) ? (int) $validated['combination_id'] : null;
        $type = $validated['adjustment_type'];
        $qty = (int) $validated['quantity'];

        $updatedInfo = DB::transaction(function () use ($productId, $combinationId, $type, $qty) {
            $product = Product::findOrFail($productId);

            if ($combinationId) {
                $combination = ProductCombination::where('product_id', $productId)->findOrFail($combinationId);
                $current = (int) $combination->stock_quantity;

                if ($type === 'set') {
                    $newQty = $qty;
                } elseif ($type === 'add') {
                    $newQty = $current + $qty;
                } else { // deduct
                    $newQty = max(0, $current - $qty);
                }

                $combination->update(['stock_quantity' => $newQty]);

                // Recalculate main product total stock sum
                $totalStock = (int) $product->combinations()->sum('stock_quantity');
                $product->update(['stock_quantity' => $totalStock]);

                return [
                    'product_id' => $product->id,
                    'combination_id' => $combination->id,
                    'new_stock_quantity' => $newQty,
                    'product_total_stock' => $totalStock,
                ];
            } else {
                $current = (int) $product->stock_quantity;

                if ($type === 'set') {
                    $newQty = $qty;
                } elseif ($type === 'add') {
                    $newQty = $current + $qty;
                } else { // deduct
                    $newQty = max(0, $current - $qty);
                }

                $product->update(['stock_quantity' => $newQty]);

                return [
                    'product_id' => $product->id,
                    'combination_id' => null,
                    'new_stock_quantity' => $newQty,
                    'product_total_stock' => $newQty,
                ];
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Stock adjusted successfully.',
            'data' => $updatedInfo,
        ]);
    }

    /**
     * Update stock quantities for all variants of a product in bulk.
     */
    public function updateVariantStocks(UpdateVariantStocksRequest $request, int $productId): JsonResponse
    {
        $product = Product::findOrFail($productId);
        $validated = $request->validated();

        DB::transaction(function () use ($product, $validated) {
            foreach ($validated['variant_stocks'] as $variant) {
                ProductCombination::where('product_id', $product->id)
                    ->where('id', $variant['combination_id'])
                    ->update(['stock_quantity' => (int) $variant['stock_quantity']]);
            }

            $overallStock = (int) $product->combinations()->sum('stock_quantity');
            $product->update(['stock_quantity' => $overallStock]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Variant stocks updated successfully.',
            'data' => new AdminInventoryResource($product->fresh(['categories', 'combinations', 'primaryMedia'])),
        ]);
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
        return array_values(array_unique($ids));
    }
}
