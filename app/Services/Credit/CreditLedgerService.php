<?php

namespace App\Services\Credit;

use App\Models\Customer;
use App\Models\CustomerCreditLedger;
use Illuminate\Support\Facades\Auth;

class CreditLedgerService
{
    public static function record(Customer $customer, array $payload): CustomerCreditLedger
    {
        return CustomerCreditLedger::create([
            'customer_id' => $customer->id,
            'user_id' => $payload['user_id'] ?? Auth::id(),
            'order_id' => $payload['order_id'] ?? null,
            'type' => $payload['type'],
            'direction' => $payload['direction'] ?? 'neutral',
            'amount' => $payload['amount'] ?? 0.00,
            'credit_limit_before' => $payload['credit_limit_before'] ?? $customer->credit_limit,
            'credit_limit_after' => $payload['credit_limit_after'] ?? $customer->credit_limit,
            'outstanding_before' => $payload['outstanding_before'] ?? $customer->outstanding_amount,
            'outstanding_after' => $payload['outstanding_after'] ?? $customer->outstanding_amount,
            'available_before' => $payload['available_before'] ?? $customer->available_credit,
            'available_after' => $payload['available_after'] ?? $customer->available_credit,
            'note' => $payload['note'] ?? null,
            'metadata' => $payload['metadata'] ?? null,
        ]);
    }
}
