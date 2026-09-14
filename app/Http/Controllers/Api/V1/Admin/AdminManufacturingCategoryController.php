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
        $query = ManufacturingProductCategory::with(['defaultTasks'])->withCount('manufacturingProducts');

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
            ->with(['defaultTasks'])
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => AdminManufacturingCategoryResource::collection($categories),
        ]);
    }

    /**
     * Show single manufacturing product category details.
     */
    public function show(int $id): JsonResponse
    {
        $category = ManufacturingProductCategory::with(['defaultTasks'])->withCount('manufacturingProducts')->findOrFail($id);

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

        $category = ManufacturingProductCategory::create([
            'name' => $validated['name'],
            'status' => $validated['status'],
        ]);

        $this->syncDefaultTasks($category, $validated['default_tasks'] ?? null);
        $category->load(['defaultTasks'])->loadCount('manufacturingProducts');

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

        if (array_key_exists('name', $validated)) {
            $category->name = $validated['name'];
        }
        if (array_key_exists('status', $validated)) {
            $category->status = $validated['status'];
        }
        $category->save();

        if (array_key_exists('default_tasks', $validated)) {
            $this->syncDefaultTasks($category, $validated['default_tasks']);
        }

        $category->load(['defaultTasks'])->loadCount('manufacturingProducts');

        return response()->json([
            'success' => true,
            'message' => "Manufacturing product category \"{$category->name}\" updated successfully.",
            'data' => new AdminManufacturingCategoryResource($category),
        ]);
    }

    /**
     * Helper to sync default task routing sequence for category.
     */
    protected function syncDefaultTasks(ManufacturingProductCategory $category, ?array $defaultTasks): void
    {
        if (is_array($defaultTasks)) {
            $syncData = [];
            $validTasks = array_filter($defaultTasks, fn($row) => !empty($row['task_id']));
            
            $hasFinal = false;
            foreach ($validTasks as $r) {
                if (!empty($r['is_final_step'])) $hasFinal = true;
            }
            $lastIndex = count($validTasks) - 1;

            $seq = 1;
            foreach (array_values($validTasks) as $idx => $r) {
                $isFinal = $hasFinal ? !empty($r['is_final_step']) : ($idx === $lastIndex);
                $taskSeq = !empty($r['sequence_number']) ? (int) $r['sequence_number'] : $seq++;
                $syncData[$r['task_id']] = [
                    'sequence_number' => $taskSeq,
                    'standard_labor_rate' => array_key_exists('standard_labor_rate', $r) && $r['standard_labor_rate'] !== null && $r['standard_labor_rate'] !== '' ? $r['standard_labor_rate'] : null,
                    'is_final_step' => $isFinal,
                ];
            }
            $category->defaultTasks()->sync($syncData);
        }
    }

    /**
     * Toggle active/inactive status of a category.
     */
    public function toggleStatus(int $id): JsonResponse
    {
        $category = ManufacturingProductCategory::with(['defaultTasks'])->withCount('manufacturingProducts')->findOrFail($id);
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
