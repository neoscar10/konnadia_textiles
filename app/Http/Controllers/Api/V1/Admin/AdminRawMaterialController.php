<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\StoreRawMaterialRequest;
use App\Http\Requests\Api\V1\Admin\UpdateRawMaterialRequest;
use App\Http\Resources\Api\V1\AdminRawMaterialResource;
use App\Models\RawMaterial;
use App\Models\RawMaterialCategory;
use App\Models\UnitGroup;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AdminRawMaterialController extends Controller
{
    /**
     * List all raw materials with searching and category filtering.
     */
    public function index(Request $request): JsonResponse
    {
        $query = RawMaterial::with(['category', 'unitGroup', 'unitModel']);

        if ($request->filled('search')) {
            $search = trim($request->query('search'));
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category_id')) {
            $query->where('raw_material_category_id', (int) $request->query('category_id'));
        }

        if ($request->query('status') === 'active') {
            $query->where('is_active', true);
        } elseif ($request->query('status') === 'inactive') {
            $query->where('is_active', false);
        }

        $query->orderBy('code', 'asc');

        if ($request->query('paginate') === 'true' || $request->has('page')) {
            $perPage = (int) $request->query('per_page', 15);
            $paginator = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => AdminRawMaterialResource::collection($paginator->getCollection()),
                'meta' => [
                    'current_page' => $paginator->currentPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                    'last_page' => $paginator->lastPage(),
                ],
            ]);
        }

        $materials = $query->get();

        return response()->json([
            'success' => true,
            'data' => AdminRawMaterialResource::collection($materials),
        ]);
    }

    /**
     * Get lookup options (Categories, Unit Groups, Base Units) for pickers.
     */
    public function options(): JsonResponse
    {
        $categories = RawMaterialCategory::active()->orderBy('name')->get(['id', 'name', 'code', 'unit_type']);
        $unitGroups = UnitGroup::with('units')->where('is_active', true)->orderBy('name')->get();
        $units = Unit::with('unitGroup')->where('is_active', true)->orderBy('name')->get();

        // Auto-generated code preview
        $latestId = RawMaterial::max('id') ?? 0;
        $codePreview = 'RM-' . str_pad($latestId + 1, 4, '0', STR_PAD_LEFT);

        return response()->json([
            'success' => true,
            'data' => [
                'code_preview' => $codePreview,
                'categories' => $categories,
                'unit_groups' => $unitGroups,
                'units' => $units,
            ],
        ]);
    }

    /**
     * Show single raw material details.
     */
    public function show(int $id): JsonResponse
    {
        $material = RawMaterial::with(['category', 'unitGroup', 'unitModel'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => new AdminRawMaterialResource($material),
        ]);
    }

    /**
     * Create a new raw material record.
     */
    public function store(StoreRawMaterialRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $validated['is_active'] = $validated['is_active'] ?? true;

        $material = RawMaterial::create($validated);
        $material->load(['category', 'unitGroup', 'unitModel']);

        return response()->json([
            'success' => true,
            'message' => "Raw Material \"{$material->name}\" created successfully.",
            'data' => new AdminRawMaterialResource($material),
        ], 201);
    }

    /**
     * Update an existing raw material record.
     */
    public function update(UpdateRawMaterialRequest $request, int $id): JsonResponse
    {
        $material = RawMaterial::findOrFail($id);
        $validated = $request->validated();

        $material->update($validated);
        $material->load(['category', 'unitGroup', 'unitModel']);

        return response()->json([
            'success' => true,
            'message' => "Raw Material \"{$material->name}\" updated successfully.",
            'data' => new AdminRawMaterialResource($material),
        ]);
    }

    /**
     * Toggle active/inactive status of a raw material.
     */
    public function toggleStatus(int $id): JsonResponse
    {
        $material = RawMaterial::findOrFail($id);
        $material->update(['is_active' => !$material->is_active]);

        $label = $material->is_active ? 'activated' : 'deactivated';

        return response()->json([
            'success' => true,
            'message' => "Raw Material \"{$material->name}\" {$label} successfully.",
            'data' => new AdminRawMaterialResource($material->load(['category', 'unitGroup', 'unitModel'])),
        ]);
    }

    /**
     * Delete a raw material record safely.
     */
    public function destroy(int $id): JsonResponse
    {
        $material = RawMaterial::findOrFail($id);

        if ($material->batches()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => "Cannot delete Raw Material \"{$material->name}\" — it has {$material->batches()->count()} linked inventory batch(es). Deactivate it instead.",
                'linked_batches_count' => $material->batches()->count(),
            ], 422);
        }

        $name = $material->name;
        $material->delete();

        return response()->json([
            'success' => true,
            'message' => "Raw Material \"{$name}\" deleted successfully.",
        ]);
    }
}
