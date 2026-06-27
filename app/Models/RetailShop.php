<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RetailShop extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'retail_shops';

    protected $fillable = [
        'shop_code',
        'name',
        'address',
        'city',
        'state',
        'pincode',
        'contact_person',
        'contact_phone',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Scope to only include active shops.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Transfers relationship.
     */
    public function transfers()
    {
        return $this->hasMany(ProductTransfer::class, 'retail_shop_id');
    }
}
