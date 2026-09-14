<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\StoreSupplierRequest;
use App\Http\Requests\Api\V1\Admin\UpdateSupplierRequest;
use App\Http\Resources\Api\V1\AdminSupplierResource;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AdminSupplierController extends Controller
{
    /**
     * List all suppliers with searching and pagination.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Supplier::withCount('inventoryBatches');

        if ($request->filled('search')) {
            $search = trim($request->query('search'));
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('contact_person', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('gstin', 'like', "%{$search}%");
            });
        }

        $query->orderBy('name', 'asc');

        if ($request->query('paginate') === 'true' || $request->has('page')) {
            $perPage = max(1, min(100, (int) $request->query('per_page', 15)));
            $paginator = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'message' => 'Suppliers retrieved successfully.',
                'data' => AdminSupplierResource::collection($paginator->getCollection()),
                'meta' => [
                    'current_page' => $paginator->currentPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                    'last_page' => $paginator->lastPage(),
                ],
            ]);
        }

        $suppliers = $query->get();

        return response()->json([
            'success' => true,
            'message' => 'Suppliers retrieved successfully.',
            'data' => AdminSupplierResource::collection($suppliers),
        ]);
    }

    /**
     * Lookup options for supplier pickers.
     */
    public function options(): JsonResponse
    {
        $suppliers = Supplier::orderBy('name')->get(['id', 'name', 'contact_person', 'gstin']);

        return response()->json([
            'success' => true,
            'data' => [
                'suppliers' => $suppliers,
            ],
        ]);
    }

    /**
     * Show detailed view of a supplier including linked batches.
     */
    public function show(int $id): JsonResponse
    {
        $supplier = Supplier::withCount('inventoryBatches')
            ->with(['inventoryBatches' => function ($q) {
                $q->with('rawMaterial')->orderBy('id', 'desc')->take(10);
            }])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => new AdminSupplierResource($supplier),
        ]);
    }

    /**
     * Create a new supplier record.
     */
    public function store(StoreSupplierRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $supplier = Supplier::create($validated);
        $supplier->loadCount('inventoryBatches');

        return response()->json([
            'success' => true,
            'message' => "Supplier \"{$supplier->name}\" created successfully.",
            'data' => new AdminSupplierResource($supplier),
        ], 201);
    }

    /**
     * Update an existing supplier record.
     */
    public function update(UpdateSupplierRequest $request, int $id): JsonResponse
    {
        $supplier = Supplier::findOrFail($id);
        $validated = $request->validated();

        $supplier->update($validated);
        $supplier->loadCount('inventoryBatches');

        return response()->json([
            'success' => true,
            'message' => "Supplier \"{$supplier->name}\" updated successfully.",
            'data' => new AdminSupplierResource($supplier),
        ]);
    }

    /**
     * Soft delete a supplier record.
     */
    public function destroy(int $id): JsonResponse
    {
        $supplier = Supplier::findOrFail($id);
        $name = $supplier->name;
        $supplier->delete();

        return response()->json([
            'success' => true,
            'message' => "Supplier \"{$name}\" deleted successfully.",
        ]);
    }
}
