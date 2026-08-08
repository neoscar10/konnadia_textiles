<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\UnitGroup;
use App\Http\Requests\Api\V1\Admin\StoreUnitGroupRequest;
use App\Http\Requests\Api\V1\Admin\UpdateUnitGroupRequest;
use App\Http\Resources\Api\V1\AdminUnitGroupResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AdminUnitGroupController extends Controller
{
    /**
     * List all unit groups.
     */
    public function index(Request $request): JsonResponse
    {
        $query = UnitGroup::with('units');

        if ($request->filled('search')) {
            $search = trim($request->query('search'));
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if ($request->query('status') === 'active') {
            $query->where('is_active', true);
        } elseif ($request->query('status') === 'inactive') {
            $query->where('is_active', false);
        }

        $query->orderBy('name');

        if ($request->query('paginate') === 'true') {
            $perPage = (int) $request->query('per_page', 15);
            $paginator = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => AdminUnitGroupResource::collection($paginator->getCollection()),
                'pagination' => [
                    'total' => $paginator->total(),
                    'count' => $paginator->count(),
                    'per_page' => $paginator->perPage(),
                    'current_page' => $paginator->currentPage(),
                    'total_pages' => $paginator->lastPage(),
                ],
            ]);
        }

        $groups = $query->get();

        return response()->json([
            'success' => true,
            'data' => AdminUnitGroupResource::collection($groups),
        ]);
    }

    /**
     * Show single unit group details.
     */
    public function show(int $id): JsonResponse
    {
        $group = UnitGroup::with('units')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => new AdminUnitGroupResource($group),
        ]);
    }

    /**
     * Create a new unit group.
     */
    public function store(StoreUnitGroupRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $validated['code'] = strtoupper($validated['code']);

        $group = UnitGroup::create($validated);
        $group->load('units');

        return response()->json([
            'success' => true,
            'message' => "Unit Group [{$group->name}] created successfully.",
            'data' => new AdminUnitGroupResource($group),
        ], 201);
    }

    /**
     * Update an existing unit group.
     */
    public function update(UpdateUnitGroupRequest $request, int $id): JsonResponse
    {
        $group = UnitGroup::with('units')->findOrFail($id);
        $validated = $request->validated();

        if (isset($validated['code'])) {
            $validated['code'] = strtoupper($validated['code']);
        }

        $group->update($validated);

        return response()->json([
            'success' => true,
            'message' => "Unit Group [{$group->name}] updated successfully.",
            'data' => new AdminUnitGroupResource($group),
        ]);
    }

    /**
     * Toggle active status of a unit group.
     */
    public function toggleStatus(int $id): JsonResponse
    {
        $group = UnitGroup::with('units')->findOrFail($id);
        $group->update(['is_active' => !$group->is_active]);

        $statusText = $group->is_active ? 'activated' : 'deactivated';

        return response()->json([
            'success' => true,
            'message' => "Unit Group [{$group->name}] {$statusText} successfully.",
            'data' => new AdminUnitGroupResource($group),
        ]);
    }

    /**
     * Delete a unit group.
     */
    public function destroy(int $id): JsonResponse
    {
        $group = UnitGroup::findOrFail($id);

        if ($group->units()->count() > 0) {
            $group->units()->delete();
        }

        $group->delete();

        return response()->json([
            'success' => true,
            'message' => "Unit Group deleted successfully.",
        ]);
    }
}
