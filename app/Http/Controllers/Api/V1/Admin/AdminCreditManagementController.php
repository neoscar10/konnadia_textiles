<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Services\Credit\CreditManagementService;
use App\Services\Credit\CustomerCreditService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AdminCreditManagementController extends Controller
{
    /**
     * Get overall credit management statistics.
     */
    public function stats(CreditManagementService $managementService): JsonResponse
    {
        $stats = $managementService->getStats();

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * List customers with credit filters.
     */
    public function index(Request $request, CreditManagementService $managementService): JsonResponse
    {
        $filters = [
            'search' => $request->query('search'),
            'level_id' => $request->query('level_id'),
            'credit_status' => $request->query('credit_status'),
            'allow_beyond_limit' => $request->query('allow_beyond_limit'),
            'credit_hold' => $request->query('credit_hold'),
            'sort_field' => $request->query('sort_field', 'company_name'),
            'sort_order' => $request->query('sort_order', 'asc'),
        ];

        $perPage = (int) $request->query('per_page', 10);
        $paginator = $managementService->list($filters, $perPage);

        return response()->json([
            'success' => true,
            'data' => $paginator->getCollection(),
            'pagination' => [
                'total' => $paginator->total(),
                'count' => $paginator->count(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'total_pages' => $paginator->lastPage(),
            ]
        ]);
    }

    /**
     * Get credit ledger for a customer.
     */
    public function ledger(int $id, Request $request, CreditManagementService $managementService): JsonResponse
    {
        $customer = Customer::with(['level', 'user'])->findOrFail($id);
        $perPage = (int) $request->query('per_page', 10);

        $ledgerPaginator = $managementService->getLedgerForCustomer($customer, [], $perPage);

        return response()->json([
            'success' => true,
            'data' => [
                'customer' => [
                    'id' => $customer->id,
                    'company_name' => $customer->company_name,
                    'customer_number' => $customer->customer_number,
                    'credit_limit' => (float) $customer->credit_limit,
                    'outstanding_amount' => (float) $customer->outstanding_amount,
                    'available_credit' => (float) ($customer->credit_limit - $customer->outstanding_amount),
                    'credit_hold' => (bool) $customer->credit_hold,
                ],
                'ledger' => $ledgerPaginator->getCollection(),
            ],
            'pagination' => [
                'total' => $ledgerPaginator->total(),
                'count' => $ledgerPaginator->count(),
                'per_page' => $ledgerPaginator->perPage(),
                'current_page' => $ledgerPaginator->currentPage(),
                'total_pages' => $ledgerPaginator->lastPage(),
            ]
        ]);
    }

    /**
     * Update customer credit limit.
     */
    public function updateLimit(Request $request, int $id, CustomerCreditService $creditService): JsonResponse
    {
        $customer = Customer::findOrFail($id);

        $validated = $request->validate([
            'credit_limit' => 'required|numeric|min:0',
            'note' => 'nullable|string|max:500',
        ]);

        try {
            $updated = $creditService->updateCreditLimit(
                $customer,
                (float) $validated['credit_limit'],
                $request->user(),
                $validated['note'] ?? null
            );

            return response()->json([
                'success' => true,
                'message' => 'Credit limit updated successfully.',
                'data' => [
                    'id' => $updated->id,
                    'credit_limit' => (float) $updated->credit_limit,
                    'outstanding_amount' => (float) $updated->outstanding_amount,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Record customer payment.
     */
    public function recordPayment(Request $request, int $id, CustomerCreditService $creditService): JsonResponse
    {
        $customer = Customer::findOrFail($id);

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'note' => 'nullable|string|max:500',
        ]);

        try {
            $updated = $creditService->recordPayment(
                $customer,
                (float) $validated['amount'],
                $request->user(),
                $validated['note'] ?? null
            );

            return response()->json([
                'success' => true,
                'message' => 'Payment recorded successfully.',
                'data' => [
                    'id' => $updated->id,
                    'outstanding_amount' => (float) $updated->outstanding_amount,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Adjust outstanding balance.
     */
    public function adjustOutstanding(Request $request, int $id, CustomerCreditService $creditService): JsonResponse
    {
        $customer = Customer::findOrFail($id);

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'direction' => 'required|in:increase,decrease',
            'note' => 'nullable|string|max:500',
        ]);

        try {
            $updated = $creditService->adjustOutstanding(
                $customer,
                (float) $validated['amount'],
                $validated['direction'],
                $request->user(),
                $validated['note'] ?? null
            );

            return response()->json([
                'success' => true,
                'message' => 'Outstanding balance adjusted successfully.',
                'data' => [
                    'id' => $updated->id,
                    'outstanding_amount' => (float) $updated->outstanding_amount,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Toggle allow credit beyond limit privilege.
     */
    public function toggleBeyondLimit(Request $request, int $id, CustomerCreditService $creditService): JsonResponse
    {
        $customer = Customer::findOrFail($id);
        $newVal = !$customer->allow_credit_beyond_limit;

        try {
            $updated = $creditService->toggleCreditBeyondLimit(
                $customer,
                $newVal,
                $request->user(),
                "Toggled allow_credit_beyond_limit to " . ($newVal ? 'true' : 'false')
            );

            return response()->json([
                'success' => true,
                'message' => 'Credit privilege updated successfully.',
                'data' => [
                    'id' => $updated->id,
                    'allow_credit_beyond_limit' => (bool) $updated->allow_credit_beyond_limit,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Place credit hold.
     */
    public function hold(Request $request, int $id, CustomerCreditService $creditService): JsonResponse
    {
        $customer = Customer::findOrFail($id);

        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        try {
            $updated = $creditService->setCreditHold(
                $customer,
                $request->user(),
                $validated['reason']
            );

            return response()->json([
                'success' => true,
                'message' => 'Credit account placed on hold successfully.',
                'data' => [
                    'id' => $updated->id,
                    'credit_hold' => true,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Release credit hold.
     */
    public function releaseHold(Request $request, int $id, CustomerCreditService $creditService): JsonResponse
    {
        $customer = Customer::findOrFail($id);

        $validated = $request->validate([
            'note' => 'nullable|string|max:500',
        ]);

        try {
            $updated = $creditService->releaseCreditHold(
                $customer,
                $request->user(),
                $validated['note'] ?? null
            );

            return response()->json([
                'success' => true,
                'message' => 'Credit hold released successfully.',
                'data' => [
                    'id' => $updated->id,
                    'credit_hold' => false,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
