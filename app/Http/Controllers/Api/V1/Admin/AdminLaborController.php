<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Labor;
use App\Models\Task;
use App\Http\Requests\Api\V1\Admin\StoreLaborRequest;
use App\Http\Requests\Api\V1\Admin\UpdateLaborRequest;
use App\Http\Resources\Api\V1\AdminLaborResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class AdminLaborController extends Controller
{
    /**
     * List all labor records with searching, filtering, and pagination.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Labor::with('tasks')->withCount('allocations');

        // Search by name, code, or mobile number
        if ($request->filled('search')) {
            $search = trim($request->query('search'));
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('mobile_number', 'like', "%{$search}%");
            });
        }

        // Filter by payment method
        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->query('payment_method'));
        }

        // Filter by status
        if ($request->has('status') && $request->query('status') !== '') {
            $statusVal = $request->query('status');
            if ($statusVal === 'active' || $statusVal === '1' || $statusVal === 'true' || $statusVal === 1) {
                $query->where('status', true);
            } elseif ($statusVal === 'inactive' || $statusVal === '0' || $statusVal === 'false' || $statusVal === 0) {
                $query->where('status', false);
            }
        }

        // Filter by authorized task ID
        if ($request->filled('task_id')) {
            $taskId = (int) $request->query('task_id');
            $query->whereHas('tasks', function ($q) use ($taskId) {
                $q->where('tasks.id', $taskId);
            });
        }

        $query->orderBy('id', 'desc');

        $summaryStats = [
            'total_count' => Labor::count(),
            'active_count' => Labor::where('status', true)->count(),
            'inactive_count' => Labor::where('status', false)->count(),
            'monthly_salary_count' => Labor::where('payment_method', 'monthly_salary')->count(),
            'job_work_count' => Labor::where('payment_method', 'job_work')->count(),
        ];

        if ($request->query('paginate') === 'true' || $request->has('page')) {
            $perPage = (int) $request->query('per_page', 15);
            $paginator = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'summary' => $summaryStats,
                'data' => AdminLaborResource::collection($paginator->getCollection()),
                'pagination' => [
                    'total' => $paginator->total(),
                    'count' => $paginator->count(),
                    'per_page' => $paginator->perPage(),
                    'current_page' => $paginator->currentPage(),
                    'total_pages' => $paginator->lastPage(),
                ],
            ]);
        }

        $labors = $query->get();

        return response()->json([
            'success' => true,
            'summary' => $summaryStats,
            'data' => AdminLaborResource::collection($labors),
        ]);
    }

    /**
     * Get lightweight metadata options for mobile forms and pickers.
     */
    public function options(): JsonResponse
    {
        $allTasks = Task::where('status', true)
            ->orderBy('sequence_number')
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'is_labor_required', 'cost_type', 'default_piece_rate']);

        $paymentMethods = [
            ['value' => 'monthly_salary', 'label' => 'Monthly Salary'],
            ['value' => 'job_work', 'label' => 'Job Work (Piece Rate)'],
        ];

        $summaryStats = [
            'total_labors' => Labor::count(),
            'active_labors' => Labor::where('status', true)->count(),
            'inactive_labors' => Labor::where('status', false)->count(),
            'monthly_salary_labors' => Labor::where('payment_method', 'monthly_salary')->count(),
            'job_work_labors' => Labor::where('payment_method', 'job_work')->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'tasks' => $allTasks,
                'payment_methods' => $paymentMethods,
                'stats' => $summaryStats,
            ],
        ]);
    }

    /**
     * Display a single labor record with detailed stats.
     */
    public function show(int $id): JsonResponse
    {
        $labor = Labor::with('tasks')->withCount('allocations')->findOrFail($id);

        $totalWagesEarned = (float) $labor->allocations()->sum('calculated_wage');
        $totalJobsAllocated = $labor->allocations()->distinct('production_job_id')->count('production_job_id');

        return response()->json([
            'success' => true,
            'data' => new AdminLaborResource($labor),
            'performance_summary' => [
                'total_allocations_count' => $labor->allocations_count,
                'total_jobs_worked' => $totalJobsAllocated,
                'total_piece_rate_wages_earned' => $totalWagesEarned,
                'formatted_total_piece_rate_wages' => '₹' . number_format($totalWagesEarned, 2),
            ],
        ]);
    }

    /**
     * Store a newly created labor entry.
     */
    public function store(StoreLaborRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $labor = DB::transaction(function () use ($validated) {
            $data = [
                'name' => $validated['name'],
                'mobile_number' => $validated['mobile_number'] ?? null,
                'status' => $validated['status'] ?? true,
                'payment_method' => $validated['payment_method'],
                'monthly_salary' => $validated['payment_method'] === 'monthly_salary' ? ($validated['monthly_salary'] ?? null) : null,
            ];

            if (!empty($validated['code'])) {
                $data['code'] = $validated['code'];
            }

            $labor = Labor::create($data);

            if (!empty($validated['authorized_tasks'])) {
                $labor->tasks()->sync($validated['authorized_tasks']);
            }

            return $labor;
        });

        $labor->load('tasks')->loadCount('allocations');

        return response()->json([
            'success' => true,
            'message' => "Labor record \"{$labor->name}\" ({$labor->code}) created successfully.",
            'data' => new AdminLaborResource($labor),
        ], 201);
    }

    /**
     * Update an existing labor entry.
     */
    public function update(UpdateLaborRequest $request, int $id): JsonResponse
    {
        $labor = Labor::with('tasks')->withCount('allocations')->findOrFail($id);
        $validated = $request->validated();

        DB::transaction(function () use ($labor, $validated) {
            $data = [
                'name' => $validated['name'],
                'mobile_number' => $validated['mobile_number'] ?? null,
                'status' => $validated['status'] ?? $labor->status,
                'payment_method' => $validated['payment_method'],
                'monthly_salary' => $validated['payment_method'] === 'monthly_salary' ? ($validated['monthly_salary'] ?? null) : null,
            ];

            if (!empty($validated['code'])) {
                $data['code'] = $validated['code'];
            }

            $labor->update($data);

            if (isset($validated['authorized_tasks'])) {
                $labor->tasks()->sync($validated['authorized_tasks']);
            }
        });

        $labor->refresh()->load('tasks')->loadCount('allocations');

        return response()->json([
            'success' => true,
            'message' => "Labor record \"{$labor->name}\" ({$labor->code}) updated successfully.",
            'data' => new AdminLaborResource($labor),
        ]);
    }

    /**
     * Toggle active/inactive status of a labor entry.
     */
    public function toggleStatus(int $id): JsonResponse
    {
        $labor = Labor::with('tasks')->withCount('allocations')->findOrFail($id);
        $labor->update(['status' => !$labor->status]);

        $label = $labor->status ? 'activated' : 'deactivated';

        return response()->json([
            'success' => true,
            'message' => "Labor record \"{$labor->name}\" {$label} successfully.",
            'data' => new AdminLaborResource($labor),
        ]);
    }

    /**
     * Delete a labor entry safely.
     */
    public function destroy(int $id): JsonResponse
    {
        $labor = Labor::withCount('allocations')->findOrFail($id);

        if ($labor->allocations_count > 0) {
            return response()->json([
                'success' => false,
                'message' => "Cannot delete labor \"{$labor->name}\" — it has {$labor->allocations_count} active job allocation log(s). Deactivate the labor record instead.",
                'linked_allocations_count' => $labor->allocations_count,
            ], 422);
        }

        $name = $labor->name;
        $code = $labor->code;
        $labor->tasks()->detach();
        $labor->delete();

        return response()->json([
            'success' => true,
            'message' => "Labor record \"{$name}\" ({$code}) deleted successfully.",
        ]);
    }
}
