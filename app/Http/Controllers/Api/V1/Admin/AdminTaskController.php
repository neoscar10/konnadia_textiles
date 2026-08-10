<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\RawMaterialCategory;
use App\Http\Requests\Api\V1\Admin\StoreTaskRequest;
use App\Http\Requests\Api\V1\Admin\UpdateTaskRequest;
use App\Http\Requests\Api\V1\Admin\ReorderTaskRequest;
use App\Http\Resources\Api\V1\AdminTaskResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class AdminTaskController extends Controller
{
    /**
     * List all factory tasks with searching, filtering, and ordering.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Task::with('rawMaterialCategories')->withCount('manufacturingProducts');

        if ($request->filled('search')) {
            $search = trim($request->query('search'));
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if ($request->query('status') === 'active') {
            $query->where('status', true);
        } elseif ($request->query('status') === 'inactive') {
            $query->where('status', false);
        }

        $query->ordered();

        if ($request->query('paginate') === 'true' || $request->has('page')) {
            $perPage = (int) $request->query('per_page', 15);
            $paginator = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => AdminTaskResource::collection($paginator->getCollection()),
                'pagination' => [
                    'total' => $paginator->total(),
                    'count' => $paginator->count(),
                    'per_page' => $paginator->perPage(),
                    'current_page' => $paginator->currentPage(),
                    'total_pages' => $paginator->lastPage(),
                ],
            ]);
        }

        $tasks = $query->get();

        return response()->json([
            'success' => true,
            'data' => AdminTaskResource::collection($tasks),
        ]);
    }

    /**
     * Get lightweight task list and available raw material categories for pickers.
     */
    public function options(): JsonResponse
    {
        $tasks = Task::where('status', true)->ordered()->get(['id', 'name', 'code', 'consumes_raw_material', 'is_labor_required', 'sequence_number']);
        $categories = RawMaterialCategory::where('is_active', true)->orderBy('name')->get(['id', 'name', 'code', 'unit_type']);

        return response()->json([
            'success' => true,
            'data' => [
                'tasks' => $tasks,
                'raw_material_categories' => $categories,
            ],
        ]);
    }

    /**
     * Show single factory task details.
     */
    public function show(int $id): JsonResponse
    {
        $task = Task::with('rawMaterialCategories')->withCount('manufacturingProducts')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => new AdminTaskResource($task),
        ]);
    }

    /**
     * Create a new factory task.
     */
    public function store(StoreTaskRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $task = DB::transaction(function () use ($validated) {
            $seq = $validated['sequence_number'] ?? null;
            if (empty($seq)) {
                $maxSeq = Task::max('sequence_number') ?? 0;
                $seq = $maxSeq + 1;
            }

            $task = Task::create([
                'name' => $validated['name'],
                'code' => $validated['code'] ?? null,
                'status' => $validated['status'] ?? true,
                'consumes_raw_material' => $validated['consumes_raw_material'],
                'is_labor_required' => $validated['is_labor_required'],
                'sequence_number' => $seq,
            ]);

            if ($task->consumes_raw_material && !empty($validated['selected_category_ids'])) {
                $task->rawMaterialCategories()->sync($validated['selected_category_ids']);
            }

            return $task;
        });

        $task->load('rawMaterialCategories')->loadCount('manufacturingProducts');

        return response()->json([
            'success' => true,
            'message' => "Factory Task \"{$task->name}\" created successfully.",
            'data' => new AdminTaskResource($task),
        ], 201);
    }

    /**
     * Update an existing factory task.
     */
    public function update(UpdateTaskRequest $request, int $id): JsonResponse
    {
        $task = Task::with('rawMaterialCategories')->withCount('manufacturingProducts')->findOrFail($id);
        $validated = $request->validated();

        DB::transaction(function () use ($task, $validated) {
            $task->update([
                'name' => $validated['name'],
                'code' => $validated['code'] ?? $task->code,
                'status' => $validated['status'] ?? $task->status,
                'consumes_raw_material' => $validated['consumes_raw_material'],
                'is_labor_required' => $validated['is_labor_required'],
                'sequence_number' => $validated['sequence_number'] ?? $task->sequence_number,
            ]);

            if ($task->consumes_raw_material && !empty($validated['selected_category_ids'])) {
                $task->rawMaterialCategories()->sync($validated['selected_category_ids']);
            } else {
                $task->rawMaterialCategories()->detach();
            }
        });

        $task->refresh()->load('rawMaterialCategories')->loadCount('manufacturingProducts');

        return response()->json([
            'success' => true,
            'message' => "Factory Task \"{$task->name}\" updated successfully.",
            'data' => new AdminTaskResource($task),
        ]);
    }

    /**
     * Toggle active/inactive status of a task.
     */
    public function toggleStatus(int $id): JsonResponse
    {
        $task = Task::with('rawMaterialCategories')->withCount('manufacturingProducts')->findOrFail($id);
        $task->update(['status' => !$task->status]);

        $label = $task->status ? 'activated' : 'deactivated';

        return response()->json([
            'success' => true,
            'message' => "Factory Task \"{$task->name}\" {$label} successfully.",
            'data' => new AdminTaskResource($task),
        ]);
    }

    /**
     * Reorder task sequence numbers.
     */
    public function reorder(ReorderTaskRequest $request): JsonResponse
    {
        $orderedIds = $request->validated()['ordered_ids'];

        DB::transaction(function () use ($orderedIds) {
            foreach ($orderedIds as $index => $id) {
                Task::where('id', $id)->update([
                    'sequence_number' => $index + 1,
                ]);
            }
        });

        $tasks = Task::with('rawMaterialCategories')->ordered()->get();

        return response()->json([
            'success' => true,
            'message' => 'Task sequence order updated successfully.',
            'data' => AdminTaskResource::collection($tasks),
        ]);
    }

    /**
     * Delete a factory task safely.
     */
    public function destroy(int $id): JsonResponse
    {
        $task = Task::withCount('manufacturingProducts')->findOrFail($id);

        if ($task->manufacturing_products_count > 0) {
            return response()->json([
                'success' => false,
                'message' => "Cannot delete task \"{$task->name}\" — it is currently linked to {$task->manufacturing_products_count} manufacturing product routing(s). Deactivate it instead.",
                'linked_products_count' => $task->manufacturing_products_count,
            ], 422);
        }

        $name = $task->name;
        $task->delete();

        return response()->json([
            'success' => true,
            'message' => "Factory Task \"{$name}\" deleted successfully.",
        ]);
    }
}
