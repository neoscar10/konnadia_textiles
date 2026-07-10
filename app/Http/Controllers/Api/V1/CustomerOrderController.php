<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Order\OrderIndexRequest;
use App\Services\Order\OrderService;
use Illuminate\Http\Request;

class CustomerOrderController extends Controller
{
    protected OrderService $orderService;

    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
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
                'message' => 'Only customer accounts can access order tracking.',
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

    public function index(OrderIndexRequest $request)
    {
        $this->ensureCustomerProfile($request);
        
        $paginator = $this->orderService->listOrdersForCustomer($request->user(), $request->validated());
        
        // Convert to array and format properly according to API standards
        $array = $paginator->toArray();

        return response()->json([
            'success' => true,
            'message' => 'Orders retrieved successfully.',
            'data' => $array['data'],
            'meta' => [
                'current_page' => $array['current_page'],
                'per_page' => $array['per_page'],
                'total' => $array['total'],
                'last_page' => $array['last_page'],
                'from' => $array['from'],
                'to' => $array['to'],
            ]
        ]);
    }

    public function summary(Request $request)
    {
        $this->ensureCustomerProfile($request);

        $summary = $this->orderService->getOrderSummaryForCustomer($request->user());

        return response()->json([
            'success' => true,
            'message' => 'Order summary retrieved successfully.',
            'data' => $summary,
        ]);
    }

    public function filters(Request $request)
    {
        $this->ensureCustomerProfile($request);

        $filters = $this->orderService->getOrderFiltersForCustomer($request->user());

        return response()->json([
            'success' => true,
            'message' => 'Order filters retrieved successfully.',
            'data' => $filters,
        ]);
    }

    public function show(Request $request, string $orderIdentifier)
    {
        $this->ensureCustomerProfile($request);

        $order = \App\Models\Order::where('user_id', $request->user()->id)
            ->where(function ($q) use ($orderIdentifier) {
                $q->where('order_number', $orderIdentifier)
                  ->orWhere('id', $orderIdentifier);
            })
            ->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found.',
                'errors' => new \stdClass()
            ], 404);
        }

        return (new \App\Http\Resources\Api\V1\OrderDetailResource($order))
            ->additional([
                'success' => true,
                'message' => 'Order retrieved successfully.',
            ])
            ->response();
    }

    public function timeline(Request $request, string $orderIdentifier)
    {
        $this->ensureCustomerProfile($request);

        $timeline = $this->orderService->getTimelineForCustomer($request->user(), $orderIdentifier);

        if ($timeline === null) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found.',
                'errors' => new \stdClass()
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Order timeline retrieved successfully.',
            'data' => $timeline,
        ]);
    }

    public function receipt(Request $request, string $orderIdentifier)
    {
        $this->ensureCustomerProfile($request);

        $receipt = $this->orderService->getReceiptForCustomer($request->user(), $orderIdentifier);

        if ($receipt === null) {
            return response()->json([
                'success' => false,
                'message' => 'No payment receipt found for this order.',
                'errors' => new \stdClass()
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Order receipt retrieved successfully.',
            'data' => $receipt,
        ]);
    }
}
