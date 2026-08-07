<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\Order\AdminOrderService;
use App\Services\Order\OrderStatusService;
use App\Http\Requests\Api\V1\Admin\DispatchOrderItemRequest;
use App\Http\Requests\Api\V1\Admin\BulkDispatchOrderItemsRequest;
use App\Http\Requests\Api\V1\Admin\OrderActionNoteRequest;
use App\Http\Requests\Api\V1\Admin\OrderRejectRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class AdminOrderController extends Controller
{
    /**
     * Get order dashboard statistics.
     */
    public function stats(Request $request, AdminOrderService $service): JsonResponse
    {
        $filters = [
            'search' => $request->query('search'),
            'status' => $request->query('status'),
            'checkout_method' => $request->query('checkout_method'),
            'payment_status' => $request->query('payment_status'),
            'credit_status' => $request->query('credit_status'),
            'date_from' => $request->query('date_from'),
            'date_to' => $request->query('date_to'),
        ];

        $stats = $service->getDashboardStats($filters);

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * List orders with search, status, payment, credit, and date filters.
     */
    public function index(Request $request, AdminOrderService $service): JsonResponse
    {
        $filters = [
            'search' => $request->query('search'),
            'status' => $request->query('status'),
            'checkout_method' => $request->query('checkout_method'),
            'payment_status' => $request->query('payment_status'),
            'credit_status' => $request->query('credit_status'),
            'date_from' => $request->query('date_from'),
            'date_to' => $request->query('date_to'),
            'per_page' => (int) $request->query('per_page', 10),
        ];

        $paginator = $service->listOrders($filters);

        return response()->json([
            'success' => true,
            'data' => $paginator->getCollection(),
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
     * Get single order detail view.
     */
    public function show(string $idOrNumber, AdminOrderService $service, OrderStatusService $statusService): JsonResponse
    {
        $orderDetail = $service->getOrderDetail($idOrNumber);
        $order = Order::findOrFail($orderDetail['id']);
        $allowedActions = $statusService->getAllowedActions($order, request()->user());

        $orderDetail['allowed_actions'] = $allowedActions;

        return response()->json([
            'success' => true,
            'data' => $orderDetail,
        ]);
    }

    /**
     * Mark order under review.
     */
    public function markUnderReview(OrderActionNoteRequest $request, int $id, AdminOrderService $service): JsonResponse
    {
        $order = Order::findOrFail($id);
        $updated = $service->markUnderReview($order, $request->user(), $request->input('admin_comment'));

        return response()->json([
            'success' => true,
            'message' => 'Order marked as under review successfully.',
            'data' => $service->getOrderDetail($updated->id),
        ]);
    }

    /**
     * Verify payment receipt for an order.
     */
    public function verifyReceipt(OrderActionNoteRequest $request, int $id, AdminOrderService $service): JsonResponse
    {
        $order = Order::findOrFail($id);

        try {
            $updated = $service->verifyReceipt($order, $request->user(), $request->input('admin_comment'));

            return response()->json([
                'success' => true,
                'message' => 'Payment receipt verified successfully.',
                'data' => $service->getOrderDetail($updated->id),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Reject payment receipt for an order.
     */
    public function rejectReceipt(OrderRejectRequest $request, int $id, AdminOrderService $service): JsonResponse
    {
        $order = Order::findOrFail($id);

        try {
            $updated = $service->rejectReceipt($order, $request->user(), $request->input('rejection_reason'));

            return response()->json([
                'success' => true,
                'message' => 'Payment receipt rejected successfully.',
                'data' => $service->getOrderDetail($updated->id),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Approve order and deduct inventory stock.
     */
    public function approve(OrderActionNoteRequest $request, int $id, AdminOrderService $service): JsonResponse
    {
        $order = Order::findOrFail($id);

        try {
            $updated = $service->approve($order, $request->user(), $request->input('admin_comment'));

            return response()->json([
                'success' => true,
                'message' => 'Order approved successfully.',
                'data' => $service->getOrderDetail($updated->id),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Reject order and reverse customer credit.
     */
    public function reject(OrderRejectRequest $request, int $id, AdminOrderService $service): JsonResponse
    {
        $order = Order::findOrFail($id);

        try {
            $updated = $service->reject($order, $request->user(), $request->input('rejection_reason'));

            return response()->json([
                'success' => true,
                'message' => 'Order rejected successfully.',
                'data' => $service->getOrderDetail($updated->id),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Cancel order and restore inventory stock.
     */
    public function cancel(OrderActionNoteRequest $request, int $id, AdminOrderService $service): JsonResponse
    {
        $order = Order::findOrFail($id);

        try {
            $updated = $service->cancel($order, $request->user(), $request->input('admin_comment'));

            return response()->json([
                'success' => true,
                'message' => 'Order cancelled successfully.',
                'data' => $service->getOrderDetail($updated->id),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Dispatch a single order item with support for fractional dispatch (e.g. 0.5 set or piece count).
     */
    public function dispatchItem(DispatchOrderItemRequest $request, int $itemId, AdminOrderService $service): JsonResponse
    {
        $item = OrderItem::findOrFail($itemId);
        $validated = $request->validated();
        $qtyToDispatch = (float) $validated['quantity'];
        $note = $validated['note'] ?? null;

        try {
            $order = $item->order;
            $now = now();
            $dispatchedAt = $now->toDateTimeString();

            $count = OrderItem::where('order_id', $order->id)
                ->whereNotNull('dispatch_number')
                ->distinct()
                ->count('dispatch_number');
            $dispatchNumber = 'DISP-' . $order->order_number . '-' . ($count + 1);

            $updatedOrder = $service->dispatchOrderItem(
                $item,
                $qtyToDispatch,
                $request->user(),
                $note,
                $dispatchNumber,
                $dispatchedAt
            );

            return response()->json([
                'success' => true,
                'message' => 'Order item dispatched successfully.',
                'data' => [
                    'dispatch_number' => $dispatchNumber,
                    'dispatched_quantity' => $qtyToDispatch,
                    'document_url' => url("/api/v1/admin/orders/dispatch/{$dispatchNumber}/document"),
                    'download_url' => url("/api/v1/admin/orders/dispatch/{$dispatchNumber}/download"),
                    'order' => $service->getOrderDetail($updatedOrder->id),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Dispatch multiple order items in bulk.
     */
    public function bulkDispatch(BulkDispatchOrderItemsRequest $request, int $id, AdminOrderService $service): JsonResponse
    {
        $order = Order::findOrFail($id);
        $validated = $request->validated();
        $note = $validated['note'] ?? null;

        try {
            $now = now();
            $dispatchedAt = $now->toDateTimeString();

            $count = OrderItem::where('order_id', $order->id)
                ->whereNotNull('dispatch_number')
                ->distinct()
                ->count('dispatch_number');
            $dispatchNumber = 'DISP-' . $order->order_number . '-' . ($count + 1);

            DB::transaction(function () use ($service, $validated, $request, $note, $dispatchNumber, $dispatchedAt) {
                foreach ($validated['items'] as $itemData) {
                    $item = OrderItem::findOrFail((int) $itemData['order_item_id']);
                    $qty = (float) $itemData['quantity'];
                    $service->dispatchOrderItem($item, $qty, $request->user(), $note, $dispatchNumber, $dispatchedAt);
                }
            });

            return response()->json([
                'success' => true,
                'message' => 'Bulk items dispatched successfully.',
                'data' => [
                    'dispatch_number' => $dispatchNumber,
                    'document_url' => url("/api/v1/admin/orders/dispatch/{$dispatchNumber}/document"),
                    'download_url' => url("/api/v1/admin/orders/dispatch/{$dispatchNumber}/download"),
                    'order' => $service->getOrderDetail($order->id),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Cancel a single pending order item.
     */
    public function cancelItem(int $itemId, AdminOrderService $service): JsonResponse
    {
        $item = OrderItem::findOrFail($itemId);

        try {
            $updatedOrder = $service->cancelOrderItem($item, request()->user());

            return response()->json([
                'success' => true,
                'message' => 'Order item cancelled successfully.',
                'data' => $service->getOrderDetail($updatedOrder->id),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Download or view printable dispatch note document.
     */
    public function downloadDispatchDocument(string $dispatchNumber, \App\Services\Order\DispatchDocumentService $docService)
    {
        return $docService->download($dispatchNumber);
    }
}
