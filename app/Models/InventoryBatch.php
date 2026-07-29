<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'raw_material_id',
        'batch_number',
        'received_quantity',
        'balance_quantity',
        'unit',
        'unit_cost',
    ];

    protected $casts = [
        'received_quantity' => 'decimal:2',
        'balance_quantity' => 'decimal:2',
        'unit_cost' => 'decimal:2',
    ];

    /**
     * Get the raw material associated with this batch.
     */
    public function rawMaterial()
    {
        return $this->belongsTo(RawMaterial::class);
    }

    /**
     * Get consumptions recorded for this batch.
     */
    public function consumptions()
    {
        return $this->hasMany(JobMaterialConsumption::class);
    }
}
