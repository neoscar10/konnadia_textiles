<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryBaleItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'inventory_bale_id',
        'raw_material_id',
        'item_name',
        'design_number',
        'stock_id',
        'declared_length',
        'cost_per_unit',
        'total_cost',
        'photo_path',
    ];

    protected $casts = [
        'declared_length' => 'float',
        'cost_per_unit' => 'decimal:2',
        'total_cost' => 'decimal:2',
    ];

    public function bale()
    {
        return $this->belongsTo(InventoryBale::class, 'inventory_bale_id');
    }

    public function rawMaterial()
    {
        return $this->belongsTo(RawMaterial::class, 'raw_material_id');
    }
}
