<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\StoreRawMaterialCategoryRequest;
use App\Http\Requests\Api\V1\Admin\UpdateRawMaterialCategoryRequest;
use App\Http\Resources\Api\V1\AdminRawMaterialCategoryResource;
use App\Models\RawMaterialCategory;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AdminRawMaterialCategoryController extends Controller
{
    /**
     * List all raw material categories.
     */
    public function index(Request $request): JsonResponse
    {
        $query = RawMaterialCategory::with(['unitGroup'])->withCount('materials');

        if ($request->filled('search')) {
            $search = trim($request->query('search'));
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if ($request->filled('unit_type')) {
            $query->where('unit_type', $request->query('unit_type'));
        }

        if ($request->query('status') === 'active') {
            $query->where('is_active', true);
        } elseif ($request->query('status') === 'inactive') {
            $query->where('is_active', false);
        }

        $query->orderBy('name', 'asc');

        if ($request->query('paginate') === 'true' || $request->has('page')) {
            $perPage = max(1, min(100, (int) $request->query('per_page', 15)));
            $paginator = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'message' => 'Raw material categories retrieved successfully.',
                'data' => AdminRawMaterialCategoryResource::collection($paginator->getCollection()),
                'meta' => [
                    'current_page' => $paginator->currentPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                    'last_page' => $paginator->lastPage(),
                ],
            ]);
        }

        $categories = $query->get();

        return response()->json([
            'success' => true,
            'message' => 'Raw material categories retrieved successfully.',
            'data' => AdminRawMaterialCategoryResource::collection($categories),
        ]);
    }

    /**
     * Show raw material category details.
     */
    public function show(int $id): JsonResponse
    {
        $category = RawMaterialCategory::with(['unitGroup'])->withCount('materials')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => new AdminRawMaterialCategoryResource($category),
        ]);
    }

    /**
     * Store a new raw material category.
     */
    public function store(StoreRawMaterialCategoryRequest $request): JsonResponse
    {
        $validated = $request->validated();
        if (isset($validated['status'])) {
            $validated['is_active'] = (bool) $validated['status'];
            unset($validated['status']);
        }

        $category = RawMaterialCategory::create($validated);
        $category->load(['unitGroup'])->loadCount('materials');

        return response()->json([
            'success' => true,
            'message' => "Raw Material Category \"{$category->name}\" created successfully.",
            'data' => new AdminRawMaterialCategoryResource($category),
        ], 201);
    }

    /**
     * Update an existing raw material category.
     */
    public function update(UpdateRawMaterialCategoryRequest $request, int $id): JsonResponse
    {
        $category = RawMaterialCategory::findOrFail($id);
        $validated = $request->validated();
        if (isset($validated['status'])) {
            $validated['is_active'] = (bool) $validated['status'];
            unset($validated['status']);
        }

        $category->update($validated);
        $category->load(['unitGroup'])->loadCount('materials');

        return response()->json([
            'success' => true,
            'message' => "Raw Material Category \"{$category->name}\" updated successfully.",
            'data' => new AdminRawMaterialCategoryResource($category),
        ]);
    }

    /**
     * Toggle status (active/inactive) of raw material category.
     */
    public function toggleStatus(int $id): JsonResponse
    {
        $category = RawMaterialCategory::findOrFail($id);
        $category->update(['is_active' => !$category->is_active]);

        $label = $category->is_active ? 'activated' : 'deactivated';

        return response()->json([
            'success' => true,
            'message' => "Raw Material Category \"{$category->name}\" {$label} successfully.",
            'data' => new AdminRawMaterialCategoryResource($category->load(['unitGroup'])->loadCount('materials')),
        ]);
    }

    /**
     * Delete a raw material category safely.
     */
    public function destroy(int $id): JsonResponse
    {
        $category = RawMaterialCategory::findOrFail($id);

        if ($category->materials()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => "Cannot delete Raw Material Category \"{$category->name}\" — it has {$category->materials()->count()} linked raw material(s). Deactivate it instead.",
                'linked_raw_materials_count' => $category->materials()->count(),
            ], 422);
        }

        $name = $category->name;
        $category->delete();

        return response()->json([
            'success' => true,
            'message' => "Raw Material Category \"{$name}\" deleted successfully.",
        ]);
    }
}
