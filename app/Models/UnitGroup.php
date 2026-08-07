<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UnitGroup extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get units belonging to this unit group.
     */
    public function units()
    {
        return $this->hasMany(Unit::class)->orderBy('is_base', 'desc')->orderBy('name');
    }

    /**
     * Get active units belonging to this group.
     */
    public function activeUnits()
    {
        return $this->hasMany(Unit::class)->where('is_active', true)->orderBy('is_base', 'desc')->orderBy('name');
    }

    /**
     * Get the base unit for this group.
     */
    public function baseUnit()
    {
        return $this->hasOne(Unit::class)->where('is_base', true);
    }

    /**
     * Scope: active unit groups.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
