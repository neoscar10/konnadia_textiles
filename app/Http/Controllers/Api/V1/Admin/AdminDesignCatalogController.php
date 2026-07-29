<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Tag;
use App\Services\Catalog\CategoryService;
use App\Http\Resources\Api\V1\AdminDesignCatalogResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AdminDesignCatalogController extends Controller
{
    /**
     * List products for Design Catalog grid preview with search and category/tag filters.
     */
    public function index(Request $request, CategoryService $categoryService): JsonResponse
    {
        $query = Product::with(['categories', 'primaryMedia', 'tags'])
            ->orderBy('created_at', 'desc');

        if ($request->filled('search')) {
            $search = trim($request->query('search'));
            $query->where(function ($sub) use ($search) {
                $sub->where('title', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category_id')) {
            $categoryId = (int) $request->query('category_id');
            $query->whereHas('categories', function ($sub) use ($categoryId) {
                $sub->where('categories.id', $categoryId);
            });
        }

        if ($request->filled('tag_id')) {
            $tagId = (int) $request->query('tag_id');
            $query->whereHas('tags', function ($sub) use ($tagId) {
                $sub->where('tags.id', $tagId);
            });
        }

        $perPage = (int) $request->query('per_page', 10);
        $paginator = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => AdminDesignCatalogResource::collection($paginator->getCollection()),
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
     * Return filter dropdown metadata (leaf categories with breadcrumb paths and tags).
     */
    public function options(CategoryService $categoryService): JsonResponse
    {
        $leafCategories = $categoryService->getLeafCategories()->map(fn($leaf) => [
            'id' => $leaf->id,
            'title' => $leaf->title,
            'full_path' => $leaf->full_path,
        ]);

        $tags = Tag::orderBy('name')->get()->map(fn($t) => [
            'id' => $t->id,
            'name' => $t->name,
            'slug' => $t->slug,
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'leaf_categories' => $leafCategories,
                'tags' => $tags,
            ]
        ]);
    }

    /**
     * Generate shareable web URL for filtered catalog.
     */
    public function share(Request $request): JsonResponse
    {
        $params = [];
        if ($request->filled('search')) {
            $params['search'] = trim($request->input('search'));
        }
        if ($request->filled('category_id')) {
            $params['category'] = (int) $request->input('category_id');
        }
        if ($request->filled('tag_id')) {
            $params['selectedTags'] = [(int) $request->input('tag_id')];
        }

        $shareUrl = route('customer.products.index', $params);
        $shareText = "Check out our latest textile design catalog on Kanodia Textiles: {$shareUrl}";

        return response()->json([
            'success' => true,
            'data' => [
                'share_url' => $shareUrl,
                'share_text' => $shareText,
                'applied_filters' => $params,
            ]
        ]);
    }
}
