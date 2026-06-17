<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerCreditLedger extends Model
{
    protected $fillable = [
        'customer_id',
        'user_id',
        'order_id',
        'type',
        'direction',
        'amount',
        'credit_limit_before',
        'credit_limit_after',
        'outstanding_before',
        'outstanding_after',
        'available_before',
        'available_after',
        'note',
        'metadata',
    ];

    protected $casts = [
        'amount'              => 'decimal:2',
        'credit_limit_before' => 'decimal:2',
        'credit_limit_after'  => 'decimal:2',
        'outstanding_before'  => 'decimal:2',
        'outstanding_after'   => 'decimal:2',
        'available_before'    => 'decimal:2',
        'available_after'     => 'decimal:2',
        'metadata'            => 'array',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Human-readable type label.
     */
    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'order_credit'             => 'Credit Order',
            'payment_received'         => 'Payment Received',
            'adjustment_increase'      => 'Adjustment (Increase)',
            'adjustment_decrease'      => 'Adjustment (Decrease)',
            'credit_limit_change'      => 'Credit Limit Change',
            'credit_hold'              => 'Credit Hold Applied',
            'credit_release'           => 'Credit Hold Released',
            'credit_privilege_changed' => 'Privilege Changed',
            'reversal'                 => 'Credit Reversal',
            default                    => ucwords(str_replace('_', ' ', $this->type)),
        };
    }

    /**
     * Badge colour for this type.
     */
    public function getDirectionBadgeAttribute(): string
    {
        return match ($this->direction) {
            'debit'   => 'error',
            'credit'  => 'success',
            default   => 'neutral',
        };
    }
}
