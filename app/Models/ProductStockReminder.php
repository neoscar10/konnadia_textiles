<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductStockReminder extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'product_id',
        'product_combination_id',
        'product_unit_id',
        'phone_number',
        'email',
        'status',
        'notified_at',
    ];

    protected $casts = [
        'notified_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function combination(): BelongsTo
    {
        return $this->belongsTo(ProductCombination::class, 'product_combination_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(ProductUnit::class, 'product_unit_id');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}
