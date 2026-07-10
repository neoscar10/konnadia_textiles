<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
    ];

    /**
     * Boot function to automatically generate slugs.
     */
    protected static function booted(): void
    {
        static::creating(function ($tag) {
            if (empty($tag->slug) && !empty($tag->name)) {
                $tag->slug = \Illuminate\Support\Str::slug($tag->name);
            }
        });

        static::updating(function ($tag) {
            if (empty($tag->slug) && !empty($tag->name)) {
                $tag->slug = \Illuminate\Support\Str::slug($tag->name);
            }
        });
    }

    /**
     * Products relationship.
     */
    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_tag');
    }

    /**
     * Categories relationship.
     */
    public function categories()
    {
        return $this->belongsToMany(Category::class, 'category_tag');
    }
}
