<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\CustomerLevel;
use App\Services\Catalog\CategoryService;
use App\Http\Requests\Api\V1\Admin\StoreCategoryRequest;
use App\Http\Requests\Api\V1\Admin\UpdateCategoryRequest;
use App\Http\Requests\Api\V1\Admin\SaveCategoryDefaultsRequest;
use App\Http\Requests\Api\V1\Admin\MoveCategoryProductsRequest;
use App\Http\Resources\Api\V1\AdminCategoryResource;
use App\Http\Resources\Api\V1\AdminCategoryTreeResource;
use App\Http\Resources\Api\V1\AdminProductResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AdminCategoryController extends Controller
{
    /**
     * Get complete recursive nested Category Tree.
     */
    public function tree(CategoryService $categoryService): JsonResponse
    {
        $tree = $categoryService->getTree();

        return response()->json([
            'success' => true,
            'data' => AdminCategoryTreeResource::collection($tree),
        ]);
    }

    /**
     * List categories under a parent folder with optional search and breadcrumb navigation trail.
     */
    public function index(Request $request, CategoryService $categoryService): JsonResponse
    {
        $parentId = $request->query('parent_id') !== null ? (int)$request->query('parent_id') : null;
        $filters = [
            'search' => $request->query('search'),
        ];

        $currentCategory = $parentId ? Category::findOrFail($parentId) : null;
        $children = $categoryService->getChildren($parentId, $filters);
        $breadcrumbs = $categoryService->getBreadcrumb($currentCategory);

        return response()->json([
            'success' => true,
            'data' => [
                'current_category' => $currentCategory ? new AdminCategoryResource($currentCategory) : null,
                'breadcrumbs' => $breadcrumbs,
                'categories' => AdminCategoryResource::collection($children),
            ]
        ]);
    }

    /**
     * Get single category details.
     */
    public function show(int $id): JsonResponse
    {
        $category = Category::findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => new AdminCategoryResource($category),
        ]);
    }

    /**
     * Create a new category or subcategory folder.
     */
    public function store(StoreCategoryRequest $request, CategoryService $categoryService): JsonResponse
    {
        $validated = $request->validated();

        try {
            $category = $categoryService->create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Category created successfully.',
                'data' => new AdminCategoryResource($category),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Update an existing category.
     */
    public function update(UpdateCategoryRequest $request, int $id, CategoryService $categoryService): JsonResponse
    {
        $category = Category::findOrFail($id);
        $validated = $request->validated();

        try {
            $updated = $categoryService->update($category, $validated);

            return response()->json([
                'success' => true,
                'message' => 'Category updated successfully.',
                'data' => new AdminCategoryResource($updated),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Toggle active status of a category.
     */
    public function toggleStatus(int $id, CategoryService $categoryService): JsonResponse
    {
        $category = Category::findOrFail($id);
        $updated = $categoryService->toggleStatus($category);
        $statusText = $updated->is_active ? 'activated' : 'deactivated';

        return response()->json([
            'success' => true,
            'message' => "Category {$statusText} successfully.",
            'data' => [
                'id' => $updated->id,
                'is_active' => (bool) $updated->is_active,
            ]
        ]);
    }

    /**
     * Delete a category safely.
     * Optional parameters:
     * - action: 'delete_products' | 'move_products'
     * - target_category_id: int (required when action is move_products)
     */
    public function destroy(Request $request, int $id, CategoryService $categoryService): JsonResponse
    {
        $category = Category::findOrFail($id);

        $action = $request->input('action');
        $targetCategoryId = $request->input('target_category_id');

        if ($category->is_leaf && $category->products()->count() > 0) {
            if ($action === 'move_products') {
                if (!$targetCategoryId) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Target category is required to move products.',
                    ], 422);
                }
                $categoryService->moveProductsToCategory($category->id, (int)$targetCategoryId);
            } elseif ($action === 'delete_products') {
                // Products will be detached/deleted recursively in CategoryService::delete
            } else {
                return response()->json([
                    'success' => false,
                    'message' => "Category contains {$category->products()->count()} product(s). Specify action='move_products' with target_category_id or action='delete_products'.",
                    'requires_leaf_safety' => true,
                    'product_count' => $category->products()->count(),
                ], 422);
            }
        }

        $categoryService->delete($category);

        return response()->json([
            'success' => true,
            'message' => 'Category deleted successfully.',
        ]);
    }

    /**
     * Get default product configuration for a category.
     */
    public function getDefaults(int $id): JsonResponse
    {
        $category = Category::findOrFail($id);
        $defaults = $category->default_product_config ?? [];

        // Ensure customer levels structure is present
        $customerLevels = CustomerLevel::active()->ordered()->get();
        $pricing = $defaults['pricingOverrides'] ?? [];
        foreach ($customerLevels as $lvl) {
            if (!isset($pricing[$lvl->id])) {
                $pricing[$lvl->id] = '';
            }
        }
        $defaults['pricingOverrides'] = $pricing;

        if (empty($defaults['units'])) {
            $defaults['units'] = [
                'level1_name' => 'Piece',
                'level1_code' => 'pcs',
                'level2_name' => '',
                'level2_code' => '',
                'level2_conversion' => '',
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'category_id' => $category->id,
                'category_name' => $category->name,
                'defaults' => $defaults,
            ]
        ]);
    }

    /**
     * Configure default product template parameters for a category.
     */
    public function saveDefaults(SaveCategoryDefaultsRequest $request, int $id): JsonResponse
    {
        $category = Category::findOrFail($id);
        $validated = $request->validated();

        $category->update([
            'default_product_config' => $validated,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Category default configuration saved successfully.',
            'data' => [
                'category_id' => $category->id,
                'defaults' => $category->default_product_config,
            ]
        ]);
    }

    /**
     * Move all attached products from source leaf category to target leaf category.
     */
    public function moveProducts(MoveCategoryProductsRequest $request, int $id, CategoryService $categoryService): JsonResponse
    {
        $validated = $request->validated();
        $targetCategoryId = (int)$validated['target_category_id'];

        try {
            $categoryService->moveProductsToCategory($id, $targetCategoryId);

            return response()->json([
                'success' => true,
                'message' => 'Products moved successfully to target category.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get attached products when viewing a leaf category folder.
     */
    public function getProducts(Request $request, int $id): JsonResponse
    {
        $category = Category::findOrFail($id);

        $query = $category->products()
            ->with(['primaryMedia', 'combinations', 'units'])
            ->select('products.*');

        if ($request->filled('search')) {
            $search = trim($request->query('search'));
            $query->where(function ($q) use ($search) {
                $q->where('products.title', 'like', "%{$search}%")
                  ->orWhere('products.sku', 'like', "%{$search}%");
            });
        }

        if ($request->query('status') === 'active') {
            $query->where('products.is_active', true);
        } elseif ($request->query('status') === 'inactive') {
            $query->where('products.is_active', false);
        }

        if ($request->query('stock_status') === 'instock') {
            $query->where('products.stock_quantity', '>', 0);
        } elseif ($request->query('stock_status') === 'outofstock') {
            $query->where(function ($q) {
                $q->whereNull('products.stock_quantity')->orWhere('products.stock_quantity', 0);
            });
        }

        $perPage = (int)$request->query('per_page', 15);
        $paginator = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => AdminProductResource::collection($paginator->getCollection()),
            'pagination' => [
                'total' => $paginator->total(),
                'count' => $paginator->count(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'total_pages' => $paginator->lastPage(),
            ]
        ]);
    }
}
