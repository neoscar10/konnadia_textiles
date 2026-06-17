<?php

namespace App\Services\Credit;

use App\Models\Customer;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CreditExportService
{
    /**
     * Stream CSV export of customer credit records based on filters.
     */
    public function exportCsv(array $filters = []): StreamedResponse
    {
        $query = Customer::query()->with(['level']);

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

        // Sorting (default to company_name asc for exports)
        $query->orderBy('company_name', 'asc');

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="credit-management-' . date('Y-m-d') . '.csv"',
            'Cache-Control'       => 'no-cache, must-revalidate',
            'Pragma'              => 'no-cache',
            'Expires'             => '0',
        ];

        $callback = function () use ($query) {
            $file = fopen('php://output', 'w');
            
            // Add UTF-8 BOM for Excel
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($file, [
                'Customer Number',
                'Company Name',
                'Contact Person',
                'Email',
                'Customer Level',
                'Credit Limit (INR)',
                'Outstanding (INR)',
                'Available Credit (INR)',
                'Overdue Amount (INR)',
                'Beyond Limit Allowed',
                'Credit Hold Status',
                'Hold Reason',
                'Last Review Date',
            ]);

            $query->chunk(100, function ($customers) use ($file) {
                foreach ($customers as $customer) {
                    fputcsv($file, [
                        $customer->customer_number,
                        $customer->company_name,
                        $customer->contact_person,
                        $customer->email,
                        $customer->level?->name ?: 'N/A',
                        number_format((float) $customer->credit_limit, 2, '.', ''),
                        number_format((float) $customer->outstanding_amount, 2, '.', ''),
                        number_format((float) $customer->available_credit, 2, '.', ''),
                        number_format((float) $customer->overdue_amount, 2, '.', ''),
                        $customer->allow_credit_beyond_limit ? 'Yes' : 'No',
                        $customer->credit_hold ? 'ON HOLD' : 'Active',
                        $customer->credit_hold_reason ?: '',
                        $customer->last_credit_review_at ? $customer->last_credit_review_at->format('Y-m-d H:i') : '',
                    ]);
                }
            });

            fclose($file);
        };

        return new StreamedResponse($callback, 200, $headers);
    }
}
