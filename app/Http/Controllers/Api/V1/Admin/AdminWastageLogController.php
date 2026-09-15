<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\StoreWastageLogRequest;
use App\Http\Requests\Api\V1\Admin\UpdateWastageLogRequest;
use App\Http\Resources\Api\V1\AdminWastageLogResource;
use App\Models\JobWastage;
use App\Models\ProductionJob;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminWastageLogController extends Controller
{
    /**
     * Compute and return KPI summary stats for the wastage log dashboard.
     * Mirrors the KPI cards on the /factory/wastage-log web page.
     */
    public function stats(): JsonResponse
    {
        $validWastages = JobWastage::where('quantity_wasted', '>', 0);

        $totalWastageQty    = (float) (clone $validWastages)->sum('quantity_wasted');
        $lossIncidentsCount = (clone $validWastages)->count();

        $impactedJobIds = (clone $validWastages)
            ->whereNotNull('production_job_id')
            ->pluck('production_job_id')
            ->unique();

        $impactedBatchCount = ProductionJob::whereIn('id', $impactedJobIds)
            ->whereNotNull('production_batch_db_id')
            ->pluck('production_batch_db_id')
            ->unique()
            ->count();

        if ($impactedBatchCount === 0) {
            $impactedBatchCount = $impactedJobIds->count();
        }

        $totalTargetQty = ProductionJob::whereIn('id', $impactedJobIds)->sum('target_quantity');
        $avgLossRate    = $totalTargetQty > 0
            ? round(($totalWastageQty / $totalTargetQty) * 100, 1)
            : 0.0;

        return response()->json([
            'success' => true,
            'data'    => [
                'total_wastage_qty'    => $totalWastageQty,
                'loss_incidents_count' => $lossIncidentsCount,
                'impacted_batch_count' => $impactedBatchCount,
                'avg_loss_rate'        => $avgLossRate,
                'avg_loss_rate_label'  => number_format($avgLossRate, 1) . '%',
            ],
        ]);
    }

    /**
     * Return lightweight options/picker data for wastage log forms.
     * Provides tasks list, wastage type list, and recent production jobs.
     */
    public function options(): JsonResponse
    {
        $tasks = Task::where('status', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        $wastageTypes = [
            ['value' => 'scrap',   'label' => 'Scrap (Unusable)'],
            ['value' => 'damaged', 'label' => 'Damaged (Resold)'],
        ];

        $recentJobs = ProductionJob::with('manufacturingProduct')
            ->orderBy('id', 'desc')
            ->take(50)
            ->get(['id', 'job_code', 'production_batch_id', 'manufacturing_product_id', 'status']);

        return response()->json([
            'success' => true,
            'data'    => [
                'tasks'        => $tasks,
                'wastage_types'=> $wastageTypes,
                'recent_jobs'  => $recentJobs->map(fn ($j) => [
                    'id'                       => $j->id,
                    'job_code'                 => $j->job_code,
                    'production_batch_id'      => $j->production_batch_id,
                    'manufacturing_product_id' => $j->manufacturing_product_id,
                    'manufacturing_product'    => $j->manufacturingProduct?->name,
                    'status'                   => $j->status,
                ]),
            ],
        ]);
    }

    /**
     * Return a paginated, filterable list of wastage log entries.
     * Supports search, task filter, wastage type filter, and date range.
     */
    public function index(Request $request): JsonResponse
    {
        $query = JobWastage::with([
            'productionJob.batch',
            'productionJob.pattern',
            'manufacturingProduct.patterns',
            'pattern',
            'task',
            'inventoryBaleRoll',
        ])->where('quantity_wasted', '>', 0);

        // Full-text search across all relevant fields (mirrors web logic)
        if ($request->filled('search')) {
            $term = '%' . trim($request->query('search')) . '%';
            $query->where(function ($q) use ($term) {
                $q->where('job_code', 'like', $term)
                  ->orWhere('reason', 'like', $term)
                  ->orWhere('wastage_type', 'like', $term)
                  ->orWhereHas('manufacturingProduct', fn ($m) => $m
                      ->where('name', 'like', $term)
                      ->orWhere('code', 'like', $term))
                  ->orWhereHas('pattern', fn ($p) => $p->where('name', 'like', $term))
                  ->orWhereHas('productionJob', fn ($j) => $j
                      ->where('job_code', 'like', $term)
                      ->orWhere('production_batch_id', 'like', $term))
                  ->orWhereHas('productionJob.pattern', fn ($jp) => $jp->where('name', 'like', $term))
                  ->orWhereHas('productionJob.batch', fn ($b) => $b->where('batch_code', 'like', $term))
                  ->orWhereHas('task', fn ($t) => $t->where('name', 'like', $term));
            });
        }

        // Task / production stage filter
        if ($request->filled('task_id')) {
            $query->where('task_id', (int) $request->query('task_id'));
        }

        // Wastage type filter — normalise damage/damaged as web does
        if ($request->filled('wastage_type')) {
            $type = strtolower($request->query('wastage_type'));
            if (in_array($type, ['damage', 'damaged'])) {
                $query->whereIn('wastage_type', ['damage', 'damaged']);
            } else {
                $query->where('wastage_type', $type);
            }
        }

        // Date range filter
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->query('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->query('date_to'));
        }

        // Production job filter
        if ($request->filled('production_job_id')) {
            $query->where('production_job_id', (int) $request->query('production_job_id'));
        }

        $query->orderBy('created_at', 'desc');

        $perPage    = max(1, min(100, (int) $request->query('per_page', 15)));
        $paginator  = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Wastage log entries retrieved successfully.',
            'data'    => AdminWastageLogResource::collection($paginator->getCollection()),
            'meta'    => [
                'current_page' => $paginator->currentPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
                'last_page'    => $paginator->lastPage(),
            ],
            'pagination' => [
                'total'       => $paginator->total(),
                'count'       => $paginator->count(),
                'per_page'    => $paginator->perPage(),
                'current_page'=> $paginator->currentPage(),
                'total_pages' => $paginator->lastPage(),
            ],
        ]);
    }

    /**
     * Return the full detail view for a single wastage log entry.
     */
    public function show(int $id): JsonResponse
    {
        $wastage = JobWastage::with([
            'productionJob.batch',
            'productionJob.pattern',
            'manufacturingProduct.patterns',
            'pattern',
            'task',
            'inventoryBaleRoll',
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data'    => new AdminWastageLogResource($wastage),
        ]);
    }

    /**
     * Create a new wastage log entry.
     */
    public function store(StoreWastageLogRequest $request): JsonResponse
    {
        $validated = $request->validated();

        // Auto-resolve job_code from production_job_id if not provided
        if (empty($validated['job_code']) && !empty($validated['production_job_id'])) {
            $job = ProductionJob::find($validated['production_job_id']);
            if ($job) {
                $validated['job_code'] = $job->job_code;
            }
        }

        // If still no job_code, generate a default WST-DIRECT placeholder
        if (empty($validated['job_code'])) {
            $validated['job_code'] = 'WST-DIRECT-' . now()->format('YmdHis');
        }
        $wastage = JobWastage::create($validated);
        $wastage->load([
            'productionJob.batch',
            'productionJob.pattern',
            'manufacturingProduct.patterns',
            'pattern',
            'task',
            'inventoryBaleRoll',
        ]);

        return response()->json([
            'success' => true,
            'message' => "Wastage log entry WST-{$wastage->created_at->format('Y')}-" . str_pad($wastage->id, 4, '0', STR_PAD_LEFT) . " created successfully.",
            'data'    => new AdminWastageLogResource($wastage),
        ], 201);
    }

    /**
     * Update an existing wastage log entry.
     */
    public function update(UpdateWastageLogRequest $request, int $id): JsonResponse
    {
        $wastage   = JobWastage::findOrFail($id);
        $validated = $request->validated();

        $wastage->update($validated);
        $wastage->refresh()->load([
            'productionJob.batch',
            'productionJob.pattern',
            'manufacturingProduct.patterns',
            'pattern',
            'task',
            'inventoryBaleRoll',
        ]);

        $code = "WST-{$wastage->created_at->format('Y')}-" . str_pad($wastage->id, 4, '0', STR_PAD_LEFT);

        return response()->json([
            'success' => true,
            'message' => "Wastage log entry {$code} updated successfully.",
            'data'    => new AdminWastageLogResource($wastage),
        ]);
    }

    /**
     * Delete a wastage log entry.
     */
    public function destroy(int $id): JsonResponse
    {
        $wastage = JobWastage::findOrFail($id);
        $code    = "WST-{$wastage->created_at->format('Y')}-" . str_pad($wastage->id, 4, '0', STR_PAD_LEFT);

        $wastage->delete();

        return response()->json([
            'success' => true,
            'message' => "Wastage log entry {$code} deleted successfully.",
        ]);
    }
}
