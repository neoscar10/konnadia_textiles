<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\StoreFabricWidthRequest;
use App\Http\Requests\Api\V1\Admin\UpdateFabricWidthRequest;
use App\Http\Resources\Api\V1\AdminFabricWidthResource;
use App\Models\FabricWidth;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AdminFabricWidthController extends Controller
{
    /**
     * List all fabric widths.
     */
    public function index(Request $request): JsonResponse
    {
        $query = FabricWidth::with('unitModel');

        if ($request->filled('search')) {
            $search = trim($request->query('search'));
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('value', 'like', "%{$search}%");
            });
        }

        if ($request->query('status') === 'active') {
            $query->where('status', true);
        } elseif ($request->query('status') === 'inactive') {
            $query->where('status', false);
        }

        $query->orderBy('value', 'asc');

        if ($request->query('paginate') === 'true' || $request->has('page')) {
            $perPage = max(1, min(100, (int) $request->query('per_page', 15)));
            $paginator = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'message' => 'Fabric widths retrieved successfully.',
                'data' => AdminFabricWidthResource::collection($paginator->getCollection()),
                'meta' => [
                    'current_page' => $paginator->currentPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                    'last_page' => $paginator->lastPage(),
                ],
            ]);
        }

        $fabricWidths = $query->get();

        return response()->json([
            'success' => true,
            'message' => 'Fabric widths retrieved successfully.',
            'data' => AdminFabricWidthResource::collection($fabricWidths),
        ]);
    }

    /**
     * Options for fabric width pickers.
     */
    public function options(): JsonResponse
    {
        $activeWidths = FabricWidth::active()->with('unitModel')->orderBy('value', 'asc')->get();
        $units = Unit::where('is_active', true)->orderBy('name')->get(['id', 'name', 'short_code']);

        return response()->json([
            'success' => true,
            'data' => [
                'fabric_widths' => AdminFabricWidthResource::collection($activeWidths),
                'units' => $units,
            ],
        ]);
    }

    /**
     * Show detail of a single fabric width.
     */
    public function show(int $id): JsonResponse
    {
        $fabricWidth = FabricWidth::with('unitModel')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => new AdminFabricWidthResource($fabricWidth),
        ]);
    }

    /**
     * Create a new fabric width.
     */
    public function store(StoreFabricWidthRequest $request): JsonResponse
    {
        $validated = $request->validated();
        
        if (!empty($validated['unit_id'])) {
            $unitObj = Unit::find($validated['unit_id']);
            if ($unitObj) {
                $validated['unit'] = $unitObj->short_code;
            }
        }
        if (empty($validated['unit'])) {
            $validated['unit'] = 'IN';
        }

        $fabricWidth = FabricWidth::create($validated);
        $fabricWidth->load('unitModel');

        return response()->json([
            'success' => true,
            'message' => "Fabric width \"{$fabricWidth->value}{$fabricWidth->unit}\" created successfully.",
            'data' => new AdminFabricWidthResource($fabricWidth),
        ], 201);
    }

    /**
     * Update an existing fabric width.
     */
    public function update(UpdateFabricWidthRequest $request, int $id): JsonResponse
    {
        $fabricWidth = FabricWidth::findOrFail($id);
        $validated = $request->validated();

        if (!empty($validated['unit_id'])) {
            $unitObj = Unit::find($validated['unit_id']);
            if ($unitObj) {
                $validated['unit'] = $unitObj->short_code;
            }
        }

        $fabricWidth->update($validated);
        $fabricWidth->load('unitModel');

        return response()->json([
            'success' => true,
            'message' => "Fabric width updated successfully.",
            'data' => new AdminFabricWidthResource($fabricWidth),
        ]);
    }

    /**
     * Toggle active/inactive status.
     */
    public function toggleStatus(int $id): JsonResponse
    {
        $fabricWidth = FabricWidth::findOrFail($id);
        $fabricWidth->update(['status' => !$fabricWidth->status]);

        $label = $fabricWidth->status ? 'activated' : 'deactivated';

        return response()->json([
            'success' => true,
            'message' => "Fabric width {$label} successfully.",
            'data' => new AdminFabricWidthResource($fabricWidth->load('unitModel')),
        ]);
    }

    /**
     * Delete a fabric width safely.
     */
    public function destroy(int $id): JsonResponse
    {
        $fabricWidth = FabricWidth::findOrFail($id);

        if ($fabricWidth->isInUse()) {
            return response()->json([
                'success' => false,
                'message' => "Cannot delete Fabric Width \"{$fabricWidth->value}{$fabricWidth->unit}\" — it is currently referenced by raw materials, fabric rolls, or product patterns. Deactivate it instead.",
            ], 422);
        }

        $display = "{$fabricWidth->value}{$fabricWidth->unit}";
        $fabricWidth->delete();

        return response()->json([
            'success' => true,
            'message' => "Fabric width \"{$display}\" deleted successfully.",
        ]);
    }
}
