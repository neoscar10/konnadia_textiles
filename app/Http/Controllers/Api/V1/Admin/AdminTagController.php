<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use App\Models\Category;
use App\Services\Catalog\CategoryService;
use App\Http\Requests\Api\V1\Admin\StoreTagRequest;
use App\Http\Requests\Api\V1\Admin\UpdateTagRequest;
use App\Http\Resources\Api\V1\AdminTagResource;
use App\Http\Resources\Api\V1\AdminCategoryTreeResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AdminTagController extends Controller
{
    /**
     * List tags with search and pagination.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Tag::with('categories')->orderBy('name');

        if ($request->filled('search')) {
            $search = trim($request->query('search'));
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        $perPage = (int) $request->query('per_page', 10);
        $paginator = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => AdminTagResource::collection($paginator->getCollection()),
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
     * Return category tree & options for assigning categories to a tag.
     */
    public function options(CategoryService $categoryService): JsonResponse
    {
        $tree = $categoryService->getTree();
        $leafCategories = $categoryService->getLeafCategories()->map(fn($leaf) => [
            'id' => $leaf->id,
            'name' => $leaf->name,
            'title' => $leaf->title,
            'full_path' => $leaf->full_path,
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'category_tree' => AdminCategoryTreeResource::collection($tree),
                'leaf_categories' => $leafCategories,
            ]
        ]);
    }

    /**
     * Display single tag details.
     */
    public function show(int $id): JsonResponse
    {
        $tag = Tag::with('categories')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => new AdminTagResource($tag),
        ]);
    }

    /**
     * Create a new tag.
     */
    public function store(StoreTagRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $tag = DB::transaction(function () use ($validated) {
            $slug = Str::slug($validated['name']);

            $tag = Tag::create([
                'name' => trim($validated['name']),
                'slug' => $slug,
            ]);

            $categoryIds = $validated['category_ids'];

            // Automatically expand descendant category IDs if requested
            if (!empty($validated['include_descendants'])) {
                $categoryIds = $this->expandCategoryDescendants($categoryIds);
            }

            $tag->categories()->sync($categoryIds);

            return $tag;
        });

        return response()->json([
            'success' => true,
            'message' => 'Tag created successfully.',
            'data' => new AdminTagResource($tag->load('categories')),
        ], 201);
    }

    /**
     * Update an existing tag.
     */
    public function update(UpdateTagRequest $request, int $id): JsonResponse
    {
        $tag = Tag::findOrFail($id);
        $validated = $request->validated();

        $updatedTag = DB::transaction(function () use ($tag, $validated) {
            $slug = Str::slug($validated['name']);

            $tag->update([
                'name' => trim($validated['name']),
                'slug' => $slug,
            ]);

            if (isset($validated['category_ids'])) {
                $categoryIds = $validated['category_ids'];
                if (!empty($validated['include_descendants'])) {
                    $categoryIds = $this->expandCategoryDescendants($categoryIds);
                }
                $tag->categories()->sync($categoryIds);
            }

            return $tag;
        });

        return response()->json([
            'success' => true,
            'message' => 'Tag updated successfully.',
            'data' => new AdminTagResource($updatedTag->load('categories')),
        ]);
    }

    /**
     * Delete a tag.
     */
    public function destroy(int $id): JsonResponse
    {
        $tag = Tag::findOrFail($id);

        DB::transaction(function () use ($tag) {
            $tag->products()->detach();
            $tag->categories()->detach();
            $tag->delete();
        });

        return response()->json([
            'success' => true,
            'message' => 'Tag deleted successfully.',
        ]);
    }

    /**
     * Helper to expand parent category IDs to include all descendant subcategories.
     */
    protected function expandCategoryDescendants(array $categoryIds): array
    {
        $allIds = $categoryIds;

        foreach ($categoryIds as $catId) {
            $category = Category::with('children')->find($catId);
            if ($category) {
                $allIds = array_merge($allIds, $this->getDescendantIds($category));
            }
        }

        return array_values(array_unique($allIds));
    }

    protected function getDescendantIds(Category $category): array
    {
        $ids = [];
        foreach ($category->children as $child) {
            $ids[] = $child->id;
            $ids = array_merge($ids, $this->getDescendantIds($child));
        }
        return $ids;
    }
}
