<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\StoreFrontEndProductConfigRequest;
use App\Http\Resources\Api\V1\AdminFrontEndProductResource;
use App\Models\Category;
use App\Models\FrontEndProduct;
use App\Models\ManufacturingProduct;
use App\Models\RawMaterial;
use App\Services\Catalog\CategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminFrontEndProductController extends Controller
{
    public function __construct(private CategoryService $categoryService) {}

    /**
     * Return summary KPI stats for the front-end-products dashboard.
     */
    public function stats(): JsonResponse
    {
        $leafCategories  = $this->categoryService->getLeafCategories(manufacturedOnly: true);
        $totalLeaf       = $leafCategories->count();

        $categoryIds = $leafCategories->pluck('id');
        $configured  = FrontEndProduct::whereIn('category_id', $categoryIds)
            ->withCount('components')
            ->get()
            ->filter(fn ($fep) => $fep->components_count > 0);

        $configuredIds = $configured->pluck('category_id');

        $configuredCount   = $configured->count();
        $unconfiguredCount = $totalLeaf - $configuredCount;
        $totalComponents   = $configured->sum(fn ($fep) => $fep->components_count);

        return response()->json([
            'success' => true,
            'data'    => [
                'total_leaf_categories'   => $totalLeaf,
                'configured_count'        => $configuredCount,
                'unconfigured_count'      => $unconfiguredCount,
                'total_components_defined'=> $totalComponents,
                'configuration_rate'      => $totalLeaf > 0 ? round(($configuredCount / $totalLeaf) * 100, 1) : 0.0,
            ],
        ]);
    }

    /**
     * Return picker options: manufacturing products + packaging materials.
     */
    public function options(): JsonResponse
    {
        $mfgProducts = ManufacturingProduct::orderBy('name')
            ->get(['id', 'name', 'code', 'status']);

        $packagingMaterials = RawMaterial::packagingOnly()
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'unit']);

        // Fallback: if no packaging-category materials exist, return all
        if ($packagingMaterials->isEmpty()) {
            $packagingMaterials = RawMaterial::orderBy('name')
                ->get(['id', 'name', 'code', 'unit']);
        }

        // Leaf categories for category picker
        $leafCategories = $this->categoryService->getLeafCategories(manufacturedOnly: true)
            ->map(fn ($cat) => [
                'id'        => $cat->id,
                'name'      => $cat->name,
                'full_path' => $cat->full_path,
            ]);

        return response()->json([
            'success' => true,
            'data'    => [
                'manufacturing_products'  => $mfgProducts,
                'packaging_materials'     => $packagingMaterials,
                'leaf_categories'         => $leafCategories,
            ],
        ]);
    }

    /**
     * Paginated list of all manufactured leaf categories with their assembly configs.
     * Mirrors the web page table — each row is a leaf category with its embedded config.
     */
    public function index(Request $request): JsonResponse
    {
        $allLeafCategories = $this->categoryService->getLeafCategories(manufacturedOnly: true);

        // Search filter (mirrors web: search category name or full path)
        if ($request->filled('search')) {
            $term = strtolower(trim($request->query('search')));
            $allLeafCategories = $allLeafCategories->filter(function ($cat) use ($term) {
                return str_contains(strtolower($cat->name), $term)
                    || str_contains(strtolower($cat->full_path ?? ''), $term);
            });
        }

        // Status filter: 'configured' | 'unconfigured'
        $statusFilter = $request->query('status');

        // Pre-load all FrontEndProduct configs for these categories in one query
        $configs = FrontEndProduct::with(['components.manufacturingProduct', 'packagingItems.rawMaterial'])
            ->whereIn('category_id', $allLeafCategories->pluck('id'))
            ->get()
            ->keyBy('category_id');

        // Map config details onto each leaf category
        $mapped = $allLeafCategories->map(function ($cat) use ($configs) {
            $config = $configs->get($cat->id);
            $isConfigured = $config !== null && $config->components->count() > 0;

            return (object) [
                'id'           => $cat->id,
                'name'         => $cat->name,
                'full_path'    => $cat->full_path,
                'is_configured'=> $isConfigured,
                'config'       => $config,
                'components'   => $config ? $config->components : collect(),
                'packaging'    => $config ? $config->packagingItems : collect(),
            ];
        });

        // Apply status filter
        if ($statusFilter === 'configured') {
            $mapped = $mapped->filter(fn ($c) => $c->is_configured);
        } elseif ($statusFilter === 'unconfigured') {
            $mapped = $mapped->filter(fn ($c) => !$c->is_configured);
        }

        // Paginate the in-memory collection
        $page    = max(1, (int) $request->query('page', 1));
        $perPage = max(1, min(100, (int) $request->query('per_page', 12)));
        $total   = $mapped->count();

        $paginated = $mapped->slice(($page - 1) * $perPage, $perPage)->values();

        // Build response data
        $data = $paginated->map(function ($cat) {
            return [
                'category_id'        => $cat->id,
                'category_name'      => $cat->name,
                'category_full_path' => $cat->full_path,
                'is_configured'      => $cat->is_configured,
                'front_end_product'  => $cat->config ? (new AdminFrontEndProductResource($cat->config))->toArray(request()) : null,
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Front-end product configurations retrieved successfully.',
            'data'    => $data,
            'meta'    => [
                'current_page' => $page,
                'per_page'     => $perPage,
                'total'        => $total,
                'last_page'    => max(1, (int) ceil($total / $perPage)),
            ],
            'pagination' => [
                'total'       => $total,
                'count'       => $paginated->count(),
                'per_page'    => $perPage,
                'current_page'=> $page,
                'total_pages' => max(1, (int) ceil($total / $perPage)),
            ],
        ]);
    }

    /**
     * Get the full assembly config for a single leaf category by category ID.
     */
    public function show(int $categoryId): JsonResponse
    {
        $category = Category::findOrFail($categoryId);
        $fullPath = $this->categoryService->buildPath($category);

        $feProduct = FrontEndProduct::with([
            'components.manufacturingProduct',
            'packagingItems.rawMaterial',
            'category',
        ])->where('category_id', $categoryId)->first();

        $isConfigured = $feProduct && $feProduct->components->count() > 0;

        return response()->json([
            'success' => true,
            'data'    => [
                'category_id'        => $category->id,
                'category_name'      => $category->name,
                'category_full_path' => $fullPath,
                'is_configured'      => $isConfigured,
                'front_end_product'  => $feProduct
                    ? (new AdminFrontEndProductResource($feProduct))->toArray(request())
                    : null,
            ],
        ]);
    }

    /**
     * Create or update the assembly configuration for a leaf category.
     * Mirrors the web `saveCategoryConfiguration()` method exactly:
     * - updateOrCreate FrontEndProduct by category_id
     * - sync components (delete + recreate)
     * - sync packaging_items (delete + recreate)
     */
    public function configure(StoreFrontEndProductConfigRequest $request, int $categoryId): JsonResponse
    {
        $category = Category::findOrFail($categoryId);

        $validated    = $request->validated();
        $components   = collect($validated['components'])->filter(
            fn ($r) => !empty($r['manufacturing_product_id']) && intval($r['quantity']) > 0
        );
        $packagingItems = collect($validated['packaging_items'] ?? [])->filter(
            fn ($r) => !empty($r['raw_material_id']) && intval($r['quantity']) > 0
        );

        if ($components->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Please add at least one valid manufacturing product component.',
                'errors'  => ['components' => ['At least one valid component with quantity > 0 is required.']],
            ], 422);
        }

        $feProduct = DB::transaction(function () use ($category, $components, $packagingItems, $validated) {
            $fep = FrontEndProduct::saveConfigForCategory($category, [
                'name'               => trim($category->name),
                'leaf_category_name' => trim($category->name),
                'is_active'          => true,
                'description'        => $validated['description'] ?? null,
            ]);

            // Sync components (delete + recreate, matching web)
            $fep->components()->delete();
            foreach ($components as $comp) {
                $fep->components()->create([
                    'manufacturing_product_id' => intval($comp['manufacturing_product_id']),
                    'quantity'                 => intval($comp['quantity']),
                ]);
            }

            // Sync packaging items
            $fep->packagingItems()->delete();
            foreach ($packagingItems as $pkg) {
                $fep->packagingItems()->create([
                    'raw_material_id' => intval($pkg['raw_material_id']),
                    'quantity'        => intval($pkg['quantity']),
                ]);
            }

            return $fep;
        });

        $feProduct->load([
            'components.manufacturingProduct',
            'packagingItems.rawMaterial',
            'category',
        ]);

        return response()->json([
            'success' => true,
            'message' => "Assembly configuration for category \"{$category->name}\" saved successfully.",
            'data'    => [
                'category_id'        => $category->id,
                'category_name'      => $category->name,
                'category_full_path' => $this->categoryService->buildPath($category),
                'is_configured'      => true,
                'front_end_product'  => (new AdminFrontEndProductResource($feProduct))->toArray(request()),
            ],
        ], $feProduct->wasRecentlyCreated ? 201 : 200);
    }

    /**
     * Toggle the active status of a FrontEndProduct config.
     */
    public function toggleStatus(int $id): JsonResponse
    {
        $fep = FrontEndProduct::with(['components.manufacturingProduct', 'packagingItems.rawMaterial', 'category'])
            ->findOrFail($id);

        $fep->update(['is_active' => !$fep->is_active]);
        $status = $fep->is_active ? 'Active' : 'Inactive';

        return response()->json([
            'success' => true,
            'message' => "Front-end product \"{$fep->name}\" set to {$status}.",
            'data'    => (new AdminFrontEndProductResource($fep))->toArray(request()),
        ]);
    }

    /**
     * Soft-delete a FrontEndProduct assembly configuration.
     * Blocked if the config has linked finished_goods_batches.
     */
    public function destroy(int $id): JsonResponse
    {
        $fep = FrontEndProduct::withCount('finishedGoodsBatches')->findOrFail($id);

        if ($fep->finished_goods_batches_count > 0) {
            return response()->json([
                'success'                   => false,
                'message'                   => "Cannot delete configuration \"{$fep->name}\" — it is linked to {$fep->finished_goods_batches_count} finished goods batch(es). Deactivate it instead.",
                'finished_goods_batches_count' => $fep->finished_goods_batches_count,
            ], 422);
        }

        $name = $fep->name;
        $fep->delete();

        return response()->json([
            'success' => true,
            'message' => "Assembly configuration \"{$name}\" deleted successfully.",
        ]);
    }
}
