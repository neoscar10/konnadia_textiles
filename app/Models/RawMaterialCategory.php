<?php

namespace App\Models;

use App\Enums\RawMaterialUnitType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RawMaterialCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'unit_group_id',
        'unit_type',
        'description',
        'is_active',
    ];

    protected $casts = [
        'unit_type' => RawMaterialUnitType::class,
        'is_active' => 'boolean',
    ];

    /**
     * Get the unit group associated with this category.
     */
    public function unitGroup()
    {
        return $this->belongsTo(UnitGroup::class, 'unit_group_id');
    }

    /**
     * Get materials belonging to this category.
     */
    public function materials()
    {
        return $this->hasMany(RawMaterial::class, 'raw_material_category_id');
    }

    /**
     * Get tasks allowed to consume materials in this category.
     */
    public function tasks()
    {
        return $this->belongsToMany(Task::class, 'task_raw_material_category');
    }

    /**
     * Get valid units for this category's unit group or fallback unit type.
     */
    public function getValidUnitsAttribute(): array
    {
        if ($this->unitGroup) {
            return $this->unitGroup->activeUnits()->pluck('name')->toArray();
        }

        return $this->unit_type ? $this->unit_type->validUnits() : [];
    }

    /**
     * Get the default unit for this category.
     */
    public function getDefaultUnitAttribute(): string
    {
        if ($this->unitGroup && $this->unitGroup->baseUnit) {
            return $this->unitGroup->baseUnit->name;
        }

        return $this->unit_type ? $this->unit_type->defaultUnit() : 'Pieces';
    }

    /**
     * Scope: only active categories.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Check if this category represents General Overheads/Consumables.
     */
    public function isOverhead(): bool
    {
        return $this->code === 'CAT-OHD';
    }
}
