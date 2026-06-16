<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Order\OrderIndexRequest;
use App\Http\Resources\Api\V1\OrderDetailResource;
use App\Http\Resources\Api\V1\OrderResource;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    /**
     * Display a listing of the customer's orders.
     */
    public function index(OrderIndexRequest $request): JsonResponse
    {
        $user = $request->user();
        $filters = $request->validated();

        $query = Order::where('user_id', $user->id)
            ->orderBy('created_at', 'desc');

        if (!empty($filters['status']) && $filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['search'])) {
            $search = trim($filters['search']);
            $query->where('order_number', 'like', "%{$search}%");
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        $perPage = $filters['per_page'] ?? 10;
        $orders = $query->paginate($perPage);

        return OrderResource::collection($orders)
            ->additional([
                'success' => true,
                'message' => 'Orders retrieved successfully.',
            ])
            ->response();
    }

    /**
     * Display the specified order details.
     */
    public function show(Request $request, $id): JsonResponse
    {
        $order = Order::where('user_id', $request->user()->id)
            ->with(['items.product.media', 'items.product.primaryMedia', 'receipts', 'statusHistories.changedBy'])
            ->where(function ($q) use ($id) {
                $q->where('id', $id)
                  ->orWhere('order_number', $id);
            })
            ->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found.',
                'errors' => new \stdClass()
            ], 404);
        }

        return (new OrderDetailResource($order))
            ->additional([
                'success' => true,
                'message' => 'Order retrieved successfully.',
            ])
            ->response();
    }
}
