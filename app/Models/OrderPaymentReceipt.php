<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderPaymentReceipt extends Model
{
    protected $fillable = [
        'order_id',
        'file_path',
        'original_name',
        'mime_type',
        'size',
        'status',
        'admin_note',
        'verified_at',
        'rejected_at',
    ];

    protected $casts = [
        'size' => 'integer',
        'verified_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
