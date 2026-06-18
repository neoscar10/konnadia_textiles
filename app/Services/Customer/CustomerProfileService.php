<?php

namespace App\Services\Customer;

use App\Models\Customer;
use App\Services\Credit\CreditStatusService;
use App\Services\Customer\CustomerActivityLogService;

class CustomerProfileService
{
    protected CreditStatusService $creditStatusService;
    protected CustomerActivityLogService $activityLogService;

    public function __construct(
        CreditStatusService $creditStatusService,
        CustomerActivityLogService $activityLogService
    ) {
        $this->creditStatusService = $creditStatusService;
        $this->activityLogService = $activityLogService;
    }

    /**
     * Get a full aggregated profile of the customer.
     */
    public function getAdminCustomerProfile(Customer $customer): array
    {
        $customer->load(['level', 'user']);

        return [
            'customer' => $customer,
            'user' => $customer->user,
            'business' => [
                'gst_number' => $customer->gst_number,
                'customer_level' => $customer->level?->name ?? 'N/A',
                'default_discount' => $customer->level?->discount_percentage ?? 0,
                'billing_address' => $customer->billing_address ?? 'Not provided',
            ],
            'configuration' => [
                'credit_limit' => (float) $customer->credit_limit,
                'allow_credit_beyond_limit' => (bool) $customer->allow_credit_beyond_limit,
                'credit_hold' => (bool) $customer->credit_hold,
                'credit_hold_reason' => $customer->credit_hold_reason ?? 'N/A',
                'is_active' => (bool) $customer->is_active,
            ],
            'credit' => $this->getCreditSnapshot($customer),
            'stats' => $this->getCustomerStats($customer),
            'recent_orders' => $this->getRecentOrders($customer, 5),
            'recent_activity_logs' => $this->activityLogService->formatCollection(
                $this->activityLogService->recent($customer, 5)
            ),
        ];
    }

    /**
     * Get recent orders for the customer.
     */
    public function getRecentOrders(Customer $customer, int $limit = 5): array
    {
        return $customer->orders()
            ->orderBy('created_at', 'desc')
            ->take($limit)
            ->get()
            ->map(function ($order) {
                return [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'created_at' => $order->created_at ? $order->created_at->format('d M Y') : 'N/A',
                    'status' => $order->status, // pending, approved, rejected, dispatched, delivered, cancelled
                    'checkout_method' => $order->checkout_method ?? 'N/A', // credit, cod, etc.
                    'payment_status' => $order->payment_status ?? 'N/A', // pending, paid, etc.
                    'items_count' => $order->items()->count(),
                    'total_amount' => (float) $order->total_amount,
                ];
            })
            ->toArray();
    }

    /**
     * Get a credit snapshot for the customer.
     */
    public function getCreditSnapshot(Customer $customer): array
    {
        $statusInfo = $this->creditStatusService->getStatus($customer);
        $riskInfo = $this->creditStatusService->getRiskLevel($customer);

        return [
            'credit_limit' => (float) $customer->credit_limit,
            'outstanding_amount' => (float) $customer->outstanding_amount,
            'available_credit' => (float) $customer->available_credit,
            'overdue_amount' => (float) $customer->overdue_amount,
            'status_value' => $statusInfo['value'],
            'status_label' => $statusInfo['label'],
            'status_badge' => $statusInfo['badge'],
            'status_message' => $statusInfo['message'],
            'risk_level' => $riskInfo['level'],
            'risk_color' => $riskInfo['color'],
        ];
    }

    /**
     * Get aggregate statistics for the customer.
     */
    public function getCustomerStats(Customer $customer): array
    {
        $orders = $customer->orders()->whereNotIn('status', ['cancelled', 'rejected'])->get();

        return [
            'total_orders_count' => $orders->count(),
            'total_spent' => (float) $orders->sum('total_amount'),
        ];
    }
}
