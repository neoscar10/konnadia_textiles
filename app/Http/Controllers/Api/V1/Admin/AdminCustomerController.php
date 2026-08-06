<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\StoreCustomerRequest;
use App\Http\Requests\Api\V1\Admin\UpdateCustomerRequest;
use App\Http\Resources\Api\V1\AdminCustomerResource;
use App\Models\Customer;
use App\Services\Customer\CustomerService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminCustomerController extends Controller
{
    use ApiResponseTrait;

    protected CustomerService $customerService;

    public function __construct(CustomerService $customerService)
    {
        $this->customerService = $customerService;
    }

    /**
     * List customers with pagination & filters.
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['search', 'level_id', 'status']);
        $perPage = (int) $request->input('per_page', 15);

        $paginator = $this->customerService->list($filters, $perPage);

        return $this->successResponse('Customers retrieved successfully.', [
            'customers' => AdminCustomerResource::collection($paginator->items()),
            'pagination' => [
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
            ]
        ]);
    }

    /**
     * Get single customer details.
     */
    public function show(int $id): JsonResponse
    {
        $customer = Customer::with(['level', 'user', 'creditHoldBy'])->findOrFail($id);

        return $this->successResponse('Customer details retrieved successfully.', new AdminCustomerResource($customer));
    }

    /**
     * Create a new Customer.
     */
    public function store(StoreCustomerRequest $request): JsonResponse
    {
        $data = $request->validated();
        
        // Auto-compile billing address if specific address fields provided
        if (!empty($data['address'])) {
            $data['billing_address'] = trim(
                ($data['address'] ?? '') . "\n" . 
                ($data['city'] ?? '') . ", " . 
                ($data['state'] ?? '') . " - " . 
                ($data['pincode'] ?? '')
            );
        }

        $customer = $this->customerService->create($data);

        return $this->successResponse('Customer created successfully.', new AdminCustomerResource($customer), 201);
    }

    /**
     * Update an existing Customer.
     */
    public function update(UpdateCustomerRequest $request, int $id): JsonResponse
    {
        $customer = Customer::findOrFail($id);
        $data = $request->validated();

        if (!empty($data['address'])) {
            $data['billing_address'] = trim(
                ($data['address'] ?? '') . "\n" . 
                ($data['city'] ?? '') . ", " . 
                ($data['state'] ?? '') . " - " . 
                ($data['pincode'] ?? '')
            );
        }

        $updatedCustomer = $this->customerService->update($customer, $data);

        return $this->successResponse('Customer updated successfully.', new AdminCustomerResource($updatedCustomer));
    }

    /**
     * Toggle Customer active/inactive status.
     */
    public function toggleStatus(int $id): JsonResponse
    {
        $customer = Customer::findOrFail($id);
        $toggledCustomer = $this->customerService->toggleStatus($customer);

        $message = $toggledCustomer->is_active ? 'Customer activated successfully.' : 'Customer deactivated successfully.';

        return $this->successResponse($message, new AdminCustomerResource($toggledCustomer));
    }

    /**
     * Reset Customer user account password.
     */
    public function resetPassword(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'password' => [
                'required',
                'string',
                'min:8',
                function ($attribute, $value, $fail) {
                    if (request()->has('password_confirmation') && request()->input('password_confirmation') !== $value) {
                        $fail('The password field confirmation does not match.');
                    }
                },
            ],
        ]);

        $customer = Customer::findOrFail($id);
        if (!$customer->user) {
            return $this->errorResponse('Linked user account not found for this customer.', [], 404);
        }

        $customer->user->update([
            'password' => Hash::make($request->input('password'))
        ]);

        return $this->successResponse('Customer password reset successfully.', []);
    }

    /**
     * Export customer records to CSV file.
     */
    public function export(Request $request)
    {
        $filters = $request->only(['search', 'level_id', 'status']);
        return $this->customerService->exportCsv($filters);
    }

    /**
     * Delete Customer (soft delete).
     */
    public function destroy(int $id): JsonResponse
    {
        $customer = Customer::findOrFail($id);
        $this->customerService->delete($customer);

        return $this->successResponse('Customer deleted successfully.', []);
    }
}
