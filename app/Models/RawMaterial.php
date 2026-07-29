<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RawMaterial extends Model
{
    use HasFactory;

    protected $fillable = [
        'raw_material_category_id',
        'name',
        'code',
        'unit',
    ];

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
}
