<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

class CustomerLevel extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'discount_percentage',
        'default_credit_limit',
        'is_active',
        'sort_order',
        'description',
    ];

    protected $casts = [
        'discount_percentage' => 'decimal:2',
        'default_credit_limit' => 'decimal:2',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order', 'asc')->orderBy('id', 'desc');
    }
}
