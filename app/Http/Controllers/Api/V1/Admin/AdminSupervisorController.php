<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\StoreSupervisorRequest;
use App\Http\Requests\Api\V1\Admin\UpdateSupervisorRequest;
use App\Http\Resources\Api\V1\AdminSupervisorResource;
use App\Models\FactorySupervisor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminSupervisorController extends Controller
{
    /**
     * List all factory floor supervisors with searching, status/department filtering, and pagination.
     */
    public function index(Request $request): JsonResponse
    {
        $query = FactorySupervisor::withCount('productionBatches');

        if ($request->filled('search')) {
            $search = trim($request->query('search'));
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('department', 'like', "%{$search}%");
            });
        }

        $statusFilter = $request->query('status') ?? $request->query('status_filter');
        if ($statusFilter === 'active') {
            $query->where('is_active', true);
        } elseif ($statusFilter === 'inactive') {
            $query->where('is_active', false);
        }

        if ($request->filled('department')) {
            $department = trim($request->query('department'));
            $query->where('department', 'like', "%{$department}%");
        }

        $query->orderBy('name', 'asc');

        if ($request->query('paginate') === 'true' || $request->has('page')) {
            $perPage = max(1, min(100, (int) $request->query('per_page', 15)));
            $paginator = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'message' => 'Supervisors retrieved successfully.',
                'data' => AdminSupervisorResource::collection($paginator->getCollection()),
                'meta' => [
                    'current_page' => $paginator->currentPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                    'last_page' => $paginator->lastPage(),
                ],
                'pagination' => [
                    'total' => $paginator->total(),
                    'count' => $paginator->count(),
                    'per_page' => $paginator->perPage(),
                    'current_page' => $paginator->currentPage(),
                    'total_pages' => $paginator->lastPage(),
                ],
            ]);
        }

        $supervisors = $query->get();

        return response()->json([
            'success' => true,
            'message' => 'Supervisors retrieved successfully.',
            'data' => AdminSupervisorResource::collection($supervisors),
        ]);
    }

    /**
     * Get lightweight supervisor list and department list for pickers/dropdowns.
     */
    public function options(): JsonResponse
    {
        $supervisors = FactorySupervisor::active()
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'department', 'phone', 'email']);

        $departments = FactorySupervisor::whereNotNull('department')
            ->where('department', '!=', '')
            ->distinct()
            ->pluck('department')
            ->sort()
            ->values();

        return response()->json([
            'success' => true,
            'data' => [
                'supervisors' => $supervisors,
                'departments' => $departments,
            ],
        ]);
    }

    /**
     * Display a single supervisor's details with linked batch statistics.
     */
    public function show(int $id): JsonResponse
    {
        $supervisor = FactorySupervisor::withCount('productionBatches')
            ->with(['productionBatches' => function ($q) {
                $q->with('manufacturingProduct')->orderBy('id', 'desc')->take(10);
            }])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => new AdminSupervisorResource($supervisor),
        ]);
    }

    /**
     * Create a new supervisor record.
     */
    public function store(StoreSupervisorRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $data = [
            'name'       => trim($validated['name']),
            'phone'      => isset($validated['phone']) && trim($validated['phone']) !== '' ? trim($validated['phone']) : null,
            'email'      => isset($validated['email']) && trim($validated['email']) !== '' ? trim($validated['email']) : null,
            'department' => isset($validated['department']) && trim($validated['department']) !== '' ? trim($validated['department']) : null,
            'notes'      => isset($validated['notes']) && trim($validated['notes']) !== '' ? trim($validated['notes']) : null,
            'is_active'  => $validated['is_active'] ?? true,
        ];

        if (isset($validated['code']) && trim($validated['code']) !== '') {
            $data['code'] = strtoupper(trim($validated['code']));
        }

        $supervisor = FactorySupervisor::create($data);
        $supervisor->loadCount('productionBatches');

        return response()->json([
            'success' => true,
            'message' => "Supervisor \"{$supervisor->name}\" created successfully.",
            'data' => new AdminSupervisorResource($supervisor),
        ], 201);
    }

    /**
     * Update an existing supervisor record.
     */
    public function update(UpdateSupervisorRequest $request, int $id): JsonResponse
    {
        $supervisor = FactorySupervisor::withCount('productionBatches')->findOrFail($id);
        $validated = $request->validated();

        $data = [];

        if (array_key_exists('name', $validated)) {
            $data['name'] = trim($validated['name']);
        }
        if (array_key_exists('phone', $validated)) {
            $data['phone'] = $validated['phone'] !== null && trim($validated['phone']) !== '' ? trim($validated['phone']) : null;
        }
        if (array_key_exists('email', $validated)) {
            $data['email'] = $validated['email'] !== null && trim($validated['email']) !== '' ? trim($validated['email']) : null;
        }
        if (array_key_exists('department', $validated)) {
            $data['department'] = $validated['department'] !== null && trim($validated['department']) !== '' ? trim($validated['department']) : null;
        }
        if (array_key_exists('notes', $validated)) {
            $data['notes'] = $validated['notes'] !== null && trim($validated['notes']) !== '' ? trim($validated['notes']) : null;
        }
        if (array_key_exists('is_active', $validated)) {
            $data['is_active'] = (bool) $validated['is_active'];
        }
        if (array_key_exists('code', $validated)) {
            if ($validated['code'] !== null && trim($validated['code']) !== '') {
                $data['code'] = strtoupper(trim($validated['code']));
            }
        }

        $supervisor->update($data);
        $supervisor->refresh()->loadCount('productionBatches');

        return response()->json([
            'success' => true,
            'message' => "Supervisor \"{$supervisor->name}\" updated successfully.",
            'data' => new AdminSupervisorResource($supervisor),
        ]);
    }

    /**
     * Toggle active status of a supervisor.
     */
    public function toggleStatus(int $id): JsonResponse
    {
        $supervisor = FactorySupervisor::withCount('productionBatches')->findOrFail($id);
        $supervisor->update(['is_active' => !$supervisor->is_active]);

        $status = $supervisor->is_active ? 'Active' : 'Inactive';

        return response()->json([
            'success' => true,
            'message' => "Supervisor {$supervisor->code} set to {$status}.",
            'data' => new AdminSupervisorResource($supervisor),
        ]);
    }

    /**
     * Soft delete a supervisor record.
     */
    public function destroy(int $id): JsonResponse
    {
        $supervisor = FactorySupervisor::withCount('productionBatches')->findOrFail($id);

        if ($supervisor->production_batches_count > 0) {
            return response()->json([
                'success' => false,
                'message' => "Cannot delete supervisor [{$supervisor->name}] — they are linked to {$supervisor->production_batches_count} production batch(es).",
                'linked_batches_count' => $supervisor->production_batches_count,
            ], 422);
        }

        $name = $supervisor->name;
        $supervisor->delete();

        return response()->json([
            'success' => true,
            'message' => "Supervisor \"{$name}\" deleted successfully.",
        ]);
    }
}
