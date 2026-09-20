<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Labor;
use App\Models\JobLaborAllocation;
use App\Models\Task;
use App\Http\Resources\Api\V1\AdminLaborResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AdminWageController extends Controller
{
    /**
     * Get aggregate payroll summary metrics across all workers and date ranges.
     */
    public function summary(Request $request): JsonResponse
    {
        $preset = $request->query('preset');
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');

        if ($preset === 'today') {
            $dateFrom = Carbon::now()->startOfDay()->format('Y-m-d H:i:s');
            $dateTo = Carbon::now()->endOfDay()->format('Y-m-d H:i:s');
        } elseif ($preset === 'this_week') {
            $dateFrom = Carbon::now()->startOfWeek()->format('Y-m-d H:i:s');
            $dateTo = Carbon::now()->endOfWeek()->format('Y-m-d H:i:s');
        } elseif ($preset === 'this_month') {
            $dateFrom = Carbon::now()->startOfMonth()->format('Y-m-d H:i:s');
            $dateTo = Carbon::now()->endOfMonth()->format('Y-m-d H:i:s');
        } elseif ($preset === 'last_month') {
            $dateFrom = Carbon::now()->subMonth()->startOfMonth()->format('Y-m-d H:i:s');
            $dateTo = Carbon::now()->subMonth()->endOfMonth()->format('Y-m-d H:i:s');
        } elseif ($preset === 'this_year') {
            $dateFrom = Carbon::now()->startOfYear()->format('Y-m-d H:i:s');
            $dateTo = Carbon::now()->endOfYear()->format('Y-m-d H:i:s');
        }

        $allocationQuery = JobLaborAllocation::query();

        if ($dateFrom) {
            $allocationQuery->where('created_at', '>=', $dateFrom);
        }
        if ($dateTo) {
            $allocationQuery->where('created_at', '<=', $dateTo);
        }

        $totalPieceRateWages = (float) $allocationQuery->sum('calculated_wage');
        $totalPiecesProcessed = (int) $allocationQuery->sum('quantity_processed');

        $activeLaborers = Labor::where('status', true)->count();
        $inactiveLaborers = Labor::where('status', false)->count();

        $monthlySalaryLaborers = Labor::where('status', true)->where('payment_method', 'monthly_salary')->count();
        $jobWorkLaborers = Labor::where('status', true)->where('payment_method', 'job_work')->count();

        $totalMonthlySalaryObligations = (float) Labor::where('status', true)
            ->where('payment_method', 'monthly_salary')
            ->sum('monthly_salary');

        $totalGrossPayroll = $totalPieceRateWages + $totalMonthlySalaryObligations;

        return response()->json([
            'success' => true,
            'message' => 'Payroll summary statistics retrieved successfully.',
            'data' => [
                'period' => [
                    'preset' => $preset ?: 'custom_or_all_time',
                    'date_from' => $dateFrom,
                    'date_to' => $dateTo,
                ],
                'labor_counts' => [
                    'total' => Labor::count(),
                    'active' => $activeLaborers,
                    'inactive' => $inactiveLaborers,
                    'monthly_salary_workers' => $monthlySalaryLaborers,
                    'job_work_workers' => $jobWorkLaborers,
                ],
                'financials' => [
                    'total_pieces_processed' => $totalPiecesProcessed,
                    'total_piece_rate_wages_earned' => $totalPieceRateWages,
                    'total_monthly_salary_obligations' => $totalMonthlySalaryObligations,
                    'total_gross_payroll' => $totalGrossPayroll,
                    'formatted_piece_rate_wages' => '₹' . number_format($totalPieceRateWages, 2),
                    'formatted_monthly_salary_obligations' => '₹' . number_format($totalMonthlySalaryObligations, 2),
                    'formatted_total_gross_payroll' => '₹' . number_format($totalGrossPayroll, 2),
                ],
            ],
        ]);
    }

    /**
     * List worker wage reports with date period filtering, search, and pagination.
     */
    public function index(Request $request): JsonResponse
    {
        $preset = $request->query('preset');
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');

        if ($preset === 'today') {
            $dateFrom = Carbon::now()->startOfDay()->format('Y-m-d H:i:s');
            $dateTo = Carbon::now()->endOfDay()->format('Y-m-d H:i:s');
        } elseif ($preset === 'this_week') {
            $dateFrom = Carbon::now()->startOfWeek()->format('Y-m-d H:i:s');
            $dateTo = Carbon::now()->endOfWeek()->format('Y-m-d H:i:s');
        } elseif ($preset === 'this_month') {
            $dateFrom = Carbon::now()->startOfMonth()->format('Y-m-d H:i:s');
            $dateTo = Carbon::now()->endOfMonth()->format('Y-m-d H:i:s');
        } elseif ($preset === 'last_month') {
            $dateFrom = Carbon::now()->subMonth()->startOfMonth()->format('Y-m-d H:i:s');
            $dateTo = Carbon::now()->subMonth()->endOfMonth()->format('Y-m-d H:i:s');
        } elseif ($preset === 'this_year') {
            $dateFrom = Carbon::now()->startOfYear()->format('Y-m-d H:i:s');
            $dateTo = Carbon::now()->endOfYear()->format('Y-m-d H:i:s');
        }

        $laborQuery = Labor::with('tasks');

        // Search worker
        if ($request->filled('search')) {
            $search = trim($request->query('search'));
            $laborQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('mobile_number', 'like', "%{$search}%");
            });
        }

        // Filter payment method
        if ($request->filled('payment_method')) {
            $laborQuery->where('payment_method', $request->query('payment_method'));
        }

        // Filter status
        if ($request->has('status') && $request->query('status') !== '') {
            $statusVal = $request->query('status');
            if ($statusVal === 'active' || $statusVal === '1' || $statusVal === 'true' || $statusVal === 1) {
                $laborQuery->where('status', true);
            } elseif ($statusVal === 'inactive' || $statusVal === '0' || $statusVal === 'false' || $statusVal === 0) {
                $laborQuery->where('status', false);
            }
        }

        $labors = $laborQuery->orderBy('name')->get();

        $workerReports = $labors->map(function ($worker) use ($dateFrom, $dateTo) {
            $allocQuery = JobLaborAllocation::where('labor_id', $worker->id);

            if ($dateFrom) {
                $allocQuery->where('created_at', '>=', $dateFrom);
            }
            if ($dateTo) {
                $allocQuery->where('created_at', '<=', $dateTo);
            }

            $allocations = $allocQuery->get();
            $piecesProcessed = (int) $allocations->sum('quantity_processed');
            $pieceRateWages = (float) $allocations->sum('calculated_wage');
            $monthlySalary = $worker->payment_method === 'monthly_salary' ? (float) ($worker->monthly_salary ?? 0) : 0.00;
            $totalPayable = $pieceRateWages + $monthlySalary;

            return [
                'worker' => new AdminLaborResource($worker),
                'allocations_count' => $allocations->count(),
                'pieces_processed' => $piecesProcessed,
                'piece_rate_wages_earned' => $pieceRateWages,
                'monthly_salary' => $monthlySalary,
                'total_payable' => $totalPayable,
                'formatted_piece_rate_wages' => '₹' . number_format($pieceRateWages, 2),
                'formatted_monthly_salary' => '₹' . number_format($monthlySalary, 2),
                'formatted_total_payable' => '₹' . number_format($totalPayable, 2),
            ];
        });

        // Compute summary metrics across returned reports
        $totalJobWorkWages = $workerReports->sum('piece_rate_wages_earned');
        $totalMonthlySalaries = $workerReports->sum('monthly_salary');
        $totalPieces = $workerReports->sum('pieces_processed');
        $totalGrossPayable = $totalJobWorkWages + $totalMonthlySalaries;

        $perPage = (int) $request->query('per_page', 15);
        $page = (int) $request->query('page', 1);
        $totalItems = $workerReports->count();

        if ($request->query('paginate') === 'true' || $request->has('page')) {
            $pagedData = $workerReports->slice(($page - 1) * $perPage, $perPage)->values();

            return response()->json([
                'success' => true,
                'summary' => [
                    'total_workers' => $totalItems,
                    'total_pieces_processed' => $totalPieces,
                    'total_piece_rate_wages' => $totalJobWorkWages,
                    'total_monthly_salaries' => $totalMonthlySalaries,
                    'total_gross_payable' => $totalGrossPayable,
                    'formatted_total_gross_payable' => '₹' . number_format($totalGrossPayable, 2),
                ],
                'data' => $pagedData,
                'pagination' => [
                    'total' => $totalItems,
                    'count' => $pagedData->count(),
                    'per_page' => $perPage,
                    'current_page' => $page,
                    'total_pages' => (int) ceil($totalItems / $perPage),
                ],
            ]);
        }

        return response()->json([
            'success' => true,
            'summary' => [
                'total_workers' => $totalItems,
                'total_pieces_processed' => $totalPieces,
                'total_piece_rate_wages' => $totalJobWorkWages,
                'total_monthly_salaries' => $totalMonthlySalaries,
                'total_gross_payable' => $totalGrossPayable,
                'formatted_total_gross_payable' => '₹' . number_format($totalGrossPayable, 2),
            ],
            'data' => $workerReports,
        ]);
    }

    /**
     * Get detailed wage breakdown and itemized allocations for a specific worker.
     */
    public function workerWages(Request $request, int $laborId): JsonResponse
    {
        $worker = Labor::with('tasks')->findOrFail($laborId);

        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');
        $preset = $request->query('preset');

        if ($preset === 'this_month') {
            $dateFrom = Carbon::now()->startOfMonth()->format('Y-m-d H:i:s');
            $dateTo = Carbon::now()->endOfMonth()->format('Y-m-d H:i:s');
        } elseif ($preset === 'last_month') {
            $dateFrom = Carbon::now()->subMonth()->startOfMonth()->format('Y-m-d H:i:s');
            $dateTo = Carbon::now()->subMonth()->endOfMonth()->format('Y-m-d H:i:s');
        }

        $query = JobLaborAllocation::where('labor_id', $laborId)
            ->with(['task', 'manufacturingProduct', 'pattern', 'productionJob']);

        if ($dateFrom) {
            $query->where('created_at', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->where('created_at', '<=', $dateTo);
        }

        $allAllocations = (clone $query)->get();

        $totalPieces = (int) $allAllocations->sum('quantity_processed');
        $pieceRateEarnings = (float) $allAllocations->sum('calculated_wage');
        $monthlySalary = $worker->payment_method === 'monthly_salary' ? (float) ($worker->monthly_salary ?? 0) : 0.00;
        $totalEarnings = $pieceRateEarnings + $monthlySalary;

        $perPage = (int) $request->query('per_page', 15);
        $paginator = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'worker' => new AdminLaborResource($worker),
            'earnings_summary' => [
                'total_pieces_processed' => $totalPieces,
                'piece_rate_earnings' => $pieceRateEarnings,
                'monthly_salary' => $monthlySalary,
                'total_earnings' => $totalEarnings,
                'formatted_piece_rate_earnings' => '₹' . number_format($pieceRateEarnings, 2),
                'formatted_monthly_salary' => '₹' . number_format($monthlySalary, 2),
                'formatted_total_earnings' => '₹' . number_format($totalEarnings, 2),
            ],
            'data' => $paginator->getCollection()->map(function ($alloc) {
                return [
                    'id' => $alloc->id,
                    'job_code' => $alloc->job_id,
                    'production_batch_id' => $alloc->production_batch_id,
                    'task' => $alloc->task ? [
                        'id' => $alloc->task->id,
                        'name' => $alloc->task->name,
                        'code' => $alloc->task->code,
                    ] : null,
                    'manufacturing_product' => $alloc->manufacturingProduct ? [
                        'id' => $alloc->manufacturingProduct->id,
                        'title' => $alloc->manufacturingProduct->title ?? $alloc->manufacturingProduct->name,
                        'product_code' => $alloc->manufacturingProduct->product_code ?? $alloc->manufacturingProduct->code,
                    ] : null,
                    'pattern' => $alloc->pattern ? [
                        'id' => $alloc->pattern->id,
                        'name' => $alloc->pattern->name,
                    ] : null,
                    'quantity_processed' => (int) $alloc->quantity_processed,
                    'base_rate' => (float) ($alloc->base_rate ?? 0),
                    'bonus_rate' => (float) ($alloc->bonus_rate ?? 0),
                    'calculated_wage' => (float) $alloc->calculated_wage,
                    'created_at' => $alloc->created_at ? $alloc->created_at->toIso8601String() : null,
                ];
            }),
            'pagination' => [
                'total' => $paginator->total(),
                'count' => $paginator->count(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'total_pages' => $paginator->lastPage(),
            ],
        ]);
    }
}
