<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductVariationGroup extends Model
{
    protected $table = 'product_variation_groups';

    protected $fillable = [
        'product_id',
        'name',
        'display_type',
        'has_images',
        'sort_order',
    ];

    protected $casts = [
        'has_images' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function values()
    {
        return $this->hasMany(ProductVariationValue::class, 'product_variation_group_id')->orderBy('sort_order');
    }
}
