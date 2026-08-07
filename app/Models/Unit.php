<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Unit extends Model
{
    use HasFactory;

    protected $fillable = [
        'unit_group_id',
        'name',
        'short_code',
        'is_base',
        'ratio_to_base',
        'is_active',
    ];

    protected $casts = [
        'is_base' => 'boolean',
        'is_active' => 'boolean',
        'ratio_to_base' => 'float',
    ];

    /**
     * Get the unit group this unit belongs to.
     */
    public function unitGroup()
    {
        return $this->belongsTo(UnitGroup::class);
    }

    /**
     * Scope: active units.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Convert quantity of this unit to base unit.
     */
    public function toBaseQuantity(float $quantity): float
    {
        return $quantity * (float) $this->ratio_to_base;
    }

    /**
     * Convert quantity from base unit to this unit.
     */
    public function fromBaseQuantity(float $baseQuantity): float
    {
        $ratio = (float) $this->ratio_to_base;
        return $ratio > 0 ? ($baseQuantity / $ratio) : $baseQuantity;
    }
}
