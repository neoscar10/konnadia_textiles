<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductTransfer;
use App\Models\RetailShop;
use App\Models\Category;
use App\Models\Product;
use App\Services\StockTransfer\ManufacturedProductTransferService;
use App\Services\StockTransfer\TransferInventoryService;
use App\Http\Requests\Api\V1\Admin\StoreProductTransferRequest;
use App\Http\Resources\Api\V1\AdminProductTransferResource;
use App\Http\Resources\Api\V1\AdminProductTransferDetailResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AdminProductTransferController extends Controller
{
    /**
     * List product transfers with search, shop, and status filters.
     */
    public function index(Request $request, ManufacturedProductTransferService $service): JsonResponse
    {
        $filters = [
            'search' => $request->query('search'),
            'retail_shop_id' => $request->query('retail_shop_id'),
            'status' => $request->query('status'),
        ];
        $perPage = (int) $request->query('per_page', 10);

        $paginator = $service->list($filters, $perPage);

        return response()->json([
            'success' => true,
            'data' => AdminProductTransferResource::collection($paginator->getCollection()),
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
     * Get reference metadata for transfer creation (active shops & categories).
     */
    public function options(): JsonResponse
    {
        $shops = RetailShop::active()->orderBy('name')->get(['id', 'shop_code', 'name', 'city']);
        $categories = Category::ordered()->get(['id', 'name', 'title', 'is_leaf']);

        return response()->json([
            'success' => true,
            'data' => [
                'retail_shops' => $shops,
                'categories' => $categories,
            ]
        ]);
    }

    /**
     * Get transfer setup details for a specific product (combinations, available units & stock).
     */
    public function productTransferInfo(int $productId, TransferInventoryService $inventoryService): JsonResponse
    {
        $product = Product::with(['units', 'combinations' => fn($q) => $q->where('is_active', true)])
            ->findOrFail($productId);

        $combinations = $product->combinations->map(function ($comb) use ($product, $inventoryService) {
            $avail = $inventoryService->getAvailableStock($product, $comb);
            return [
                'id' => $comb->id,
                'sku' => $comb->sku,
                'combination_values' => $comb->combination_values,
                'available_stock' => (int) $avail,
            ];
        });

        $availableUnits = $product->units->map(fn($u) => [
            'id' => $u->id,
            'level' => (int) $u->level,
            'name' => $u->name,
            'short_code' => $u->short_code,
            'conversion_to_base' => (float) $u->conversion_to_base,
        ]);

        $nonVariantStock = $inventoryService->getAvailableStock($product, null);

        return response()->json([
            'success' => true,
            'data' => [
                'product_id' => $product->id,
                'product_title' => $product->title,
                'product_sku' => $product->sku,
                'available_stock' => (int) $nonVariantStock,
                'has_variants' => $product->combinations->isNotEmpty(),
                'combinations' => $combinations,
                'units' => $availableUnits,
            ]
        ]);
    }

    /**
     * Get single product transfer details.
     */
    public function show(int $id, ManufacturedProductTransferService $service): JsonResponse
    {
        $transfer = ProductTransfer::findOrFail($id);
        $detailed = $service->getDetail($transfer);

        return response()->json([
            'success' => true,
            'data' => new AdminProductTransferDetailResource($detailed),
        ]);
    }

    /**
     * Create and complete a product transfer.
     */
    public function store(StoreProductTransferRequest $request, ManufacturedProductTransferService $service): JsonResponse
    {
        $validated = $request->validated();
        $adminUser = $request->user();

        try {
            $transfer = $service->createCompletedTransfer($adminUser, $validated);

            return response()->json([
                'success' => true,
                'message' => 'Product transfer completed successfully.',
                'data' => new AdminProductTransferDetailResource($transfer),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
