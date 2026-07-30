<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\RetailShop;
use App\Services\RetailShop\RetailShopService;
use App\Http\Requests\Api\V1\Admin\StoreRetailShopRequest;
use App\Http\Requests\Api\V1\Admin\UpdateRetailShopRequest;
use App\Http\Resources\Api\V1\AdminRetailShopResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AdminRetailShopController extends Controller
{
    /**
     * List retail shops with search and status filters.
     */
    public function index(Request $request, RetailShopService $service): JsonResponse
    {
        $filters = [
            'search' => $request->query('search'),
            'status' => $request->query('status'),
        ];
        $perPage = (int) $request->query('per_page', 10);

        $paginator = $service->list($filters, $perPage);

        return response()->json([
            'success' => true,
            'data' => AdminRetailShopResource::collection($paginator->getCollection()),
            'pagination' => [
                'total' => $paginator->total(),
                'count' => $paginator->count(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'total_pages' => $paginator->lastPage(),
            ]
        ]);
    }

    /**
     * Fetch simple options list of active retail shops for select dropdowns.
     */
    public function options(): JsonResponse
    {
        $shops = RetailShop::active()->orderBy('name')->get(['id', 'shop_code', 'name', 'city']);

        return response()->json([
            'success' => true,
            'data' => $shops,
        ]);
    }

    /**
     * Get details for a single retail shop.
     */
    public function show(int $id): JsonResponse
    {
        $shop = RetailShop::findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => new AdminRetailShopResource($shop),
        ]);
    }

    /**
     * Create a new retail shop.
     */
    public function store(StoreRetailShopRequest $request, RetailShopService $service): JsonResponse
    {
        $validated = $request->validated();
        $shop = $service->create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Retail shop created successfully.',
            'data' => new AdminRetailShopResource($shop),
        ], 201);
    }

    /**
     * Update an existing retail shop.
     */
    public function update(UpdateRetailShopRequest $request, int $id, RetailShopService $service): JsonResponse
    {
        $shop = RetailShop::findOrFail($id);
        $validated = $request->validated();

        $updatedShop = $service->update($shop, $validated);

        return response()->json([
            'success' => true,
            'message' => 'Retail shop updated successfully.',
            'data' => new AdminRetailShopResource($updatedShop),
        ]);
    }

    /**
     * Toggle retail shop active status.
     */
    public function toggleStatus(int $id, RetailShopService $service): JsonResponse
    {
        $shop = RetailShop::findOrFail($id);
        $updatedShop = $service->toggleStatus($shop);
        $statusText = $updatedShop->is_active ? 'activated' : 'deactivated';

        return response()->json([
            'success' => true,
            'message' => "Retail shop {$statusText} successfully.",
            'data' => [
                'id' => $updatedShop->id,
                'is_active' => (bool) $updatedShop->is_active,
            ]
        ]);
    }

    /**
     * Delete a retail shop.
     */
    public function destroy(int $id, RetailShopService $service): JsonResponse
    {
        $shop = RetailShop::findOrFail($id);
        $service->delete($shop);

        return response()->json([
            'success' => true,
            'message' => 'Retail shop deleted successfully.',
        ]);
    }
}
