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

    /**
     * Safely create or update a FrontEndProduct for a Category, restoring soft-deleted records and preventing SKU collisions.
     */
    public static function saveConfigForCategory(Category $category, array $attributes = []): static
    {
        $categoryId = $category->id;
        $categoryName = trim($category->name);

        $feProduct = static::withTrashed()->where('category_id', $categoryId)->first();

        $baseSku = "CAT-CFG-" . str_pad((string) $categoryId, 4, '0', STR_PAD_LEFT);
        $sku = $baseSku;
        $counter = 1;

        while (static::withTrashed()->where('sku', $sku)->where('id', '!=', $feProduct?->id)->exists()) {
            $sku = $baseSku . '-' . $counter;
            $counter++;
        }

        $data = array_merge([
            'name' => $categoryName,
            'sku' => $sku,
            'leaf_category_name' => $categoryName,
            'is_active' => true,
        ], $attributes);

        if ($feProduct) {
            if ($feProduct->trashed()) {
                $feProduct->restore();
            }
            $feProduct->update($data);
        } else {
            $data['category_id'] = $categoryId;
            $feProduct = static::create($data);
        }

        return $feProduct;
    }
}
