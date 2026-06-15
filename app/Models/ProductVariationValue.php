<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductVariationValue extends Model
{
    protected $table = 'product_variation_values';

    protected $fillable = [
        'product_variation_group_id',
        'value',
        'color_hex',
        'is_default',
        'sort_order',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function group()
    {
        return $this->belongsTo(ProductVariationGroup::class, 'product_variation_group_id');
    }

    public function media()
    {
        return $this->hasMany(ProductVariationValueMedia::class, 'product_variation_value_id')->orderBy('sort_order');
    }
}
