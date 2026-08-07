<?php

namespace App\Models;

use App\Enums\RawMaterialUnitType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RawMaterial extends Model
{
    use HasFactory;

    protected $fillable = [
        'raw_material_category_id',
        'unit_group_id',
        'unit_id',
        'name',
        'code',
        'unit',
        'standard_width',
        'width_unit',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'standard_width' => 'float',
    ];

    protected static function boot()
    {
        parent::boot();

        // Auto-generate RM-XXXX code on creation
        static::creating(function ($material) {
            if (empty($material->code)) {
                $latestId = static::max('id') ?? 0;
                $material->code = 'RM-' . str_pad($latestId + 1, 4, '0', STR_PAD_LEFT);
            }
        });
    }

    /**
     * Get the unit group of this raw material.
     */
    public function unitGroup()
    {
        return $this->belongsTo(UnitGroup::class, 'unit_group_id');
    }

    /**
     * Get the unit model of this raw material.
     */
    public function unitModel()
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    /**
     * Get the category of this raw material.
     */
    public function category()
    {
        return $this->belongsTo(RawMaterialCategory::class, 'raw_material_category_id');
    }

    /**
     * Get inventory batches for this raw material.
     */
    public function batches()
    {
        return $this->hasMany(InventoryBatch::class);
    }

    /**
     * Scope: only active materials.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: search by name or code.
     */
    public function scopeSearch($query, string $term)
    {
        if (empty($term)) {
            return $query;
        }

        return $query->where(function ($q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
              ->orWhere('code', 'like', "%{$term}%");
        });
    }

    /**
     * Check if this material's unit is valid for its category.
     */
    public function isUnitValidForCategory(): bool
    {
        if (!$this->category) {
            return true;
        }

        return in_array($this->unit, $this->category->valid_units);
    }

    /**
     * Helper to convert a quantity from this material's default unit to another unit.
     */
    public function convertQuantity(float $quantity, string $targetUnit): float
    {
        return $quantity * RawMaterialUnitType::getConversionRate($this->unit, $targetUnit);
    }

    /**
     * Check if this raw material represents General Overheads/Consumables.
     */
    public function isOverhead(): bool
    {
        return $this->category ? $this->category->isOverhead() : false;
    }
}
