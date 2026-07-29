<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\StoreAdminRequest;
use App\Http\Requests\Api\V1\Admin\UpdateAdminRequest;
use App\Http\Resources\Api\V1\AdminUserResource;
use App\Services\Admin\AdminManagementService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminManagementController extends Controller
{
    use ApiResponseTrait;

    protected AdminManagementService $managementService;

    public function __construct(AdminManagementService $managementService)
    {
        $this->managementService = $managementService;
    }

    /**
     * List all admins with pagination & filters.
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['search', 'status']);
        $perPage = (int) $request->input('per_page', 15);

        $result = $this->managementService->listAdmins($filters, $perPage);

        return $this->successResponse('Admins retrieved successfully.', $result);
    }

    /**
     * Get list of all available page and feature permissions.
     */
    public function permissions(): JsonResponse
    {
        $permissions = $this->managementService->getAvailablePermissions();

        return $this->successResponse('Available permissions retrieved successfully.', $permissions);
    }

    /**
     * Get single admin details.
     */
    public function show(int $id): JsonResponse
    {
        $admin = $this->managementService->getAdminDetail($id);

        return $this->successResponse('Admin details retrieved successfully.', new AdminUserResource($admin));
    }

    /**
     * Create a new Admin user.
     */
    public function store(StoreAdminRequest $request): JsonResponse
    {
        $admin = $this->managementService->createAdmin($request->validated());

        return $this->successResponse('Admin account created successfully.', new AdminUserResource($admin), 201);
    }

    /**
     * Update an existing Admin user.
     */
    public function update(UpdateAdminRequest $request, int $id): JsonResponse
    {
        $admin = $this->managementService->updateAdmin($id, $request->validated());

        return $this->successResponse('Admin account updated successfully.', new AdminUserResource($admin));
    }

    /**
     * Toggle active/inactive status of an admin.
     */
    public function toggleStatus(int $id): JsonResponse
    {
        $admin = $this->managementService->toggleAdminStatus($id);

        $message = $admin->is_active ? 'Admin account enabled successfully.' : 'Admin account restricted successfully.';

        return $this->successResponse($message, new AdminUserResource($admin));
    }

    /**
     * Delete an Admin account.
     */
    public function destroy(int $id): JsonResponse
    {
        $this->managementService->deleteAdmin($id);

        return $this->successResponse('Admin account deleted successfully.', []);
    }
}
