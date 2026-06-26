<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class HomeContentSection extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'type',
        'title',
        'subtitle',
        'is_active',
        'sort_order',
        'display_style',
        'items_per_view',
        'display_limit',
        'starts_at',
        'ends_at',
        'settings',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'items_per_view' => 'integer',
        'display_limit' => 'integer',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'settings' => 'array',
    ];

    public function items()
    {
        return $this->hasMany(HomeContentItem::class, 'home_content_section_id')->orderBy('sort_order');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    public function scopeCurrentlyVisible($query)
    {
        $now = now();
        return $query->where('is_active', true)
            ->where(function ($q) use ($now) {
                $q->whereNull('starts_at')
                  ->orWhere('starts_at', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('ends_at')
                  ->orWhere('ends_at', '>=', $now);
            });
    }

    public function scopeType($query, $type)
    {
        return $query->where('type', $type);
    }
}
