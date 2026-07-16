<?php

namespace App\Services\Order;

use App\Models\Order;
use App\Models\User;
use App\Models\OrderPaymentReceipt;
use App\Services\Inventory\OrderInventoryService;
use App\Services\Credit\CustomerCreditService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class AdminOrderService
{
    protected OrderStatusService $statusService;
    protected OrderInventoryService $inventoryService;
    protected CustomerCreditService $creditService;

    public function __construct(
        OrderStatusService $statusService,
        OrderInventoryService $inventoryService,
        CustomerCreditService $creditService
    ) {
        $this->statusService = $statusService;
        $this->inventoryService = $inventoryService;
        $this->creditService = $creditService;
    }

    /**
     * List all orders for administrative panel.
     */
    public function listOrders(array $filters = []): LengthAwarePaginator
    {
        $query = Order::with(['customer', 'items.product'])
            ->latestFirst();

        $this->applyOrderScope($query);

        $query->status($filters['status'] ?? null);
        $query->checkoutMethod($filters['checkout_method'] ?? null);
        $query->paymentStatus($filters['payment_status'] ?? null);
        $query->creditStatus($filters['credit_status'] ?? null);
        $query->dateRange($filters['date_from'] ?? null, $filters['date_to'] ?? null);
        $query->search($filters['search'] ?? null);

        $perPage = $filters['per_page'] ?? 10;
        $paginator = $query->paginate($perPage);

        $paginator->getCollection()->transform(function ($order) {
            return $this->formatAdminOrderCard($order);
        });

        return $paginator;
    }

    /**
     * Format an order card for administration panel list views.
     */
    public function formatAdminOrderCard(Order $order): array
    {
        $user = auth()->user();
        $isSuperAdmin = $user && $user->hasRole('super_admin');
        
        $total_amount = (float) $order->total_amount;
        if (!$isSuperAdmin && $user) {
            $hasManufactured = $user->hasPermissionTo('manage manufactured orders');
            $hasRetail = $user->hasPermissionTo('manage retail orders');
            
            if ($hasManufactured && !$hasRetail) {
                $total_amount = (float) $order->items->filter(function ($item) {
                    return $item->product && $item->product->product_type === 'retail';
                })->sum('line_total');
            } elseif ($hasRetail && !$hasManufactured) {
                $total_amount = (float) $order->items->filter(function ($item) {
                    return $item->product && $item->product->product_type === 'manufactured';
                })->sum('line_total');
            } elseif (!$hasManufactured && !$hasRetail) {
                $total_amount = 0.0;
            }
        }

        return [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'customer_name' => $order->customer->company_name ?? 'N/A',
            'customer_level' => $order->customer->level->name ?? 'N/A',
            'checkout_method' => $order->checkout_method,
            'checkout_method_label' => $order->checkout_method_label,
            'payment_status' => $order->payment_status,
            'credit_status' => $order->credit_status,
            'total_amount' => $total_amount,
            'formatted_total' => '₹' . number_format($total_amount, 2),
            'status' => $order->status,
            'status_label' => $order->status_label,
            'submitted_at' => $order->submitted_at ? $order->submitted_at->format('d-M-Y') : 'N/A',
            'created_at' => $order->created_at->format('d-M-Y'),
        ];
    }

    /**
     * Retrieve complete admin-details for an order.
     */
    public function getOrderDetail(Order|string|int $order): array
    {
        if (!$order instanceof Order) {
            $query = Order::where(function($q) use ($order) {
                $q->where('order_number', $order)
                  ->orWhere('id', $order);
            })->with(['customer.level', 'items.product', 'receipts', 'statusHistories.changedBy']);
            
            $this->applyOrderScope($query);
            
            $order = $query->firstOrFail();
        } else {
            $user = auth()->user();
            if ($user && !$user->hasRole('super_admin')) {
                $hasManufactured = $user->hasPermissionTo('manage manufactured orders');
                $hasRetail = $user->hasPermissionTo('manage retail orders');

                $hasItemsInScope = false;
                if ($hasManufactured && !$hasRetail) {
                    $hasItemsInScope = $order->items()->whereHas('product', function($q) {
                        $q->where('product_type', 'retail');
                    })->exists();
                } elseif ($hasRetail && !$hasManufactured) {
                    $hasItemsInScope = $order->items()->whereHas('product', function($q) {
                        $q->where('product_type', 'manufactured');
                    })->exists();
                } elseif ($hasManufactured && $hasRetail) {
                    $hasItemsInScope = true;
                }

                if (!$hasItemsInScope) {
                    throw new \Illuminate\Database\Eloquent\ModelNotFoundException("Order not found or out of scope.");
                }
            }
            $order->load(['customer.level', 'items.product', 'receipts', 'statusHistories.changedBy']);
        }

        return $this->formatAdminOrderDetail($order);
    }

    /**
     * Format detailed administration metadata.
     */
    public function formatAdminOrderDetail(Order $order): array
    {
        $user = auth()->user();
        $isSuperAdmin = $user && $user->hasRole('super_admin');
        
        $filteredItems = $order->items;
        if (!$isSuperAdmin && $user) {
            $hasManufactured = $user->hasPermissionTo('manage manufactured orders');
            $hasRetail = $user->hasPermissionTo('manage retail orders');
            
            if ($hasManufactured && !$hasRetail) {
                $filteredItems = $order->items->filter(function ($item) {
                    return $item->product && $item->product->product_type === 'retail';
                });
            } elseif ($hasRetail && !$hasManufactured) {
                $filteredItems = $order->items->filter(function ($item) {
                    return $item->product && $item->product->product_type === 'manufactured';
                });
            } elseif (!$hasManufactured && !$hasRetail) {
                $filteredItems = collect();
            }
        }

        $items = $filteredItems->map(function ($item) {
            $product = $item->product;
            $lvl2Unit = $product ? $product->units->where('level', 2)->first() : null;

            // Get product primary media file path (fallback to first media if primary not set)
            $primaryMediaFilePath = null;
            if ($product) {
                $primaryMedia = $product->primaryMedia ?: $product->media()->first();
                if ($primaryMedia) {
                    $primaryMediaFilePath = $primaryMedia->file_path;
                }
            }

            return [
                'id' => $item->id,
                'product_title' => $item->product_title,
                'product_sku' => $item->product_sku,
                'selected_options' => $item->selected_options,
                'unit_name' => $item->unit_name,
                'unit_short_code' => $item->unit_short_code,
                'unit_conversion_quantity' => (float) $item->unit_conversion_quantity,
                'quantity' => (int) $item->quantity,
                'quantity_lvl1' => (int) $item->quantity_lvl1,
                'quantity_lvl2' => (int) $item->quantity_lvl2,
                'has_lvl2_unit' => !empty($lvl2Unit),
                'lvl1_unit_name' => $item->unit_name,
                'lvl2_unit_name' => $lvl2Unit ? $lvl2Unit->name : 'Box',
                'base_quantity' => (int) ($item->quantity * $item->unit_conversion_quantity),
                'customer_unit_price' => (float) $item->customer_unit_price,
                'gst_percentage' => (float) $item->gst_percentage,
                'gst_amount' => (float) $item->gst_amount,
                'line_total' => (float) $item->line_total,
                'formatted_line_total' => '₹' . number_format($item->line_total, 2),
                'status' => $item->status ?: 'pending_dispatch',
                'product_type' => $product ? $product->product_type : 'retail',
                'dispatch_note' => $item->dispatch_note,
                'dispatch_number' => $item->dispatch_number,
                'dispatched_at' => $item->dispatched_at ? $item->dispatched_at->format('d-M-Y H:i') : null,
                'dispatched_by_name' => $item->dispatchedBy->name ?? 'System',
                'primary_media_file_path' => $primaryMediaFilePath,
            ];
        })->toArray();

        $receipts = $order->receipts->map(function ($r) {
            return [
                'id' => $r->id,
                'file_path' => $r->file_path,
                'file_url' => Storage::url($r->file_path),
                'original_name' => $r->original_name,
                'mime_type' => $r->mime_type,
                'size' => $r->size,
                'status' => $r->status,
                'admin_note' => $r->admin_note,
                'verified_at' => $r->verified_at ? $r->verified_at->format('d-M-Y H:i') : null,
                'rejected_at' => $r->rejected_at ? $r->rejected_at->format('d-M-Y H:i') : null,
            ];
        })->toArray();

        $statusHistory = $order->statusHistories->map(function ($history) {
            return [
                'from_status' => $history->from_status,
                'to_status' => $history->to_status,
                'note' => $history->note,
                'changed_by' => $history->changedBy->name ?? 'System',
                'created_at' => $history->created_at->format('d-M-Y H:i'),
            ];
        })->toArray();

        $customer = $order->customer;

        if (!$isSuperAdmin && $user && (isset($hasManufactured) && isset($hasRetail) && $hasManufactured !== $hasRetail)) {
            $subtotal = (float) $filteredItems->sum('line_subtotal');
            $gst_amount = (float) $filteredItems->sum('gst_amount');
            $total_amount = $subtotal + $gst_amount;
        } else {
            $subtotal = (float) $order->subtotal;
            $gst_amount = (float) $order->gst_amount;
            $total_amount = (float) $order->total_amount;
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
            'subtotal' => $subtotal,
            'gst_amount' => $gst_amount,
            'total_amount' => $total_amount,
            'formatted_subtotal' => '₹' . number_format($subtotal, 2),
            'formatted_gst' => '₹' . number_format($gst_amount, 2),
            'formatted_total' => '₹' . number_format($total_amount, 2),
            'credit_limit_at_order' => $order->credit_limit_at_order !== null ? (float) $order->credit_limit_at_order : null,
            'available_credit_at_order' => $order->available_credit_at_order !== null ? (float) $order->available_credit_at_order : null,
            'used_credit_override_privilege' => (bool) $order->used_credit_override_privilege,
            'customer_notes' => $order->customer_notes,
            'admin_note' => $order->admin_note,
            'rejection_reason' => $order->rejection_reason,
            'submitted_at' => $order->submitted_at ? $order->submitted_at->format('d-M-Y \a\t h:i A') : 'N/A',
            'created_at' => $order->created_at->format('d-M-Y'),
            'customer' => [
                'id' => $customer->id ?? null,
                'company_name' => $customer->company_name ?? 'N/A',
                'contact_person' => $customer->contact_person ?? 'N/A',
                'mobile_number' => $customer->mobile_number ?? 'N/A',
                'email' => $customer->email ?? 'N/A',
                'customer_number' => $customer->customer_number ?? 'N/A',
                'level_name' => $customer->level->name ?? 'N/A',
                'gst_number' => $customer->gst_number ?? 'N/A',
                'billing_address' => $customer->billing_address ?? 'N/A',
                'credit_limit' => $customer ? (float) $customer->credit_limit : 0.0,
                'available_credit' => $customer ? (float) $customer->available_credit : 0.0,
                'outstanding_amount' => $customer ? (float) $customer->outstanding_amount : 0.0,
            ],
            'items' => $items,
            'receipts' => $receipts,
            'status_history' => $statusHistory,
            'stock_deducted' => $order->stock_deducted_at !== null,
        ];
    }

    /**
     * Mark an order as under review.
     */
    public function markUnderReview(Order $order, User $admin, ?string $note = null): Order
    {
        return DB::transaction(function () use ($order, $admin, $note) {
            $order->update(['admin_note' => $note]);
            return $this->statusService->transition($order, 'under_review', $admin, $note ?: 'Order marked as under review by admin.');
        });
    }

    /**
     * Approve an order. Deducts stock during execution.
     */
    public function approve(Order $order, User $admin, ?string $note = null): Order
    {
        return DB::transaction(function () use ($order, $admin, $note) {
            // Mark approved
            $order->update([
                'admin_note' => $note,
                'approved_at' => now(),
            ]);

            return $this->statusService->transition($order, 'approved', $admin, $note ?: 'Order approved by admin.');
        });
    }

    /**
     * Reject an order. Reverses outstanding credit.
     */
    public function reject(Order $order, User $admin, string $reason): Order
    {
        if (empty(trim($reason))) {
            throw ValidationException::withMessages([
                'reason' => 'A rejection reason is required.',
            ]);
        }

        return DB::transaction(function () use ($order, $admin, $reason) {
            // 1. Restore stock if it was previously approved/deducted
            $this->inventoryService->restoreStockForOrder($order);

            // 2. Reverse credit
            if ($order->checkout_method === 'credit') {
                $this->creditService->reverseCreditOrder($order->customer, $order);
            }

            // 3. Reject order
            $order->update([
                'rejection_reason' => $reason,
                'rejected_at' => now(),
            ]);

            return $this->statusService->transition($order, 'rejected', $admin, "Order rejected. Reason: {$reason}");
        });
    }

    /**
     * Dispatch an approved order.
     */
    public function dispatch(Order $order, User $admin, ?string $note = null): Order
    {
        return DB::transaction(function () use ($order, $admin, $note) {
            $order->update(['dispatched_at' => now()]);
            return $this->statusService->transition($order, 'dispatched', $admin, $note ?: 'Order dispatched.');
        });
    }

    /**
     * Verify payment receipt. Moves status to under_review automatically.
     */
    public function verifyReceipt(Order $order, User $admin, ?string $note = null): Order
    {
        return DB::transaction(function () use ($order, $admin, $note) {
            $receipt = $order->receipt;
            if (!$receipt) {
                throw ValidationException::withMessages([
                    'receipt' => 'No payment receipt found to verify.',
                ]);
            }

            $receipt->update([
                'status' => 'verified',
                'verified_at' => now(),
                'admin_note' => $note,
            ]);

            $order->update(['payment_status' => 'verified']);

            // Determine the correct next status based on where the order currently is.
            // If the order is already under_review (admin moved it there manually before
            // verifying the receipt), advance it to pending_approval so it can be approved.
            // Otherwise (e.g. status is pending_payment_verification), move it to under_review.
            $nextStatus = $order->status === 'under_review' ? 'pending_approval' : 'under_review';

            return $this->statusService->transition($order, $nextStatus, $admin, 'Payment receipt verified by admin.');
        });
    }

    /**
     * Reject payment receipt. Rejects order automatically.
     */
    public function rejectReceipt(Order $order, User $admin, string $reason): Order
    {
        if (empty(trim($reason))) {
            throw ValidationException::withMessages([
                'reason' => 'A rejection reason is required.',
            ]);
        }

        return DB::transaction(function () use ($order, $admin, $reason) {
            $receipt = $order->receipt;
            if (!$receipt) {
                throw ValidationException::withMessages([
                    'receipt' => 'No payment receipt found to reject.',
                ]);
            }

            $receipt->update([
                'status' => 'rejected',
                'rejected_at' => now(),
                'admin_note' => $reason,
            ]);

            $order->update([
                'payment_status' => 'rejected',
                'rejection_reason' => $reason,
                'rejected_at' => now(),
            ]);

            return $this->statusService->transition($order, 'rejected', $admin, "Payment receipt rejected. Reason: {$reason}");
        });
    }

    /**
     * Cancel an order. Restores stock and reverses outstanding credit.
     */
    public function cancel(Order $order, User $admin, ?string $note = null): Order
    {
        return DB::transaction(function () use ($order, $admin, $note) {
            // 1. Restore stock if it was previously deducted
            $this->inventoryService->restoreStockForOrder($order);

            // 2. Reverse credit
            if ($order->checkout_method === 'credit') {
                $this->creditService->reverseCreditOrder($order->customer, $order);
            }

            // 3. Update notes
            if ($note) {
                $order->update(['admin_note' => $note]);
            }

            // 4. Cancel order status
            return $this->statusService->transition($order, 'cancelled', $admin, $note ?: 'Order cancelled by admin.');
        });
    }

    /**
     * Fetch aggregated statistics for administration dashboard.
     */
    public function getDashboardStats(array $filters = []): array
    {
        $baseQuery = Order::query();
        $this->applyOrderScope($baseQuery);

        $baseQuery->checkoutMethod($filters['checkout_method'] ?? null);
        $baseQuery->paymentStatus($filters['payment_status'] ?? null);
        $baseQuery->creditStatus($filters['credit_status'] ?? null);
        $baseQuery->dateRange($filters['date_from'] ?? null, $filters['date_to'] ?? null);
        $baseQuery->search($filters['search'] ?? null);

        $totalOrders = (clone $baseQuery)->count();
        $pendingReview = (clone $baseQuery)->whereIn('status', ['submitted', 'under_review', 'pending_approval'])->count();
        $pendingPayment = (clone $baseQuery)->where('status', 'pending_payment_verification')->count();
        $approvedOrders = (clone $baseQuery)->where('status', 'approved')->count();
        $rejectedOrders = (clone $baseQuery)->where('status', 'rejected')->count();

        $user = auth()->user();
        $isSuperAdmin = $user && $user->hasRole('super_admin');

        if (!$isSuperAdmin && $user) {
            $hasManufactured = $user->hasPermissionTo('manage manufactured orders');
            $hasRetail = $user->hasPermissionTo('manage retail orders');

            if ($hasManufactured && !$hasRetail) {
                // Sum line_total for only manufactured items
                $totalValue = DB::table('orders')
                    ->join('order_items', 'orders.id', '=', 'order_items.order_id')
                    ->join('products', 'order_items.product_id', '=', 'products.id')
                    ->whereIn('orders.id', (clone $baseQuery)->pluck('id'))
                    ->whereNotIn('orders.status', ['cancelled', 'rejected'])
                    ->whereNull('products.deleted_at')
                    ->where('products.product_type', 'retail')
                    ->sum('order_items.line_total');
            } elseif ($hasRetail && !$hasManufactured) {
                // Sum line_total for only retail items
                $totalValue = DB::table('orders')
                    ->join('order_items', 'orders.id', '=', 'order_items.order_id')
                    ->join('products', 'order_items.product_id', '=', 'products.id')
                    ->whereIn('orders.id', (clone $baseQuery)->pluck('id'))
                    ->whereNotIn('orders.status', ['cancelled', 'rejected'])
                    ->whereNull('products.deleted_at')
                    ->where('products.product_type', 'manufactured')
                    ->sum('order_items.line_total');
            } else {
                $totalValue = 0.0;
            }
        } else {
            $totalValue = (clone $baseQuery)->whereNotIn('status', ['cancelled', 'rejected'])->sum('total_amount');
        }

        return [
            'total_orders' => $totalOrders,
            'pending_review' => $pendingReview,
            'pending_payment_verification' => $pendingPayment,
            'approved_orders' => $approvedOrders,
            'rejected_orders' => $rejectedOrders,
            'total_value' => (float) $totalValue,
            'formatted_total_value' => '₹' . number_format($totalValue, 2),
        ];
    }

    /**
     * Dispatch a specific quantity of an order item.
     */
    public function dispatchOrderItem(\App\Models\OrderItem $item, int $qtyToDispatch, User $admin, ?string $note = null, ?string $dispatchNumber = null, ?string $dispatchedAt = null): Order
    {
        if ($qtyToDispatch <= 0) {
            throw ValidationException::withMessages(['quantity' => 'Dispatch quantity must be greater than zero.']);
        }
        if ($qtyToDispatch > $item->quantity) {
            throw ValidationException::withMessages(['quantity' => "Cannot dispatch more than the ordered quantity ({$item->quantity})."]);
        }
        if ($item->status && $item->status !== 'pending_dispatch') {
            throw ValidationException::withMessages(['item' => 'Only pending dispatch items can be dispatched.']);
        }

        $order = $item->order;

        return DB::transaction(function () use ($item, $qtyToDispatch, $order, $admin, $note, $dispatchNumber, $dispatchedAt) {
            $item->load('unit');

            // Deduct stock for this item
            $this->inventoryService->deductStockForOrderItem($item, $qtyToDispatch);

            if ($qtyToDispatch < $item->quantity) {
                // Split the item:
                $remainingQty = $item->quantity - $qtyToDispatch;

                // Create a new order item for the remainder
                $newItem = $item->replicate();
                $newItem->quantity = $remainingQty;
                
                // Adjust level-specific quantities
                if ($item->product_unit_id && $item->unit && $item->unit->level === 2) {
                    $newItem->quantity_lvl2 = $remainingQty;
                    $newItem->quantity_lvl1 = 0;
                } else {
                    $newItem->quantity_lvl1 = $remainingQty;
                    $newItem->quantity_lvl2 = 0;
                }

                // Recalculate line totals for the new item
                $newItem->line_subtotal = $newItem->customer_unit_price * $remainingQty;
                $newItem->gst_amount = $newItem->line_subtotal * ($newItem->gst_percentage / 100);
                $newItem->line_total = $newItem->line_subtotal + $newItem->gst_amount;
                $newItem->status = 'pending_dispatch';
                $newItem->save();

                // Adjust original item
                $item->quantity = $qtyToDispatch;
                if ($item->product_unit_id && $item->unit && $item->unit->level === 2) {
                    $item->quantity_lvl2 = $qtyToDispatch;
                    $item->quantity_lvl1 = 0;
                } else {
                    $item->quantity_lvl1 = $qtyToDispatch;
                    $item->quantity_lvl2 = 0;
                }
                $item->line_subtotal = $item->customer_unit_price * $qtyToDispatch;
                $item->gst_amount = $item->line_subtotal * ($item->gst_percentage / 100);
                $item->line_total = $item->line_subtotal + $item->gst_amount;
            }

            // Mark original item as dispatched
            $item->status = 'dispatched';
            $item->dispatch_note = $note;
            $item->dispatch_number = $dispatchNumber;
            $item->dispatched_at = $dispatchedAt ? \Carbon\Carbon::parse($dispatchedAt) : now();
            $item->dispatched_by_id = $admin->id;
            $item->save();

            // Refresh items list for correct calculations
            $order->load('items');

            // Determine correct order status transition
            $hasPending = $order->items->where('status', 'pending_dispatch')->isNotEmpty();
            $hasDispatched = $order->items->where('status', 'dispatched')->isNotEmpty();
            $hasCancelled = $order->items->where('status', 'cancelled')->isNotEmpty();

            if (!$hasPending) {
                if ($hasDispatched && $hasCancelled) {
                    if ($order->status !== 'partially_dispatched_balance_cancelled') {
                        $this->statusService->transition($order, 'partially_dispatched_balance_cancelled', $admin, 'Some items dispatched, balance cancelled.');
                    }
                } else {
                    if ($order->status !== 'dispatched') {
                        $this->statusService->transition($order, 'dispatched', $admin, 'All items have been dispatched.');
                    }
                }
            } else {
                if ($order->status !== 'partially_dispatched') {
                    $this->statusService->transition($order, 'partially_dispatched', $admin, 'Some items have been dispatched.');
                }
            }

            return $order->fresh();
        });
    }

    /**
     * Cancel an order item. Restores stock and updates order totals.
     */
    public function cancelOrderItem(\App\Models\OrderItem $item, User $admin): Order
    {
        if ($item->status && $item->status !== 'pending_dispatch') {
            throw ValidationException::withMessages(['item' => 'Only pending dispatch items can be cancelled.']);
        }

        $order = $item->order;

        return DB::transaction(function () use ($item, $order, $admin) {
            // Mark item as cancelled
            $item->status = 'cancelled';
            $item->save();

            // Recalculate order totals based on non-cancelled items
            $activeItems = $order->items()->where('status', '!=', 'cancelled')->get();
            $subtotal = $activeItems->sum('line_subtotal');
            $gst_amount = $activeItems->sum('gst_amount');
            $total_amount = $subtotal + $gst_amount;

            $order->update([
                'subtotal' => $subtotal,
                'gst_amount' => $gst_amount,
                'total_amount' => $total_amount,
            ]);

            // Determine correct order status transition
            $hasPending = $order->items()->where('status', 'pending_dispatch')->exists();
            $hasDispatched = $order->items()->where('status', 'dispatched')->exists();
            $hasCancelled = $order->items()->where('status', 'cancelled')->exists();

            if (!$hasPending) {
                if ($hasDispatched) {
                    if ($hasCancelled) {
                        if ($order->status !== 'partially_dispatched_balance_cancelled') {
                            $this->statusService->transition($order, 'partially_dispatched_balance_cancelled', $admin, 'Some items dispatched, balance cancelled.');
                        }
                    } else {
                        if ($order->status !== 'dispatched') {
                            $this->statusService->transition($order, 'dispatched', $admin, 'All items dispatched or cancelled.');
                        }
                    }
                } else {
                    if ($order->status !== 'cancelled') {
                        $this->statusService->transition($order, 'cancelled', $admin, 'All items cancelled.');
                    }
                }
            } else {
                if ($hasDispatched) {
                    if ($order->status !== 'partially_dispatched') {
                        $this->statusService->transition($order, 'partially_dispatched', $admin, 'Item cancelled. Order is partially dispatched.');
                    }
                }
            }

            return $order->fresh();
        });
    }

    /**
     * Apply order scope filtering based on user permissions.
     */
    protected function applyOrderScope($query)
    {
        $user = auth()->user();
        if ($user && !$user->hasRole('super_admin')) {
            $hasManufactured = $user->hasPermissionTo('manage manufactured orders');
            $hasRetail = $user->hasPermissionTo('manage retail orders');

            if ($hasManufactured && !$hasRetail) {
                $query->whereHas('items.product', function ($q) {
                    $q->where('product_type', 'retail');
                });
            } elseif ($hasRetail && !$hasManufactured) {
                $query->whereHas('items.product', function ($q) {
                    $q->where('product_type', 'manufactured');
                });
            } elseif (!$hasManufactured && !$hasRetail) {
                $query->whereRaw('1 = 0');
            }
        }
    }
}
