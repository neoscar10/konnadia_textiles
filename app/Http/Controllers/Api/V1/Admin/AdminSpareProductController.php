<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\AdminSpareProductResource;
use App\Models\ManufacturingProduct;
use App\Models\SpareProduct;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminSpareProductController extends Controller
{
    /**
     * Return KPI statistics for spare products dashboard.
     */
    public function stats(): JsonResponse
    {
        $totalRecorded  = (int) SpareProduct::sum('quantity');
        $totalUsed      = (int) SpareProduct::sum('used_quantity');
        $totalAvailable = max(0, $totalRecorded - $totalUsed);
        $uniqueDesigns  = SpareProduct::distinct('design_id')->count('design_id');
        $availableDesignIds = SpareProduct::whereRaw('quantity > used_quantity')
            ->distinct('design_id')
            ->pluck('design_id')
            ->values();

        return response()->json([
            'success' => true,
            'data'    => [
                'total_spare_recorded'   => $totalRecorded,
                'total_spare_used'       => $totalUsed,
                'total_available_spare'  => $totalAvailable,
                'unique_design_ids_count' => $uniqueDesigns,
                'available_design_ids'   => $availableDesignIds,
            ],
        ]);
    }

    /**
     * Return picker lookup options for filter dropdowns.
     */
    public function options(): JsonResponse
    {
        $designIds = SpareProduct::select('design_id')
            ->distinct()
            ->whereNotNull('design_id')
            ->where('design_id', '!=', '')
            ->orderBy('design_id')
            ->pluck('design_id');

        $mfgProductIds = SpareProduct::distinct('manufacturing_product_id')->pluck('manufacturing_product_id');
        $manufacturingProducts = ManufacturingProduct::whereIn('id', $mfgProductIds)
            ->get(['id', 'name', 'code', 'status'])
            ->map(fn ($p) => [
                'id'   => $p->id,
                'name' => $p->name ?? $p->title,
                'code' => $p->code ?? $p->product_code,
            ]);

        return response()->json([
            'success' => true,
            'data'    => [
                'design_ids'             => $designIds,
                'manufacturing_products' => $manufacturingProducts,
                'status_options' => [
                    ['value' => 'all', 'label' => 'All Spare Stock'],
                    ['value' => 'available', 'label' => 'Available Stock Only'],
                    ['value' => 'used', 'label' => 'Fully Used Stock'],
                ],
            ],
        ]);
    }

    /**
     * Paginated list of spare products with filtering options.
     */
    public function index(Request $request): JsonResponse
    {
        $query = SpareProduct::with(['manufacturingProduct', 'productionBatch', 'productionJob']);

        // Search filter: design_id, manufacturing product name/code, batch_code
        if ($request->filled('search')) {
            $search = trim($request->query('search'));
            $query->where(function ($q) use ($search) {
                $q->where('design_id', 'like', "%{$search}%")
                  ->orWhereHas('manufacturingProduct', function ($mp) use ($search) {
                      $mp->where('name', 'like', "%{$search}%")
                         ->orWhere('title', 'like', "%{$search}%")
                         ->orWhere('code', 'like', "%{$search}%")
                         ->orWhere('product_code', 'like', "%{$search}%");
                  })
                  ->orWhereHas('productionBatch', function ($pb) use ($search) {
                      $pb->where('batch_code', 'like', "%{$search}%");
                  });
            });
        }

        // Filter by Design ID
        if ($request->filled('design_id')) {
            $query->where('design_id', trim($request->query('design_id')));
        }

        // Filter by Status: 'available' | 'used' | 'all'
        if ($request->filled('status')) {
            $status = $request->query('status');
            if ($status === 'available') {
                $query->whereRaw('quantity > used_quantity');
            } elseif ($status === 'used') {
                $query->whereRaw('quantity <= used_quantity');
            }
        }

        $perPage   = max(1, min(100, (int) $request->query('per_page', 15)));
        $paginated = $query->latest()->paginate($perPage);

        // KPI Summary stats (matching current scope)
        $totalRecorded  = (int) SpareProduct::sum('quantity');
        $totalUsed      = (int) SpareProduct::sum('used_quantity');
        $totalAvailable = max(0, $totalRecorded - $totalUsed);

        return response()->json([
            'success' => true,
            'message' => 'Spare products retrieved successfully.',
            'summary' => [
                'total_spare_recorded'  => $totalRecorded,
                'total_spare_used'      => $totalUsed,
                'total_available_spare' => $totalAvailable,
            ],
            'data' => AdminSpareProductResource::collection($paginated),
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'per_page'     => $paginated->perPage(),
                'total'        => $paginated->total(),
                'last_page'    => $paginated->lastPage(),
            ],
        ]);
    }

    /**
     * Single spare product record details.
     */
    public function show(int $id): JsonResponse
    {
        $spare = SpareProduct::with(['manufacturingProduct', 'productionBatch', 'productionJob'])
            ->find($id);

        if (!$spare) {
            return response()->json([
                'success' => false,
                'message' => 'Spare product record not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => new AdminSpareProductResource($spare),
        ]);
    }

    /**
     * Delete an un-used spare product record.
     */
    public function destroy(int $id): JsonResponse
    {
        $spare = SpareProduct::find($id);

        if (!$spare) {
            return response()->json([
                'success' => false,
                'message' => 'Spare product record not found.',
            ], 404);
        }

        if ($spare->used_quantity > 0) {
            return response()->json([
                'success' => false,
                'message' => "Cannot delete spare product record #{$id} — {$spare->used_quantity} unit(s) have already been consumed in finished goods conversions.",
            ], 422);
        }

        $spare->delete();

        return response()->json([
            'success' => true,
            'message' => "Spare product record #{$id} deleted successfully.",
        ]);
    }
}
