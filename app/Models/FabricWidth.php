<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FabricWidth extends Model
{
    use HasFactory;

    protected $table = 'fabric_widths';

    protected $fillable = [
        'name',
        'value',
        'unit',
        'status',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'status' => 'boolean',
    ];

    /**
     * Product patterns using this fabric width.
     */
    public function productPatterns()
    {
        return $this->hasMany(ManufacturingProductPattern::class, 'fabric_width_id');
    }

    /**
     * Check if this fabric width is referenced by any raw material or product pattern.
     */
    public function isInUse(): bool
    {
        $usedInRawMaterials = RawMaterial::where('standard_width', $this->value)->exists();
        $usedInPatterns = $this->productPatterns()->exists();

        return $usedInRawMaterials || $usedInPatterns;
    }

    public function scopeActive($query)
    {
        return $query->where('status', true);
    }
}
