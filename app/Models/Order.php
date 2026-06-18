<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use HasFactory, SoftDeletes;

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
        'admin_note',
        'rejection_reason',
        'approved_at',
        'rejected_at',
        'dispatched_at',
        'submitted_at',
        'stock_deducted_at',
        'credit_applied_at',
        'credit_reversed_at',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'gst_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'credit_limit_at_order' => 'decimal:2',
        'available_credit_at_order' => 'decimal:2',
        'used_credit_override_privilege' => 'boolean',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'dispatched_at' => 'datetime',
        'submitted_at' => 'datetime',
        'stock_deducted_at' => 'datetime',
        'credit_applied_at' => 'datetime',
        'credit_reversed_at' => 'datetime',
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

    public function receipt()
    {
        return $this->hasOne(OrderPaymentReceipt::class)->latestOfMany();
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
            'pending_approval' => 'Pending Approval',
            'pending_payment_verification' => 'Pending Payment Verification',
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

    /**
     * Scopes
     */
    public function scopeForCustomer($query, $customer)
    {
        $id = $customer instanceof Customer ? $customer->id : $customer;
        return $query->where('customer_id', $id);
    }

    public function scopeStatus($query, $status)
    {
        if ($status && $status !== 'all') {
            return $query->where('status', $status);
        }
        return $query;
    }

    public function scopeCheckoutMethod($query, $method)
    {
        if ($method && $method !== 'all') {
            return $query->where('checkout_method', $method);
        }
        return $query;
    }

    public function scopePaymentStatus($query, $status)
    {
        if ($status && $status !== 'all') {
            return $query->where('payment_status', $status);
        }
        return $query;
    }

    public function scopeCreditStatus($query, $status)
    {
        if ($status && $status !== 'all') {
            return $query->where('credit_status', $status);
        }
        return $query;
    }

    public function scopeDateRange($query, $from, $to)
    {
        if ($from) {
            $query->whereDate('created_at', '>=', $from);
        }
        if ($to) {
            $query->whereDate('created_at', '<=', $to);
        }
        return $query;
    }

    public function scopeSearch($query, $search)
    {
        if ($search) {
            $search = trim($search);
            return $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhereHas('customer', function ($sub) use ($search) {
                      $sub->where('company_name', 'like', "%{$search}%")
                          ->orWhere('customer_number', 'like', "%{$search}%");
                  });
            });
        }
        return $query;
    }

    public function scopeLatestFirst($query)
    {
        return $query->orderBy('created_at', 'desc');
    }
}
