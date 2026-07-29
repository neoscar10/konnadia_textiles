<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\StoreCustomerLevelRequest;
use App\Http\Requests\Api\V1\Admin\UpdateCustomerLevelRequest;
use App\Http\Resources\Api\V1\CustomerLevelResource;
use App\Models\CustomerLevel;
use App\Services\Customer\CustomerLevelService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminCustomerLevelController extends Controller
{
    use ApiResponseTrait;

    protected CustomerLevelService $levelService;

    public function __construct(CustomerLevelService $levelService)
    {
        $this->levelService = $levelService;
    }

    /**
     * List Customer Levels with pagination & filters.
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['search', 'status']);
        $perPage = (int) $request->input('per_page', 15);

        $paginator = $this->levelService->list($filters, $perPage);

        return $this->successResponse('Customer levels retrieved successfully.', [
            'levels' => CustomerLevelResource::collection($paginator->items()),
            'pagination' => [
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
            ]
        ]);
    }

    /**
     * Get single Customer Level details.
     */
    public function show(int $id): JsonResponse
    {
        $level = CustomerLevel::withCount('customers')->findOrFail($id);

        return $this->successResponse('Customer level details retrieved successfully.', new CustomerLevelResource($level));
    }

    /**
     * Create a new Customer Level tier.
     */
    public function store(StoreCustomerLevelRequest $request): JsonResponse
    {
        $level = $this->levelService->create($request->validated());

        return $this->successResponse('Customer level created successfully.', new CustomerLevelResource($level), 201);
    }

    /**
     * Update an existing Customer Level tier.
     */
    public function update(UpdateCustomerLevelRequest $request, int $id): JsonResponse
    {
        $level = CustomerLevel::findOrFail($id);
        $updatedLevel = $this->levelService->update($level, $request->validated());

        return $this->successResponse('Customer level updated successfully.', new CustomerLevelResource($updatedLevel));
    }

    /**
     * Toggle Customer Level active status.
     */
    public function toggleStatus(int $id): JsonResponse
    {
        $level = CustomerLevel::findOrFail($id);
        $toggledLevel = $this->levelService->toggleStatus($level);

        $message = $toggledLevel->is_active ? 'Customer level activated successfully.' : 'Customer level deactivated successfully.';

        return $this->successResponse($message, new CustomerLevelResource($toggledLevel));
    }

    /**
     * Delete Customer Level tier.
     */
    public function destroy(int $id): JsonResponse
    {
        $level = CustomerLevel::findOrFail($id);
        $this->levelService->delete($level);

        return $this->successResponse('Customer level deleted successfully.', []);
    }
}
