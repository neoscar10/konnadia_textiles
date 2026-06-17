<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title',
        'slug',
        'sku',
        'base_price',
        'description',
        'is_active',
        'stock_quantity',
        'hsn_code',
        'gst_percentage',
    ];

    protected $casts = [
        'base_price' => 'decimal:2',
        'gst_percentage' => 'decimal:2',
        'is_active' => 'boolean',
        'stock_quantity' => 'integer',
    ];

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::creating(function ($product) {
            if (empty($product->slug) && !empty($product->title)) {
                $product->slug = \Illuminate\Support\Str::slug($product->title);
            }
        });

        static::updating(function ($product) {
            if (empty($product->slug) && !empty($product->title)) {
                $product->slug = \Illuminate\Support\Str::slug($product->title);
            }
        });
    }

    /**
     * Categories relationship.
     */
    public function categories()
    {
        return $this->belongsToMany(Category::class, 'product_category');
    }

    /**
     * Media relationship.
     */
    public function media()
    {
        return $this->hasMany(ProductMedia::class)->orderBy('sort_order');
    }

    /**
     * Primary media relationship.
     */
    public function primaryMedia()
    {
        return $this->hasOne(ProductMedia::class)->where('is_primary', true);
    }

    /**
     * Variation groups relationship.
     */
    public function variationGroups()
    {
        return $this->hasMany(ProductVariationGroup::class)->orderBy('sort_order');
    }

    /**
     * Combinations relationship.
     */
    public function combinations()
    {
        return $this->hasMany(ProductCombination::class);
    }

    /**
     * Customer level price overrides relationship.
     */
    public function customerLevelPrices()
    {
        return $this->hasMany(ProductCustomerLevelPrice::class);
    }

    /**
     * Units relationship.
     */
    public function units()
    {
        return $this->hasMany(ProductUnit::class)->orderBy('level');
    }
}
