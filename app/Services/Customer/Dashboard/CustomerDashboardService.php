<?php

namespace App\Services\Customer\Dashboard;

use App\Models\User;
use App\Models\Cart;
use App\Models\Order;
use App\Models\Product;
use App\Services\Credit\CreditStatusService;
use App\Services\Portal\ProductCatalogService;
use Illuminate\Support\Facades\DB;

class CustomerDashboardService
{
    protected CreditStatusService $creditStatusService;
    protected ProductCatalogService $productCatalogService;

    public function __construct(
        CreditStatusService $creditStatusService,
        ProductCatalogService $productCatalogService
    ) {
        $this->creditStatusService = $creditStatusService;
        $this->productCatalogService = $productCatalogService;
    }

    /**
     * Get the full customer dashboard payload.
     */
    public function getDashboard(User $user): array
    {
        $customer = $user->customer;
        if (!$customer) {
            return [];
        }

        // Load level relationship if not loaded
        if (!$customer->relationLoaded('level')) {
            $customer->load('level');
        }

        return [
            'customer' => [
                'id' => $customer->id,
                'customer_number' => $customer->customer_number,
                'company_name' => $customer->company_name,
                'contact_person' => $customer->contact_person,
                'level' => $customer->level ? $customer->level->name : null,
                'is_active' => (bool) $customer->is_active,
            ],
            'credit'             => $this->getCreditSummary($user),
            'cart'               => $this->getCartSummary($user),
            'orders'             => $this->getOrderSummary($user),
            'recent_orders'      => $this->getRecentOrders($user),
            'alerts'             => $this->getDashboardAlerts($user),
            'quick_actions'      => $this->getQuickActions($user),
            // Slider data
            'recent_products'    => $this->getRecommendedProducts($user),
            'popular_products'   => $this->getPopularProducts($user),
            'recent_purchases'   => $this->getRecentPurchases($user),
            // Legacy alias kept for older mobile clients
            'recommended_products' => $this->getRecommendedProducts($user),
        ];
    }

    /**
     * Get credit overview details.
     */
    public function getCreditSummary(User $user): array
    {
        $customer = $user->customer;
        if (!$customer) {
            return [];
        }

        $limit = (float) $customer->credit_limit;
        $outstanding = (float) $customer->outstanding_amount;
        $available = (float) $customer->available_credit;
        $overdue = (float) $customer->overdue_amount;

        $status = $this->creditStatusService->getStatus($customer);
        $risk = $this->creditStatusService->getRiskLevel($customer);

        // Utilization percentage: cap at 100% or allow higher if outstanding exceeds limit
        $utilizationPercentage = $limit > 0 ? min(100, round(($outstanding / $limit) * 100, 1)) : 0;

        return [
            'credit_limit' => $limit,
            'outstanding_amount' => $outstanding,
            'available_credit' => $available,
            'overdue_amount' => $overdue,
            'credit_hold' => (bool) $customer->credit_hold,
            'credit_hold_reason' => $customer->credit_hold_reason,
            'allow_credit_beyond_limit' => (bool) $customer->allow_credit_beyond_limit,
            'status' => $status,
            'risk_level' => $risk,
            'utilization_percentage' => $utilizationPercentage,
            'formatted_credit_limit' => $this->formatIndianCurrency($limit),
            'formatted_outstanding_amount' => $this->formatIndianCurrency($outstanding),
            'formatted_available_credit' => $this->formatIndianCurrency($available),
            'formatted_overdue_amount' => $this->formatIndianCurrency($overdue),
        ];
    }

