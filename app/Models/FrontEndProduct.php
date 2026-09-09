<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FrontEndProduct extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'sku',
        'category_id',
        'leaf_category_name',
        'is_active',
        'description',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function components()
    {
        return $this->hasMany(FrontEndProductComponent::class, 'front_end_product_id');
    }

    public function packagingItems()
    {
        return $this->hasMany(FrontEndProductPackaging::class, 'front_end_product_id');
    }

    public function finishedGoodsBatches()
    {
        return $this->hasMany(FinishedGoodsBatch::class, 'front_end_product_id');
    }

    public function getCategoryDisplayNameAttribute(): string
    {
        if ($this->category) {
            return app(\App\Services\Catalog\CategoryService::class)->buildPath($this->category);
        }
        return $this->leaf_category_name ?? 'General';
    }
}
