<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Production\AssignJobLaborersRequest;
use App\Http\Requests\Api\V1\Production\RecordJobOutputRequest;
use App\Http\Requests\Api\V1\Production\RecordJobAlterationRequest;
use App\Http\Resources\Api\V1\ProductionJobResource;
use App\Http\Resources\Api\V1\ProductionJobDetailResource;
use App\Models\ProductionJob;
use App\Models\Task;
use App\Models\ManufacturingProduct;
use App\Models\Labor;
use App\Models\JobLaborAllocation;
use App\Models\JobProductionOutput;
use App\Models\JobMaterialConsumption;
use App\Models\JobAlteration;
use App\Models\InventoryBatch;
use App\Services\Manufacturing\LaborWageService;
use App\Services\Manufacturing\ProductionWorkflowService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Exception;

class AdminProductionJobController extends Controller
{
    protected LaborWageService $wageService;
    protected ProductionWorkflowService $workflowService;

    public function __construct(
        LaborWageService $wageService,
        ProductionWorkflowService $workflowService
    ) {
        $this->wageService = $wageService;
        $this->workflowService = $workflowService;
    }

    /**
     * List paginated production jobs with stage, status, and search filters.
     */
    public function index(Request $request)
    {
        $query = ProductionJob::with(['batch', 'task', 'manufacturingProduct', 'stageExecutions.task']);

        // Search filter
        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('job_code', 'like', "%{$search}%")
                  ->orWhere('production_batch_id', 'like', "%{$search}%")
                  ->orWhereHas('manufacturingProduct', function ($sub) use ($search) {
                      $sub->where('title', 'like', "%{$search}%")
                          ->orWhere('product_code', 'like', "%{$search}%");
                  })
                  ->orWhereHas('task', function ($sub) use ($search) {
                      $sub->where('name', 'like', "%{$search}%")
                          ->orWhere('code', 'like', "%{$search}%");
                  });
            });
        }

        // Status filter
        if ($request->filled('status')) {
            $status = $request->status;
            if ($status !== 'all') {
                $query->where('status', $status);
            }
        }

        // Stage/Task filter
        if ($request->filled('task_id')) {
            $taskId = (int)$request->task_id;
            $query->where(function ($q) use ($taskId) {
                $q->where('task_id', $taskId)
                  ->orWhereHas('stageExecutions', function ($sq) use ($taskId) {
                      $sq->where('task_id', $taskId);
                  });
            });
        }

        $perPage = max(1, min(100, (int)$request->input('per_page', 15)));
        $jobs = $query->orderBy('id', 'desc')->paginate($perPage);

        // KPI Summary Metrics
        $totalJobs = ProductionJob::count();
        $inProgressJobs = ProductionJob::where('status', 'in_progress')->count();
        $completedJobs = ProductionJob::where('status', 'completed')->count();
        $unconvertedQty = (int) ProductionJob::where('status', 'completed')->sum('remaining_unconverted_quantity');

        return response()->json([
            'success' => true,
            'message' => 'Production jobs retrieved successfully.',
            'summary' => [
                'total_jobs' => $totalJobs,
                'in_progress_jobs' => $inProgressJobs,
                'completed_jobs' => $completedJobs,
                'unconverted_pieces' => $unconvertedQty,
            ],
            'data' => ProductionJobResource::collection($jobs),
            'meta' => [
                'current_page' => $jobs->currentPage(),
                'per_page' => $jobs->perPage(),
                'total' => $jobs->total(),
                'last_page' => $jobs->lastPage(),
            ],
        ]);
    }

    /**
     * Get available UI filter options (Tasks, Manufacturing Products, Laborers).
     */
    public function options()
    {
        $tasks = Task::where('status', true)->orderBy('sequence_number', 'asc')->get()->map(fn($t) => [
            'id' => $t->id,
            'code' => $t->code,
            'name' => $t->name,
            'consumes_raw_material' => (bool)$t->consumes_raw_material,
            'default_rate_per_piece' => (float)$t->default_rate_per_piece,
        ]);

        $products = ManufacturingProduct::where('status', true)->orderBy('title', 'asc')->get()->map(fn($p) => [
            'id' => $p->id,
            'product_code' => $p->product_code,
            'title' => $p->title,
        ]);

        $laborers = Labor::where('status', true)->orWhere('is_active', true)->orderBy('name', 'asc')->get()->map(fn($l) => [
            'id' => $l->id,
            'labor_code' => $l->code ?? $l->labor_code,
            'name' => $l->name,
            'phone' => $l->mobile_number ?? $l->phone,
            'daily_rate' => (float)($l->daily_rate ?? 0),
            'piece_rate' => (float)($l->piece_rate ?? 0),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Production job options retrieved successfully.',
            'data' => [
                'tasks' => $tasks,
                'manufacturing_products' => $products,
                'laborers' => $laborers,
                'statuses' => [
                    ['value' => 'pending', 'label' => 'Pending'],
                    ['value' => 'in_progress', 'label' => 'In Progress'],
                    ['value' => 'completed', 'label' => 'Completed'],
                    ['value' => 'cancelled', 'label' => 'Cancelled'],
                ],
            ],
        ]);
    }

    /**
     * Get detailed view of a production job.
     */
    public function show(int $id)
    {
        $job = ProductionJob::with([
            'batch',
            'task',
            'manufacturingProduct',
            'laborAllocations.labor',
            'materialConsumptions.inventoryBatch.rawMaterial',
            'outputs.recordedBy',
            'alterations.targetManufacturingProduct',
            'stageExecutions.task',
        ])->find($id);

        if (!$job) {
            return response()->json([
                'success' => false,
                'message' => 'Production job not found.',
            ], 404);
        }

        // Available Fabric & Raw Material Inventory Batches for consumption picker
        $availableMaterialBatches = InventoryBatch::where('remaining_quantity', '>', 0)
            ->with(['rawMaterial.category'])
            ->orderBy('id', 'desc')
            ->get()
            ->map(fn($b) => [
                'id' => $b->id,
                'batch_number' => $b->batch_number,
                'raw_material_name' => $b->rawMaterial ? $b->rawMaterial->name : 'N/A',
                'category_code' => $b->rawMaterial && $b->rawMaterial->category ? $b->rawMaterial->category->code : null,
                'remaining_quantity' => (float)$b->remaining_quantity,
                'unit' => $b->unit,
                'purchase_rate' => (float)$b->purchase_rate,
            ]);

        return response()->json([
            'success' => true,
            'message' => 'Job detail retrieved successfully.',
            'job' => new ProductionJobDetailResource($job),
            'available_material_batches' => $availableMaterialBatches,
        ]);
    }

    /**
     * Assign laborers and piece rates to a job step.
     */
    public function assignLaborers(AssignJobLaborersRequest $request, int $id)
    {
        $job = ProductionJob::find($id);
        if (!$job) {
            return response()->json([
                'success' => false,
                'message' => 'Production job not found.',
            ], 404);
        }

        $validated = $request->validated();

        DB::transaction(function () use ($job, $validated) {
            $taskId = $job->task_id ?? $job->manufacturingProduct?->tasks?->first()?->id ?? Task::first()?->id;

            foreach ($validated['labor_allocations'] as $allocData) {
                JobLaborAllocation::updateOrCreate(
                    [
                        'job_id' => $job->job_code,
                        'labor_id' => $allocData['labor_id'],
                    ],
                    [
                        'production_job_id' => $job->id,
                        'task_id' => $taskId,
                        'manufacturing_product_id' => $job->manufacturing_product_id,
                        'rate_per_piece' => $allocData['rate_per_piece'] ?? 0,
                        'assigned_quantity' => $allocData['assigned_quantity'] ?? $job->target_quantity,
                        'quantity_processed' => 0,
                        'notes' => $validated['notes'] ?? null,
                    ]
                );
            }
        });

        $updatedJob = $job->fresh(['laborAllocations.labor']);

        return response()->json([
            'success' => true,
            'message' => 'Laborers assigned successfully.',
            'job' => new ProductionJobDetailResource($updatedJob),
        ]);
    }

    /**
     * Record production job output, material consumptions, and auto-advance workflow.
     */
    public function recordOutput(RecordJobOutputRequest $request, int $id)
    {
        $job = ProductionJob::find($id);
        if (!$job) {
            return response()->json([
                'success' => false,
                'message' => 'Production job not found.',
            ], 404);
        }

        $validated = $request->validated();

        try {
            DB::transaction(function () use ($job, $validated, $request) {
                $completedQty = (int)$validated['completed_quantity'];
                $rejectedQty = (int)($validated['rejected_quantity'] ?? 0);
                $damagedQty = (int)($validated['damaged_quantity'] ?? 0);

                // 1. Record Output Entry
                JobProductionOutput::create([
                    'production_job_id' => $job->id,
                    'manufacturing_product_id' => $job->manufacturing_product_id,
                    'task_id' => $job->task_id,
                    'completed_quantity' => $completedQty,
                    'rejected_quantity' => $rejectedQty,
                    'damaged_quantity' => $damagedQty,
                    'recorded_by_id' => $request->user()->id,
                    'notes' => $validated['notes'] ?? null,
                ]);

                // 2. Record Material Consumptions if provided
                if (!empty($validated['raw_material_consumptions'])) {
                    foreach ($validated['raw_material_consumptions'] as $mat) {
                        $invBatch = InventoryBatch::find($mat['inventory_batch_id']);
                        if ($invBatch) {
                            $consQty = (float)$mat['quantity_consumed'];
                            $wastageQty = (float)($mat['wastage_quantity'] ?? 0);
                            $totalCost = round($consQty * (float)$invBatch->purchase_rate, 2);

                            JobMaterialConsumption::create([
                                'job_code' => $job->job_code,
                                'production_job_id' => $job->id,
                                'inventory_batch_id' => $invBatch->id,
                                'task_id' => $job->task_id,
                                'quantity_consumed' => $consQty,
                                'wastage_quantity' => $wastageQty,
                                'unit' => $invBatch->unit,
                                'unit_cost' => $invBatch->purchase_rate,
                                'total_cost' => $totalCost,
                            ]);

                            // Deduct inventory batch
                            $invBatch->decrement('remaining_quantity', $consQty);
                        }
                    }
                }

                // 3. Update Job Quantities
                $newCompleted = $job->completed_quantity + $completedQty;
                $newRejected = $job->rejected_quantity + $rejectedQty;
                $newDamaged = $job->damaged_quantity + $damagedQty;

                $job->update([
                    'completed_quantity' => $newCompleted,
                    'rejected_quantity' => $newRejected,
                    'damaged_quantity' => $newDamaged,
                    'remaining_unconverted_quantity' => $newCompleted,
                ]);

                // 4. Calculate Labor Payouts
                $this->wageService->calculateAndSaveJobLaborEarnings($job);
            });

            // 5. Complete step execution & trigger workflow auto-advance
            $workflowResult = $this->workflowService->completeJob($job->id);

            return response()->json([
                'success' => true,
                'message' => 'Job output recorded and workflow advanced successfully!',
                'workflow' => $workflowResult->getData(true),
                'job' => new ProductionJobDetailResource($job->fresh(['outputs', 'laborAllocations', 'materialConsumptions'])),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Record product alteration (converting defective/excess piece to smaller product area).
     */
    public function recordAlteration(RecordJobAlterationRequest $request, int $id)
    {
        $job = ProductionJob::find($id);
        if (!$job) {
            return response()->json([
                'success' => false,
                'message' => 'Production job not found.',
            ], 404);
        }

        $validated = $request->validated();

        try {
            $alteration = DB::transaction(function () use ($job, $validated) {
                return JobAlteration::create([
                    'production_job_id' => $job->id,
                    'source_manufacturing_product_id' => $job->manufacturing_product_id,
                    'target_manufacturing_product_id' => $validated['target_manufacturing_product_id'],
                    'quantity' => (int)$validated['quantity'],
                    'reason' => $validated['reason'] ?? null,
                    'notes' => $validated['notes'] ?? null,
                ]);
            });

            return response()->json([
                'success' => true,
                'message' => 'Product alteration recorded successfully.',
                'alteration' => $alteration,
                'job' => new ProductionJobDetailResource($job->fresh(['alterations'])),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Supervisor Workbench summary dashboard.
     */
    public function workbench()
    {
        $activeJobs = ProductionJob::where('status', 'in_progress')
            ->with(['batch', 'task', 'manufacturingProduct'])
            ->orderBy('id', 'desc')
            ->get();

        $pendingLaborerAssignments = ProductionJob::where('status', 'in_progress')
            ->whereDoesntHave('laborAllocations')
            ->with(['task', 'manufacturingProduct'])
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Supervisor workbench retrieved successfully.',
            'active_jobs' => ProductionJobResource::collection($activeJobs),
            'pending_laborer_assignments' => ProductionJobResource::collection($pendingLaborerAssignments),
        ]);
    }

    /**
     * Get lookup options for tracking history filter pickers.
     */
    public function trackingHistoryOptions(): JsonResponse
    {
        $workers = Labor::orderBy('name')->get(['id', 'code', 'name', 'payment_method']);
        $jobs = ProductionJob::select(['id', 'job_code', 'production_batch_id'])->orderBy('id', 'desc')->get();
        $tasks = Task::where('status', true)->orderBy('name')->get(['id', 'code', 'name']);

        return response()->json([
            'success' => true,
            'data' => [
                'workers' => $workers,
                'jobs' => $jobs,
                'tasks' => $tasks,
                'payment_methods' => [
                    ['value' => 'monthly_salary', 'label' => 'Monthly Salary'],
                    ['value' => 'job_work', 'label' => 'Job Work (Piece Rate)'],
                ],
            ],
        ]);
    }

    /**
     * Production audit & labor tracking history (Replicates TrackingHistory.php web page).
     */
    public function trackingHistory(Request $request): JsonResponse
    {
        $query = JobLaborAllocation::with(['labor', 'task', 'manufacturingProduct', 'productionJob', 'inventoryBaleRoll.bale']);

        if ($request->filled('search')) {
            $search = trim($request->query('search'));
            $query->where(function ($q) use ($search) {
                $q->where('job_id', 'like', "%{$search}%")
                  ->orWhere('production_batch_id', 'like', "%{$search}%")
                  ->orWhereHas('labor', fn($l) => $l->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%"))
                  ->orWhereHas('task', fn($t) => $t->where('name', 'like', "%{$search}%"))
                  ->orWhereHas('manufacturingProduct', fn($p) => $p->where('title', 'like', "%{$search}%")->orWhere('product_code', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('payment_method')) {
            $pm = $request->query('payment_method');
            $query->whereHas('labor', fn($l) => $l->where('payment_method', $pm));
        }

        if ($request->filled('job_id')) {
            $jobId = $request->query('job_id');
            $query->where(function ($q) use ($jobId) {
                $q->where('job_id', $jobId)
                  ->orWhere('production_job_id', $jobId);
            });
        }

        if ($request->filled('worker_id') || $request->filled('labor_id')) {
            $laborId = (int) ($request->query('worker_id') ?? $request->query('labor_id'));
            $query->where('labor_id', $laborId);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->query('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->query('date_to'));
        }

        // Summary KPI Metrics
        $allRecords = (clone $query)->get();
        $totalAllocations = $allRecords->count();
        $totalPiecesProcessed = (int) $allRecords->sum('quantity_processed');
        $totalWagesPaid = (float) $allRecords->sum('calculated_wage');

        $perPage = max(1, min(100, (int) $request->query('per_page', 15)));
        $paginator = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Tracking history retrieved successfully.',
            'summary' => [
                'total_allocations' => $totalAllocations,
                'total_pieces_processed' => $totalPiecesProcessed,
                'total_wages_paid' => $totalWagesPaid,
                'formatted_total_wages_paid' => '₹' . number_format($totalWagesPaid, 2),
            ],
            'data' => $paginator->getCollection()->map(function ($alloc) {
                $labor = $alloc->labor;
                $task = $alloc->task;
                $product = $alloc->manufacturingProduct;
                $baleRoll = $alloc->inventoryBaleRoll;
                $bale = $baleRoll ? $baleRoll->bale : null;

                return [
                    'id' => $alloc->id,
                    'job_code' => $alloc->job_id,
                    'production_batch_id' => $alloc->production_batch_id,
                    'worker' => $labor ? [
                        'id' => $labor->id,
                        'name' => $labor->name,
                        'code' => $labor->code,
                        'payment_method' => $labor->payment_method,
                    ] : null,
                    'task' => $task ? [
                        'id' => $task->id,
                        'name' => $task->name,
                        'code' => $task->code,
                    ] : null,
                    'manufacturing_product' => $product ? [
                        'id' => $product->id,
                        'title' => $product->title ?? $product->name,
                        'product_code' => $product->product_code ?? $product->code,
                    ] : null,
                    'roll_info' => $baleRoll ? [
                        'roll_id' => $baleRoll->id,
                        'roll_number' => $baleRoll->roll_number,
                        'bale_number' => $bale ? $bale->bale_number : null,
                    ] : null,
                    'assigned_quantity' => (int) $alloc->assigned_quantity,
                    'quantity_processed' => (int) $alloc->quantity_processed,
                    'piece_rate' => (float) ($alloc->piece_rate ?? $alloc->rate_per_piece ?? 0),
                    'calculated_wage' => (float) $alloc->calculated_wage,
                    'notes' => $alloc->notes,
                    'created_at' => $alloc->created_at ? $alloc->created_at->toIso8601String() : null,
                ];
            }),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }
}
