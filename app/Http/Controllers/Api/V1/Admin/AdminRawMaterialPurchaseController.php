<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\StoreRawMaterialPurchaseRequest;
use App\Http\Resources\Api\V1\AdminInventoryBatchResource;
use App\Models\RawMaterial;
use App\Models\InventoryBatch;
use App\Services\InventoryBatchLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Exception;

class AdminRawMaterialPurchaseController extends Controller
{
    /**
     * Get lookup options for raw material purchase entry.
     */
    public function options(): JsonResponse
    {
        $materials = RawMaterial::active()
            ->with(['category', 'unitGroup', 'unitModel'])
            ->orderBy('name')
            ->get()
            ->map(function ($m) {
                return [
                    'id' => $m->id,
                    'code' => $m->code,
                    'name' => $m->name,
                    'unit' => $m->unit,
                    'unit_type' => $m->category && is_object($m->category->unit_type) ? $m->category->unit_type->value : ($m->category->unit_type ?? 'unit_based'),
                    'category_name' => $m->category ? $m->category->name : null,
                ];
            });

        $recentSuppliers = InventoryBatch::whereNotNull('supplier_name')
            ->distinct()
            ->pluck('supplier_name')
            ->take(20);

        return response()->json([
            'success' => true,
            'data' => [
                'raw_materials' => $materials,
                'recent_suppliers' => $recentSuppliers,
            ],
        ]);
    }

    /**
     * Record a new raw material purchase entry and create inventory batch with bales.
     */
    public function store(StoreRawMaterialPurchaseRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $material = RawMaterial::with(['category', 'unitGroup', 'unitModel'])->findOrFail($validated['raw_material_id']);

        $unitType = $material->category && is_object($material->category->unit_type) 
            ? $material->category->unit_type->value 
            : ($material->category->unit_type ?? 'unit_based');

        try {
            $batch = DB::transaction(function () use ($validated, $material, $unitType) {
                $qtyReceived = floatval($validated['quantity_received'] ?? 0);
                
                // Calculate quantity for length-based if bales provided
                if ($unitType === 'length_based') {
                    $numBales = max(1, (int)($validated['num_bales'] ?? 1));
                    if (!empty($validated['all_bales_equal_length'])) {
                        $lenPerBale = floatval($validated['declared_bale_length'] ?? 0);
                        $qtyReceived = $numBales * $lenPerBale;
                    } else if (!empty($validated['individual_bale_lengths'])) {
                        $qtyReceived = array_sum(array_map('floatval', $validated['individual_bale_lengths']));
                    }
                }

                if ($qtyReceived <= 0) {
                    throw new Exception("Quantity received must be greater than zero.");
                }

                // Base unit conversion if unit model attached
                $baseQty = $qtyReceived;
                if ($material->unitModel) {
                    $baseQty = $material->unitModel->toBaseQuantity($qtyReceived);
                }

                $numBales = $unitType === 'length_based' ? (int)($validated['num_bales'] ?? 1) : null;
                $declaredLengthArg = null;
                if ($unitType === 'length_based') {
                    if (!empty($validated['all_bales_equal_length'])) {
                        $declaredLengthArg = floatval($validated['declared_bale_length'] ?? 0);
                    } else if (!empty($validated['individual_bale_lengths'])) {
                        $declaredLengthArg = array_map('floatval', $validated['individual_bale_lengths']);
                    }
                }
                $avgDeclaredLength = is_array($declaredLengthArg) ? (array_sum($declaredLengthArg) / max(1, count($declaredLengthArg))) : $declaredLengthArg;

                // GST calculations
                $purchaseRate = floatval($validated['purchase_rate']);
                $totalBaseAmount = round($qtyReceived * $purchaseRate, 2);

                $gstIncluded = !isset($validated['gst_included']) || (bool)$validated['gst_included'];
                $gstPercent = floatval($validated['gst_percent'] ?? 18.0);
                $gstAmount = $gstIncluded ? 0.00 : round($totalBaseAmount * ($gstPercent / 100), 2);
                $grandTotal = round($totalBaseAmount + $gstAmount, 2);

                $effectiveRate = $qtyReceived > 0 ? round($grandTotal / $qtyReceived, 4) : $purchaseRate;

                $batch = InventoryBatch::create([
                    'raw_material_id' => $material->id,
                    'supplier_name' => $validated['supplier_name'],
                    'purchase_date' => $validated['purchase_date'],
                    'invoice_number' => $validated['invoice_number'],
                    'quantity_received' => $qtyReceived,
                    'balance_quantity' => $qtyReceived,
                    'base_quantity' => $baseQty,
                    'base_current_balance' => $baseQty,
                    'quantity_consumed' => 0.0000,
                    'purchase_rate' => $effectiveRate,
                    'total_amount' => $grandTotal,
                    'unit' => $material->unit,
                    'purchase_unit_id' => $material->unit_id,
                    'num_bales' => $numBales,
                    'declared_bale_length' => $avgDeclaredLength,
                    'status' => 'active',
                ]);

                // Create unopened bales if length-based
                if ($unitType === 'length_based' && $numBales > 0) {
                    $batch->createBales($numBales, $declaredLengthArg);
                }

                // Log entry creation
                InventoryBatchLogger::log($batch->id, 'created', $batch->quantity_received, null, "Purchase entry recorded via API ({$numBales} bales)");

                return $batch;
            });

            return response()->json([
                'success' => true,
                'message' => "Purchase entry recorded successfully! Batch {$batch->batch_number} created.",
                'data' => new AdminInventoryBatchResource($batch->fresh(['rawMaterial.category'])),
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
