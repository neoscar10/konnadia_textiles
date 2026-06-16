<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'order_number',
        'user_id',
        'customer_id',
        'status',
        'checkout_method',
        'payment_status',
        'credit_status',
        'subtotal',
        'gst_amount',
        'total_amount',
        'credit_limit_at_order',
        'available_credit_at_order',
        'used_credit_override_privilege',
        'customer_notes',
        'submitted_at',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'gst_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'credit_limit_at_order' => 'decimal:2',
        'available_credit_at_order' => 'decimal:2',
        'used_credit_override_privilege' => 'boolean',
        'submitted_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function receipts()
    {
        return $this->hasMany(OrderPaymentReceipt::class);
    }

    public function statusHistories()
    {
        return $this->hasMany(OrderStatusHistory::class)->orderBy('created_at', 'desc');
    }

    /**
     * Get the human-readable status label.
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'submitted' => 'Submitted',
            'under_review' => 'Under Review',
            'pending_payment_verification' => 'Pending Payment Verification',
            'pending_credit_review' => 'Pending Credit Review',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            'dispatched' => 'Dispatched',
            'cancelled' => 'Cancelled',
            default => ucfirst(str_replace('_', ' ', $this->status)),
        };
    }

    /**
     * Get the human-readable checkout method label.
     */
    public function getCheckoutMethodLabelAttribute(): string
    {
        return match ($this->checkout_method) {
            'manual_payment' => 'Manual Payment',
            'credit' => 'Credit Purchase',
            default => ucfirst(str_replace('_', ' ', $this->checkout_method)),
        };
    }
}
