<?php

namespace App\Http\Controllers\Api/V1/Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Customer;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class AdminReportsController extends Controller
{
    /**
     * Get sales & revenue report metrics.
     */
    public function sales(Request $request): JsonResponse
    {
        $dateFrom = $request->query('date_from', now()->subDays(30)->toDateString());
        $dateTo = $request->query('date_to', now()->toDateString());

        $ordersQuery = Order::whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']);

        $totalSales = (float) (clone $ordersQuery)->where('status', 'approved')->sum('total_amount');
        $totalOrders = (clone $ordersQuery)->count();
        $approvedOrders = (clone $ordersQuery)->where('status', 'approved')->count();

        $dailySales = (clone $ordersQuery)
            ->where('status', 'approved')
            ->selectRaw('DATE(created_at) as date, COUNT(*) as orders_count, SUM(total_amount) as revenue')
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'summary' => [
                    'total_sales' => $totalSales,
                    'total_orders' => $totalOrders,
                    'approved_orders' => $approvedOrders,
                    'date_from' => $dateFrom,
                    'date_to' => $dateTo,
                ],
                'daily_sales' => $dailySales,
            ]
        ]);
    }

    /**
     * Get customer purchasing performance report.
     */
    public function customers(): JsonResponse
    {
        $topCustomers = Customer::withCount(['orders' => function ($q) {
            $q->where('status', 'approved');
        }])
        ->orderBy('total_spent', 'desc')
        ->take(10)
        ->get()
        ->map(function ($cust) {
            return [
                'id' => $cust->id,
                'company_name' => $cust->company_name,
                'customer_number' => $cust->customer_number,
                'contact_person' => $cust->contact_person,
                'total_orders' => $cust->orders_count,
                'total_spent' => (float) $cust->total_spent,
                'outstanding_amount' => (float) $cust->outstanding_amount,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'top_customers' => $topCustomers,
            ]
        ]);
    }

    /**
     * Get inventory stock valuation report.
     */
    public function inventory(): JsonResponse
    {
        $totalProducts = Product::count();
        $activeProducts = Product::where('is_active', true)->count();
        $totalStockUnits = (int) Product::sum('stock_quantity');
        $totalStockValue = (float) Product::selectRaw('SUM(stock_quantity * base_price) as total_val')->value('total_val');

        $lowStockProducts = Product::where('is_active', true)
            ->where('stock_quantity', '<=', 10)
            ->get(['id', 'title', 'sku', 'stock_quantity', 'base_price']);

        return response()->json([
            'success' => true,
            'data' => [
                'total_products' => $totalProducts,
                'active_products' => $activeProducts,
                'total_stock_units' => $totalStockUnits,
                'total_stock_value' => $totalStockValue,
                'low_stock_products' => $lowStockProducts,
            ]
        ]);
    }
}