    /**
     * Get summary of active cart items and totals.
     */
    public function getCartSummary(User $user): array
    {
        $cart = Cart::where('user_id', $user->id)
            ->where('status', 'active')
            ->first();

        if (!$cart) {
            return [
                'exists' => false,
                'items_count' => 0,
                'total_amount' => 0.0,
                'formatted_total_amount' => $this->formatIndianCurrency(0.0),
                'items' => [],
            ];
        }

        $cartItems = $cart->items()
            ->with(['product.primaryMedia', 'product.media', 'combination', 'unit'])
            ->get();

        $totalAmount = 0.0;
        $itemsData = [];

        foreach ($cartItems as $item) {
            $totalAmount += (float) $item->line_total;

            $product = $item->product;
            if (!$product) {
                continue;
            }

            $primaryImage = $product->primaryMedia ? $product->primaryMedia->file_path : null;
            if (!$primaryImage && $product->media->first()) {
                $primaryImage = $product->media->first()->file_path;
            }
            $imageUrl = $primaryImage
                ? (str_starts_with($primaryImage, 'http') ? $primaryImage : \Illuminate\Support\Facades\Storage::url($primaryImage))
                : 'https://images.unsplash.com/photo-1596755094514-f87e34085b2c?w=100';

            $itemsData[] = [
                'id' => $item->id,
                'title' => $product->title,
                'image_url' => $imageUrl,
                'quantity' => $item->quantity,
                'unit_name' => $item->unit ? $item->unit->short_code : 'Pcs',
                'line_total' => (float)$item->line_total,
                'formatted_line_total' => $this->formatIndianCurrency((float)$item->line_total),
            ];
        }

        return [
            'exists' => true,
            'cart_id' => $cart->id,
            'items_count' => $cartItems->count(),
            'total_amount' => $totalAmount,
            'formatted_total_amount' => $this->formatIndianCurrency($totalAmount),
            'items' => $itemsData,
        ];
    }

    /**
     * Get order counts and overall values using performance-focused grouped query.
     */
    public function getOrderSummary(User $user): array
    {
        $stats = DB::table('orders')
            ->where('user_id', $user->id)
            ->select('status', DB::raw('count(*) as count'), DB::raw('sum(total_amount) as total'))
            ->groupBy('status')
            ->get();

        $totalOrders = 0;
        $pendingCount = 0;
        $approvedCount = 0;
        $rejectedCount = 0;
        $dispatchedCount = 0;
        $totalValue = 0.0;

        foreach ($stats as $stat) {
            $count = (int) $stat->count;
            $total = (float) $stat->total;

            $totalOrders += $count;

            if (in_array($stat->status, ['submitted', 'under_review', 'pending_approval', 'pending_payment_verification'])) {
                $pendingCount += $count;
            } elseif ($stat->status === 'approved') {
                $approvedCount += $count;
            } elseif ($stat->status === 'rejected') {
                $rejectedCount += $count;
            } elseif ($stat->status === 'dispatched') {
                $dispatchedCount += $count;
            }

            if (!in_array($stat->status, ['rejected', 'cancelled'])) {
                $totalValue += $total;
            }
        }

        return [
            'total_orders' => $totalOrders,
            'pending_orders' => $pendingCount,
            'approved_orders' => $approvedCount,
            'rejected_orders' => $rejectedCount,
            'dispatched_orders' => $dispatchedCount,
            'total_order_value' => $totalValue,
            'formatted_total_order_value' => $this->formatIndianCurrency($totalValue),
        ];
    }

    /**
     * Get the latest 5 orders for dashboard lists.
     */
    public function getRecentOrders(User $user): array
    {
        $orders = Order::where('user_id', $user->id)
            ->latest()
            ->limit(6)
            ->withCount('items')
            ->get();

        return $orders->map(function (Order $order) {
            $badgeData = $order->status_badge ?? $this->getOrderStatusBadgeDetails($order->status);
            $paymentBadge = $this->getOrderStatusBadgeDetails($order->payment_status);

            return [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'status' => $badgeData,
                'payment_status' => $paymentBadge,
                'total_amount' => (float) $order->total_amount,
                'formatted_total_amount' => $this->formatIndianCurrency((float) $order->total_amount),
                'items_count' => $order->items_count,
                'submitted_at' => $order->submitted_at ? $order->submitted_at->toIso8601String() : null,
                'created_at' => $order->created_at->toIso8601String(),
            ];
        })->toArray();
    }

