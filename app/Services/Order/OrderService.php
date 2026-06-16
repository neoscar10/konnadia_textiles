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

            if ($method === 'manual_payment') {
                $status = 'pending_payment_verification';
                $paymentStatus = 'pending_verification';
            } elseif ($method === 'credit') {
                $status = 'submitted';
                $paymentStatus = 'not_required';

                if ($checkoutEvaluation['is_within_limit']) {
                    $creditStatus = 'within_limit';
                } elseif ($checkoutEvaluation['is_privileged_override']) {
                    $creditStatus = 'over_limit_allowed';
                }
            }

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
                'credit_limit_at_order' => $method === 'credit' ? $customer->credit_limit : null,
                'available_credit_at_order' => $method === 'credit' ? $customer->available_credit : null,
                'used_credit_override_privilege' => $checkoutEvaluation['is_privileged_override'] ?? false,
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
                'order_id' => $order->id,
                'product_id' => $item->product_id,
                'product_combination_id' => $item->product_combination_id,
                'product_unit_id' => $item->product_unit_id,
                'product_title' => $item->product->title,
                'product_sku' => $item->combination ? $item->combination->sku : $item->product->sku,
                'selected_options' => $item->selected_options,
                'unit_name' => $item->unit ? $item->unit->name : 'Piece',
                'unit_short_code' => $item->unit ? $item->unit->short_code : 'Pcs',
                'unit_conversion_quantity' => $item->unit_conversion_quantity,
                'quantity' => $item->quantity,
                'base_unit_price' => $item->base_unit_price,
                'customer_unit_price' => $item->customer_unit_price,
                'line_subtotal' => $item->line_subtotal,
                'gst_percentage' => $item->gst_percentage,
                'gst_amount' => $item->gst_amount,
                'line_total' => $item->line_total,
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
            ->with(['items.product.media', 'items.product.primaryMedia', 'receipts', 'statusHistories.changedBy', 'customer'])
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
            ->with(['items.product.primaryMedia'])
            ->orderBy('created_at', 'desc');

        if (!empty($filters['status']) && $filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['search'])) {
            $search = trim($filters['search']);
            $query->where('order_number', 'like', "%{$search}%");
        }

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
        $images = [];
        foreach ($order->items as $item) {
            if ($item->product && $item->product->primaryMedia) {
                $path = $item->product->primaryMedia->file_path;
                $images[] = str_starts_with($path, 'http') ? $path : Storage::url($path);
            }
        }

        if (empty($images)) {
            $images[] = 'https://images.unsplash.com/photo-1596755094514-f87e34085b2c?w=120';
        }

        $totalBaseQty = 0;
        foreach ($order->items as $item) {
            $conversion = (float) ($item->unit_conversion_quantity ?: 1);
            $totalBaseQty += (int) ($conversion * $item->quantity);
        }

        return [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'status' => $order->status,
            'status_label' => $order->status_label,
            'checkout_method' => $order->checkout_method,
            'checkout_method_label' => $order->checkout_method_label,
            'payment_status' => $order->payment_status,
            'items_count' => $order->items->count(),
            'total_base_quantity' => $totalBaseQty,
            'subtotal' => (float) $order->subtotal,
            'gst_amount' => (float) $order->gst_amount,
            'total_amount' => (float) $order->total_amount,
            'images' => array_slice($images, 0, 4),
            'submitted_at' => $order->submitted_at?->format('F d, Y'),
            'created_at' => $order->created_at->format('F d, Y'),
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
                'base_unit_price' => (float) $item->base_unit_price,
                'customer_unit_price' => (float) $item->customer_unit_price,
                'line_subtotal' => (float) $item->line_subtotal,
                'gst_percentage' => (float) $item->gst_percentage,
                'gst_amount' => (float) $item->gst_amount,
                'line_total' => (float) $item->line_total,
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
            'file_url' => Storage::url($r->file_path),
            'original_name' => $r->original_name,
            'status' => $r->status,
            'admin_note' => $r->admin_note,
            'verified_at' => $r->verified_at?->format('F d, Y'),
            'uploaded_at' => $r->created_at->format('F d, Y \\a\\t h:i A'),
        ])->toArray();

        $totalBaseQty = 0;
        foreach ($order->items as $item) {
            $conversion = (float) ($item->unit_conversion_quantity ?: 1);
            $totalBaseQty += (int) ($conversion * $item->quantity);
        }

        return [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'status' => $order->status,
            'status_label' => $order->status_label,
            'checkout_method' => $order->checkout_method,
            'checkout_method_label' => $order->checkout_method_label,
            'payment_status' => $order->payment_status,
            'credit_status' => $order->credit_status,
            'subtotal' => (float) $order->subtotal,
            'gst_amount' => (float) $order->gst_amount,
            'total_amount' => (float) $order->total_amount,
            'credit_limit_at_order' => $order->credit_limit_at_order ? (float) $order->credit_limit_at_order : null,
            'available_credit_at_order' => $order->available_credit_at_order ? (float) $order->available_credit_at_order : null,
            'used_credit_override_privilege' => $order->used_credit_override_privilege,
            'customer_notes' => $order->customer_notes,
            'items' => $items,
            'items_count' => count($items),
            'total_base_quantity' => $totalBaseQty,
            'status_history' => $statusHistory,
            'receipts' => $receipts,
            'submitted_at' => $order->submitted_at?->format('F d, Y \\a\\t h:i A'),
            'created_at' => $order->created_at->format('F d, Y'),
        ];
    }
}
