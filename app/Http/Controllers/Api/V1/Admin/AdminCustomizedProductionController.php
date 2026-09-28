<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\CustomizedProductionOrder;
use App\Models\RawMaterial;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminCustomizedProductionController extends Controller
{
    /**
     * List customized production orders with search, status filtering, and pagination.
     */
    public function index(Request $request): JsonResponse
    {
        $query = CustomizedProductionOrder::with(['rawMaterial', 'productionJob', 'user']);

        // Search filter
        if ($request->filled('search')) {
            $search = trim($request->query('search'));
            $query->where(function ($q) use ($search) {
                $q->where('custom_order_id', 'like', "%{$search}%")
                  ->orWhere('item_description', 'like', "%{$search}%")
                  ->orWhere('fabric_name', 'like', "%{$search}%")
                  ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        // Status filter
        if ($request->filled('status') && $request->query('status') !== 'all') {
            $query->where('status', $request->query('status'));
        }

        // Raw material filter
        if ($request->filled('raw_material_id')) {
            $query->where('raw_material_id', (int) $request->query('raw_material_id'));
        }

        $query->orderBy('id', 'desc');

        $perPage = max(1, min(100, (int) $request->query('per_page', 15)));
        $paginator = $query->paginate($perPage);

        $summary = [
            'total_count' => CustomizedProductionOrder::count(),
            'pending_count' => CustomizedProductionOrder::where('status', 'pending')->count(),
            'in_production_count' => CustomizedProductionOrder::where('status', 'in_production')->count(),
            'completed_count' => CustomizedProductionOrder::where('status', 'completed')->count(),
            'cancelled_count' => CustomizedProductionOrder::where('status', 'cancelled')->count(),
        ];

        return response()->json([
            'success' => true,
            'message' => 'Customized production orders retrieved successfully.',
            'summary' => $summary,
            'data' => $paginator->getCollection()->map(fn ($order) => [
                'id' => $order->id,
                'custom_order_id' => $order->custom_order_id,
                'item_description' => $order->item_description,
                'raw_material_id' => $order->raw_material_id,
                'raw_material_name' => $order->rawMaterial?->name ?? $order->fabric_name,
                'fabric_name' => $order->fabric_name,
                'width' => (float) $order->width,
                'length' => (float) $order->length,
                'length_unit' => $order->length_unit ?: 'Inch',
                'dimensions_formatted' => $order->dimensions_formatted,
                'target_quantity' => (int) $order->target_quantity,
                'status' => $order->status,
                'notes' => $order->notes,
                'production_job_id' => $order->production_job_id,
                'production_job_code' => $order->productionJob?->job_code,
                'created_by' => $order->user?->name ?? 'System',
                'created_at' => $order->created_at ? $order->created_at->toIso8601String() : null,
                'updated_at' => $order->updated_at ? $order->updated_at->toIso8601String() : null,
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
     * Get metadata options for customized production order forms.
     */
    public function options(): JsonResponse
    {
        $rawMaterials = RawMaterial::active()
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'unit']);

        $statuses = [
            ['value' => 'pending', 'label' => 'Pending'],
            ['value' => 'in_production', 'label' => 'In Production'],
            ['value' => 'completed', 'label' => 'Completed'],
            ['value' => 'cancelled', 'label' => 'Cancelled'],
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'raw_materials' => $rawMaterials,
                'statuses' => $statuses,
                'units' => ['Inch', 'Meter', 'Yard', 'Centimeter', 'Foot'],
            ],
        ]);
    }

    /**
     * Display a single customized production order detail.
     */
    public function show(int $id): JsonResponse
    {
        $order = CustomizedProductionOrder::with(['rawMaterial', 'productionJob.task', 'user'])->find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Customized production order not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $order->id,
                'custom_order_id' => $order->custom_order_id,
                'item_description' => $order->item_description,
                'raw_material_id' => $order->raw_material_id,
                'raw_material_name' => $order->rawMaterial?->name ?? $order->fabric_name,
                'fabric_name' => $order->fabric_name,
                'width' => (float) $order->width,
                'length' => (float) $order->length,
                'length_unit' => $order->length_unit ?: 'Inch',
                'dimensions_formatted' => $order->dimensions_formatted,
                'target_quantity' => (int) $order->target_quantity,
                'status' => $order->status,
                'notes' => $order->notes,
                'production_job_id' => $order->production_job_id,
                'production_job_code' => $order->productionJob?->job_code,
                'configured_tasks_count' => $order->configured_tasks_count,
                'created_by' => $order->user?->name ?? 'System',
                'created_at' => $order->created_at ? $order->created_at->toIso8601String() : null,
                'updated_at' => $order->updated_at ? $order->updated_at->toIso8601String() : null,
            ],
        ]);
    }

    /**
     * Create a new customized production order.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'item_description' => 'required|string|max:255',
            'raw_material_id' => 'nullable|integer|exists:raw_materials,id',
            'fabric_name' => 'nullable|string|max:255',
            'width' => 'required|numeric|min:0.01',
            'length' => 'required|numeric|min:0.01',
            'length_unit' => 'nullable|string|max:50',
            'target_quantity' => 'required|integer|min:1',
            'notes' => 'nullable|string',
            'status' => 'nullable|string|in:pending,in_production,completed,cancelled',
        ]);

        $rawMaterial = !empty($validated['raw_material_id']) ? RawMaterial::find($validated['raw_material_id']) : null;

        $order = CustomizedProductionOrder::create([
            'item_description' => $validated['item_description'],
            'raw_material_id' => $validated['raw_material_id'] ?? null,
            'fabric_name' => $validated['fabric_name'] ?? ($rawMaterial?->name ?? 'Custom Fabric'),
            'width' => $validated['width'],
            'length' => $validated['length'],
            'length_unit' => $validated['length_unit'] ?? 'Inch',
            'target_quantity' => $validated['target_quantity'],
            'status' => $validated['status'] ?? 'pending',
            'notes' => $validated['notes'] ?? null,
            'created_by' => Auth::id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => "Customized production order {$order->custom_order_id} created successfully.",
            'data' => [
                'id' => $order->id,
                'custom_order_id' => $order->custom_order_id,
                'item_description' => $order->item_description,
                'dimensions_formatted' => $order->dimensions_formatted,
                'target_quantity' => (int) $order->target_quantity,
                'status' => $order->status,
            ],
        ], 201);
    }

    /**
     * Update order status.
     */
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $order = CustomizedProductionOrder::find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Customized production order not found.',
            ], 404);
        }

        $validated = $request->validate([
            'status' => 'required|string|in:pending,in_production,completed,cancelled',
        ]);

        $order->update(['status' => $validated['status']]);

        return response()->json([
            'success' => true,
            'message' => "Order {$order->custom_order_id} status updated to '{$order->status}'.",
            'data' => [
                'id' => $order->id,
                'custom_order_id' => $order->custom_order_id,
                'status' => $order->status,
            ],
        ]);
    }
}
