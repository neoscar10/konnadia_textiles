<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StorefrontProductBundleItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'storefront_product_bundle_id',
        'production_batch_id',
        'manufacturing_product_id',
        'quantity_used',
    ];

    public function bundle()
    {
        return $this->belongsTo(StorefrontProductBundle::class, 'storefront_product_bundle_id');
    }

    public function productionBatch()
    {
        return $this->belongsTo(ProductionBatch::class, 'production_batch_id');
    }

    public function manufacturingProduct()
    {
        return $this->belongsTo(ManufacturingProduct::class, 'manufacturing_product_id');
    }
}