    /**
     * Generate prioritize alerts for dashboard alerts display.
     */
    public function getDashboardAlerts(User $user): array
    {
        $customer = $user->customer;
        if (!$customer) {
            return [];
        }

        $alerts = [];

        // 1. Credit Hold (High Priority)
        if ($customer->credit_hold) {
            $alerts[] = [
                'type' => 'danger',
                'title' => 'Account On Hold',
                'message' => 'Your account is currently on credit hold. ' . ($customer->credit_hold_reason ?: 'Please contact support to resolve this.'),
                'priority' => 10,
            ];
        }

        // 2. Over Limit (High Priority)
        $limit = (float) $customer->credit_limit;
        $outstanding = (float) $customer->outstanding_amount;
        if ($limit > 0 && $outstanding > $limit) {
            $alerts[] = [
                'type' => 'danger',
                'title' => 'Credit Limit Exceeded',
                'message' => 'Your outstanding balance (' . $this->formatIndianCurrency($outstanding) . ') exceeds your credit limit (' . $this->formatIndianCurrency($limit) . '). Please clear outstanding payments.',
                'priority' => 9,
            ];
        }

        // 3. Near Limit (Medium Priority)
        if ($limit > 0 && !$customer->credit_hold && $outstanding < $limit && $outstanding >= $limit * 0.85) {
            $alerts[] = [
                'type' => 'warning',
                'title' => 'Approaching Credit Limit',
                'message' => 'You have utilized over 85% of your credit limit. Remaining available credit is ' . $this->formatIndianCurrency($customer->available_credit) . '.',
                'priority' => 7,
            ];
        }

        // 4. Extended Privilege Alert (Info Priority)
        if ($customer->allow_credit_beyond_limit && !$customer->credit_hold) {
            $alerts[] = [
                'type' => 'info',
                'title' => 'Over-Limit Orders Enabled',
                'message' => 'Your account is authorized to place credit orders beyond your standard limit, subject to review.',
                'priority' => 5,
            ];
        }

        // 5. Rejected orders (High Priority)
        $rejectedOrdersCount = Order::where('user_id', $user->id)
            ->where('status', 'rejected')
            ->where('updated_at', '>=', now()->subDays(7))
            ->count();
        if ($rejectedOrdersCount > 0) {
            $alerts[] = [
                'type' => 'danger',
                'title' => 'Rejected Orders',
                'message' => "You have {$rejectedOrdersCount} order(s) rejected in the last 7 days. Please check order details.",
                'priority' => 8,
            ];
        }

        // 6. Pending Payment verification
        $pendingVerificationCount = Order::where('user_id', $user->id)
            ->where('status', 'pending_payment_verification')
            ->count();
        if ($pendingVerificationCount > 0) {
            $alerts[] = [
                'type' => 'warning',
                'title' => 'Receipt Verification Pending',
                'message' => "We are verifying payment receipts for {$pendingVerificationCount} order(s). We will process them shortly.",
                'priority' => 6,
            ];
        }

        // Sort by priority desc
        usort($alerts, fn($a, $b) => $b['priority'] <=> $a['priority']);

        return array_slice($alerts, 0, 5);
    }

    /**
     * Get quick action buttons with dynamic badge info.
     */
    public function getQuickActions(User $user): array
    {
        $cartSummary = $this->getCartSummary($user);

        return [
            [
                'id' => 'shop_catalog',
                'label' => 'Browse Catalog',
                'icon' => 'shopping_bag',
                'route' => 'customer.products.index',
                'badge' => null,
            ],
            [
                'id' => 'view_cart',
                'label' => 'View Active Cart',
                'icon' => 'shopping_cart',
                'route' => 'customer.cart.index',
                'badge' => $cartSummary['items_count'] > 0 ? (string) $cartSummary['items_count'] : null,
            ],
            [
                'id' => 'order_history',
                'label' => 'My Orders',
                'icon' => 'history',
                'route' => 'customer.orders.index',
                'badge' => null,
            ],
            [
                'id' => 'credit_ledger',
                'label' => 'Credit Statement',
                'icon' => 'receipt_long',
                'route' => 'customer.profile.show',
                'badge' => null,
            ],
        ];
    }

    /**
     * Get recommended / recent products — newest 10 active products.
     */
    public function getRecommendedProducts(User $user): array
    {
        $products = Product::where('is_active', true)
            ->latest()
            ->limit(10)
            ->with(['categories', 'media', 'primaryMedia', 'customerLevelPrices', 'units'])
            ->get();

        return $products->map(function (Product $product) use ($user) {
            return $this->productCatalogService->formatProductCard($product, $user);
        })->toArray();
    }

    /**
     * Get top 10 most purchased products (by total units sold across all orders).
     * Falls back to random active products if fewer than 10 eligible results.
     */
    public function getPopularProducts(User $user): array
    {
        $popularIds = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->whereNotIn('orders.status', ['rejected', 'cancelled'])
            ->select('order_items.product_id', DB::raw('SUM(order_items.quantity) as total_qty'))
            ->groupBy('order_items.product_id')
            ->orderByDesc('total_qty')
            ->limit(10)
            ->pluck('order_items.product_id')
            ->toArray();

        $products = collect();

        if (!empty($popularIds)) {
            $found = Product::where('is_active', true)
                ->whereIn('id', $popularIds)
                ->with(['categories', 'media', 'primaryMedia', 'customerLevelPrices', 'units'])
                ->get()
                ->sortBy(fn($p) => array_search($p->id, $popularIds))
                ->values();
            $products = $products->concat($found);
        }

        // Pad with random products if we have fewer than 10
        if ($products->count() < 10) {
            $excludeIds = $products->pluck('id')->toArray();
            $needed = 10 - $products->count();
            $extras = Product::where('is_active', true)
                ->whereNotIn('id', $excludeIds)
                ->with(['categories', 'media', 'primaryMedia', 'customerLevelPrices', 'units'])
                ->inRandomOrder()
                ->limit($needed)
                ->get();
            $products = $products->concat($extras);
        }

        return $products->map(function (Product $product) use ($user) {
            return $this->productCatalogService->formatProductCard($product, $user);
        })->toArray();
    }

