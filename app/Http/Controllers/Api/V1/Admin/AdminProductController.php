<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use App\Models\CustomerLevel;
use App\Models\Tag;
use App\Models\ProductMedia;
use App\Services\Catalog\ProductService;
use App\Services\Catalog\ProductVariationService;
use App\Services\Catalog\ProductMediaService;
use App\Http\Requests\Api\V1\Admin\StoreAdminProductRequest;
use App\Http\Requests\Api\V1\Admin\UpdateAdminProductRequest;
use App\Http\Resources\Api\V1\AdminProductResource;
use App\Http\Resources\Api\V1\AdminProductDetailResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AdminProductController extends Controller
{
    /**
     * List products with search, filtering, and pagination.
     */
    public function index(Request $request, ProductService $productService): JsonResponse
    {
        $filters = [
            'search' => $request->query('search'),
            'category_id' => $request->query('category_id'),
            'tag_id' => $request->query('tag_id'),
            'status' => $request->query('status'),
            'product_type' => $request->query('product_type'),
            'stock_status' => $request->query('stock_status'),
            'sort' => $request->query('sort'),
            'per_page' => $request->query('per_page'),
        ];

        $perPage = (int) $request->query('per_page', 10);
        $paginator = $productService->list($filters, $perPage);

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

    /**
     * Return options & reference metadata for building product forms in mobile app.
     */
    public function options(): JsonResponse
    {
        $categories = Category::ordered()->get()->map(fn($c) => [
            'id' => $c->id,
            'title' => $c->title,
            'parent_id' => $c->parent_id,
            'is_leaf' => $c->children()->count() === 0,
            'default_product_config' => $c->default_product_config,
        ]);

        $customerLevels = CustomerLevel::active()->ordered()->get()->map(fn($l) => [
            'id' => $l->id,
            'name' => $l->name,
            'discount_percentage' => (float) $l->discount_percentage,
        ]);

        $tags = Tag::orderBy('name')->get()->map(fn($t) => [
            'id' => $t->id,
            'name' => $t->name,
            'slug' => $t->slug,
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'categories' => $categories,
                'customer_levels' => $customerLevels,
                'tags' => $tags,
                'product_types' => [
                    ['key' => 'retail', 'label' => 'Retail Product'],
                    ['key' => 'manufactured', 'label' => 'Manufactured Product'],
                ],
                'default_units' => [
                    'level1_name' => 'Piece',
                    'level1_code' => 'pcs',
                ],
            ]
        ]);
    }

    /**
     * Display full details for a single product.
     */
    public function show(int $id): JsonResponse
    {
        $product = Product::findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => new AdminProductDetailResource($product),
        ]);
    }

    /**
     * Create a new product.
     */
    public function store(
        StoreAdminProductRequest $request,
        ProductService $productService,
        ProductVariationService $varService,
        ProductMediaService $mediaService
    ): JsonResponse {
        $validated = $request->validated();

        $product = DB::transaction(function () use ($validated, $request, $productService, $varService, $mediaService) {
            $payload = [
                'title' => trim($validated['title']),
                'base_price' => (float) $validated['base_price'],
                'description' => trim($validated['description']),
                'hsn_code' => !empty($validated['hsn_code']) ? trim($validated['hsn_code']) : null,
                'gst_percentage' => isset($validated['gst_percentage']) && $validated['gst_percentage'] !== '' ? (float) $validated['gst_percentage'] : null,
                'minimum_order_quantity' => isset($validated['minimum_order_quantity']) ? (int) $validated['minimum_order_quantity'] : 1,
                'product_type' => $validated['product_type'],
                'is_active' => isset($validated['is_active']) ? (bool) $validated['is_active'] : true,
                'stock_quantity' => array_key_exists('stock_quantity', $validated) && $validated['stock_quantity'] !== null && $validated['stock_quantity'] !== '' ? (int)$validated['stock_quantity'] : null,
                'category_ids' => $validated['category_ids'],
                'customer_level_prices' => $validated['customer_level_prices'] ?? [],
                'units' => $validated['units'] ?? [],
            ];

            if (!empty($validated['sku'])) {
                $payload['sku'] = trim($validated['sku']);
            }

            $product = $productService->create($payload);

            // Sync Tags
            if (isset($validated['tag_ids'])) {
                $product->tags()->sync($validated['tag_ids']);
            }

            // Sync Variations & Combinations
            if (!empty($validated['variation_groups'])) {
                $varService->syncVariationGroups($product, $validated['variation_groups']);

                $combinations = $validated['combinations'] ?? $varService->generateCombinations($validated['variation_groups']);
                $varService->syncCombinations($product, $combinations);

                // Update product total stock sum across variants
                $totalStock = collect($combinations)->sum(fn($c) => (isset($c['stock_quantity']) && $c['stock_quantity'] !== '') ? (int)$c['stock_quantity'] : 0);
                $product->update(['stock_quantity' => $totalStock]);
            }

            // Upload media files if provided
            if ($request->hasFile('images')) {
                $mediaService->storeProductMedia($product, $request->file('images'));
            }

            return $product;
        });

        return response()->json([
            'success' => true,
            'message' => 'Product created successfully.',
            'data' => new AdminProductDetailResource($product->fresh()),
        ], 201);
    }

    /**
     * Update an existing product.
     */
    public function update(
        UpdateAdminProductRequest $request,
        int $id,
        ProductService $productService,
        ProductVariationService $varService,
        ProductMediaService $mediaService
    ): JsonResponse {
        $product = Product::findOrFail($id);
        $validated = $request->validated();

        $updatedProduct = DB::transaction(function () use ($product, $validated, $request, $productService, $varService, $mediaService) {
            $payload = [
                'title' => trim($validated['title']),
                'base_price' => (float) $validated['base_price'],
                'description' => trim($validated['description']),
                'hsn_code' => !empty($validated['hsn_code']) ? trim($validated['hsn_code']) : null,
                'gst_percentage' => isset($validated['gst_percentage']) && $validated['gst_percentage'] !== '' ? (float) $validated['gst_percentage'] : null,
                'minimum_order_quantity' => isset($validated['minimum_order_quantity']) ? (int) $validated['minimum_order_quantity'] : 1,
                'product_type' => $validated['product_type'],
                'is_active' => isset($validated['is_active']) ? (bool) $validated['is_active'] : true,
                'stock_quantity' => array_key_exists('stock_quantity', $validated) && $validated['stock_quantity'] !== null && $validated['stock_quantity'] !== '' ? (int)$validated['stock_quantity'] : null,
            ];

            if (isset($validated['category_ids'])) {
                $payload['category_ids'] = $validated['category_ids'];
            }
            if (isset($validated['customer_level_prices'])) {
                $payload['customer_level_prices'] = $validated['customer_level_prices'];
            }
            if (isset($validated['units'])) {
                $payload['units'] = $validated['units'];
            }

            $productService->update($product, $payload);

            if (isset($validated['tag_ids'])) {
                $product->tags()->sync($validated['tag_ids']);
            }

            if (array_key_exists('variation_groups', $validated)) {
                if (!empty($validated['variation_groups'])) {
                    $varService->syncVariationGroups($product, $validated['variation_groups']);

                    $combinations = $validated['combinations'] ?? $varService->generateCombinations($validated['variation_groups']);
                    $varService->syncCombinations($product, $combinations);

                    $totalStock = collect($combinations)->sum(fn($c) => (isset($c['stock_quantity']) && $c['stock_quantity'] !== '') ? (int)$c['stock_quantity'] : 0);
                    $product->update(['stock_quantity' => $totalStock]);
                } else {
                    $product->variationGroups()->delete();
                    $product->combinations()->delete();
                }
            }

            if ($request->hasFile('images')) {
                $mediaService->storeProductMedia($product, $request->file('images'));
            }

            return $product->fresh();
        });

        return response()->json([
            'success' => true,
            'message' => 'Product updated successfully.',
            'data' => new AdminProductDetailResource($updatedProduct),
        ]);
    }

    /**
     * Toggle product active status.
     */
    public function toggleStatus(int $id, ProductService $productService): JsonResponse
    {
        $product = Product::findOrFail($id);
        $updated = $productService->toggleStatus($product);

        $statusText = $updated->is_active ? 'activated' : 'deactivated';

        return response()->json([
            'success' => true,
            'message' => "Product {$statusText} successfully.",
            'data' => [
                'id' => $updated->id,
                'is_active' => (bool) $updated->is_active,
            ]
        ]);
    }

    /**
     * Delete product (soft delete).
     */
    public function destroy(int $id, ProductService $productService): JsonResponse
    {
        $product = Product::findOrFail($id);
        $productService->delete($product);

        return response()->json([
            'success' => true,
            'message' => 'Product deleted successfully.'
        ]);
    }

    /**
     * Upload media file(s) for a product.
     */
    public function uploadMedia(Request $request, int $id, ProductMediaService $mediaService): JsonResponse
    {
        $request->validate([
            'images' => 'required|array|min:1',
            'images.*' => 'image|max:4096',
        ]);

        $product = Product::findOrFail($id);
        $mediaService->storeProductMedia($product, $request->file('images'));

        return response()->json([
            'success' => true,
            'message' => 'Media uploaded successfully.',
            'data' => new AdminProductDetailResource($product->fresh()),
        ]);
    }

    /**
     * Set primary cover media.
     */
    public function setPrimaryMedia(int $id, int $mediaId, ProductMediaService $mediaService): JsonResponse
    {
        $product = Product::findOrFail($id);
        $media = ProductMedia::where('product_id', $product->id)->where('id', $mediaId)->firstOrFail();

        $mediaService->setPrimary($media);

        return response()->json([
            'success' => true,
            'message' => 'Cover image updated successfully.',
            'data' => new AdminProductDetailResource($product->fresh()),
        ]);
    }

    /**
     * Delete media record.
     */
    public function deleteMedia(int $id, int $mediaId, ProductMediaService $mediaService): JsonResponse
    {
        $product = Product::findOrFail($id);
        $media = ProductMedia::where('product_id', $product->id)->where('id', $mediaId)->firstOrFail();

        $mediaService->deleteMedia($media);

        return response()->json([
            'success' => true,
            'message' => 'Media item deleted successfully.',
            'data' => new AdminProductDetailResource($product->fresh()),
        ]);
    }
}
