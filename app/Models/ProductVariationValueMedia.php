<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductVariationValueMedia extends Model
{
    protected $table = 'product_variation_value_media';

    protected $fillable = [
        'product_variation_value_id',
        'file_path',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    public function value()
    {
        return $this->belongsTo(ProductVariationValue::class, 'product_variation_value_id');
    }
}
