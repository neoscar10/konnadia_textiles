<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductTransfer extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'product_transfers';

    protected $fillable = [
        'transfer_number',
        'retail_shop_id',
        'created_by',
        'status',
        'transfer_date',
        'total_items',
        'total_quantity_base_units',
        'stock_deducted',
        'stock_deducted_at',
        'notes',
        'completed_at',
        'cancelled_at',
    ];

    protected $casts = [
        'transfer_date' => 'date',
        'total_items' => 'integer',
        'total_quantity_base_units' => 'decimal:4',
        'stock_deducted' => 'boolean',
        'stock_deducted_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    /**
     * Shop relationship.
     */
    public function shop()
    {
        return $this->belongsTo(RetailShop::class, 'retail_shop_id')->withTrashed();
    }

    /**
     * Alias for shop relationship.
     */
    public function retailShop()
    {
        return $this->shop();
    }

    /**
     * Created by user relationship.
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Alias for createdBy relationship.
     */
    public function creator()
    {
        return $this->createdBy();
    }

    /**
     * Virtual attribute for reference_number.
     */
    public function getReferenceNumberAttribute(): ?string
    {
        return $this->attributes['transfer_number'] ?? null;
    }

    /**
     * Items relationship.
     */
    public function items()
    {
        return $this->hasMany(ProductTransferItem::class, 'product_transfer_id');
    }
}
