<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'customer_number',
        'customer_level_id',
        'company_name',
        'gst_number',
        'contact_person',
        'mobile_number',
        'email',
        'credit_limit',
        'outstanding_amount',
        'available_credit',
        'overdue_amount',
        'allow_credit_beyond_limit',
        'credit_hold',
        'credit_hold_reason',
        'credit_hold_at',
        'credit_hold_by',
        'last_credit_review_at',
        'billing_address',
        'address',
        'city',
        'state',
        'pincode',
        'is_active',
    ];

    protected $casts = [
        'credit_limit'             => 'decimal:2',
        'outstanding_amount'       => 'decimal:2',
        'available_credit'         => 'decimal:2',
        'overdue_amount'           => 'decimal:2',
        'allow_credit_beyond_limit'=> 'boolean',
        'credit_hold'              => 'boolean',
        'credit_hold_at'           => 'datetime',
        'last_credit_review_at'    => 'datetime',
        'is_active'                => 'boolean',
    ];

    // ──────────────────────────────────────────────────────────────────────────
    // Relationships
    // ──────────────────────────────────────────────────────────────────────────

    public function level()
    {
        return $this->belongsTo(CustomerLevel::class, 'customer_level_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function creditHoldBy()
    {
        return $this->belongsTo(User::class, 'credit_hold_by');
    }

    public function carts()
    {
        return $this->hasMany(Cart::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function creditLedgers()
    {
        return $this->hasMany(CustomerCreditLedger::class)->orderByDesc('created_at');
    }

    public function activityLogs()
    {
        return $this->hasMany(CustomerActivityLog::class);
    }
}
