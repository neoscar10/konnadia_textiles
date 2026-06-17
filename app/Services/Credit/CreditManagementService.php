<?php

namespace App\Services\Credit;

use App\Models\Customer;
use App\Models\CustomerCreditLedger;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class CreditManagementService
{
    protected CreditStatusService $statusService;

    public function __construct(CreditStatusService $statusService)
    {
        $this->statusService = $statusService;
    }

    /**
     * Get statistics for Credit Control dashboard.
     */
    public function getStats(): array
    {
        $customers = Customer::query()->where('is_active', true)->get();

        $totalLimit = 0.0;
        $totalOutstanding = 0.0;
        $totalAvailable = 0.0;
        $totalOverdue = 0.0;
        $nearLimitCount = 0;
        $overLimitCount = 0;
        $onHoldCount = 0;
        $extendedCreditCount = 0;

        foreach ($customers as $customer) {
            $limit = (float) $customer->credit_limit;
            $outstanding = (float) $customer->outstanding_amount;
            $available = (float) $customer->available_credit;
            $overdue = (float) $customer->overdue_amount;

            $totalLimit += $limit;
            $totalOutstanding += $outstanding;
            $totalAvailable += $available;
            $totalOverdue += $overdue;

            if ($customer->credit_hold) {
                $onHoldCount++;
            }

            if ($customer->allow_credit_beyond_limit) {
                $extendedCreditCount++;
            }

            if (!$customer->credit_hold && $limit > 0) {
                if ($outstanding > $limit) {
                    $overLimitCount++;
                } elseif ($outstanding >= $limit * 0.85) {
                    $nearLimitCount++;
                }
            }
        }

        return [
            'total_credit_limit' => $totalLimit,
            'total_outstanding' => $totalOutstanding,
            'total_available' => $totalAvailable,
            'total_overdue' => $totalOverdue,
            'near_limit_count' => $nearLimitCount,
            'over_limit_count' => $overLimitCount,
            'on_hold_count' => $onHoldCount,
            'extended_credit_count' => $extendedCreditCount,
        ];
    }

    /**
     * List customers with sorting, pagination, and filters.
     */
    public function list(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $query = Customer::query()->with(['level', 'user']);

        // Search
        if (!empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $query->where(function ($q) use ($search) {
                $q->where('company_name', 'like', $search)
                  ->orWhere('customer_number', 'like', $search)
                  ->orWhere('contact_person', 'like', $search)
                  ->orWhere('email', 'like', $search);
            });
        }

        // Customer Level
        if (!empty($filters['level_id'])) {
            $query->where('customer_level_id', $filters['level_id']);
        }

        // Allow beyond limit
        if (isset($filters['allow_beyond_limit']) && $filters['allow_beyond_limit'] !== '') {
            $query->where('allow_credit_beyond_limit', (bool) $filters['allow_beyond_limit']);
        }

        // Credit hold
        if (isset($filters['credit_hold']) && $filters['credit_hold'] !== '') {
            $query->where('credit_hold', (bool) $filters['credit_hold']);
        }

        // Filter by calculated credit status
        if (!empty($filters['credit_status'])) {
            $status = $filters['credit_status'];
            if ($status === 'on_hold') {
                $query->where('credit_hold', true);
            } elseif ($status === 'no_credit') {
                $query->where('credit_hold', false)->where('credit_limit', '<=', 0);
            } elseif ($status === 'over_limit') {
                $query->where('credit_hold', false)
                      ->where('credit_limit', '>', 0)
                      ->whereColumn('outstanding_amount', '>', 'credit_limit');
            } elseif ($status === 'near_limit') {
                $query->where('credit_hold', false)
                      ->where('credit_limit', '>', 0)
                      ->whereColumn('outstanding_amount', '>=', DB::raw('credit_limit * 0.85'))
                      ->whereColumn('outstanding_amount', '<=', 'credit_limit');
            } elseif ($status === 'healthy') {
                $query->where('credit_hold', false)
                      ->where('credit_limit', '>', 0)
                      ->whereColumn('outstanding_amount', '<', DB::raw('credit_limit * 0.85'));
            }
        }

        // Sorting
        $sortField = $filters['sort_field'] ?? 'company_name';
        $sortOrder = $filters['sort_order'] ?? 'asc';
        
        $allowedSorts = ['company_name', 'credit_limit', 'outstanding_amount', 'available_credit', 'overdue_amount', 'created_at'];
        if (in_array($sortField, $allowedSorts)) {
            $query->orderBy($sortField, $sortOrder);
        } else {
            $query->orderBy('company_name', 'asc');
        }

        return $query->paginate($perPage);
    }

    /**
     * Get a full detailed credit profile for the modal.
     */
    public function getCustomerCreditProfile(Customer $customer): array
    {
        $status = $this->statusService->getStatus($customer);
        $risk = $this->statusService->getRiskLevel($customer);
        $actions = $this->statusService->getAvailableActions($customer);

        return [
            'customer' => $customer->load(['level', 'user', 'creditHoldBy']),
            'status' => $status,
            'risk' => $risk,
            'actions' => $actions,
            'recent_ledgers' => $customer->creditLedgers()->take(5)->get(),
        ];
    }

    /**
     * Get paginated ledger entries for a customer.
     */
    public function getLedgerForCustomer(Customer $customer, array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $query = CustomerCreditLedger::query()
            ->where('customer_id', $customer->id)
            ->with(['user', 'order'])
            ->orderBy('created_at', 'desc');

        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        return $query->paginate($perPage);
    }
}
