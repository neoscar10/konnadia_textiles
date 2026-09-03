<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdminNotificationContact extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'phone_number',
        'is_active',
        'notify_new_orders',
        'notify_goods_transfers',
        'notify_order_dispatches',
        'notes',
    ];

    protected $casts = [
        'is_active'               => 'boolean',
        'notify_new_orders'       => 'boolean',
        'notify_goods_transfers'   => 'boolean',
        'notify_order_dispatches' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeSubscribedToNewOrders($query)
    {
        return $query->where('is_active', true)->where('notify_new_orders', true);
    }

    public function scopeSubscribedToGoodsTransfers($query)
    {
        return $query->where('is_active', true)->where('notify_goods_transfers', true);
    }

    public function scopeSubscribedToOrderDispatches($query)
    {
        return $query->where('is_active', true)->where('notify_order_dispatches', true);
    }
}
