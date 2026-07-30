<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\Dashboard\AdminDashboardService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AdminDashboardAnalyticsController extends Controller
{
    /**
     * Get admin dashboard KPIs, charts, orders summary, credit exposure, and alerts.
     */
    public function show(Request $request, AdminDashboardService $dashboardService): JsonResponse
    {
        $filters = [
            'date_range' => $request->query('date_range', '30_days'),
        ];

        $dashboard = $dashboardService->getDashboard($filters);

        return response()->json([
            'success' => true,
            'data' => $dashboard,
        ]);
    }
}