    /**
     * Get up to 10 distinct products from this user's own order history,
     * ordered by most recently ordered, excluding rejected/cancelled orders.
     */
    public function getRecentPurchases(User $user): array
    {
        $productIds = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('orders.user_id', $user->id)
            ->whereNotIn('orders.status', ['rejected', 'cancelled'])
            ->select('order_items.product_id', DB::raw('MAX(orders.created_at) as last_ordered_at'))
            ->groupBy('order_items.product_id')
            ->orderByDesc('last_ordered_at')
            ->limit(10)
            ->pluck('order_items.product_id')
            ->toArray();

        if (empty($productIds)) {
            return [];
        }

        $products = Product::where('is_active', true)
            ->whereIn('id', $productIds)
            ->with(['categories', 'media', 'primaryMedia', 'customerLevelPrices', 'units'])
            ->get()
            ->sortBy(fn($p) => array_search($p->id, $productIds))
            ->values();

        return $products->map(function (Product $product) use ($user) {
            return $this->productCatalogService->formatProductCard($product, $user);
        })->toArray();
    }

    /**
     * Indian currency formatting helper (e.g. ₹5,00,000.00).
     */
    public function formatIndianCurrency(float $amount): string
    {
        $negative = $amount < 0;
        $amount = abs($amount);

        // Format to 2 decimal places
        $parts = explode('.', number_format($amount, 2, '.', ''));
        $num = $parts[0];
        $dec = $parts[1];

        // Format Indian number system grouping
        $lastThree = substr($num, -3);
        $rest = substr($num, 0, -3);
        if ($rest !== '') {
            $rest = preg_replace("/\B(?=(\d{2})+(?!\d))/", ",", $rest) . ",";
        }
        $formattedNum = $rest . $lastThree;

        $result = '₹' . $formattedNum . '.' . $dec;
        return $negative ? '-' . $result : $result;
    }

    /**
     * Fallback order status badge resolver for service.
     */
    protected function getOrderStatusBadgeDetails(string $status): array
    {
        $statusMap = [
            'submitted' => ['label' => 'Submitted', 'type' => 'info', 'badge' => 'info'],
            'under_review' => ['label' => 'Under Review', 'type' => 'warning', 'badge' => 'warning'],
            'pending_approval' => ['label' => 'Pending Approval', 'type' => 'warning', 'badge' => 'warning'],
            'pending_payment_verification' => ['label' => 'Pending Verification', 'type' => 'warning', 'badge' => 'warning'],
            'approved' => ['label' => 'Approved', 'type' => 'success', 'badge' => 'success'],
            'dispatched' => ['label' => 'Dispatched', 'type' => 'success', 'badge' => 'success'],
            'delivered' => ['label' => 'Delivered', 'type' => 'success', 'badge' => 'success'],
            'rejected' => ['label' => 'Rejected', 'type' => 'danger', 'badge' => 'danger'],
            'cancelled' => ['label' => 'Cancelled', 'type' => 'secondary', 'badge' => 'secondary'],

            'not_required' => ['label' => 'Not Required', 'type' => 'secondary', 'badge' => 'secondary'],
            'pending_verification' => ['label' => 'Pending Verification', 'type' => 'warning', 'badge' => 'warning'],
            'verified' => ['label' => 'Verified', 'type' => 'success', 'badge' => 'success'],
            // for credit
            'within_limit' => ['label' => 'Within Limit', 'type' => 'success', 'badge' => 'success'],
            'over_limit_allowed' => ['label' => 'Over Limit Allowed', 'type' => 'warning', 'badge' => 'warning'],
        ];

        return $statusMap[$status] ?? ['label' => ucfirst(str_replace('_', ' ', $status)), 'type' => 'secondary', 'badge' => 'secondary'];
    }
}
