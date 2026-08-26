<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Production\StoreProductionBatchRequest;
use App\Http\Requests\Api\V1\Production\ConvertFinishedGoodsRequest;
use App\Http\Resources\Api\V1\ProductionBatchResource;
use App\Http\Resources\Api\V1\ProductionJobResource;
use App\Http\Resources\Api\V1\ProductionLedgerResource;
use App\Models\ProductionBatch;
use App\Models\ProductionJob;
use App\Models\Product;
use App\Services\Manufacturing\ProductionWorkflowService;
use App\Services\Manufacturing\ProductionCostingService;
use App\Services\Manufacturing\FinishedGoodsConversionService;
use Illuminate\Http\Request;
use Exception;

class AdminProductionBatchController extends Controller
{
    protected ProductionWorkflowService $workflowService;
    protected ProductionCostingService $costingService;
    protected FinishedGoodsConversionService $conversionService;

    public function __construct(
        ProductionWorkflowService $workflowService,
        ProductionCostingService $costingService,
        FinishedGoodsConversionService $conversionService
    ) {
        $this->workflowService = $workflowService;
        $this->costingService = $costingService;
        $this->conversionService = $conversionService;
    }

    /**
     * List paginated production batches with filtering.
     */
    public function index(Request $request)
    {
        $query = ProductionBatch::with(['manufacturingProduct', 'supervisor', 'jobs']);

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('batch_code', 'like', "%{$search}%")
                  ->orWhereHas('manufacturingProduct', function ($sub) use ($search) {
                      $sub->where('title', 'like', "%{$search}%")
                          ->orWhere('product_code', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('status')) {
            $status = $request->status;
            if ($status !== 'all') {
                $query->where('status', $status);
            }
        }

        if ($request->filled('date_from')) {
            $query->whereDate('batch_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('batch_date', '<=', $request->date_to);
        }

        $perPage = max(1, min(100, (int)$request->input('per_page', 15)));
        $batches = $query->orderBy('id', 'desc')->paginate($perPage);

        // Summary KPI Metrics
        $totalBatches = ProductionBatch::count();
        $inProgressBatches = ProductionBatch::where('status', 'In Progress')->count();
        $completedBatches = ProductionBatch::where('status', 'Completed')->count();

        return response()->json([
            'success' => true,
            'message' => 'Production batches retrieved successfully.',
            'summary' => [
                'total_batches' => $totalBatches,
                'in_progress_batches' => $inProgressBatches,
                'completed_batches' => $completedBatches,
            ],
            'data' => ProductionBatchResource::collection($batches),
            'meta' => [
                'current_page' => $batches->currentPage(),
                'per_page' => $batches->perPage(),
                'total' => $batches->total(),
                'last_page' => $batches->lastPage(),
            ],
        ]);
    }

    /**
     * Create a new production batch and initiate workflow task routing.
     */
    public function store(StoreProductionBatchRequest $request)
    {
        $validated = $request->validated();

        $response = $this->workflowService->initiateBatch(
            $validated['manufacturing_product_id'],
            $validated['supervisor_id'] ?? $request->user()->id,
            (int) $validated['planned_quantity'],
            'Normal',
            $validated['notes'] ?? null,
            $validated['batch_date'] ?? null
        );

        return $response;
    }

    /**
     * Get 360-degree batch jobs details.
     */
    public function batchJobs(string $batchCode)
    {
        $batch = ProductionBatch::where('batch_code', $batchCode)
            ->orWhere('id', $batchCode)
            ->with(['manufacturingProduct', 'supervisor'])
            ->first();

        if (!$batch) {
            return response()->json([
                'success' => false,
                'message' => 'Production batch not found.',
            ], 404);
        }

        $jobs = ProductionJob::where('production_batch_id', $batch->batch_code)
            ->orWhere('production_batch_db_id', $batch->id)
            ->with(['task', 'manufacturingProduct', 'stageExecutions.task'])
            ->orderBy('sequence_index', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Batch jobs retrieved successfully.',
            'batch' => new ProductionBatchResource($batch),
            'jobs' => ProductionJobResource::collection($jobs),
        ]);
    }

    /**
     * Get 360-degree Production Batch Ledger breakdown.
     */
    public function ledger(int|string $id)
    {
        $batch = ProductionBatch::where('id', $id)
            ->orWhere('batch_code', $id)
            ->first();

        if (!$batch) {
            return response()->json([
                'success' => false,
                'message' => 'Production batch not found.',
            ], 404);
        }

        try {
            $ledgerData = $this->costingService->getBatchCostSummary($batch->id);
            $ledgerData['batch'] = $batch;

            return response()->json([
                'success' => true,
                'message' => '360 Production Batch Ledger retrieved successfully.',
                'data' => new ProductionLedgerResource($ledgerData),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get storefront conversion modal options for a batch.
     */
    public function convertOptions(int|string $id)
    {
        $batch = ProductionBatch::where('id', $id)
            ->orWhere('batch_code', $id)
            ->first();

        if (!$batch) {
            return response()->json([
                'success' => false,
                'message' => 'Production batch not found.',
            ], 404);
        }

        $unconvertedJobs = ProductionJob::where('production_batch_id', $batch->batch_code)
            ->orWhere('production_batch_db_id', $batch->id)
            ->where('status', 'completed')
            ->where('remaining_unconverted_quantity', '>', 0)
            ->with(['manufacturingProduct'])
            ->get();

        $storefrontProducts = Product::where('is_active', true)
            ->with(['units', 'combinations'])
            ->orderBy('title', 'asc')
            ->get()
            ->map(function ($prod) {
                return [
                    'id' => $prod->id,
                    'title' => $prod->title,
                    'sku' => $prod->sku,
                    'units' => $prod->units->map(fn($u) => [
                        'id' => $u->id,
                        'name' => $u->name,
                        'level' => $u->level,
                        'conversion_to_base' => (float)$u->conversion_to_base,
                    ]),
                ];
            });

        return response()->json([
            'success' => true,
            'message' => 'Storefront conversion options retrieved successfully.',
            'batch' => new ProductionBatchResource($batch),
            'unconverted_jobs' => ProductionJobResource::collection($unconvertedJobs),
            'storefront_products' => $storefrontProducts,
        ]);
    }

    /**
     * Convert completed batch WIP products to storefront finished goods inventory.
     */
    public function convert(ConvertFinishedGoodsRequest $request, int|string $id)
    {
        $batch = ProductionBatch::where('id', $id)
            ->orWhere('batch_code', $id)
            ->first();

        if (!$batch) {
            return response()->json([
                'success' => false,
                'message' => 'Production batch not found.',
            ], 404);
        }

        $validated = $request->validated();

        try {
            $conversionResult = $this->conversionService->convertMultipleJobsToStorefrontProduct(
                $validated['target_product_id'],
                $validated['components'],
                $validated['packaging'] ?? [],
                (int) $validated['target_unit_level'],
                $validated['conversion_notes'] ?? ''
            );

            return response()->json([
                'success' => true,
                'message' => 'Finished goods conversion completed successfully!',
                'data' => $conversionResult,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
