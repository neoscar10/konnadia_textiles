<?php

namespace App\Services\Order;

use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class OrderService
{
    protected OrderNumberService $orderNumberService;

    public function __construct(OrderNumberService $orderNumberService)
    {
        $this->orderNumberService = $orderNumberService;
    }

    /**
     * Create an order from a cart.
     */
    public function createFromCart(
        User $user,
        Cart $cart,
        array $checkoutPayload,
        array $checkoutEvaluation
    ): Order {
        return DB::transaction(function () use ($user, $cart, $checkoutPayload, $checkoutEvaluation) {
            $customer = $user->customer;
            $method = $checkoutPayload['checkout_method'];

            // Determine order status based on checkout method
            $status = 'submitted';
            $paymentStatus = 'not_required';
            $creditStatus = null;

            $status = 'submitted';
            $paymentStatus = 'not_required';
            $creditStatus = null;

            $order = Order::create([
                'order_number' => $this->orderNumberService->generate(),
                'user_id' => $user->id,
                'customer_id' => $customer->id,
                'status' => $status,
                'checkout_method' => $method,
                'payment_status' => $paymentStatus,
                'credit_status' => $creditStatus,
                'subtotal' => $checkoutPayload['subtotal'],
                'gst_amount' => $checkoutPayload['gst_amount'],
                'total_amount' => $checkoutPayload['total'],
                'credit_limit_at_order' => null,
                'available_credit_at_order' => null,
                'used_credit_override_privilege' => false,
                'customer_notes' => $checkoutPayload['customer_notes'] ?? null,
                'submitted_at' => now(),
            ]);

            // Copy cart items to order items
            $this->createOrderItems($order, $cart);

            // Record initial status history
            $this->recordStatus($order, null, $status, 'Order submitted by customer.', $user);

            return $order;
        });
    }

    /**
     * Create order items by snapshotting cart item data.
     */
    public function createOrderItems(Order $order, Cart $cart): void
    {
        $cart->load(['items.product', 'items.combination', 'items.unit']);

        foreach ($cart->items as $item) {
            OrderItem::create([
                'order_id'                 => $order->id,
                'product_id'               => $item->product_id,
                'product_combination_id'   => $item->product_combination_id,
                'product_unit_id'          => $item->product_unit_id,
                'product_title'            => $item->product->title,
                'product_sku'              => $item->combination ? $item->combination->sku : $item->product->sku,
                'hsn_code'                 => $item->hsn_code,  // snapshot from cart item
                'selected_options'         => $item->selected_options,
                'unit_name'                => $item->unit ? $item->unit->name : 'Piece',
                'unit_short_code'          => $item->unit ? $item->unit->short_code : 'Pcs',
                'unit_conversion_quantity' => $item->unit_conversion_quantity,
                'quantity'                 => $item->quantity,
                'quantity_lvl1'            => $item->quantity_lvl1,
                'quantity_lvl2'            => $item->quantity_lvl2,
                'base_unit_price'          => $item->base_unit_price,
                'customer_unit_price'      => $item->customer_unit_price,
                'line_subtotal'            => $item->line_subtotal,
                'gst_percentage'           => $item->gst_percentage,  // snapshot from cart item
                'gst_amount'               => $item->gst_amount,
                'line_total'               => $item->line_total,
            ]);
        }
    }

    /**
     * Record a status change in order history.
     */
    public function recordStatus(
        Order $order,
        ?string $from,
        string $to,
        ?string $note = null,
        ?User $changedBy = null
    ): void {
        $order->statusHistories()->create([
            'from_status' => $from,
            'to_status' => $to,
            'note' => $note,
            'changed_by' => $changedBy?->id,
        ]);
    }

    /**
     * Get a single order formatted for customer display.
     */
    public function getOrderForCustomer(User $user, string|int $identifier): ?array
    {
        $order = Order::where('user_id', $user->id)
            ->with(['items.product.media', 'items.product.primaryMedia', 'items.product.units', 'receipts', 'statusHistories.changedBy', 'customer'])
            ->where(function ($q) use ($identifier) {
                $q->where('order_number', $identifier)
                  ->orWhere('id', $identifier);
            })
            ->first();

        if (!$order) {
            return null;
        }

        return $this->formatOrderDetail($order);
    }

    /**
     * List orders for a customer with pagination.
     */
    public function listOrdersForCustomer(User $user, array $filters = []): LengthAwarePaginator
    {
        $query = Order::where('user_id', $user->id)
            ->with(['items.product.primaryMedia']);
            
        if (!empty($filters['status']) && $filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['checkout_method'])) {
            $query->where('checkout_method', $filters['checkout_method']);
        }
        if (!empty($filters['payment_status'])) {
            $query->where('payment_status', $filters['payment_status']);
        }
        if (!empty($filters['credit_status'])) {
            $query->where('credit_status', $filters['credit_status']);
        }

        if (!empty($filters['search'])) {
            $search = trim($filters['search']);
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhereHas('items', function ($iq) use ($search) {
                      $iq->where('product_title', 'like', "%{$search}%");
                  });
            });
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        $sort = $filters['sort'] ?? 'newest';
        match ($sort) {
            'oldest' => $query->orderBy('created_at', 'asc'),
            'total_high' => $query->orderBy('total_amount', 'desc'),
            'total_low' => $query->orderBy('total_amount', 'asc'),
            default => $query->orderBy('created_at', 'desc'),
        };

        $perPage = $filters['per_page'] ?? 10;
        $paginator = $query->paginate($perPage);

        $paginator->getCollection()->transform(function ($order) {
            return $this->formatOrderCard($order);
        });

        return $paginator;
    }

    /**
     * Format an order for list/card display.
     */
    protected function formatOrderCard(Order $order): array
    {
        $firstItem = $order->items->first();
        $firstItemData = null;
        if ($firstItem) {
            $imageUrl = 'https://images.unsplash.com/photo-1596755094514-f87e34085b2c?w=120';
            if ($firstItem->product && $firstItem->product->primaryMedia) {
                $path = $firstItem->product->primaryMedia->file_path;
                $imageUrl = str_starts_with($path, 'http') ? $path : url(Storage::url($path));
            }
            $firstItemData = [
                'product_title' => $firstItem->product_title,
                'product_sku' => $firstItem->product_sku,
                'image_url' => $imageUrl,
            ];
        }

        return [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'status' => app(OrderStatusService::class)->getStatusBadge($order->status),
            'checkout_method' => [
                'value' => $order->checkout_method,
                'label' => $order->checkout_method_label,
            ],
            'payment_status' => app(OrderStatusService::class)->getStatusBadge($order->payment_status),
            'credit_status' => $order->credit_status ? app(OrderStatusService::class)->getStatusBadge($order->credit_status) : null,
            'summary' => [
                'currency' => 'INR',
                'subtotal' => (float) $order->subtotal,
                'gst_amount' => (float) $order->gst_amount,
                'total' => (float) $order->total_amount,
                'formatted_subtotal' => '₹' . number_format($order->subtotal, 2),
                'formatted_gst_amount' => '₹' . number_format($order->gst_amount, 2),
                'formatted_total' => '₹' . number_format($order->total_amount, 2),
            ],
            'items_count' => $order->items->count(),
            'first_item' => $firstItemData,
            'submitted_at' => $order->submitted_at?->toIso8601String(),
            'created_at' => $order->created_at->toIso8601String(),
        ];
    }

    /**
     * Format an order for detail display.
     */
    protected function formatOrderDetail(Order $order): array
    {
        $items = $order->items->map(function ($item) {
            $imageUrl = 'https://images.unsplash.com/photo-1596755094514-f87e34085b2c?w=100';
            if ($item->product) {
                $primaryMedia = $item->product->primaryMedia;
                if ($primaryMedia) {
                    $imageUrl = str_starts_with($primaryMedia->file_path, 'http')
                        ? $primaryMedia->file_path
                        : Storage::url($primaryMedia->file_path);
                } elseif ($item->product->media->first()) {
                    $path = $item->product->media->first()->file_path;
                    $imageUrl = str_starts_with($path, 'http') ? $path : Storage::url($path);
                }
            }

            $optionsDisplay = '';
            if ($item->selected_options && is_array($item->selected_options)) {
                $parts = [];
                foreach ($item->selected_options as $key => $val) {
                    $parts[] = "{$key}: {$val}";
                }
                $optionsDisplay = implode(' • ', $parts);
            }

            $product = $item->product;
            $lvl2Unit = $product ? $product->units->where('level', 2)->first() : null;

            return [
                'id' => $item->id,
                'product_title' => $item->product_title,
                'product_sku' => $item->product_sku,
                'image_url' => $imageUrl,
                'selected_options' => $item->selected_options,
                'options_display' => $optionsDisplay,
                'unit_name' => $item->unit_name,
                'unit_short_code' => $item->unit_short_code,
                'unit_conversion_quantity' => (float) $item->unit_conversion_quantity,
                'quantity' => $item->quantity,
                'quantity_lvl1' => $item->quantity_lvl1,
                'quantity_lvl2' => $item->quantity_lvl2,
                'has_lvl2_unit' => !empty($lvl2Unit),
                'lvl1_unit_name' => $item->unit_name,
                'lvl2_unit_name' => $lvl2Unit ? $lvl2Unit->name : 'Box',
                'base_quantity' => (int)($item->quantity * (float)($item->unit_conversion_quantity ?: 1)),
                'tax' => [
                    'hsn_code' => $item->hsn_code,
                    'gst_percentage' => (float) $item->gst_percentage,
                    'gst_amount' => (float) $item->gst_amount,
                    'formatted_gst_amount' => '₹' . number_format($item->gst_amount, 2),
                ],
                'pricing' => [
                    'currency' => 'INR',
                    'base_unit_price' => (float) $item->base_unit_price,
                    'customer_unit_price' => (float) $item->customer_unit_price,
                    'line_subtotal' => (float) $item->line_subtotal,
                    'line_total' => (float) $item->line_total,
                    'formatted_base_unit_price' => '₹' . number_format($item->base_unit_price, 2),
                    'formatted_customer_unit_price' => '₹' . number_format($item->customer_unit_price, 2),
                    'formatted_line_subtotal' => '₹' . number_format($item->line_subtotal, 2),
                    'formatted_line_total' => '₹' . number_format($item->line_total, 2),
                ],
            ];
        })->toArray();

        $statusHistory = $order->statusHistories->map(fn($h) => [
            'from_status' => $h->from_status,
            'to_status' => $h->to_status,
            'note' => $h->note,
            'changed_by' => $h->changedBy?->name,
            'created_at' => $h->created_at->format('F d, Y \\a\\t h:i A'),
        ])->toArray();

        $receipts = $order->receipts->map(fn($r) => [
            'id' => $r->id,
            'url' => str_starts_with($r->file_path, 'http') ? $r->file_path : url(Storage::url($r->file_path)),
            'original_name' => $r->original_name,
            'mime_type' => 'application/pdf',
            'status' => app(OrderStatusService::class)->getStatusBadge($r->status),
            'admin_note' => $r->admin_note,
            'verified_at' => $r->verified_at?->toIso8601String(),
            'uploaded_at' => $r->created_at->toIso8601String(),
        ])->toArray();

        $totalBaseQty = 0;
        foreach ($order->items as $item) {
            $conversion = (float) ($item->unit_conversion_quantity ?: 1);
            $totalBaseQty += (int) ($conversion * $item->quantity);
        }

        $importantMessage = $this->getImportantMessageForCustomer($order);

        return [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'status' => app(OrderStatusService::class)->getStatusBadge($order->status),
            'checkout_method' => [
                'value' => $order->checkout_method,
                'label' => $order->checkout_method_label,
            ],
            'payment_status' => app(OrderStatusService::class)->getStatusBadge($order->payment_status),
            'credit_status' => $order->credit_status ? app(OrderStatusService::class)->getStatusBadge($order->credit_status) : null,
            'summary' => [
                'currency' => 'INR',
                'subtotal' => (float) $order->subtotal,
                'gst_amount' => (float) $order->gst_amount,
                'total' => (float) $order->total_amount,
                'formatted_subtotal' => '₹' . number_format($order->subtotal, 2),
                'formatted_gst_amount' => '₹' . number_format($order->gst_amount, 2),
                'formatted_total' => '₹' . number_format($order->total_amount, 2),
            ],
            'credit_snapshot' => [
                'credit_limit_at_order' => $order->credit_limit_at_order ? (float) $order->credit_limit_at_order : null,
                'available_credit_at_order' => $order->available_credit_at_order ? (float) $order->available_credit_at_order : null,
                'used_credit_override_privilege' => (bool)$order->used_credit_override_privilege,
            ],
            'used_credit_override_privilege' => (bool)$order->used_credit_override_privilege,
            'customer_notes' => $order->customer_notes,
            'admin_note' => $order->admin_note,
            'rejection_reason' => $order->rejection_reason,
            'items' => $items,
            'items_count' => count($items),
            'total_base_quantity' => $totalBaseQty,
            'timeline' => array_map(function($h) {
                return [
                    'status' => $h['to_status'],
                    'label' => app(OrderStatusService::class)->getStatusBadge($h['to_status'])['label'],
                    'description' => '',
                    'note' => $h['note'],
                    'badge' => app(OrderStatusService::class)->getStatusBadge($h['to_status'])['type'],
                    'is_completed' => true,
                    'created_at' => $h['created_at'],
                ];
            }, $statusHistory),
            'receipt' => !empty($receipts) ? $receipts[0] : null,
            'important_message' => $importantMessage,
            'submitted_at' => $order->submitted_at?->toIso8601String(),
            'approved_at' => $order->approved_at?->toIso8601String(),
            'rejected_at' => $order->rejected_at?->toIso8601String(),
            'dispatched_at' => $order->dispatched_at?->toIso8601String(),
            'created_at' => $order->created_at->toIso8601String(),
        ];
    }

    public function getImportantMessageForCustomer(Order $order): ?array
    {
        if ($order->status === 'rejected') {
            return [
                'type' => 'error',
                'title' => 'Order rejected',
                'message' => $order->rejection_reason ?: 'This order was rejected. Please contact support.',
            ];
        }

        if ($order->status === 'approved') {
            return [
                'type' => 'success',
                'title' => 'Order approved',
                'message' => 'Your order has been approved and is being prepared.',
            ];
        }

        if ($order->status === 'dispatched') {
            return [
                'type' => 'success',
                'title' => 'Order dispatched',
                'message' => 'Your order has been dispatched.',
            ];
        }

        if ($order->checkout_method === 'manual_payment') {
            if ($order->payment_status === 'pending_verification') {
                return [
                    'type' => 'info',
                    'title' => 'Receipt under review',
                    'message' => 'Your payment receipt has been uploaded and is awaiting verification.',
                ];
            }
            if ($order->payment_status === 'rejected') {
                $lastReceipt = $order->receipts->sortByDesc('created_at')->first();
                $note = $lastReceipt?->admin_note ? " Reason: {$lastReceipt->admin_note}" : '';
                return [
                    'type' => 'error',
                    'title' => 'Payment receipt rejected',
                    'message' => "Your payment receipt could not be verified.{$note}",
                ];
            }
        }

        if ($order->checkout_method === 'credit') {
            if ($order->used_credit_override_privilege) {
                return [
                    'type' => 'warning',
                    'title' => 'Extended credit privilege used',
                    'message' => 'This order exceeded your available credit limit but was allowed because your account has extended credit privilege.',
                ];
            }
            if ($order->status === 'submitted') {
                return [
                    'type' => 'success',
                    'title' => 'Credit order submitted',
                    'message' => 'This order was submitted using your available credit.',
                ];
            }
        }

        return null;
    }

    public function getOrderSummaryForCustomer(User $user): array
    {
        $orders = Order::where('user_id', $user->id)->get();
        $totalOrders = $orders->count();
        $pending = $orders->whereIn('status', ['submitted', 'under_review', 'pending_approval', 'pending_payment_verification'])->count();
        $approved = $orders->where('status', 'approved')->count();
        $rejected = $orders->where('status', 'rejected')->count();
        $dispatched = $orders->where('status', 'dispatched')->count();
        $pendingPayment = $orders->where('status', 'pending_payment_verification')->count();
        $totalValue = $orders->whereNotIn('status', ['rejected', 'cancelled'])->sum('total_amount');

        $lastOrderModel = $orders->sortByDesc('created_at')->first();
        $lastOrder = null;
        if ($lastOrderModel) {
            $lastOrder = [
                'order_number' => $lastOrderModel->order_number,
                'status' => app(OrderStatusService::class)->getStatusBadge($lastOrderModel->status),
                'total' => (float)$lastOrderModel->total_amount,
                'formatted_total' => '₹' . number_format($lastOrderModel->total_amount, 2),
                'submitted_at' => $lastOrderModel->submitted_at?->toIso8601String()
            ];
        }

        return [
            'total_orders' => $totalOrders,
            'pending_orders' => $pending,
            'approved_orders' => $approved,
            'rejected_orders' => $rejected,
            'dispatched_orders' => $dispatched,
            'pending_payment_verification' => $pendingPayment,
            'total_order_value' => (float)$totalValue,
            'formatted_total_order_value' => '₹' . number_format($totalValue, 2),
            'last_order' => $lastOrder,
        ];
    }

    public function getOrderFiltersForCustomer(User $user): array
    {
        return [
            'statuses' => [
                ['value' => 'submitted', 'label' => 'Submitted'],
                ['value' => 'under_review', 'label' => 'Under Review'],
                ['value' => 'pending_payment_verification', 'label' => 'Pending Payment Verification'],
                ['value' => 'approved', 'label' => 'Approved'],
                ['value' => 'rejected', 'label' => 'Rejected'],
                ['value' => 'dispatched', 'label' => 'Dispatched'],
                ['value' => 'cancelled', 'label' => 'Cancelled'],
            ],
            'checkout_methods' => [
                ['value' => 'manual_payment', 'label' => 'Manual Payment'],
                ['value' => 'credit', 'label' => 'Credit Purchase'],
            ],
            'payment_statuses' => [
                ['value' => 'not_required', 'label' => 'Not Required'],
                ['value' => 'pending_verification', 'label' => 'Pending Verification'],
                ['value' => 'verified', 'label' => 'Verified'],
                ['value' => 'rejected', 'label' => 'Rejected'],
            ],
            'credit_statuses' => [
                ['value' => 'within_limit', 'label' => 'Within Limit'],
                ['value' => 'over_limit_allowed', 'label' => 'Over Limit Allowed'],
                ['value' => 'pending_review', 'label' => 'Pending Review'],
            ],
            'sort_options' => [
                ['value' => 'newest', 'label' => 'Newest First'],
                ['value' => 'oldest', 'label' => 'Oldest First'],
                ['value' => 'total_high', 'label' => 'Total: High to Low'],
                ['value' => 'total_low', 'label' => 'Total: Low to High'],
            ]
        ];
    }

    public function getTimelineForCustomer(User $user, string|int $identifier): ?array
    {
        $order = $this->getOrderForCustomer($user, $identifier);
        if (!$order) {
            return null;
        }
        return $order['timeline'] ?? [];
    }

    public function getReceiptForCustomer(User $user, string|int $identifier): ?array
    {
        $order = $this->getOrderForCustomer($user, $identifier);
        if (!$order) {
            return null;
        }
        return $order['receipt'] ?? null;
    }
}
