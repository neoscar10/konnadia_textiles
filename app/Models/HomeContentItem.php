<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HomeContentItem extends Model
{
    protected $fillable = [
        'home_content_section_id',
        'item_type',
        'product_id',
        'category_id',
        'title',
        'subtitle',
        'cta_label',
        'image_path',
        'image_alt',
        'link_type',
        'link_category_id',
        'link_product_id',
        'external_url',
        'sort_order',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'metadata' => 'array',
    ];

    public function section()
    {
        return $this->belongsTo(HomeContentSection::class, 'home_content_section_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function linkCategory()
    {
        return $this->belongsTo(Category::class, 'link_category_id');
    }

    public function linkProduct()
    {
        return $this->belongsTo(Product::class, 'link_product_id');
    }
}
