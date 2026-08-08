<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Unit;
use App\Models\UnitGroup;
use App\Services\UnitConversionService;
use App\Http\Requests\Api\V1\Admin\StoreUnitRecordRequest;
use App\Http\Requests\Api\V1\Admin\UpdateUnitRecordRequest;
use App\Http\Requests\Api\V1\Admin\ConvertUnitRequest;
use App\Http\Resources\Api\V1\AdminUnitRecordResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AdminUnitController extends Controller
{
    /**
     * List all unit records with optional filtering & search.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Unit::with('unitGroup');

        if ($request->filled('unit_group_id')) {
            $query->where('unit_group_id', (int) $request->query('unit_group_id'));
        }

        if ($request->filled('search')) {
            $search = trim($request->query('search'));
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('short_code', 'like', "%{$search}%");
            });
        }

        if ($request->query('status') === 'active') {
            $query->where('is_active', true);
        } elseif ($request->query('status') === 'inactive') {
            $query->where('is_active', false);
        }

        if ($request->has('is_base')) {
            $isBase = filter_var($request->query('is_base'), FILTER_VALIDATE_BOOLEAN);
            $query->where('is_base', $isBase);
        }

        $query->orderBy('is_base', 'desc')->orderBy('name');

        if ($request->query('paginate') === 'true') {
            $perPage = (int) $request->query('per_page', 15);
            $paginator = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => AdminUnitRecordResource::collection($paginator->getCollection()),
                'pagination' => [
                    'total' => $paginator->total(),
                    'count' => $paginator->count(),
                    'per_page' => $paginator->perPage(),
                    'current_page' => $paginator->currentPage(),
                    'total_pages' => $paginator->lastPage(),
                ],
            ]);
        }

        $units = $query->get();

        return response()->json([
            'success' => true,
            'data' => AdminUnitRecordResource::collection($units),
        ]);
    }

    /**
     * Show single unit record details.
     */
    public function show(int $id): JsonResponse
    {
        $unit = Unit::with('unitGroup')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => new AdminUnitRecordResource($unit),
        ]);
    }

    /**
     * Create a new unit record.
     */
    public function store(StoreUnitRecordRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $groupId = (int) $validated['unit_group_id'];
        $isBase = !empty($validated['is_base']);

        $group = UnitGroup::with('units')->findOrFail($groupId);

        // If group has no existing units, default this unit to Base Unit
        if ($group->units->isEmpty()) {
            $isBase = true;
        }

        if ($isBase) {
            Unit::where('unit_group_id', $groupId)->update(['is_base' => false]);
            $ratioToBase = 1.0;
        } else {
            $ratioToBase = (float) ($validated['ratio_to_base'] ?? 1.0);
        }

        $unit = Unit::create([
            'unit_group_id' => $groupId,
            'name' => $validated['name'],
            'short_code' => $validated['short_code'],
            'is_base' => $isBase,
            'ratio_to_base' => $ratioToBase,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        $unit->load('unitGroup');

        return response()->json([
            'success' => true,
            'message' => "Unit [{$unit->name}] added successfully.",
            'data' => new AdminUnitRecordResource($unit),
        ], 201);
    }

    /**
     * Update an existing unit record.
     */
    public function update(UpdateUnitRecordRequest $request, int $id): JsonResponse
    {
        $unit = Unit::with('unitGroup')->findOrFail($id);
        $validated = $request->validated();

        $groupId = isset($validated['unit_group_id']) ? (int) $validated['unit_group_id'] : $unit->unit_group_id;
        $isBase = isset($validated['is_base']) ? (bool) $validated['is_base'] : $unit->is_base;

        if ($isBase) {
            Unit::where('unit_group_id', $groupId)->update(['is_base' => false]);
            $ratioToBase = 1.0;
        } else {
            $ratioToBase = isset($validated['ratio_to_base']) ? (float) $validated['ratio_to_base'] : (float) $unit->ratio_to_base;
        }

        $unit->update([
            'unit_group_id' => $groupId,
            'name' => $validated['name'] ?? $unit->name,
            'short_code' => $validated['short_code'] ?? $unit->short_code,
            'is_base' => $isBase,
            'ratio_to_base' => $ratioToBase,
            'is_active' => isset($validated['is_active']) ? (bool) $validated['is_active'] : $unit->is_active,
        ]);

        $unit->load('unitGroup');

        return response()->json([
            'success' => true,
            'message' => "Unit [{$unit->name}] updated successfully.",
            'data' => new AdminUnitRecordResource($unit),
        ]);
    }

    /**
     * Toggle active status of a unit record.
     */
    public function toggleStatus(int $id): JsonResponse
    {
        $unit = Unit::with('unitGroup')->findOrFail($id);
        $unit->update(['is_active' => !$unit->is_active]);

        $statusText = $unit->is_active ? 'activated' : 'deactivated';

        return response()->json([
            'success' => true,
            'message' => "Unit [{$unit->name}] {$statusText} successfully.",
            'data' => new AdminUnitRecordResource($unit),
        ]);
    }

    /**
     * Set a unit as the Base Unit for its group.
     */
    public function setBaseUnit(int $id): JsonResponse
    {
        $unit = Unit::with('unitGroup')->findOrFail($id);

        Unit::where('unit_group_id', $unit->unit_group_id)->update(['is_base' => false]);
        $unit->update(['is_base' => true, 'ratio_to_base' => 1.0]);

        $unit->load('unitGroup');

        return response()->json([
            'success' => true,
            'message' => "[{$unit->name}] is now set as the Base Unit for {$unit->unitGroup?->name}.",
            'data' => new AdminUnitRecordResource($unit),
        ]);
    }

    /**
     * Delete a unit record.
     */
    public function destroy(int $id): JsonResponse
    {
        $unit = Unit::findOrFail($id);

        if ($unit->is_base) {
            $otherUnitsCount = Unit::where('unit_group_id', $unit->unit_group_id)
                ->where('id', '!=', $unit->id)
                ->count();

            if ($otherUnitsCount > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete the Base Unit of a group. Set another unit as base first.',
                ], 422);
            }
        }

        $unit->delete();

        return response()->json([
            'success' => true,
            'message' => 'Unit deleted successfully.',
        ]);
    }

    /**
     * Perform conversion between two units.
     */
    public function convert(ConvertUnitRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $fromUnit = $validated['from_unit'];
        $toUnit = $validated['to_unit'];
        $quantity = (float) $validated['quantity'];
        $groupId = isset($validated['unit_group_id']) ? (int) $validated['unit_group_id'] : null;

        try {
            $convertedQuantity = UnitConversionService::convert($quantity, $fromUnit, $toUnit, $groupId);

            // Fetch unit models for rich mobile payload metadata
            $fromModel = is_numeric($fromUnit) ? Unit::find($fromUnit) : Unit::where('name', $fromUnit)->orWhere('short_code', $fromUnit)->first();
            $toModel = is_numeric($toUnit) ? Unit::find($toUnit) : Unit::where('name', $toUnit)->orWhere('short_code', $toUnit)->first();

            return response()->json([
                'success' => true,
                'data' => [
                    'input' => [
                        'unit' => $fromModel ? $fromModel->name : $fromUnit,
                        'short_code' => $fromModel ? $fromModel->short_code : null,
                        'quantity' => $quantity,
                    ],
                    'output' => [
                        'unit' => $toModel ? $toModel->name : $toUnit,
                        'short_code' => $toModel ? $toModel->short_code : null,
                        'quantity' => $convertedQuantity,
                    ],
                    'conversion_ratio' => $quantity > 0 ? ($convertedQuantity / $quantity) : 1.0,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Preview relationship calculations live for mobile applications.
     */
    public function previewRelationship(Request $request): JsonResponse
    {
        $request->validate([
            'unit_group_id' => 'required|integer|exists:unit_groups,id',
            'name' => 'nullable|string',
            'short_code' => 'nullable|string',
            'is_base' => 'nullable|boolean',
            'ratio_to_base' => 'nullable|numeric',
        ]);

        $group = UnitGroup::with('units')->findOrFail($request->input('unit_group_id'));
        $baseUnit = $group->units->firstWhere('is_base', true);

        $name = trim($request->input('name', '')) !== '' ? trim($request->input('name')) : 'Unit';
        $code = trim($request->input('short_code', '')) !== '' ? trim($request->input('short_code')) : 'code';
        $isBase = (bool) $request->input('is_base', false);

        $rawRatio = is_numeric($request->input('ratio_to_base')) ? (float) $request->input('ratio_to_base') : 1.0;
        $ratio = $rawRatio > 0 ? $rawRatio : 1.0;

        if ($isBase) {
            return response()->json([
                'success' => true,
                'data' => [
                    'type' => 'base',
                    'title' => 'Base Unit Designation',
                    'primary' => "1 {$name} ({$code}) will be set as the Base Unit for {$group->name}",
                    'explanation' => "All other units in this group will calculate their quantities relative to 1 {$code}.",
                ]
            ]);
        }

        if (!$baseUnit) {
            return response()->json([
                'success' => true,
                'data' => [
                    'type' => 'no_base',
                    'title' => 'No Base Unit Set',
                    'primary' => "This group currently has no base unit configured.",
                    'explanation' => "Check 'Set as Base Unit' to make {$name} the primary reference unit for {$group->name}.",
                ]
            ]);
        }

        $formattedRatio = (floor($ratio) == $ratio) ? number_format($ratio, 0) : rtrim(rtrim(number_format($ratio, 6), '0'), '.');
        $primaryStatement = "1 {$name} ({$code}) = {$formattedRatio} {$baseUnit->name} ({$baseUnit->short_code})";

        $explanation = "";
        if ($ratio < 1 && $ratio > 0) {
            $reciprocal = 1 / $ratio;
            $formattedReciprocal = (floor($reciprocal) == $reciprocal) ? number_format($reciprocal, 0) : rtrim(rtrim(number_format($reciprocal, 6), '0'), '.');
            $explanation = "1 {$baseUnit->name} ({$baseUnit->short_code}) = {$formattedReciprocal} {$name} ({$code})";
        } else {
            $explanation = "Every 1 {$name} ({$code}) used in manufacturing or stock counts as {$formattedRatio} {$baseUnit->name} ({$baseUnit->short_code})";
        }

        return response()->json([
            'success' => true,
            'data' => [
                'type' => 'relationship',
                'title' => 'Live Relationship Preview',
                'primary' => $primaryStatement,
                'explanation' => $explanation,
                'unit_name' => $name,
                'unit_code' => $code,
                'base_unit_name' => $baseUnit->name,
                'base_unit_code' => $baseUnit->short_code,
                'ratio' => $ratio,
            ]
        ]);
    }

    /**
     * Get system unit templates & level configurations (backward compatibility).
     */
    public function templates(): JsonResponse
    {
        $units = [
            'default_level1' => [
                'name' => 'Piece',
                'short_code' => 'pcs',
                'conversion_to_base' => 1.0,
            ],
            'common_level2_templates' => [
                ['name' => 'Set (4 Pcs)', 'short_code' => 'set', 'conversion_to_base' => 4.0],
                ['name' => 'Box (10 Pcs)', 'short_code' => 'box', 'conversion_to_base' => 10.0],
                ['name' => 'Carton (50 Pcs)', 'short_code' => 'ctn', 'conversion_to_base' => 50.0],
            ]
        ];

        return response()->json([
            'success' => true,
            'data' => $units,
        ]);
    }
}
