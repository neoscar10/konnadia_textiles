<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\LaborCategory;
use App\Http\Requests\Api\V1\Admin\StoreLaborCategoryRequest;
use App\Http\Requests\Api\V1\Admin\UpdateLaborCategoryRequest;
use App\Http\Resources\Api\V1\AdminLaborCategoryResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AdminLaborCategoryController extends Controller
{
    /**
     * List all labor categories with search, filtering, and pagination.
     */
    public function index(Request $request): JsonResponse
    {
        $query = LaborCategory::withCount(['tasks', 'labors']);

        if ($request->filled('search')) {
            $search = trim($request->query('search'));
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->has('status') && $request->query('status') !== '') {
            $statusVal = $request->query('status');
            if ($statusVal === 'active' || $statusVal === '1' || $statusVal === 'true' || $statusVal === 1) {
                $query->where('status', true);
            } elseif ($statusVal === 'inactive' || $statusVal === '0' || $statusVal === 'false' || $statusVal === 0) {
                $query->where('status', false);
            }
        }

        $query->orderBy('name');

        $summary = [
            'total_count' => LaborCategory::count(),
            'active_count' => LaborCategory::where('status', true)->count(),
            'inactive_count' => LaborCategory::where('status', false)->count(),
        ];

        if ($request->query('paginate') === 'true' || $request->has('page')) {
            $perPage = (int) $request->query('per_page', 15);
            $paginator = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'summary' => $summary,
                'data' => AdminLaborCategoryResource::collection($paginator->getCollection()),
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
            'summary' => $summary,
            'data' => AdminLaborCategoryResource::collection($categories),
        ]);
    }

    /**
     * Get lightweight options for mobile form pickers and dropdowns.
     */
    public function options(): JsonResponse
    {
        $categories = LaborCategory::active()
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'status']);

        return response()->json([
            'success' => true,
            'data' => [
                'labor_categories' => $categories,
                'total_active' => $categories->count(),
            ],
        ]);
    }

    /**
     * Display single labor category details.
     */
    public function show(int $id): JsonResponse
    {
        $category = LaborCategory::withCount(['tasks', 'labors'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => new AdminLaborCategoryResource($category),
        ]);
    }

    /**
     * Store a new labor category record.
     */
    public function store(StoreLaborCategoryRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $data = [
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'status' => $validated['status'] ?? true,
        ];

        if (!empty($validated['code'])) {
            $data['code'] = $validated['code'];
        }

        $category = LaborCategory::create($data);
        $category->loadCount(['tasks', 'labors']);

        return response()->json([
            'success' => true,
            'message' => "Labour Category [{$category->name}] created successfully.",
            'data' => new AdminLaborCategoryResource($category),
        ], 201);
    }

    /**
     * Update an existing labor category record.
     */
    public function update(UpdateLaborCategoryRequest $request, int $id): JsonResponse
    {
        $category = LaborCategory::withCount(['tasks', 'labors'])->findOrFail($id);
        $validated = $request->validated();

        $data = [
            'name' => $validated['name'],
            'description' => $validated['description'] ?? $category->description,
            'status' => $validated['status'] ?? $category->status,
        ];

        if (array_key_exists('code', $validated)) {
            $data['code'] = $validated['code'];
        }

        $category->update($data);
        $category->refresh()->loadCount(['tasks', 'labors']);

        return response()->json([
            'success' => true,
            'message' => "Labour Category [{$category->name}] updated successfully.",
            'data' => new AdminLaborCategoryResource($category),
        ]);
    }

    /**
     * Toggle active/inactive status of a labor category.
     */
    public function toggleStatus(int $id): JsonResponse
    {
        $category = LaborCategory::withCount(['tasks', 'labors'])->findOrFail($id);
        $category->update(['status' => !$category->status]);

        $statusText = $category->status ? 'Active' : 'Inactive';

        return response()->json([
            'success' => true,
            'message' => "Labour Category [{$category->name}] status set to {$statusText}.",
            'data' => new AdminLaborCategoryResource($category),
        ]);
    }

    /**
     * Delete a labor category safely.
     */
    public function destroy(int $id): JsonResponse
    {
        $category = LaborCategory::withCount(['tasks', 'labors'])->findOrFail($id);

        if ($category->tasks_count > 0 || $category->labors_count > 0) {
            $msg = "Cannot delete category [{$category->name}] because it is currently assigned to ";
            $parts = [];
            if ($category->tasks_count > 0) $parts[] = "{$category->tasks_count} task(s)";
            if ($category->labors_count > 0) $parts[] = "{$category->labors_count} worker(s)";
            $msg .= implode(' and ', $parts) . '.';

            return response()->json([
                'success' => false,
                'message' => $msg,
                'tasks_count' => $category->tasks_count,
                'labors_count' => $category->labors_count,
            ], 422);
        }

        $name = $category->name;
        $category->delete();

        return response()->json([
            'success' => true,
            'message' => "Labour Category [{$name}] deleted successfully.",
        ]);
    }
}
