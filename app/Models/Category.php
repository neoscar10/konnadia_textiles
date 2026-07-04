<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'parent_id',
        'name',
        'slug',
        'description',
        'is_active',
        'sort_order',
        'is_leaf',
        'default_product_config',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'is_leaf'   => 'boolean',
        'default_product_config' => 'array',
    ];

    /**
     * Relationship to parent category (folder).
     */
    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    /**
     * Relationship to direct child sub-categories (subfolders).
     */
    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id')->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Relationship to active children.
     */
    public function activeChildren()
    {
        return $this->children()->where('is_active', true);
    }

    /**
     * Products directly assigned to this (leaf) category.
     */
    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_category');
    }

    /**
     * Scope to only include root level categories.
     */
    public function scopeRoot($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Scope to only include active categories.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to only include leaf categories.
     */
    public function scopeLeaf($query)
    {
        return $query->where('is_leaf', true);
    }

    /**
     * Scope to order categories.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }
}
