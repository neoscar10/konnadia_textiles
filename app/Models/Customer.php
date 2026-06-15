<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use SoftDeletes;

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
        'billing_address',
        'is_active',
    ];

    protected $casts = [
        'credit_limit' => 'decimal:2',
        'outstanding_amount' => 'decimal:2',
        'available_credit' => 'decimal:2',
        'overdue_amount' => 'decimal:2',
        'allow_credit_beyond_limit' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function level()
    {
        return $this->belongsTo(CustomerLevel::class, 'customer_level_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
