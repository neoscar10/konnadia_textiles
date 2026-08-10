<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StorefrontProductBundle extends Model
{
    use HasFactory;

    protected $fillable = [
        'bundle_code',
        'product_id',
        'product_combination_id',
        'created_by',
        'quantity_created',
        'notes',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function productCombination()
    {
        return $this->belongsTo(ProductCombination::class, 'product_combination_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items()
    {
        return $this->hasMany(StorefrontProductBundleItem::class, 'storefront_product_bundle_id');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($bundle) {
            if (empty($bundle->bundle_code)) {
                $latestId = static::max('id') ?? 0;
                $bundle->bundle_code = 'BNDL-' . str_pad($latestId + 1, 4, '0', STR_PAD_LEFT);
            }
        });
    }
}
