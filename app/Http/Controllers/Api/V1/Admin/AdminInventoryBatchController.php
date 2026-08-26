<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\OpenInventoryBaleRequest;
use App\Http\Requests\Api\V1\Admin\AdjustInventoryBatchQuantityRequest;
use App\Http\Resources\Api\V1\AdminInventoryBatchResource;
use App\Http\Resources\Api\V1\AdminInventoryBatchDetailResource;
use App\Models\InventoryBatch;
use App\Models\InventoryBale;
use App\Services\InventoryBatchLogger;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Exception;

class AdminInventoryBatchController extends Controller
{
    /**
     * List paginated inventory batches with searching, filtering, and summary KPIs.
     */
    public function index(Request $request): JsonResponse
    {
        $query = InventoryBatch::with(['rawMaterial.category']);

        if ($request->filled('search')) {
            $search = trim($request->query('search'));
            $query->where(function ($q) use ($search) {
                $q->where('batch_number', 'like', "%{$search}%")
                  ->orWhere('supplier_name', 'like', "%{$search}%")
                  ->orWhere('invoice_number', 'like', "%{$search}%");
            });
        }

        if ($request->filled('raw_material_id')) {
            $query->where('raw_material_id', (int) $request->query('raw_material_id'));
        }

        if ($request->filled('category_id')) {
            $catId = (int) $request->query('category_id');
            $query->whereHas('rawMaterial', function ($sub) use ($catId) {
                $sub->where('raw_material_category_id', $catId);
            });
        }

        if ($request->filled('status')) {
            $status = $request->query('status');
            if ($status !== 'all') {
                $query->where('status', $status);
            }
        }

        $perPage = max(1, min(100, (int) $request->query('per_page', 15)));
        $batches = $query->orderBy('id', 'desc')->paginate($perPage);

        // Summary KPI Metrics
        $totalBatches = InventoryBatch::count();
        $activeBatches = InventoryBatch::where('status', 'active')->count();
        $depletedBatches = InventoryBatch::where('status', 'depleted')->count();
        $totalInventoryValue = (float) InventoryBatch::where('status', 'active')->sum('total_amount');

        return response()->json([
            'success' => true,
            'message' => 'Inventory batches retrieved successfully.',
            'summary' => [
                'total_batches' => $totalBatches,
                'active_batches' => $activeBatches,
                'depleted_batches' => $depletedBatches,
                'total_inventory_value' => $totalInventoryValue,
            ],
            'data' => AdminInventoryBatchResource::collection($batches),
            'meta' => [
                'current_page' => $batches->currentPage(),
                'per_page' => $batches->perPage(),
                'total' => $batches->total(),
                'last_page' => $batches->lastPage(),
            ],
        ]);
    }

    /**
     * Show detailed view of an inventory batch.
     */
    public function show(int $id): JsonResponse
    {
        $batch = InventoryBatch::with([
            'rawMaterial.category',
            'rawMaterial.unitGroup',
            'rawMaterial.unitModel',
            'consumptions.job.manufacturingProduct',
            'logs.user',
            'bales.rolls',
        ])->find($id);

        if (!$batch) {
            return response()->json([
                'success' => false,
                'message' => 'Inventory batch not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Inventory batch detail retrieved successfully.',
            'data' => new AdminInventoryBatchDetailResource($batch),
        ]);
    }

    /**
     * Open an unopened fabric bale into measured rolls.
     */
    public function openBale(OpenInventoryBaleRequest $request, int $id, int $baleId): JsonResponse
    {
        $batch = InventoryBatch::find($id);
        if (!$batch) {
            return response()->json([
                'success' => false,
                'message' => 'Inventory batch not found.',
            ], 404);
        }

        $bale = InventoryBale::where('inventory_batch_id', $batch->id)->find($baleId);
        if (!$bale) {
            return response()->json([
                'success' => false,
                'message' => 'Bale not found on this batch.',
            ], 404);
        }

        if ($bale->is_opened) {
            return response()->json([
                'success' => false,
                'message' => "Bale {$bale->bale_number} has already been opened.",
            ], 422);
        }

        $validated = $request->validated();

        try {
            $result = DB::transaction(function () use ($bale, $validated) {
                return $bale->openBale($validated['bale_roll_lengths']);
            });

            $updatedBatch = $batch->fresh([
                'rawMaterial.category',
                'rawMaterial.unitGroup',
                'rawMaterial.unitModel',
                'consumptions.job.manufacturingProduct',
                'logs.user',
                'bales.rolls',
            ]);

            return response()->json([
                'success' => true,
                'message' => "Bale {$bale->bale_number} opened successfully with {$bale->roll_count} rolls! Measured length ({$result['total_recorded_length']}m) recorded.",
                'result' => $result,
                'data' => new AdminInventoryBatchDetailResource($updatedBatch),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Manually adjust (deduct or restore) stock quantity on an inventory batch.
     */
    public function adjustQuantity(AdjustInventoryBatchQuantityRequest $request, int $id): JsonResponse
    {
        $batch = InventoryBatch::find($id);
        if (!$batch) {
            return response()->json([
                'success' => false,
                'message' => 'Inventory batch not found.',
            ], 404);
        }

        $validated = $request->validated();
        $qty = floatval($validated['quantity']);
        $type = $validated['adjustment_type'];
        $reason = $validated['reason'];

        try {
            DB::transaction(function () use ($batch, $qty, $type, $reason) {
                if ($type === 'deduct') {
                    if ($qty > $batch->balance_quantity) {
                        throw new Exception("Cannot deduct {$qty} {$batch->unit}: Exceeds available balance quantity ({$batch->balance_quantity} {$batch->unit}).");
                    }
                    $newBalance = max(0, $batch->balance_quantity - $qty);
                    $newConsumed = $batch->quantity_consumed + $qty;
                    $status = $newBalance <= 0 ? 'depleted' : 'active';

                    $batch->update([
                        'balance_quantity' => $newBalance,
                        'base_current_balance' => $newBalance,
                        'quantity_consumed' => $newConsumed,
                        'status' => $status,
                    ]);

                    InventoryBatchLogger::log($batch->id, 'manual_deduction', $qty, null, "Manual deduction: {$reason}");
                } else {
                    $newBalance = $batch->balance_quantity + $qty;
                    $newConsumed = max(0, $batch->quantity_consumed - $qty);

                    $batch->update([
                        'balance_quantity' => $newBalance,
                        'base_current_balance' => $newBalance,
                        'quantity_consumed' => $newConsumed,
                        'status' => 'active',
                    ]);

                    InventoryBatchLogger::log($batch->id, 'manual_restoration', $qty, null, "Manual restoration: {$reason}");
                }
            });

            $updatedBatch = $batch->fresh([
                'rawMaterial.category',
                'rawMaterial.unitGroup',
                'rawMaterial.unitModel',
                'consumptions.job.manufacturingProduct',
                'logs.user',
                'bales.rolls',
            ]);

            return response()->json([
                'success' => true,
                'message' => "Batch {$batch->batch_number} stock {$type}ed successfully by {$qty} {$batch->unit}.",
                'data' => new AdminInventoryBatchDetailResource($updatedBatch),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
