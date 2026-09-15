<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\StoreFinishedGoodsConversionRequest;
use App\Http\Resources\Api\V1\AdminFinishedGoodsBatchResource;
use App\Models\Category;
use App\Models\FinishedGoodsBatch;
use App\Models\FrontEndProduct;
use App\Models\Product;
use App\Services\Catalog\CategoryService;
use App\Services\Manufacturing\FinishedGoodsConversionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminFinishedGoodsController extends Controller
{
    public function __construct(
        private CategoryService $categoryService,
        private FinishedGoodsConversionService $conversionService
    ) {}

    /**
     * KPI stats for the finished goods dashboard.
     */
    public function stats(): JsonResponse
    {
        $totalBatches  = FinishedGoodsBatch::count();
        $totalQty      = (int) FinishedGoodsBatch::sum('converted_qty');
        $totalDesigns  = FinishedGoodsBatch::distinct('design_id')->count('design_id');
        $totalCategories = FinishedGoodsBatch::with('frontEndProduct')
            ->get()
            ->pluck('frontEndProduct.category_id')
            ->filter()
            ->unique()
            ->count();

        // Recent conversion in last 7 days
        $recentBatches = FinishedGoodsBatch::where('created_at', '>=', now()->subDays(7))->count();
        $recentQty     = (int) FinishedGoodsBatch::where('created_at', '>=', now()->subDays(7))->sum('converted_qty');

        // Configured categories with active FEP (can be converted)
        $configuredCategories = FrontEndProduct::where('is_active', true)
            ->whereNotNull('category_id')
            ->withCount('components')
            ->get()
            ->filter(fn ($fep) => $fep->components_count > 0)
            ->count();

        return response()->json([
            'success' => true,
            'data'    => [
                'total_batches'               => $totalBatches,
                'total_converted_qty'         => $totalQty,
                'total_unique_designs'        => $totalDesigns,
                'total_categories_converted'  => $totalCategories,
                'recent_batches_7_days'       => $recentBatches,
                'recent_qty_7_days'           => $recentQty,
                'configured_categories_count' => $configuredCategories,
            ],
        ]);
    }

    /**
     * Return picker data needed for the conversion wizard.
     * - Configured leaf categories (active FEPs)
     * - Storefront products per category (optional: pass ?category_id=X)
     */
    public function options(Request $request): JsonResponse
    {
        // All manufactured leaf categories
        $allLeafCats = $this->categoryService->getLeafCategories(manufacturedOnly: true);
        $configuredCatIds = FrontEndProduct::where('is_active', true)
            ->whereNotNull('category_id')
            ->pluck('category_id')
            ->toArray();

        $categories = $allLeafCats->map(fn ($cat) => [
            'id'             => $cat->id,
            'name'           => $cat->name,
            'full_path'      => $cat->full_path,
            'is_configured'  => in_array($cat->id, $configuredCatIds),
        ]);

        // Storefront products for a specific category (for "existing design" picker)
        $storefrontProducts = collect();
        if ($request->filled('category_id')) {
            $catId = (int) $request->query('category_id');
            $storefrontProducts = Product::whereHas('categories', fn ($q) => $q->where('categories.id', $catId))
                ->where('is_active', true)
                ->orderBy('title')
                ->get(['id', 'title', 'sku', 'stock_quantity']);
        }

        return response()->json([
            'success' => true,
            'data'    => [
                'leaf_categories'      => $categories,
                'configured_category_ids' => $configuredCatIds,
                'storefront_products'  => $storefrontProducts,
            ],
        ]);
    }

    /**
     * Stock availability pre-flight check for a category + target quantity.
     * Used by Step 1 of the conversion wizard to show real-time stock status.
     */
    public function stockCheck(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'category_id'         => 'required|integer|exists:categories,id',
            'target_qty'          => 'required|integer|min:1',
            'component_selections'=> 'nullable|array',
        ]);

        $stockResult = $this->conversionService->checkCategoryStockAvailability(
            (int) $validated['category_id'],
            (int) $validated['target_qty'],
            $validated['component_selections'] ?? []
        );

        // Enrich with the category config
        $feProduct = FrontEndProduct::with([
            'components.manufacturingProduct.patterns',
            'packagingItems.rawMaterial',
            'category',
        ])->where('category_id', $validated['category_id'])->first();

        return response()->json([
            'success'         => true,
            'can_proceed'     => $stockResult['canProceed'],
            'missing_items'   => $stockResult['missingItems'],
            'mfg_stock'       => $stockResult['mfgStock'],
            'pkg_stock'       => $stockResult['pkgStock'],
            'category_config' => $feProduct ? [
                'id'               => $feProduct->id,
                'sku'              => $feProduct->sku,
                'name'             => $feProduct->name,
                'components'       => $feProduct->components->map(fn ($c) => [
                    'component_index'          => $feProduct->components->search($c),
                    'manufacturing_product_id' => $c->manufacturing_product_id,
                    'manufacturing_product'    => [
                        'id'   => $c->manufacturingProduct?->id,
                        'name' => $c->manufacturingProduct?->name,
                        'code' => $c->manufacturingProduct?->code,
                        'patterns' => $c->manufacturingProduct?->patterns->map(fn ($p) => [
                            'id'   => $p->id,
                            'name' => $p->name,
                        ]),
                    ],
                    'quantity'                 => $c->quantity,
                    'total_required'           => $c->quantity * (int) $validated['target_qty'],
                ]),
                'packaging_items' => $feProduct->packagingItems->map(fn ($p) => [
                    'raw_material_id'   => $p->raw_material_id,
                    'raw_material_name' => $p->rawMaterial?->name,
                    'qty_per_set'       => $p->quantity,
                    'total_required'    => $p->quantity * (int) $validated['target_qty'],
                ]),
            ] : null,
        ]);
    }

    /**
     * Barcode lookup + full audit for the scan-and-audit panel.
     * Mirrors the web's searchBarcodeFromInput() + openAuditModal().
     */
    public function barcodeSearch(Request $request): JsonResponse
    {
        $query = trim($request->query('q', ''));

        if (empty($query)) {
            return response()->json([
                'success' => false,
                'message' => 'Please provide a barcode or design ID to search.',
            ], 422);
        }

        $cleanQuery = preg_replace('/[^a-zA-Z0-9\-]/', '', $query);

        $batch = FinishedGoodsBatch::with([
            'frontEndProduct.category',
            'items.manufacturingProduct',
            'items.pattern',
            'items.productionJob',
            'items.productionBatch',
            'packagingDeductions.rawMaterial',
            'creator',
        ])
            ->where('barcode', 'like', "%{$query}%")
            ->orWhere('barcode', 'like', "%{$cleanQuery}%")
            ->orWhere('design_id', 'like', "%{$query}%")
            ->orWhereHas('frontEndProduct', fn ($fp) => $fp->where('name', 'like', "%{$query}%")->orWhere('sku', 'like', "%{$query}%"))
            ->first();

        if (!$batch) {
            return response()->json([
                'success' => false,
                'message' => "No finished goods batch found matching '{$query}'.",
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => new AdminFinishedGoodsBatchResource($batch, withAudit: true),
        ]);
    }

    /**
     * Paginated list of all finished goods conversion batches.
     * Mirrors the web table: barcode, category, design_id, qty, date, actions.
     */
    public function index(Request $request): JsonResponse
    {
        $query = FinishedGoodsBatch::with(['frontEndProduct.category', 'creator'])
            ->latest();

        // Search: barcode or design ID (mirrors web debounced searchBarcode)
        if ($request->filled('search')) {
            $term = $request->query('search');
            $query->where(function ($q) use ($term) {
                $q->where('barcode', 'like', "%{$term}%")
                  ->orWhere('design_id', 'like', "%{$term}%")
                  ->orWhereHas('frontEndProduct', fn ($fp) => $fp->where('name', 'like', "%{$term}%"));
            });
        }

        // Filter by category ID
        if ($request->filled('category_id')) {
            $catId = (int) $request->query('category_id');
            $query->whereHas('frontEndProduct', fn ($q) => $q->where('category_id', $catId));
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('converted_date', '>=', $request->query('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('converted_date', '<=', $request->query('date_to'));
        }

        $perPage   = max(1, min(100, (int) $request->query('per_page', 10)));
        $paginated = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Finished goods batches retrieved successfully.',
            'data'    => AdminFinishedGoodsBatchResource::collection($paginated),
            'meta'    => [
                'current_page' => $paginated->currentPage(),
                'per_page'     => $paginated->perPage(),
                'total'        => $paginated->total(),
                'last_page'    => $paginated->lastPage(),
            ],
            'pagination' => [
                'total'        => $paginated->total(),
                'count'        => $paginated->count(),
                'per_page'     => $paginated->perPage(),
                'current_page' => $paginated->currentPage(),
                'total_pages'  => $paginated->lastPage(),
            ],
        ]);
    }

    /**
     * Full audit detail for a single finished goods batch.
     * Mirrors openAuditModal() — includes items, patterns, production source, packaging, costing.
     */
    public function show(int $id): JsonResponse
    {
        $batch = FinishedGoodsBatch::with([
            'frontEndProduct.category',
            'items.manufacturingProduct',
            'items.pattern',
            'items.productionJob',
            'items.productionBatch',
            'packagingDeductions.rawMaterial',
            'creator',
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data'    => new AdminFinishedGoodsBatchResource($batch, withAudit: true),
        ]);
    }

    /**
     * Execute the finished goods conversion wizard.
     * Wraps FinishedGoodsConversionService::convertCategoryToFinishedGoods(),
     * mirroring the web's confirmAndExecuteConversion() action exactly.
     */
    public function convert(StoreFinishedGoodsConversionRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $category = Category::findOrFail($validated['category_id']);

        // Validate assembly config exists
        $feProduct = FrontEndProduct::where('category_id', $category->id)
            ->where('is_active', true)
            ->withCount('components')
            ->first();

        if (!$feProduct || $feProduct->components_count === 0) {
            return response()->json([
                'success' => false,
                'message' => "Assembly rules for category '{$category->name}' have not been configured yet. Please configure them on the Front-End Products page first.",
            ], 422);
        }

        // Validate existing product if design_type=existing
        if ($validated['design_type'] === 'existing') {
            if (empty($validated['existing_storefront_product_id'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please select an existing storefront product.',
                    'errors'  => ['existing_storefront_product_id' => ['Required when design type is existing.']],
                ], 422);
            }
        }

        // Validate design_id required for new design
        if ($validated['design_type'] === 'new' && empty(trim($validated['design_id'] ?? ''))) {
            return response()->json([
                'success' => false,
                'message' => 'A Design ID is required when creating a new design.',
                'errors'  => ['design_id' => ['Design ID is required for new designs.']],
            ], 422);
        }

        try {
            $fgBatch = $this->conversionService->convertCategoryToFinishedGoods([
                'category_id'                   => $validated['category_id'],
                'target_qty'                     => $validated['target_qty'],
                'design_type'                    => $validated['design_type'],
                'design_id'                      => $validated['design_id'] ?? '',
                'existing_storefront_product_id' => $validated['existing_storefront_product_id'] ?? 0,
                'component_selections'           => $validated['component_selections'] ?? [],
                'notes'                          => $validated['notes'] ?? null,
                'reuse_cutting_photo'            => !empty($validated['reuse_cutting_photo']),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        $fgBatch->load([
            'frontEndProduct.category',
            'items.manufacturingProduct',
            'packagingDeductions.rawMaterial',
            'creator',
        ]);

        return response()->json([
            'success' => true,
            'message' => "Finished Goods Lot {$fgBatch->barcode} converted successfully! Added {$fgBatch->converted_qty} set(s) to stock.",
            'data'    => new AdminFinishedGoodsBatchResource($fgBatch, withAudit: true),
        ], 201);
    }

    /**
     * Toggle the is_published flag of a finished goods batch.
     */
    public function togglePublish(int $id): JsonResponse
    {
        $batch = FinishedGoodsBatch::findOrFail($id);
        $batch->update(['is_published' => !$batch->is_published]);

        $status = $batch->is_published ? 'Published' : 'Unpublished';

        return response()->json([
            'success' => true,
            'message' => "Batch {$batch->barcode} is now {$status}.",
            'data'    => new AdminFinishedGoodsBatchResource($batch),
        ]);
    }

    /**
     * Soft-delete a finished goods batch.
     */
    public function destroy(int $id): JsonResponse
    {
        $batch = FinishedGoodsBatch::findOrFail($id);
        $barcode = $batch->barcode;
        $batch->delete();

        return response()->json([
            'success' => true,
            'message' => "Finished goods batch \"{$barcode}\" deleted successfully.",
        ]);
    }
}
