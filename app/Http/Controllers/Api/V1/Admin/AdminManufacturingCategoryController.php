<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\ManufacturingProductCategory;
use App\Http\Requests\Api\V1\Admin\StoreManufacturingCategoryRequest;
use App\Http\Requests\Api\V1\Admin\UpdateManufacturingCategoryRequest;
use App\Http\Resources\Api\V1\AdminManufacturingCategoryResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AdminManufacturingCategoryController extends Controller
{
    /**
     * List all manufacturing product categories.
     */
    public function index(Request $request): JsonResponse
    {
        $query = ManufacturingProductCategory::withCount('manufacturingProducts');

        if ($request->filled('search')) {
            $search = trim($request->query('search'));
            $query->where('name', 'like', "%{$search}%");
        }

        if ($request->query('status') === 'active') {
            $query->where('status', true);
        } elseif ($request->query('status') === 'inactive') {
            $query->where('status', false);
        }

        $query->orderBy('name');

        if ($request->query('paginate') === 'true') {
            $perPage = (int) $request->query('per_page', 15);
            $paginator = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => AdminManufacturingCategoryResource::collection($paginator->getCollection()),
                'pagination' => [
                    'total' => $paginator->total(),
                    'count' => $paginator->count(),
                    'per_page' => $paginator->perPage(),
                    'current_page' => $paginator->currentPage(),
                    'total_pages' => $paginator->lastPage(),
                ],
            ]);
        }

        $categories = $query->get();

        return response()->json([
            'success' => true,
            'data' => AdminManufacturingCategoryResource::collection($categories),
        ]);
    }

    /**
     * Get lightweight list of active manufacturing product categories for pickers.
     */
    public function options(): JsonResponse
    {
        $categories = ManufacturingProductCategory::active()
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json([
            'success' => true,
            'data' => $categories,
        ]);
    }

    /**
     * Show single manufacturing product category details.
     */
    public function show(int $id): JsonResponse
    {
        $category = ManufacturingProductCategory::withCount('manufacturingProducts')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => new AdminManufacturingCategoryResource($category),
        ]);
    }

    /**
     * Create a new manufacturing product category.
     */
    public function store(StoreManufacturingCategoryRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $validated['status'] = $validated['status'] ?? true;

        $category = ManufacturingProductCategory::create($validated);
        $category->loadCount('manufacturingProducts');

        return response()->json([
            'success' => true,
            'message' => "Manufacturing product category \"{$category->name}\" created successfully.",
            'data' => new AdminManufacturingCategoryResource($category),
        ], 201);
    }

    /**
     * Update an existing manufacturing product category.
     */
    public function update(UpdateManufacturingCategoryRequest $request, int $id): JsonResponse
    {
        $category = ManufacturingProductCategory::withCount('manufacturingProducts')->findOrFail($id);
        $validated = $request->validated();

        $category->update($validated);

        return response()->json([
            'success' => true,
            'message' => "Manufacturing product category \"{$category->name}\" updated successfully.",
            'data' => new AdminManufacturingCategoryResource($category),
        ]);
    }

    /**
     * Toggle active/inactive status of a category.
     */
    public function toggleStatus(int $id): JsonResponse
    {
        $category = ManufacturingProductCategory::withCount('manufacturingProducts')->findOrFail($id);
        $category->update(['status' => !$category->status]);

        $label = $category->status ? 'activated' : 'deactivated';

        return response()->json([
            'success' => true,
            'message' => "Manufacturing product category \"{$category->name}\" {$label} successfully.",
            'data' => new AdminManufacturingCategoryResource($category),
        ]);
    }

    /**
     * Delete a manufacturing product category safely.
     */
    public function destroy(int $id): JsonResponse
    {
        $category = ManufacturingProductCategory::withCount('manufacturingProducts')->findOrFail($id);

        if ($category->manufacturing_products_count > 0) {
            return response()->json([
                'success' => false,
                'message' => "Cannot delete \"{$category->name}\" — it is linked to {$category->manufacturing_products_count} manufacturing product(s). Deactivate it instead.",
                'linked_products_count' => $category->manufacturing_products_count,
            ], 422);
        }

        $name = $category->name;
        $category->delete();

        return response()->json([
            'success' => true,
            'message' => "Manufacturing product category \"{$name}\" deleted successfully.",
        ]);
    }
}
