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

    /**
     * Get detailed Worker Profile, Earnings, and Performance Analytics (Replicates LaborDetail.php web page).
     */
    public function detailStats(Request $request, int $id): JsonResponse
    {
        $labor = Labor::with('tasks')->findOrFail($id);

        $query = \App\Models\JobLaborAllocation::where('labor_id', $id)
            ->with(['task', 'productionJob', 'inventoryBaleRoll.bale', 'manufacturingProduct']);

        // Handle Date Preset
        $preset = $request->query('preset');
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');

        if ($preset === 'this_month') {
            $dateFrom = \Carbon\Carbon::now()->startOfMonth()->format('Y-m-d');
            $dateTo = \Carbon\Carbon::now()->endOfDay()->format('Y-m-d');
        } elseif ($preset === 'last_30') {
            $dateFrom = \Carbon\Carbon::now()->subDays(30)->format('Y-m-d');
            $dateTo = \Carbon\Carbon::now()->endOfDay()->format('Y-m-d');
        } elseif ($preset === 'this_year') {
            $dateFrom = \Carbon\Carbon::now()->startOfYear()->format('Y-m-d');
            $dateTo = \Carbon\Carbon::now()->endOfDay()->format('Y-m-d');
        }

        if ($dateFrom) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        // Filters
        if ($request->filled('batch_filter')) {
            $batchF = trim($request->query('batch_filter'));
            $query->where(function ($q) use ($batchF) {
                $q->where('production_batch_id', 'like', "%{$batchF}%")
                  ->orWhere('job_id', 'like', "%{$batchF}%");
            });
        }

        if ($request->filled('task_filter')) {
            $query->where('task_id', (int) $request->query('task_filter'));
        }

        if ($request->filled('search')) {
            $search = trim($request->query('search'));
            $query->where(function ($q) use ($search) {
                $q->where('job_id', 'like', "%{$search}%")
                  ->orWhere('production_batch_id', 'like', "%{$search}%")
                  ->orWhereHas('manufacturingProduct', fn($pq) => $pq->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%"));
            });
        }

        // Clone query to compute totals across filtered dataset
        $allAllocations = (clone $query)->get();

        $totalPieces = (int) $allAllocations->sum('quantity_processed');
        $totalDirectWages = (float) $allAllocations->sum('calculated_wage');

        $totalJobCostValue = 0.0;
        foreach ($allAllocations as $alloc) {
            $rate = (float) ($alloc->piece_rate ?? $alloc->rate_per_piece ?? 0);
            if ($rate <= 0 && $alloc->manufacturingProduct) {
                $rate = (float) $alloc->manufacturingProduct->getStandardLaborRateForTask($alloc->task_id);
            }
            $totalJobCostValue += round((float)$alloc->quantity_processed * $rate, 2);
        }

        $uniqueBatches = $allAllocations->pluck('production_batch_id')->filter()->unique();
        $uniqueJobs = $allAllocations->pluck('job_id')->filter()->unique();

        // Batch Breakdown Array
        $batchBreakdown = [];
        $groupedByBatch = $allAllocations->groupBy('production_batch_id');
        foreach ($groupedByBatch as $batchCode => $items) {
            $bPieces = $items->sum('quantity_processed');
            $bWages = $items->sum('calculated_wage');
            $bValuation = 0.0;
            foreach ($items as $it) {
                $r = (float) ($it->piece_rate ?? $it->rate_per_piece ?? 0);
                if ($r <= 0 && $it->manufacturingProduct) {
                    $r = (float) $it->manufacturingProduct->getStandardLaborRateForTask($it->task_id);
                }
                $bValuation += round((float)$it->quantity_processed * $r, 2);
            }

            $batchBreakdown[] = [
                'batch_code' => $batchCode ?: 'General Batch',
                'total_pieces' => (int) $bPieces,
                'total_wages' => (float) $bWages,
                'total_valuation' => (float) $bValuation,
                'jobs_count' => $items->pluck('job_id')->unique()->count(),
            ];
        }

        $perPage = (int) $request->query('per_page', 15);
        $paginator = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'worker' => new AdminLaborResource($labor),
            'performance_metrics' => [
                'total_pieces_processed' => $totalPieces,
                'total_direct_wages' => $totalDirectWages,
                'total_job_cost_valuation' => $totalJobCostValue,
                'total_batches_count' => $uniqueBatches->count(),
                'total_jobs_count' => $uniqueJobs->count(),
                'batch_breakdown' => array_values($batchBreakdown),
            ],
            'data' => $paginator->getCollection()->map(fn($alloc) => [
                'id' => $alloc->id,
                'job_code' => $alloc->job_id,
                'production_batch_id' => $alloc->production_batch_id,
                'task_name' => $alloc->task ? $alloc->task->name : 'N/A',
                'manufacturing_product_title' => $alloc->manufacturingProduct ? $alloc->manufacturingProduct->title : 'N/A',
                'quantity_processed' => (int) $alloc->quantity_processed,
                'piece_rate' => (float) ($alloc->piece_rate ?? $alloc->rate_per_piece ?? 0),
                'calculated_wage' => (float) $alloc->calculated_wage,
                'created_at' => $alloc->created_at ? $alloc->created_at->toIso8601String() : null,
            ]),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    /**
     * Get aggregate Labor Payroll & Wage summary.
     */
    public function payrollSummary(Request $request): JsonResponse
    {
        $totalMonthlySalary = (float) Labor::where('status', true)->where('payment_method', 'monthly_salary')->sum('monthly_salary');
        $totalPieceRateWages = (float) \App\Models\JobLaborAllocation::sum('calculated_wage');

        return response()->json([
            'success' => true,
            'data' => [
                'total_laborers' => Labor::count(),
                'active_laborers' => Labor::where('status', true)->count(),
                'inactive_laborers' => Labor::where('status', false)->count(),
                'monthly_salary_obligations' => $totalMonthlySalary,
                'piece_rate_wages_earned' => $totalPieceRateWages,
                'formatted_monthly_salary' => '₹' . number_format($totalMonthlySalary, 2),
                'formatted_piece_rate_wages' => '₹' . number_format($totalPieceRateWages, 2),
            ],
        ]);
    }
}
