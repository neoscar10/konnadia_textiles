<?php

namespace App\Services\Admin\Dashboard;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Order;
use App\Models\ProductCombination;
use App\Support\MoneyFormatter;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AdminDashboardService
{
    public function getDashboard(array $filters = []): array
    {
        $dateRange = $filters['date_range'] ?? '30_days';

        return [
            'kpis' => $this->getKpiMetrics($filters),
            'orders' => $this->getOrderSummary($filters),
            'customers' => $this->getCustomerSummary($filters),
            'products' => $this->getProductSummary($filters),
            'credit' => $this->getCreditSummary($filters),
            'inventory' => $this->getInventorySummary($filters),
            'pending_approvals' => $this->getPendingApprovals($filters),
            'recent_customers' => $this->getRecentCustomers(5),
            'recent_orders' => $this->getRecentOrders(5),
            'alerts' => $this->getAlerts($filters),
            'quick_actions' => $this->getQuickActions(),
            'metadata' => [
                'date_range' => $dateRange,
                'generated_at' => now()->toIso8601String(),
                'currency' => 'INR',
            ],
        ];
    }

    public function getKpiMetrics(array $filters = []): array
    {
        $totalCustomers = Customer::count();
        $activeCustomers = Customer::where('is_active', true)->count();
        $totalProducts = Product::count();
        $activeProducts = Product::where('is_active', true)->count();
        $pendingOrders = Order::whereIn('status', ['submitted', 'under_review', 'pending_approval', 'pending_payment_verification'])->count();
        $approvedOrders = Order::where('status', 'approved')->count();
        $creditExposure = (float) Customer::sum('outstanding_amount');
        $lowStockItems = $this->countLowStockItems(10);

        return [
            [
                'key' => 'total_customers',
                'label' => 'Total Customers',
                'value' => $totalCustomers,
                'formatted_value' => number_format($totalCustomers),
                'icon' => 'groups',
                'trend' => null,
                'status' => ['value' => 'normal', 'label' => 'Active Network'],
                'route' => '/admin/customers',
            ],
            [
                'key' => 'active_customers',
                'label' => 'Active Customers',
                'value' => $activeCustomers,
                'formatted_value' => number_format($activeCustomers),
                'icon' => 'person_check',
                'trend' => null,
                'status' => ['value' => 'normal', 'label' => 'Online'],
                'route' => '/admin/customers',
            ],
            [
                'key' => 'total_products',
                'label' => 'Total Products',
                'value' => $totalProducts,
                'formatted_value' => number_format($totalProducts),
                'icon' => 'inventory_2',
                'trend' => null,
                'status' => ['value' => 'normal', 'label' => 'Catalog'],
                'route' => '/admin/products',
            ],
            [
                'key' => 'pending_orders',
                'label' => 'Pending Orders',
                'value' => $pendingOrders,
                'formatted_value' => number_format($pendingOrders),
                'icon' => 'pending_actions',
                'trend' => null,
                'status' => $pendingOrders > 0 ? ['value' => 'warning', 'label' => 'Action Needed'] : ['value' => 'success', 'label' => 'Cleared'],
                'route' => '/admin/orders?status=pending',
            ],
            [
                'key' => 'approved_orders',
                'label' => 'Approved Orders',
                'value' => $approvedOrders,
                'formatted_value' => number_format($approvedOrders),
                'icon' => 'check_circle',
                'trend' => null,
                'status' => ['value' => 'success', 'label' => 'On Track'],
                'route' => '/admin/orders?status=approved',
            ],
            [
                'key' => 'credit_exposure',
                'label' => 'Credit Exposure',
                'value' => $creditExposure,
                'formatted_value' => MoneyFormatter::compactInr($creditExposure),
                'icon' => 'account_balance_wallet',
                'trend' => null,
                'status' => $creditExposure > 0 ? ['value' => 'info', 'label' => 'Outstanding'] : ['value' => 'success', 'label' => 'Clear'],
                'route' => '/admin/credit-management',
            ],
            [
                'key' => 'low_stock_items',
                'label' => 'Low Stock Items',
                'value' => $lowStockItems,
                'formatted_value' => number_format($lowStockItems),
                'icon' => 'warning',
                'trend' => null,
                'status' => $lowStockItems > 0 ? ['value' => 'warning', 'label' => 'Reorder'] : ['value' => 'success', 'label' => 'Healthy'],
                'route' => '/admin/inventory',
            ],
        ];
    }

    public function getOrderSummary(array $filters = []): array
    {
        $query = Order::query();

        if (!empty($filters['date_range'])) {
            $dateRange = $this->getDateRangeFromFilter($filters['date_range']);
            $query->whereBetween('created_at', $dateRange);
        }

        $byStatus = $query->groupBy('status')
            ->selectRaw('status, COUNT(*) as count, SUM(total_amount) as total_value')
            ->get()
            ->keyBy('status')
            ->toArray();

        return [
            'total_orders' => Order::count(),
            'by_status' => $byStatus,
            'total_value' => (float) Order::sum('total_amount'),
            'formatted_total_value' => MoneyFormatter::compactInr(Order::sum('total_amount') ?? 0),
        ];
    }

    public function getCustomerSummary(array $filters = []): array
    {
        $query = Customer::query();

        if (!empty($filters['date_range'])) {
            $dateRange = $this->getDateRangeFromFilter($filters['date_range']);
            $query->whereBetween('created_at', $dateRange);
        }

        return [
            'total_customers' => Customer::count(),
            'active_customers' => Customer::where('is_active', true)->count(),
            'inactive_customers' => Customer::where('is_active', false)->count(),
            'new_customers_period' => $query->count(),
            'by_level' => Customer::groupBy('customer_level_id')
                ->selectRaw('customer_level_id, COUNT(*) as count')
                ->get()
                ->toArray(),
        ];
    }

    public function getProductSummary(array $filters = []): array
    {
        return [
            'total_products' => Product::count(),
            'active_products' => Product::where('is_active', true)->count(),
            'inactive_products' => Product::where('is_active', false)->count(),
            'products_with_variants' => Product::whereHas('variationGroups')->count(),
            'products_with_combinations' => Product::whereHas('combinations')->count(),
        ];
    }

    public function getCreditSummary(array $filters = []): array
    {
        $totalLimit = (float) Customer::sum('credit_limit');
        $totalOutstanding = (float) Customer::sum('outstanding_amount');
        $availableCredit = $totalLimit - $totalOutstanding;

        $overLimit = Customer::whereRaw('outstanding_amount > credit_limit')->count();
        $nearLimit = Customer::whereRaw('outstanding_amount >= credit_limit * 0.85')
            ->whereRaw('outstanding_amount <= credit_limit')
            ->count();

        return [
            'total_credit_limit' => $totalLimit,
            'formatted_total_limit' => MoneyFormatter::compactInr($totalLimit),
            'total_outstanding' => $totalOutstanding,
            'formatted_outstanding' => MoneyFormatter::compactInr($totalOutstanding),
            'available_credit' => $availableCredit,
            'formatted_available' => MoneyFormatter::compactInr($availableCredit),
            'utilization_percent' => $totalLimit > 0 ? round(($totalOutstanding / $totalLimit) * 100, 1) : 0,
            'customers_over_limit' => $overLimit,
            'customers_near_limit' => $nearLimit,
            'on_hold_count' => Customer::where('credit_hold', true)->count(),
        ];
    }

    public function getInventorySummary(array $filters = []): array
    {
        $threshold = $filters['low_stock_threshold'] ?? 10;
        $lowStockCount = $this->countLowStockItems($threshold);

        return [
            'low_stock_threshold' => $threshold,
            'low_stock_count' => $lowStockCount,
            'total_stock_value' => (float) Product::sum('stock_quantity'),
            'products_out_of_stock' => Product::where('stock_quantity', 0)->count(),
        ];
    }

    public function getPendingApprovals(array $filters = []): array
    {
        $pendingStatuses = ['submitted', 'under_review', 'pending_approval', 'pending_payment_verification'];

        $query = Order::whereIn('status', $pendingStatuses);

        if (!empty($filters['date_range'])) {
            $dateRange = $this->getDateRangeFromFilter($filters['date_range']);
            $query->whereBetween('created_at', $dateRange);
        }

        $orders = (clone $query)->with('customer')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(fn($order) => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'customer_name' => $order->customer?->company_name ?? 'Unknown',
                'customer_id' => $order->customer_id,
                'status' => $order->status,
                'total_amount' => $order->total_amount,
                'formatted_amount' => MoneyFormatter::compactInr($order->total_amount),
                'created_at' => $order->created_at,
                'formatted_date' => $order->created_at?->format('d M Y') ?? 'N/A',
            ])
            ->toArray();

        return [
            'count' => $query->count(),
            'orders' => $orders,
        ];
    }

    public function getRecentCustomers(int $limit = 5): array
    {
        return Customer::orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(fn($customer) => [
                'id' => $customer->id,
                'company_name' => $customer->company_name,
                'customer_number' => $customer->customer_number,
                'contact_person' => $customer->contact_person_name ?? 'N/A',
                'contact_number' => $customer->contact_number ?? 'N/A',
                'is_active' => $customer->is_active,
                'status' => $customer->is_active ? 'Active' : 'Inactive',
                'level_name' => $customer->level?->name ?? 'N/A',
                'outstanding_credit' => $customer->outstanding_amount,
                'credit_limit' => $customer->credit_limit,
                'initials' => strtoupper(substr($customer->company_name, 0, 2)),
            ])
            ->toArray();
    }

    public function getRecentOrders(int $limit = 5): array
    {
        return Order::with('customer')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(fn($order) => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'customer_name' => $order->customer?->company_name ?? 'Unknown',
                'customer_id' => $order->customer_id,
                'status' => $order->status,
                'status_label' => ucfirst(str_replace('_', ' ', $order->status)),
                'total_amount' => $order->total_amount,
                'formatted_amount' => MoneyFormatter::compactInr($order->total_amount),
                'created_at' => $order->created_at,
                'formatted_date' => $order->created_at?->format('d M Y, h:i A') ?? 'N/A',
            ])
            ->toArray();
    }

    public function getAlerts(array $filters = []): array
    {
        $alerts = [];

        // Missing GST configuration alert
        $productsWithoutGst = Product::where('is_active', true)
            ->whereNull('gst_percentage')
            ->count();

        if ($productsWithoutGst > 0) {
            $alerts[] = [
                'id' => 'missing_gst',
                'type' => 'warning',
                'icon' => 'warning',
                'title' => 'Products Missing GST',
                'message' => "{$productsWithoutGst} active product(s) are missing GST configuration.",
                'action_label' => 'Review Products',
                'action_route' => '/admin/products',
                'severity' => 'warning',
            ];
        }

        // Customers over credit limit
        $overLimitCount = Customer::whereRaw('outstanding_amount > credit_limit')->count();
        if ($overLimitCount > 0) {
            $alerts[] = [
                'id' => 'over_credit_limit',
                'type' => 'danger',
                'icon' => 'error',
                'title' => 'Customers Over Credit Limit',
                'message' => "{$overLimitCount} customer(s) have exceeded their credit limit.",
                'action_label' => 'Review Credit',
                'action_route' => '/admin/credit-management?credit_status=over_limit',
                'severity' => 'danger',
            ];
        }

        // Low stock alert
        $lowStockCount = $this->countLowStockItems(10);
        if ($lowStockCount > 0) {
            $alerts[] = [
                'id' => 'low_stock',
                'type' => 'warning',
                'icon' => 'warning',
                'title' => 'Low Stock Items',
                'message' => "{$lowStockCount} product(s) have low stock levels.",
                'action_label' => 'View Inventory',
                'action_route' => '/admin/inventory',
                'severity' => 'warning',
            ];
        }

        // Pending orders waiting for approval
        $pendingCount = Order::whereIn('status', ['submitted', 'under_review', 'pending_approval'])->count();
        if ($pendingCount > 0) {
            $alerts[] = [
                'id' => 'pending_approvals',
                'type' => 'info',
                'icon' => 'info',
                'title' => 'Pending Order Approvals',
                'message' => "{$pendingCount} order(s) awaiting approval.",
                'action_label' => 'Review Orders',
                'action_route' => '/admin/orders?status=pending',
                'severity' => 'info',
            ];
        }

        return array_slice($alerts, 0, 5);
    }

    public function getQuickActions(): array
    {
        return [
            [
                'icon' => 'person_add',
                'label' => 'Add Customer',
                'route' => '/admin/customers',
                'action_type' => 'navigate',
            ],
            [
                'icon' => 'add_circle',
                'label' => 'Add Product',
                'route' => '/admin/products',
                'action_type' => 'navigate',
            ],
            [
                'icon' => 'pending_actions',
                'label' => 'Review Orders',
                'route' => '/admin/orders?status=pending',
                'action_type' => 'navigate',
            ],
            [
                'icon' => 'account_balance_wallet',
                'label' => 'Credit Management',
                'route' => '/admin/credit-management',
                'action_type' => 'navigate',
            ],
            [
                'icon' => 'inventory_2',
                'label' => 'View Inventory',
                'route' => '/admin/inventory',
                'action_type' => 'navigate',
            ],
            [
                'icon' => 'download',
                'label' => 'Export Reports',
                'route' => '/admin/reports',
                'action_type' => 'navigate',
            ],
        ];
    }

    /**
     * Count products/combinations with stock below threshold.
     */
    private function countLowStockItems(int $threshold): int
    {
        // Check for combination-level stock first
        $combinationsWithVariants = Product::whereHas('combinations')->count();

        if ($combinationsWithVariants > 0) {
            // Count low-stock combinations
            return ProductCombination::where('is_active', true)
                ->where('stock_quantity', '<', $threshold)
                ->count();
        }

        // Fall back to product-level stock
        return Product::where('is_active', true)
            ->where('stock_quantity', '<', $threshold)
            ->count();
    }

    /**
     * Get date range from filter string.
     */
    private function getDateRangeFromFilter(string $filter): array
    {
        $now = Carbon::now();

        return match ($filter) {
            'today' => [$now->clone()->startOfDay(), $now->clone()->endOfDay()],
            '7_days' => [$now->clone()->subDays(7), $now],
            '30_days' => [$now->clone()->subDays(30), $now],
            '90_days' => [$now->clone()->subDays(90), $now],
            'this_month' => [$now->clone()->startOfMonth(), $now->clone()->endOfMonth()],
            'this_year' => [$now->clone()->startOfYear(), $now->clone()->endOfYear()],
            'all_time' => [Carbon::parse('1970-01-01'), $now],
            default => [$now->clone()->subDays(30), $now],
        };
    }
}
