<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\CustomerDashboardResource;
use App\Services\Customer\Dashboard\CustomerDashboardService;
use Illuminate\Http\Request;

class CustomerDashboardController extends Controller
{
    protected CustomerDashboardService $dashboardService;

    public function __construct(CustomerDashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    /**
     * Ensure the authenticated user has an active customer profile.
     */
    protected function ensureCustomerProfile(Request $request)
    {
        $user = $request->user();

        if (!$user->hasRole('customer')) {
            abort(response()->json([
                'success' => false,
                'message' => 'Only customer accounts can access the dashboard.',
                'errors' => new \stdClass()
            ], 403));
        }

        if (!$user->customer) {
            abort(response()->json([
                'success' => false,
                'message' => 'Customer profile not found for this account.',
                'errors' => new \stdClass()
            ], 403));
        }

        if (!$user->is_active) {
            abort(response()->json([
                'success' => false,
                'message' => 'Your account is inactive. Please contact support.',
                'errors' => new \stdClass()
            ], 403));
        }
    }

    /**
     * Show the dashboard details for the authenticated customer.
     */
    public function show(Request $request)
    {
        $this->ensureCustomerProfile($request);

        $data = $this->dashboardService->getDashboard($request->user());

        return response()->json([
            'success' => true,
            'message' => 'Dashboard details retrieved successfully.',
            'data' => new CustomerDashboardResource($data),
        ]);
    }
}
